<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class PengingatCron extends Command
{
    protected $signature = 'pengingat:cron {--dry-run : Tampilkan calon penerima tanpa mengirim WhatsApp}';
    protected $description = 'Kirim satu pengingat WA per 24 jam untuk item kalibrasi/repair yang belum dikerjakan.';

    protected const LAST_ATTEMPT_CACHE_KEY = 'pengingat_cron:last_attempt_at';
    protected const PROCESSING_CACHE_KEY = 'pengingat_cron:processing';
    protected const RECIPIENT_ATTEMPT_CACHE_PREFIX = 'pengingat_cron:recipient_last_attempt:';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($timezone);
        $dryRun = (bool) $this->option('dry-run');
        $cooldownHours = max(24, (int) config('whatsapp.pengingat_cooldown_hours', 24));

        Log::info('PengingatCron START', [
            'dry_run' => $dryRun,
            'cooldown_hours' => $cooldownHours,
        ]);

        if (!$dryRun) {
            $lastAttemptValue = Cache::get(self::LAST_ATTEMPT_CACHE_KEY);
            if (!empty($lastAttemptValue)) {
                try {
                    $lastAttempt = Carbon::parse($lastAttemptValue, $timezone);
                    $nextAllowedAt = $lastAttempt->copy()->addHours($cooldownHours);

                    if ($now->lt($nextAllowedAt)) {
                        Log::info('PengingatCron: pengiriman ditunda karena masih dalam masa jeda', [
                            'last_attempt_at' => $lastAttempt->toDateTimeString(),
                            'next_allowed_at' => $nextAllowedAt->toDateTimeString(),
                        ]);
                        $this->info(
                            'Belum mengirim WA. Pengiriman berikutnya paling cepat '
                            . $nextAllowedAt->format('d-m-Y H:i')
                        );
                        return 0;
                    }
                } catch (\Throwable $e) {
                    Log::warning('PengingatCron: cache waktu pengiriman tidak valid, proses dilanjutkan', [
                        'value' => $lastAttemptValue,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('mapalatstandar_m as mmps', function ($join) {
                $join->on('mmps.id', '=', 'mtrd.namaalatfk')
                    ->where('mmps.statusenabled', true)
                    ->whereRaw('mtr.isstandarulab = true');
            })
            ->leftJoin('mapunittoalat_m as mmp', function ($join) {
                $join->on('mmp.id', '=', 'mtrd.namaalatfk')
                    ->where('mmp.statusenabled', true)
                    ->whereNull('mtr.isstandarulab');
            })
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->leftJoin('jabatan_m as jb2', 'jb2.id', '=', 'pg2.jabatan1fk')
            ->select(
                'mtrd.tanggalmulai',
                'mtr.jenisorder',
                'mtr.nopendaftaran',
                'mtrd.durasikalbrasi',
                'mtrd.statusorderpelaksana',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.noorderalat',
                'mtrd.pelaksanaisilaporanrepairfk',
                'mtrd.sublingkupfk',
                'mtrd.statusrepairfk',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg.nohandphone as wa_penyelia',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg2.nohandphone as wa_pelaksana'
            )
            ->where('mtr.statusorder', 1)
            ->where('mtr.iskaji', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('mtr.jenisorder', 'kalibrasi')
                        ->whereNull('mtrd.pelaksanaisilembarkerjafk')
                        ->whereNull('mtrd.penyeliaisilembarkerjafk');
                })
                    ->orWhere(function ($q) {
                        $q->where('mtr.jenisorder', 'repair')
                            ->whereNull('mtrd.pelaksanaisilaporanrepairfk')
                            ->whereNull('mtrd.penyeliaisilaporanrepairfk');
                    });
            })
            ->orderBy('mt.namaperusahaan')
            ->orderBy('mtrd.noorderalat')
            ->get();

        $count = $data->count();
        Log::info("PengingatCron: total pending rows = {$count}");

        if ($count === 0) {
            Log::info("PengingatCron: Tidak ada data pending. END");
            return 0;
        }

        $candidates = collect();

        foreach ($data->groupBy('pelaksanateknikfk') as $pelaksanaId => $items) {
            $first = $items->first();
            $namaPelaksana = $first->pelaksanateknik ?? null;
            $waPelaksana   = $first->wa_pelaksana ?? null;

            if (empty($pelaksanaId) || empty($waPelaksana)) {
                continue;
            }

            $candidates->push([
                'key' => 'pelaksana:' . $pelaksanaId,
                'role' => 'pelaksana',
                'id' => $pelaksanaId,
                'name' => $namaPelaksana,
                'phone' => $waPelaksana,
                'items' => $items->values(),
            ]);
        }

        foreach ($data->groupBy('penyeliateknikfk') as $penyeliaId => $items) {
            $first = $items->first();
            $namaPenyelia = $first->penyeliateknik ?? null;
            $waPenyelia   = $first->wa_penyelia ?? null;

            if (empty($penyeliaId) || empty($waPenyelia)) {
                continue;
            }

            $candidates->push([
                'key' => 'penyelia:' . $penyeliaId,
                'role' => 'penyelia',
                'id' => $penyeliaId,
                'name' => $namaPenyelia,
                'phone' => $waPenyelia,
                'items' => $items->values(),
            ]);
        }

        if ($candidates->isEmpty()) {
            Log::info('PengingatCron: Tidak ada penerima dengan nomor WA. END');
            $this->info('Tidak ada penerima dengan nomor WhatsApp.');
            return 0;
        }

        $candidate = $candidates
            ->map(function (array $candidate) {
                $cacheKey = self::RECIPIENT_ATTEMPT_CACHE_PREFIX . $candidate['key'];
                $candidate['cache_key'] = $cacheKey;
                $candidate['last_attempt_at'] = Cache::get($cacheKey, '1970-01-01T00:00:00+00:00');
                return $candidate;
            })
            ->sortBy('last_attempt_at')
            ->values()
            ->first();

        $pesan = $this->buildSingleMessage(
            $candidate['role'],
            $candidate['name'],
            $candidate['items'],
            $now
        );

        if ($dryRun) {
            Log::info('PengingatCron DRY RUN', [
                'role' => $candidate['role'],
                'recipient_id' => $candidate['id'],
                'recipient_name' => $candidate['name'],
                'items' => $candidate['items']->count(),
                'encoded_message_length' => strlen(urlencode($pesan)),
            ]);
            $this->line(
                "[DRY RUN] {$candidate['role']} {$candidate['name']} "
                . "({$candidate['phone']}), items={$candidate['items']->count()}"
            );
            return 0;
        }

        // Simpan waktu percobaan sebelum request API agar eksekusi manual/bersamaan
        // tidak menghasilkan burst meskipun respons provider gagal atau timeout.
        $attemptAt = $now->toIso8601String();
        if (!Cache::add(self::PROCESSING_CACHE_KEY, $attemptAt, $now->copy()->addMinutes(10))) {
            Log::info('PengingatCron: pengiriman lain sedang berjalan, proses ini dilewati');
            $this->info('Tidak mengirim WA karena proses pengiriman lain sedang berjalan.');
            return 0;
        }

        $cacheUntil = $now->copy()->addYears(2);
        Cache::put(self::LAST_ATTEMPT_CACHE_KEY, $attemptAt, $cacheUntil);
        Cache::put($candidate['cache_key'], $attemptAt, $cacheUntil);

        try {
            $result = $this->kirimWhatsappNotifikasi($candidate['phone'], $pesan);
            if ($this->isSuccessfulWaResult($result)) {
                Log::info('PengingatCron: satu WA terkirim', [
                    'role' => $candidate['role'],
                    'recipient_id' => $candidate['id'],
                    'recipient_name' => $candidate['name'],
                    'items' => $candidate['items']->count(),
                    'next_allowed_at' => $now->copy()->addHours($cooldownHours)->toDateTimeString(),
                ]);
                $this->info(
                    "WA terkirim ke {$candidate['name']}. "
                    . "Penerima berikutnya paling cepat {$cooldownHours} jam lagi."
                );
            } else {
                Log::warning('PengingatCron: percobaan kirim WA gagal; jeda tetap diberlakukan', [
                    'role' => $candidate['role'],
                    'recipient_id' => $candidate['id'],
                    'recipient_name' => $candidate['name'],
                    'message' => $result['message'] ?? 'Tidak diketahui',
                ]);
                $this->warn(
                    "Pengiriman gagal. Demi mencegah burst, percobaan berikutnya "
                    . "tetap menunggu {$cooldownHours} jam."
                );
            }
        } finally {
            Cache::forget(self::PROCESSING_CACHE_KEY);
        }

        Log::info("PengingatCron END");
        return 0;
    }

    protected function buildSingleMessage(
        string $role,
        ?string $name,
        $items,
        Carbon $now,
        int $maxEncodedLength = 1800
    ): string
    {
        $name = $this->compactText($name ?: '-', 60);
        $total = $items->count();
        $waktu = $now->format('d-m-Y H:i');

        if ($role === 'penyelia') {
            $header = "Pengingat Monitoring ({$waktu})\n"
                . "Yth. {$name}, berikut alat yang masih pending pada tim Anda "
                . "(Total: {$total} alat):\n";
            $footer = "\n\nMohon koordinasi dan tindak lanjut. Terima kasih.";
        } else {
            $header = "Pengingat Pekerjaan ({$waktu})\n"
                . "Halo {$name}, berikut alat yang belum dikerjakan "
                . "(Total: {$total} alat):\n";
            $footer = "\n\nMohon isi lembar kerja kalibrasi atau laporan repair. Terima kasih.";
        }

        $lines = [];
        foreach ($items as $item) {
            $line = $this->buildCompactItemLine($item);
            $candidateLines = array_merge($lines, [$line]);
            $remaining = $total - count($candidateLines);
            $moreLine = $remaining > 0 ? "\n- ... dan {$remaining} alat lainnya" : '';
            $candidateMessage = $header
                . implode("\n", $candidateLines)
                . $moreLine
                . $footer;

            if (strlen(urlencode($candidateMessage)) > $maxEncodedLength) {
                break;
            }

            $lines[] = $line;
        }

        if (empty($lines)) {
            $lines[] = '- Daftar alat terlalu panjang untuk ditampilkan.';
        }

        $remaining = max(0, $total - count($lines));
        $moreLine = $remaining > 0 ? "\n- ... dan {$remaining} alat lainnya" : '';
        $message = $header . implode("\n", $lines) . $moreLine . $footer;

        // Pengaman terakhir: tetap satu request/pesan, jangan kembali memecah pesan.
        while (strlen(urlencode($message)) > $maxEncodedLength && count($lines) > 1) {
            array_pop($lines);
            $remaining = max(0, $total - count($lines));
            $moreLine = $remaining > 0 ? "\n- ... dan {$remaining} alat lainnya" : '';
            $message = $header . implode("\n", $lines) . $moreLine . $footer;
        }

        return $message;
    }

    protected function buildCompactItemLine($item): string
    {
        $no = $this->compactText($item->noorderalat ?: '-', 30);
        $alat = $this->compactText($item->namaproduk ?: '-', 55);
        $sn = $this->compactText($item->namaserialnumber ?: '-', 35);

        return "- {$no} - {$alat} - SN: {$sn}";
    }

    protected function compactText(?string $value, int $maxLength): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));
        if ($value === '') {
            return '-';
        }

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($value, 0, $maxLength, '...');
        }

        return strlen($value) > $maxLength
            ? substr($value, 0, max(0, $maxLength - 3)) . '...'
            : $value;
    }

    protected function isSuccessfulWaResult($result): bool
    {
        if (!is_array($result) || !array_key_exists('status', $result)) {
            return false;
        }

        $status = $result['status'];
        if ($status === true || $status === 1 || $status === '1') {
            return true;
        }

        if (is_string($status)) {
            return in_array(strtolower($status), ['true', 'success', 'sent'], true);
        }

        return false;
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
            $token = config('whatsapp.token');
            $secretKey = config('whatsapp.secret_key');
            $url = config('whatsapp.url');
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
                'timeout' => (int) config('whatsapp.timeout', 10),
                'connect_timeout' => (int) config('whatsapp.connect_timeout', 5),
            ])
                ->get($url, [
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
