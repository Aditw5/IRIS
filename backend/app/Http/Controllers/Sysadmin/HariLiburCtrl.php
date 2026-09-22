<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\MasterHariLibur;
use App\Traits\Valet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HariLiburCtrl extends Controller
{
    use Valet;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function syncHariLibur(Request $request)
    {
        DB::beginTransaction();

        try {
            $tahun = (int) $request->get('tahun', date('Y'));

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'ULAB-UMRO-Laravel',
            ])->timeout(30)->get('https://tanggalmerah.upset.dev/api/holidays', [
                'year' => $tahun,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Gagal mengambil data hari libur dari Tanggal Merah API. Status: ' . $response->status());
            }

            $json = $response->json();

            if (!is_array($json)) {
                throw new \Exception('Format response Tanggal Merah API tidak sesuai.');
            }

            if (!isset($json['success']) || $json['success'] !== true) {
                throw new \Exception($json['message'] ?? 'Tanggal Merah API mengembalikan response gagal.');
            }

            if (!isset($json['data']) || !is_array($json['data'])) {
                throw new \Exception('Data hari libur tidak ditemukan pada response Tanggal Merah API.');
            }

            $dataLibur = $json['data'];

            if (count($dataLibur) == 0) {
                throw new \Exception('Data hari libur dari Tanggal Merah API kosong untuk tahun ' . $tahun);
            }

            /*
                Penting:
                Data lama dari API sebelumnya dinonaktifkan dulu.
                Tujuannya agar data yang salah, misalnya API lama salah tanggal Idul Adha,
                tidak ikut terbaca saat hitung durasi.
            */
            MasterHariLibur::where('tahun', $tahun)
                ->whereIn('source_api', [
                    'api-hari-libur.vercel.app',
                    'tanggalmerah.upset.dev',
                    'libur.deno.dev',
                    'api.co.id',
                ])
                ->update([
                    'statusenabled' => false,
                    'updated_at' => now(),
                ]);

            $jumlahSimpan = 0;

            foreach ($dataLibur as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $tanggalApi = $item['date'] ?? null;

                if (empty($tanggalApi)) {
                    continue;
                }

                $tanggal = Carbon::parse($tanggalApi)->format('Y-m-d');

                $namaLibur = $item['name'] ?? 'Hari Libur';

                $type = strtolower($item['type'] ?? '');

                $jenisLibur = 'nasional';

                if ($type == 'leave') {
                    $jenisLibur = 'cuti_bersama';
                }

                if ($type == 'holiday') {
                    $jenisLibur = 'nasional';
                }

                /*
                    Fallback jika suatu saat field type berubah,
                    tetap deteksi dari nama libur.
                */
                $namaLower = strtolower($namaLibur);

                if (
                    strpos($namaLower, 'cuti bersama') !== false ||
                    strpos($namaLower, 'cuti') !== false
                ) {
                    $jenisLibur = 'cuti_bersama';
                }

                MasterHariLibur::updateOrCreate(
                    [
                        'tanggal' => $tanggal,
                    ],
                    [
                        'nama_libur' => $namaLibur,
                        'jenis_libur' => $jenisLibur,
                        'source_api' => 'tanggalmerah.upset.dev',
                        'tahun' => $tahun,
                        'statusenabled' => true,
                        'updated_at' => now(),
                    ]
                );

                $jumlahSimpan++;
            }

            DB::commit();

            $res = [
                'tahun' => $tahun,
                'jumlah' => $jumlahSimpan,
                'source_api' => 'tanggalmerah.upset.dev',
                'data' => [
                    'tahun' => $tahun,
                    'jumlah' => $jumlahSimpan,
                    'source_api' => 'tanggalmerah.upset.dev',
                ],
            ];

            return $this->respond($res, 200, 'Data hari libur berhasil disinkronkan dari Tanggal Merah API');
        } catch (\Exception $e) {
            DB::rollBack();

            $res = [
                'error' => $e->getMessage(),
            ];

            return $this->respond($res, 400, 'Gagal sync hari libur dari Tanggal Merah API');
        }
    }

    public function getHariLibur(Request $request)
    {
        try {
            $tahun = (int) $request->get('tahun', date('Y'));

            $data = DB::table('master_hari_libur_m')
                ->select(
                    'id',
                    DB::raw("to_char(tanggal, 'YYYY-MM-DD') as tanggal"),
                    'nama_libur',
                    'jenis_libur',
                    'source_api',
                    'tahun',
                    'statusenabled',
                    'created_at',
                    'updated_at'
                )
                ->where('statusenabled', true)
                ->where('tahun', $tahun)
                ->orderBy('tanggal', 'asc')
                ->get();

            foreach ($data as $d) {
                $d->status = 'Aktif';
                $d->status_c = 'success';

                if ($d->statusenabled == false || $d->statusenabled == 'false' || $d->statusenabled == 'f') {
                    $d->status = 'Nonaktif';
                    $d->status_c = 'danger';
                }

                if ($d->jenis_libur == 'cuti_bersama') {
                    $d->jenis_libur_label = 'Cuti Bersama';
                    $d->jenis_libur_c = 'warning';
                } elseif ($d->jenis_libur == 'nasional') {
                    $d->jenis_libur_label = 'Libur Nasional';
                    $d->jenis_libur_c = 'info';
                } else {
                    $d->jenis_libur_label = 'Libur';
                    $d->jenis_libur_c = 'primary';
                }
            }

            $res = [
                'tahun' => $tahun,
                'jumlah' => count($data),
                'data' => $data,
            ];

            return $this->respond($res, 200, 'Data hari libur berhasil diambil');
        } catch (\Exception $e) {
            $res = [
                'error' => $e->getMessage(),
                'data' => [],
            ];

            return $this->respond($res, 400, 'Gagal mengambil data hari libur');
        }
    }

    public function deleteHariLibur(Request $request)
    {
        DB::beginTransaction();

        try {
            if (empty($request['id'])) {
                throw new \Exception('ID hari libur tidak ditemukan');
            }

            $data = MasterHariLibur::where('id', $request['id'])
                ->update([
                    'statusenabled' => false,
                    'updated_at' => now(),
                ]);

            DB::commit();

            $res = [
                'data' => $data,
            ];

            return $this->respond($res, 200, 'Data hari libur berhasil dinonaktifkan');
        } catch (\Exception $e) {
            DB::rollBack();

            $res = [
                'error' => $e->getMessage(),
            ];

            return $this->respond($res, 400, 'Hapus data hari libur gagal');
        }
    }
}