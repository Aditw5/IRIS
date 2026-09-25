<?php

namespace App\Http\Controllers\Registrasi;

use App\Http\Controllers\Controller;
use App\Models\Transaksi\MitraRegistrasi;
use App\Models\Transaksi\MitraRegistrasiDetail;
use App\Models\Transaksi\MitraRegistrasiTerima;
use App\Models\Transaksi\MitraRegistrasiTerimaD;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\App;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Builder;
use Illuminate\Support\Facades\Log;

class MitraCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function listMitraGrid(Request $r)
    {
        $tglAwal = $r['dari'];
        $tglAkhir = $r['sampai'];
        $data  = DB::table('mitra_m as mt')
            ->leftjoin('mitraregistrasi_t as mtr', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->select(
                'mt.id',
                'mt.namaperusahaan',
                'mt.tgldaftar',
                'mt.email',
                'mt.nohp',
                'mt.foto',
                'mt.progress',
                'mtr.tglregistrasi',
                'mtr.petugas',
                'mtr.nopendaftaran',
                'mtr.lokasikalibrasi',
                'mtr.norec as iddetail',
                'mtr.iskaji',
                'mtr.statusorder',
                'mtr.namapenanggungjawab',
                'mtr.tanggalkonfirmasipendaftaran',
                'mtr.jenisorder',
                'mtr.isregiscustomer',
                'mtr.verifregiscustomer',
                'mtr.filecustomerams',
                'mtr.filecustomertools',
                'mtr.isSimpanTerima',
                'mtr.lokasikalibrasi',
                'mtr.lokasirepair',
                'mtr.isstandarulab',
                'mtr.iskalibrasiinternal',
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->whereBetween('mtr.tglregistrasi', [$tglAwal, $tglAkhir]);
        $filter = false;
        if (isset($r['search']) && $r['search'] != '') {
            $filter = true;
            $searchTerm = '%' . $r['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm);
            });
        }
        if (!empty($r['lokasifk'])) {
            $data = $data->whereRaw("COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?", [$r['lokasifk']]);
        }
        if (!empty($r['jenisorder'])) {
            $data = $data->where("mtr.jenisorder", $r['jenisorder']);
        }
        if (!empty($r['unitfk'])) {
            $data = $data->where('mt.id', $r['unitfk']);
        }
        $page = 1;
        if (isset($r['page']) && $r['page'] != '') {
            $page = $r['page'];
        }
        $data = $data->orderByDesc('mtr.tglregistrasi');
        $data = $data->paginate(isset($r['limit']) ? $r['limit'] : 10, ['*'], 'page', $page);

        foreach ($data as $d) {
            $d->progress = $d->progress == null ? 0 : (int)  $d->progress;
            if ((int)  $d->progress <= 50) {
                $d->class_proggress = 'danger';
            }
            if ((int)  $d->progress > 50 && (int)  $d->progress <= 80) {
                $d->class_proggress = 'warning';
            }
            if ((int)  $d->progress > 80) {
                $d->class_proggress = 'success';
            }
        }

        $detailAll = DB::table('mitraregistrasidetail_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregistrasifk');

        foreach ($data as $item) {
            $detailList = $detailAll[$item->iddetail] ?? collect();
            $item->jumlahdetail = $detailList->count();
            $item->jumlahselesai = 0;
            $item->jumlahditerima = 0;

            foreach ($detailList as $detail) {
                if (($item->jenisorder === 'kalibrasi' && $detail->tglsetujumanagerlembarkerja !== null) || ($item->jenisorder === 'repair' && $detail->tglsetujumanagerlaporanrepair !== null)) {
                    $item->jumlahselesai++;
                }
                if ($detail->isterima == true) {
                    $item->jumlahditerima++;
                }
            }
            $item->jumlahbelumselesai = $item->jumlahdetail - $item->jumlahselesai;
            $item->jumlahbelumterima = $item->jumlahdetail - $item->jumlahditerima;
        }

        return $this->respond($data);
    }

    public function dashboardStatistik(Request $r)
    {
        $tglAwal = $r->get('dari') ?: Carbon::now()->startOfYear()->format('Y-m-d 00:00:00');
        $tglAkhir = $r->get('sampai') ?: Carbon::now()->format('Y-m-d 23:59:59');

        $base = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select(
                'mtr.norec',
                'mtr.tglregistrasi',
                'mtr.jenisorder',
                'mtr.iskaji',
                'mtr.statusorder',
                'mtr.isregiscustomer',
                'mtr.verifregiscustomer',
                'mtr.isstandarulab',
                'mtr.iskalibrasiinternal',
                'mt.id as unitfk',
                'mt.namaperusahaan'
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->whereBetween('mtr.tglregistrasi', [$tglAwal, $tglAkhir]);

        if ($r->filled('search')) {
            $searchTerm = '%' . $r->get('search') . '%';
            $base = $base->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm);
            });
        }

        if ($r->filled('lokasifk')) {
            $base = $base->whereRaw("COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?", [$r->get('lokasifk')]);
        }

        if ($r->filled('jenisorder')) {
            $base = $base->where('mtr.jenisorder', $r->get('jenisorder'));
        }

        if ($r->filled('unitfk')) {
            $base = $base->where('mt.id', $r->get('unitfk'));
        }

        $detailSub = function () use ($base) {
            return DB::query()
                ->fromSub((clone $base), 'reg')
                ->leftJoin('mitraregistrasidetail_t as mtrd', function ($join) {
                    $join->on('mtrd.noregistrasifk', '=', 'reg.norec')
                        ->where('mtrd.statusenabled', true);
                });
        };

        $selesaiCase = "
            CASE
                WHEN reg.jenisorder = 'kalibrasi' AND mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1
                WHEN reg.jenisorder = 'repair' AND mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1
                ELSE 0
            END
        ";

        $diambilCase = "CASE WHEN COALESCE(mtrd.isterima, false) = true THEN 1 ELSE 0 END";

        $summaryDetail = $detailSub()
            ->selectRaw('COUNT(DISTINCT reg.norec) as total_registrasi')
            ->selectRaw('COUNT(mtrd.norec) as total_alat')
            ->selectRaw("COALESCE(SUM($selesaiCase), 0) as total_selesai")
            ->selectRaw("COALESCE(SUM($diambilCase), 0) as total_diambil")
            ->first();

        $statusSummary = DB::query()
            ->fromSub((clone $base), 'reg')
            ->selectRaw("COALESCE(SUM(CASE WHEN reg.iskaji IS NULL OR reg.iskaji = false THEN 1 ELSE 0 END), 0) as belum_kaji")
            ->selectRaw("COALESCE(SUM(CASE WHEN reg.iskaji = true AND COALESCE(reg.statusorder, 0) <> 1 THEN 1 ELSE 0 END), 0) as sudah_kaji")
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(reg.statusorder, 0) = 1 THEN 1 ELSE 0 END), 0) as verif_asman")
            ->selectRaw("COALESCE(SUM(CASE WHEN reg.isregiscustomer = true AND reg.verifregiscustomer IS NULL THEN 1 ELSE 0 END), 0) as menunggu_admin")
            ->first();

        $byUnit = $detailSub()
            ->select('reg.unitfk', 'reg.namaperusahaan')
            ->selectRaw('COUNT(DISTINCT reg.norec) as total_registrasi')
            ->selectRaw('COUNT(mtrd.norec) as total_alat')
            ->selectRaw("COALESCE(SUM($selesaiCase), 0) as total_selesai")
            ->selectRaw("COALESCE(SUM($diambilCase), 0) as total_diambil")
            ->groupBy('reg.unitfk', 'reg.namaperusahaan')
            ->orderByDesc(DB::raw('COUNT(DISTINCT reg.norec)'))
            ->limit(8)
            ->get();

        $byJenisOrder = DB::query()
            ->fromSub((clone $base), 'reg')
            ->selectRaw("COALESCE(NULLIF(reg.jenisorder, ''), 'lainnya') as jenisorder")
            ->selectRaw('COUNT(*) as total')
            ->groupBy(DB::raw("COALESCE(NULLIF(reg.jenisorder, ''), 'lainnya')"))
            ->orderByDesc('total')
            ->get();

        $monthlyTrend = DB::query()
            ->fromSub((clone $base), 'reg')
            ->selectRaw("TO_CHAR(DATE_TRUNC('month', reg.tglregistrasi), 'YYYY-MM') as bulan")
            ->selectRaw('COUNT(*) as total')
            ->groupBy(DB::raw("DATE_TRUNC('month', reg.tglregistrasi)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', reg.tglregistrasi)"))
            ->get();

        $totalAlat = (int) ($summaryDetail->total_alat ?? 0);
        $totalSelesai = (int) ($summaryDetail->total_selesai ?? 0);
        $totalDiambil = (int) ($summaryDetail->total_diambil ?? 0);

        $result = [
            'summary' => [
                'total_registrasi' => (int) ($summaryDetail->total_registrasi ?? 0),
                'total_alat' => $totalAlat,
                'total_selesai' => $totalSelesai,
                'total_diambil' => $totalDiambil,
                'belum_selesai' => max($totalAlat - $totalSelesai, 0),
                'belum_diambil' => max($totalAlat - $totalDiambil, 0),
            ],
            'status' => [
                'belum_kaji' => (int) ($statusSummary->belum_kaji ?? 0),
                'sudah_kaji' => (int) ($statusSummary->sudah_kaji ?? 0),
                'verif_asman' => (int) ($statusSummary->verif_asman ?? 0),
                'menunggu_admin' => (int) ($statusSummary->menunggu_admin ?? 0),
            ],
            'by_unit' => $byUnit,
            'by_jenisorder' => $byJenisOrder,
            'monthly_trend' => $monthlyTrend,
        ];

        return $this->respond($result);
    }

    public function getAlatRegistrasi(Request $r)
    {
        $statusMgr = $r->input('statusordermanager');
        $aktivitasAlat = $r->input('aktivitasalat');
        $semuaStatus = filter_var($r->input('semuastatus'), FILTER_VALIDATE_BOOLEAN);

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
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('jenissurkes_m as js', 'js.id', '=', 'mtrd.statussurkesfk')
            ->leftjoin('vendor_m as vm', 'vm.id', '=', 'mtrd.vendorkalibrasifk')
            ->select(
                'mtr.norec',
                'mtr.isstandarulab',
                'mtr.iskalibrasiinternal',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.statusorderpenyelia',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.tglverifasman',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtrd.tanggalmulai',
                'mtr.lokasikalibrasi',
                'mtr.lokasirepair',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'mt.id as idunit',
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
                'mtrd.noorderalat',
                'mtrd.setujuilembarkerjaasman',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.asmansetujulembarkerjafk',
                'mtrd.statusorderasman',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.managersetujulembarkerjafk',
                'mtrd.ketgagalkalibrasi',
                'mtrd.tglverifpelaksana',
                'mtrd.penyeliasetujulaporanrepairfk',
                'mtrd.asmansetujulaporanrepairfk',
                'mtrd.managersetujulaporanrepairfk',
                'mtrd.isverifikasi',
                'mtrd.isVendor',
                'mtrd.isterima',
                'mtrd.statusordermanager',
                'mtrd.fileSertiVendor',
                'mtrd.alasanpembatalanorderalat',
                'mtrd.pelaksanaisilaporanrepairfk',
                'mtrd.statusrepairfk',
                'mtrd.statusenabled as statusaktifpendaftaranalat',
                'mtrd.versisertifikat',
                'mtrd.versilaporanrepair',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.nosertifikat',
                'mtrd.nosertifikatamandemen',
                'js.id as jenissurkesfk',
                'js.jenissurkes',
                'vm.namavendor',
                'vm.id as vendorkalibrasifk',
            )
            ->where('mtr.statusenabled', true);

        if (isset($r['dari']) && $r['dari'] != '') {
            $data = $data->where(DB::raw("mtr.tglregistrasi::date"), '>=', $r->dari);
        }

        if (isset($r['sampai']) && $r['sampai'] != '') {
            $data = $data->where(DB::raw("mtr.tglregistrasi::date"), '<=', $r->sampai);
        }

        if (!empty($r['lokasifk'])) {
            $data = $data->whereRaw("COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?", [$r['lokasifk']]);
        }

        if (!empty($r['ruanglingkupfk'])) {
            $data = $data->where('mtrd.lingkupkalibrasifk', $r['ruanglingkupfk']);
        }

        if (!empty($r['unitfk'])) {
            $data = $data->where('mt.id', $r['unitfk']);
        }

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';

            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('mtrd.noorderalat', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm)
                    ->orWhere('mtrd.nosertifikat', 'ilike', $searchTerm)
                    ->orWhere('mtrd.nosertifikatamandemen', 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namamerk, mmp.namamerk)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namatipe, mmp.namatipe)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber)"), 'ilike', $searchTerm);
            });
        }

        if (!$semuaStatus) {
            $data = $data->when($statusMgr == 3, function ($q) {
                return $q->where('mtrd.statusenabled', false);
            }, function ($q) {
                return $q->where('mtrd.statusenabled', true);
            });

            if ($statusMgr !== null && $statusMgr !== '' && $statusMgr != 3) {
                $data = $data->where(DB::raw('COALESCE(mtrd.statusordermanager, 0)'), '=', $statusMgr);
            }
        }

        if (!$semuaStatus && !empty($aktivitasAlat)) {
            if ($aktivitasAlat === 'belum_kaji') {
                $data = $data->where(function ($q) {
                    $q->whereNull('mtrd.iskaji')
                        ->orWhere('mtrd.iskaji', false);
                });
            }

            if ($aktivitasAlat === 'sudah_kaji') {
                $data = $data->where('mtrd.iskaji', true)
                    ->whereNull('mtrd.pelaksanaisilembarkerjafk')
                    ->whereNull('mtrd.pelaksanaisilaporanrepairfk');
            }

            if ($aktivitasAlat === 'diisi_pelaksana') {
                $data = $data->where(function ($q) {
                    $q->whereNotNull('mtrd.pelaksanaisilembarkerjafk')
                        ->orWhereNotNull('mtrd.pelaksanaisilaporanrepairfk');
                })
                    ->where(function ($q) {
                        $q->whereNull('mtrd.setujuilembarkerjapenyelia')
                            ->orWhere('mtrd.setujuilembarkerjapenyelia', false);
                    })
                    ->whereNull('mtrd.penyeliasetujulaporanrepairfk');
            }

            if ($aktivitasAlat === 'disetujui_penyelia') {
                $data = $data->where(function ($q) {
                    $q->where(function ($qKalibrasi) {
                        $qKalibrasi->where('mtr.jenisorder', 'kalibrasi')
                            ->where('mtrd.setujuilembarkerjapenyelia', true)
                            ->where(function ($q) {
                                $q->whereNull('mtrd.setujuilembarkerjaasman')
                                    ->orWhere('mtrd.setujuilembarkerjaasman', false);
                            })
                            ->where(function ($q) {
                                $q->whereNull('mtrd.setujuilembarkerjamanager')
                                    ->orWhere('mtrd.setujuilembarkerjamanager', false);
                            });
                    })
                        ->orWhere(function ($qRepair) {
                            $qRepair->where('mtr.jenisorder', 'repair')
                                ->whereNotNull('mtrd.penyeliasetujulaporanrepairfk')
                                ->whereNull('mtrd.asmansetujulaporanrepairfk')
                                ->whereNull('mtrd.managersetujulaporanrepairfk');
                        });
                });
            }

            if ($aktivitasAlat === 'disetujui_asman') {
                $data = $data->where(function ($q) {
                    $q->where(function ($qKalibrasi) {
                        $qKalibrasi->where('mtr.jenisorder', 'kalibrasi')
                            ->where('mtrd.setujuilembarkerjaasman', true)
                            ->where(function ($q) {
                                $q->whereNull('mtrd.setujuilembarkerjamanager')
                                    ->orWhere('mtrd.setujuilembarkerjamanager', false);
                            });
                    })
                        ->orWhere(function ($qRepair) {
                            $qRepair->where('mtr.jenisorder', 'repair')
                                ->whereNotNull('mtrd.asmansetujulaporanrepairfk')
                                ->whereNull('mtrd.managersetujulaporanrepairfk');
                        });
                });
            }

            if ($aktivitasAlat === 'disetujui_manager') {
                $data = $data->where(function ($q) {
                    $q->where(function ($qKalibrasi) {
                        $qKalibrasi->where('mtr.jenisorder', 'kalibrasi')
                            ->where('mtrd.setujuilembarkerjamanager', true);
                    })
                        ->orWhere(function ($qRepair) {
                            $qRepair->where('mtr.jenisorder', 'repair')
                                ->whereNotNull('mtrd.managersetujulaporanrepairfk');
                        });
                });
            }

            if ($aktivitasAlat === 'vendor') {
                $data = $data->where('mtrd.isVendor', true);
            }
        }

        $data = $data->orderBy('lp.lingkupkalibrasi');

        if (isset($r['limit'])) {
            $data = $data->limit($r['limit']);
        }

        $data = $data->get();

        $holidayDates = DB::table('master_hari_libur_m')
            ->where('statusenabled', true)
            ->pluck('tanggal')
            ->map(function ($tanggal) {
                return Carbon::parse($tanggal)->format('Y-m-d');
            })
            ->toArray();

        $calcWorkingSeconds = function (?Carbon $start, ?Carbon $end) use ($holidayDates): ?int {
            if (!$start || !$end) {
                return null;
            }

            if ($end->lessThan($start)) {
                [$start, $end] = [$end, $start];
            }

            $workStart = '07:30:00';
            $workEnd   = '16:00:00';

            $totalSeconds = 0;
            $cursor = $start->copy()->startOfDay();
            $lastDay = $end->copy()->startOfDay();

            while ($cursor->lte($lastDay)) {
                $isoDay = $cursor->isoWeekday();
                $currentDate = $cursor->format('Y-m-d');

                $isWeekend = ($isoDay == 6 || $isoDay == 7);
                $isHoliday = in_array($currentDate, $holidayDates);
                $isWorkday = !$isWeekend && !$isHoliday;

                if ($isWorkday) {
                    $dayWorkStart = $cursor->copy()->setTimeFromTimeString($workStart);
                    $dayWorkEnd   = $cursor->copy()->setTimeFromTimeString($workEnd);

                    $effectiveStart = $start->copy()->max($dayWorkStart);
                    $effectiveEnd   = $end->copy()->min($dayWorkEnd);

                    if ($effectiveEnd->greaterThan($effectiveStart)) {
                        $totalSeconds += $effectiveEnd->diffInSeconds($effectiveStart);
                    }
                }

                $cursor->addDay();
            }

            return $totalSeconds;
        };

        foreach ($data as $item) {
            $tglMulai = !empty($item->tanggalmulai)
                ? Carbon::parse($item->tanggalmulai)
                : null;

            $tglSetujuAsman = !empty($item->tglsetujumanagerlembarkerja)
                ? Carbon::parse($item->tglsetujumanagerlembarkerja)
                : null;

            $diffInSeconds = $calcWorkingSeconds($tglMulai, $tglSetujuAsman);

            if ($diffInSeconds !== null) {
                $workdaySeconds = (8 * 60 * 60) + (30 * 60);

                $days = intdiv($diffInSeconds, $workdaySeconds);
                $rem  = $diffInSeconds % $workdaySeconds;

                $hours = intdiv($rem, 3600);
                $rem   = $rem % 3600;

                $item->durasi_proses = "Selesai Dalam: {$days} hari kerja {$hours} jam";
                $item->durasi_detik  = $diffInSeconds;
            } else {
                $item->durasi_proses = null;
                $item->durasi_detik  = null;
            }

            $item->statusPengerjaan = null;
            $item->statusColor = null;
        }

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function HeaderMitra(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select(
                'mt.id',
                'mt.namaperusahaan',
                'mt.tgldaftar',
                'mt.email',
                'mt.nohp',
                'mt.foto',
                'mt.progress',
                'mtr.tglregistrasi',
                'mtr.petugas',
                'mtr.nopendaftaran',
                'mtr.lokasikalibrasi',
                'mtr.norec as norec_pd',
                'mtr.iskaji',
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mt.id', $r['nocmfk'])
            ->where('mtr.norec', $r['norec_pd'])
            ->first();

        $result['mitra'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function pegawaiManager(Request $r)
    {
        $data = DB::table('pegawai_m as pg')
            ->select(
                'pg.id',
                'pg.namalengkap',
                'pg.lokasikalibrasifk',
            )
            ->where('pg.statusenabled', true);

        if (isset($r['jabatan']) && $r['jabatan'] != "" && $r['jabatan'] != "undefined") {
            $data = $data->where('pg.jabatan1fk', '=', $r['jabatan']);
        };

        if (isset($r['lokasi']) && $r['lokasi'] != "" && $r['lokasi'] != "undefined") {
            $data = $data->where('pg.lokasikalibrasifk', '=', $r['lokasi']);
        };
        $data = $data->first();


        $result['data'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function pegawaiLokasi(Request $r)
    {
        $search = $r['query'];
        $data = DB::table('pegawai_m as pg')
            ->select(
                'pg.id',
                'pg.namalengkap',
                'pg.lokasikalibrasifk',
                'pg.statuspegawaifk',
            )
            ->where('pg.statuspegawaifk', 1)
            ->where('pg.statusenabled', true);

        if (isset($r['lokasi']) && $r['lokasi'] != "" && $r['lokasi'] != "undefined") {
            $data = $data->where('pg.lokasikalibrasifk', '=', $r['lokasi']);
        };
        if (isset($r['jenispegawai']) && $r['jenispegawai'] != "" && $r['jenispegawai'] != "undefined") {
            $data = $data->where('pg.objectjenispegawaifk', '=', $r['jenispegawai']);
        };
        if (isset($r['param_search']) && $r['param_search'] != '') {
            $exp = explode(',', $r['param_search']);
            foreach ($exp as $items) {
                $where[] = [$items, 'ILIKE', '%' . $search . '%'];
            }
            $data = $data->where($where);
        }
        $data = $data->get();


        $result['data'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function produkByMitra(Request $r)
    {
        $search = $r['query'];

        $data = DB::table('mapunittoalat_m as mmp')
            ->select(
                'mmp.id',
                'mmp.namaproduk',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber'
            )
            ->where('mmp.statusenabled', true)
            ->where('mmp.objectmitrafk', $r['idmitra']);

        if (isset($r['id_alat']) && $r['id_alat'] != "" && $r['id_alat'] != "undefined") {
            $data = $data->where('mmp.id', '=', $r['id_alat']);
        };

        if (!empty($r['param_search']) && $search != '') {
            $exp = explode(',', $r['param_search']);
            $data = $data->where(function ($query) use ($exp, $search) {
                foreach ($exp as $item) {
                    $query->orWhere($item, 'ILIKE', '%' . $search . '%');
                }
            });
        }

        $result['data'] = $data->get();
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function alatStandarUlab(Request $r)
    {
        $search = $r['query'];

        $data = DB::table('mapalatstandar_m as mmt')
            ->select(
                'mmt.id',
                'mmt.namaalatstandar',
                'mmt.namamerk',
                'mmt.namatipe',
                'mmt.namaserialnumber'
            )
            ->where('mmt.statusenabled', true)
            ->where('mmt.standarmilikfk', $r['idulab']);

        if (isset($r['id_alat']) && $r['id_alat'] != "" && $r['id_alat'] != "undefined") {
            $data = $data->where('mmt.id', '=', $r['id_alat']);
        };

        if (!empty($r['param_search']) && $search != '') {
            $exp = explode(',', $r['param_search']);
            $data = $data->where(function ($query) use ($exp, $search) {
                foreach ($exp as $item) {
                    $query->orWhere($item, 'ILIKE', '%' . $search . '%');
                }
            });
        }

        $result['data'] = $data->get();
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function fetcUlab(Request $r)
    {
        $search = $r['query'];

        $data = DB::table('mitra_m as mt')
            ->select(
                'mt.id',
                'mt.namaperusahaan',
            )
            ->where('mt.statusenabled', true)
            ->where('mt.islab', true);

        if (!empty($r['param_search']) && $search != '') {
            $exp = explode(',', $r['param_search']);
            $data = $data->where(function ($query) use ($exp, $search) {
                foreach ($exp as $item) {
                    $query->orWhere($item, 'ILIKE', '%' . $search . '%');
                }
            });
        }

        $result['data'] = $data->get();
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }


    public function LayananKajian(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->leftjoin('jenissurkes_m as jm', 'jm.id', '=', 'mtrd.statussurkesfk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.isVendor',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.tanggalpenolakanregis',
                'mtrd.durasikalbrasi',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.paketkalibrasi',
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
                'pk.namapaket',
                'mtr.isregiscustomer',
                'jm.id as jenissurkesfk',
                'jm.jenissurkes',
                'mtrd.namaalatfk',
                'mtr.nomitrafk as idunit',
                DB::raw("
                    CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM mapalattolingkup_t AS mal
                            WHERE mal.objectalatfk = mtrd.namaalatfk
                            AND mal.statusenabled = true
                        )
                        THEN 1 ELSE 0
                    END as mapping_surkes_id
                "),
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtr.norec', $r['norec_pd']);


        if (isset($r['norecdetail']) && $r['norecdetail'] != "" && $r['norecdetail'] != "undefined") {
            $data = $data->where('mtrd.norec', '=', $r['norecdetail']);
        };

        $data = $data->orderByDesc('mmp.namaproduk')->get();

        $detailNorecs = $data->pluck('norec_detail')->filter()->values();
        $fotoMap = collect();

        if ($detailNorecs->count() > 0) {
            $fotoMap = DB::table('mitraregistrasidetailfoto_t')
                ->select('mitraregistrasidetailfk', 'namafile')
                ->whereIn('mitraregistrasidetailfk', $detailNorecs)
                ->where('statusenabled', true)
                ->orderBy('created_at')
                ->get()
                ->groupBy('mitraregistrasidetailfk');
        }

        foreach ($data as $item) {
            $fotoFiles = $fotoMap
                ->get($item->norec_detail, collect())
                ->pluck('namafile')
                ->filter()
                ->values();

            if ($fotoFiles->isEmpty() && !empty($item->namafile)) {
                $fotoFiles = collect([$item->namafile]);
            }

            $item->foto_files = $fotoFiles->values();
        }

        $paket = optional($data->first())->paketkalibrasi;
        $totalDurasi = 0;
        $tanggalSelesai = null;
        if ($paket) {
            $sumPerLingkup = [];
            foreach ($data as $item) {
                $key    = $item->lingkupfk;
                $durasi = (int) $item->durasikalbrasi;
                if (!isset($sumPerLingkup[$key])) {
                    $sumPerLingkup[$key] = 0;
                }
                $sumPerLingkup[$key] += $durasi;
            }
            $totalDurasi = !empty($sumPerLingkup) ? max($sumPerLingkup) : 0;
            $tanggalSelesai = Carbon::today()
                ->addDays($totalDurasi)
                ->format('d-m-Y');
        }


        $result['length'] = count($data);
        $result['detail'] = $data;
        $result['totalDurasi'] = $totalDurasi;
        $result['tanggalSelesai'] = $tanggalSelesai;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    private function queryAlatTersediaKajiUlang($registrasi)
    {
        $norecRegistrasi = $registrasi->norec;

        return DB::table('mapunittoalat_m as alat')
            ->select(
                'alat.id',
                'alat.namaproduk',
                'alat.namamerk',
                'alat.namatipe',
                'alat.namaserialnumber'
            )
            ->where('alat.statusenabled', true)
            ->where('alat.objectmitrafk', $registrasi->nomitrafk)
            ->whereNotExists(function ($query) use ($norecRegistrasi) {
                $query->select(DB::raw(1))
                    ->from('mitraregistrasidetail_t as detail_saat_ini')
                    ->whereColumn('detail_saat_ini.namaalatfk', 'alat.id')
                    ->where('detail_saat_ini.noregistrasifk', $norecRegistrasi);
            })
            ->whereNotExists(function ($query) use ($norecRegistrasi) {
                $query->select(DB::raw(1))
                    ->from('mitraregistrasidetail_t as detail_aktif')
                    ->join('mitraregistrasi_t as registrasi_aktif', 'registrasi_aktif.norec', '=', 'detail_aktif.noregistrasifk')
                    ->whereColumn('detail_aktif.namaalatfk', 'alat.id')
                    ->where('detail_aktif.statusenabled', true)
                    ->where('registrasi_aktif.statusenabled', true)
                    ->where('registrasi_aktif.norec', '<>', $norecRegistrasi)
                    ->where(function ($pekerjaan) {
                        $pekerjaan
                            ->where(function ($kalibrasi) {
                                $kalibrasi->whereRaw("LOWER(COALESCE(registrasi_aktif.jenisorder, '')) = 'kalibrasi'")
                                    ->whereNull('detail_aktif.tglsetujumanagerlembarkerja');
                            })
                            ->orWhere(function ($repair) {
                                $repair->whereRaw("LOWER(COALESCE(registrasi_aktif.jenisorder, '')) = 'repair'")
                                    ->whereNull('detail_aktif.tglsetujumanagerlaporanrepair');
                            });
                    });
            });
    }

    private function isDatabaseTrue($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 't'], true);
    }

    public function alatTersediaKajiUlang(Request $r)
    {
        $norecRegistrasi = $r->input('norec_registrasi');
        $registrasi = DB::table('mitraregistrasi_t as registrasi')
            ->join('mitra_m as unit', 'unit.id', '=', 'registrasi.nomitrafk')
            ->select(
                'registrasi.norec',
                'registrasi.nopendaftaran',
                'registrasi.nomitrafk',
                'registrasi.jenisorder',
                'registrasi.iskaji',
                'unit.namaperusahaan'
            )
            ->where('registrasi.norec', $norecRegistrasi)
            ->where('registrasi.statusenabled', true)
            ->first();

        if (!$registrasi) {
            return $this->respond([], 404, 'Pendaftaran tidak ditemukan.');
        }

        if ($this->isDatabaseTrue($registrasi->iskaji)) {
            return $this->respond([], 400, 'Kaji ulang pendaftaran sudah disimpan.');
        }

        $query = $this->queryAlatTersediaKajiUlang($registrasi);
        $search = trim((string) $r->input('query', ''));

        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($alat) use ($searchTerm) {
                $alat->where('alat.namaproduk', 'ILIKE', $searchTerm)
                    ->orWhere('alat.namamerk', 'ILIKE', $searchTerm)
                    ->orWhere('alat.namatipe', 'ILIKE', $searchTerm)
                    ->orWhere('alat.namaserialnumber', 'ILIKE', $searchTerm);
            });
        }

        $data = $query
            ->orderBy('alat.namaproduk')
            ->orderBy('alat.namaserialnumber')
            ->limit(100)
            ->get();

        return $this->respond([
            'data' => $data,
            'registrasi' => [
                'norec' => $registrasi->norec,
                'nopendaftaran' => $registrasi->nopendaftaran,
                'jenisorder' => $registrasi->jenisorder,
                'unitfk' => $registrasi->nomitrafk,
                'namaperusahaan' => $registrasi->namaperusahaan,
            ],
        ]);
    }

    public function tambahAlatKajiUlang(Request $r)
    {
        DB::beginTransaction();

        try {
            $norecRegistrasi = $r->input('norec_registrasi');
            $alatIds = collect($r->input('alat_ids', []))
                ->map(function ($id) {
                    return trim((string) $id);
                })
                ->filter(function ($id) {
                    return $id !== '';
                })
                ->unique()
                ->values();

            if (empty($norecRegistrasi)) {
                throw new \Exception('Pendaftaran tidak ditemukan.');
            }

            if ($alatIds->isEmpty()) {
                throw new \Exception('Pilih minimal satu alat yang akan ditambahkan.');
            }

            $registrasi = DB::table('mitraregistrasi_t')
                ->where('norec', $norecRegistrasi)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$registrasi) {
                throw new \Exception('Pendaftaran tidak ditemukan atau sudah tidak aktif.');
            }

            if ($this->isDatabaseTrue($registrasi->iskaji)) {
                throw new \Exception('Kaji ulang pendaftaran sudah disimpan sehingga alat tidak dapat ditambahkan.');
            }

            $jenisOrder = strtolower(trim((string) ($registrasi->jenisorder ?? '')));
            if (!in_array($jenisOrder, ['kalibrasi', 'repair'], true)) {
                throw new \Exception('Jenis pendaftaran tidak mendukung penambahan alat dari kaji ulang.');
            }

            DB::table('mapunittoalat_m')
                ->whereIn('id', $alatIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $alatTersedia = $this->queryAlatTersediaKajiUlang($registrasi)
                ->whereIn('alat.id', $alatIds->all())
                ->get();

            $alatTersediaIds = $alatTersedia
                ->pluck('id')
                ->map(function ($id) {
                    return (string) $id;
                });

            $alatTidakTersedia = $alatIds->diff($alatTersediaIds)->values();
            if ($alatTidakTersedia->isNotEmpty()) {
                throw new \Exception('Sebagian alat tidak tersedia, berasal dari unit lain, sudah ada di pendaftaran ini, atau masih dalam pekerjaan yang belum disetujui manager. Muat ulang daftar alat lalu coba kembali.');
            }

            $durasiKalibrasi = null;
            if ($jenisOrder === 'kalibrasi' && !empty($registrasi->paketkalibrasi)) {
                $durasiKalibrasi = DB::table('paketkalibrasi_m')
                    ->where('id', $registrasi->paketkalibrasi)
                    ->where('statusenabled', true)
                    ->value('hari');
            }

            foreach ($alatIds as $alatId) {
                $detail = new MitraRegistrasiDetail();
                $detail->norec = $detail->generateNewId();
                $detail->statusenabled = true;
                $detail->noregistrasifk = $registrasi->norec;
                $detail->namaalatfk = $alatId;
                $detail->iskaji = false;

                if ($durasiKalibrasi !== null) {
                    $detail->durasikalbrasi = $durasiKalibrasi;
                }

                $detail->save();
            }

            DB::commit();

            return $this->respond([
                'jumlah_ditambahkan' => $alatIds->count(),
            ], 201, $alatIds->count() . ' alat berhasil ditambahkan ke pendaftaran ini.');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'error' => $e->getMessage(),
            ], 400, $e->getMessage());
        }
    }

    public function saveKajianUlangItem(Request $r)
    {
        DB::beginTransaction();
        try {
            $detailInfo = DB::table('mitraregistrasidetail_t as mtrd')
                ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
                ->select(
                    'mtrd.namaalatfk',
                    'mtrd.lingkupkalibrasifk',
                    'mtrd.namafile',
                    'mtrd.iskaji',
                    'mtrd.isVendor',
                    'mtr.nomitrafk as objectmitrafk'
                )
                ->where('mtrd.norec', $r->norec)
                ->where('mtrd.statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$detailInfo) {
                throw new \Exception("Detail alat tidak ditemukan atau sudah tidak aktif.");
            }

            if ($this->isDatabaseTrue($detailInfo->iskaji) && $this->isDatabaseTrue($detailInfo->isVendor)) {
                throw new \Exception('Alat sudah dikaji melalui vendor dan tidak dapat dialihkan ke kaji biasa.');
            }

            $isMappedSurkes = $this->isAlatMappedSurkes($detailInfo->namaalatfk);
            $statusSurkes = $isMappedSurkes ? 1 : (int)$r->statussurkes;
            $lingkupKalibrasi = $r->lingkupkalibrasi ?: ($detailInfo->lingkupkalibrasifk ?? null);

            $files = $r->file('fileMitra');
            $existingFotoCount = DB::table('mitraregistrasidetailfoto_t')
                ->where('mitraregistrasidetailfk', $r->norec)
                ->where('statusenabled', true)
                ->count();
            $legacyFile = $detailInfo->namafile ?? null;
            $hasExistingFoto = $existingFotoCount > 0 || !empty($legacyFile);

            if (!$files && !$hasExistingFoto) {
                throw new \Exception("Minimal 1 foto harus diunggah.");
            }
            if (!$files) {
                $files = [];
            }
            if (!is_array($files)) {
                $files = [$files];
            }
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $savedFiles = [];
            $filename = $legacyFile;
            $fotoRows = [];
            foreach ($files ?? [] as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (jpg, jpeg, png, webp).");
                }
                $filename = time() . '_' . Str::random(6) . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('berkas-mitra'), $filename);
                $savedFiles[] = $filename;

                $fotoRows[] = [
                    'norec' => (string) Str::uuid(),
                    'mitraregistrasidetailfk' => $r->norec,
                    'namafile' => $filename,
                    'keterangan' => $r->keterangan ?? null,
                    'statusenabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($fotoRows)) {
                DB::table('mitraregistrasidetailfoto_t')->insert($fotoRows);
            }

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r->norec)
                ->update([
                    'keterangan' => $r->keterangan,
                    // 'tanggalkaji' => $r->tanggalKajian,
                    'lokasikajifk' => $r->lokasikalibrasi ?? null,
                    'lokasirepairfk' => $r->lokasirepairfk ?? null,
                    'lingkupkalibrasifk' => $lingkupKalibrasi,
                    'penyeliateknikfk' => $r->penyeliateknik,
                    'pelaksanateknikfk' => $r->pelaksana,
                    'statussurkesfk' => $statusSurkes,
                    'namamanager' => $r->manager,
                    'namaasman' => $r->namaasman,
                    'durasikalbrasi' => $r->durasikalbrasi ?? null,
                    'iskaji' => true,
                    'isVendor' => null,
                    'vendorkalibrasifk' => null,
                    // 'namafile' => $filename,
                    'updated_at' => now(),
                    'statusorderpelaksana' => 0,
                    'statusorderpenyelia' => 0,
                ]);

            if ((int)$statusSurkes === 1) {
                $this->ensureMappingAlatSurkes(
                    $detailInfo->namaalatfk,
                    $detailInfo->objectmitrafk,
                    $lingkupKalibrasi
                );
            }

            $transMessage = "Simpan Kajian Ulang Alat Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => 'aditwiran19@gmail.com',
                    "namafile" => $savedFiles[0] ?? $filename,
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = $e->getMessage();
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    private function isAlatMappedSurkes($objectAlatFk)
    {
        if (empty($objectAlatFk)) {
            return false;
        }

        return DB::table('mapalattolingkup_t')
            ->where('objectalatfk', $objectAlatFk)
            ->where('statusenabled', true)
            ->exists();
    }

    private function ensureMappingAlatSurkes($objectAlatFk, $objectMitraFk, $objectLingkupFk)
    {
        if (empty($objectAlatFk) || empty($objectMitraFk) || empty($objectLingkupFk)) {
            return;
        }

        $alatExists = DB::table('mapunittoalat_m')
            ->where('id', $objectAlatFk)
            ->where('statusenabled', true)
            ->exists();

        if (!$alatExists) {
            return;
        }

        $existing = DB::table('mapalattolingkup_t')
            ->where('objectalatfk', $objectAlatFk)
            ->first();

        if ($existing) {
            DB::table('mapalattolingkup_t')
                ->where('id', $existing->id)
                ->update([
                    'objectmitrafk' => $objectMitraFk,
                    'objectlingkupfk' => $objectLingkupFk,
                    'statusenabled' => true,
                    'updated_at' => now(),
                ]);
            return;
        }

        DB::table('mapalattolingkup_t')->insert([
            'objectalatfk' => $objectAlatFk,
            'objectmitrafk' => $objectMitraFk,
            'objectlingkupfk' => $objectLingkupFk,
            'statusenabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveKajianUlangItemVendor(Request $r)
    {
        DB::beginTransaction();
        try {
            $files = $r->file('fileMitra');
            $detailInfo = DB::table('mitraregistrasidetail_t')
                ->select('namafile', 'iskaji', 'isVendor')
                ->where('norec', $r->norec)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$detailInfo) {
                throw new \Exception('Detail alat tidak ditemukan atau sudah tidak aktif.');
            }

            if ($this->isDatabaseTrue($detailInfo->iskaji) && !$this->isDatabaseTrue($detailInfo->isVendor)) {
                throw new \Exception('Alat sudah dikaji biasa dan tidak dapat dialihkan ke kaji vendor.');
            }

            $existingFotoCount = DB::table('mitraregistrasidetailfoto_t')
                ->where('mitraregistrasidetailfk', $r->norec)
                ->where('statusenabled', true)
                ->count();
            $legacyFile = $detailInfo->namafile ?? null;
            $hasExistingFoto = $existingFotoCount > 0 || !empty($legacyFile);

            if (!$files && !$hasExistingFoto) {
                throw new \Exception("Minimal 1 foto harus diunggah.");
            }
            if (!$files) {
                $files = [];
            }
            if (!is_array($files)) {
                $files = [$files];
            }
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $savedFiles = [];
            $filename = $legacyFile;
            $fotoRows = [];
            foreach ($files ?? [] as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (jpg, jpeg, png, webp).");
                }
                $filename = time() . '_' . Str::random(6) . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('berkas-mitra'), $filename);
                $savedFiles[] = $filename;

                $fotoRows[] = [
                    'norec' => (string) Str::uuid(),
                    'mitraregistrasidetailfk' => $r->norec,
                    'namafile' => $filename,
                    'keterangan' => $r->keterangan ?? null,
                    'statusenabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($fotoRows)) {
                DB::table('mitraregistrasidetailfoto_t')->insert($fotoRows);
            }

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r->norec)
                ->update([
                    'keterangan' => $r->keterangan,
                    'lokasikajifk' => $r->lokasikalibrasi ?? null,
                    'lokasirepairfk' => $r->lokasirepairfk ?? null,
                    'lingkupkalibrasifk' => $r->lingkupkalibrasi,
                    'namamanager' => $r->manager,
                    'namaasman' => $r->namaasman,
                    'durasikalbrasi' => null,
                    'iskaji' => true,
                    'statussurkesfk' => 2,
                    'updated_at' => now(),
                    'vendorkalibrasifk' => $r->vendorkalibrasi ?? null,
                    'isVendor' => true,
                ]);

            $transMessage = "Simpan Kajian Ulang Alat Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => 'aditwiran19@gmail.com',
                    "namafile" => $savedFiles[0] ?? $filename,
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = $e->getMessage();
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function saveKajiUlang(Request $r)
    {
        DB::beginTransaction();
        try {
            $PD = $r['kajian'];
            $registrasi = DB::table('mitraregistrasi_t')
                ->where('norec', $PD['norec'] ?? null)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$registrasi) {
                throw new \Exception('Pendaftaran tidak ditemukan atau sudah tidak aktif.');
            }

            $detailAktif = DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $registrasi->norec)
                ->where('statusenabled', true);

            $jumlahAlat = (clone $detailAktif)->count();
            $jumlahBelumKaji = (clone $detailAktif)
                ->where(function ($query) {
                    $query->whereNull('iskaji')
                        ->orWhere('iskaji', false);
                })
                ->count();

            if ($jumlahAlat === 0) {
                throw new \Exception('Pendaftaran harus memiliki minimal satu alat aktif sebelum kaji ulang disimpan.');
            }

            if ($jumlahBelumKaji > 0) {
                throw new \Exception("Masih ada {$jumlahBelumKaji} alat yang belum dikaji. Kaji setiap alat atau keluarkan alat tersebut dari pendaftaran.");
            }

            DB::table('mitraregistrasi_t')
                ->where('norec', $registrasi->norec)
                ->update([
                    'iskaji' => true,
                    'tglkajiulang' => now(),
                    'petugaskaji' => $this->getPegawaiId(),
                ]);

            $transMessage = "Simpan Kajian Ulang Sukses";
            DB::commit();

            $regis = DB::table('mitraregistrasi_t')
                ->where('norec', $PD['norec'])
                ->first();

            $detail = DB::table('mitraregistrasidetail_t')->where('noregistrasifk', $regis->norec)->first();

            $nopendaftaran = $regis->nopendaftaran ?? '-';
            $asmanId = $detail->namaasman ?? null;
            if ($asmanId) {
                $asman = DB::table('pegawai_m')->where('namalengkap', $asmanId)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';

                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Telah dilakukan kaji ulang oleh Admin atas Pendaftaran dengan No Pendaftaran : *$nopendaftaran*\n"
                        . "Silahkan cek dan review untuk diproses ketahap selanjutnya."
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $customer = DB::table('users')->where('id', $regis->customerfk)->first();
            $nohpCustomer = $customer->nowa ?? ($regis->nohppenanggungjawab ?? null);
            $usernamCustomer = $customer->email ?? null;
            $namaCustomer = $customer->name ?? ($regis->namapenanggungjawab ?? 'Customer');

            $jenisOrder = $regis->jenisorder ?? '';
            if (strtolower($jenisOrder) == 'kalibrasi') {
                $judulTandaTerima = "Tanda Terima Kalibrasi";
            } elseif (strtolower($jenisOrder) == 'repair') {
                $judulTandaTerima = "Tanda Terima Repair";
            } else {
                $judulTandaTerima = "Tanda Terima";
            }

            $username = $usernamCustomer ?? 'Mahzumi';
            $profileId = $customer->kdprofile ?? 1;
            $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
            $link = url('/service/registrasi/cetak-tanda-terima') . '?pdf=true'
                . '&norec=' . $PD['norec']
                . '&user=' . urlencode($username)
                . '&kdprofile=' . $profileId
                . '&token=' . $token;
            $shortLink = $this->shortLink($link);
            $playStoreLink = 'https://play.google.com/store/apps/details?id=id.ulabumro.mobile';
            $pesan = "Yth. $namaCustomer,\n\n"
                . "Dokumen *$judulTandaTerima* alat Anda sudah tersedia dan dapat dilihat serta dicetak melalui aplikasi U-LAB Mobile.\n"
                . "Jika belum memiliki aplikasinya, silakan unduh melalui Play Store:\n"
                . "$playStoreLink\n\n"
                . "Dokumen juga dapat dilihat melalui web seperti sebelumnya pada tautan berikut:\n"
                . "$shortLink\n\n";
            $unit = '-';
            if ($customer && $customer->mitrafk) {
                $mitra = DB::table('mitra_m')
                    ->whereRaw('CAST(id AS INTEGER) = ?', [$customer->mitrafk])
                    ->first();
                $unit = $mitra->namaperusahaan ?? '-';
            }
            $pesan .= "Terima kasih Yth Bpk/Ibu $namaCustomer dari $unit\n"
                . "atas kepercayaan Anda kepada Tim U-LAB\n"
                . "Laboratorium Kalibrasi Multilokasi PT PLN Nusantara Power terakreditasi KAN.\n\n"
                . "Bagi kami, setiap alat ukur bukan hanya sekadar perangkat, tapi bagian penting dari produktivitas dan keselamatan kerja Anda.\n\n"
                . "Dengan akurasi, kecepatan, dan layanan 24/7, kami hadir sebagai mitra strategis untuk industri Anda.\n\n"
                . "🌐 Layanan Multilokasi (Jakarta & Gresik)\n"
                . "🕐 Operasional 24/7\n"
                . "🔧 Kalibrasi & Repair dalam satu pintu\n"
                . "📄 Sertifikat ISO/IEC 17025\n\n"
                . "Karena kepuasan Anda adalah tolok ukur keberhasilan kami.\n"
                . "Bersama U-LAB, akurasi & presisi bukan lagi sekadar janji — tapi bukti.\n\n"
                . "Pagi cerah langit membiru,\n"
                . "Langkah mantap menuju tujuan.\n"
                . "Mari bekerja penuh rindu,\n"
                . "Demi pelayanan dan kepuasan pelanggan.\n\n"
                . "Salam,\n"
                . "U-LAB ! Cepat, Tepat, Akurat 💯";
            $this->kirimWhatsappNotifikasi($nohpCustomer, $pesan);

            $this->sendMobilePushToUser(
                $regis->customerfk ?? ($customer->id ?? null),
                $judulTandaTerima . ' Tersedia',
                $judulTandaTerima . " untuk No Pendaftaran $nopendaftaran sudah tersedia. Ketuk notifikasi untuk membuka pesanan dan mencetaknya.",
                [
                    'type' => 'tanda_terima_tersedia',
                    'screen' => 'history_screen',
                    'target_screen' => 'history_screen',
                    'action' => 'print_tanda_terima',
                    'norec_registrasi' => (string) ($regis->norec ?? $PD['norec'] ?? ''),
                    'nopendaftaran' => (string) ($nopendaftaran ?? ''),
                    'jenis_order' => (string) ($jenisOrder ?? ''),
                ],
                'saveKajiUlang',
                [
                    'customerfk' => $regis->customerfk ?? null,
                    'nomitrafk' => $regis->nomitrafk ?? null,
                    'norec_registrasi' => $regis->norec ?? $PD['norec'] ?? null,
                    'nopendaftaran' => $nopendaftaran ?? null,
                ]
            );

            $result = [
                "status" => 200,
                "result" => [
                    "as" => 'aditwiran19@gmail.com',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = $e->getMessage();
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => [
                    'error' => $e->getMessage(),
                ]
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }


    public function shortLink($longUrl)
    {
        $response = Http::get('https://tinyurl.com/api-create.php?url=' . urlencode($longUrl));
        if ($response->successful()) {
            return trim($response->body());
        }
        return $longUrl;
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
        return $token;
    }

    private function resolveMobileMitraFk($regis, $customer = null)
    {
        if ($regis && !empty($regis->nomitrafk)) {
            return trim((string) $regis->nomitrafk);
        }

        if ($customer && !empty($customer->mitrafk)) {
            return trim((string) $customer->mitrafk);
        }

        return null;
    }

    private function sendMobilePushToMitra($mitraFkMobile, string $title, string $body, array $data, string $source, array $logContext = [])
    {
        if (empty($mitraFkMobile)) {
            Log::warning("FCM {$source} dilewati karena mitrafk kosong", $logContext);
            return;
        }

        try {
            $pushResult = app(\App\Services\MobilePushNotificationService::class)->sendToMitra(
                $mitraFkMobile,
                $title,
                $body,
                array_merge($data, [
                    'source' => $source,
                ])
            );

            Log::info("FCM {$source} berhasil diproses", array_merge($logContext, [
                'mitrafk' => $mitraFkMobile,
                'push_result' => $pushResult,
            ]));
        } catch (\Throwable $pushError) {
            Log::error("Gagal kirim FCM {$source}", array_merge($logContext, [
                'message' => $pushError->getMessage(),
                'mitrafk' => $mitraFkMobile,
            ]));
        }
    }

    private function sendMobilePushToUser($userId, string $title, string $body, array $data, string $source, array $logContext = [])
    {
        if (empty($userId)) {
            Log::warning("FCM {$source} dilewati karena customerfk kosong", $logContext);
            return;
        }

        try {
            $pushResult = app(\App\Services\MobilePushNotificationService::class)->sendToUser(
                $userId,
                $title,
                $body,
                array_merge($data, [
                    'source' => $source,
                ])
            );

            Log::info("FCM {$source} berhasil diproses", array_merge($logContext, [
                'user_id' => $userId,
                'push_result' => $pushResult,
            ]));
        } catch (\Throwable $pushError) {
            Log::error("Gagal kirim FCM {$source}", array_merge($logContext, [
                'message' => $pushError->getMessage(),
                'user_id' => $userId,
            ]));
        }
    }

    public function saveBatalRegis(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_NewMitra = $request['mitraregis'];
            $dataMitra = DB::table('mitraregistrasi_t')
                ->where('norec', $r_NewMitra['norecregis'])
                ->where('statusenabled', true)
                ->first();

            if (!$dataMitra) {
                throw new \Exception("Data registrasi tidak ditemukan atau sudah tidak aktif");
            }

            $norecMitra = $dataMitra->norec;
            DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $norecMitra)
                ->update(['statusenabled' => false]);

            DB::table('mitraregistrasi_t')
                ->where('norec', $norecMitra)
                ->update([
                    'statusenabled' => false,
                    'tanggalpembatalan' => $r_NewMitra['tanggalpembatalan'] ?? null,
                    'alasanpembatalan' => $r_NewMitra['alasanpembatalan'] ?? null
                ]);

            $message = 'Berhasil Batal Registrasi';

            DB::commit();

            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                )
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => "Something Went Wrong",
                "result"  => $e->getMessage() . ' | Line: ' . $e->getLine()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function savePenolakanALat(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_NewMitra = $request['mitraregis'];
            $detail = DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_NewMitra['norecregis'] ?? null)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$detail) {
                throw new \Exception('Alat tidak ditemukan atau sudah dikeluarkan dari pendaftaran.');
            }

            if ($this->isDatabaseTrue($detail->iskaji)) {
                throw new \Exception('Alat yang sudah dikaji tidak dapat dikeluarkan dari pendaftaran.');
            }

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $detail->norec)
                ->where('statusenabled', true)
                ->update([
                    'statusenabled' => false,
                    'iskaji' => true,
                    'updated_at' => now(),
                    'statusorderpelaksana' => 0,
                    'statusorderpenyelia' => 0,
                    'tanggalpenolakanregis' => $r_NewMitra['tanggalpenolakanregis'] ?? null,
                    'alasanpenolakanregis' => $r_NewMitra['alasanpenolakanregis'] ?? null
                ]);

            $message = 'Alat berhasil dikeluarkan dari pendaftaran';

            DB::commit();

            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                )
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $e->getMessage(),
                "result"  => $e->getMessage()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function saveKonfirmasiPendaftaran(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_NewMitra = $request['mitrakonfirmasi'];
            DB::table('mitraregistrasi_t')
                ->where('norec', $r_NewMitra['norecregis'])
                ->update([
                    'tanggalkonfirmasipendaftaran' => $r_NewMitra['tanggalkonfirmasi'] ?? null,
                    'ttdpenanggungjawab' => $r_NewMitra['datattd'] ?? null,
                    'namapenanggungjawab' => $r_NewMitra['namapenanggungjawab'],
                ]);

            $message = 'Berhasil Konfirmasi Pendaftaran';

            DB::commit();

            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                )
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => "Something Went Wrong",
                "result"  => $e->getMessage() . ' | Line: ' . $e->getLine()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function cetakTandaTerima(Request $r)
    {

        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $res['identitas'] =  $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->leftJoin('lokasikalibrasi_m as lk', function ($join) {
                $join->on('lk.id', '=', DB::raw('COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair)'));
            })
            ->select(
                'jb.id as idjabatan',
                'jb.namajabatanulab as namajabanpetugaskaji',
                'mtr.norec',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mt.namaperusahaan',
                'pg.id as petugaskajifk',
                'pg.namalengkap as namapetugaskaji',
                'pg.nid as nidnamapetugaskaji',
                'lk.lokasi',
                'mtr.jabatanpenanggungjawab',
                'mtr.namapenanggungjawab',
                'mtr.jabatanpenerima',
                'mtr.penerima',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $r['norec'])
            ->first();

        $res['alat'] = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->leftJoin('lokasikalibrasi_m as lk', function ($join) {
                $join->on('lk.id', '=', DB::raw('COALESCE(mtrd.lokasikajifk, mtrd.lokasirepairfk)'));
            })
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('mitraregistrasidetailfoto_t as mtrdf', function ($join) {
                $join->on('mtrdf.mitraregistrasidetailfk', '=', 'mtrd.norec')
                    ->where('mtrdf.statusenabled', true);
            })
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.keterangan',
                'mtrd.tanggalpenolakanregis',
                'mtrd.alasanpenolakanregis',
                'mtrd.namafile',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                DB::raw("COALESCE(json_agg(mtrdf.namafile) FILTER (WHERE mtrdf.namafile IS NOT NULL), '[]') as foto_files")
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtr.norec', $r['norec'])
            ->groupBy(
                'mtr.norec',
                'mtrd.norec',
                'mtrd.iskaji',
                'mtrd.keterangan',
                'mtrd.tanggalpenolakanregis',
                'mtrd.alasanpenolakanregis',
                'mtrd.namafile',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'lk.id',
                'lk.lokasi',
                'lp.id',
                'lp.lingkupkalibrasi'
            )
            ->orderByDesc('mmp.namaproduk')
            ->get();

        $res['pdf']  = $r['pdf'];
        $res['ttdPetugas'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            $res['identitas']->namajabanpetugaskaji . "\n" . $res['identitas']->namapetugaskaji . "\n" .  $res['identitas']->nidnamapetugaskaji . "\n" . 'nosurat: FMMO-163-14.4.3.b-74.2'
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdPelanggan'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            $res['identitas']->jabatanpenerima . "\n" . $res['identitas']->penerima . "\n" . 'nosurat: FMMO-163-14.4.3.b-74.2'
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));

        $blade = 'report.registrasi.tanda-terima';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                )
            );
            return $pdf->stream();
        }
        if (isset($r['storage'])) {
            $res['storage']  = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                )
            );
            return $pdf;
        }

        return view(
            $blade,
            compact('profile', 'pageWidth', 'print', 'res')
        );
    }

    public function cetakPermintaanKalibrasi(Request $r)
    {

        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $res['identitas'] =  $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->leftJoin('lokasikalibrasi_m as lk2', 'lk2.id', '=', 'mtr.lokasirepair')
            ->select(
                'jb.id as idjabatan',
                'jb.namajabatanulab as namajabanpetugaskaji',
                'mtr.norec',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.rentangUkur',
                'mtr.rentangUkurketPermintaanPelanggan',
                'mt.namaperusahaan',
                'mt.alamatktr',
                'mt.nohp',
                'mt.email',
                'pg.id as petugaskajifk',
                'pg.namalengkap as namapetugaskaji',
                'pg.nid as nidnamapetugaskaji',
                'mtr.jabatanpenerima',
                'mtr.penerima',
                'lk.lokasi',
                'lk2.lokasi as lokasirepair',
                'mtr.jabatanpenanggungjawab',
                'mtr.namapenanggungjawab',
                'mtr.ttdpenanggungjawab',
                'mtr.jenisorder',
                'mtr.nohppenanggungjawab',
                'mtr.statuspendaftaran',
                'mtr.tanggalserahdari',
                'mtr.tanggalserahsd',
                'mtr.alasanditangguhkan',
                'mtr.alasanditangguhkanlain',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $r['norec'])
            ->first();

        $res['alat'] =  $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.asmanveriffk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.keterangan',
                'mtrd.namafile',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pk.id as idpaket',
                'pk.namapaket',
                'pk.hari as hari_paket',
                'pg.id as asmanfk',
                'pg.namalengkap as asamanverifikasi',
                'pg.nid as nidasman',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtr.norec', $r['norec'])
            ->orderByDesc('mmp.namaproduk')
            ->get();

        $alatPerRegistrasi = [];
        foreach ($data as $item) {
            $alatPerRegistrasi[$item->norec][] = $item;
        }

        foreach ($data as $d) {
            $alatList = isset($alatPerRegistrasi[$d->norec]) ? $alatPerRegistrasi[$d->norec] : [];
            $countPerLingkup = [];
            foreach ($alatList as $alat) {
                $lingkup = $alat->lingkupfk;
                if (!$lingkup) continue;
                if (!isset($countPerLingkup[$lingkup])) {
                    $countPerLingkup[$lingkup] = 0;
                }
                $countPerLingkup[$lingkup]++;
            }
            $maxAlat = 0;
            foreach ($countPerLingkup as $total) {
                if ($total > $maxAlat) $maxAlat = $total;
            }
            $hariPaket = $d->hari_paket ?? 0;
            $totalDurasi = $maxAlat * $hariPaket;
            $d->totalDurasi = $totalDurasi;
            $tglDasar = $d->tglverifasman ?? \Carbon\Carbon::now()->format('Y-m-d');
            if ($totalDurasi > 0) {
                $d->tanggalSelesai = \Carbon\Carbon::parse($tglDasar)
                    ->addDays($totalDurasi)
                    ->format('d-m-Y');
            } else {
                $d->tanggalSelesai = null;
            }
        }

        $res['pdf']  = $r['pdf'];
        $res['ttdAsman'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            'Asman NMW' . "\n" . $res['alat'][0]->asamanverifikasi . "\n" .  $res['alat'][0]->nidasman . "\n" . 'nosurat: FMMO-163-14.4.3.b-71.1'
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdPetugas'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            $res['identitas']->namajabanpetugaskaji . "\n" . $res['identitas']->namapetugaskaji . "\n" .  $res['identitas']->nidnamapetugaskaji . "\n" . 'nosurat: FMMO-163-14.4.3.b-71.1'
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdPelanggan'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            $res['identitas']->jabatanpenerima . "\n" . $res['identitas']->namapenanggungjawab . "\n" . 'nosurat: FMMO-163-14.4.3.b-71.1'
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));


        $blade = 'report.registrasi.permintaan-kalibrasi';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                )
            );
            return $pdf->stream();
        }
        if (isset($r['storage'])) {
            $res['storage']  = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->loadView(
                $blade,
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                )
            );
            return $pdf;
        }

        return view(
            $blade,
            compact('profile', 'pageWidth', 'print', 'res')
        );
    }

    public function getVerifAlatCustomer(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->leftjoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtr.lokasirepair')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.alasanpenolakanregis',
                'mtrd.tanggalpenolakanregis',
                'mmp.namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mt.namaperusahaan',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lk1.id as lokasirepairfk',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pk.id as idpaket',
                'pk.namapaket',
                'pk.hari as hari_paket'
            )
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.norec', $r['norec_pd']);

        $data = $data->orderByDesc('mmp.namaproduk')->get();

        $result['length'] = count($data);
        $result['detail'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function saveVerifikasiRegisCustomer(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_NewMitra = $request['verifregistrasi'];

            $registrasiNomor = DB::table('mitraregistrasi_t')
                ->select(
                    'norec',
                    'jenisorder',
                    'tglregistrasi',
                    'lokasikalibrasi',
                    'lokasirepair',
                    'nopendaftaran'
                )
                ->where('norec', $r_NewMitra['norecregis'])
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$registrasiNomor) {
                throw new \Exception('Registrasi aktif tidak ditemukan.');
            }

            $jenisOrder = strtolower(trim((string) $registrasiNomor->jenisorder));
            if (!in_array($jenisOrder, ['kalibrasi', 'repair'], true)) {
                throw new \Exception('Jenis order registrasi tidak valid.');
            }

            $kolomLokasi = $jenisOrder === 'repair' ? 'lokasirepair' : 'lokasikalibrasi';
            $lokasi = $registrasiNomor->{$kolomLokasi};
            $lokasiRequest = $r_NewMitra[$kolomLokasi] ?? null;

            if (
                $lokasiRequest !== null
                && $lokasiRequest !== ''
                && (int) $lokasiRequest !== (int) $lokasi
            ) {
                throw new \Exception('Lokasi verifikasi tidak sesuai dengan lokasi registrasi.');
            }

            if (!empty($registrasiNomor->nopendaftaran)) {
                $this->validateNomorPendaftaran(
                    $registrasiNomor->nopendaftaran,
                    $lokasi,
                    $jenisOrder,
                    $registrasiNomor->tglregistrasi
                );
                $nopendaftaran = $registrasiNomor->nopendaftaran;
            } else {
                $nopendaftaran = $this->generateNomorPendaftaran(
                    $lokasi,
                    $jenisOrder,
                    $registrasiNomor->tglregistrasi
                );
            }

            DB::table('mitraregistrasi_t')
                ->where('norec', $r_NewMitra['norecregis'])
                ->update([
                    'tanggalverifregiscustomer' => $r_NewMitra['tanggalverifregiscustomer'] ?? null,
                    'catatancustomer' => $r_NewMitra['catatancustomer'] ?? null,
                    'statuspendaftaran' => $r_NewMitra['statuspendaftaran'] ?? null,
                    'tanggalserahdari' => $r_NewMitra['tanggalserahdari'] ?? null,
                    'tanggalserahsd' => $r_NewMitra['tanggalserahsd'] ?? null,
                    'alasanditangguhkan' => $r_NewMitra['alasanditangguhkan'] ?? null,
                    'alasanditangguhkanlain' => $r_NewMitra['alasanditangguhkanlain'] ?? null,
                    'nopendaftaran' => $nopendaftaran,
                    'verifregiscustomer' => true,
                ]);

            $message = 'Berhasil Verifikasi Registrasi';

            DB::commit();
            $regis = DB::table('mitraregistrasi_t')
                ->where('norec', $r_NewMitra['norecregis'])
                ->first();
            $customer = DB::table('users')->where('id', $regis->customerfk)->first();
            $nohpCustomer = $customer->nowa ?? null;
            $namaCustomer = $customer->name ?? '-';
            $catatanAdmin = $regis->catatancustomer ?? '-';
            $alatList = DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $regis->norec)
                ->get();
            $alatRows = $alatList->isNotEmpty()
                ? DB::table('mapunittoalat_m')
                ->whereIn('id', $alatList->pluck('namaalatfk')->filter(function ($id) {
                    return $id !== null && $id !== '';
                })->unique()->values())
                ->get()
                ->keyBy('id')
                : collect();

            $pesanAlat = "";
            $i = 1;
            foreach ($alatList as $alat) {
                $row = $alatRows->get($alat->namaalatfk);
                if ($row) {
                    $pesanAlat .= $i++ . '. ' . ($row->namaproduk ?? 'Alat Tidak Dikenal')
                        . ' | Merk: ' . ($row->namamerk ?? '-')
                        . ' | Tipe: ' . ($row->namatipe ?? '-')
                        . ' | SN: ' . ($row->namaserialnumber ?? '-') . "\n";
                }
            }

            if ($nohpCustomer) {
                $pesan =
                    "Yth. {$namaCustomer},\n\n"
                    . "Registrasi alat Anda telah diverifikasi oleh admin.\n"
                    . "Nomor Pendaftaran: *{$nopendaftaran}*\n"
                    . "Catatan dari Admin: {$catatanAdmin}\n\n"
                    . "Rincian alat Anda:\n{$pesanAlat}\n"
                    . "Untuk memantau perkembangan alat Anda, silakan cek secara berkala pada:\nhttps://ulabumro.id/\n\n";

                $unit = '-';
                if ($customer && $customer->mitrafk) {
                    $mitra = DB::table('mitra_m')
                        ->whereRaw('CAST(id AS INTEGER) = ?', [$customer->mitrafk])
                        ->first();
                    $unit = $mitra->namaperusahaan ?? '-';
                }
                $pesan .= "Terima kasih Yth Bpk/Ibu $namaCustomer dari $unit\n"
                    . "atas kepercayaan Anda kepada Tim U-LAB\n"
                    . "Laboratorium Kalibrasi Multilokasi PT PLN Nusantara Power terakreditasi KAN.\n\n"
                    . "Bagi kami, setiap alat ukur bukan hanya sekadar perangkat, tapi bagian penting dari produktivitas dan keselamatan kerja Anda.\n\n"
                    . "Dengan akurasi, kecepatan, dan layanan 24/7, kami hadir sebagai mitra strategis untuk industri Anda.\n\n"
                    . "🌐 Layanan Multilokasi (Jakarta & Gresik)\n"
                    . "🕐 Operasional 24/7\n"
                    . "🔧 Kalibrasi & Repair dalam satu pintu\n"
                    . "📄 Sertifikat ISO/IEC 17025\n\n"
                    . "Karena kepuasan Anda adalah tolok ukur keberhasilan kami.\n"
                    . "Bersama U-LAB, akurasi & presisi bukan lagi sekadar janji — tapi bukti.\n\n"
                    . "Pagi cerah langit membiru,\n"
                    . "Langkah mantap menuju tujuan.\n"
                    . "Mari bekerja penuh rindu,\n"
                    . "Demi pelayanan dan kepuasan pelanggan.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpCustomer, $pesan);
            }

            $mitraFkMobile = $this->resolveMobileMitraFk($regis, $customer);
            $this->sendMobilePushToMitra(
                $mitraFkMobile,
                'Registrasi Diverifikasi',
                "Registrasi Anda sudah diverifikasi. No Pendaftaran $nopendaftaran telah diterbitkan.",
                [
                    'type' => 'registrasi_customer_diverifikasi',
                    'screen' => 'history_screen',
                    'target_screen' => 'history_screen',
                    'action' => 'open_registration_history',
                    'norec_registrasi' => (string) ($regis->norec ?? $r_NewMitra['norecregis'] ?? ''),
                    'nopendaftaran' => (string) ($nopendaftaran ?? ''),
                    'statuspendaftaran' => (string) ($regis->statuspendaftaran ?? $r_NewMitra['statuspendaftaran'] ?? ''),
                    'catatan_admin' => (string) ($catatanAdmin ?? ''),
                ],
                'saveVerifikasiRegisCustomer',
                [
                    'customerfk' => $regis->customerfk ?? null,
                    'nomitrafk' => $regis->nomitrafk ?? null,
                    'norec_registrasi' => $regis->norec ?? $r_NewMitra['norecregis'] ?? null,
                    'nopendaftaran' => $nopendaftaran ?? null,
                ]
            );

            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                )
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => "Something Went Wrong",
                "result"  => $e->getMessage() . ' | Line: ' . $e->getLine()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
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
            $secret_key = config('whatsapp.secret_key');
            if (empty($token) || empty($secret_key)) {
                Log::warning('WA tidak dikirim: token atau secret Wablas kosong', [
                    'phone' => $nomor,
                ]);
                return [
                    'status' => false,
                    'message' => 'Token atau secret Wablas kosong',
                ];
            }
            $response = \App\Helpers\WhatsAppHelper::send($nomor, $pesan);
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

    public function cetakAms(Request $request)
    {
        $data = DB::table('mitraregistrasi_t')
            ->where('norec', $request['norecregis'])
            ->first();

        if (!$data || !$data->filecustomerams) {
            abort(404, 'Data AMS atau file tidak ditemukan');
        }

        $filename = basename($data->filecustomerams);
        $filepath = $this->publicFileUrl('berkas-customer', $filename);

        return view('report.customer.view-pdf', compact('filepath', 'data'));
    }

    public function downloadToolsCustomer(Request $request)
    {
        $data = DB::table('mitraregistrasi_t')
            ->where('norec', $request['norecregis'])
            ->first();

        if (!$data || !$data->filecustomertools) {
            abort(404, 'Data AMS atau file tidak ditemukan');
        }

        $filename = basename($data->filecustomertools);

        $pathbundle = 'berkas-customer/' . $filename;
        $name = $filename;
        $path =  public_path($pathbundle);
        if (File::exists($path)) {
            $file = File::get($path);

            $type = File::mimeType($path);

            $response = response()->make($file, 200);
            $response->header("Content-Type", $type)
                ->header('Content-disposition', 'attachment; filename="' . $name . '"');
            return $response;
        } else {
            return '
            <script language="javascript">
                window.alert("Tidak ada data.");
                window.close()
            </script>';
        }
    }

    public function cetakBarcodeOrder(Request $r)
    {
        $profile = $this->profile();
        $print = false;
        $customWidth  = 113.39;
        $customHeight = 85.04;

        $res['alat'] = DB::table('mitraregistrasi_t as mtr')
            ->leftJoin('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
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
            ->select(
                'mtr.norec',
                'mtr.tglkajiulang',
                'mtrd.norec as norec_detail',
                'mtrd.noorderalat',
                DB::raw("COALESCE(mmps.id, mmp.id) as idalat"),
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'mtr.jenisorder',
                DB::raw("
            CASE
                WHEN lower(coalesce(mtr.jenisorder,'')) = 'repair' THEN mtr.lokasirepair
                ELSE mtr.lokasikalibrasi
            END as lokasi_insitu
        ")
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.norec', $r['norec'])
            ->get();


        $res['pdf']  = $r['pdf'];
        $blade = 'report.registrasi.barcodeorder';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');

            // setPaper custom size (pt)
            $pdf->setPaper([0, 0, $customWidth, $customHeight]);

            $pdf->loadView($blade, compact('profile', 'print', 'res'));
            return $pdf->stream();
        }

        if (isset($r['storage'])) {
            $res['storage'] = true;

            $pdf = App::make('dompdf.wrapper');
            $pdf->setPaper([0, 0, $customWidth, $customHeight]);
            $pdf->loadView($blade, compact('profile', 'print', 'res'));

            return $pdf;
        }

        return view($blade, compact('profile', 'print', 'res'));
    }

    public function saveSelesaiTerima(Request $request)
    {
        DB::beginTransaction();
        try {
            $noreg = $request->input('norec');
            $ids   = (array) $request->input('detailalatterima', []);

            $versiNext = (int) DB::table('mitraregistrasi_terimah_t')
                ->where('noregistrasifk', $noreg)
                ->max('versi');
            $versiNext = $versiNext + 1;

            $genHeader = new MitraRegistrasiTerima();
            $model = new MitraRegistrasiTerimaD();
            $norecHeader = $genHeader->generateNewId();

            DB::table('mitraregistrasi_terimah_t')->insert([
                'norec'            => $norecHeader,
                'noregistrasifk'   => $noreg,
                'versi'            => $versiNext,
                'tanggalterima'    => $request->input('tanggalselesaiterima'),
                'tempatterima'     => $request->input('tempatterima'),
                'penerima'         => $request->input('penerima'),
                'jabatanpenerima'  => $request->input('jabatanpenerima'),
                'petugasterima'    => $request->input('petugasterima'),
                'jabatanpetugas'   => $request->input('jabatanpetugas'),
                'statusenabled'    => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            if (!empty($ids)) {
                $rows = [];
                $fotoRows = [];
                $existing = DB::table('mitraregistrasi_terimad_t')
                    ->where('noregistrasiterimahfk', $norecHeader)
                    ->whereIn('detailalatfk', $ids)
                    ->pluck('detailalatfk')
                    ->toArray();

                $newIds = array_diff($ids, $existing);

                foreach ($newIds as $id) {
                    $filenameFirst = null;
                    $norecDetail = $model->generateNewId();
                    if ($request->hasFile("files.$id")) {
                        $files = $request->file("files.$id");
                        if (!is_array($files)) {
                            $files = [$files];
                        }

                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                        foreach ($files as $file) {
                            if (!$file) continue;

                            $extension = strtolower($file->getClientOriginalExtension());
                            if (!in_array($extension, $allowedExtensions)) {
                                throw new \Exception("File untuk alat ID $id harus berupa gambar (jpg, jpeg, png, atau webp).");
                            }
                            $filename = time() . '_' . \Illuminate\Support\Str::random(8) . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                            $file->move(public_path('berkas-customer'), $filename);
                            if ($filenameFirst === null) {
                                $filenameFirst = $filename;
                            }
                            $fotoRows[] = [
                                'norec' => (string) \Illuminate\Support\Str::uuid(),
                                'noregistrasiterimadfk' => $norecDetail,
                                'namafile' => $filename,
                                'statusenabled' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    $rows[] = [
                        'norec'                 => $norecDetail,
                        'noregistrasiterimahfk' => $norecHeader,
                        'detailalatfk'          => $id,
                        'isterima'              => true,
                        // 'fotoalatditerima'      => $filenameFirst,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                }

                if (!empty($rows)) {
                    DB::table('mitraregistrasi_terimad_t')->insert($rows);
                }
                if (!empty($fotoRows)) {
                    DB::table('mitraregistrasi_terimadfoto_t')->insert($fotoRows);
                }
                DB::table('mitraregistrasidetail_t')
                    ->whereIn('norec', $ids)
                    ->update(['isterima' => true]);
            }

            $total   = DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $noreg)
                ->where('statusenabled', true)
                ->count();
            $accepted = DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $noreg)
                ->where('statusenabled', true)
                ->where('isterima', true)
                ->count();

            DB::table('mitraregistrasi_t')
                ->where('norec', $noreg)
                ->update(['isSimpanTerima' => ($total > 0 && $total === $accepted)]);

            DB::commit();

            $transMessage = "Sukses Simpan";
            $result = [
                "status" => 200,
                "result" => [
                    "norec_terima" => $norecHeader,
                    "versi"        => $versiNext,
                    "jumlah"       => count($ids),
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $transMessage = "Gagal Simpan";
            $result = [
                "status" => 400,
                "result" => [
                    "error" => $e->getMessage(),
                ],
            ];
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function LayananVerif(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftjoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtrd.lokasirepairfk')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.tanggalmulai',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.namaproduk',
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lk1.id as lokasirepairfk',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pk.id as idpaket',
                'pk.namapaket',
                'pk.hari as hari_paket',
            )
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.isterima', null)
            ->where(function ($q) {
                $q->whereNotNull('mtrd.tglsetujumanagerlaporanrepair')
                    ->orWhereNotNull('mtrd.tglsetujumanagerlembarkerja');
            })
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $r['norec_pd']);

        $data = $data->orderByDesc('mmp.namaproduk')->get();

        $alatPerRegistrasi = [];
        foreach ($data as $item) {
            $alatPerRegistrasi[$item->norec][] = $item;
        }

        $result['length'] = count($data);
        $result['detail'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function cetakSelesaiTerima(Request $r)
    {
        $profile   = $this->profile();
        $print     = false;
        $pageWidth = 950;

        $terimafk = $r->get('terimafk');
        $noreg    = $r->get('norec');

        if ($terimafk) {
            $res['identitas'] = DB::table('mitraregistrasi_terimah_t as th')
                ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'th.noregistrasifk')
                ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
                ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
                ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
                ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
                ->leftJoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtr.lokasirepair')
                ->select(
                    'jb.id as idjabatan',
                    'jb.namajabatanulab as namajabanpetugaskaji',
                    'mtr.norec',
                    'mtr.tglregistrasi',
                    'mtr.nopendaftaran',
                    'mtr.catatan',
                    'mt.namaperusahaan',
                    'pg.id as petugaskajifk',
                    'pg.namalengkap as namapetugaskaji',
                    'pg.nid as nidnamapetugaskaji',
                    'lk.lokasi',
                    'lk1.lokasi as lokasirepair',
                    'mtr.jabatanpenanggungjawab',
                    'mtr.namapenanggungjawab',
                    DB::raw('th.tanggalterima as tanggalselesaiterima'),
                    'th.tempatterima',
                    'th.penerima',
                    'th.jabatanpenerima',
                    'th.petugasterima',
                    'th.jabatanpetugas'
                )
                ->where('mtr.statusenabled', true)
                ->where('mtr.iskaji', true)
                ->where('th.norec', $terimafk)
                ->first();

            $res['alat'] = DB::table('mitraregistrasi_terimad_t as td')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'td.detailalatfk')
                ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
                ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
                ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
                ->leftJoin('mitraregistrasi_terimadfoto_t as mtrdf', function ($join) {
                    $join->on('mtrdf.noregistrasiterimadfk', '=', 'td.norec')
                        ->where('mtrdf.statusenabled', true);
                })
                ->select(
                    'mtrd.norec as norec_detail',
                    'mtrd.iskaji',
                    'mtrd.keterangan',
                    'mtrd.tanggalpenolakanregis',
                    'mtrd.alasanpenolakanregis',
                    'mtrd.namafile',
                    'mtrd.nosertifikat',
                    'mtrd.nolaporanrepair',
                    'mtrd.tanggalmulai',
                    'mtrd.tglsetujumanagerlembarkerja',
                    'mmp.namaproduk',
                    'mmp.namamerk',
                    'mmp.namatipe',
                    'mmp.namaserialnumber',
                    'lk.id as lokasikalibrasifk',
                    'lk.lokasi',
                    'lp.id as lingkupfk',
                    'lp.lingkupkalibrasi',
                    'td.fotoalatditerima',
                    DB::raw("COALESCE(json_agg(mtrdf.namafile) FILTER (WHERE mtrdf.namafile IS NOT NULL), '[]') as foto_files")
                )
                ->where('td.noregistrasiterimahfk', $terimafk)
                ->groupBy(
                    'mtrd.norec',
                    'mtrd.iskaji',
                    'mtrd.keterangan',
                    'mtrd.tanggalpenolakanregis',
                    'mtrd.alasanpenolakanregis',
                    'mtrd.namafile',
                    'mtrd.nosertifikat',
                    'mtrd.nolaporanrepair',
                    'mtrd.tanggalmulai',
                    'mtrd.tglsetujumanagerlembarkerja',
                    'mmp.namaproduk',
                    'mmp.namamerk',
                    'mmp.namatipe',
                    'mmp.namaserialnumber',
                    'lk.id',
                    'lk.lokasi',
                    'lp.id',
                    'lp.lingkupkalibrasi',
                    'td.fotoalatditerima'
                )
                ->orderByDesc('mmp.namaproduk')
                ->get();
        } else {
            $res['identitas'] = DB::table('mitraregistrasi_t as mtr')
                ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
                ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
                ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
                ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
                ->leftJoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtr.lokasirepair')
                ->select(
                    'jb.id as idjabatan',
                    'jb.namajabatanulab as namajabanpetugaskaji',
                    'mtr.norec',
                    'mtr.tglregistrasi',
                    'mtr.nopendaftaran',
                    'mtr.catatan',
                    'mt.namaperusahaan',
                    'pg.id as petugaskajifk',
                    'pg.namalengkap as namapetugaskaji',
                    'pg.nid as nidnamapetugaskaji',
                    'lk.lokasi',
                    'lk1.lokasi as lokasirepair',
                    'mtr.jabatanpenanggungjawab',
                    'mtr.namapenanggungjawab',
                    'mtr.penerima',
                    'mtr.tempatterima',
                    'mtr.tanggalselesaiterima',
                    'mtr.jabatanpenerima',
                    'mtr.petugasterima',
                    'mtr.jabatanpetugas'
                )
                ->where('mtr.statusenabled', true)
                ->where('mtr.iskaji', true)
                ->where('mtr.norec', $noreg)
                ->first();

            $res['alat'] = DB::table('mitraregistrasi_t as mtr')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
                ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
                ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
                ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
                ->select(
                    'mtr.norec',
                    'mtrd.norec as norec_detail',
                    'mtrd.iskaji',
                    'mtrd.keterangan',
                    'mtrd.tanggalpenolakanregis',
                    'mtrd.alasanpenolakanregis',
                    'mtrd.namafile',
                    'mtrd.nosertifikat',
                    'mtrd.nolaporanrepair',
                    'mtrd.tanggalmulai',
                    'mtrd.tglsetujumanagerlembarkerja',
                    'mmp.namaproduk',
                    'mmp.namamerk',
                    'mmp.namatipe',
                    'mmp.namaserialnumber',
                    'lk.id as lokasikalibrasifk',
                    'lk.lokasi',
                    'lp.id as lingkupfk',
                    'lp.lingkupkalibrasi'
                )
                ->where('mtr.statusenabled', true)
                ->where('mtr.iskaji', true)
                ->where('mtrd.statusenabled', true)
                ->where('mmp.statusenabled', true)
                ->where('mtr.norec', $noreg)
                ->orderByDesc('mmp.namaproduk')
                ->get();
        }

        $holidayDates = DB::table('master_hari_libur_m')
            ->where('statusenabled', true)
            ->pluck('tanggal')
            ->map(function ($tanggal) {
                return Carbon::parse($tanggal)->format('Y-m-d');
            })
            ->toArray();

        $calcWorkingSeconds = function (?Carbon $start, ?Carbon $end) use ($holidayDates): ?int {
            if (!$start || !$end) {
                return null;
            }

            if ($end->lessThan($start)) {
                [$start, $end] = [$end, $start];
            }

            $workStart = '07:30:00';
            $workEnd   = '16:00:00';

            $totalSeconds = 0;
            $cursor = $start->copy()->startOfDay();
            $lastDay = $end->copy()->startOfDay();

            while ($cursor->lte($lastDay)) {
                $isoDay = $cursor->isoWeekday();
                $currentDate = $cursor->format('Y-m-d');

                $isWeekend = ($isoDay == 6 || $isoDay == 7);
                $isHoliday = in_array($currentDate, $holidayDates);
                $isWorkday = !$isWeekend && !$isHoliday;

                if ($isWorkday) {
                    $dayWorkStart = $cursor->copy()->setTimeFromTimeString($workStart);
                    $dayWorkEnd   = $cursor->copy()->setTimeFromTimeString($workEnd);

                    $effectiveStart = $start->copy()->max($dayWorkStart);
                    $effectiveEnd   = $end->copy()->min($dayWorkEnd);

                    if ($effectiveEnd->greaterThan($effectiveStart)) {
                        $totalSeconds += $effectiveEnd->diffInSeconds($effectiveStart);
                    }
                }

                $cursor->addDay();
            }

            return $totalSeconds;
        };

        foreach ($res['alat'] as $item) {
            $tglMulai = !empty($item->tanggalmulai)
                ? Carbon::parse($item->tanggalmulai)
                : null;

            $tglSetujuManager = !empty($item->tglsetujumanagerlembarkerja)
                ? Carbon::parse($item->tglsetujumanagerlembarkerja)
                : null;

            $diffInSeconds = $calcWorkingSeconds($tglMulai, $tglSetujuManager);

            if ($diffInSeconds !== null) {
                $workdaySeconds = (8 * 60 * 60) + (30 * 60);

                $days = intdiv($diffInSeconds, $workdaySeconds);
                $rem  = $diffInSeconds % $workdaySeconds;

                $hours = intdiv($rem, 3600);
                $rem   = $rem % 3600;

                $minutes = intdiv($rem, 60);
                $seconds = $rem % 60;

                $item->durasi_proses = "{$days} hari kerja {$hours} jam";
                $item->durasi_detik  = $diffInSeconds;
            } else {
                $item->durasi_proses = '-';
                $item->durasi_detik  = null;
            }
        }

        $res['pdf']  = $r['pdf'];

        $res['ttdPetugas'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            ($res['identitas']->jabatanpetugas ?? '') . "\n" . ($res['identitas']->petugasterima ?? '') . "\n\n" .
                'nosurat: FMMO-163-14.4.3.b-74.6' . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));

        $res['ttdPelanggan'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            ($res['identitas']->jabatanpenerima ?? '') . "\n" . ($res['identitas']->penerima ?? '') . "\n" .
                'nosurat: FMMO-163-14.4.3.b-74.6' . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));

        $blade = 'report.registrasi.selesai-terima';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->setPaper('a4', 'portrait');
            $pdf->loadView($blade . '-dom', compact('profile', 'pageWidth', 'print', 'res'));
            return $pdf->stream();
        }

        if (isset($r['storage'])) {
            $res['storage']  = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->loadView($blade . '-dom', compact('profile', 'pageWidth', 'print', 'res'));
            return $pdf;
        }

        return view($blade, compact('profile', 'pageWidth', 'print', 'res'));
    }

    public function listVersiTerima(Request $r)
    {
        $noreg = $r->get('norec');

        $rows = DB::table('mitraregistrasi_terimah_t as th')
            ->leftJoin('mitraregistrasi_terimad_t as td', 'td.noregistrasiterimahfk', '=', 'th.norec')
            ->select(
                'th.norec as terimafk',
                'th.versi',
                'th.tanggalterima',
                DB::raw('count(td.norec) as jumlah_alat')
            )
            ->where('th.noregistrasifk', $noreg)
            ->groupBy('th.norec', 'th.versi', 'th.tanggalterima')
            ->orderBy('th.versi')
            ->get();

        return $this->respond($rows, 200, 'ok');
    }

    public function saveSertifikatVendor(Request $r)
    {
        DB::beginTransaction();
        try {
            $filename = null;
            if ($r->hasFile('fileSertiVendor')) {
                $file = $r->file('fileSertiVendor');
                $allowedExtensions = ['pdf'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (pdf).");
                }
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('berkas-vendor'), $filename);
            } else {
                $filename = $r['namaFileLama'] ?? null;
            }

            if ($r['jenisorder'] == 'repair') {
                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $r->norec)
                    ->update([
                        'fileSertiVendor'      => $filename,
                        'tglupladosertivendor' => now(),
                        'statusordermanager' => 2,
                        'tglsetujumanagerlaporanrepair' => now(),
                    ]);
            } else {
                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $r->norec)
                    ->update([
                        'fileSertiVendor'      => $filename,
                        'tglupladosertivendor' => now(),
                        'statusordermanager' => 2,
                        'tglsetujumanagerlembarkerja' => now(),
                    ]);
            }

            DB::commit();
            $transMessage = "Simpan Seertifikat Vendor Sukses";
            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@aditwiran19@gmail.com',
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $result = [
                "status" => 400,
                "result" => $e->getMessage(),
            ];
            $transMessage = "Simpan Gagal";
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function saveAmsBaru(Request $r)
    {
        DB::beginTransaction();
        try {
            $filename = null;
            if ($r->hasFile('fileAMSBaru')) {
                $file = $r->file('fileAMSBaru');
                $allowedExtensions = ['pdf'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa gambar (pdf).");
                }
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('berkas-customer'), $filename);
            } else {
                $filename = $r['namaFileLama'] ?? null;
            }

            DB::table('mitraregistrasi_t')
                ->where('norec', $r->norec)
                ->update([
                    'filecustomerams'      => $filename,
                ]);

            DB::commit();
            $transMessage = "Simpan AMS Baru Sukses";
            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@aditwiran19@gmail.com',
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $result = [
                "status" => 400,
                "result" => $e->getMessage(),
            ];
            $transMessage = "Simpan Gagal";
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function cetakSertiVendor(Request $request)
    {
        $data = DB::table('mitraregistrasidetail_t')
            ->where('norec', $request['norec'])
            ->first();

        if (!$data || !$data->fileSertiVendor) {
            abort(404, 'Data Sertifikat atau file tidak ditemukan');
        }

        $filename = basename($data->fileSertiVendor);
        $filepath = $this->publicFileUrl('berkas-vendor', $filename);

        return view('report.customer.sertifikat-vendor', compact('filepath', 'data'));
    }

    public function cetakSertifikatLembarKerjaPdf(Request $request)
    {
        $q = DB::table('sertifikat_log')
            ->where('norec_detail', $request->get('norec_detail'));

        if ($request->filled('version')) {
            $q->where('version', $request->get('version'));
        } else {
            $q->orderByDesc('version')->orderByDesc('id');
        }

        $data = $q->first();

        if (!$data || !$data->file_path) {
            abort(404, 'Data Sertifikat atau file tidak ditemukan');
        }

        $filename = basename($data->file_path);

        $filepath = $this->publicFileUrl('sertifikat', $filename);

        return view(
            'report.customer.sertifikat-customer',
            compact('filepath', 'data')
        );
    }

    public function cetakLaporanRepairPdf(Request $request)
    {
        $q = DB::table('laporan_repair_log')
            ->where('norec_detail', $request->get('norec_detail'));

        if ($request->filled('version')) {
            $q->where('version', $request->get('version'));
        } else {
            $q->orderByDesc('version')->orderByDesc('id');
        }

        $data = $q->first();

        if (!$data || !$data->file_path) {
            abort(404, 'Data laporan repair atau file tidak ditemukan');
        }

        $filename = basename($data->file_path);
        $filepath = $this->publicFileUrl('laporan-repair', $filename);

        return view('report.customer.laporan-repair-customer', compact('filepath', 'data'));
    }

    public function saveBatalKalibrasiAlat(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_batal = $request['itembatal'];
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_batal['norec_detail'])
                ->update([
                    'statusenabled' => false,
                    'ketgagalkalibrasi' => $r_batal['ketgagalkalibrasi']
                ]);

            $message = 'Pemmbatalan Berhasil';

            DB::commit();

            $regis = DB::table('mitraregistrasi_t')->where('norec', $r_batal['norec'])->first();
            $nopendaftaran = $r_batal['nopendaftaran'] ?? null;
            $ketgagalkalibrasi = $r_batal['ketgagalkalibrasi'] ?? null;
            $detailBatal = DB::table('mitraregistrasidetail_t as mtrd')
                ->leftJoin('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
                ->select(
                    'mtrd.*',
                    'mmp.namaproduk',
                    'mmp.namamerk',
                    'mmp.namatipe',
                    'mmp.namaserialnumber'
                )
                ->where('mtrd.norec', $r_batal['norec_detail'])
                ->first();
            $noorderalat = $r_batal['noorderalat'] ?? ($detailBatal->noorderalat ?? null);
            $alatParts = array_filter([
                $detailBatal->namaproduk ?? null,
                $detailBatal->namamerk ?? null,
                $detailBatal->namatipe ?? null,
                $detailBatal->namaserialnumber ?? null,
            ], function ($value) {
                return $value !== null && trim((string) $value) !== '';
            });
            $alatLabel = !empty($alatParts) ? implode(' / ', $alatParts) : 'Alat';

            $asmanId = $regis->asmanveriffk ?? null;
            if ($asmanId) {
                $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';
                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Pemberitahuan: *$alatLabel* dengan No Order Alat *$noorderalat* telah dikeluarkan dari kalibrasi pada No Pendaftaran *$nopendaftaran* oleh Admin.\n"
                        . "Alasan pembatalan: *$ketgagalkalibrasi*.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $customer = DB::table('users')->where('id', $regis->customerfk)->first() ?? null;
            $nohpCustomer = $customer->nowa ?? ($regis->nohppenanggungjawab ?? null);
            $namaCustomer = $customer->name ?? ($regis->namapenanggungjawab ?? 'Customer');
            $pesanCustomer = "Yth. $namaCustomer,\n\n"
                . "Pemberitahuan: *$alatLabel* dengan No Order Alat *$noorderalat* telah dikeluarkan dari kalibrasi pada No Pendaftaran *$nopendaftaran* oleh Admin.\n"
                . "Alasan pembatalan: *$ketgagalkalibrasi*.\n\n"
                . "Terima kasih Yth Bpk/Ibu $namaCustomer\n"
                . "atas kepercayaan Anda kepada Tim U-LAB\n"
                . "Laboratorium Kalibrasi Multilokasi PT PLN Nusantara Power terakreditasi KAN.\n\n"
                . "Bagi kami, setiap alat ukur bukan hanya sekadar perangkat, tapi bagian penting dari produktivitas dan keselamatan kerja Anda.\n\n"
                . "Dengan akurasi, kecepatan, dan layanan 24/7, kami hadir sebagai mitra strategis untuk industri Anda.\n\n"
                . "🌐 Layanan Multilokasi (Jakarta & Gresik)\n"
                . "🕐 Operasional 24/7\n"
                . "🔧 Kalibrasi & Repair dalam satu pintu\n"
                . "📄 Sertifikat ISO/IEC 17025\n\n"
                . "Karena kepuasan Anda adalah tolok ukur keberhasilan kami.\n"
                . "Bersama U-LAB, akurasi & presisi bukan lagi sekadar janji — tapi bukti.\n\n"
                . "Pagi cerah langit membiru,\n"
                . "Langkah mantap menuju tujuan.\n"
                . "Mari bekerja penuh rindu,\n"
                . "Demi pelayanan dan kepuasan pelanggan.\n\n"
                . "Salam,\n"
                . "U-LAB ! Cepat, Tepat, Akurat 💯";

            if ($nohpCustomer) {
                $this->kirimWhatsappNotifikasi($nohpCustomer, $pesanCustomer);
            }

            $mitraFkMobile = $this->resolveMobileMitraFk($regis, $customer);
            $this->sendMobilePushToMitra(
                $mitraFkMobile,
                'Alat Dibatalkan',
                "$alatLabel dengan No Order $noorderalat dibatalkan. Alasan: $ketgagalkalibrasi",
                [
                    'type' => 'alat_dibatalkan',
                    'screen' => 'history_screen',
                    'target_screen' => 'history_screen',
                    'action' => 'open_registration_history',
                    'norec_registrasi' => (string) ($regis->norec ?? $r_batal['norec'] ?? ''),
                    'norec_detail' => (string) ($r_batal['norec_detail'] ?? ''),
                    'nopendaftaran' => (string) ($nopendaftaran ?? ''),
                    'noorderalat' => (string) ($noorderalat ?? ''),
                    'alat' => (string) ($alatLabel ?? ''),
                    'alasan' => (string) ($ketgagalkalibrasi ?? ''),
                ],
                'saveBatalKalibrasiAlat',
                [
                    'customerfk' => $regis->customerfk ?? null,
                    'nomitrafk' => $regis->nomitrafk ?? null,
                    'norec_registrasi' => $regis->norec ?? $r_batal['norec'] ?? null,
                    'norec_detail' => $r_batal['norec_detail'] ?? null,
                    'nopendaftaran' => $nopendaftaran ?? null,
                    'noorderalat' => $noorderalat ?? null,
                ]
            );


            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "as" => '@aditwiran19@gmail.com',
                )
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => "Something Went Wrong",
                "result"  => $e->getMessage() . ' | Line: ' . $e->getLine()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }


    public function saveUbahPelaksana(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_ubah = $request['itemPelaksana'];
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_ubah['norec_detail'])
                ->update([
                    'pelaksanateknikfk' => $r_ubah['pelaksanbaru'],
                    'penyeliateknikfk' => $r_ubah['penyeliabaru']
                ]);

            $message = 'Ubah Penyelia dan Pelaksana Berhasil';

            DB::commit();

            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "as" => '@aditwiran19@gmail.com',
                )
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => "Something Went Wrong",
                "result"  => $e->getMessage() . ' | Line: ' . $e->getLine()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function saveUbahVendor(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_ubah = $request['itemVendor'];
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_ubah['norec_detail'])
                ->update([
                    'vendorkalibrasifk' => $r_ubah['vendorbaru']
                ]);

            $message = 'Ubah Vendor Berhasil';

            DB::commit();

            $result = array(
                "status" => 200,
                "message" => $message,
                "result" => array(
                    "as" => '@aditwiran19@gmail.com',
                )
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => "Something Went Wrong",
                "result"  => $e->getMessage() . ' | Line: ' . $e->getLine()
            );
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function getRiwayatAmandemen(Request $request)
    {
        $data = DB::table('sertifikat_log as sl')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'sl.norec_detail')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('sl.*', 'mtr.jenisorder')
            ->where('sl.norec_detail', $request->norec_detail)
            ->orderBy('sl.created_at', 'ASC')
            ->get();

        return $this->respond($data);
    }


    private function generateNoSuratJalan($tanggal = null)
    {
        $tgl = $tanggal ? Carbon::parse($tanggal) : Carbon::now();

        $tahun = $tgl->format('Y');
        $bulan = (int) $tgl->format('n');

        $this->lockNomorTransaksi('nomor-surat-jalan', [$tahun]);

        $lastUrut = DB::table('suratjalan_t')
            ->whereNotNull('nosuratjalan')
            ->whereRaw("split_part(nosuratjalan, '/', 1) ~ '^[0-9]+$'")
            ->whereRaw("split_part(nosuratjalan, '/', 5) = ?", [$tahun])
            ->max(DB::raw("CAST(split_part(nosuratjalan, '/', 1) AS INTEGER)"));

        $urut = str_pad((string)(((int) ($lastUrut ?? 0)) + 1), 3, '0', STR_PAD_LEFT);

        return $urut . '/' . $bulan . '/PLNNPUMRO/ULAB/' . $tahun;
    }

    private function formatNamaFileSuratJalan($noSuratJalan)
    {
        $noSuratJalan = $noSuratJalan ?: 'TANPA-NOMOR';
        $noSuratJalan = str_replace(['/', '\\'], '-', $noSuratJalan);
        $noSuratJalan = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $noSuratJalan);

        return 'Surat-Jalan-No-' . $noSuratJalan . '.pdf';
    }

    private function getUrlCetakSuratJalanPublic($norec)
    {
        return url('service/registrasi/cetak-surat-jalan?pdf=true&norec=' . $norec);
    }

    public function kirimWaPengajuanSuratJalanKeAsman($norecSuratJalan)
    {
        $head = DB::table('suratjalan_t as sj')
            ->leftJoin('pegawai_m as pg_asman', 'pg_asman.id', '=', 'sj.asmanfk')
            ->leftJoin('pegawai_m as pg_admin', 'pg_admin.id', '=', 'sj.petugasfk')
            ->select(
                'sj.norec',
                'sj.nosuratjalan',
                'sj.nopendaftaran',
                'sj.diberikankepada',
                'sj.tujuan',
                'sj.tanggalsurat',
                'sj.asmanfk',
                'sj.namaasman',
                'sj.petugasfk',
                'sj.namapetugas',
                'pg_asman.namalengkap as nama_asman_master',
                'pg_asman.nohandphone as nohp_asman',
                'pg_admin.namalengkap as nama_admin_master'
            )
            ->where('sj.statusenabled', true)
            ->where('sj.norec', $norecSuratJalan)
            ->first();

        if (!$head) {
            return;
        }

        if (empty($head->nohp_asman)) {
            return;
        }

        $jumlahBarang = DB::table('suratjalandetail_t')
            ->where('statusenabled', true)
            ->where('suratjalanfk', $norecSuratJalan)
            ->count();

        $tanggalSurat = '-';
        if (!empty($head->tanggalsurat)) {
            $tanggalSurat = Carbon::parse($head->tanggalsurat)
                ->locale('id')
                ->isoFormat('D MMMM Y');
        }

        $namaAsman = $head->namaasman ?: ($head->nama_asman_master ?: 'Asman');
        $namaAdmin = $head->namapetugas ?: ($head->nama_admin_master ?: '-');
        $pesan = "Halo Tim Hebat U-LAB ! 👋\n\n"
            . "Yth. *" . $namaAsman . "*\n\n"
            . "Terdapat pengajuan *Surat Jalan* yang membutuhkan persetujuan Asman.\n\n"
            . "No Surat Jalan : *" . ($head->nosuratjalan ?? '-') . "*\n"
            . "No Pendaftaran : *" . ($head->nopendaftaran ?? '-') . "*\n"
            . "Diberikan Kepada : *" . ($head->diberikankepada ?? '-') . "*\n"
            . "Tujuan : *" . ($head->tujuan ?? '-') . "*\n"
            . "Tanggal : *" . $tanggalSurat . "*\n"
            . "Jumlah Barang : *" . $jumlahBarang . " barang*\n"
            . "Diajukan Oleh : *" . $namaAdmin . "*\n\n"
            . "Silakan buka menu *Persetujuan Surat Jalan* pada aplikasi U-LAB untuk menyetujui atau menolak pengajuan.\n\n"
            . "Preview Dokumen:\n"
            . "\nSalam,\n"
            . "U-LAB ! Cepat, Tepat, Akurat 💯";

        $this->kirimWhatsappNotifikasi($head->nohp_asman, $pesan);
    }

    private function statusSuratJalanText($status)
    {
        return 'Dibuat';
    }

    private function statusSuratJalanColor($status)
    {
        return 'info';
    }

    public function listRegistrasiSuratJalan(Request $r)
    {
        $page = isset($r['page']) && $r['page'] != '' ? (int) $r['page'] : 1;
        $limit = isset($r['limit']) && $r['limit'] != '' ? (int) $r['limit'] : 6;
        $offset = ($page - 1) * $limit;

        $eligibleCondition = "
        mtrd.statusenabled = true
        AND (mtrd.isterima IS NULL OR mtrd.isterima = false)
        AND (
            mtrd.tglsetujumanagerlaporanrepair IS NOT NULL
            OR mtrd.tglsetujumanagerlembarkerja IS NOT NULL
        )
    ";

        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select(
                'mtr.norec',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.tanggalmulai',
                'mt.namaperusahaan',

                DB::raw("(
                SELECT COUNT(1)
                FROM mitraregistrasidetail_t mtrd
                WHERE mtrd.noregistrasifk = mtr.norec
                  AND $eligibleCondition
            ) as jumlahdetail"),

                DB::raw("(
                SELECT COUNT(DISTINCT sj.norec)
                FROM suratjalan_t sj
                INNER JOIN suratjalandetail_t sjd ON sjd.suratjalanfk = sj.norec
                INNER JOIN mitraregistrasidetail_t mtrd ON mtrd.norec = sjd.noregistrasidetailfk
                WHERE sj.noregistrasifk = mtr.norec
                  AND sj.statusenabled = true
                  AND sjd.statusenabled = true
                  AND $eligibleCondition
            ) as jumlahsuratjalan"),

                DB::raw("(
                SELECT COUNT(DISTINCT sjd.noregistrasidetailfk)
                FROM suratjalan_t sj
                INNER JOIN suratjalandetail_t sjd ON sjd.suratjalanfk = sj.norec
                INNER JOIN mitraregistrasidetail_t mtrd ON mtrd.norec = sjd.noregistrasidetailfk
                WHERE sj.noregistrasifk = mtr.norec
                  AND sj.statusenabled = true
                  AND sjd.statusenabled = true
                  AND sjd.noregistrasidetailfk IS NOT NULL
                  AND $eligibleCondition
            ) as jumlahalatsuratjalan"),

                DB::raw("(
                (
                    SELECT COUNT(1)
                    FROM mitraregistrasidetail_t mtrd
                    WHERE mtrd.noregistrasifk = mtr.norec
                      AND $eligibleCondition
                )
                -
                (
                    SELECT COUNT(DISTINCT sjd.noregistrasidetailfk)
                    FROM suratjalan_t sj
                    INNER JOIN suratjalandetail_t sjd ON sjd.suratjalanfk = sj.norec
                    INNER JOIN mitraregistrasidetail_t mtrd ON mtrd.norec = sjd.noregistrasidetailfk
                    WHERE sj.noregistrasifk = mtr.norec
                      AND sj.statusenabled = true
                      AND sjd.statusenabled = true
                      AND sjd.noregistrasidetailfk IS NOT NULL
                      AND $eligibleCondition
                )
            ) as jumlahtersedia")
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('mitraregistrasidetail_t as mtrd')
                    ->whereRaw('mtrd.noregistrasifk = mtr.norec')
                    ->where('mtrd.statusenabled', true)
                    ->where(function ($w) {
                        $w->whereNull('mtrd.isterima')
                            ->orWhere('mtrd.isterima', false);
                    })
                    ->where(function ($w) {
                        $w->whereNotNull('mtrd.tglsetujumanagerlaporanrepair')
                            ->orWhereNotNull('mtrd.tglsetujumanagerlembarkerja');
                    })
                    ->whereNotExists(function ($x) {
                        $x->select(DB::raw(1))
                            ->from('suratjalandetail_t as sjd')
                            ->join('suratjalan_t as sj', 'sj.norec', '=', 'sjd.suratjalanfk')
                            ->whereRaw('sjd.noregistrasidetailfk = mtrd.norec')
                            ->where('sjd.statusenabled', true)
                            ->where('sj.statusenabled', true);
                    });
            });

        if (isset($r['dari']) && $r['dari'] != '') {
            $data = $data->where('mtr.tglregistrasi', '>=', $r['dari']);
        }

        if (isset($r['sampai']) && $r['sampai'] != '') {
            $data = $data->where('mtr.tglregistrasi', '<=', $r['sampai']);
        }

        if (isset($r['search']) && $r['search'] != '') {
            $search = '%' . strtolower($r['search']) . '%';

            $data = $data->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(mtr.nopendaftaran) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(mt.namaperusahaan) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(mtr.jenisorder) LIKE ?', [$search]);
            });
        }

        $total = (clone $data)->count();

        $data = $data
            ->orderByDesc('mtr.tglregistrasi')
            ->offset($offset)
            ->limit($limit)
            ->get();

        foreach ($data as $item) {
            $item->jumlahdetail = (int) $item->jumlahdetail;
            $item->jumlahsuratjalan = (int) $item->jumlahsuratjalan;
            $item->jumlahalatsuratjalan = (int) $item->jumlahalatsuratjalan;
            $item->jumlahtersedia = (int) $item->jumlahtersedia;

            if ($item->jumlahtersedia < 0) {
                $item->jumlahtersedia = 0;
            }
        }

        return $this->respond([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'as' => 'aditwiran19@gmail.com'
        ]);
    }

    public function layananSuratJalan(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapunittoalat_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtrd.lokasirepairfk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.tanggalmulai',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.namaproduk',
                'mmp.fotoproduk',
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lk1.id as lokasirepairfk',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pk.id as idpaket',
                'pk.namapaket',
                'pk.hari as hari_paket'
            )
            ->where('mtr.statusenabled', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where(function ($q) {
                $q->whereNull('mtrd.isterima')
                    ->orWhere('mtrd.isterima', false);
            })
            ->where(function ($q) {
                $q->whereNotNull('mtrd.tglsetujumanagerlaporanrepair')
                    ->orWhereNotNull('mtrd.tglsetujumanagerlembarkerja');
            })
            ->where('mtr.norec', $r['norec_pd'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('suratjalandetail_t as sjd')
                    ->join('suratjalan_t as sj', 'sj.norec', '=', 'sjd.suratjalanfk')
                    ->whereRaw('sjd.noregistrasidetailfk = mtrd.norec')
                    ->where('sjd.statusenabled', true)
                    ->where('sj.statusenabled', true);
            })
            ->orderByDesc('mmp.namaproduk')
            ->get();

        $result['length'] = count($data);
        $result['detail'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function riwayatSuratJalan(Request $r)
    {
        $data = DB::table('suratjalan_t as sj')
            ->leftJoin('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'sj.noregistrasifk')
            ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'sj.pegawaibawafk')
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
                'sj.namaasman',
                'sj.created_at',
                'mt.namaperusahaan',
                DB::raw("(SELECT COUNT(1) FROM suratjalandetail_t sjd WHERE sjd.suratjalanfk = sj.norec AND sjd.statusenabled = true) as jumlahbarang")
            )
            ->where('sj.statusenabled', true);

        if (isset($r['norec_pd']) && $r['norec_pd'] != '') {
            $data = $data->where('sj.noregistrasifk', $r['norec_pd']);
        }

        if (isset($r['search']) && $r['search'] != '') {
            $search = '%' . strtolower($r['search']) . '%';
            $data = $data->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(sj.nosuratjalan) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.nopendaftaran) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.diberikankepada) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.tujuan) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.namapegawaibawa) like ?', [$search])
                    ->orWhereRaw('LOWER(mt.namaperusahaan) like ?', [$search]);
            });
        }

        $data = $data->orderByDesc('sj.created_at')->get();

        foreach ($data as $item) {
            $item->status = $this->statusSuratJalanText($item->statussuratjalan);
            $item->color = $this->statusSuratJalanColor($item->statussuratjalan);
        }

        return $this->respond($data);
    }

    public function detailSuratJalan(Request $r)
    {
        $head = DB::table('suratjalan_t as sj')
            ->leftJoin('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'sj.noregistrasifk')
            ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'sj.pegawaibawafk')
            ->select(
                'sj.*',
                'mt.namaperusahaan',
                'pg.namalengkap as pegawaibawa'
            )
            ->where('sj.statusenabled', true)
            ->where('sj.norec', $r['norec'])
            ->first();

        if (!$head) {
            return $this->respond([
                'message' => 'Data surat jalan tidak ditemukan',
                'head' => null,
                'detail' => []
            ]);
        }

        $detail = DB::table('suratjalandetail_t as sjd')
            ->select('sjd.*')
            ->where('sjd.statusenabled', true)
            ->where('sjd.suratjalanfk', $r['norec'])
            ->orderBy('sjd.nourut')
            ->get();

        $fotos = $detail->isNotEmpty()
            ? DB::table('suratjalanfoto_t')
            ->where('statusenabled', true)
            ->whereIn('suratjalandetailfk', $detail->pluck('norec'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('suratjalandetailfk')
            : collect();

        foreach ($detail as $d) {
            $d->foto = $fotos->get($d->norec, collect())->values();
        }

        $head->status = $this->statusSuratJalanText($head->statussuratjalan);
        $head->color = $this->statusSuratJalanColor($head->statussuratjalan);

        $result = [
            'head' => $head,
            'detail' => $detail,
            'as' => 'aditwiran19@gmail.com'
        ];

        return $this->respond($result);
    }

    public function saveSuratJalan(Request $r)
    {
        DB::beginTransaction();

        try {
            $detailBarang = json_decode($r['detailbarang'] ?? '[]', true);

            if (!is_array($detailBarang) || count($detailBarang) == 0) {
                throw new \Exception('Detail barang tidak boleh kosong.');
            }

            if (!isset($r['tanggalsurat']) || $r['tanggalsurat'] == '') {
                throw new \Exception('Tanggal surat wajib diisi.');
            }

            if (!isset($r['diberikankepada']) || trim($r['diberikankepada']) == '') {
                throw new \Exception('Diberikan kepada wajib diisi.');
            }

            if (!isset($r['tujuan']) || trim($r['tujuan']) == '') {
                throw new \Exception('Tujuan wajib diisi.');
            }

            if (!isset($r['barangbarangdari']) || trim($r['barangbarangdari']) == '') {
                throw new \Exception('Barang-barang dari wajib diisi.');
            }

            if (!isset($r['namapegawaibawa']) || trim($r['namapegawaibawa']) == '') {
                throw new \Exception('Yang membawa wajib diisi.');
            }

            $sumber = $r['sumber'] ?? 'registrasi';
            $noregistrasifk = $r['noregistrasifk'] ?? null;
            $nopendaftaran = $r['nopendaftaran'] ?? null;
            if (!empty($noregistrasifk)) {
                $regis = DB::table('mitraregistrasi_t')
                    ->where('norec', $noregistrasifk)
                    ->first();

                if ($regis) {
                    $nopendaftaran = $regis->nopendaftaran ?? $nopendaftaran;
                }
            }
            $detailFk = [];
            foreach ($detailBarang as $item) {
                if (isset($item['noregistrasidetailfk']) && $item['noregistrasidetailfk'] != '') {
                    $detailFk[] = $item['noregistrasidetailfk'];
                }
            }
            $detailFk = array_values(array_unique($detailFk));
            if (count($detailFk) > 0) {
                $sudahAda = DB::table('suratjalandetail_t as sjd')
                    ->join('suratjalan_t as sj', 'sj.norec', '=', 'sjd.suratjalanfk')
                    ->select(
                        'sjd.noregistrasidetailfk',
                        'sjd.namabarang',
                        'sjd.namaserialnumber',
                        'sj.nosuratjalan'
                    )
                    ->whereIn('sjd.noregistrasidetailfk', $detailFk)
                    ->where('sjd.statusenabled', true)
                    ->where('sj.statusenabled', true)
                    ->get();

                if (count($sudahAda) > 0) {
                    $list = [];
                    foreach ($sudahAda as $x) {
                        $list[] = ($x->namabarang ?? 'Alat')
                            . ' / SN: '
                            . ($x->namaserialnumber ?? '-')
                            . ' sudah ada di Surat Jalan No. '
                            . ($x->nosuratjalan ?? '-');
                    }

                    throw new \Exception('Tidak bisa membuat surat jalan. ' . implode('; ', $list));
                }
            }
            $norec = (string) Str::uuid();
            $tanggalSurat = Carbon::parse($r['tanggalsurat']);
            $noSuratJalan = $this->generateNoSuratJalan($tanggalSurat);

            DB::table('suratjalan_t')->insert([
                'norec' => $norec,
                'kdprofile' => 1,
                'statusenabled' => true,
                'nosuratjalan' => $noSuratJalan,
                'sumber' => $sumber,
                'noregistrasifk' => $noregistrasifk,
                'nopendaftaran' => $nopendaftaran,
                'diberikankepada' => $r['diberikankepada'] ?? null,
                'berdasarkan' => $r['berdasarkan'] ?? null,
                'tanggalsurat' => $tanggalSurat,
                'tujuan' => $r['tujuan'] ?? null,
                'barangbarangdari' => $r['barangbarangdari'] ?? null,
                'kendaraan' => $r['kendaraan'] ?? null,
                'nomorpolisi' => $r['nomorpolisi'] ?? null,
                'pengemudi' => $r['pengemudi'] ?? null,
                'pegawaibawafk' => $r['pegawaibawafk'] ?? null,
                'namapegawaibawa' => $r['namapegawaibawa'] ?? null,
                'statussuratjalan' => 1,
                'keteranganstatus' => 'Dibuat',
                'tglajukan' => Carbon::now(),
                'petugasfk' => $r['petugasfk'] ?? null,
                'namapetugas' => $r['namapetugas'] ?? null,
                'asmanfk' => null,
                'namaasman' => null,
                'tglasman' => null,
                'created_at' => Carbon::now(),
                'updated_at' => null
            ]);

            $uploadDir = public_path('surat-jalan');
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0777, true, true);
            }

            $detailRows = [];
            $fotoRows = [];
            foreach ($detailBarang as $index => $item) {
                if (!isset($item['namabarang']) || trim($item['namabarang']) == '') {
                    throw new \Exception('Nama barang pada baris ' . ($index + 1) . ' wajib diisi.');
                }

                $norecDetail = (string) Str::uuid();

                $detailRows[] = [
                    'norec' => $norecDetail,
                    'kdprofile' => 1,
                    'statusenabled' => true,

                    'suratjalanfk' => $norec,
                    'noregistrasidetailfk' => $item['noregistrasidetailfk'] ?? null,

                    'nourut' => $index + 1,
                    'namabarang' => $item['namabarang'] ?? null,
                    'namamerk' => $item['namamerk'] ?? null,
                    'namatipe' => $item['namatipe'] ?? null,
                    'namaserialnumber' => $item['namaserialnumber'] ?? null,

                    'jumlah' => $item['jumlah'] ?? '1',
                    'satuan' => strtoupper($item['satuan'] ?? 'SET'),
                    'ket' => $item['ket'] ?? null,

                    'created_at' => Carbon::now(),
                    'updated_at' => null
                ];

                if (isset($item['fotoproduk']) && $item['fotoproduk'] != '') {
                    $fotoRows[] = [
                        'norec' => (string) Str::uuid(),
                        'kdprofile' => 1,
                        'statusenabled' => true,
                        'suratjalanfk' => $norec,
                        'suratjalandetailfk' => $norecDetail,
                        'jenisfoto' => 'fotoproduk',
                        'namafile' => basename($item['fotoproduk']),
                        'filepath' => $item['fotoproduk'],
                        'originalname' => basename($item['fotoproduk']),
                        'created_at' => Carbon::now(),
                        'updated_at' => null
                    ];
                }

                $files = $r->file('files.' . $index);

                if ($files) {
                    if (!is_array($files)) {
                        $files = [$files];
                    }

                    foreach ($files as $file) {
                        if (!$file) {
                            continue;
                        }

                        $ext = $file->getClientOriginalExtension();
                        $filename = 'surat_jalan_' . date('YmdHis') . '_' . Str::random(12) . '.' . $ext;
                        $file->move($uploadDir, $filename);

                        $fotoRows[] = [
                            'norec' => (string) Str::uuid(),
                            'kdprofile' => 1,
                            'statusenabled' => true,
                            'suratjalanfk' => $norec,
                            'suratjalandetailfk' => $norecDetail,
                            'jenisfoto' => 'upload',
                            'namafile' => $filename,
                            'filepath' => '/surat-jalan/' . $filename,
                            'originalname' => $file->getClientOriginalName(),
                            'created_at' => Carbon::now(),
                            'updated_at' => null
                        ];
                    }
                }
            }

            if (!empty($detailRows)) {
                DB::table('suratjalandetail_t')->insert($detailRows);
            }
            if (!empty($fotoRows)) {
                DB::table('suratjalanfoto_t')->insert($fotoRows);
            }

            DB::commit();

            return $this->respond([
                'status' => 201,
                'message' => 'Surat jalan berhasil dibuat.',
                'norec' => $norec,
                'nosuratjalan' => $noSuratJalan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function batalSuratJalan(Request $r)
    {
        DB::beginTransaction();

        try {
            if (!isset($r['norec']) || $r['norec'] == '') {
                throw new \Exception('Norec surat jalan tidak ditemukan.');
            }

            $data = DB::table('suratjalan_t')
                ->where('statusenabled', true)
                ->where('norec', $r['norec'])
                ->first();

            if (!$data) {
                throw new \Exception('Data surat jalan tidak ditemukan.');
            }

            DB::table('suratjalan_t')
                ->where('norec', $r['norec'])
                ->update([
                    'statusenabled' => false,
                    'keteranganstatus' => $r['alasanbatal'] ?? 'Dibatalkan',
                    'updated_at' => Carbon::now()
                ]);

            DB::table('suratjalandetail_t')
                ->where('suratjalanfk', $r['norec'])
                ->update([
                    'statusenabled' => false,
                    'updated_at' => Carbon::now()
                ]);

            DB::table('suratjalanfoto_t')
                ->where('suratjalanfk', $r['norec'])
                ->update([
                    'statusenabled' => false,
                    'updated_at' => Carbon::now()
                ]);

            DB::commit();

            return $this->respond([
                'status' => 200,
                'message' => 'Surat jalan berhasil dibatalkan.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function buildValidasiSuratJalanUrl($norec, $ttd)
    {
        return 'https://ulabumro.id/validasi?jenis=surat_jalan'
            . '&norec=' . urlencode($norec)
            . '&ttd=' . urlencode($ttd);
    }

    public function cetakSuratJalan(Request $r)
    {
        $profile   = $this->profile();
        $print     = false;
        $pageWidth = 950;

        $norec = $r->get('norec');

        if (!$norec) {
            abort(404, 'Parameter norec surat jalan tidak ditemukan.');
        }

        $res['identitas'] = DB::table('suratjalan_t as sj')
            ->leftJoin('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'sj.noregistrasifk')
            ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg_buat', 'pg_buat.id', '=', 'sj.petugasfk')
            ->leftJoin('jabatan_m as jb_buat', 'jb_buat.id', '=', 'pg_buat.jabatan1fk')
            ->leftJoin('pegawai_m as pg_bawa', 'pg_bawa.id', '=', 'sj.pegawaibawafk')
            ->leftJoin('jabatan_m as jb_bawa', 'jb_bawa.id', '=', 'pg_bawa.jabatan1fk')
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

                'sj.petugasfk',
                'sj.namapetugas',

                'mt.namaperusahaan',

                'pg_buat.namalengkap as nama_petugas_master',
                'jb_buat.namajabatanulab as jabatan_petugas_master',

                'pg_bawa.namalengkap as nama_pembawa_master',
                'jb_bawa.namajabatanulab as jabatan_pembawa_master'
            )
            ->where('sj.statusenabled', true)
            ->where('sj.norec', $norec)
            ->first();

        if (!$res['identitas']) {
            abort(404, 'Data surat jalan tidak ditemukan.');
        }

        $res['alat'] = DB::table('suratjalandetail_t as sjd')
            ->select(
                'sjd.norec',
                'sjd.suratjalanfk',
                'sjd.noregistrasidetailfk',
                'sjd.nourut',
                'sjd.namabarang',
                'sjd.namamerk',
                'sjd.namatipe',
                'sjd.namaserialnumber',
                'sjd.jumlah',
                'sjd.satuan',
                'sjd.ket'
            )
            ->where('sjd.statusenabled', true)
            ->where('sjd.suratjalanfk', $norec)
            ->orderBy('sjd.nourut')
            ->get();

        $fotos = $res['alat']->isNotEmpty()
            ? DB::table('suratjalanfoto_t')
            ->where('statusenabled', true)
            ->where('suratjalanfk', $norec)
            ->whereIn('suratjalandetailfk', $res['alat']->pluck('norec'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('suratjalandetailfk')
            : collect();

        foreach ($res['alat'] as $item) {
            $item->foto = $fotos->get($item->norec, collect())->values();

            $item->foto_resolved = [];

            foreach ($item->foto as $foto) {
                $fotoPath = $this->resolveFotoSuratJalanPath($foto->filepath);

                if ($fotoPath) {
                    $item->foto_resolved[] = [
                        'path' => $fotoPath,
                        'caption' => $item->namabarang,
                        'sn' => $item->namaserialnumber,
                        'jenisfoto' => $foto->jenisfoto,
                        'namafile' => $foto->namafile,
                    ];
                }
            }
        }

        $res['lampiran'] = [];

        foreach ($res['alat'] as $item) {
            if (!empty($item->foto_resolved)) {
                foreach ($item->foto_resolved as $foto) {
                    $res['lampiran'][] = [
                        'path' => $foto['path'],
                        'caption' => $foto['caption'],
                        'sn' => $foto['sn'] ?? '-',
                        'jenisfoto' => $foto['jenisfoto'],
                        'namafile' => $foto['namafile'],
                    ];
                }
            }
        }

        $res['namaPembuat'] = $res['identitas']->namapetugas
            ?? $res['identitas']->nama_petugas_master
            ?? '-';

        $res['jabatanPembuat'] = $res['identitas']->jabatan_petugas_master
            ?? 'Petugas Registrasi';

        $res['namaPembawa'] = $res['identitas']->namapegawaibawa
            ?? $res['identitas']->nama_pembawa_master
            ?? '-';

        $res['jabatanPembawa'] = $res['identitas']->jabatan_pembawa_master
            ?? 'Petugas Pembawa';

        $res['pdf'] = $r['pdf'];

        $blade = 'report.registrasi.surat-jalan';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option('isPhpEnabled', true);
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            $pdf->setPaper('a4', 'portrait');
            $pdf->loadView($blade . '-dom', compact('profile', 'pageWidth', 'print', 'res'));

            $filename = $this->formatNamaFileSuratJalan($res['identitas']->nosuratjalan ?? null);
            return $pdf->stream($filename);
        }

        if (isset($r['storage'])) {
            $res['storage'] = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option('isPhpEnabled', true);
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            $pdf->setPaper('a4', 'portrait');
            $pdf->loadView($blade . '-dom', compact('profile', 'pageWidth', 'print', 'res'));
            return $pdf;
        }

        return view($blade, compact('profile', 'pageWidth', 'print', 'res'));
    }

    private function resolveFotoSuratJalanPath($path)
    {
        if (!$path) {
            return null;
        }

        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (substr($path, 0, 7) === 'http://' || substr($path, 0, 8) === 'https://') {
            return $path;
        }

        $cleanPath = ltrim($path, '/');

        $possiblePaths = [
            public_path($cleanPath),
            public_path('produk/' . $cleanPath),
            public_path('surat-jalan/' . $cleanPath),
            public_path('berkas-mitra/' . $cleanPath),
            public_path('berkas-customer/' . $cleanPath),
            public_path('images/' . $cleanPath),
            public_path('img/' . $cleanPath),
            public_path('storage/' . $cleanPath),
        ];

        foreach ($possiblePaths as $localPath) {
            if (File::exists($localPath)) {
                return $localPath;
            }
        }

        if (strpos($cleanPath, 'produk/') === 0) {
            return $this->publicFileUrl('produk', substr($cleanPath, strlen('produk/')));
        }

        if (strpos($cleanPath, 'surat-jalan/') === 0) {
            return $this->publicFileUrl('surat-jalan', substr($cleanPath, strlen('surat-jalan/')));
        }

        if (strpos($cleanPath, 'berkas-mitra/') === 0) {
            return $this->publicFileUrl('berkas-mitra', substr($cleanPath, strlen('berkas-mitra/')));
        }

        if (strpos($cleanPath, 'berkas-customer/') === 0) {
            return $this->publicFileUrl('berkas-customer', substr($cleanPath, strlen('berkas-customer/')));
        }

        return $this->publicFileUrl('produk', $cleanPath);
    }
}
