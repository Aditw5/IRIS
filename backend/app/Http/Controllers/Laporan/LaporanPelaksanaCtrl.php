<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Transaksi\NotesSynergyUnit;
use App\Models\Transaksi\NotesSynergyUtama;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class LaporanPelaksanaCtrl extends Controller
{
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function getLaporanAlatPelaksana(Request $request)
    {
        $rangeDate = [$request->tglAwal, $request->tglAkhir];

        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftjoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtr.lokasirepair')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.statusorderpenyelia',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.tglverifasman',
                'prd.namaproduk',
                'mtr.tglregistrasi',
                'mtr.catatan',
                'mtr.jenisorder',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtrd.statusorderasman',
                'mtrd.tglverifpelaksana',
                'mtrd.noorderalat',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.tglisilembarkerjapenyelia',
                'mtrd.tglsetujupenyelialembarkerja',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglisilaporanrepairpelaksana',
                'mtrd.tglisilaporanrepairpenyelia',
                'mtrd.tglsetujupenyelialaporanrepair',
                'mtrd.tglsetujuasmanlaporanrepair',
                'mtrd.tglsetujumanagerlaporanrepair',
            )
            ->where('mtrd.pelaksanateknikfk', $request['id'])
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereBetween(DB::raw("CAST(mtr.tglregistrasi AS DATE)"), $rangeDate);

        if (isset($request['search']) && $request['search'] != '') {
            $searchTerm = '%' . $request['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('lk.lokasi', 'ilike', $searchTerm)
                    ->orWhere('mrk.namamerk', 'ilike', $searchTerm)
                    ->orWhere('lp.lingkupkalibrasi', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm)
                    ->orWhere('mmp.namatipe', 'ilike', $searchTerm)
                    ->orWhere('mmpnamaserialnumber', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaproduk', 'ilike', $searchTerm);
            });
        }

        $data = $data->orderBy('mtr.tglregistrasi');
        $data = $data->get();

        foreach ($data as $item) {
            $item->statusAlat = null;
            $item->lokasiAlat = null;
            if ($item->jenisorder == 'kalibrasi') {
                if ($item->tglsetujumanagerlembarkerja != null) {
                    $item->statusAlat = 'Lembar Kerja Disetujui Manager';
                    $item->color = 'success';
                } elseif ($item->tglsetujuasmanlembarkerja != null) {
                    $item->statusAlat = 'Lembar Kerja Disetujui Asman';
                    $item->color = 'success';
                } elseif ($item->tglsetujupenyelialembarkerja != null) {
                    $item->statusAlat = 'Lembar Kerja Disetujui Penyelia';
                    $item->color = 'success';
                } elseif ($item->tglisilembarkerjapenyelia != null) {
                    $item->statusAlat = 'Lembar Kerja Diisi Penyelia';
                    $item->color = 'primary';
                } elseif ($item->tglisilembarkerjapelaksana != null) {
                    $item->statusAlat = 'Lembar Kerja Diisi Pelaksana';
                    $item->color = 'primary';
                } else {
                    $item->statusAlat = 'Lembar Kerja Belum Diisi';
                    $item->color = 'warning';
                }
                $item->lokasiAlat = $item->lokasi;
            } else {
                if ($item->tglsetujumanagerlaporanrepair != null) {
                    $item->statusAlat = 'Laporan Repair Disetujui Manager';
                    $item->color = 'success';
                } elseif ($item->tglsetujuasmanlaporanrepair != null) {
                    $item->statusAlat = 'Laporan Repair Disetujui Asman';
                    $item->color = 'success';
                } elseif ($item->tglsetujupenyelialaporanrepair != null) {
                    $item->statusAlat = 'Laporan Repair Disetujui Penyelia';
                    $item->color = 'success';
                } elseif ($item->tglisilaporanrepairpenyelia != null) {
                    $item->statusAlat = 'Laporan Repair Diisi Penyelia';
                    $item->color = 'primary';
                } elseif ($item->tglisilaporanrepairpelaksana != null) {
                    $item->statusAlat = 'Laporan Repair Diisi Pelaksana';
                    $item->color = 'primary';
                } else {
                    $item->statusAlat = 'Laporan Repair Belum Diisi';
                    $item->color = 'warning';
                }
                $item->lokasiAlat = $item->lokasirepair;
            }
        }

        return $this->respond([
            'data' => $data,
            'message' => 'eaaditwiran19@gmail.com',
        ]);
    }

    public function getIsiSynergy(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $keyword = $request->input('keyword');
        $sortBy = $request->input('sort_by', 'total');
        $lokasiParam = $request->input('lokasi_id', 1);
        $lokasiId = is_numeric($lokasiParam)
            ? (int)$lokasiParam
            : (strtolower((string)$lokasiParam) === 'gresik' ? 2 : 1);
        $lokasiNama = $lokasiId === 2 ? 'Gresik' : 'Lokasi 1';

        $query = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select('mt.id as mitra_id', 'mt.namaperusahaan')
            ->where('mtr.statusenabled', true);

        if (!empty($keyword)) {
            $query->where(DB::raw('LOWER(mt.namaperusahaan)'), 'LIKE', '%' . strtolower($keyword) . '%');
        }

        $data = $query->groupBy('mt.id', 'mt.namaperusahaan')
            ->orderBy('mt.namaperusahaan')
            ->get();

        $mitraIds = $data->pluck('mitra_id')->filter(function ($id) {
            return $id !== null && $id !== '';
        })->unique()->values();
        $notesByMitra = $mitraIds->isNotEmpty()
            ? DB::table('notesyenergy_t')
                ->select('unitfk', DB::raw('COUNT(*) as total_notes'))
                ->whereIn('unitfk', $mitraIds)
                ->where('statusenabled', true)
                ->where('tahunnotes', $year)
                ->groupBy('unitfk')
                ->pluck('total_notes', 'unitfk')
            : collect();

        $chartLingkupByMitra = collect();
        if ($mitraIds->isNotEmpty()) {
            $chartLingkupByMitra = DB::table('mitraregistrasi_t as mtr')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
                ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
                ->select(
                    'mtr.nomitrafk as mitra_id',
                    DB::raw("COALESCE(lp.lingkupkalibrasi, 'Lainnya') as lingkup"),

                    // TOTAL per lingkup
                    DB::raw('COUNT(*) as total_jumlah'),

                    DB::raw("
                    COUNT(*) FILTER (
                        WHERE (
                            CASE
                                WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                    THEN mtrd.tglsetujumanagerlaporanrepair
                                ELSE mtrd.tglsetujumanagerlembarkerja
                            END
                        ) IS NOT NULL
                    ) as total_selesai
                "),
                    DB::raw("
                    COUNT(*) -
                    COUNT(*) FILTER (
                        WHERE (
                            CASE
                                WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                    THEN mtrd.tglsetujumanagerlaporanrepair
                                ELSE mtrd.tglsetujumanagerlembarkerja
                            END
                        ) IS NOT NULL
                    ) as total_belum
                "),

                    // SURKES per lingkup
                    DB::raw("COUNT(*) FILTER (WHERE mtrd.statussurkesfk = 1) as surkes_jumlah"),
                    DB::raw("
                    COUNT(*) FILTER (
                        WHERE mtrd.statussurkesfk = 1
                        AND (
                            CASE
                                WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                    THEN mtrd.tglsetujumanagerlaporanrepair
                                ELSE mtrd.tglsetujumanagerlembarkerja
                            END
                        ) IS NOT NULL
                    ) as surkes_selesai
                "),
                    DB::raw("
                    COUNT(*) FILTER (WHERE mtrd.statussurkesfk = 1) -
                    COUNT(*) FILTER (
                        WHERE mtrd.statussurkesfk = 1
                        AND (
                            CASE
                                WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                    THEN mtrd.tglsetujumanagerlaporanrepair
                                ELSE mtrd.tglsetujumanagerlembarkerja
                            END
                        ) IS NOT NULL
                    ) as surkes_belum
                "),

                    // NON SURKES per lingkup (TIDAK termasuk NULL)
                    DB::raw("COUNT(*) FILTER (WHERE mtrd.statussurkesfk = 2 OR mtrd.statussurkesfk IS NULL) as non_jumlah"),
                    DB::raw("
                    COUNT(*) FILTER (
                        WHERE (mtrd.statussurkesfk = 2 OR mtrd.statussurkesfk IS NULL)
                        AND (
                            CASE
                                WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                    THEN mtrd.tglsetujumanagerlaporanrepair
                                ELSE mtrd.tglsetujumanagerlembarkerja
                            END
                        ) IS NOT NULL
                    ) as non_selesai
                "),
                    DB::raw("
                    COUNT(*) FILTER (WHERE mtrd.statussurkesfk = 2 OR mtrd.statussurkesfk IS NULL) -
                                        COUNT(*) FILTER (
                                            WHERE (mtrd.statussurkesfk = 2 OR mtrd.statussurkesfk IS NULL)
                                            AND (
                                                CASE
                                                    WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                                        THEN mtrd.tglsetujumanagerlaporanrepair
                                    ELSE mtrd.tglsetujumanagerlembarkerja
                                END
                            ) IS NOT NULL
                        ) as non_belum
                    "),

                    // STATUS PENGAMBILAN BERDASARKAN TANDA SELESAI TERIMA
                    DB::raw("COUNT(*) FILTER (WHERE COALESCE(mtrd.isterima, false) = true) as total_diambil"),
                    DB::raw("COUNT(*) FILTER (WHERE COALESCE(mtrd.isterima, false) = false) as total_belum_diambil"),
                )
                ->where('mtr.statusenabled', true)
                ->where('mtrd.statusenabled', true)
                ->whereIn('mtr.nomitrafk', $mitraIds)
                ->whereRaw("TO_CHAR(mtr.tglregistrasi, 'YYYY') = ?", [$year])
                ->where(function ($q) use ($lokasiId) {
                    $q->where('mtr.lokasikalibrasi', $lokasiId)
                        ->orWhere('mtr.lokasirepair', $lokasiId);
                })
                ->groupBy('mtr.nomitrafk', 'lp.lingkupkalibrasi')
                ->orderBy('mtr.nomitrafk')
                ->orderBy('lp.lingkupkalibrasi')
                ->get()
                ->groupBy('mitra_id');
        }

        foreach ($data as $mitra) {
            $totalNotes = (int) ($notesByMitra->get($mitra->mitra_id) ?? 0);

            // =========================
            // CHART PER LINGKUP + SURKES/NON
            // =========================
            $chartLingkup = $chartLingkupByMitra->get($mitra->mitra_id, collect())->values();

            // =========================
            // TOTAL UNIT (akumulasi dari semua lingkup)
            // =========================
            $totalJumlah = 0;
            $totalSelesai = 0;
            $totalBelum = 0;

            $totalSurkesJumlah = 0;
            $totalSurkesSelesai = 0;
            $totalSurkesBelum = 0;

            $totalNonJumlah = 0;
            $totalNonSelesai = 0;
            $totalNonBelum = 0;

            $totalDiambil = 0;
            $totalBelumDiambil = 0;

            foreach ($chartLingkup as $c) {
                unset($c->mitra_id);
                $totalJumlah += (int)($c->total_jumlah ?? 0);
                $totalSelesai += (int)($c->total_selesai ?? 0);
                $totalBelum += (int)($c->total_belum ?? 0);

                $totalSurkesJumlah += (int)($c->surkes_jumlah ?? 0);
                $totalSurkesSelesai += (int)($c->surkes_selesai ?? 0);
                $totalSurkesBelum += (int)($c->surkes_belum ?? 0);

                $totalNonJumlah += (int)($c->non_jumlah ?? 0);
                $totalNonSelesai += (int)($c->non_selesai ?? 0);
                $totalNonBelum += (int)($c->non_belum ?? 0);

                $totalDiambil += (int)($c->total_diambil ?? 0);
                $totalBelumDiambil += (int)($c->total_belum_diambil ?? 0);
            }

            $mitra->chartLingkup = $chartLingkup;
            $mitra->chartTotal = [
                'jumlah' => $totalJumlah,
                'jumlahselesai' => $totalSelesai,
                'jumlahbelumselesai' => $totalBelum,
                'jumlahdiambil' => $totalDiambil,
                'jumlahbelumdiambil' => $totalBelumDiambil,

                'surkes' => [
                    'jumlah' => $totalSurkesJumlah,
                    'selesai' => $totalSurkesSelesai,
                    'belum' => $totalSurkesBelum,
                ],
                'non_surkes' => [
                    'jumlah' => $totalNonJumlah,
                    'selesai' => $totalNonSelesai,
                    'belum' => $totalNonBelum,
                ],
            ];

            $mitra->total_notes = $totalNotes;
            $mitra->year = $year;
            $mitra->lokasi_id = $lokasiId;
            $mitra->lokasi_nama = $lokasiNama;
        }

        // Baris pembentuk statistik dipakai frontend untuk drilldown kartu.
        $detailItemsByMitra = collect();
        if ($mitraIds->isNotEmpty()) {
            $detailItemsByMitra = DB::table('mitraregistrasi_t as mtr')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
                ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
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
                ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
                ->select(
                    'mtr.nomitrafk as mitra_id',
                    'mt.namaperusahaan',
                    'mtr.nopendaftaran',
                    'mtr.jenisorder',
                    'mtr.tglregistrasi',
                    'mtrd.norec as detail_norec',
                    'mtrd.noorderalat',
                    'mtrd.isterima',
                    'mtrd.statussurkesfk as jenissurkesfk',
                    DB::raw("COALESCE(lp.lingkupkalibrasi, 'Lainnya') as lingkup"),
                    DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk, 'Alat tanpa nama') as namaproduk"),
                    DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber, '-') as namaserialnumber"),
                    DB::raw("
                        CASE
                            WHEN LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'
                                THEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL
                            ELSE mtrd.tglsetujumanagerlembarkerja IS NOT NULL
                        END as selesai
                    ")
                )
                ->where('mtr.statusenabled', true)
                ->where('mtrd.statusenabled', true)
                ->whereIn('mtr.nomitrafk', $mitraIds)
                ->whereRaw("TO_CHAR(mtr.tglregistrasi, 'YYYY') = ?", [$year])
                ->where(function ($q) use ($lokasiId) {
                    $q->where('mtr.lokasikalibrasi', $lokasiId)
                        ->orWhere('mtr.lokasirepair', $lokasiId);
                })
                ->orderBy('mt.namaperusahaan')
                ->orderByDesc('mtr.tglregistrasi')
                ->orderByDesc('mtrd.noorderalat')
                ->get()
                ->groupBy('mitra_id');
        }

        foreach ($data as $mitra) {
            $mitra->details = $detailItemsByMitra
                ->get($mitra->mitra_id, collect())
                ->values();
        }

        $sortedData = collect($data)->sortByDesc(function ($row) use ($sortBy) {
            switch ($sortBy) {
                case 'selesai':
                    return (int)($row->chartTotal['jumlahselesai'] ?? 0);
                case 'belum':
                    return (int)($row->chartTotal['jumlahbelumselesai'] ?? 0);
                case 'total':
                default:
                    return (int)($row->chartTotal['jumlah'] ?? 0);
            }
        })->values()->all();

        return $this->respond([
            'data' => $sortedData,
            'message' => 'ok',
        ]);
    }

    public function saveNotesPerUnit(Request $r)
    {
        DB::beginTransaction();
        try {
            if (!$r->input('unitfk') || !$r->input('notes')) {
                throw new \Exception("Data unitfk atau notes tidak boleh kosong.");
            }
            $tahun = $r->input('year') ?? date('Y');

            if ($r->has('norec') && !empty($r->input('norec'))) {
                $model = NotesSynergyUnit::where('norec', $r->input('norec'))->first();
                if (!$model) {
                    throw new \Exception("Data catatan tidak ditemukan untuk diedit.");
                }
            } else {
                $model = new NotesSynergyUnit();
                $model->norec = $model->generateNewId();
                $model->statusenabled = true;
            }

            $model->unitfk = $r->input('unitfk');
            $model->notes = $r->input('notes');
            $model->petugasfk = $this->getPegawaiId();
            $model->tahunnotes = $tahun;
            $model->save();

            $transStatus = true;
            $transMessage = "Sukses Menyimpan Catatan";
            $dataResult = $model;
        } catch (\Exception $e) {
            $transStatus = false;
            $transMessage = "Gagal: " . $e->getMessage();
            $dataResult = null;
        }

        if ($transStatus) {
            DB::commit();
            $result = [
                "status" => 200,
                "data" => $dataResult
            ];
        } else {
            DB::rollBack();
            $result = [
                "status" => 400,
                "data" => null
            ];
        }

        return $this->respond($result['data'], $result['status'], $transMessage);
    }

    public function getNotesUnit(Request $r)
    {
        $unitId = $r->input('unitfk') ?? $r->input('mitra_id');
        $tahun = $r->input('tahun') ?? date('Y');

        if (!$unitId) {
            return $this->respond(null, 400, 'Unit ID tidak ditemukan');
        }
        $data = DB::table('notesyenergy_t as kc')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'kc.petugasfk')
            ->select(
                'kc.norec',
                'kc.notes',
                'kc.created_at',
                'kc.updated_at',
                'kc.tahunnotes',
                'pg.namalengkap as nama_petugas',
                'pg.id as petugas_id'
            )
            ->where('kc.unitfk', $unitId)
            ->where('kc.tahunnotes', $tahun)
            ->where('kc.statusenabled', true)
            ->orderBy('kc.created_at', 'desc')
            ->get();
        $data->transform(function ($item) {
            $item->formatted_date = date('d M Y H:i', strtotime($item->created_at));
            return $item;
        });

        return $this->respond($data);
    }

    public function getGlobalNotes(Request $r)
    {
        $lokasiId = $r->input('lokasi_id');
        $tahun = $r->input('tahun') ?? date('Y');

        $data = DB::table('notessynergyutama_t as nu')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'nu.petugasfk')
            ->select(
                'nu.norec',
                'nu.notes',
                'nu.created_at',
                'nu.updated_at',
                'nu.tahunnotes',
                'pg.namalengkap as nama_petugas'
            )
            ->where('nu.lokasi', $lokasiId)
            ->where('nu.tahunnotes', $tahun)
            ->where('nu.statusenabled', true)
            ->orderBy('nu.created_at', 'desc')
            ->get();

        $data->transform(function ($item) {
            $item->formatted_date = date('d M Y H:i', strtotime($item->created_at));
            return $item;
        });

        return $this->respond($data);
    }

    public function saveGlobalNote(Request $r)
    {
        DB::beginTransaction();
        try {
            if (!$r->input('lokasi_id') || !$r->input('notes')) {
                throw new \Exception("Data tidak lengkap.");
            }
            $tahun = $r->input('year') ?? date('Y');

            if ($r->has('norec') && !empty($r->input('norec'))) {
                $model = NotesSynergyUtama::where('norec', $r->input('norec'))->first();
                if (!$model) {
                    throw new \Exception("Data catatan tidak ditemukan untuk diedit.");
                }
            } else {
                $model = new NotesSynergyUtama();
                $model->norec = $model->generateNewId();
                $model->statusenabled = true;
            }
            $model->lokasi = $r->input('lokasi_id');
            $model->notes = $r->input('notes');
            $model->petugasfk = $this->getPegawaiId();
            $model->tahunnotes = $tahun;
            $model->save();

            $transStatus = true;
            $transMessage = "Sukses Menyimpan Catatan";
            $dataResult = $model;
        } catch (\Exception $e) {
            $transStatus = false;
            $transMessage = "Gagal: " . $e->getMessage();
            $dataResult = null;
        }

        if ($transStatus) {
            DB::commit();
            $result = [
                "status" => 200,
                "data" => $dataResult
            ];
        } else {
            DB::rollBack();
            $result = [
                "status" => 400,
                "data" => null
            ];
        }

        return $this->respond($result['data'], $result['status'], $transMessage);
    }

    public function hapusGlobalNote(Request $request)
    {
        DB::beginTransaction();
        try {

            NotesSynergyUtama::where('norec', $request['norec'])->update([
                'statusenabled' => false,
            ]);

            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Hapus Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function hapusUnitNote(Request $request)
    {
        DB::beginTransaction();
        try {

            NotesSynergyUnit::where('norec', $request['norec'])->update([
                'statusenabled' => false,
            ]);

            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Hapus Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function hapusDetailNote(Request $request)
    {
        DB::beginTransaction();
        try {

            DB::table('notesdetailsynergy_t')->where('norec', $request['norec'])->update([
                'statusenabled' => false,
            ]);

            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            DB::rollback();
            $transMessage = "Hapus Gagal";
            $result = array(
                "status" => 400,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getUnitItems(Request $r)
    {
        $unitId = $r->input('unit_id') ?? $r->input('mitra_id');
        $tahun = $r->input('year') ?? date('Y');

        if (!$unitId) {
            return $this->respond(null, 400, 'Unit ID (Mitra ID) tidak ditemukan');
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
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtr.asmanveriffk')
            ->leftJoin('pegawai_m as pg4', 'pg4.id', '=', 'mtrd.pelaksanaisilembarkerjafk')
            ->leftJoin('pegawai_m as pg5', 'pg5.id', '=', 'mtrd.penyeliasetujulembarkerjafk')
            ->leftJoin('pegawai_m as pg6', 'pg6.id', '=', 'mtrd.asmansetujulembarkerjafk')
            ->leftJoin('pegawai_m as pg7', 'pg7.id', '=', 'mtrd.managersetujulembarkerjafk')
            ->leftJoin('pegawai_m as pg8', 'pg8.id', '=', 'mtrd.pelaksanaisilaporanrepairfk')
            ->leftJoin('pegawai_m as pg9', 'pg9.id', '=', 'mtrd.penyeliaisilaporanrepairfk')
            ->leftJoin('pegawai_m as pg10', 'pg10.id', '=', 'mtrd.penyeliasetujulaporanrepairfk')
            ->leftJoin('pegawai_m as pg11', 'pg11.id', '=', 'mtrd.asmansetujulaporanrepairfk')
            ->leftJoin('pegawai_m as pg12', 'pg12.id', '=', 'mtrd.managersetujulaporanrepairfk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('jenissurkes_m as jm', 'jm.id', '=', 'mtrd.statussurkesfk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtr.jenisorder',
                'mtrd.iskaji',
                'mtrd.noorderalat',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtrd.namaalatfk as id_alat',
                DB::raw("COALESCE(mmps.fotoproduk, mmp.fotoproduk) as fotoproduk"),
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'mtr.tglverifasman',
                'mtrd.tglverifpenyelia',
                'mtrd.tglverifpelaksana',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.tglisilembarkerjapenyelia',
                'mtrd.tglsetujupenyelialembarkerja',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglisilaporanrepairpelaksana',
                'mtrd.tglisilaporanrepairpenyelia',
                'mtrd.tglsetujupenyelialaporanrepair',
                'mtrd.tglsetujuasmanlaporanrepair',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.isterima',
                'pg3.namalengkap as asamanverifikasi',
                'pg.namalengkap as penyeliateknik',
                'pg2.namalengkap as pelaksanateknik',
                'pg5.namalengkap as penyeliasetujuilembarkerja',
                'pg6.namalengkap as asmansetujuilembarkerja',
                'pg7.namalengkap as managersetujuilembarkerja',
                'pg9.namalengkap as penyeliaisilaporanrepair',
                'pg8.namalengkap as pelaksanaisilaporanrepair',
                'pg10.namalengkap as penyeliasetujulaporanrepair',
                'pg11.namalengkap as asmansetujulaporanrepair',
                'pg12.namalengkap as managersetujulaporanrepair',
                'lp.lingkupkalibrasi',
                'jm.jenissurkes',
                'jm.id as jenissurkesfk',
                DB::raw("(SELECT COUNT(nts.norec) FROM notesdetailsynergy_t as nts WHERE nts.norecalatfk = mtrd.norec AND nts.statusenabled = true) as total_notes")
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.nomitrafk', $unitId)
            ->whereYear('mtr.tglregistrasi', $tahun)
            ->orderByDesc('mtrd.noorderalat')
            ->get();

        $resultItems = [];

        foreach ($data as $item) {
            $timeline = [];

            // --- LOGIC REPAIR ---
            if (!is_null($item->tglsetujumanagerlaporanrepair)) {
                $timeline[] = ['date' => $item->tglsetujumanagerlaporanrepair, 'type' => 'Laporan Repair Disetujui Manager', 'nama' => $item->managersetujulaporanrepair];
            }
            if (!is_null($item->tglsetujuasmanlaporanrepair)) {
                $timeline[] = ['date' => $item->tglsetujuasmanlaporanrepair, 'type' => 'Laporan Repair Disetujui Asman', 'nama' => $item->asmansetujulaporanrepair];
            }
            if (!is_null($item->tglsetujupenyelialaporanrepair)) {
                $timeline[] = ['date' => $item->tglsetujupenyelialaporanrepair, 'type' => 'Laporan Repair Disetujui Penyelia', 'nama' => $item->penyeliasetujulaporanrepair];
            }
            if (!is_null($item->tglisilaporanrepairpenyelia)) {
                $timeline[] = ['date' => $item->tglisilaporanrepairpenyelia, 'type' => 'Laporan Repair Diisi Penyelia', 'nama' => $item->penyeliaisilaporanrepair];
            }
            if (!is_null($item->tglisilaporanrepairpelaksana)) {
                $timeline[] = ['date' => $item->tglisilaporanrepairpelaksana, 'type' => 'Laporan Repair Diisi Pelaksana', 'nama' => $item->pelaksanaisilaporanrepair];
            }

            // --- LOGIC KALIBRASI ---
            if (!is_null($item->tglsetujumanagerlembarkerja)) {
                $timeline[] = ['date' => $item->tglsetujumanagerlembarkerja, 'type' => 'Sertifikat Disetujui Manager', 'nama' => $item->managersetujuilembarkerja];
            }
            if (!is_null($item->tglsetujuasmanlembarkerja)) {
                $timeline[] = ['date' => $item->tglsetujuasmanlembarkerja, 'type' => 'Sertifikat Disetujui Asman', 'nama' => $item->asmansetujuilembarkerja];
            }
            if (!is_null($item->tglsetujupenyelialembarkerja)) {
                $timeline[] = ['date' => $item->tglsetujupenyelialembarkerja, 'type' => 'Sertifikat Disetujui Penyelia', 'nama' => $item->penyeliasetujuilembarkerja];
            }
            if (!is_null($item->tglverifasman)) {
                $timeline[] = ['date' => $item->tglverifasman, 'type' => 'Diverifikasi Asman', 'nama' => $item->asamanverifikasi];
            }
            if (!is_null($item->tglverifpenyelia)) {
                $timeline[] = ['date' => $item->tglverifpenyelia, 'type' => 'Diverifikasi Penyelia', 'nama' => $item->penyeliateknik];
            }
            if (!is_null($item->tglverifpelaksana)) {
                $timeline[] = ['date' => $item->tglverifpelaksana, 'type' => 'Diverifikasi Pelaksana', 'nama' => $item->pelaksanateknik];
            }
            if (!is_null($item->tglisilembarkerjapelaksana)) {
                $timeline[] = ['date' => $item->tglisilembarkerjapelaksana, 'type' => 'Lembar Kerja Diisi Pelaksana', 'nama' => $item->pelaksanateknik];
            }
            if (!is_null($item->tglisilembarkerjapenyelia)) {
                $timeline[] = ['date' => $item->tglisilembarkerjapenyelia, 'type' => 'Lembar Kerja Diisi Penyelia', 'nama' => $item->penyeliateknik];
            }

            // Sort timeline desc
            usort($timeline, function ($a, $b) {
                return strtotime($b['date']) <=> strtotime($a['date']);
            });

            $totalSteps = 8;
            $completedSteps = count($timeline);
            $progress = ($completedSteps > 0) ? round(($completedSteps / $totalSteps) * 100) : 0;

            if (!empty($timeline)) {
                $latestType = $timeline[0]['type'];
                if (strpos($latestType, 'Manager') !== false) {
                    $progress = 100;
                }
            }
            if ($progress > 100) $progress = 100;

            $isRepair = false;
            if (isset($item->jenisorder) && stripos($item->jenisorder, 'repair') !== false) {
                $isRepair = true;
            } else {
                foreach ($timeline as $t) {
                    if (strpos($t['type'], 'Repair') !== false) {
                        $isRepair = true;
                        break;
                    }
                }
            }

            $resultItems[] = [
                'id_alat' => $item->id_alat,
                'norec' => $item->norec_detail,
                'namaproduk' => $item->namaproduk,
                'fotoproduk' => $item->fotoproduk,
                'namamerk' => $item->namamerk,
                'namatipe' => $item->namatipe,
                'namaperusahaan' => $item->namaperusahaan,
                'namaserialnumber' => $item->namaserialnumber,
                'penyeliateknik' => $item->penyeliateknik,
                'pelaksanateknik' => $item->pelaksanateknik,
                'nopendaftaran' => $item->nopendaftaran,
                'noorderalat' => $item->noorderalat,
                'jenissurkes' => $item->jenissurkes,
                'jenissurkesfk' => $item->jenissurkesfk,
                'lingkup' => $item->lingkupkalibrasi ?? 'Lainnya',
                'tglMasuk' => date('d M Y', strtotime($item->tglregistrasi)),
                'isRepair' => $isRepair,
                'progress' => $progress,
                'latestStatus' => !empty($timeline) ? $timeline[0]['type'] : 'Menunggu Proses',
                'isterima' => (bool) $item->isterima,
                'showTimeline' => false,
                'timeline' => $timeline,
                'total_notes' => $item->total_notes
            ];
        }

        return $this->respond($resultItems);
    }

    public function saveNotesDetail(Request $r)
    {
        DB::beginTransaction();
        try {
            if (!$r->input('norecalatfk') || !$r->input('notes')) {
                throw new \Exception("Data norecalatfk atau notes tidak boleh kosong.");
            }

            if ($r->has('norec') && !empty($r->input('norec'))) {
                $norec = $r->input('norec');
                $exists = DB::table('notesdetailsynergy_t')->where('norec', $norec)->exists();
                if (!$exists) {
                    throw new \Exception("Catatan tidak ditemukan untuk diedit.");
                }
                DB::table('notesdetailsynergy_t')
                    ->where('norec', $norec)
                    ->update([
                        'notes' => $r->input('notes'),
                        'petugasfk' => $this->getPegawaiId(),
                    ]);

                $transMessage = "Sukses Update Catatan Detail";
            } else {
                $norec = \Illuminate\Support\Str::uuid()->toString();
                DB::table('notesdetailsynergy_t')->insert([
                    'norec' => $norec,
                    'norecalatfk' => $r->input('norecalatfk'),
                    'notes' => $r->input('notes'),
                    'petugasfk' => $this->getPegawaiId(),
                    'statusenabled' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $transMessage = "Sukses Menyimpan Catatan Detail";
            }

            $transStatus = true;
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond(null, 400, "Gagal: " . $e->getMessage());
        }

        if ($transStatus) {
            DB::commit();
            return $this->respond(['norec' => $norec], 200, $transMessage);
        }
    }

    public function getNotesDetail(Request $r)
    {
        $norecalatfk = $r->input('norecalatfk');

        if (!$norecalatfk) {
            return $this->respond(null, 400, 'ID Alat tidak ditemukan');
        }

        $data = DB::table('notesdetailsynergy_t as nd')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'nd.petugasfk')
            ->select(
                'nd.norec',
                'nd.notes',
                'nd.created_at',
                'nd.updated_at',
                'pg.namalengkap as nama_petugas'
            )
            ->where('nd.norecalatfk', $norecalatfk)
            ->where('nd.statusenabled', true)
            ->orderBy('nd.created_at', 'desc')
            ->get();

        $data->transform(function ($item) {
            $item->formatted_date = date('d M Y H:i', strtotime($item->created_at));
            return $item;
        });

        return $this->respond($data);
    }
}
