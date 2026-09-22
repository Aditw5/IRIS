<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Traits\Valet;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;

class NoAuthCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function dataChartIntegrasi(Request $request)
    {

        $data = DB::table('mitra_m')
            ->select('id', 'namaperusahaan', 'alamatktr', 'lat', 'lng')
            ->where('statusenabled', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        $result = array(
            'data' => $data,
            'message' => 'as@aditwiran19@gmail.com',
        );

        return $this->respond($result);
    }

    public function getLandingServiceStats(Request $request)
    {
        try {
            $year = (int) $request->query('year', Carbon::now()->year);
            if ($year < 2000 || $year > (int) Carbon::now()->addYear()->year) {
                $year = (int) Carbon::now()->year;
            }

            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = $year === (int) Carbon::now()->year
                ? Carbon::now()->endOfDay()
                : Carbon::create($year, 12, 31)->endOfDay();

            $registrasiBase = DB::table('mitraregistrasi_t as mtr')
                ->where('mtr.statusenabled', true)
                ->whereBetween('mtr.tglregistrasi', [$start, $end]);

            $unitDilayani = (clone $registrasiBase)
                ->whereNotNull('mtr.nomitrafk')
                ->distinct()
                ->count('mtr.nomitrafk');

            $detailBase = DB::table('mitraregistrasi_t as mtr')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
                ->where('mtr.statusenabled', true)
                ->where('mtrd.statusenabled', true)
                ->whereBetween('mtr.tglregistrasi', [$start, $end]);

            $jumlahKalibrasi = (clone $detailBase)
                ->whereRaw("LOWER(TRIM(COALESCE(mtr.jenisorder, ''))) = ?", ['kalibrasi'])
                ->count('mtrd.norec');

            $jumlahRepair = (clone $detailBase)
                ->whereRaw("LOWER(TRIM(COALESCE(mtr.jenisorder, ''))) = ?", ['repair'])
                ->count('mtrd.norec');

            $totalLayanan = $jumlahKalibrasi + $jumlahRepair;

            return $this->respond([
                'year' => $year,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'generated_at' => Carbon::now()->toDateTimeString(),
                'stats' => [
                    'unit_dilayani' => (int) $unitDilayani,
                    'jumlah_kalibrasi' => (int) $jumlahKalibrasi,
                    'jumlah_repair' => (int) $jumlahRepair,
                    'total_layanan' => (int) $totalLayanan,
                ],
                'items' => [
                    [
                        'key' => 'unit_dilayani',
                        'label' => 'Unit Dilayani',
                        'value' => (int) $unitDilayani,
                    ],
                    [
                        'key' => 'jumlah_kalibrasi',
                        'label' => 'Kalibrasi',
                        'value' => (int) $jumlahKalibrasi,
                    ],
                    [
                        'key' => 'jumlah_repair',
                        'label' => 'Repair',
                        'value' => (int) $jumlahRepair,
                    ],
                    [
                        'key' => 'total_layanan',
                        'label' => 'Total Layanan',
                        'value' => (int) $totalLayanan,
                    ],
                ],
            ], 200, 'Statistik layanan landing');
        } catch (\Exception $e) {
            return $this->respond([
                'year' => (int) Carbon::now()->year,
                'period_start' => Carbon::now()->startOfYear()->toDateString(),
                'period_end' => Carbon::now()->toDateString(),
                'generated_at' => Carbon::now()->toDateTimeString(),
                'stats' => [
                    'unit_dilayani' => 0,
                    'jumlah_kalibrasi' => 0,
                    'jumlah_repair' => 0,
                    'total_layanan' => 0,
                ],
                'items' => [],
                'error' => $e->getMessage(),
            ], 200, 'Statistik layanan landing gagal dimuat');
        }
    }

    public function getLandingMappingLayanan(Request $request)
    {
        try {
            $scope = strtolower(trim((string) $request->query('scope', '')));
            $scopeAliases = [
                'kelistrikan' => ['Kelistrikan'],
                'tekanan' => ['Tekanan'],
                'suhu' => ['Suhu', 'Suhu & Kelembaban', 'Suhu & Kelembapan'],
                'vibrasi' => ['Vibrasi'],
                'dimensi' => ['Dimensi', 'Gaya'],
                'repair' => ['Repair'],
            ];
            $scopeTitles = [
                'kelistrikan' => 'Kelistrikan',
                'tekanan' => 'Tekanan',
                'suhu' => 'Suhu',
                'vibrasi' => 'Vibrasi',
                'dimensi' => 'Dimensi dan Gaya',
                'repair' => 'Repair',
            ];

            $scopeLabels = $scopeAliases[$scope] ?? [];
            if ($scope && empty($scopeLabels)) {
                $scopeLabels = [str_replace(['-', '_'], ' ', $scope)];
            }

            $normalizedLabels = collect($scopeLabels)
                ->map(function ($value) {
                    return strtolower(trim($value));
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $query = DB::table('mappinglayanan_m as ml')
                ->join('lingkupkalibrasi_m as lk', 'lk.id', '=', 'ml.objectlingkupfk')
                ->where('ml.statusenabled', true)
                ->where('lk.statusenabled', true)
                ->select(
                    'ml.id',
                    'ml.kategori',
                    'ml.objectlingkupfk',
                    'ml.namalayanan',
                    'ml.merktipe',
                    'ml.gambar',
                    'lk.lingkupkalibrasi as lingkup'
                );

            if (!empty($normalizedLabels)) {
                $query->whereIn(DB::raw('LOWER(TRIM(lk.lingkupkalibrasi))'), $normalizedLabels);
            }

            if ($request->filled('kategori')) {
                $kategori = trim((string) $request->query('kategori'));
                if (in_array($kategori, ['KAN', 'Non KAN'], true)) {
                    $query->where('ml.kategori', $kategori);
                }
            }

            if ($request->filled('search')) {
                $search = '%' . strtolower(trim((string) $request->query('search'))) . '%';
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereRaw('LOWER(ml.namalayanan) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(ml.merktipe) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(lk.lingkupkalibrasi) LIKE ?', [$search]);
                });
            }

            $data = $query
                ->orderBy('lk.lingkupkalibrasi')
                ->orderBy('ml.kategori')
                ->orderBy('ml.namalayanan')
                ->orderBy('ml.merktipe')
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'kategori' => $row->kategori,
                        'objectlingkupfk' => $row->objectlingkupfk,
                        'namalayanan' => $row->namalayanan,
                        'merktipe' => $row->merktipe,
                        'gambar' => $row->gambar,
                        'gambar_url' => $row->gambar ? url('mapping-layanan/' . $row->gambar) : null,
                        'lingkup' => $row->lingkup,
                    ];
                })
                ->values();

            $kategoriCounts = $data
                ->groupBy('kategori')
                ->map(function ($items) {
                    return $items->count();
                });

            return $this->respond([
                'scope' => [
                    'key' => $scope,
                    'label' => $scopeTitles[$scope] ?? ucwords(str_replace(['-', '_'], ' ', $scope)),
                    'lingkup' => $data->pluck('lingkup')->unique()->values(),
                ],
                'summary' => [
                    'total' => $data->count(),
                    'kan' => (int) ($kategoriCounts['KAN'] ?? 0),
                    'non_kan' => (int) ($kategoriCounts['Non KAN'] ?? 0),
                    'lingkup' => $data->pluck('lingkup')->unique()->values(),
                ],
                'data' => $data,
            ], 200, 'Daftar mapping layanan landing');
        } catch (\Exception $e) {
            return $this->respond([
                'scope' => null,
                'summary' => [
                    'total' => 0,
                    'kan' => 0,
                    'non_kan' => 0,
                    'lingkup' => [],
                ],
                'data' => [],
                'error' => $e->getMessage(),
            ], 200, 'Daftar mapping layanan landing gagal dimuat');
        }
    }

    public function saveDataTemperature(Request $request)
    {
        DB::beginTransaction();
        try {
            if (!isset($request['data']) || !is_array($request['data']) || empty($request['data'][0])) {
                $response = [
                    "response_code" => "0013",
                    "response_message" => "Data Request Kosong",
                ];
                return response()->json($response, 400);
            }

            $data = $request['data'];

            $dataSave = [];
            foreach ($data as $item) {
                $dataSave[] = array(
                    "id" => Uuid::uuid4(),
                    "room_id" => $item["room_id"],
                    "temperature" => $item["temperature"] ?? null,
                    "humidity" => $item["humidity"] ?? null,
                    "pressure" => $item["pressure"] ?? null,
                    "light" => $item["light"] ?? null,
                    "uv" => $item["uv"] ?? null,
                    "created_at" => now(),
                    "updated_at" => now(),
                    "statusenabled" => 1,
                );
            }

            $dataSave = collect($dataSave);
            $chunks = $dataSave->chunk(25);
            foreach ($chunks as $chunk) {
                DB::table('sensor_readings')->insert($chunk->toArray());
            }

            DB::commit();
            $response = [
                "response_code" => "0000",
                "response_message" => "Sukses",
            ];
            return response()->json($response, 201);
        } catch (Exception $e) {
            DB::rollBack();
            $result = [
                "response_code" => "0005",
                "response_message" => 'Error Lainnya',
                "error" => $e->getMessage()
            ];
            return response()->json($result, 400);
        }
    }

    public function saveKalibrasiAI(Request $request)
    {
        DB::beginTransaction();
        try {
            if (!isset($request['data']) || !is_array($request['data']) || count($request['data']) === 0) {
                DB::rollBack();
                return response()->json([
                    "response_code" => "0013",
                    "response_message" => "Data Request Kosong",
                ], 400);
            }

            $data = $request['data'];
            $noOrders = collect($data)
                ->pluck('noorder')
                ->filter(function ($noorder) {
                    return trim((string) $noorder) !== '';
                })
                ->map(function ($noorder) {
                    return (string) $noorder;
                })
                ->unique()
                ->values();

            $vibrationOrders = DB::table('mitraregistrasidetail_t as mtrd')
                ->join('lingkupkalibrasi_m as lk', 'lk.id', '=', 'mtrd.lingkupkalibrasifk')
                ->whereIn('mtrd.noorderalat', $noOrders)
                ->whereRaw('LOWER(TRIM(lk.lingkupkalibrasi)) = ?', ['vibrasi'])
                ->pluck('mtrd.noorderalat')
                ->mapWithKeys(function ($noorder) {
                    return [(string) $noorder => true];
                });

            $thermalOrders = DB::table('mitraregistrasidetail_t as mtrd')
                ->join('lingkupkalibrasi_m as lk', 'lk.id', '=', 'mtrd.lingkupkalibrasifk')
                ->whereIn('mtrd.noorderalat', $noOrders)
                ->whereRaw("LOWER(TRIM(lk.lingkupkalibrasi)) IN ('suhu', 'suhu & kelembaban')")
                ->pluck('mtrd.noorderalat')
                ->mapWithKeys(function ($noorder) {
                    return [(string) $noorder => true];
                });

            $dataSave = [];
            foreach ($data as $item) {
                if (!isset($item['noorder']) || trim($item['noorder']) === '') {
                    DB::rollBack();
                    return response()->json([
                        "response_code" => "0014",
                        "response_message" => "Field 'noorder' wajib diisi pada setiap baris",
                    ], 400);
                }

                $noorder = (string) $item['noorder'];
                $calibrationType = $this->kalibrasiAiType(
                    $item,
                    isset($vibrationOrders[$noorder]),
                    isset($thermalOrders[$noorder])
                );

                $isAllBlank = true;
                $fieldsToCheck = $this->kalibrasiAiFieldsToCheck($calibrationType);
                foreach ($fieldsToCheck as $f) {
                    if (isset($item[$f]) && trim((string)$item[$f]) !== '') {
                        $isAllBlank = false;
                        break;
                    }
                }
                if ($isAllBlank) {
                    continue;
                }

                $row = [
                    "id" => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    "noorder" => $noorder,
                    "rentang" => null,
                    "penunjukan_standar" => null,
                    "pembacaan_alat" => null,
                    "frekuensi" => null,
                    "vibrasi_reference" => null,
                    "uut" => null,
                    "set_point" => null,
                    "temperature_reference" => null,
                    "temperature_uut" => null,
                    "koreksi" => $item["koreksi"] ?? null,
                    "ketidakpastian" => $item["ketidakpastian"] ?? null,
                    "created_at" => now(),
                    "updated_at" => now(),
                    "statusenabled" => 1,
                ];

                $row = array_merge($row, $this->kalibrasiAiValues($item, $calibrationType));

                $dataSave[] = $row;
            }

            if (count($dataSave) === 0) {
                DB::rollBack();
                return response()->json([
                    "response_code" => "0015",
                    "response_message" => "Semua baris kosong, tidak ada yang disimpan",
                ], 400);
            }

            $thermalNoOrders = collect($dataSave)
                ->filter(function ($row) use ($thermalOrders) {
                    return isset($thermalOrders[(string) $row['noorder']])
                        && ($row['set_point'] !== null
                        || $row['temperature_reference'] !== null
                        || $row['temperature_uut'] !== null);
                })
                ->pluck('noorder')
                ->unique()
                ->values();

            if ($thermalNoOrders->isNotEmpty()) {
                $thermalSublingkupId = DB::table('sublingkupkalibrasi_m')
                    ->where('namasublingkup', 'KALIBRASI AI SUHU')
                    ->where('statusenabled', 1)
                    ->value('id');

                if (!$thermalSublingkupId) {
                    throw new \RuntimeException(
                        "Sublingkup 'KALIBRASI AI SUHU' belum tersedia atau tidak aktif"
                    );
                }

                // Resend suhu replaces the previous result so the certificate is not duplicated.
                DB::table('kalibrasi_ai')
                    ->whereIn('noorder', $thermalNoOrders)
                    ->delete();

                DB::table('mitraregistrasidetail_t')
                    ->whereIn('noorderalat', $thermalNoOrders)
                    ->update(['sublingkupfk' => $thermalSublingkupId]);
            }

            $chunks = collect($dataSave)->chunk(25);
            foreach ($chunks as $chunk) {
                DB::table('kalibrasi_ai')->insert($chunk->toArray());
            }

            DB::commit();
            return response()->json([
                "response_code" => "0000",
                "response_message" => "Sukses",
                "total_insert" => count($dataSave),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "response_code" => "0005",
                "response_message" => "Error Lainnya",
                "error" => $e->getMessage()
            ], 400);
        }
    }

    private function kalibrasiAiType(
        array $item,
        bool $isVibrationOrder,
        bool $isThermalOrder
    ): string {
        $jenis = strtolower(trim((string) ($item['jenis'] ?? '')));
        $lingkup = strtolower(trim((string) ($item['lingkup'] ?? '')));

        if (
            $isVibrationOrder
            || $jenis === 'vibration'
            || $lingkup === 'vibrasi'
            || array_key_exists('frekuensi', $item)
            || array_key_exists('vibrasi_reference', $item)
            || array_key_exists('uut', $item)
        ) {
            return 'vibration';
        }

        if (
            $isThermalOrder
            || in_array($jenis, ['thermal', 'temperature'], true)
            || in_array($lingkup, ['suhu', 'suhu & kelembaban'], true)
            || array_key_exists('set_point', $item)
            || array_key_exists('temperature_reference', $item)
            || array_key_exists('temperature_uut', $item)
        ) {
            return 'thermal';
        }

        return 'electrical';
    }

    private function kalibrasiAiFieldsToCheck(string $calibrationType): array
    {
        $common = ['rentang', 'penunjukan_standar', 'pembacaan_alat', 'koreksi', 'ketidakpastian'];

        if ($calibrationType === 'vibration') {
            return array_merge(['frekuensi', 'vibrasi_reference', 'uut'], $common);
        }

        if ($calibrationType === 'thermal') {
            return array_merge(['set_point', 'temperature_reference', 'temperature_uut'], $common);
        }

        return $common;
    }

    private function kalibrasiAiValues(array $item, string $calibrationType): array
    {
        $values = [
            'rentang' => $item['rentang'] ?? null,
            'penunjukan_standar' => $item['penunjukan_standar'] ?? null,
            'pembacaan_alat' => $item['pembacaan_alat'] ?? null,
            'frekuensi' => null,
            'vibrasi_reference' => null,
            'uut' => null,
            'set_point' => null,
            'temperature_reference' => null,
            'temperature_uut' => null,
        ];

        if ($calibrationType === 'vibration') {
            $values['frekuensi'] = $item['frekuensi'] ?? $values['rentang'];
            $values['vibrasi_reference'] = $item['vibrasi_reference'] ?? $values['penunjukan_standar'];
            $values['uut'] = $item['uut'] ?? $values['pembacaan_alat'];
            $values['rentang'] = $values['rentang'] ?? $values['frekuensi'];
            $values['penunjukan_standar'] = $values['penunjukan_standar'] ?? $values['vibrasi_reference'];
            $values['pembacaan_alat'] = $values['pembacaan_alat'] ?? $values['uut'];
        } elseif ($calibrationType === 'thermal') {
            $values['set_point'] = $item['set_point'] ?? $values['rentang'];
            $values['temperature_reference'] = $item['temperature_reference'] ?? $values['penunjukan_standar'];
            $values['temperature_uut'] = $item['temperature_uut'] ?? $values['pembacaan_alat'];
            $values['rentang'] = $values['rentang'] ?? $values['set_point'];
            $values['penunjukan_standar'] = $values['penunjukan_standar'] ?? $values['temperature_reference'];
            $values['pembacaan_alat'] = $values['pembacaan_alat'] ?? $values['temperature_uut'];
        }

        return $values;
    }

    public function getMeasurementCorrections(Request $request)
    {
        $sn = trim((string) ($request->get('sn', $request->get('serial_number', $request->get('serial', '')))));
        $legacyNoorder = trim((string) $request->get('noorder', ''));
        if ($sn === '' && $legacyNoorder === '') {
            return response()->json([
                'response_code' => '0014',
                'response_message' => "Parameter 'sn' wajib diisi",
            ], 400);
        }

        try {
            if ($sn === '') {
                $sn = $this->measurementSerialFromNoorder($legacyNoorder);
            }

            if ($sn === '') {
                return response()->json([
                    'response_code' => '0016',
                    'response_message' => 'SN tidak ditemukan dari No Order lama',
                    'requested_noorder' => $legacyNoorder,
                    'requested_sn' => null,
                    'found' => false,
                    'corrections' => [],
                ], 404);
            }

            $alat = $this->measurementAlatBySerial($sn);
            if (!$alat) {
                return response()->json([
                    'response_code' => '0017',
                    'response_message' => 'SN belum terdaftar pada master alat',
                    'requested_noorder' => $legacyNoorder ?: null,
                    'requested_sn' => $sn,
                    'found' => false,
                    'corrections' => [],
                ], 404);
            }

            $registrationCandidate = $this->latestRegistrationMeasurementCandidate($sn);
            $aiCandidate = $this->latestKalibrasiAiMeasurementCandidate($sn);
            $selectedCandidate = $this->newestMeasurementCandidate(
                $registrationCandidate,
                $aiCandidate
            );
            $corrections = collect($selectedCandidate['corrections'] ?? []);

            return response()->json([
                'response_code' => '0000',
                'response_message' => $corrections->isEmpty()
                    ? 'History koreksi terbaru belum tersedia untuk SN ini'
                    : 'History koreksi terbaru ditemukan',
                'requested_noorder' => $legacyNoorder ?: null,
                'requested_sn' => $sn,
                'found' => $corrections->isNotEmpty(),
                'alat' => $this->measurementAlatPayload($alat),
                'source_order' => $this->measurementSourceOrderPayload($selectedCandidate),
                'corrections' => $corrections->values(),
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil history koreksi measurement', [
                'sn' => $sn,
                'noorder' => $legacyNoorder,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'response_code' => '0005',
                'response_message' => 'Gagal mengambil history koreksi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getMeasurementCorrectionsFromKalibrasiAi(Request $request)
    {
        $noorder = trim((string) $request->get('noorder', ''));
        $sn = trim((string) ($request->get('sn', $request->get('serial_number', $request->get('serial', '')))));
        if ($noorder === '' && $sn === '') {
            return response()->json([
                'response_code' => '0014',
                'response_message' => "Parameter 'noorder' atau 'sn' wajib diisi",
            ], 400);
        }

        try {
            if ($sn !== '') {
                $alat = $this->measurementAlatBySerial($sn);
                $candidate = $this->latestKalibrasiAiMeasurementCandidate($sn);
                $corrections = collect($candidate['corrections'] ?? []);

                return response()->json([
                    'response_code' => '0000',
                    'response_message' => $corrections->isEmpty()
                        ? 'Koreksi kalibrasi_ai belum tersedia untuk SN ini'
                        : 'Koreksi kalibrasi_ai ditemukan',
                    'requested_sn' => $sn,
                    'found' => $corrections->isNotEmpty(),
                    'alat' => $alat ? $this->measurementAlatPayload($alat) : null,
                    'source_order' => $this->measurementSourceOrderPayload($candidate),
                    'corrections' => $corrections->values(),
                ]);
            }

            $sourceOrder = (object) ['noorderalat' => $noorder];
            $corrections = DB::table('kalibrasi_ai')
                ->where('noorder', $noorder)
                ->where('statusenabled', true)
                ->get()
                ->map(function ($row) use ($sourceOrder) {
                    return $this->normalizeMeasurementCorrection($row, 'kalibrasi_ai', $sourceOrder);
                })
                ->filter(function ($row) {
                    return $row !== null;
                })
                ->values();

            return response()->json([
                'response_code' => '0000',
                'response_message' => $corrections->isEmpty()
                    ? 'Koreksi kalibrasi_ai belum tersedia untuk No Order ini'
                    : 'Koreksi kalibrasi_ai ditemukan',
                'requested_noorder' => $noorder,
                'found' => $corrections->isNotEmpty(),
                'source_order' => [
                    'noorder' => $noorder,
                    'table' => 'kalibrasi_ai',
                ],
                'corrections' => $corrections,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil koreksi measurement dari kalibrasi_ai', [
                'noorder' => $noorder,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'response_code' => '0005',
                'response_message' => 'Gagal mengambil koreksi kalibrasi_ai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function measurementSerialFromNoorder(string $noorder): string
    {
        if ($noorder === '') {
            return '';
        }

        $row = DB::table('mitraregistrasidetail_t as mtrd')
            ->leftJoin('mapunittoalat_m as alat', 'alat.id', '=', 'mtrd.namaalatfk')
            ->select('alat.namaserialnumber')
            ->where('mtrd.noorderalat', $noorder)
            ->where('mtrd.statusenabled', true)
            ->first();

        return trim((string) ($row->namaserialnumber ?? ''));
    }

    private function measurementAlatBySerial(string $sn)
    {
        return DB::table('mapunittoalat_m')
            ->select('id', 'namaproduk', 'namamerk', 'namatipe', 'namaserialnumber')
            ->whereRaw('LOWER(TRIM(namaserialnumber)) = ?', [strtolower(trim($sn))])
            ->where('statusenabled', true)
            ->orderByDesc('id')
            ->first();
    }

    private function measurementAlatPayload($alat): ?array
    {
        if (!$alat) {
            return null;
        }

        return [
            'id' => $alat->id ?? null,
            'nama' => $alat->namaproduk ?? null,
            'merk' => $alat->namamerk ?? null,
            'tipe' => $alat->namatipe ?? null,
            'serial_number' => $alat->namaserialnumber ?? null,
        ];
    }

    private function latestRegistrationMeasurementCandidate(string $sn): ?array
    {
        $historyOrders = DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->leftJoin('mapunittoalat_m as alat', 'alat.id', '=', 'mtrd.namaalatfk')
            ->leftJoin('sublingkupkalibrasi_m as slk', 'slk.id', '=', 'mtrd.sublingkupfk')
            ->select(
                'mtrd.norec',
                'mtrd.namaalatfk',
                'mtrd.noorderalat',
                'mtrd.lingkupkalibrasifk',
                'mtrd.sublingkupfk',
                'mtrd.tglkalibrasilembarkerja',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.created_at',
                'mtr.tglregistrasi',
                'slk.namasublingkup',
                'alat.namaproduk',
                'alat.namamerk',
                'alat.namatipe',
                'alat.namaserialnumber'
            )
            ->whereRaw('LOWER(TRIM(alat.namaserialnumber)) = ?', [strtolower(trim($sn))])
            ->where('mtrd.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->whereRaw('LOWER(TRIM(mtr.jenisorder)) = ?', ['kalibrasi'])
            ->where(function ($query) {
                $query->whereNotNull('mtrd.tglsetujumanagerlembarkerja')
                    ->orWhere('mtrd.isverifikasi', true);
            })
            ->orderByRaw(
                'COALESCE(mtrd.created_at, mtrd.tglsetujumanagerlembarkerja, mtrd.tglkalibrasilembarkerja, mtr.tglregistrasi) DESC NULLS LAST'
            )
            ->get();

        $candidates = [];
        foreach ($historyOrders as $historyOrder) {
            $table = $this->measurementWorksheetTable($historyOrder);
            if ($table === null || $table === 'kalibrasi_ai') {
                continue;
            }

            $rows = $this->measurementCorrectionRows($historyOrder);
            if ($rows->isEmpty()) {
                continue;
            }

            $candidates[] = $this->measurementCandidate(
                'pendaftaran',
                $table,
                $historyOrder,
                $rows
            );
        }

        return $this->newestMeasurementCandidate(...$candidates);
    }

    private function latestKalibrasiAiMeasurementCandidate(string $sn): ?array
    {
        $rows = DB::table('kalibrasi_ai as kai')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noorderalat', '=', 'kai.noorder')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->leftJoin('mapunittoalat_m as alat', 'alat.id', '=', 'mtrd.namaalatfk')
            ->select(
                'kai.*',
                'mtrd.norec as source_norec',
                'mtrd.noorderalat as noorderalat',
                'mtrd.tglkalibrasilembarkerja',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtr.tglregistrasi',
                'alat.namaproduk',
                'alat.namamerk',
                'alat.namatipe',
                'alat.namaserialnumber'
            )
            ->whereRaw('LOWER(TRIM(alat.namaserialnumber)) = ?', [strtolower(trim($sn))])
            ->where('kai.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->orderByDesc('kai.created_at')
            ->get();

        $candidates = [];
        foreach ($rows->groupBy('noorder') as $noorder => $groupRows) {
            $first = $groupRows->first();
            $sourceOrder = (object) [
                'norec' => $first->source_norec ?? null,
                'noorderalat' => $noorder,
                'tglkalibrasilembarkerja' => $first->tglkalibrasilembarkerja ?? null,
                'tglsetujumanagerlembarkerja' => $first->tglsetujumanagerlembarkerja ?? null,
                'tglregistrasi' => $first->tglregistrasi ?? null,
                'created_at' => $first->created_at ?? null,
            ];

            $corrections = $groupRows
                ->map(function ($row) use ($sourceOrder) {
                    return $this->normalizeMeasurementCorrection($row, 'kalibrasi_ai', $sourceOrder);
                })
                ->filter(function ($row) {
                    return $row !== null;
                })
                ->values();

            if ($corrections->isEmpty()) {
                continue;
            }

            $candidates[] = $this->measurementCandidate(
                'kalibrasi_ai',
                'kalibrasi_ai',
                $sourceOrder,
                $corrections
            );
        }

        return $this->newestMeasurementCandidate(...$candidates);
    }

    private function measurementCandidate(
        string $source,
        string $table,
        object $order,
        $corrections
    ): array {
        $corrections = collect($corrections)->values();

        return [
            'source' => $source,
            'table' => $table,
            'order' => $order,
            'source_created_at' => $this->latestMeasurementTimestamp($corrections, $order),
            'corrections' => $corrections,
        ];
    }

    private function newestMeasurementCandidate(?array ...$candidates): ?array
    {
        $best = null;
        foreach ($candidates as $candidate) {
            if ($candidate === null || collect($candidate['corrections'] ?? [])->isEmpty()) {
                continue;
            }

            if ($best === null) {
                $best = $candidate;
                continue;
            }

            $candidateTime = $this->measurementCarbon($candidate['source_created_at'] ?? null);
            $bestTime = $this->measurementCarbon($best['source_created_at'] ?? null);
            if ($candidateTime && (!$bestTime || $candidateTime->greaterThan($bestTime))) {
                $best = $candidate;
            }
        }

        return $best;
    }

    private function latestMeasurementTimestamp($corrections, object $order): ?string
    {
        $latest = null;
        foreach (collect($corrections) as $correction) {
            foreach (['source_created_at', 'created_at'] as $key) {
                $time = $this->measurementCarbon($correction[$key] ?? null);
                if ($time && (!$latest || $time->greaterThan($latest))) {
                    $latest = $time;
                }
            }
        }

        foreach (['created_at', 'tglsetujumanagerlembarkerja', 'tglkalibrasilembarkerja', 'tglregistrasi'] as $key) {
            $time = $this->measurementCarbon($order->{$key} ?? null);
            if ($time && (!$latest || $time->greaterThan($latest))) {
                $latest = $time;
            }
        }

        return $latest ? $latest->toDateTimeString() : null;
    }

    private function measurementCarbon($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function measurementSourceOrderPayload(?array $candidate): ?array
    {
        if (!$candidate) {
            return null;
        }

        $order = $candidate['order'];
        return [
            'noorder' => $order->noorderalat ?? null,
            'source' => $candidate['source'],
            'table' => $candidate['table'],
            'worksheet' => $candidate['table'],
            'source_created_at' => $candidate['source_created_at'],
            'tanggal_kalibrasi' => $order->tglkalibrasilembarkerja ?? null,
            'tanggal_disetujui' => $order->tglsetujumanagerlembarkerja ?? null,
        ];
    }

    private function measurementCorrectionRows(object $order)
    {
        $table = $this->measurementWorksheetTable($order);
        if ($table === null) {
            return collect();
        }

        if ($table === 'kalibrasi_ai') {
            $rows = DB::table($table)
                ->where('noorder', $order->noorderalat)
                ->where('statusenabled', true)
                ->get();
        } else {
            $query = DB::table($table)
                ->where('detailregistraifk', $order->norec);
            if ($this->measurementWorksheetUsesStatusEnabled($table)) {
                $query->where('statusenabled', true);
            }
            $rows = $query->get();
        }

        return $rows
            ->map(function ($row) use ($table, $order) {
                return $this->normalizeMeasurementCorrection($row, $table, $order);
            })
            ->filter(function ($row) {
                return $row !== null;
            });
    }

    private function measurementWorksheetTable(object $order): ?string
    {
        if ((int) $order->lingkupkalibrasifk === 2 || $order->sublingkupfk === null) {
            return 'lembarkerjatekanan_t';
        }

        if (in_array($order->namasublingkup, ['KALIBRASI AI', 'KALIBRASI AI VIBRASI', 'KALIBRASI AI SUHU'], true)) {
            return 'kalibrasi_ai';
        }

        $tables = [
            1 => 'lembarkerja_t',
            2 => 'lembarkerjaclamp_t',
            3 => 'lembarkerjasumber_t',
            4 => 'lembarkerjavibration_t',
            5 => 'lembarkerjaaccellerometer_t',
            6 => 'lembarkerjaclampmeter_t',
            7 => 'lembarkerjametersumber_t',
            8 => 'lembarkerjathermometerinfrared_t',
            9 => 'lembarkerjathermalimager_t',
            10 => 'lembarkerjathermohygrometer_t',
            11 => 'lembarkerjadryblock_t',
            12 => 'lembarkerjavibrationcalibrator_t',
            13 => 'lembarkerjacalipermicrometer_t',
            14 => 'lembarkerjadialindicator_t',
            15 => 'lembarkerjaboregauge_t',
            16 => 'lembarkerjaclampmetersumber_t',
            18 => 'lembarkerjacontinuitysource_t',
            19 => 'lembarkerjatachometer_t',
            20 => 'lembarkerjainsulationmeterresistansi_t',
            21 => 'lembarkerjatorquewrench_t',
            22 => 'lembarkerjatemperatureindicator_t',
            23 => 'lembarkerjaultrasonicthickness_t',
            24 => 'lembarkerjathicknessgauge_t',
            33 => 'lembarkerjafeelergauge_t',
            25 => 'lembarkerjainsulationtester_t',
            26 => 'lembarkerjainsulationsource_t',
            27 => 'lembarkerjamicrometerhead_t',
            28 => 'lembarkerjaoutsidemicrometer_t',
            29 => 'lembarkerjaoscilloscope_t',
            30 => 'lembarkerjathermocouplertdsimulator_t',
        ];

        return $tables[(int) $order->sublingkupfk] ?? null;
    }

    private function measurementWorksheetUsesStatusEnabled(string $table): bool
    {
        return !in_array($table, [
            'lembarkerja_t',
            'lembarkerjacalipermicrometer_t',
            'lembarkerjadialindicator_t',
            'lembarkerjaboregauge_t',
            'lembarkerjamicrometerhead_t',
            'lembarkerjaoutsidemicrometer_t',
            'lembarkerjaoscilloscope_t',
        ], true);
    }

    private function normalizeMeasurementCorrection(object $row, string $table, object $order): ?array
    {
        $data = (array) $row;
        $correctionRaw = $this->firstMeasurementValue($data, [
            'koreksi',
            'correction',
        ]);
        $correction = $this->measurementNumber($correctionRaw);
        if ($correction === null) {
            return null;
        }

        $referenceRaw = $this->firstMeasurementValue($data, [
            'pembacaan_alat',
            'uut',
            'penunjukan_uut',
            'pembacaanalat',
            'indikasi_instrumen',
            'nilai_uut',
            'rata_rata_uut',
            'rata_rata',
            'penunjukan_standar',
            'vibrasi_reference',
            'rentang',
            'frekuensi',
        ]);
        $reference = $this->measurementNumber($referenceRaw);
        if ($reference === null) {
            return null;
        }

        $rangeRaw = $data['rentang'] ?? $data['frekuensi'] ?? null;
        $referenceUnit = $this->firstMeasurementValue($data, [
            'pembacaan_alat_satuan',
            'uut_satuan',
            'penunjukan_uut_satuan',
            'rentang_satuan',
        ]) ?? $this->measurementUnit($referenceRaw) ?? $this->measurementUnit($rangeRaw);
        $correctionUnit = $this->firstMeasurementValue($data, [
            'koreksi_satuan',
            'correction_unit',
        ]) ?? $this->measurementUnit($correctionRaw) ?? $referenceUnit;

        return [
            'source_noorder' => $order->noorderalat,
            'worksheet' => $table,
            'row_id' => $data['norec'] ?? $data['id'] ?? null,
            'group' => $data['group'] ?? null,
            'no' => $data['no'] ?? null,
            'jenis' => $data['jenis'] ?? null,
            'rentang' => $rangeRaw,
            'range_value' => $this->measurementNumber($rangeRaw),
            'range_unit' => $this->measurementUnit($rangeRaw) ?? $referenceUnit,
            'reference_raw' => $referenceRaw,
            'reference_value' => $reference,
            'reference_unit' => $referenceUnit,
            'correction_raw' => $correctionRaw,
            'correction_value' => $correction,
            'correction_unit' => $correctionUnit,
            'uncertainty' => $data['ketidakpastian'] ?? null,
            'source_created_at' => $data['created_at'] ?? null,
        ];
    }

    private function firstMeasurementValue(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && trim((string) $data[$key]) !== '') {
                return $data[$key];
            }
        }

        return null;
    }

    private function measurementUnit($value): ?string
    {
        if ($value === null || is_bool($value)) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (!preg_match_all('/[A-Za-z%\x{00B0}\x{00B5}\x{03BC}\x{03A9}][A-Za-z0-9%\/\.\^\-\x{00B0}\x{00B5}\x{03BC}\x{03A9}]*/u', $text, $matches)) {
            return null;
        }

        $unit = trim(implode(' ', $matches[0]));
        return $unit !== '' ? $unit : null;
    }

    private function measurementNumber($value): ?float
    {
        if ($value === null || is_bool($value)) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/[-+]?(?:\d+(?:\.\d+)?|\.\d+)/', $text, $matches)) {
            return null;
        }

        return (float) $matches[0];
    }

    public function getNav(Request $request)
    {
        $kdprofile = (int)($request->get('kdprofile', 1));

        $parents = DB::table('navmenu_m')
            ->select('id', 'namamenu', 'slug', 'link', 'has_dropdown', 'is_external', 'urut')
            ->where('statusenabled', true)
            ->where('kdprofile', $kdprofile)
            ->orderBy('urut')
            ->get();

        $children = DB::table('navmenu_d')
            ->select('id', 'navmenu_m_id as parent_id', 'namaitem', 'link', 'is_external', 'urut')
            ->where('statusenabled', true)
            ->where('kdprofile', $kdprofile)
            ->orderBy('urut')
            ->get();

        $grouped = [];
        foreach ($parents as $p) {
            $grouped[] = [
                'id'           => $p->id,
                'namamenu'     => $p->namamenu,
                'slug'         => $p->slug,
                'link'         => $p->link,
                'has_dropdown' => (bool)$p->has_dropdown,
                'is_external'  => (bool)$p->is_external,
                'urut'         => (int)$p->urut,
                'children'     => $children->where('parent_id', $p->id)->values()->all(),
            ];
        }

        return $this->respond([
            'data'    => $grouped,
            'message' => 'ok',
        ]);
    }

    public function getNavBySlug(Request $request, $slug)
    {
        $kdprofile = (int)($request->get('kdprofile', 1));

        $parent = DB::table('navmenu_m')
            ->select('id', 'namamenu', 'slug', 'link', 'has_dropdown', 'is_external', 'urut')
            ->where('statusenabled', true)
            ->where('kdprofile', $kdprofile)
            ->where('slug', $slug)
            ->first();

        if (!$parent) {
            return $this->respond(['data' => null, 'message' => 'not found'], 404);
        }

        $children = DB::table('navmenu_d')
            ->select('id', 'navmenu_m_id as parent_id', 'namaitem', 'link', 'is_external', 'urut')
            ->where('statusenabled', true)
            ->where('kdprofile', $kdprofile)
            ->where('navmenu_m_id', $parent->id)
            ->orderBy('urut')
            ->get();

        return $this->respond([
            'data' => [
                'id'           => $parent->id,
                'namamenu'     => $parent->namamenu,
                'slug'         => $parent->slug,
                'link'         => $parent->link,
                'has_dropdown' => (bool)$parent->has_dropdown,
                'is_external'  => (bool)$parent->is_external,
                'urut'         => (int)$parent->urut,
                'children'     => $children,
            ],
            'message' => 'ok',
        ]);
    }

    public function createToken($username)
    {
        $class = new Builder();
        $signer = new Sha512();
        $now = time();

        $token = $class->setHeader('alg', config('app.JWT_ALG'))
            ->set('sub', $username)
            ->expiresAt($now + (config('app.JWT_EXPIRED_MINUTE') * 60))
            ->sign($signer, config('app.JWT_KEY'))
            ->getToken();

        return (string) $token;
    }

    public function getMobilePrintToken(Request $request)
    {
        try {
            $username = trim((string) $request->input('username'));
            $password = trim((string) $request->input('password'));
            $norecDetail = trim((string) $request->input('norec_detail'));
            $jenis = trim((string) $request->input('jenis', 'sertifikat'));
            $kdprofile = (int) ($request->input('kdprofile', 1));

            if ($username === '' || $password === '') {
                return response()->json([
                    'status' => 400,
                    'message' => 'username dan password wajib diisi',
                ], 400);
            }

            // hardcode sesuai permintaan
            if ($username !== 'Mahzumi' || $password !== 'ulab123') {
                return response()->json([
                    'status' => 401,
                    'message' => 'Username atau password salah',
                ], 401);
            }

            $token = $this->createToken($username);

            $baseService = rtrim(config('app.url'), '/') . '/service/';
            $printUrl = null;

            if ($norecDetail !== '') {
                if ($jenis === 'repair') {
                    $printUrl = $baseService
                        . 'registrasi/cetak-laporan-repair-pdf?norec_detail=' . urlencode($norecDetail)
                        . '&user=' . urlencode($username)
                        . '&kdprofile=' . $kdprofile
                        . '&token=' . urlencode($token);
                } else {
                    $printUrl = $baseService
                        . 'registrasi/cetak-sertif-customer-pdf?norec_detail=' . urlencode($norecDetail)
                        . '&user=' . urlencode($username)
                        . '&kdprofile=' . $kdprofile
                        . '&token=' . urlencode($token);
                }
            }

            return response()->json([
                'status' => 200,
                'message' => 'Sukses',
                'data' => [
                    'token' => $token,
                    'user' => $username,
                    'kdprofile' => $kdprofile,
                    'norec_detail' => $norecDetail,
                    'jenis' => $jenis,
                    'print_url' => $printUrl,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Gagal generate token print',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function validasiPbjPublic(Request $request)
    {
        try {
            $norec = $request->query('norec');
            $ttd = strtolower((string) $request->query('ttd', 'pemohon'));

            $allowedTtd = ['pemohon', 'asman', 'manager'];
            if (!in_array($ttd, $allowedTtd)) {
                $ttd = 'pemohon';
            }

            if (empty($norec)) {
                return $this->respond([
                    'success' => false,
                    'jenis' => 'pbj',
                    'message' => 'Kode dokumen tidak ditemukan pada URL validasi.',
                    'data' => null,
                ], 200, 'Validasi PBJ');
            }

            $pbj = DB::table('pengajuanpbj_t as ppbj')
                ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'ppbj.pegawaifk')
                ->leftJoin('pegawai_m as pg1', 'pg1.id', '=', 'ppbj.asmanveriffk')
                ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'ppbj.managerveriffk')
                ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan2fk')
                ->leftJoin('jenispbj_m as jpbj', 'jpbj.id', '=', 'ppbj.jenispbj')
                ->leftJoin('pemohonpbj_m as phpbj', 'phpbj.id', '=', 'ppbj.pemohon')
                ->leftJoin('pengadaanpbj_m as pgpbj', 'pgpbj.id', '=', 'ppbj.pengadaan')
                ->leftJoin('lokasikalibrasi_m as lkal', 'lkal.id', '=', 'ppbj.lokasipbjfk')
                ->leftJoin('prioritaspbj_m as prpbj', 'prpbj.id', '=', 'ppbj.prioritasfk')
                ->leftJoin('dasaranggaranpbj_m as dspbj', 'dspbj.id', '=', 'ppbj.kebutuhan')
                ->leftJoin('managerbidangpbj_m as mbpbj', 'mbpbj.id', '=', 'ppbj.bidang')
                ->leftJoin('kepadapbj_m as kpbj', 'kpbj.id', '=', 'ppbj.kepada')
                ->leftJoin(DB::raw('mitra_m as mupbj'), DB::raw('CAST(mupbj.id AS INTEGER)'), '=', 'ppbj.user')
                ->select(
                    'ppbj.norec',
                    'ppbj.nosuratpbj',
                    'ppbj.tglpengajuan',
                    'ppbj.judulpermintaan',
                    'ppbj.statusenabled',
                    'ppbj.statusorder',
                    'ppbj.statusorderasman',
                    'ppbj.statusordermanager',
                    'ppbj.tglverifasman',
                    'ppbj.tglverifmanager',
                    'ppbj.prk',
                    'ppbj.wo',
                    'ppbj.project',
                    'ppbj.noproyek',
                    'ppbj.costcode',
                    'ppbj.ppn',
                    DB::raw("COALESCE(NULLIF(TRIM(ppbj.kebutuhanmanual), ''), dspbj.dasar) as kebutuhan"),
                    DB::raw("COALESCE(NULLIF(TRIM(ppbj.bidangmanual), ''), mbpbj.managerbidang) as bidang"),
                    DB::raw("COALESCE(NULLIF(TRIM(ppbj.kepadamanual), ''), kpbj.kepada) as kepada"),
                    DB::raw("COALESCE(NULLIF(TRIM(ppbj.usermanual), ''), mupbj.namaperusahaan) as user_unit"),
                    'pg.namalengkap as nama_pemohon',
                    'pg.nid as nid_pemohon',
                    'jb.namajabatan as jabatan_pemohon',
                    'pg1.namalengkap as nama_asman',
                    'pg1.nid as nid_asman',
                    'pg2.namalengkap as nama_manager',
                    'pg2.nid as nid_manager',
                    'jpbj.jenispbj',
                    'phpbj.pemohonpbj',
                    'pgpbj.pengadaanpbj',
                    'lkal.lokasi as lokasipbj',
                    'prpbj.prioritaspbj'
                )
                ->where('ppbj.norec', $norec)
                ->where('ppbj.statusenabled', true)
                ->first();

            if (!$pbj) {
                return $this->respond([
                    'success' => false,
                    'jenis' => 'pbj',
                    'message' => 'Dokumen PBJ tidak ditemukan atau sudah tidak aktif.',
                    'data' => null,
                ], 200, 'Validasi PBJ');
            }

            $detail = DB::table('pengajuandetailpbj_t')
                ->where('noregpbjfk', $pbj->norec)
                ->where('statusenabled', true)
                ->select(
                    'norec',
                    'namaitem',
                    'uraianitem',
                    'stockcode',
                    'banyak',
                    'satuan',
                    'hargasatuan',
                    'keterangan'
                )
                ->get();

            $statusAsman = (int) ($pbj->statusorderasman ?? 0);
            $statusManager = (int) ($pbj->statusordermanager ?? 0);

            $signers = [
                'pemohon' => [
                    'key' => 'pemohon',
                    'label' => 'Pemohon',
                    'nama' => $pbj->nama_pemohon ?: '-',
                    'nid' => $pbj->nid_pemohon ?: '-',
                    'jabatan' => $pbj->jabatan_pemohon ?: 'Pemohon',
                    'status' => !empty($pbj->tglpengajuan) ? 'Tanda tangan tervalidasi' : 'Tanda tangan tervalidasi Draft',
                    'tanggal' => $pbj->tglpengajuan,
                    'is_valid' => !empty($pbj->tglpengajuan),
                    'keterangan' => 'QR yang discan adalah tanda tangan Pemohon pada dokumen PBJ.',
                ],
                'asman' => [
                    'key' => 'asman',
                    'label' => 'Assistant Manager',
                    'nama' => $pbj->nama_asman ?: '-',
                    'nid' => $pbj->nid_asman ?: '-',
                    'jabatan' => 'Assistant Manager Non Mechanical Workshop',
                    'status' => $statusAsman === 1 ? 'Tanda tangan tervalidasi' : ($statusAsman === 2 ? 'Ditolak' : 'Belum ditandatangani'),
                    'tanggal' => $pbj->tglverifasman,
                    'is_valid' => $statusAsman === 1,
                    'keterangan' => 'QR yang discan adalah tanda tangan Assistant Manager pada dokumen PBJ.',
                ],
                'manager' => [
                    'key' => 'manager',
                    'label' => 'Manager',
                    'nama' => $pbj->nama_manager ?: '-',
                    'nid' => $pbj->nid_manager ?: '-',
                    'jabatan' => 'Manager Repair Unit Maintenance Repair dan Overhaul',
                    'status' => $statusManager === 3 ? 'Tanda tangan tervalidasi' : ($statusManager === 4 ? 'Ditolak' : 'Belum ditandatangani'),
                    'tanggal' => $pbj->tglverifmanager,
                    'is_valid' => $statusManager === 3,
                    'keterangan' => 'QR yang discan adalah tanda tangan Manager pada dokumen PBJ.',
                ],
            ];

            return $this->respond([
                'success' => true,
                'jenis' => 'pbj',
                'message' => 'Tanda tangan PBJ ini terdaftar pada sistem resmi U-LAB.',
                'data' => [
                    'jenis_dokumen' => 'pbj',
                    'ttd_key' => $ttd,
                    'signer_focus' => $signers[$ttd],
                    'norec' => $pbj->norec,
                    'nomor_dokumen' => $pbj->nosuratpbj,
                    'nosuratpbj' => $pbj->nosuratpbj,
                    'tglpengajuan' => $pbj->tglpengajuan,
                    'judulpermintaan' => $pbj->judulpermintaan,
                    'jenispbj' => $pbj->jenispbj,
                    'kebutuhan' => $pbj->kebutuhan,
                    'bidang' => $pbj->bidang,
                    'kepada' => $pbj->kepada,
                    'user_unit' => $pbj->user_unit,
                    'pemohonpbj' => $pbj->pemohonpbj,
                    'pengadaanpbj' => $pbj->pengadaanpbj,
                    'lokasipbj' => $pbj->lokasipbj,
                    'prioritaspbj' => $pbj->prioritaspbj,
                    'prk' => $pbj->prk,
                    'wo' => $pbj->wo,
                    'project' => $pbj->project,
                    'noproyek' => $pbj->noproyek,
                    'costcode' => $pbj->costcode,
                    'ppn' => $pbj->ppn,
                    'jumlah_item' => $detail->count(),
                    'detail' => $detail,
                ],
            ], 200, 'Validasi tanda tangan PBJ sukses');
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'jenis' => 'pbj',
                'message' => 'Terjadi kesalahan saat memvalidasi tanda tangan PBJ.',
                'error' => $e->getMessage(),
                'data' => null,
            ], 200, 'Validasi tanda tangan PBJ gagal');
        }
    }

    public function validasiSertifikatPublic(Request $request)
    {
        try {
            $norecDetail = $request->query('norec_detail');
            $ttd = strtolower((string) $request->query('ttd', 'pelaksana'));

            $allowedTtd = ['pelaksana', 'penyelia', 'asman', 'manager'];
            if (!in_array($ttd, $allowedTtd)) {
                $ttd = 'pelaksana';
            }

            if (empty($norecDetail)) {
                return $this->respond([
                    'success' => false,
                    'jenis' => 'sertifikat',
                    'message' => 'Kode sertifikat tidak ditemukan pada URL validasi.',
                    'data' => null,
                ], 200, 'Validasi sertifikat');
            }

            $data = DB::table('mitraregistrasidetail_t as mtrd')
                ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
                ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
                ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
                ->leftJoin('pegawai_m as penyelia', 'penyelia.id', '=', 'mtrd.penyeliateknikfk')
                ->leftJoin('pegawai_m as pelaksana', 'pelaksana.id', '=', 'mtrd.pelaksanateknikfk')
                ->leftJoin('pegawai_m as asman', 'asman.id', '=', 'mtr.asmanveriffk')
                ->leftJoin('pegawai_m as manager', 'manager.id', '=', 'mtrd.managersetujulembarkerjafk')
                ->leftJoin('jabatan_m as jbPenyelia', 'jbPenyelia.id', '=', 'penyelia.jabatan1fk')
                ->leftJoin('jabatan_m as jbPelaksana', 'jbPelaksana.id', '=', 'pelaksana.jabatan1fk')
                ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
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
                ->leftJoin('lingkupkalibrasi_m as lkf', 'lkf.id', '=', 'mtrd.lingkupkalibrasifk')
                ->leftJoin('sublingkupkalibrasi_m as slkf', 'slkf.id', '=', 'mtrd.sublingkupfk')
                ->select(
                    'mtr.norec as norec_registrasi',
                    'mtrd.norec as norec_detail',
                    'mtr.tglregistrasi',
                    'mtr.nopendaftaran',
                    'mtrd.noorderalat',
                    'mtrd.nosertifikat',
                    'mtrd.tglkalibrasilembarkerja',
                    'mtrd.tglsetujupenyelialembarkerja',
                    'mtrd.tglsetujuasmanlembarkerja',
                    'mtrd.tglsetujumanagerlembarkerja',
                    'mtrd.tglsetujumanagerlembarkerja',
                    'mtrd.setujuilembarkerjamanager',
                    'mtrd.setujuilembarkerjaasman',
                    'mtrd.setujuilembarkerjapenyelia',
                    'mtrd.statuskanfk',
                    'mt.namaperusahaan',
                    'mt.alamatktr',
                    'lk.lokasi',
                    'pg.namalengkap as petugaskaji',
                    'penyelia.namalengkap as penyeliateknik',
                    'penyelia.nid as nidpenyelia',
                    'jbPenyelia.namajabatanulab as jabatanpenyelia',
                    'pelaksana.namalengkap as pelaksanateknik',
                    'pelaksana.nid as nidpelaksana',
                    'jbPelaksana.namajabatanulab as jabatanpelaksana',
                    'asman.namalengkap as asmanverifikasi',
                    'asman.nid as nidasman',
                    'manager.namalengkap as managerverifikasi',
                    'manager.nid as nidmanager',
                    DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as nama_alat"),
                    DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as merk"),
                    DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as tipe"),
                    DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as serial_number"),
                    'lkf.lingkupkalibrasi',
                    'slkf.namasublingkup as sublingkup'
                )
                ->where('mtr.statusenabled', true)
                ->where('mtrd.statusenabled', true)
                ->where('mtrd.norec', $norecDetail)
                ->first();

            if (!$data) {
                return $this->respond([
                    'success' => false,
                    'jenis' => 'sertifikat',
                    'message' => 'Sertifikat tidak ditemukan atau sudah tidak aktif.',
                    'data' => null,
                ], 200, 'Validasi sertifikat');
            }

            $signers = [
                'pelaksana' => [
                    'key' => 'pelaksana',
                    'label' => 'Pelaksana Teknik',
                    'nama' => $data->pelaksanateknik ?: '-',
                    'nid' => $data->nidpelaksana ?: '-',
                    'jabatan' => $data->jabatanpelaksana ?: 'Pelaksana Teknik',
                    'status' => !empty($data->tglkalibrasilembarkerja) ? 'Tanda tangan tervalidasi' : 'Belum ditandatangani',
                    'tanggal' => $data->tglkalibrasilembarkerja,
                    'is_valid' => !empty($data->tglkalibrasilembarkerja),
                    'keterangan' => 'QR yang discan adalah tanda tangan Pelaksana Teknik pada sertifikat.',
                ],
                'penyelia' => [
                    'key' => 'penyelia',
                    'label' => 'Penyelia Teknik',
                    'nama' => $data->penyeliateknik ?: '-',
                    'nid' => $data->nidpenyelia ?: '-',
                    'jabatan' => $data->jabatanpenyelia ?: 'Penyelia Teknik',
                    'status' => !empty($data->setujuilembarkerjapenyelia) ? 'Tanda tangan tervalidasi' : 'Belum ditandatangani',
                    'tanggal' => $data->tglsetujupenyelialembarkerja,
                    'is_valid' => !empty($data->setujuilembarkerjapenyelia),
                    'keterangan' => 'QR yang discan adalah tanda tangan Penyelia Teknik pada sertifikat.',
                ],
                'asman' => [
                    'key' => 'asman',
                    'label' => 'Assistant Manager',
                    'nama' => $data->asmanverifikasi ?: '-',
                    'nid' => $data->nidasman ?: '-',
                    'jabatan' => 'Assistant Manager',
                    'status' => !empty($data->setujuilembarkerjaasman) ? 'Tanda tangan tervalidasi' : 'Belum ditandatangani',
                    'tanggal' => $data->tglsetujuasmanlembarkerja,
                    'is_valid' => !empty($data->setujuilembarkerjaasman),
                    'keterangan' => 'QR yang discan adalah tanda tangan Assistant Manager pada sertifikat.',
                ],
                'manager' => [
                    'key' => 'manager',
                    'label' => 'Manager',
                    'nama' => $data->managerverifikasi ?: '-',
                    'nid' => $data->nidmanager ?: '-',
                    'jabatan' => 'Manager Repair',
                    'status' => !empty($data->setujuilembarkerjamanager) ? 'Tanda tangan tervalidasi' : 'Belum ditandatangani',
                    'tanggal' => $data->tglsetujumanagerlembarkerja,
                    'is_valid' => !empty($data->setujuilembarkerjamanager),
                    'keterangan' => 'QR yang discan adalah tanda tangan Manager pada sertifikat.',
                ],
            ];

            return $this->respond([
                'success' => true,
                'jenis' => 'sertifikat',
                'message' => 'Tanda tangan sertifikat ini terdaftar pada sistem resmi U-LAB.',
                'data' => [
                    'jenis_dokumen' => 'sertifikat',
                    'ttd_key' => $ttd,
                    'signer_focus' => $signers[$ttd],
                    'norec_registrasi' => $data->norec_registrasi,
                    'norec_detail' => $data->norec_detail,
                    'nomor_dokumen' => $data->nosertifikat,
                    'nopendaftaran' => $data->nopendaftaran,
                    'noorderalat' => $data->noorderalat,
                    'nosertifikat' => $data->nosertifikat,
                    'tglregistrasi' => $data->tglregistrasi,
                    'tglkalibrasi' => $data->tglkalibrasilembarkerja,
                    'tglsetujumanager' => $data->tglsetujumanagerlembarkerja,
                    'customer' => $data->namaperusahaan,
                    'alamat' => $data->alamatktr,
                    'lokasi' => $data->lokasi,
                    'nama_alat' => $data->nama_alat,
                    'merk' => $data->merk,
                    'tipe' => $data->tipe,
                    'serial_number' => $data->serial_number,
                    'lingkupkalibrasi' => $data->lingkupkalibrasi,
                    'sublingkup' => $data->sublingkup,
                    'petugaskaji' => $data->petugaskaji,
                ],
            ], 200, 'Validasi tanda tangan sertifikat sukses');
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'jenis' => 'sertifikat',
                'message' => 'Terjadi kesalahan saat memvalidasi tanda tangan sertifikat.',
                'error' => $e->getMessage(),
                'data' => null,
            ], 200, 'Validasi tanda tangan sertifikat gagal');
        }
    }

    public function validasiSuratJalan(Request $request)
    {
        try {
            $norec = $request->query('norec');
            $ttdKey = strtolower((string) $request->query('ttd', 'pembuat'));

            $allowedTtd = ['pembuat', 'pembawa', 'asman'];
            if (!in_array($ttdKey, $allowedTtd)) {
                $ttdKey = 'pembuat';
            }

            if (empty($norec)) {
                return $this->respond([
                    'success' => false,
                    'jenis' => 'surat_jalan',
                    'message' => 'Kode surat jalan tidak ditemukan pada URL validasi.',
                    'data' => null,
                ], 200, 'Validasi surat jalan');
            }

            $head = DB::table('suratjalan_t as sj')
                ->leftJoin('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'sj.noregistrasifk')
                ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
                ->leftJoin('pegawai_m as pg_buat', 'pg_buat.id', '=', 'sj.petugasfk')
                ->leftJoin('jabatan_m as jb_buat', 'jb_buat.id', '=', 'pg_buat.jabatan1fk')
                ->leftJoin('pegawai_m as pg_bawa', 'pg_bawa.id', '=', 'sj.pegawaibawafk')
                ->leftJoin('jabatan_m as jb_bawa', 'jb_bawa.id', '=', 'pg_bawa.jabatan1fk')
                ->leftJoin('pegawai_m as pg_asman', 'pg_asman.id', '=', 'sj.asmanfk')
                ->leftJoin('jabatan_m as jb_asman', 'jb_asman.id', '=', 'pg_asman.jabatan1fk')
                ->select(
                    'sj.norec',
                    'sj.nosuratjalan',
                    'sj.sumber',
                    'sj.noregistrasifk',
                    'sj.nopendaftaran',
                    'sj.diberikankepada',
                    'sj.berdasarkan',
                    'sj.tanggalsurat',
                    'sj.tujuan',
                    'sj.barangbarangdari',
                    'sj.kendaraan',
                    'sj.nomorpolisi',
                    'sj.pengemudi',
                    'sj.pegawaibawafk',
                    'sj.namapegawaibawa',
                    'sj.statussuratjalan',
                    'sj.keteranganstatus',
                    'sj.tglajukan',
                    'sj.tglasman',
                    'sj.petugasfk',
                    'sj.namapetugas',
                    'sj.asmanfk',
                    'sj.namaasman',
                    'sj.created_at',
                    'sj.updated_at',
                    'mt.namaperusahaan',
                    'pg_buat.namalengkap as nama_pembuat_master',
                    'pg_buat.nid as nid_pembuat',
                    'jb_buat.namajabatanulab as jabatan_pembuat',
                    'pg_bawa.namalengkap as nama_pembawa_master',
                    'pg_bawa.nid as nid_pembawa',
                    'jb_bawa.namajabatanulab as jabatan_pembawa',
                    'pg_asman.namalengkap as nama_asman_master',
                    'pg_asman.nid as nid_asman',
                    'jb_asman.namajabatan as jabatan_asman'
                )
                ->where('sj.statusenabled', true)
                ->where('sj.norec', $norec)
                ->first();

            if (!$head) {
                return $this->respond([
                    'success' => false,
                    'jenis' => 'surat_jalan',
                    'message' => 'Surat jalan tidak ditemukan pada sistem resmi U-LAB.',
                    'data' => null,
                ], 200, 'Validasi surat jalan');
            }

            $detail = DB::table('suratjalandetail_t')
                ->select(
                    'norec',
                    'nourut',
                    'namabarang',
                    'namamerk',
                    'namatipe',
                    'namaserialnumber',
                    'jumlah',
                    'satuan',
                    'ket'
                )
                ->where('statusenabled', true)
                ->where('suratjalanfk', $head->norec)
                ->orderBy('nourut')
                ->get();

            $statusSuratJalan = 'Diajukan';
            if ((int) $head->statussuratjalan === 2) {
                $statusSuratJalan = 'Disetujui Asman';
            } elseif ((int) $head->statussuratjalan === 3) {
                $statusSuratJalan = 'Ditolak Asman';
            }

            $signerFocus = [
                'key' => $ttdKey,
                'label' => '-',
                'nama' => null,
                'nid' => null,
                'jabatan' => null,
                'status' => null,
                'tanggal' => null,
                'is_valid' => false,
                'keterangan' => null,
            ];

            if ($ttdKey === 'pembuat') {
                $nama = $head->namapetugas ?: $head->nama_pembuat_master;

                $signerFocus = [
                    'key' => 'pembuat',
                    'label' => 'Yang Membuat',
                    'nama' => $nama,
                    'nid' => $head->nid_pembuat,
                    'jabatan' => $head->jabatan_pembuat ?: 'Petugas Registrasi',
                    'status' => !empty($nama)
                        ? 'Tercatat sebagai pembuat surat jalan'
                        : 'Data pembuat belum lengkap',
                    'tanggal' => $head->tglajukan ?: $head->created_at,
                    'is_valid' => !empty($nama),
                    'keterangan' => 'QR ini memvalidasi bagian tanda tangan Yang Membuat pada dokumen Surat Jalan U-LAB.',
                ];
            }

            if ($ttdKey === 'pembawa') {
                $nama = $head->namapegawaibawa ?: $head->nama_pembawa_master;

                $signerFocus = [
                    'key' => 'pembawa',
                    'label' => 'Yang Membawa',
                    'nama' => $nama,
                    'nid' => $head->nid_pembawa,
                    'jabatan' => $head->jabatan_pembawa ?: 'Petugas Pembawa',
                    'status' => !empty($nama)
                        ? 'Tercatat sebagai pembawa barang'
                        : 'Data pembawa belum lengkap',
                    'tanggal' => $head->tanggalsurat ?: $head->created_at,
                    'is_valid' => !empty($nama),
                    'keterangan' => 'QR ini memvalidasi bagian tanda tangan Yang Membawa pada dokumen Surat Jalan U-LAB.',
                ];
            }

            if ($ttdKey === 'asman') {
                $nama = $head->namaasman ?: $head->nama_asman_master;
                $isApproved = (int) $head->statussuratjalan === 2;

                $signerFocus = [
                    'key' => 'asman',
                    'label' => 'Mengetahui / Asman',
                    'nama' => $nama,
                    'nid' => $head->nid_asman,
                    'jabatan' => $head->jabatan_asman ?: 'Assistant Manager Non Mechanical Workshop',
                    'status' => $isApproved ? 'Disetujui Asman' : $statusSuratJalan,
                    'tanggal' => $head->tglasman,
                    'is_valid' => $isApproved && !empty($nama),
                    'keterangan' => $isApproved
                        ? 'QR ini memvalidasi persetujuan Asman pada dokumen Surat Jalan U-LAB.'
                        : 'Surat jalan ini belum memiliki persetujuan Asman yang valid.',
                ];
            }

            $payload = [
                'jenis_dokumen' => 'surat_jalan',
                'ttd_key' => $ttdKey,
                'signer_focus' => $signerFocus,

                'norec' => $head->norec,
                'nosuratjalan' => $head->nosuratjalan,
                'sumber' => $head->sumber,
                'noregistrasifk' => $head->noregistrasifk,
                'nopendaftaran' => $head->nopendaftaran,

                'diberikankepada' => $head->diberikankepada,
                'berdasarkan' => $head->berdasarkan,
                'tanggalsurat' => $head->tanggalsurat,
                'tujuan' => $head->tujuan,
                'barangbarangdari' => $head->barangbarangdari,

                'kendaraan' => $head->kendaraan,
                'nomorpolisi' => $head->nomorpolisi,
                'pengemudi' => $head->pengemudi,

                'statussuratjalan' => $head->statussuratjalan,
                'status_surat_jalan' => $statusSuratJalan,
                'keteranganstatus' => $head->keteranganstatus,
                'tglajukan' => $head->tglajukan,
                'tglasman' => $head->tglasman,

                'namapetugas' => $head->namapetugas ?: $head->nama_pembuat_master,
                'namapegawaibawa' => $head->namapegawaibawa ?: $head->nama_pembawa_master,
                'namaasman' => $head->namaasman ?: $head->nama_asman_master,

                'jumlah_item' => count($detail),
                'detail' => $detail,
            ];

            if (!$signerFocus['is_valid']) {
                return $this->respond([
                    'success' => false,
                    'jenis' => 'surat_jalan',
                    'message' => 'Tanda tangan surat jalan belum valid atau belum lengkap pada sistem resmi U-LAB.',
                    'data' => $payload,
                ], 200, 'Validasi surat jalan');
            }

            return $this->respond([
                'success' => true,
                'jenis' => 'surat_jalan',
                'message' => 'Tanda tangan surat jalan ini tercatat pada sistem resmi U-LAB.',
                'data' => $payload,
            ], 200, 'Validasi surat jalan');
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'jenis' => 'surat_jalan',
                'message' => 'Terjadi kesalahan saat validasi surat jalan.',
                'error' => $e->getMessage(),
                'data' => null,
            ], 200, 'Validasi surat jalan');
        }
    }
}
