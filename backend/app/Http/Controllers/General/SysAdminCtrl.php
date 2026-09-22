<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SysAdminCtrl extends Controller
{

    public function listMenu(Request $r)
    {
        $dataraw3 = [];
        $dataRaw = DB::table('objekmodulaplikasi_s as oma')
            ->join('mapobjekmodulaplikasitomodulaplikasi_s as acdc', 'acdc.objekmodulaplikasiid', '=', 'oma.id')
            ->join(
                'maploginusertomodulaplikasi_s as maps',
                function ($join) {
                    $join->on('maps.objectmodulaplikasifk', '=', 'acdc.modulaplikasiid');
                    //					$join->on('maps.objectmodulaplikasifk', '=', 'acdc.modulaplikasiid');
                }
            )
            ->join('modulaplikasi_s as ma', 'ma.id', '=', 'acdc.modulaplikasiid')
            ->where('oma.statusenabled', true)
            ->where('ma.reportdisplay', 'Menu')
            ->where('maps.statusenabled', true)
            ->where('maps.objectloginuserfk', $r['idUser'])
            ->select(
                'oma.id',
                'oma.kdobjekmodulaplikasihead',
                'oma.objekmodulaplikasi',
                'oma.icon',
                'oma.alamaturlform',
                'ma.modulaplikasi',
                'acdc.modulaplikasiid',
                'oma.kodeexternal',
                'oma.ishide'
            )
            ->groupBy(
                'oma.id',
                'oma.kdobjekmodulaplikasihead',
                'oma.objekmodulaplikasi',
                'oma.alamaturlform',
                'ma.modulaplikasi',
                'acdc.modulaplikasiid',
                'oma.kodeexternal',
                'oma.nourut',
                'oma.icon',
                'oma.ishide'
            )
            ->orderBy('oma.nourut');
        $dataRaw = $dataRaw->get();
        foreach ($dataRaw as $dataRaw2) {
            //                if ((integer)$dataRaw2->id < 100) {
            if ($dataRaw2->kdobjekmodulaplikasihead == null) {
                if ($dataRaw2->alamaturlform != null || $dataRaw2->alamaturlform != '') {
                    $dataraw3[] = array(
                        'id' => $dataRaw2->id,
                        'parent_id' => 0,
                        'name' => $dataRaw2->objekmodulaplikasi,
                        'link' => $dataRaw2->alamaturlform,
                        'icon' => $dataRaw2->icon != null ? $dataRaw2->icon : 'chevron-right',
                        'ishide' => $dataRaw2->ishide
                    );
                } else {
                    $dataraw3[] = array(
                        'id' => $dataRaw2->id,
                        'parent_id' => 0,
                        'name' => $dataRaw2->objekmodulaplikasi,
                        'icon' => $dataRaw2->icon != null ? $dataRaw2->icon : 'chevron-right',
                        'ishide' => $dataRaw2->ishide
                    );
                }
            } else {
                if ($dataRaw2->kdobjekmodulaplikasihead != null) {
                    if ($dataRaw2->alamaturlform != null || $dataRaw2->alamaturlform != '') {
                        $dataraw3[] = array(
                            'id' => $dataRaw2->id,
                            'parent_id' => $dataRaw2->kdobjekmodulaplikasihead,
                            'name' => $dataRaw2->objekmodulaplikasi,
                            'link' => $dataRaw2->alamaturlform,
                            'icon' => $dataRaw2->icon != null ? $dataRaw2->icon : 'chevron-right',
                            'ishide' => $dataRaw2->ishide
                        );
                    } else {
                        $dataraw3[] = array(
                            'id' => $dataRaw2->id,
                            'parent_id' => $dataRaw2->kdobjekmodulaplikasihead,
                            'name' => $dataRaw2->objekmodulaplikasi,
                            'icon' => $dataRaw2->icon != null ? $dataRaw2->icon : 'chevron-right',
                            'ishide' => $dataRaw2->ishide
                            // 'link' => $dataRaw2->alamaturlform
                        );
                    }
                } else {
                    if ($dataRaw2->modulaplikasiid == $r['id']) {
                        if ($dataRaw2->alamaturlform != null || $dataRaw2->alamaturlform != '') {
                            $dataraw3[] = array(
                                'id' => $dataRaw2->id,
                                'parent_id' => $dataRaw2->kdobjekmodulaplikasihead,
                                'name' => $dataRaw2->objekmodulaplikasi,
                                'link' => $dataRaw2->alamaturlform,
                                'icon' => $dataRaw2->icon != null ? $dataRaw2->icon : 'chevron-right',
                                'ishide' => $dataRaw2->ishide
                            );
                        } else {
                            $dataraw3[] = array(
                                'id' => $dataRaw2->id,
                                'parent_id' => $dataRaw2->kdobjekmodulaplikasihead,
                                'name' => $dataRaw2->objekmodulaplikasi,
                                'icon' => $dataRaw2->icon != null ? $dataRaw2->icon : 'chevron-right',
                                'ishide' => $dataRaw2->ishide
                            );
                        }
                    }
                }
            }
        }
        $data = $dataraw3;
        function recursiveElements($data)
        {
            $elements = [];
            $tree = [];
            foreach ($data as &$element) {
                $id = $element['id'];
                $parent_id = $element['parent_id'];

                $elements[$id] = &$element;
                if (isset($elements[$parent_id])) {
                    $elements[$parent_id]['children'][] = &$element;
                } else {
                    if ($parent_id <= 10) {
                        $tree[] = &$element;
                    }
                }
            }
            return $tree;
        }

        $data = recursiveElements($data);


        return $this->respond($data);
    }

    public function getTimeServer(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        $date = date('Y-m-d');
        $time = date('H:i');

        $result = array(
            'date' => $date,
            'time' => $time,
            'second' => date('s')
        );

        return $this->respond($result);
    }

    public function getDropdownMappingSurkes(Request $request)
    {
        $lokasiParam = $request->input('lokasi_id');
        $lokasiId = null;
        if ($lokasiParam !== null && $lokasiParam !== '') {
            $lokasiId = is_numeric($lokasiParam)
                ? (int)$lokasiParam
                : (strtolower((string)$lokasiParam) === 'gresik' ? 2 : 1);
        }

        $mitra = DB::table('mitra_m')
            ->where('statusenabled', true)
            ->where('issurkes', true)
            ->when($lokasiId !== null, function ($q) use ($lokasiId) {
                return $q->where('lokasisurkes', $lokasiId);
            })
            ->select('id as value', 'namaperusahaan as label')
            ->orderBy('namaperusahaan', 'ASC')
            ->get();

        $lingkup = DB::table('lingkupkalibrasi_m')
            ->where('statusenabled', true)
            ->select('id as value', 'lingkupkalibrasi as label')
            ->orderBy('lingkupkalibrasi', 'ASC')
            ->get();

        $result = array(
            'mitra' => $mitra,
            'lingkup' => $lingkup
        );

        return $this->respond($result);
    }

    public function getAlatByUnit(Request $request)
    {
        $mitraId = $request->query('mitra_id');

        $data = DB::table('mapunittoalat_m as mua')
            ->leftJoin('mapalattolingkup_t as mal', function ($join) {
                $join->on('mua.id', '=', 'mal.objectalatfk')
                    ->where('mal.statusenabled', true);
            })
            ->where('mua.objectmitrafk', $mitraId)
            ->where('mua.statusenabled', true)
            ->select(
                'mua.id',
                'mua.namaproduk',
                'mua.namaserialnumber',
                'mua.namamerk',
                'mua.namatipe',
                'mal.id as mapping_id',
                'mal.objectlingkupfk'
            )
            ->get();

        return $this->respond($data);
    }

    public function saveMappingAlatLingkup(Request $request)
    {
        $lingkupId = $request->input('objectlingkupfk');
        $mitraId = $request->input('objectmitrafk');
        $details = $request->input('detail');

        DB::beginTransaction();
        try {
            $alatIds = collect($details)->pluck('objectalatfk')->filter(function ($id) {
                return $id !== null && $id !== '';
            })->unique()->values();
            $existingAlatIds = $alatIds->isNotEmpty()
                ? DB::table('mapalattolingkup_t')
                    ->whereIn('objectalatfk', $alatIds)
                    ->where('statusenabled', true)
                    ->pluck('objectalatfk')
                    ->map(function ($id) {
                        return (string) $id;
                    })
                    ->flip()
                : collect();
            $rows = [];

            foreach ($details as $item) {
                $alatKey = (string) $item['objectalatfk'];
                if (!$existingAlatIds->has($alatKey)) {
                    $rows[] = [
                        'objectlingkupfk' => $lingkupId,
                        'objectalatfk'    => $item['objectalatfk'],
                        'objectmitrafk'   => $mitraId,
                        'statusenabled'   => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                    $existingAlatIds->put($alatKey, true);
                }
            }

            if (!empty($rows)) {
                DB::table('mapalattolingkup_t')->insert($rows);
            }

            DB::commit();

            $result = array(
                'message' => 'Mapping berhasil disimpan'
            );

            return $this->respond($result);
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                'message' => 'Gagal simpan',
                'error'   => $e->getMessage()
            );
            return $this->respond($result);
        }
    }

    public function getListMappingAlat(Request $request)
    {
        $mitraId = $request->query('mitra_id');
        $lingkupId = $request->query('lingkup_id');
        $lokasiParam = $request->query('lokasi_id');
        $lokasiId = null;
        if ($lokasiParam !== null && $lokasiParam !== '') {
            $lokasiId = is_numeric($lokasiParam)
                ? (int)$lokasiParam
                : (strtolower((string)$lokasiParam) === 'gresik' ? 2 : 1);
        }

        $query = DB::table('mapalattolingkup_t as mal')
            ->join('mapunittoalat_m as mua', 'mua.id', '=', 'mal.objectalatfk')
            ->join('mitra_m as m', 'm.id', '=', 'mal.objectmitrafk')
            ->join('lingkupkalibrasi_m as l', 'l.id', '=', 'mal.objectlingkupfk')
            ->where('mal.statusenabled', true)
            ->where('mua.statusenabled', true)
            ->select(
                'mal.id',
                'm.namaperusahaan',
                'l.lingkupkalibrasi',
                'mua.namaproduk',
                'mua.namamerk',
                'mua.namatipe',
                'mua.namaserialnumber',
                'mal.created_at'
            );

        if ($mitraId) $query->where('mal.objectmitrafk', $mitraId);
        if ($lingkupId) $query->where('mal.objectlingkupfk', $lingkupId);
        if ($lokasiId !== null) $query->where('m.lokasisurkes', $lokasiId);

        $data = $query->orderBy('mal.created_at', 'DESC')->get();

        return $this->respond($data);
    }

    public function deleteMappingAlatSurkes(Request $request)
    {
        $id = $request->input('id');

        if (empty($id)) {
            return $this->respond([
                'message' => 'ID mapping tidak valid',
            ]);
        }

        DB::table('mapalattolingkup_t')
            ->where('id', $id)
            ->update([
                'statusenabled' => false,
                'updated_at' => now(),
            ]);

        return $this->respond([
            'message' => 'Mapping berhasil dihapus',
        ]);
    }

    public function getMonitoringAlatSurkes(Request $request)
    {
        $year = (int)($request->input('year', date('Y')));
        $year = $year > 0 ? $year : (int)date('Y');

        $lokasiParam = $request->input('lokasi_id', 1);
        $lokasiId = is_numeric($lokasiParam)
            ? (int)$lokasiParam
            : (strtolower((string)$lokasiParam) === 'gresik' ? 2 : 1);

        $mitraId = $request->input('mitra_id');
        $lingkupId = $request->input('lingkup_id');
        $keyword = trim((string)$request->input('keyword', ''));
        $statusFilter = trim((string)$request->input('status', ''));

        $mappingQuery = DB::table('mapalattolingkup_t as mal')
            ->join('mapunittoalat_m as mua', 'mua.id', '=', 'mal.objectalatfk')
            ->join('mitra_m as m', 'm.id', '=', 'mal.objectmitrafk')
            ->leftJoin('lingkupkalibrasi_m as lk', 'lk.id', '=', 'mal.objectlingkupfk')
            ->where('mal.statusenabled', true)
            ->where('mua.statusenabled', true)
            ->where('m.statusenabled', true)
            ->where('m.issurkes', true)
            ->where('m.lokasisurkes', $lokasiId)
            ->select(
                'mal.id as mapping_id',
                'mal.objectalatfk',
                'mal.objectmitrafk as unit_id',
                'mal.objectlingkupfk',
                'mal.created_at as mapping_created_at',
                'm.namaperusahaan',
                'mua.namaproduk',
                'mua.namamerk',
                'mua.namatipe',
                'mua.namaserialnumber',
                'lk.lingkupkalibrasi'
            );

        if (!empty($mitraId)) {
            $mappingQuery->where('mal.objectmitrafk', $mitraId);
        }

        if (!empty($lingkupId)) {
            $mappingQuery->where('mal.objectlingkupfk', $lingkupId);
        }

        $mappingRows = $mappingQuery
            ->orderBy('m.namaperusahaan')
            ->orderBy('mua.namaproduk')
            ->get();

        $mappingByKey = [];
        foreach ($mappingRows as $row) {
            $key = $this->buildSurkesAlatKey(
                $row->objectalatfk,
                $row->unit_id,
                $row->namaproduk,
                $row->namamerk,
                $row->namatipe,
                $row->namaserialnumber,
                $row->mapping_id
            );

            if (!isset($mappingByKey[$key])) {
                $mappingByKey[$key] = [
                    'row' => $row,
                    'mapping_count' => 0,
                ];
            }

            $mappingByKey[$key]['mapping_count']++;
        }

        $registrationQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as m', 'm.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('mapunittoalat_m as mua', function ($join) {
                $join->on('mua.id', '=', 'mtrd.namaalatfk')
                    ->where('mua.statusenabled', true)
                    ->whereNull('mtr.isstandarulab');
            })
            ->leftJoin('lingkupkalibrasi_m as lk', 'lk.id', '=', 'mtrd.lingkupkalibrasifk')
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('m.statusenabled', true)
            ->where('m.issurkes', true)
            ->where('m.lokasisurkes', $lokasiId)
            ->whereRaw("TO_CHAR(mtr.tglregistrasi, 'YYYY') = ?", [$year])
            ->select(
                'mtr.norec as norec_registrasi',
                'mtr.nopendaftaran',
                'mtr.tglregistrasi',
                'mtr.jenisorder',
                'mtr.nomitrafk as unit_id',
                'm.namaperusahaan',
                'mtrd.norec as norec_detail',
                'mtrd.namaalatfk as objectalatfk',
                'mtrd.noorderalat',
                'mtrd.lingkupkalibrasifk',
                'mtrd.statussurkesfk',
                DB::raw("COALESCE(pg.namalengkap, '(Belum ditetapkan)') as pelaksana"),
                DB::raw("
                    CASE
                        WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair' THEN mtr.lokasirepair
                        ELSE mtr.lokasikalibrasi
                    END as lokasi_id
                "),
                'mtr.tglkajiulang',
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
                'mua.namaproduk',
                'mua.namamerk',
                'mua.namatipe',
                'mua.namaserialnumber',
                'lk.lingkupkalibrasi',
                DB::raw("
                    CASE
                        WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                            THEN mtrd.tglsetujumanagerlaporanrepair
                        ELSE mtrd.tglsetujumanagerlembarkerja
                    END as tgl_selesai
                ")
            );

        if (!empty($mitraId)) {
            $registrationQuery->where('mtr.nomitrafk', $mitraId);
        }

        $registrationRows = $registrationQuery
            ->orderByDesc('mtr.tglregistrasi')
            ->get();

        $registrationByKey = [];
        $nonSurkesByKey = [];
        foreach ($registrationRows as $row) {
            $key = $this->buildSurkesAlatKey(
                $row->objectalatfk,
                $row->unit_id,
                $row->namaproduk,
                $row->namamerk,
                $row->namatipe,
                $row->namaserialnumber,
                $row->norec_detail
            );

            if ((int)($row->statussurkesfk ?? 0) === 1) {
                $this->pushSurkesRegistrationGroup($registrationByKey, $key, $row);
            } else {
                $this->pushSurkesRegistrationGroup($nonSurkesByKey, $key, $row);
            }
        }

        $rows = [];

        foreach ($mappingByKey as $key => $mapped) {
            $mapping = $mapped['row'];
            $actual = $registrationByKey[$key] ?? null;
            $latest = $actual['latest'] ?? null;
            $workProgress = $this->resolveSurkesWorkProgress($latest);

            $rows[] = [
                'key' => $key,
                'status' => $actual ? 'realisasi_surkes' : 'sisa_surkes_belum_daftar',
                'status_label' => $actual ? 'Realisasi Surkes' : 'Surkes Belum Daftar',
                'unit_id' => (int)$mapping->unit_id,
                'namaperusahaan' => $mapping->namaperusahaan,
                'mapping_id' => $mapping->mapping_id,
                'objectalatfk' => $mapping->objectalatfk,
                'namaproduk' => $mapping->namaproduk,
                'namamerk' => $mapping->namamerk,
                'namatipe' => $mapping->namatipe,
                'namaserialnumber' => $mapping->namaserialnumber,
                'objectlingkupfk' => $mapping->objectlingkupfk,
                'lingkup_mapping' => $mapping->lingkupkalibrasi,
                'lingkup_registrasi' => $actual['lingkupkalibrasi'] ?? null,
                'lingkup_mismatch' => $actual
                    && !empty($mapping->objectlingkupfk)
                    && !empty($actual['lingkupfk'])
                    && (int)$mapping->objectlingkupfk !== (int)$actual['lingkupfk'],
                'registration_count' => (int)($actual['registration_count'] ?? 0),
                'duplicate_count' => max(((int)($actual['registration_count'] ?? 0)) - 1, 0),
                'mapping_duplicate_count' => max(((int)$mapped['mapping_count']) - 1, 0),
                'selesai_count' => (int)($actual['selesai_count'] ?? 0),
                'jenisorder' => $actual ? array_values($actual['jenisorder']) : [],
                'nopendaftaran' => $actual ? array_slice(array_values($actual['nopendaftaran']), 0, 5) : [],
                'noorderalat' => $actual ? array_slice(array_values($actual['noorderalat']), 0, 5) : [],
                'last_tglregistrasi' => $latest->tglregistrasi ?? null,
                'last_nopendaftaran' => $latest->nopendaftaran ?? null,
                'last_noorderalat' => $latest->noorderalat ?? null,
                'last_jenisorder' => $latest->jenisorder ?? null,
                'last_tgl_selesai' => $latest->tgl_selesai ?? null,
                'status_pekerjaan' => $actual
                    ? (!empty($latest->tgl_selesai) ? 'Selesai' : 'Belum Selesai')
                    : 'Belum Masuk',
                'pelaksana' => $latest->pelaksana ?? '-',
                'lokasi' => $this->formatUlabLocation($latest->lokasi_id ?? null),
                'status_pengambilan' => $latest
                    ? (filter_var($latest->isterima ?? false, FILTER_VALIDATE_BOOLEAN) ? 'Sudah Diambil' : 'Belum Diambil')
                    : '-',
                'status_terakhir' => $workProgress['status_terakhir'],
                'progress_persen' => $workProgress['progress_persen'],
            ];
        }

        foreach ($nonSurkesByKey as $key => $actual) {
            if (isset($mappingByKey[$key])) {
                continue;
            }

            if (!empty($lingkupId) && (int)($actual['lingkupfk'] ?? 0) !== (int)$lingkupId) {
                continue;
            }

            $latest = $actual['latest'];
            $workProgress = $this->resolveSurkesWorkProgress($latest);

            $rows[] = [
                'key' => $key,
                'status' => 'alat_non_surkes',
                'status_label' => 'Alat Non Surkes',
                'unit_id' => (int)$actual['unit_id'],
                'namaperusahaan' => $actual['namaperusahaan'],
                'mapping_id' => null,
                'objectalatfk' => $actual['objectalatfk'],
                'namaproduk' => $actual['namaproduk'],
                'namamerk' => $actual['namamerk'],
                'namatipe' => $actual['namatipe'],
                'namaserialnumber' => $actual['namaserialnumber'],
                'objectlingkupfk' => null,
                'lingkup_mapping' => null,
                'lingkup_registrasi' => $actual['lingkupkalibrasi'],
                'lingkup_mismatch' => false,
                'registration_count' => (int)$actual['registration_count'],
                'duplicate_count' => max(((int)$actual['registration_count']) - 1, 0),
                'mapping_duplicate_count' => 0,
                'selesai_count' => (int)$actual['selesai_count'],
                'jenisorder' => array_values($actual['jenisorder']),
                'nopendaftaran' => array_slice(array_values($actual['nopendaftaran']), 0, 5),
                'noorderalat' => array_slice(array_values($actual['noorderalat']), 0, 5),
                'last_tglregistrasi' => $latest->tglregistrasi ?? null,
                'last_nopendaftaran' => $latest->nopendaftaran ?? null,
                'last_noorderalat' => $latest->noorderalat ?? null,
                'last_jenisorder' => $latest->jenisorder ?? null,
                'last_tgl_selesai' => $latest->tgl_selesai ?? null,
                'status_pekerjaan' => !empty($latest->tgl_selesai) ? 'Selesai' : 'Belum Selesai',
                'pelaksana' => $latest->pelaksana ?? '-',
                'lokasi' => $this->formatUlabLocation($latest->lokasi_id ?? null),
                'status_pengambilan' => filter_var($latest->isterima ?? false, FILTER_VALIDATE_BOOLEAN)
                    ? 'Sudah Diambil'
                    : 'Belum Diambil',
                'status_terakhir' => $workProgress['status_terakhir'],
                'progress_persen' => $workProgress['progress_persen'],
            ];
        }

        if ($keyword !== '') {
            $rows = array_values(array_filter($rows, function ($row) use ($keyword) {
                $haystack = strtolower(implode(' ', [
                    $row['namaperusahaan'] ?? '',
                    $row['namaproduk'] ?? '',
                    $row['namamerk'] ?? '',
                    $row['namatipe'] ?? '',
                    $row['namaserialnumber'] ?? '',
                    $row['lingkup_mapping'] ?? '',
                    $row['lingkup_registrasi'] ?? '',
                    $row['last_nopendaftaran'] ?? '',
                    $row['last_noorderalat'] ?? '',
                    implode(' ', $row['nopendaftaran'] ?? []),
                    implode(' ', $row['noorderalat'] ?? []),
                ]));

                return strpos($haystack, strtolower($keyword)) !== false;
            }));
        }

        $summary = $this->summarizeSurkesMonitoringRows($rows);
        $byUnit = $this->groupSurkesMonitoringRows($rows, 'unit');
        $byLingkup = $this->groupSurkesMonitoringRows($rows, 'lingkup');

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $rows = array_values(array_filter($rows, function ($row) use ($statusFilter) {
                return ($row['status'] ?? '') === $statusFilter;
            }));
        }

        usort($rows, function ($a, $b) {
            $order = [
                'sisa_surkes_belum_daftar' => 1,
                'realisasi_surkes' => 2,
                'alat_non_surkes' => 3,
            ];

            $statusCompare = ($order[$a['status']] ?? 99) <=> ($order[$b['status']] ?? 99);
            if ($statusCompare !== 0) {
                return $statusCompare;
            }

            $unitCompare = strcasecmp((string)$a['namaperusahaan'], (string)$b['namaperusahaan']);
            if ($unitCompare !== 0) {
                return $unitCompare;
            }

            return strcasecmp((string)$a['namaproduk'], (string)$b['namaproduk']);
        });

        return $this->respond([
            'summary' => $summary,
            'by_unit' => $byUnit,
            'by_lingkup' => $byLingkup,
            'rows' => $request->boolean('summary_only') ? [] : $rows,
            'filters' => [
                'year' => $year,
                'lokasi_id' => $lokasiId,
                'mitra_id' => $mitraId,
                'lingkup_id' => $lingkupId,
                'keyword' => $keyword,
                'status' => $statusFilter ?: 'all',
            ],
            'message' => 'ok',
        ]);
    }

    private function buildSurkesAlatKey($objectAlatFk, $unitId, $namaProduk, $merk, $tipe, $serialNumber, $fallback)
    {
        if (!empty($objectAlatFk)) {
            return 'alat:' . (string)$objectAlatFk;
        }

        $parts = [
            (string)($unitId ?? ''),
            $this->normalizeSurkesKeyPart($namaProduk),
            $this->normalizeSurkesKeyPart($merk),
            $this->normalizeSurkesKeyPart($tipe),
            $this->normalizeSurkesKeyPart($serialNumber),
        ];

        $hasReadableIdentity = false;
        foreach (array_slice($parts, 1) as $part) {
            if ($part !== '-') {
                $hasReadableIdentity = true;
                break;
            }
        }

        if (!$hasReadableIdentity) {
            return 'detail:' . (string)$fallback;
        }

        $identifier = implode('|', $parts);
        if (trim(str_replace('|', '', $identifier)) === '') {
            return 'detail:' . (string)$fallback;
        }

        return 'manual:' . $identifier;
    }

    private function normalizeSurkesKeyPart($value)
    {
        $value = strtolower(trim((string)($value ?? '')));
        $value = preg_replace('/\s+/', ' ', $value);
        return $value ?: '-';
    }

    private function pushSurkesRegistrationGroup(array &$map, string $key, $row)
    {
        if (!isset($map[$key])) {
            $map[$key] = [
                'key' => $key,
                'unit_id' => $row->unit_id,
                'namaperusahaan' => $row->namaperusahaan,
                'objectalatfk' => $row->objectalatfk,
                'namaproduk' => $row->namaproduk,
                'namamerk' => $row->namamerk,
                'namatipe' => $row->namatipe,
                'namaserialnumber' => $row->namaserialnumber,
                'lingkupfk' => $row->lingkupkalibrasifk,
                'lingkupkalibrasi' => $row->lingkupkalibrasi,
                'registration_count' => 0,
                'selesai_count' => 0,
                'jenisorder' => [],
                'nopendaftaran' => [],
                'noorderalat' => [],
                'latest' => null,
                'latest_time' => null,
            ];
        }

        $group = &$map[$key];
        $group['registration_count']++;
        if (!empty($row->tgl_selesai)) {
            $group['selesai_count']++;
        }

        $jenisOrder = trim((string)($row->jenisorder ?? ''));
        if ($jenisOrder !== '') {
            $group['jenisorder'][$jenisOrder] = $jenisOrder;
        }

        if (!empty($row->nopendaftaran)) {
            $group['nopendaftaran'][(string)$row->nopendaftaran] = (string)$row->nopendaftaran;
        }

        if (!empty($row->noorderalat)) {
            $group['noorderalat'][(string)$row->noorderalat] = (string)$row->noorderalat;
        }

        $rowTime = strtotime((string)$row->tglregistrasi) ?: 0;
        if ($group['latest'] === null || $rowTime >= (int)$group['latest_time']) {
            $group['latest'] = $row;
            $group['latest_time'] = $rowTime;
            $group['lingkupfk'] = $row->lingkupkalibrasifk;
            $group['lingkupkalibrasi'] = $row->lingkupkalibrasi;
        }

        unset($group);
    }

    private function formatUlabLocation($locationId)
    {
        if ((int)$locationId === 1) {
            return 'Jakarta';
        }

        if ((int)$locationId === 2) {
            return 'Gresik';
        }

        return '-';
    }

    private function resolveSurkesWorkProgress($row)
    {
        if (empty($row)) {
            return [
                'status_terakhir' => 'Belum masuk pendaftaran tahun ini',
                'progress_persen' => 0,
            ];
        }

        $isRepair = strtolower(trim((string)($row->jenisorder ?? ''))) === 'repair';
        $technicalByExecutor = $isRepair
            ? ($row->tglisilaporanrepairpelaksana ?? null)
            : ($row->tglisilembarkerjapelaksana ?? null);
        $technicalBySupervisor = $isRepair
            ? ($row->tglisilaporanrepairpenyelia ?? null)
            : ($row->tglisilembarkerjapenyelia ?? null);
        $approvedBySupervisor = $isRepair
            ? ($row->tglsetujupenyelialaporanrepair ?? null)
            : ($row->tglsetujupenyelialembarkerja ?? null);
        $approvedByAsman = $isRepair
            ? ($row->tglsetujuasmanlaporanrepair ?? null)
            : ($row->tglsetujuasmanlembarkerja ?? null);
        $approvedByManager = $isRepair
            ? ($row->tglsetujumanagerlaporanrepair ?? null)
            : ($row->tglsetujumanagerlembarkerja ?? null);

        $progress = 10;
        $milestones = [
            [$row->tglkajiulang ?? null, 20],
            [$row->tglverifasman ?? null, 30],
            [$row->tglverifpenyelia ?? null, 40],
            [$row->tglverifpelaksana ?? null, 50],
            [$technicalByExecutor, 65],
            [$technicalBySupervisor, 75],
            [$approvedBySupervisor, 85],
            [$approvedByAsman, 95],
            [$approvedByManager, 100],
        ];

        foreach ($milestones as $milestone) {
            if (!empty($milestone[0])) {
                $progress = max($progress, (int)$milestone[1]);
            }
        }

        if (!empty($approvedByManager)) {
            return [
                'status_terakhir' => $isRepair
                    ? 'Selesai - Laporan repair disetujui Manager'
                    : 'Selesai - Sertifikat disetujui Manager',
                'progress_persen' => 100,
            ];
        }

        if (!empty($approvedByAsman)) {
            $status = 'Menunggu persetujuan Manager';
        } else if (!empty($approvedBySupervisor)) {
            $status = 'Menunggu persetujuan Asman';
        } else if (!empty($technicalBySupervisor)) {
            $status = 'Menunggu persetujuan Penyelia';
        } else if (!empty($technicalByExecutor)) {
            $status = 'Menunggu pemeriksaan/pengisian Penyelia';
        } else if (!empty($row->tglverifpelaksana)) {
            $status = $isRepair
                ? 'Laporan repair belum diisi Pelaksana'
                : 'Sertifikat/lembar kerja belum diisi Pelaksana';
        } else if (!empty($row->tglverifpenyelia)) {
            $status = 'Menunggu verifikasi Pelaksana';
        } else if (!empty($row->tglverifasman)) {
            $status = 'Menunggu verifikasi Penyelia';
        } else if (!empty($row->tglkajiulang)) {
            $status = 'Menunggu verifikasi Asman';
        } else {
            $status = 'Menunggu kaji ulang Admin';
        }

        return [
            'status_terakhir' => $status,
            'progress_persen' => $progress,
        ];
    }

    private function summarizeSurkesMonitoringRows(array $rows)
    {
        $summary = [
            'target_mapping' => 0,
            'realisasi_unik' => 0,
            'sudah_terdaftar' => 0,
            'belum_terdaftar' => 0,
            'surkes_tanpa_mapping' => 0,
            'rencana_alat_surkes' => 0,
            'realisasi_surkes' => 0,
            'sisa_surkes_belum_daftar' => 0,
            'alat_non_surkes' => 0,
            'total_registrasi_rows' => 0,
            'duplicate_registrasi_rows' => 0,
            'duplicate_mapping_rows' => 0,
            'selesai' => 0,
            'belum_selesai' => 0,
            'lingkup_mismatch' => 0,
            'progress' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'] ?? '';
            $hasRegistration = $status !== 'sisa_surkes_belum_daftar';

            if ($status !== 'alat_non_surkes') {
                $summary['target_mapping']++;
                $summary['rencana_alat_surkes']++;
            }

            if ($hasRegistration) {
                $summary['realisasi_unik']++;
                $summary['total_registrasi_rows'] += (int)($row['registration_count'] ?? 0);
                $summary['duplicate_registrasi_rows'] += (int)($row['duplicate_count'] ?? 0);

                if (($row['status_pekerjaan'] ?? '') === 'Selesai') {
                    $summary['selesai']++;
                } else {
                    $summary['belum_selesai']++;
                }
            }

            if ($status === 'realisasi_surkes') {
                $summary['sudah_terdaftar']++;
                $summary['realisasi_surkes']++;
            } else if ($status === 'sisa_surkes_belum_daftar') {
                $summary['belum_terdaftar']++;
                $summary['sisa_surkes_belum_daftar']++;
            } else if ($status === 'alat_non_surkes') {
                $summary['surkes_tanpa_mapping']++;
                $summary['alat_non_surkes']++;
            }

            $summary['duplicate_mapping_rows'] += (int)($row['mapping_duplicate_count'] ?? 0);

            if (!empty($row['lingkup_mismatch'])) {
                $summary['lingkup_mismatch']++;
            }
        }

        if ($summary['target_mapping'] > 0) {
            $summary['progress'] = round(($summary['realisasi_surkes'] / $summary['target_mapping']) * 100, 1);
        }

        return $summary;
    }

    private function groupSurkesMonitoringRows(array $rows, string $mode)
    {
        $grouped = [];

        foreach ($rows as $row) {
            if ($mode === 'lingkup') {
                $id = $row['objectlingkupfk'] ?: ($row['lingkup_registrasi'] ?: 'tanpa_lingkup');
                $label = $row['lingkup_mapping'] ?: ($row['lingkup_registrasi'] ?: 'Tanpa Lingkup');
            } else {
                $id = $row['unit_id'] ?: 'tanpa_unit';
                $label = $row['namaperusahaan'] ?: 'Tanpa Unit';
            }

            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $id,
                    'label' => $label,
                    'target_mapping' => 0,
                    'realisasi_unik' => 0,
                    'sudah_terdaftar' => 0,
                    'belum_terdaftar' => 0,
                    'surkes_tanpa_mapping' => 0,
                    'rencana_alat_surkes' => 0,
                    'realisasi_surkes' => 0,
                    'sisa_surkes_belum_daftar' => 0,
                    'alat_non_surkes' => 0,
                    'progress' => 0,
                ];
            }

            $status = $row['status'] ?? '';
            if ($status !== 'alat_non_surkes') {
                $grouped[$id]['target_mapping']++;
                $grouped[$id]['rencana_alat_surkes']++;
            }

            if ($status !== 'sisa_surkes_belum_daftar') {
                $grouped[$id]['realisasi_unik']++;
            }

            if (isset($grouped[$id][$status])) {
                $grouped[$id][$status]++;
            }

            if ($status === 'realisasi_surkes') {
                $grouped[$id]['sudah_terdaftar']++;
            } else if ($status === 'sisa_surkes_belum_daftar') {
                $grouped[$id]['belum_terdaftar']++;
            } else if ($status === 'alat_non_surkes') {
                $grouped[$id]['surkes_tanpa_mapping']++;
            }
        }

        foreach ($grouped as &$item) {
            if ((int)$item['target_mapping'] > 0) {
                $item['progress'] = round(((int)$item['realisasi_surkes'] / (int)$item['target_mapping']) * 100, 1);
            }
        }
        unset($item);

        return collect(array_values($grouped))
            ->sortByDesc(function ($item) {
                return (int)$item['sisa_surkes_belum_daftar'] + (int)$item['alat_non_surkes'];
            })
            ->values()
            ->all();
    }

    public function getDropdownMappingLayanan()
    {
        $kategori = DB::table('mappinglayanan_m')
            ->select('kategori')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori')
            ->map(function ($value) {
                return ['value' => $value, 'label' => $value];
            })
            ->values();

        $lingkup = DB::table('lingkupkalibrasi_m')
            ->where('statusenabled', true)
            ->orderBy('lingkupkalibrasi')
            ->get(['id', 'lingkupkalibrasi'])
            ->map(function ($value) {
                return ['value' => $value->id, 'label' => $value->lingkupkalibrasi];
            })
            ->values();

        return $this->respond([
            'kategori' => $kategori,
            'lingkup' => $lingkup,
        ]);
    }

    public function getListMappingLayanan(Request $request)
    {
        $query = DB::table('mappinglayanan_m as ml')
            ->join('lingkupkalibrasi_m as lk', 'lk.id', '=', 'ml.objectlingkupfk')
            ->select('ml.*', 'lk.lingkupkalibrasi as lingkup');

        if ($request->filled('search')) {
            $search = '%' . strtolower(trim($request->query('search'))) . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw('LOWER(ml.namalayanan) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(ml.merktipe) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(lk.lingkupkalibrasi) LIKE ?', [$search]);
            });
        }

        if ($request->filled('kategori')) {
            $query->where('ml.kategori', $request->query('kategori'));
        }

        if ($request->filled('objectlingkupfk')) {
            $query->where('ml.objectlingkupfk', $request->query('objectlingkupfk'));
        }

        if ($request->filled('statusenabled')) {
            $status = filter_var(
                $request->query('statusenabled'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
            if ($status !== null) {
                $query->where('ml.statusenabled', $status);
            }
        }

        $data = $query
            ->orderBy('ml.kategori')
            ->orderBy('lk.lingkupkalibrasi')
            ->orderBy('ml.namalayanan')
            ->orderBy('ml.merktipe')
            ->get();

        $summary = DB::table('mappinglayanan_m')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN statusenabled = true THEN 1 ELSE 0 END) as aktif'),
                DB::raw('SUM(CASE WHEN statusenabled = false THEN 1 ELSE 0 END) as nonaktif')
            )
            ->first();

        return $this->respond([
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    public function saveMappingLayanan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'kategori' => 'required|in:KAN,Non KAN|max:30',
            'objectlingkupfk' => 'required|integer|exists:lingkupkalibrasi_m,id',
            'namalayanan' => 'required|string|max:255',
            'merktipe' => 'required|string|max:255',
            'statusenabled' => 'nullable|boolean',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->respond(
                $validator->errors(),
                422,
                $validator->errors()->first()
            );
        }

        $id = $request->input('id');
        $existing = $id
            ? DB::table('mappinglayanan_m')->where('id', $id)->first()
            : null;

        if ($id && !$existing) {
            return $this->respond(null, 404, 'Data layanan tidak ditemukan');
        }

        $duplicate = DB::table('mappinglayanan_m')
            ->where('kategori', trim($request->input('kategori')))
            ->where('objectlingkupfk', $request->input('objectlingkupfk'))
            ->where('namalayanan', trim($request->input('namalayanan')))
            ->where('merktipe', trim($request->input('merktipe')));

        if ($id) {
            $duplicate->where('id', '<>', $id);
        }

        if ($duplicate->exists()) {
            return $this->respond(null, 422, 'Layanan dengan data yang sama sudah tersedia');
        }

        $gambar = $existing ? $existing->gambar : null;
        $newImagePath = null;
        DB::beginTransaction();
        try {
            if ($request->hasFile('gambar')) {
                $destination = $this->mappingLayananUploadDirectory();
                $file = $request->file('gambar');
                $gambar = date('YmdHis') . '-' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
                $file->move($destination, $gambar);
                $newImagePath = $destination . DIRECTORY_SEPARATOR . $gambar;
            }

            $payload = [
                'kategori' => trim($request->input('kategori')),
                'objectlingkupfk' => $request->input('objectlingkupfk'),
                'namalayanan' => trim($request->input('namalayanan')),
                'merktipe' => trim($request->input('merktipe')),
                'gambar' => $gambar,
                'statusenabled' => $request->has('statusenabled')
                    ? $request->boolean('statusenabled')
                    : true,
                'updated_at' => now(),
            ];

            if ($id) {
                DB::table('mappinglayanan_m')->where('id', $id)->update($payload);
                $message = 'Layanan berhasil diperbarui';
            } else {
                $payload['created_at'] = now();
                $id = DB::table('mappinglayanan_m')->insertGetId($payload);
                $message = 'Layanan berhasil ditambahkan';
            }

            DB::commit();

            if (
                $newImagePath
                && $existing
                && $existing->gambar
                && $existing->gambar !== $gambar
            ) {
                $this->deleteUnusedMappingLayananImage($existing->gambar);
            }

            return $this->respond(['id' => $id], 200, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($newImagePath && is_file($newImagePath)) {
                unlink($newImagePath);
            }
            return $this->respond(null, 500, 'Gagal menyimpan layanan: ' . $e->getMessage());
        }
    }

    public function uploadBulkMappingLayananImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'required|integer|distinct',
            'gambar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->respond(
                $validator->errors(),
                422,
                $validator->errors()->first()
            );
        }

        $ids = array_values(array_unique(array_map('intval', $request->input('ids'))));
        $services = DB::table('mappinglayanan_m')
            ->whereIn('id', $ids)
            ->get(['id', 'gambar']);

        if ($services->count() !== count($ids)) {
            return $this->respond(null, 404, 'Sebagian data layanan tidak ditemukan');
        }

        $file = $request->file('gambar');
        $gambar = date('YmdHis') . '-' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $newImagePath = null;

        DB::beginTransaction();
        try {
            $destination = $this->mappingLayananUploadDirectory();
            $newImagePath = $destination . DIRECTORY_SEPARATOR . $gambar;
            $file->move($destination, $gambar);

            DB::table('mappinglayanan_m')
                ->whereIn('id', $ids)
                ->update([
                    'gambar' => $gambar,
                    'updated_at' => now(),
                ]);

            DB::commit();

            $services
                ->pluck('gambar')
                ->filter()
                ->unique()
                ->each(function ($oldImage) use ($gambar) {
                    if ($oldImage !== $gambar) {
                        $this->deleteUnusedMappingLayananImage($oldImage);
                    }
                });

            return $this->respond(
                ['updated' => count($ids)],
                200,
                'Gambar berhasil diterapkan ke ' . count($ids) . ' layanan'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            if ($newImagePath && is_file($newImagePath)) {
                unlink($newImagePath);
            }
            return $this->respond(null, 500, 'Gagal mengunggah gambar massal: ' . $e->getMessage());
        }
    }

    public function setStatusMappingLayanan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'statusenabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->respond(
                $validator->errors(),
                422,
                $validator->errors()->first()
            );
        }

        $exists = DB::table('mappinglayanan_m')
            ->where('id', $request->input('id'))
            ->exists();

        if (!$exists) {
            return $this->respond(null, 404, 'Data layanan tidak ditemukan');
        }

        DB::table('mappinglayanan_m')
            ->where('id', $request->input('id'))
            ->update([
                'statusenabled' => $request->boolean('statusenabled'),
                'updated_at' => now(),
            ]);

        return $this->respond(
            null,
            200,
            $request->boolean('statusenabled')
                ? 'Layanan berhasil diaktifkan'
                : 'Layanan berhasil dinonaktifkan'
        );
    }

    private function deleteUnusedMappingLayananImage($image)
    {
        if (!$image) {
            return;
        }

        $isUsed = DB::table('mappinglayanan_m')
            ->where('gambar', $image)
            ->exists();

        if (!$isUsed) {
            $path = public_path('mapping-layanan/' . $image);
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function mappingLayananUploadDirectory()
    {
        $destination = public_path('mapping-layanan');

        if (
            !is_dir($destination)
            && !mkdir($destination, 0775, true)
            && !is_dir($destination)
        ) {
            throw new \RuntimeException('Folder upload mapping-layanan tidak dapat dibuat');
        }

        if (!is_writable($destination)) {
            throw new \RuntimeException(
                'Folder public/mapping-layanan tidak writable oleh user PHP/web server'
            );
        }

        return $destination;
    }

}
