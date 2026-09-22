<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class PengingatCronAsman extends Command
{
    protected $signature = 'pengingatasman:cron';
    protected $description = 'Kirim pengingat WA ke ASMAN: rekap per perusahaan (alat, noorder, SN) + jumlah per perusahaan & total.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info("PengingatCronAsman START");

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
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtr.asmanveriffk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select(
                'mtr.jenisorder',
                'mtr.nopendaftaran',
                'mtrd.noorderalat',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'pg3.id as asmanferiv',
                'pg3.namalengkap as asmanverif',
                'pg3.nohandphone as wa_asman'
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
        Log::info("PengingatCronAsman: total pending rows = {$count}");
        if ($count === 0) {
            Log::info("PengingatCronAsman: Tidak ada data pending. END");
            return 0;
        }

        $now = Carbon::now('Asia/Jakarta')->format('d-m-Y H:i');

        $groupByAsman = $data->groupBy('asmanferiv');
        foreach ($groupByAsman as $asmanId => $items) {
            $first = $items->first();
            $namaAsman = $first->asmanverif ?? null;
            $waAsman   = $first->wa_asman ?? null;

            if (empty($asmanId) || empty($waAsman)) {
                continue;
            }

            $byCompany = $items->groupBy(function ($it) {
                $nama  = $it->namaperusahaan ?? '-';
                $nopen = $it->nopendaftaran ?? '-';
                return "{$nama}||{$nopen}";
            });

            $totalPending = $items->count();
            $lines = [];
            foreach ($byCompany as $key => $rows) {
                [$perusahaan, $nopen] = explode('||', $key);
                $jumlah = $rows->count();

                $detail = $rows->map(function ($it) {
                    $no   = $it->noorderalat ?: '-';
                    $alat = $it->namaproduk ?: '-';
                    $tipe = $it->namatipe ?: '-';
                    $merk = $it->namamerk ?: '-';
                    $sn   = $it->namaserialnumber ?: '-';
                    return "  - {$no} - {$alat} - MERK/TIPE:{$merk}/{$tipe} - SN: {$sn}";
                })->implode("\n");

                $lines[] = "{$perusahaan} (Nopen: {$nopen}) — {$jumlah} alat\n{$detail}";
            }

            $pesan =
                // "MAAF SPAM HANYA PERCOBAAN\n\n" .
                "Rekap Pending Tim ({$now})\n" .
                "Yth. {$namaAsman}, berikut rekap item pending per unit:\n\n" .
                implode("\n\n", $lines) .
                "\n\nTotal pending: {$totalPending} alat.";

            try {
                $parts = $this->sendChunkedWhatsapp($waAsman, $pesan, 1500, 800);
                Log::info("PengingatCronAsman: WA terkirim ke ASMAN {$namaAsman} ({$waAsman}), total={$totalPending}, perusahaan=" . $byCompany->count() . ", parts={$parts}");
            } catch (\Exception $e) {
                Log::error("PengingatCronAsman: Gagal kirim WA ke ASMAN {$namaAsman} ({$waAsman}) - {$e->getMessage()}");
            }

            usleep(400000);
        }

        Log::info("PengingatCronAsman END");
        return 0;
    }

    protected function sendChunkedWhatsapp(string $nohp, string $pesan, int $maxEncodedLen = 1500, int $sleepMs = 800): int
    {
        $clean = str_replace(
            ["•", "✌️", "—", "–", "No. Pendaftaran:"],
            ["-",  "",   "-",  "-", "Nopen:"],
            $pesan
        );

        $lines = preg_split("/\r\n|\r|\n/", $clean);
        $partsSent = 0;
        $current = "";

        $push = function ($chunk) use ($nohp, $sleepMs, &$partsSent) {
            $chunk = trim($chunk);
            if ($chunk === "") return;
            try {
                $this->kirimWhatsappNotifikasi($nohp, $chunk);
            } catch (\Throwable $e) {
                usleep($sleepMs * 1000);
                $this->kirimWhatsappNotifikasi($nohp, $chunk);
            }
            $partsSent++;
            usleep($sleepMs * 1000);
        };

        foreach ($lines as $line) {
            if ($line === '') {
                $candidate = ($current === "") ? "" : $current . "\n";
                if (strlen(urlencode($candidate)) > $maxEncodedLen) {
                    $push($current);
                    $current = "";
                } else {
                    $current = $candidate;
                }
                continue;
            }

            if (strlen(urlencode($line)) > $maxEncodedLen) {
                $seg = "";
                $chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($chars as $ch) {
                    $tmp = ($seg === "") ? $ch : $seg . $ch;
                    if (strlen(urlencode($tmp)) > $maxEncodedLen) {
                        $push($seg);
                        $seg = $ch;
                    } else {
                        $seg = $tmp;
                    }
                }
                if ($seg !== "") {
                    $candidate = ($current === "") ? $seg : $current . "\n" . $seg;
                    if (strlen(urlencode($candidate)) > $maxEncodedLen) {
                        $push($current);
                        $current = $seg;
                    } else {
                        $current = $candidate;
                    }
                }
                continue;
            }

            $candidate = ($current === "") ? $line : $current . "\n" . $line;
            if (strlen(urlencode($candidate)) > $maxEncodedLen) {
                $push($current);
                $current = $line;
            } else {
                $current = $candidate;
            }
        }

        $push($current);
        return $partsSent;
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
            $token = 'jEdiLI6VSWDzZfTQV2WKtkV6iA8eNTzhGTREoAOEhmpjMTM7uIaM5ll';
            $secret_key = 'EpxyT46x';
            $url = "https://sby.wablas.com/api/send-message";
            if (empty($token) || empty($secret_key)) {
                Log::warning('WA tidak dikirim: token atau secret Wablas kosong', [
                    'phone' => $nomor,
                ]);
                return [
                    'status' => false,
                    'message' => 'Token atau secret Wablas kosong',
                ];
            }
            $url = 'https://sby.wablas.com/api/send-message';
            $response = \Illuminate\Support\Facades\Http::withOptions([
                'timeout' => 10,
                'connect_timeout' => 5,
            ])
                ->get($url, [
                    'token' => $token . '.' . $secret_key,
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
