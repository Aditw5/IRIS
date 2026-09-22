<?php

namespace App\Http\Controllers\RBK;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RbkCtrl extends Controller
{
    public function getUnitCandidates()
    {
        $units = DB::table('mitra_m as mt')
            ->select('mt.id as value', 'mt.namaperusahaan as label')
            ->where('mt.statusenabled', true)
            ->where(function ($query) {
                $query->whereNull('mt.issurkes')
                    ->orWhere('mt.issurkes', false);
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('mitra_m as registered')
                    ->whereColumn('registered.id', '<>', 'mt.id')
                    ->where('registered.statusenabled', true)
                    ->where('registered.issurkes', true)
                    ->whereRaw('LOWER(TRIM(registered.namaperusahaan)) = LOWER(TRIM(mt.namaperusahaan))');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rbk_rencana_t as rbk')
                    ->whereRaw('CAST(rbk.unitfk AS TEXT) = CAST(mt.id AS TEXT)')
                    ->where('rbk.statusenabled', true);
            })
            ->orderBy('mt.namaperusahaan')
            ->get();

        return $this->respond([
            'data' => $units,
            'message' => 'ok',
        ]);
    }

    public function addUnit(Request $request)
    {
        $unitId = (int) $request->input('unit_id');
        $lokasiId = (int) $request->input('lokasi_id');

        if ($unitId <= 0) {
            return $this->respond([], 422, 'Unit wajib dipilih.');
        }

        if (!in_array($lokasiId, [1, 2], true)) {
            return $this->respond([], 422, 'Lokasi RBK harus Jakarta atau Gresik.');
        }

        return DB::transaction(function () use ($unitId, $lokasiId) {
            $unit = DB::table('mitra_m')
                ->select('id', 'namaperusahaan', 'issurkes', 'lokasisurkes')
                ->where('id', $unitId)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$unit) {
                return $this->respond([], 404, 'Unit tidak ditemukan atau sudah tidak aktif.');
            }

            $existingRbk = DB::table('rbk_rencana_t')
                ->select('lokasi_id')
                ->where('unitfk', $unitId)
                ->where('statusenabled', true)
                ->first();

            $registeredSameName = DB::table('mitra_m as registered')
                ->leftJoin('rbk_rencana_t as rbk', function ($join) {
                    $join->whereRaw('CAST(rbk.unitfk AS TEXT) = CAST(registered.id AS TEXT)')
                        ->where('rbk.statusenabled', true);
                })
                ->select(DB::raw('COALESCE(registered.lokasisurkes, rbk.lokasi_id) as lokasi_id'))
                ->where('registered.id', '<>', $unitId)
                ->where('registered.statusenabled', true)
                ->whereRaw('LOWER(TRIM(registered.namaperusahaan)) = LOWER(TRIM(?))', [
                    $unit->namaperusahaan,
                ])
                ->where(function ($query) {
                    $query->where('registered.issurkes', true)
                        ->orWhereNotNull('rbk.norec');
                })
                ->first();

            $isSurkes = in_array($unit->issurkes, [true, 1, '1', 't', 'true'], true);
            if ($isSurkes || $existingRbk || $registeredSameName) {
                $registeredLocationId = $isSurkes
                    ? (int) $unit->lokasisurkes
                    : (int) ($existingRbk->lokasi_id ?? $registeredSameName->lokasi_id);
                $lokasi = $registeredLocationId === 2 ? 'Gresik' : 'Jakarta';

                return $this->respond([], 409, "Unit sudah terdaftar sebagai unit RBK {$lokasi}.");
            }

            DB::table('mitra_m')
                ->where('id', $unitId)
                ->update([
                    'issurkes' => true,
                    'lokasisurkes' => $lokasiId,
                ]);

            return $this->respond([
                'data' => [
                    'unit_id' => $unit->id,
                    'namaperusahaan' => $unit->namaperusahaan,
                    'lokasi_id' => $lokasiId,
                    'lokasi' => $lokasiId === 2 ? 'Gresik' : 'Jakarta',
                ],
                'message' => 'ok',
            ], 200, 'Unit berhasil ditambahkan ke RBK.');
        });
    }

    public function getUnitsMonitoring(Request $request)
    {
        $year = (int)($request->input('year', date('Y')));
        $keyword = $request->input('keyword');
        $sortBy = $request->input('sort_by', 'total_rencana');
        $lokasiParam = $request->input('lokasi_id', 1);
        $lokasiId = is_numeric($lokasiParam)
            ? (int)$lokasiParam
            : (strtolower((string)$lokasiParam) === 'gresik' ? 2 : 1);

        $unitsQ = DB::table('mitra_m as mt')
            ->select('mt.id as unit_id', 'mt.namaperusahaan')
            ->where('mt.statusenabled', true)
            ->where('mt.issurkes', true)
            ->where('mt.lokasisurkes', $lokasiId);

        if (!empty($keyword)) {
            $unitsQ->whereRaw("LOWER(mt.namaperusahaan) LIKE ?", ['%' . strtolower($keyword) . '%']);
        }

        $units = $unitsQ->orderBy('mt.namaperusahaan')->get();
        $unitIds = $units->pluck('unit_id')->all();

        if (empty($unitIds)) {
            return $this->respond([
                'data' => [],
                'message' => 'ok',
            ]);
        }

        $rbkHeaders = DB::table('rbk_rencana_t')
            ->select(
                'norec',
                'unitfk',
                'lokasi_id',
                'no_surat',
                'no_prk',
                'cost_code',
                'jadwal_kalibrasi'
            )
            ->where('statusenabled', true)
            ->where('tahun', $year)
            ->where('lokasi_id', $lokasiId)
            ->whereIn('unitfk', $unitIds)
            ->get();

        $rbkByUnit = $rbkHeaders->groupBy('unitfk');

        $rencanaRows = DB::table('rbk_rencana_item_t as it')
            ->join('rbk_rencana_t as hd', 'hd.norec', '=', 'it.rbk_norec')
            ->select(
                'hd.unitfk',
                'it.kategori',
                DB::raw("COALESCE(SUM(it.nilai_rencana),0) as total_rencana")
            )
            ->where('hd.statusenabled', true)
            ->where('it.statusenabled', true)
            ->where('hd.tahun', $year)
            ->where('hd.lokasi_id', $lokasiId)
            ->whereIn('hd.unitfk', $unitIds)
            ->groupBy('hd.unitfk', 'it.kategori')
            ->get();

        $rencanaMap = [];
        foreach ($rencanaRows as $r) {
            $rencanaMap[$r->unitfk][$r->kategori] = (float)$r->total_rencana;
        }

        $useHybridSource = $request->boolean('source_pbj');
        $manualRealisasiRows = $this->getManualRealisasiSummaryRows(
            $unitIds,
            $year,
            $lokasiId,
            $useHybridSource
        );

        if ($useHybridSource) {
            $pbjRealisasiRows = $this->getPbjRealisasiRows($rbkHeaders, $year)
                ->groupBy(function ($row) {
                    return $row->unitfk . '|' . $row->kategori;
                })
                ->map(function ($rows) {
                    $first = $rows->first();
                    return (object) [
                        'unitfk' => $first->unitfk,
                        'kategori' => $first->kategori,
                        'total_realisasi' => $rows->sum('nilai_realisasi'),
                    ];
                })
                ->values();

            $realisasiRows = $pbjRealisasiRows
                ->concat($manualRealisasiRows)
                ->groupBy(function ($row) {
                    return $row->unitfk . '|' . $row->kategori;
                })
                ->map(function ($rows) {
                    $first = $rows->first();
                    return (object) [
                        'unitfk' => $first->unitfk,
                        'kategori' => $first->kategori,
                        'total_realisasi' => $rows->sum('total_realisasi'),
                    ];
                })
                ->values();
        } else {
            $realisasiRows = $manualRealisasiRows;
        }

        $realisasiMap = [];
        foreach ($realisasiRows as $r) {
            $realisasiMap[$r->unitfk][$r->kategori] = (float)$r->total_realisasi;
        }

        $data = [];
        foreach ($units as $u) {
            $unitfk = $u->unit_id;

            $hdr = $rbkByUnit->get($unitfk);
            $header = null;
            if ($hdr && $hdr->count() > 0) {
                $header = $hdr->first();
            }

            $manpowerR = (float)($rencanaMap[$unitfk]['manpower'] ?? 0);
            $materialR = (float)($rencanaMap[$unitfk]['material'] ?? 0);
            $mobilR    = (float)($rencanaMap[$unitfk]['mobilisasi'] ?? 0);

            $manpowerA = (float)($realisasiMap[$unitfk]['manpower'] ?? 0);
            $materialA = (float)($realisasiMap[$unitfk]['material'] ?? 0);
            $mobilA    = (float)($realisasiMap[$unitfk]['mobilisasi'] ?? 0);

            $totalRencana = $manpowerR + $materialR + $mobilR;
            $totalRealisasi = $manpowerA + $materialA + $mobilA;

            $progress = 0;
            if ($totalRencana > 0) {
                $progress = round(($totalRealisasi / $totalRencana) * 100);
            }

            $data[] = [
                'unit_id' => $unitfk,
                'namaperusahaan' => $u->namaperusahaan,
                'no_surat'  => $header->no_surat ?? null,
                'no_prk'    => $header->no_prk ?? null,
                'cost_code' => $header->cost_code ?? null,
                'jadwal_kalibrasi' => $header->jadwal_kalibrasi ?? null,
                'has_rbk' => ($rbkByUnit->has($unitfk)),
                'realisasi_source' => $useHybridSource ? 'hybrid' : 'manual',
                'summary' => [
                    'total_rencana' => $totalRencana,
                    'total_realisasi' => $totalRealisasi,
                    'total_sisa' => max($totalRencana - $totalRealisasi, 0),
                    'progress' => $progress,
                    'kategori' => [
                        'manpower' => [
                            'rencana' => $manpowerR,
                            'realisasi' => $manpowerA,
                            'sisa' => $manpowerR - $manpowerA,
                        ],
                        'material' => [
                            'rencana' => $materialR,
                            'realisasi' => $materialA,
                            'sisa' => $materialR - $materialA,
                        ],
                        'mobilisasi' => [
                            'rencana' => $mobilR,
                            'realisasi' => $mobilA,
                            'sisa' => $mobilR - $mobilA,
                        ],
                    ],
                ],
            ];
        }

        $data = collect($data)->sortByDesc(function ($row) use ($sortBy) {
            if ($sortBy === 'total_realisasi') return (float)($row['summary']['total_realisasi'] ?? 0);
            if ($sortBy === 'progress') return (int)($row['summary']['progress'] ?? 0);
            return (float)($row['summary']['total_rencana'] ?? 0);
        })->values()->all();

        return $this->respond([
            'data' => $data,
            'message' => 'ok',
        ]);
    }

    public function getRbkByUnit(Request $request)
    {
        $unitfk = (int)$request->input('unitfk');
        $tahun = (int)$request->input('tahun', date('Y'));
        $lokasiId = (int)$request->input('lokasi_id', 1);

        $rbk = DB::table('rbk_rencana_t as hd')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'hd.created_by')
            ->select(
                'hd.*',
                'pg.namalengkap as created_by_nama'
            )
            ->where('hd.statusenabled', true)
            ->where('hd.unitfk', $unitfk)
            ->where('hd.tahun', $tahun)
            ->where('hd.lokasi_id', $lokasiId)
            ->first();

        if (!$rbk) {
            return $this->respond([
                'data' => null,
                'message' => 'rbk_not_found',
            ]);
        }

        $items = DB::table('rbk_rencana_item_t as it')
            ->select(
                'it.norec',
                'it.kategori',
                'it.uraian',
                'it.qty',
                'it.satuan',
                'it.nilai_rencana',
                'it.urutan',
                DB::raw("COALESCE((
                    SELECT SUM(rl.nilai_realisasi)
                    FROM rbk_realisasi_t rl
                    WHERE rl.statusenabled = true
                      AND rl.item_norec = it.norec
                      AND rl.sumberrealisasi = 'legacy'
                ), 0) as total_realisasi")
            )
            ->where('it.statusenabled', true)
            ->where('it.rbk_norec', $rbk->norec)
            ->orderBy('it.kategori')
            ->orderBy('it.urutan')
            ->get();

        $usePbjSource = $request->boolean('source_pbj');
        $pbjRows = collect();
        $pbjByKategori = collect();
        $hybridManualRows = collect();
        $hybridManualByKategori = collect();

        if ($usePbjSource) {
            $pbjRows = $this->getPbjRealisasiRows(collect([$rbk]), $tahun);
            $pbjByKategori = $pbjRows
                ->groupBy('kategori')
                ->map(function ($rows) {
                    return (float) $rows->sum('nilai_realisasi');
                });

            $hybridManualRows = $this->getManualRealisasiRowsByUnit(
                $unitfk,
                $tahun,
                $lokasiId,
                true
            );
            $hybridManualByKategori = $hybridManualRows
                ->groupBy('kategori')
                ->map(function ($rows) {
                    return (float) $rows->sum('nilai_realisasi');
                });
            $hybridManualByItem = $hybridManualRows
                ->groupBy('item_norec')
                ->map(function ($rows) {
                    return (float) $rows->sum('nilai_realisasi');
                });

            foreach ($items as $item) {
                // PBJ berada pada level kategori. Input manual hybrid tetap dapat
                // ditautkan ke item rencana yang dipilih pengguna.
                $item->total_realisasi = (float) $hybridManualByItem->get($item->norec, 0);
            }
        }

        $summary = [
            'manpower' => ['rencana' => 0, 'realisasi' => 0, 'sisa' => 0],
            'material' => ['rencana' => 0, 'realisasi' => 0, 'sisa' => 0],
            'mobilisasi' => ['rencana' => 0, 'realisasi' => 0, 'sisa' => 0],
            'total' => ['rencana' => 0, 'realisasi' => 0, 'sisa' => 0, 'progress' => 0],
        ];

        foreach ($items as $it) {
            $kat = $it->kategori;
            $rencana = (float)$it->nilai_rencana;
            $realisasi = (float)$it->total_realisasi;

            if (!isset($summary[$kat])) continue;

            $summary[$kat]['rencana'] += $rencana;
            $summary[$kat]['realisasi'] += $realisasi;
        }

        if ($usePbjSource) {
            foreach (['manpower', 'material', 'mobilisasi'] as $kategori) {
                $summary[$kategori]['realisasi'] =
                    (float) $pbjByKategori->get($kategori, 0)
                    + (float) $hybridManualByKategori->get($kategori, 0);
            }
        }

        foreach (['manpower', 'material', 'mobilisasi'] as $k) {
            $sisaKategori = $summary[$k]['rencana'] - $summary[$k]['realisasi'];
            $summary[$k]['sisa'] = $usePbjSource ? $sisaKategori : max($sisaKategori, 0);
            $summary['total']['rencana'] += $summary[$k]['rencana'];
            $summary['total']['realisasi'] += $summary[$k]['realisasi'];
        }

        $summary['total']['sisa'] = max($summary['total']['rencana'] - $summary['total']['realisasi'], 0);
        if ($summary['total']['rencana'] > 0) {
            $summary['total']['progress'] = (int)round(($summary['total']['realisasi'] / $summary['total']['rencana']) * 100);
        }

        return $this->respond([
            'data' => [
                'header' => $rbk,
                'items' => $items,
                'summary' => $summary,
                'realisasi_source' => $usePbjSource ? 'hybrid' : 'manual',
                'pbj_realisasi_count' => $usePbjSource ? $pbjRows->count() : 0,
                'manual_realisasi_count' => $usePbjSource ? $hybridManualRows->count() : 0,
            ],
            'message' => 'ok',
        ]);
    }

    public function saveRbk(Request $request)
    {
        $unitfk = (int)$request->input('unitfk');
        $lokasiId = (int)$request->input('lokasi_id', 1);
        $tahun = (int)$request->input('tahun', date('Y'));

        $judul = $request->input('judul');
        $jadwal = $request->input('jadwal_kalibrasi');
        $no_surat = $request->input('no_surat');
        $no_prk = $request->input('no_prk');
        $cost_code = $request->input('cost_code');
        $hasJmlAlatSurkes = $request->has('jmlalatsurkes');
        $jmlAlatSurkes = (int)$request->input('jmlalatsurkes', 0);
        $items = $request->input('items', []);

        DB::beginTransaction();
        try {
            $existing = DB::table('rbk_rencana_t')
                ->where('statusenabled', true)
                ->where('unitfk', $unitfk)
                ->where('lokasi_id', $lokasiId)
                ->where('tahun', $tahun)
                ->first();

            $rbkNorec = null;

            if ($existing) {
                $rbkNorec = $existing->norec;

                $headerUpdate = [
                    'judul' => $judul,
                    'jadwal_kalibrasi' => $jadwal,
                    'no_surat' => $no_surat,
                    'no_prk' => $no_prk,
                    'cost_code' => $cost_code,
                    'updated_at' => now(),
                ];

                if ($hasJmlAlatSurkes) {
                    $headerUpdate['jmlalatsurkes'] = $jmlAlatSurkes;
                }

                DB::table('rbk_rencana_t')
                    ->where('norec', $rbkNorec)
                    ->update($headerUpdate);

                $existingItems = DB::table('rbk_rencana_item_t')
                    ->where('rbk_norec', $rbkNorec)
                    ->get();

                $existingEnabledNorecs = [];
                foreach ($existingItems as $ex) {
                    if ((bool)($ex->statusenabled ?? false)) {
                        $existingEnabledNorecs[] = (string)$ex->norec;
                    }
                }

                $existingByNorec = [];
                $existingByKey = [];

                foreach ($existingItems as $ex) {
                    $existingByNorec[(string)$ex->norec] = $ex;

                    $k = strtolower((string)($ex->kategori ?? ''));
                    $u = (int)($ex->urutan ?? 0);
                    $key = $k . '|' . $u;
                    if ((bool)($ex->statusenabled ?? false)) {
                        $existingByKey[$key] = $ex;
                    }
                }

                $keepNorecs = [];
                $toInsert = [];

                foreach ($items as $it) {
                    $kategori = strtolower((string)($it['kategori'] ?? ''));
                    if (!in_array($kategori, ['manpower', 'material', 'mobilisasi'])) {
                        continue;
                    }

                    $incomingNorec = isset($it['norec']) ? (string)$it['norec'] : null;
                    $urutan = (int)($it['urutan'] ?? 0);
                    $fallbackKey = $kategori . '|' . $urutan;
                    $target = null;

                    if ($incomingNorec && isset($existingByNorec[$incomingNorec])) {
                        $target = $existingByNorec[$incomingNorec];
                    } else if (isset($existingByKey[$fallbackKey])) {
                        $target = $existingByKey[$fallbackKey];
                    }

                    if ($target) {
                        DB::table('rbk_rencana_item_t')
                            ->where('norec', $target->norec)
                            ->update([
                                'kategori' => $kategori,
                                'uraian' => (string)($it['uraian'] ?? ''),
                                'qty' => (float)($it['qty'] ?? 1),
                                'satuan' => $it['satuan'] ?? null,
                                'nilai_rencana' => (float)($it['nilai_rencana'] ?? 0),
                                'urutan' => $urutan,
                                'statusenabled' => true,
                                'updated_at' => now(),
                            ]);

                        $keepNorecs[] = (string)$target->norec;

                        if (isset($existingByKey[$fallbackKey]) && (string)$existingByKey[$fallbackKey]->norec === (string)$target->norec) {
                            unset($existingByKey[$fallbackKey]);
                        }
                    } else {
                        $toInsert[] = [
                            'rbk_norec' => $rbkNorec,
                            'kategori' => $kategori,
                            'uraian' => (string)($it['uraian'] ?? ''),
                            'qty' => (float)($it['qty'] ?? 1),
                            'satuan' => $it['satuan'] ?? null,
                            'nilai_rencana' => (float)($it['nilai_rencana'] ?? 0),
                            'urutan' => $urutan,
                            'statusenabled' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($toInsert)) {
                    DB::table('rbk_rencana_item_t')->insert($toInsert);
                }

                DB::table('rbk_rencana_item_t')
                    ->where('rbk_norec', $rbkNorec)
                    ->when(!empty($existingEnabledNorecs), function ($q) use ($existingEnabledNorecs) {
                        return $q->whereIn('norec', $existingEnabledNorecs);
                    }, function ($q) {
                        return $q->whereRaw('1=0');
                    })
                    ->when(!empty($keepNorecs), function ($q) use ($keepNorecs) {
                        return $q->whereNotIn('norec', $keepNorecs);
                    }, function ($q) {
                        return $q;
                    })
                    ->update([
                        'statusenabled' => false,
                        'updated_at' => now(),
                    ]);
            } else {
                $rbkNorec = DB::table('rbk_rencana_t')->insertGetId([
                    'unitfk' => $unitfk,
                    'lokasi_id' => $lokasiId,
                    'tahun' => $tahun,
                    'judul' => $judul,
                    'jadwal_kalibrasi' => $jadwal,
                    'no_surat' => $no_surat,
                    'no_prk' => $no_prk,
                    'cost_code' => $cost_code,
                    'jmlalatsurkes' => $jmlAlatSurkes,
                    'statusenabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => $this->getPegawaiId(),
                ], 'norec');

                $bulk = [];
                foreach ($items as $it) {
                    $kategori = (string)($it['kategori'] ?? '');
                    $kategori = strtolower($kategori);
                    if (!in_array($kategori, ['manpower', 'material', 'mobilisasi'])) continue;

                    $bulk[] = [
                        'rbk_norec' => $rbkNorec,
                        'kategori' => $kategori,
                        'uraian' => (string)($it['uraian'] ?? ''),
                        'qty' => (float)($it['qty'] ?? 1),
                        'satuan' => $it['satuan'] ?? null,
                        'nilai_rencana' => (float)($it['nilai_rencana'] ?? 0),
                        'urutan' => (int)($it['urutan'] ?? 0),
                        'statusenabled' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($bulk)) {
                    DB::table('rbk_rencana_item_t')->insert($bulk);
                }
            }

            DB::commit();

            return $this->respond([
                'data' => ['rbk_norec' => $rbkNorec],
                'message' => 'ok',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function addRealisasi(Request $request)
    {
        $itemNorec = $request->input('item_norec') ?? $request->input('rbk_item_norec');

        $nilai = (float)$request->input('nilai_realisasi', 0);
        $ket = $request->input('keterangan');
        $tanggal = $request->input('tanggal');

        if ($nilai <= 0) {
            return $this->respond(['data' => null, 'message' => 'nilai_invalid']);
        }

        $item = DB::table('rbk_rencana_item_t')
            ->where('statusenabled', true)
            ->where('norec', $itemNorec)
            ->first();

        if (!$item) {
            return $this->respond(['data' => null, 'message' => 'item_not_found']);
        }

        DB::table('rbk_realisasi_t')->insert([
            'item_norec' => $itemNorec,
            'tanggal' => $tanggal ? $tanggal : now(),
            'nilai_realisasi' => $nilai,
            'keterangan' => $ket,
            'sumberrealisasi' => $request->boolean('source_hybrid') ? 'hybrid' : 'legacy',
            'statusenabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => $this->getPegawaiId()
        ]);

        return $this->respond(['data' => true, 'message' => 'ok']);
    }

    public function updateRealisasi(Request $request)
    {
        $norec = $request->input('norec');
        $itemNorec = $request->input('item_norec') ?? $request->input('rbk_item_norec');
        $nilai = (float)$request->input('nilai_realisasi', 0);
        $ket = $request->input('keterangan');
        $tanggal = $request->input('tanggal');

        if (!$norec) {
            return $this->respond(['data' => null, 'message' => 'norec_required']);
        }

        if ($nilai <= 0) {
            return $this->respond(['data' => null, 'message' => 'nilai_invalid']);
        }

        $realisasi = DB::table('rbk_realisasi_t')
            ->where('statusenabled', true)
            ->where('norec', $norec)
            ->first();

        if (!$realisasi) {
            return $this->respond(['data' => null, 'message' => 'realisasi_not_found']);
        }

        $item = DB::table('rbk_rencana_item_t')
            ->where('statusenabled', true)
            ->where('norec', $itemNorec ?: $realisasi->item_norec)
            ->first();

        if (!$item) {
            return $this->respond(['data' => null, 'message' => 'item_not_found']);
        }

        DB::table('rbk_realisasi_t')
            ->where('norec', $norec)
            ->update([
                'item_norec' => $item->norec,
                'tanggal' => $tanggal ? $tanggal : $realisasi->tanggal,
                'nilai_realisasi' => $nilai,
                'keterangan' => $ket,
                'updated_at' => now(),
            ]);

        return $this->respond(['data' => true, 'message' => 'Realisasi berhasil diupdate']);
    }

    public function getRealisasiByUnit(Request $request)
    {
        $unitfk = (int)$request->input('unitfk');
        $tahun = (int)$request->input('tahun', date('Y'));
        $lokasiId = (int)$request->input('lokasi_id', 1);

        if ($request->boolean('source_pbj')) {
            $rbk = DB::table('rbk_rencana_t')
                ->where('statusenabled', true)
                ->where('unitfk', $unitfk)
                ->where('tahun', $tahun)
                ->where('lokasi_id', $lokasiId)
                ->first();

            $pbjRows = $rbk
                ? $this->getPbjRealisasiRows(collect([$rbk]), $tahun)
                : collect();

            $manualRows = $this->getManualRealisasiRowsByUnit(
                $unitfk,
                $tahun,
                $lokasiId,
                true
            );

            $rows = $pbjRows
                ->concat($manualRows)
                ->sortByDesc('tanggal')
                ->values();

            return $this->respond([
                'data' => $rows,
                'message' => 'ok',
                'source' => 'hybrid',
            ]);
        }

        $rows = DB::table('rbk_realisasi_t as rl')
            ->join('rbk_rencana_item_t as it', 'it.norec', '=', 'rl.item_norec')
            ->join('rbk_rencana_t as hd', 'hd.norec', '=', 'it.rbk_norec')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'rl.created_by')
            ->select(
                'rl.*',
                'it.kategori',
                'it.uraian',
                'hd.unitfk',
                'hd.tahun',
                'hd.lokasi_id',
                'pg.namalengkap as created_by_nama'
            )
            ->where('rl.statusenabled', true)
            ->where('rl.sumberrealisasi', 'legacy')
            ->where('it.statusenabled', true)
            ->where('hd.statusenabled', true)
            ->where('hd.unitfk', $unitfk)
            ->where('hd.tahun', $tahun)
            ->where('hd.lokasi_id', $lokasiId)
            ->orderByDesc('rl.tanggal')
            ->orderByDesc('rl.created_at')
            ->get();

        return $this->respond(['data' => $rows, 'message' => 'ok']);
    }

    private function getManualRealisasiSummaryRows(
        array $unitIds,
        int $tahun,
        int $lokasiId,
        bool $hybridOnly = false
    ): Collection {
        if (empty($unitIds)) {
            return collect();
        }

        $query = DB::table('rbk_realisasi_t as rl')
            ->join('rbk_rencana_item_t as it', 'it.norec', '=', 'rl.item_norec')
            ->join('rbk_rencana_t as hd', 'hd.norec', '=', 'it.rbk_norec')
            ->select(
                'hd.unitfk',
                'it.kategori',
                DB::raw("COALESCE(SUM(rl.nilai_realisasi),0) as total_realisasi")
            )
            ->where('hd.statusenabled', true)
            ->where('it.statusenabled', true)
            ->where('rl.statusenabled', true)
            ->where('hd.tahun', $tahun)
            ->where('hd.lokasi_id', $lokasiId)
            ->whereIn('hd.unitfk', $unitIds);

        $query->where('rl.sumberrealisasi', $hybridOnly ? 'hybrid' : 'legacy');

        return $query
            ->groupBy('hd.unitfk', 'it.kategori')
            ->get();
    }

    private function getManualRealisasiRowsByUnit(
        int $unitfk,
        int $tahun,
        int $lokasiId,
        bool $hybridOnly = false
    ): Collection {
        $query = DB::table('rbk_realisasi_t as rl')
            ->join('rbk_rencana_item_t as it', 'it.norec', '=', 'rl.item_norec')
            ->join('rbk_rencana_t as hd', 'hd.norec', '=', 'it.rbk_norec')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'rl.created_by')
            ->select(
                'rl.*',
                'it.kategori',
                'it.uraian',
                'hd.norec as rbk_norec',
                'hd.unitfk',
                'hd.tahun',
                'hd.lokasi_id',
                'pg.namalengkap as created_by_nama'
            )
            ->where('rl.statusenabled', true)
            ->where('it.statusenabled', true)
            ->where('hd.statusenabled', true)
            ->where('hd.unitfk', $unitfk)
            ->where('hd.tahun', $tahun)
            ->where('hd.lokasi_id', $lokasiId);

        $query->where('rl.sumberrealisasi', $hybridOnly ? 'hybrid' : 'legacy');

        return $query
            ->orderByDesc('rl.tanggal')
            ->orderByDesc('rl.created_at')
            ->get()
            ->map(function ($row) {
                $row->rbk_item_norec = $row->item_norec;
                $row->source = 'manual';
                $row->read_only = false;
                return $row;
            });
    }

    private function getPbjRealisasiRows(Collection $rbkHeaders, int $tahun): Collection
    {
        if ($rbkHeaders->isEmpty()) {
            return collect();
        }

        $targetNorecs = $rbkHeaders->pluck('norec')->map(function ($norec) {
            return (string) $norec;
        })->all();

        // Selalu cocokkan terhadap seluruh RBK tahun tersebut. Jika hanya satu unit
        // yang dipakai, PRK generik yang juga digunakan unit lain bisa salah masuk.
        $matchingHeaders = DB::table('rbk_rencana_t')
            ->select('norec', 'unitfk', 'lokasi_id', 'no_prk', 'cost_code')
            ->where('statusenabled', true)
            ->where('tahun', $tahun)
            ->get();

        $unitNames = DB::table('mitra_m')
            ->select('id', 'namaperusahaan')
            ->get()
            ->keyBy(function ($unit) {
                return (string) $unit->id;
            });

        $details = DB::table('pengajuandetailpbj_t as detail')
            ->join('pengajuanpbj_t as pbj', 'pbj.norec', '=', 'detail.noregpbjfk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'pbj.pegawaifk')
            ->leftJoin('jenispbj_m as jenis', 'jenis.id', '=', 'pbj.jenispbj')
            ->select(
                'detail.norec as pbj_detail_norec',
                'detail.namaitem',
                'detail.uraianitem',
                'detail.banyak',
                'detail.satuan',
                'detail.hargasatuan',
                'detail.keterangan',
                'detail.kategorirbk as kategori',
                'detail.grupmobilisasi',
                'detail.judulmobilisasi',
                'jenis.jenispbj as jenis_pbj',
                'pbj.norec as pbj_norec',
                'pbj.nosuratpbj',
                'pbj.judulpermintaan',
                'pbj.prk',
                'pbj.costcode',
                'pbj.ppn',
                'pbj.user',
                'pbj.usermanual',
                'pbj.tglpengajuan',
                'pbj.created_at as pbj_created_at',
                'pg.namalengkap as created_by_nama'
            )
            ->where('detail.statusenabled', true)
            ->where('pbj.statusenabled', true)
            ->where('pbj.sinkronrbk', true)
            ->whereNull('pbj.tanggalpembatalan')
            ->whereRaw('EXTRACT(YEAR FROM COALESCE(pbj.tglpengajuan, pbj.created_at)) = ?', [$tahun])
            ->orderBy('pbj.norec')
            ->orderBy('detail.urutitem')
            ->orderBy('detail.norec')
            ->get();

        $rows = collect();
        foreach ($details->groupBy('pbj_norec') as $pbjDetails) {
            $pbj = $pbjDetails->first();
            $pbj->user_unit_nama = !is_null($pbj->user)
                ? $this->unitNameById($unitNames, $pbj->user)
                : trim((string) $pbj->usermanual);

            $match = $this->matchPbjToRbk($pbj, $matchingHeaders, $unitNames);
            if (!$match) {
                continue;
            }

            [$header, $matchBy] = $match;
            if (!in_array((string) $header->norec, $targetNorecs, true)) {
                continue;
            }

            $pbjDetails->each(function ($detail) {
                $kategori = strtolower(trim((string) ($detail->kategori ?? '')));
                if (!in_array($kategori, ['manpower', 'material', 'mobilisasi'], true)) {
                    $detail->kategori = $this->tentukanKategoriRbkDetail($detail);
                }
            });

            $alokasi = $this->alokasikanTotalEstimasiPbj($pbjDetails, (float) ($pbj->ppn ?? 0));
            $kategoriValid = ['manpower', 'material', 'mobilisasi'];
            $adaKategoriTidakValid = $alokasi['details']->contains(function ($item) use ($kategoriValid) {
                return !in_array(strtolower((string) $item->detail->kategori), $kategoriValid, true);
            });

            // Jangan membagi nilai item tanpa kategori ke kategori lain karena total PBJ bisa salah kamar.
            if ($adaKategoriTidakValid) {
                continue;
            }

            foreach ($alokasi['details'] as $itemAlokasi) {
                $detail = $itemAlokasi->detail;
                $jumlah = (float) ($detail->banyak ?? 0);
                $hargaSatuan = $this->parsePbjMoney($detail->hargasatuan ?? null);

                $rows->push((object) [
                    'norec' => $detail->pbj_detail_norec,
                    'pbj_detail_norec' => $detail->pbj_detail_norec,
                    'pbj_norec' => $detail->pbj_norec,
                    'rbk_norec' => $header->norec,
                    'rbk_item_norec' => null,
                    'unitfk' => $header->unitfk,
                    'tahun' => $tahun,
                    'lokasi_id' => $header->lokasi_id ?? null,
                    'kategori' => strtolower((string) $detail->kategori),
                    'uraian' => $detail->namaitem ?: $detail->judulpermintaan,
                    'uraianitem' => $detail->uraianitem,
                    'keterangan' => $detail->uraianitem ?: $detail->keterangan,
                    'pbj_head' => $detail->judulmobilisasi,
                    'grupmobilisasi' => $detail->grupmobilisasi,
                    'banyak' => $jumlah,
                    'satuan' => $detail->satuan,
                    'hargasatuan' => $hargaSatuan,
                    'nilai_subtotal_detail' => $itemAlokasi->subtotal,
                    'nilai_ppn_detail' => $itemAlokasi->nilai_ppn,
                    'nilai_realisasi' => $itemAlokasi->total_estimasi,
                    'pbj_subtotal' => $alokasi['subtotal'],
                    'pbj_ppn_persen' => $alokasi['ppn_persen'],
                    'pbj_nilai_ppn' => $alokasi['nilai_ppn'],
                    'pbj_total_estimasi' => $alokasi['total_estimasi'],
                    'tanggal' => $detail->tglpengajuan ?: $detail->pbj_created_at,
                    'created_at' => $detail->pbj_created_at,
                    'created_by_nama' => $detail->created_by_nama,
                    'nosuratpbj' => $detail->nosuratpbj,
                    'judulpermintaan' => $detail->judulpermintaan,
                    'prk' => $detail->prk,
                    'costcode' => $detail->costcode,
                    'user_unit_id' => $detail->user,
                    'user_unit_nama' => $pbj->user_unit_nama ?: null,
                    'rbk_unit_nama' => $this->unitNameById($unitNames, $header->unitfk),
                    'match_by' => $matchBy,
                    'user_unit_validated' => true,
                    'source' => 'pbj',
                    'read_only' => true,
                ]);
            }
        }

        return $rows;
    }

    private function tentukanKategoriRbkDetail($detail): string
    {
        if (strtoupper(trim((string) ($detail->jenis_pbj ?? ''))) === 'MATERIAL') {
            return 'material';
        }

        if (trim((string) ($detail->grupmobilisasi ?? '')) !== '') {
            return 'mobilisasi';
        }

        $teks = strtolower(implode(' ', [
            (string) ($detail->namaitem ?? ''),
            (string) ($detail->uraianitem ?? ''),
            (string) ($detail->judulmobilisasi ?? ''),
        ]));

        $penugasanMobilisasiAlat = preg_match('/penugasan/u', $teks)
            && preg_match('/tool|alat|peralatan/u', $teks)
            && preg_match('/kalibrasi|pengambilan|penyerahan|serah\s*terima/u', $teks);

        if (
            preg_match('/mobilisasi|mobilisai|demobilisasi|transportasi|pengantaran|pengiriman|tiket|travel|ojek|bagasi/u', $teks)
            || $penugasanMobilisasiAlat
        ) {
            return 'mobilisasi';
        }

        return 'manpower';
    }

    private function alokasikanTotalEstimasiPbj(Collection $details, float $ppnPersen): array
    {
        $detailBernilai = collect();
        foreach ($details as $detail) {
            $subtotal = (float) ($detail->banyak ?? 0)
                * $this->parsePbjMoney($detail->hargasatuan ?? null);

            if ($subtotal <= 0) {
                continue;
            }

            $detailBernilai->push((object) [
                'detail' => $detail,
                'subtotal' => $subtotal,
                'nilai_ppn' => 0,
                'total_estimasi' => $subtotal,
            ]);
        }

        $subtotalPbj = (float) $detailBernilai->sum('subtotal');
        $ppnPersen = max(0, $ppnPersen);
        $nilaiPpn = $ppnPersen > 0
            ? (float) round($subtotalPbj * $ppnPersen / 100)
            : 0;
        $totalEstimasi = $subtotalPbj + $nilaiPpn;

        if ($subtotalPbj > 0 && $ppnPersen > 0) {
            $totalTerbagi = 0;
            $lastIndex = $detailBernilai->count() - 1;

            foreach ($detailBernilai as $index => $item) {
                $nilaiDetail = $index === $lastIndex
                    ? $totalEstimasi - $totalTerbagi
                    : (float) round($totalEstimasi * ($item->subtotal / $subtotalPbj));

                $item->total_estimasi = $nilaiDetail;
                $item->nilai_ppn = $nilaiDetail - $item->subtotal;
                $totalTerbagi += $nilaiDetail;
            }
        }

        return [
            'details' => $detailBernilai,
            'subtotal' => $subtotalPbj,
            'ppn_persen' => $ppnPersen,
            'nilai_ppn' => $nilaiPpn,
            'total_estimasi' => $totalEstimasi,
        ];
    }

    private function matchPbjToRbk($pbj, Collection $headers, Collection $unitNames): ?array
    {
        $userId = trim((string) ($pbj->user ?? ''));
        $userManual = trim((string) ($pbj->usermanual ?? ''));

        if ($userId !== '') {
            $unitHeaders = $headers
                ->filter(function ($header) use ($userId) {
                    return (string) $header->unitfk === $userId;
                })
                ->values();
        } elseif ($userManual !== '') {
            $unitHeaders = $headers
                ->filter(function ($header) use ($userManual, $unitNames) {
                    $rbkUnitName = $this->unitNameById($unitNames, $header->unitfk);
                    return $this->unitNameMatches($userManual, $rbkUnitName);
                })
                ->values();
        } else {
            // Tanpa User Unit, hasil PRK/Cost Code tidak cukup aman untuk menentukan kamar RBK.
            return null;
        }

        if ($unitHeaders->isEmpty()) {
            return null;
        }

        $hasPrk = $this->hasIdentifierValue($pbj->prk ?? null, 5);
        $hasCostCode = $this->hasIdentifierValue($pbj->costcode ?? null, 10);

        $prkMatches = $hasPrk ? $unitHeaders
            ->filter(function ($header) use ($pbj) {
                return $this->identifierMatches($pbj->prk ?? null, $header->no_prk ?? null);
            })
            ->values() : collect();

        $costMatches = $hasCostCode ? $unitHeaders
            ->filter(function ($header) use ($pbj) {
                return $this->costCodeMatches($pbj->costcode ?? null, $header->cost_code ?? null);
            })
            ->values() : collect();

        if ($hasPrk && $hasCostCode) {
            $costNorecs = $costMatches->pluck('norec')->map(function ($norec) {
                return (string) $norec;
            })->all();

            $combined = $prkMatches
                ->filter(function ($header) use ($costNorecs) {
                    return in_array((string) $header->norec, $costNorecs, true);
                })
                ->values();

            if ($combined->count() === 1) {
                return [$combined->first(), 'user_unit+prk+cost_code'];
            }

            // PRK boleh berbeda hanya jika Cost Code masih menghasilkan satu kamar yang pasti.
            if ($prkMatches->isEmpty() && $costMatches->count() === 1) {
                return [$costMatches->first(), 'user_unit+cost_code'];
            }

            // Kedua penanda tersedia tetapi bertentangan/ambigu: jangan masukkan ke RBK.
            return null;
        }

        if ($hasPrk && $prkMatches->count() === 1) {
            return [$prkMatches->first(), 'user_unit+prk'];
        }

        if ($hasCostCode && $costMatches->count() === 1) {
            return [$costMatches->first(), 'user_unit+cost_code'];
        }

        return null;
    }

    private function unitNameById(Collection $unitNames, $unitId): ?string
    {
        $unit = $unitNames->get((string) $unitId);
        if (!$unit && is_numeric($unitId)) {
            $unit = $unitNames->get((int) $unitId);
        }

        return $unit->namaperusahaan ?? null;
    }

    private function unitNameMatches($left, $right): bool
    {
        $leftNormalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $left)));
        $rightNormalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $right)));

        if ($leftNormalized === '' || $rightNormalized === '') {
            return false;
        }

        if ($leftNormalized === $rightNormalized) {
            return true;
        }

        if (min(strlen($leftNormalized), strlen($rightNormalized)) < 8) {
            return false;
        }

        return strpos($leftNormalized, $rightNormalized) !== false
            || strpos($rightNormalized, $leftNormalized) !== false;
    }

    private function hasIdentifierValue($value, int $minimumLength): bool
    {
        $normalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
        return strlen($normalized) >= $minimumLength;
    }

    private function costCodeMatches($left, $right): bool
    {
        if ($this->identifierMatches($left, $right)) {
            return true;
        }

        $leftNormalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $left)));
        $rightNormalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $right)));

        if (min(strlen($leftNormalized), strlen($rightNormalized)) < 10) {
            return false;
        }

        return strpos($leftNormalized, $rightNormalized) !== false
            || strpos($rightNormalized, $leftNormalized) !== false;
    }

    private function identifierMatches($left, $right): bool
    {
        $leftNormalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $left)));
        $rightNormalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $right)));

        if ($leftNormalized === '' || $rightNormalized === '') {
            return false;
        }

        if ($leftNormalized === $rightNormalized) {
            return true;
        }

        return count(array_intersect(
            $this->identifierTokens($left),
            $this->identifierTokens($right)
        )) > 0;
    }

    private function identifierTokens($value): array
    {
        preg_match_all('/[A-Z0-9]{6,}/', strtoupper((string) $value), $matches);

        $tokens = array_values(array_unique(array_filter($matches[0] ?? [], function ($token) {
            $hasLetterAndNumber = preg_match('/[A-Z]/', $token) && preg_match('/[0-9]/', $token);
            $isLongNumber = strlen($token) >= 10 && preg_match('/^[0-9]+$/', $token);
            return $hasLetterAndNumber || $isLongNumber;
        })));

        return array_values(array_unique(array_map(function ($token) {
            return preg_replace('/^PR(?=[0-9])/', '', $token);
        }, $tokens)));
    }

    private function parsePbjMoney($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $value);
        return $digits === '' ? 0 : (float) $digits;
    }

    public function getRealisasiByItem(Request $request)
    {
        $itemNorec = $request->input('item_norec') ?? $request->input('rbk_item_norec');

        if (!$itemNorec) {
            return $this->respond(['data' => [], 'message' => 'item_norec_required']);
        }

        $rows = DB::table('rbk_realisasi_t as rl')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'rl.created_by')
            ->select(
                'rl.*',
                'pg.namalengkap as created_by_nama'
            )
            ->where('rl.statusenabled', true)
            ->where('rl.item_norec', $itemNorec)
            ->orderByDesc('rl.tanggal')
            ->orderByDesc('rl.created_at')
            ->get();

        return $this->respond(['data' => $rows, 'message' => 'ok']);
    }

    public function disableItem(Request $request)
    {
        $norec = $request->input('norec');

        DB::table('rbk_rencana_item_t')
            ->where('norec', $norec)
            ->update(['statusenabled' => false, 'updated_at' => now()]);

        return $this->respond(['data' => true, 'message' => 'ok']);
    }

    public function disableRbk(Request $request)
    {
        $norec = $request->input('norec');

        DB::table('rbk_rencana_t')
            ->where('norec', $norec)
            ->update(['statusenabled' => false, 'updated_at' => now()]);

        return $this->respond(['data' => true, 'message' => 'ok']);
    }
}
