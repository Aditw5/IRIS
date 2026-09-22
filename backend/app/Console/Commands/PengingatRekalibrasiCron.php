<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PengingatRekalibrasiCron extends Command
{
    protected $signature = 'pengingat-rekalibrasi:cron {--dry-run : Tampilkan calon notifikasi tanpa mengirim WhatsApp}';
    protected $description = 'Kirim pengingat WA rekalibrasi alat standar H-30, H-15, H-7, dan alat yang sudah lewat due date.';

    protected array $reminderDays = [30, 15, 7];

    public function handle()
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::now($timezone)->startOfDay();
        $dryRun = (bool) $this->option('dry-run');
        [$minDelaySeconds, $maxDelaySeconds] = $this->resolveSendDelayRange();

        $targetDates = collect($this->reminderDays)
            ->mapWithKeys(function ($days) use ($today) {
                return [$today->copy()->addDays($days)->toDateString() => $days];
            });

        Log::info('PengingatRekalibrasiCron START', [
            'tanggal' => $today->toDateString(),
            'target_dates' => $targetDates->keys()->values()->all(),
            'include_overdue_until' => $today->toDateString(),
            'dry_run' => $dryRun,
            'send_delay_seconds' => [
                'min' => $minDelaySeconds,
                'max' => $maxDelaySeconds,
            ],
        ]);

        $data = DB::table('mapalatstandar_m as mp')
            ->leftJoin('mitra_m as mt', function ($join) {
                $join->on(DB::raw('CAST(mt.id AS INTEGER)'), '=', 'mp.standarmilikfk');
            })
            ->leftJoin('lingkupkalibrasi_m as lk', 'lk.id', '=', 'mp.lingkupfk')
            ->select(
                'mp.id',
                'mp.namaalatstandar',
                'mp.namamerk',
                'mp.namatipe',
                'mp.namaserialnumber',
                'mp.duedate',
                'mp.calldate',
                'mp.standarmilikfk',
                'mp.lingkupfk',
                'mt.namaperusahaan',
                'lk.lingkupkalibrasi'
            )
            ->where('mp.statusenabled', true)
            ->where('mp.pengingat_rekalibrasi_aktif', true)
            ->whereNotNull('mp.duedate')
            ->whereNotNull('mp.lingkupfk')
            ->where(function ($q) use ($today, $targetDates) {
                $q->whereDate('mp.duedate', '<=', $today->toDateString())
                    ->orWhereIn(DB::raw('DATE(mp.duedate)'), $targetDates->keys()->values()->all());
            })
            ->orderBy('mp.duedate')
            ->orderBy('mp.namaalatstandar')
            ->get();

        Log::info('PengingatRekalibrasiCron: total alat target = ' . $data->count());
        $this->info('Total alat target: ' . $data->count());

        if ($data->isEmpty()) {
            Log::info('PengingatRekalibrasiCron: Tidak ada alat jatuh tempo reminder. END');
            return 0;
        }

        $sent = 0;
        $skipped = 0;
        $sendAttempts = 0;

        foreach ($data as $alat) {
            $dueDate = Carbon::parse($alat->duedate, $timezone)->startOfDay();
            $daysBefore = (int) $today->diffInDays($dueDate, false);

            if ($daysBefore > 0 && !in_array($daysBefore, $this->reminderDays, true)) {
                $skipped++;
                continue;
            }

            $lokasiId = $this->resolveLokasiId($alat->standarmilikfk, $alat->namaperusahaan);
            if (empty($lokasiId)) {
                $skipped++;
                Log::warning('PengingatRekalibrasiCron: lokasi alat standar tidak dikenali', [
                    'alat_id' => $alat->id,
                    'standarmilikfk' => $alat->standarmilikfk,
                    'namaperusahaan' => $alat->namaperusahaan,
                ]);
                continue;
            }

            $recipients = $this->getRecipients($lokasiId, $alat->lingkupkalibrasi);
            if ($recipients->isEmpty()) {
                $skipped++;
                Log::warning('PengingatRekalibrasiCron: penerima tidak ditemukan', [
                    'alat_id' => $alat->id,
                    'lokasi_id' => $lokasiId,
                    'lingkup' => $alat->lingkupkalibrasi,
                ]);
                continue;
            }

            foreach ($recipients as $recipient) {
                $cacheKey = implode(':', [
                    'pengingat_rekalibrasi',
                    $today->toDateString(),
                    $alat->id,
                    $recipient->id,
                    $daysBefore,
                ]);

                if (!$dryRun && Cache::has($cacheKey)) {
                    $skipped++;
                    continue;
                }

                $pesan = $this->buildPesan($alat, $recipient, $daysBefore, $dueDate);

                if ($dryRun) {
                    $this->line("[DRY RUN] {$alat->id} {$alat->namaalatstandar} -> {$recipient->namalengkap} ({$recipient->namajabatanulab})");
                    Log::info('PengingatRekalibrasiCron DRY RUN', [
                        'alat_id' => $alat->id,
                        'pegawai_id' => $recipient->id,
                        'days_before' => $daysBefore,
                    ]);
                    $sent++;
                    continue;
                }

                if ($sendAttempts > 0) {
                    $this->waitBeforeNextSend(
                        $minDelaySeconds,
                        $maxDelaySeconds,
                        $alat->id,
                        $recipient->id
                    );
                }

                $result = $this->kirimWhatsappNotifikasi($recipient->nohandphone, $pesan);
                $sendAttempts++;
                if (isset($result['status']) && $result['status'] != false) {
                    Cache::put($cacheKey, true, now()->addDays(2));
                    $sent++;
                    Log::info('PengingatRekalibrasiCron: WA rekalibrasi terkirim', [
                        'alat_id' => $alat->id,
                        'pegawai_id' => $recipient->id,
                        'days_before' => $daysBefore,
                    ]);
                } else {
                    $skipped++;
                    Log::warning('PengingatRekalibrasiCron: WA rekalibrasi gagal', [
                        'alat_id' => $alat->id,
                        'pegawai_id' => $recipient->id,
                        'message' => $result['message'] ?? 'Tidak diketahui',
                    ]);
                }
            }
        }

        Log::info('PengingatRekalibrasiCron END', [
            'sent' => $sent,
            'skipped' => $skipped,
            'send_attempts' => $sendAttempts,
            'dry_run' => $dryRun,
        ]);
        $this->info("Selesai. sent={$sent}, skipped={$skipped}, attempts={$sendAttempts}, dry_run=" . ($dryRun ? 'true' : 'false'));

        return 0;
    }

    protected function resolveSendDelayRange(): array
    {
        // Minimal satu menit agar konfigurasi yang keliru tidak kembali membuat burst pengiriman.
        $min = max(60, (int) config('whatsapp.rekalibrasi_min_delay_seconds', 90));
        $max = max($min, (int) config('whatsapp.rekalibrasi_max_delay_seconds', 150));

        return [$min, $max];
    }

    protected function waitBeforeNextSend(int $minSeconds, int $maxSeconds, $alatId, $pegawaiId): void
    {
        $delaySeconds = random_int($minSeconds, $maxSeconds);

        $this->line("Menunggu {$delaySeconds} detik sebelum pengiriman WA berikutnya...");
        Log::info('PengingatRekalibrasiCron: throttle sebelum kirim WA berikutnya', [
            'delay_seconds' => $delaySeconds,
            'next_alat_id' => $alatId,
            'next_pegawai_id' => $pegawaiId,
        ]);

        sleep($delaySeconds);
    }

    protected function getRecipients(int $lokasiId, ?string $lingkup)
    {
        $lingkup = trim((string) $lingkup);
        if ($lingkup === '') {
            return collect();
        }

        return $this->recipientQuery($lokasiId)
            ->get()
            ->filter(function ($pegawai) use ($lingkup) {
                return $this->jabatanMatchesLingkup($pegawai->namajabatanulab ?? '', $lingkup);
            })
            ->unique('id')
            ->values();
    }

    protected function recipientQuery(int $lokasiId)
    {
        return DB::table('pegawai_m as pg')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->leftJoin('jabatan_m as jb2', 'jb2.id', '=', 'pg.jabatan2fk')
            ->select(
                'pg.id',
                'pg.namalengkap',
                'pg.nohandphone',
                'pg.lokasikalibrasifk',
                DB::raw("CONCAT_WS(' ', jb1.namajabatanulab, jb2.namajabatanulab) as namajabatanulab")
            )
            ->where('pg.statusenabled', true)
            ->where('pg.statuspegawaifk', 1)
            ->where(function ($q) {
                $q->whereNull('pg.tglkeluar')
                    ->orWhereDate('pg.tglkeluar', '>=', now()->toDateString());
            })
            ->where('pg.lokasikalibrasifk', $lokasiId)
            ->whereNotNull('pg.nohandphone')
            ->where(function ($q) {
                $q->where('jb1.statusenabled', true)
                    ->orWhere('jb2.statusenabled', true);
            })
            ->where(function ($q) {
                $q->where('jb1.namajabatanulab', 'ilike', '%Penyelia%')
                    ->orWhere('jb1.namajabatanulab', 'ilike', '%Pelaksana%')
                    ->orWhere('jb2.namajabatanulab', 'ilike', '%Penyelia%')
                    ->orWhere('jb2.namajabatanulab', 'ilike', '%Pelaksana%');
            });
    }

    protected function jabatanMatchesLingkup(string $jabatan, string $lingkup): bool
    {
        $jabatanNorm = $this->normalizeText($jabatan);
        if (
            strpos($jabatanNorm, 'penyelia') === false
            && strpos($jabatanNorm, 'pelaksana') === false
        ) {
            return false;
        }

        foreach ($this->lingkupKeywords($lingkup) as $keyword) {
            $keywordNorm = $this->normalizeText($keyword);
            if ($keywordNorm !== '' && strpos($jabatanNorm, $keywordNorm) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function lingkupKeywords(string $lingkup): array
    {
        $lingkupNorm = $this->normalizeText($lingkup);
        $map = [
            'kelistrikan' => ['kelistrikan'],
            'tekanan' => ['tekanan'],
            'waktu dan frekuensi' => ['waktu dan frekuensi', 'waktu', 'frekuensi'],
            'suhu dan kelembaban' => ['suhu dan kelembaban', 'suhu', 'kelembaban'],
            'vibrasi' => ['vibrasi'],
            'dimensi' => ['dimensi'],
            'gaya' => ['gaya'],
            'repair' => ['repair'],
            'vendor' => ['vendor'],
        ];

        foreach ($map as $key => $keywords) {
            if ($lingkupNorm === $key || strpos($lingkupNorm, $key) !== false) {
                return $keywords;
            }
        }

        return [$lingkup];
    }

    protected function normalizeText(?string $value): string
    {
        $value = strtolower((string) $value);
        $value = str_replace('&', ' dan ', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    protected function resolveLokasiId($standarMilikFk, ?string $namaPerusahaan): ?int
    {
        $nama = $this->normalizeText($namaPerusahaan);

        if (strpos($nama, 'gresik') !== false || (string) $standarMilikFk === '98') {
            return 2;
        }

        if (
            strpos($nama, 'jakarta') !== false
            || strpos($nama, 'jkt') !== false
            || (string) $standarMilikFk === '97'
        ) {
            return 1;
        }

        return null;
    }

    protected function buildPesan($alat, $recipient, int $daysBefore, Carbon $dueDate): string
    {
        $namaPenerima = $recipient->namalengkap ?? '-';
        $namaAlat = $alat->namaalatstandar ?: '-';
        $merk = $alat->namamerk ?: '-';
        $tipe = $alat->namatipe ?: '-';
        $sn = $alat->namaserialnumber ?: '-';
        $unit = $alat->namaperusahaan ?: '-';
        $lingkup = $alat->lingkupkalibrasi ?: '-';
        $dueDateStr = $dueDate->locale('id')->translatedFormat('d F Y');
        $calDateStr = !empty($alat->calldate)
            ? Carbon::parse($alat->calldate)->locale('id')->translatedFormat('d F Y')
            : '-';
        $statusTempo = $this->buildStatusTempoText($daysBefore);

        return "Halo Tim Hebat U-LAB !\n\n"
            . "Yth. {$namaPenerima}\n"
            . "Kami informasikan bahwa,\n\n"
            . "Alat Standar : *{$namaAlat}*\n"
            . "Merk/Tipe : *{$merk}/{$tipe}*\n"
            . "Serial Number : *{$sn}*\n"
            . "Standar Milik : *{$unit}*\n"
            . "Lingkup : *{$lingkup}*\n"
            . "Cal Date : *{$calDateStr}*\n"
            . "Due Date Rekalibrasi : *{$dueDateStr}*\n"
            . "{$statusTempo}\n\n"
            . "Mohon segera dijadwalkan proses rekalibrasi alat standar tersebut.\n\n"
            . "Salam,\n"
            . "U-LAB ! Cepat, Tepat, Akurat 100%";
    }

    protected function buildStatusTempoText(int $daysBefore): string
    {
        if ($daysBefore > 0) {
            return "Sisa waktu : *{$daysBefore} hari lagi*";
        }

        if ($daysBefore === 0) {
            return "Status : *Jatuh tempo hari ini*";
        }

        return "Status : *Terlambat " . abs($daysBefore) . " hari dari due date*";
    }

    public function kirimWhatsappNotifikasi($nohp, $pesan)
    {
        try {
            if (empty($nohp)) {
                Log::warning('WA tidak dikirim: nomor HP kosong', [
                    'pesan' => $pesan,
                ]);
                return [
                    'status' => false,
                    'message' => 'Nomor HP kosong',
                    'skip' => true,
                ];
            }

            $nomor = preg_replace('/^0/', '62', $nohp);
            $nomor = preg_replace('/[^0-9]/', '', $nomor);
            if (strlen($nomor) < 10) {
                Log::warning('WA tidak dikirim: nomor HP tidak valid', [
                    'nohp_asli' => $nohp,
                    'nomor_format' => $nomor,
                    'pesan' => $pesan,
                ]);
                return [
                    'status' => false,
                    'message' => 'Nomor HP tidak valid',
                    'skip' => true,
                ];
            }

            $token = env('WABLAS_TOKEN', 'jEdiLI6VSWDzZfTQV2WKtkV6iA8eNTzhGTREoAOEhmpjMTM7uIaM5ll');
            $secretKey = env('WABLAS_SECRET_KEY', 'EpxyT46x');
            $url = env('WABLAS_URL', 'https://sby.wablas.com/api/send-message');

            if (empty($token) || empty($secretKey)) {
                Log::warning('WA tidak dikirim: token atau secret Wablas kosong', [
                    'phone' => $nomor,
                ]);
                return [
                    'status' => false,
                    'message' => 'Token atau secret Wablas kosong',
                ];
            }

            $response = Http::withOptions([
                'timeout' => 10,
                'connect_timeout' => 5,
            ])->get($url, [
                'token' => $token . '.' . $secretKey,
                'phone' => $nomor,
                'message' => $pesan,
            ]);

            if (!$response->successful()) {
                Log::warning('WA gagal dikirim: HTTP Wablas tidak sukses', [
                    'phone' => $nomor,
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return [
                    'status' => false,
                    'message' => 'HTTP Wablas tidak sukses',
                    'http_status' => $response->status(),
                ];
            }

            $result = $response->json();
            if (!is_array($result)) {
                Log::warning('WA gagal dikirim: response Wablas bukan JSON valid', [
                    'phone' => $nomor,
                    'response' => $response->body(),
                ]);
                return [
                    'status' => false,
                    'message' => 'Response Wablas tidak valid',
                ];
            }

            if (!isset($result['status']) || $result['status'] == false) {
                Log::warning('WA gagal dikirim dari Wablas', [
                    'phone' => $nomor,
                    'message' => $result['message'] ?? 'Tidak diketahui',
                    'response' => $result,
                ]);
                return [
                    'status' => false,
                    'message' => $result['message'] ?? 'Gagal kirim WhatsApp',
                    'response' => $result,
                ];
            }

            Log::info('WA berhasil dikirim', [
                'phone' => $nomor,
                'response' => $result,
            ]);
            return $result;
        } catch (\Throwable $e) {
            Log::error('WA error tapi proses utama tetap dilanjutkan', [
                'nohp' => $nohp,
                'message_error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [
                'status' => false,
                'message' => 'WA error: ' . $e->getMessage(),
                'error_handled' => true,
            ];
        }
    }
}
