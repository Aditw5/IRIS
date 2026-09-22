<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class LaporanRegistrasiAlatCtrl extends Controller
{
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function getLaporanRegistrasiAlat(Request $request)
    {
        $rangeDate = [$request->tglAwal, $request->tglAkhir];

        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.statusorderpenyelia',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.tglverifasman',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtrd.setujuilembarkerjapenyelia',
                'mtrd.tglsetujupenyelialembarkerja',
                'mtrd.penyeliasetujulembarkerjafk',
                'mtrd.setujuilembarkerjaasman',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.asmansetujulembarkerjafk',
                'mtrd.statusorderasman',
                'mtrd.tglverifpelaksana',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereBetween(DB::raw("CAST(mtr.tglregistrasi AS DATE)"), $rangeDate);

        if (isset($request['search']) && $request['search'] != '') {
            $searchTerm = '%' . $request['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('lk.lokasi', 'ilike', $searchTerm)
                    ->orWhere('mmp.namamerk', 'ilike', $searchTerm)
                    ->orWhere('lp.lingkupkalibrasi', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaserialnumber', 'ilike', $searchTerm)
                    ->orWhere('mmp.namatipe', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaproduk', 'ilike', $searchTerm);
            });
        }

        $data = $data->orderBy('mtr.tglregistrasi');
        $data = $data->get();

        return $this->respond([
            'data' => $data,
            'message' => 'eaaditwiran19@gmail.com',
        ]);
    }

    public function getLaporanMonitoring(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->leftjoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtr.lokasirepair')
            ->select(
                'mtr.norec',
                'mt.id as idunit',
                'mt.namaperusahaan',
                'mt.alamatktr',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.isikepuasanpelanggan',
                'mtr.lokasikalibrasi',
                'mtr.lokasirepair',
                'mtr.statusorder',
                'mtr.namapenanggungjawab',
                'mtr.tanggalmulai',
                'lk.lokasi as lokasikalibrasi',
                'lk1.lokasi as lokasirepair',
            )
            ->where('mtr.statusenabled', true)
            ->where('mt.statusenabled', true)
            ->whereNotNull('mtr.nopendaftaran');
        if (isset($request['search']) && $request['search'] != '') {
            $searchTerm = '%' . $request['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm);
            });
        }
         if (!empty($r['lokasifk'])) {
            $data = $data->whereRaw("COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?", [$r['lokasifk']]);
        }
        if (!empty($r['jenisorder'])) {
            $data = $data->where("mtr.jenisorder", [$r['jenisorder']]);
        }
        if (!empty($r['unitfk'])) {
            $data = $data->where('mt.id', $r['unitfk']);
        }
        $data = $data->orderBy('mtr.tglregistrasi', 'DESC');
        $data = $data->get();

        $dataDetail = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->select(
                'mtr.norec',
                'mtr.statusorder',
                'mtrd.norec as norec_detail',
                'mtrd.noregistrasifk',
                'mtrd.keterangan',
                'mtrd.noorderalat',
                'mmp.namaproduk',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.iskaji',
                'mtrd.ulasanpenilaian',
                'mtr.jenisorder',
                'mtr.isSimpanTerima',
                'mtrd.bintangpenilaian',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtrd.statusorderpelaksana',
                'mtrd.statusorderpenyelia',
                'mtrd.statusorderasman',
                'mtrd.statusordermanager',
                'mtrd.durasikalbrasi',
                'mtrd.nosertifikat',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.pelaksanaisilaporanrepairfk',
                'mtrd.isverifikasi',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
            )
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->get();

        $detailAll = DB::table('mitraregistrasidetail_t')
            ->select(
                'noregistrasifk',
                'tglsetujumanagerlembarkerja',
                'tglsetujumanagerlaporanrepair'
            )
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregistrasifk');
        $dataDetailByRegistrasi = $dataDetail->groupBy('noregistrasifk');

        foreach ($data as $item) {
            $detailList = $detailAll[$item->norec] ?? collect();
            $item->jumlahdetail = $detailList->count();
            $item->jumlahselesai = 0;

            foreach ($detailList as $detail) {
                if (
                    ($item->jenisorder === 'kalibrasi' && $detail->tglsetujumanagerlembarkerja !== null) ||
                    ($item->jenisorder === 'repair' && $detail->tglsetujumanagerlaporanrepair !== null)
                ) {
                    $item->jumlahselesai++;
                }
            }
            $item->jumlahbelumselesai = $item->jumlahdetail - $item->jumlahselesai;

            $item->detail = [];
            foreach ($dataDetailByRegistrasi->get($item->norec, collect()) as $dd) {
                if ($dd->noregistrasifk === $item->norec) {
                    $stPel = (int) ($dd->statusorderpelaksana ?? 0);
                    $stPen = (int) ($dd->statusorderpenyelia   ?? 0);
                    $stAsm = (int) ($dd->statusorderasman     ?? 0);
                    $stMan = (int) ($dd->statusordermanager   ?? 0);

                    $jenis = strtolower($item->jenisorder ?? '');
                    $isRepair = ($jenis === 'repair');

                    $termIsi   = $isRepair ? 'Laporan repair' : 'Lembar kerja';
                    $termAppPen = $isRepair ? 'Laporan repair disetujui penyelia'
                        : 'Lembar kerja/sertifikat disetujui penyelia';
                    $termAppAsm = $isRepair ? 'Laporan repair disetujui asman'
                        : 'Lembar kerja/sertifikat disetujui asman';
                    $termAppMan = $isRepair ? 'Laporan repair disetujui manager'
                        : 'Lembar kerja/sertifikat disetujui manager';

                    if ($stMan === 2) {
                        $progressLabel = $termAppMan;
                        $progressStep  = 5;
                    } elseif ($stAsm === 2) {
                        $progressLabel = $termAppAsm;
                        $progressStep  = 4;
                    } elseif ($stPen === 2) {
                        $progressLabel = $termAppPen;
                        $progressStep  = 3;
                    } elseif ($stPel === 2 || $stPen === 2) {
                        $progressLabel = $termIsi . ' diisi';
                        $progressStep  = 2;
                    } else {
                        $progressLabel = $termIsi . ' belum diisi';
                        $progressStep  = 1;
                    }

                    $item->detail[] = [
                        'norec'             => $dd->norec,
                        'norec_detail'      => $dd->norec_detail,
                        'namaproduk'        => $dd->namaproduk,
                        'namamerk'          => $dd->namamerk,
                        'namatipe'          => $dd->namatipe,
                        'namaserialnumber'  => $dd->namaserialnumber,
                        'noorderalat'       => $dd->noorderalat,
                        'bintangpenilaian'  => $dd->bintangpenilaian,
                        'keterangan'        => $dd->keterangan,
                        'ulasanpenilaian'   => $dd->ulasanpenilaian,
                        'lingkupkalibrasi'  => $dd->lingkupkalibrasi,
                        'progress'          => $progressLabel,
                        'progress_step'     => $progressStep,
                        'pelaksanateknik'   => $dd->pelaksanateknik,
                        'penyeliateknik'    => $dd->penyeliateknik,
                        'durasikalbrasi'    => $dd->durasikalbrasi,
                        'nosertifikat'      => $dd->nosertifikat,
                        'pelaksanaisilembarkerjafk'      => $dd->pelaksanaisilembarkerjafk,
                        'pelaksanaisilaporanrepairfk'      => $dd->pelaksanaisilaporanrepairfk,
                        'isverifikasi'      => $dd->isverifikasi,
                        'jenisorder'        => $dd->jenisorder,
                        'iskaji'            => $dd->iskaji,
                        'isSimpanTerima'    => $dd->isSimpanTerima,
                        'statusorder'    => $dd->statusorder,
                        'penyeliateknikfk'   => $dd->penyeliateknikfk,
                    ];
                }
            }

            $ratings = array_filter(
                array_column($item->detail, 'bintangpenilaian'),
                fn($v) => $v !== null && is_numeric($v)
            );
            $item->rata2Bintang = count($ratings) > 0
                ? round(array_sum($ratings) / count($ratings), 2)
                : null;
        }

        $result = [
            'length' => $data->count(),
            'detail' => $data,
            'as'     => '@aditwiran19@gmail.com'
        ];

        return $this->respond($result);
    }
}
