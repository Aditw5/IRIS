<?php

namespace App\Http\Controllers\Asman;

use App\Http\Controllers\Controller;
use App\Models\Master\PaketKalibrasi;
use App\Services\CalibrationNotesTranslationService;
use App\Traits\ProtectsVerificationExcelPages;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Arr;
// use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PgSql\Lob;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Builder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Support\Str;

class AsmanCtrl extends Controller
{
    use \App\Traits\HandlesWorksheetAttachments;
    use Valet;
    use ProtectsVerificationExcelPages;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function listMitraAsmanGrid(Request $r)
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
                'mtr.lokasirepair',
                'mtr.norec as iddetail',
                'mtr.statusorder',
                'mtr.jenisorder',
                'mtr.isstandarulab',
                'mtr.iskalibrasiinternal',
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
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
        if (isset($r['statusorder']) && $r['statusorder'] != '') {
            $data = $data->where('mtr.statusorder', '=', $r['statusorder']);
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

            foreach ($detailList as $detail) {
                if (
                    ($item->jenisorder === 'kalibrasi' && $detail->tglsetujumanagerlembarkerja !== null) ||
                    ($item->jenisorder === 'repair' && $detail->tglsetujumanagerlaporanrepair !== null)
                ) {
                    $item->jumlahselesai++;
                }
            }
            $item->jumlahbelumselesai = $item->jumlahdetail - $item->jumlahselesai;
        }

        return $this->respond($data);
    }

    public function getAlatAsman(Request $r)
    {
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
            ->leftjoin('sublingkupkalibrasi_m as slk', 'slk.id', '=', 'mtrd.sublingkupfk')
            ->leftjoin('jenissurkes_m as js', 'js.id', '=', 'mtrd.statussurkesfk')
            ->select(
                DB::raw("CASE WHEN EXISTS (
                SELECT 1
                FROM kalibrasi_ai kai2
                WHERE kai2.noorder = mtrd.noorderalat
            ) THEN 'ADA' ELSE '-' END as tanda_kalibrasi_ai"),
                'mtr.norec',
                'mtr.isstandarulab',
                'mtr.iskalibrasiinternal',
                'mtrd.versisertifikat',
                'mtrd.versilaporanrepair',
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
                'mtrd.tanggalmulai',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
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
                'mtrd.noorderalat',
                'mtrd.setujuilembarkerjaasman',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.asmansetujulembarkerjafk',
                'mtrd.statusorderasman',
                'mtrd.tglverifpelaksana',
                'mtrd.penyeliasetujulaporanrepairfk',
                'mtrd.tglsetujupenyelialaporanrepair',
                'mtrd.asmansetujulaporanrepairfk',
                'mtr.jenisorder',
                'mtrd.sublingkupfk',
                'mtrd.statusrepairfk',
                'mtrd.isverifikasi',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.managersetujulaporanrepairfk',
                'mtrd.istolaksertiasman',
                'mtrd.alasanpenolakansertiasman',
                'slk.namasublingkup',
                'js.jenissurkes',
                'js.id as jenissurkesfk'
            )
            ->where('mtr.statusorder', 1)
            ->where('mtr.iskaji', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true);

        if (isset($r['dari']) && $r['dari'] != '') {
            $data = $data->where(function ($q) use ($r) {
                $q->where(function ($sub) use ($r) {
                    $sub->where('mtr.jenisorder', 'repair')
                        ->where(DB::raw("mtrd.tglsetujupenyelialaporanrepair::date"), '>=', $r->dari);
                })->orWhere(function ($sub) use ($r) {
                    $sub->where('mtr.jenisorder', '!=', 'repair')
                        ->where(DB::raw("mtrd.tglsetujupenyelialembarkerja::date"), '>=', $r->dari);
                });
            });
        }

        if (isset($r['sampai']) && $r['sampai'] != '') {
            $data = $data->where(function ($q) use ($r) {
                $q->where(function ($sub) use ($r) {
                    $sub->where('mtr.jenisorder', 'repair')
                        ->where(DB::raw("mtrd.tglsetujupenyelialaporanrepair::date"), '<=', $r->sampai);
                })->orWhere(function ($sub) use ($r) {
                    $sub->where('mtr.jenisorder', '!=', 'repair')
                        ->where(DB::raw("mtrd.tglsetujupenyelialembarkerja::date"), '<=', $r->sampai);
                });
            });
        }

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';

            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('mtrd.noorderalat', 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namamerk, mmp.namamerk)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namatipe, mmp.namatipe)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber)"), 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm);
            });
        }

        if (isset($r['statusorderasman']) && $r['statusorderasman'] != '') {
            $data = $data->where('mtrd.statusorderasman', '=', $r['statusorderasman']);
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
            $tglMulaiAsman = !empty($item->tanggalmulai)
                ? Carbon::parse($item->tanggalmulai)
                : null;

            $tglSelesaiAsman = !empty($item->tglsetujumanagerlembarkerja)
                ? Carbon::parse($item->tglsetujumanagerlembarkerja)
                : null;

            $diffInSeconds = $calcWorkingSeconds($tglMulaiAsman, $tglSelesaiAsman);

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

    public function getLembarKerjaAI(Request $request)
    {
        $data = DB::table('kalibrasi_ai as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noorderalat', '=', 'lk.noorder')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis', 'mtrd.norec as detailregistraifk')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaAsman(Request $request)
    {
        $data = DB::table('lembarkerja_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaSumber(Request $request)
    {
        $data = DB::table('lembarkerjasumber_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaClamp(Request $request)
    {
        $data = DB::table('lembarkerjaclamp_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaVibrationMeter(Request $request)
    {
        $data = DB::table('lembarkerjavibration_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaVibrationCalibrator(Request $request)
    {
        $data = DB::table('lembarkerjavibrationcalibrator_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaAccellerometer(Request $request)
    {
        $data = DB::table('lembarkerjaaccellerometer_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaTekanan(Request $request)
    {
        $hasGroup = DB::table('lembarkerjatekanan_t as lk')
            ->where('lk.detailregistraifk', $request->norecdetail)
            ->where('lk.statusenabled', true)
            ->whereNotNull('lk.group')
            ->whereRaw("TRIM(COALESCE(lk.group,'')) <> ''")
            ->exists();

        $query = DB::table('lembarkerjatekanan_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true);

        if ($hasGroup) {
            $query->orderBy('lk.group', 'ASC')
                ->orderByRaw('CAST(lk.no AS INTEGER) ASC');
        } else {
            $query->orderByRaw('CAST(lk.no AS INTEGER) ASC');
        }

        $data = $query->get();

        return $this->respond($data);
    }

    public function getLembarKerjaCLampMeter(Request $request)
    {
        $data = DB::table('lembarkerjaclampmeter_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaMeterSumber(Request $request)
    {
        $data = DB::table('lembarkerjametersumber_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaClampMeterSumber(Request $request)
    {
        $data = DB::table('lembarkerjaclampmetersumber_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }


    public function getLembarKerjaThermometerInfrared(Request $request)
    {
        $data = DB::table('lembarkerjathermometerinfrared_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaThermalImager(Request $request)
    {
        $data = DB::table('lembarkerjathermalimager_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaThermohygrometer(Request $request)
    {
        $data = DB::table('lembarkerjathermohygrometer_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaDryblock(Request $request)
    {
        $data = DB::table('lembarkerjadryblock_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaCaliperMicrometer(Request $request)
    {
        $data = DB::table('lembarkerjacalipermicrometer_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaOutsideMicrometer(Request $request)
    {
        $data = DB::table('lembarkerjaoutsidemicrometer_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaOscilloscope(Request $request)
    {
        $data = DB::table('lembarkerjaoscilloscope_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaMicrometerHead(Request $request)
    {
        $data = DB::table('lembarkerjamicrometerhead_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaDialIndicator(Request $request)
    {
        $data = DB::table('lembarkerjadialindicator_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaBoreGauge(Request $request)
    {
        $data = DB::table('lembarkerjaboregauge_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaContinuitySource(Request $request)
    {
        $data = DB::table('lembarkerjacontinuitysource_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaInsulationSource(Request $request)
    {
        $data = DB::table('lembarkerjainsulationsource_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.jenis', 'ASC')
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaThermocoupleRTDSimulator(Request $request)
    {
        $data = DB::table('lembarkerjathermocouplertdsimulator_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaTachometer(Request $request)
    {
        $data = DB::table('lembarkerjatachometer_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaInsulationMeterResistansi(Request $request)
    {
        $data = DB::table('lembarkerjainsulationmeterresistansi_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaInsulationTester(Request $request)
    {
        $data = DB::table('lembarkerjainsulationtester_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaTorqueWrench(Request $request)
    {
        $data = DB::table('lembarkerjatorquewrench_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaTemperatureIndicator(Request $request)
    {
        $data = DB::table('lembarkerjatemperatureindicator_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaUltrasonicThickness(Request $request)
    {
        $data = DB::table('lembarkerjaultrasonicthickness_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaThicknessGauge(Request $request)
    {
        $data = DB::table('lembarkerjathicknessgauge_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function getLembarKerjaFeelerGauge(Request $request)
    {
        $data = DB::table('lembarkerjafeelergauge_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('lk.*', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->orderBy('lk.group', 'ASC')
            ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
            ->get();

        return $this->respond($data);
    }

    public function setujuiSertifikatAsman(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['verif'];
            $timestamp = now();

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'setujuilembarkerjaasman' => true,
                    'asmansetujulembarkerjafk' => $this->getPegawaiId(),
                    'tglsetujuasmanlembarkerja' => now(),
                    'statusorderasman' => 2
                ]);

            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $VI['norec'])->first();
            $noorderalat = $detail->noorderalat ?? '-';
            $waktuVerif = $detail->tglsetujuasmanlembarkerja ?? $timestamp;

            try {
                $waktuVerifStr = \Carbon\Carbon::parse($waktuVerif)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuVerifStr = date('d-m-Y H:i', strtotime($waktuVerif)) . ' WIB';
            }

            $norecReg = null;
            if (!$norecReg && isset($VI['norec'])) {
                $detail = DB::table('mitraregistrasidetail_t')->where('norec', $VI['norec'])->first();
                $norecReg = $detail->noregistrasifk ?? null;
            }

            $regis = null;
            if ($norecReg) {
                $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
            }

            $username = 'Mahzumi';
            $profileId = $regis->kdprofile ?? 1;
            $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);

            $link = url('/service/penyelia/cetak-sertifikat-lembar-kerja') . '?pdf=true'
                . '&norec=' . $norecReg
                . '&norec_detail=' . $VI['norec']
                . '&user=' . urlencode($username)
                . '&kdprofile=' . $profileId
                . '&token=' . $token;

            $shortLink = $this->shortLink($link);

            $asman = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaAsman = $asman->namalengkap ?? '-';

            $recipients = [];

            // PENYELIA
            $penyeliaId = $detail->penyeliateknikfk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                if ($penyelia) {
                    $recipients[$penyelia->id] = [
                        'id' => $penyelia->id,
                        'nama' => $penyelia->namalengkap ?? '-',
                        'nohp' => $penyelia->nohandphone ?? null,
                        'role' => 'Penyelia Teknik',
                        'urlform' => 'module-dashboard-penyelia',
                    ];
                }
            }

            // PELAKSANA
            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            if ($pelaksanaId) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanaId)->first();
                if ($pelaksana) {
                    $recipients[$pelaksana->id] = [
                        'id' => $pelaksana->id,
                        'nama' => $pelaksana->namalengkap ?? '-',
                        'nohp' => $pelaksana->nohandphone ?? null,
                        'role' => 'Pelaksana Teknik',
                        'urlform' => 'module-dashboard-pelaksana',
                    ];
                }
            }

            // MANAGER
            $namaManager = $detail->namamanager ?? null;
            if ($namaManager) {
                $manager = DB::table('pegawai_m')->where('namalengkap', $namaManager)->first();
                if ($manager) {
                    $recipients[$manager->id] = [
                        'id' => $manager->id,
                        'nama' => $manager->namalengkap ?? '-',
                        'nohp' => $manager->nohandphone ?? null,
                        'role' => 'Manager',
                        'urlform' => 'module-dashboard-manager',
                    ];
                }
            }

            foreach ($recipients as $recipient) {
                $namaPenerima = $recipient['nama'] ?? '-';
                $nohpPenerima = $recipient['nohp'] ?? null;
                $rolePenerima = $recipient['role'] ?? '-';
                $urlForm = $recipient['urlform'] ?? 'module-dashboard';

                $pesan = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenerima\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan persetujuan Draft Sertifikat Kalibrasi oleh Asman NMW : *$namaAsman*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                if ($nohpPenerima) {
                    $this->kirimWhatsappNotifikasi($nohpPenerima, $pesan);
                }

                $notif = new \App\Models\Master\ListNotif();
                $notif->norec = (string) \Illuminate\Support\Str::uuid();
                $notif->norec_trans = $VI['norec'] ?? (string) \Illuminate\Support\Str::uuid();
                $notif->judul = 'Persetujuan Draft Sertifikat Kalibrasi';
                $notif->jenis = 'Sertifikat Kalibrasi';
                $notif->pegawaifk = $recipient['id'];
                $notif->namapegawai = $namaPenerima;
                $notif->keterangan = 'Draft Sertifikat Kalibrasi No Order ' . $noorderalat . ' telah disetujui oleh Asman NMW ' . $namaAsman . '.';
                $notif->tgl = now();
                $notif->tgl_string = now()->format('d-m-Y H:i');
                $notif->urlform = $urlForm;
                $notif->params = json_encode([
                    'norec' => $norecReg,
                    'norec_detail' => $VI['norec'],
                    'noorderalat' => $noorderalat,
                    'role_tujuan' => $rolePenerima,
                ]);
                $notif->dataarray = json_encode([
                    'registrasi_norec' => $norecReg,
                    'detail_norec' => $VI['norec'],
                    'noorderalat' => $noorderalat,
                    'namaAsman' => $namaAsman,
                    'waktuVerif' => $waktuVerifStr,
                    'shortLink' => $shortLink,
                ]);
                $notif->statusenabled = true;
                $notif->isread = false;
                $notif->save();

                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->withHeaders([
                            'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                            'Accept' => 'application/json',
                        ])
                        ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                            'norec' => $notif->norec,
                            'norec_trans' => $notif->norec_trans,
                            'judul' => $notif->judul,
                            'jenis' => $notif->jenis,
                            'idPegawai' => $notif->pegawaifk,
                            'namapegawai' => $notif->namapegawai,
                            'pesanNotifikasi' => $notif->keterangan,
                            'tgl' => $notif->tgl,
                            'tgl_string' => $notif->tgl_string,
                            'urlForm' => $notif->urlform,
                            'params' => json_decode($notif->params, true),
                            'dataArray' => json_decode($notif->dataarray, true),
                        ]);
                } catch (\Exception $e) {
                    // abaikan jika socket gagal, notif DB tetap tersimpan
                }
            }

            $transMessage = "Simpan Setujui Sertifikat Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getAsmanDetail(Request $r)
    {
        $jabatanIds = [17, 20, 3, 7, 8, 4, 19, 9, 5, 18, 13, 15, 12, 16, 14];

        $pegawaiJakarta = DB::table('pegawai_m as pg')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->select(
                'pg.id as pegawai_id',
                'pg.namalengkap',
                'pg.jabatan1fk',
                'jb.id as jabatan_id',
                'jb.namajabatanulab'
            )
            ->where('pg.statusenabled', true)
            ->whereIn('jb.id', $jabatanIds)
            ->where('pg.lokasikalibrasifk', 1)
            ->where('jb.statusenabled', true);

        if (isset($r['limit']) && $r['limit'] != '') {
            $pegawaiJakarta->limit($r['limit']);
        }

        $pegawaiJakarta->orderBy('jb.namajabatanulab');
        $pegawaiJakarta = $pegawaiJakarta->get();

        $pegawaiGresik = DB::table('pegawai_m as pg')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->select(
                'pg.id as pegawai_id',
                'pg.namalengkap',
                'pg.jabatan1fk',
                'jb.id as jabatan_id',
                'jb.namajabatanulab'
            )
            ->where('pg.statusenabled', true)
            ->whereIn('jb.id', $jabatanIds)
            ->where('pg.lokasikalibrasifk', 2)
            ->where('jb.statusenabled', true);

        if (isset($r['limit']) && $r['limit'] != '') {
            $pegawaiGresik->limit($r['limit']);
        }

        $pegawaiGresik->orderBy('jb.namajabatanulab');
        $pegawaiGresik = $pegawaiGresik->get();

        $res['pegawaiJakarta'] = $pegawaiJakarta;
        $res['pegawaiGresik'] = $pegawaiGresik;
        return $this->respond($res);
    }

    public function LayananVerif(Request $r)
    {
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
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtrd.lokasirepairfk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('paketkalibrasi_m as pk', 'pk.id', '=', 'mtr.paketkalibrasi')
            ->leftJoin('vendor_m as vm', 'vm.id', '=', 'mtrd.vendorkalibrasifk')
            ->leftJoin('jenissurkes_m as js', 'js.id', '=', 'mtrd.statussurkesfk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.alasanpenolakanregis',
                'mtrd.tanggalpenolakanregis',
                'mtrd.tanggalmulai as tglmulai',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mtr.tanggalmulai',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
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
                'mtrd.vendorkalibrasifk',
                'mtrd.isVendor',
                'vm.namavendor',
                'js.jenissurkes',
                'js.id as jenissurkesfk',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $r['norec_pd']);

        if ($r->filled('norecdetail') && $r['norecdetail'] != 'undefined') {
            $data->where('mtrd.norec', $r['norecdetail']);
        }

        $data = $data->orderByDesc('mmp.namaproduk')->get();

        $allIsVendor = $data->every(function ($item) {
            return $item->isVendor == true;
        });
        $alatPerRegistrasi = [];
        foreach ($data as $item) {
            $alatPerRegistrasi[$item->norec][] = $item;
        }

        foreach ($data as $d) {
            $alatList = $alatPerRegistrasi[$d->norec] ?? [];
            $countPerLingkup = [];

            foreach ($alatList as $alat) {
                $lingkup = $alat->lingkupfk;
                if (!$lingkup) {
                    continue;
                }
                if (!isset($countPerLingkup[$lingkup])) {
                    $countPerLingkup[$lingkup] = 0;
                }
                $countPerLingkup[$lingkup]++;
            }

            $maxAlat = 0;
            foreach ($countPerLingkup as $total) {
                if ($total > $maxAlat) {
                    $maxAlat = $total;
                }
            }

            $hariPaket   = $d->hari_paket ?? 0;
            $totalDurasi = $maxAlat * $hariPaket;
            $d->totalDurasi = $totalDurasi;

            $tglDasar = $d->tanggalmulai;
            if ($tglDasar && $totalDurasi > 0) {
                $d->tanggalSelesai = Carbon::parse($tglDasar)
                    ->addDays($totalDurasi)
                    ->format('d-m-Y');
            } else {
                $d->tanggalSelesai = null;
            }
        }
        $lokasiId = null;
        if ($data->count() > 0) {
            $lokasiId = $data[0]->lokasikalibrasifk ?? $data[0]->lokasirepairfk ?? null;
        }
        $backlogQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                DB::raw("
                    MAX(
                        (COALESCE(mtrd.tanggalmulai, mtr.tanggalmulai))::date
                        + COALESCE(mtrd.durasikalbrasi, 0)
                    ) as tglselesai_maks
                ")
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->whereRaw('COALESCE(mtrd.tanggalmulai, mtr.tanggalmulai) IS NOT NULL')
            ->where('mtr.norec', '!=', $r['norec_pd'])
            ->whereNotNull('mtrd.lingkupkalibrasifk')
            ->whereRaw("
            (
                CASE
                    WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                        THEN mtrd.tglsetujumanagerlaporanrepair
                    ELSE mtrd.tglsetujumanagerlembarkerja
                END
            ) IS NULL
        ");

        if ($lokasiId) {
            $backlogQuery->whereRaw('COALESCE(mtrd.lokasikajifk, mtrd.lokasirepairfk) = ?', [$lokasiId]);
        }

        $backlog = $backlogQuery
            ->groupBy('lp.id', 'lp.lingkupkalibrasi')
            ->get();
        $estimasiLingkup = [];

        foreach ($data as $row) {
            if (!$row->lingkupfk) {
                continue;
            }

            $back = $backlog->firstWhere('lingkupfk', $row->lingkupfk);
            $tglSelesaiPrev = $back && $back->tglselesai_maks
                ? Carbon::parse($back->tglselesai_maks)
                : null;
            if ($tglSelesaiPrev) {
                $start = $tglSelesaiPrev->copy()->addDay();
            } else {
                $start = null;
                if (!empty($row->tglmulai)) {
                    $start = Carbon::parse($row->tglmulai);
                } elseif (!empty($row->tanggalmulai)) {
                    $start = Carbon::parse($row->tanggalmulai);
                } elseif (!empty($row->tglregistrasi)) {
                    $start = Carbon::parse($row->tglregistrasi);
                }
            }
            $dur = (int)($row->durasikalbrasi ?? 0);
            if ($dur <= 0) {
                $dur = (int)($row->hari_paket ?? 0);
            }

            $end = $start && $dur > 0
                ? $start->copy()->addDays($dur)
                : null;

            $key = $row->lingkupfk;

            if (!isset($estimasiLingkup[$key])) {
                $estimasiLingkup[$key] = [
                    'lingkupfk'        => $row->lingkupfk,
                    'lingkupkalibrasi' => $row->lingkupkalibrasi,
                    'backlog_selesai'  => $tglSelesaiPrev ? $tglSelesaiPrev->format('Y-m-d') : null,
                    'start'            => $start ? $start->format('Y-m-d') : null,
                    'end'              => $end ? $end->format('Y-m-d') : null,
                ];
            } else {
                if ($start) {
                    if (
                        empty($estimasiLingkup[$key]['start']) ||
                        Carbon::parse($estimasiLingkup[$key]['start'])->gt($start)
                    ) {
                        $estimasiLingkup[$key]['start'] = $start->format('Y-m-d');
                    }
                }
                if ($end) {
                    if (
                        empty($estimasiLingkup[$key]['end']) ||
                        Carbon::parse($estimasiLingkup[$key]['end'])->lt($end)
                    ) {
                        $estimasiLingkup[$key]['end'] = $end->format('Y-m-d');
                    }
                }
            }
        }

        $estimasiLingkup = array_values($estimasiLingkup);

        $jumlahKalibrasiLingkupJakarta = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'lp.lingkupkalibrasi as lingkup',
                DB::raw("
                COUNT(*) -
                COUNT(
                    CASE
                        WHEN (
                            CASE
                                WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                    THEN mtrd.tglsetujumanagerlaporanrepair
                                ELSE mtrd.tglsetujumanagerlembarkerja
                            END
                        ) IS NOT NULL
                        THEN 1
                    END
                ) as jumlahbelumselesai
            ")
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->whereRaw('COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?', [1])
            ->whereNotNull('mtrd.lingkupkalibrasifk')
            ->groupBy('lp.lingkupkalibrasi')
            ->get();

        $jumlahKalibrasiLingkupGresik = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'lp.lingkupkalibrasi as lingkup',
                DB::raw("
                COUNT(*) -
                COUNT(
                    CASE
                        WHEN (
                            CASE
                                WHEN LOWER(COALESCE(mtr.jenisorder,'')) = 'repair'
                                    THEN mtrd.tglsetujumanagerlaporanrepair
                                ELSE mtrd.tglsetujumanagerlembarkerja
                            END
                        ) IS NOT NULL
                        THEN 1
                    END
                ) as jumlahbelumselesai
            ")
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->whereRaw('COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?', [2])
            ->whereNotNull('mtrd.lingkupkalibrasifk')
            ->groupBy('lp.lingkupkalibrasi')
            ->get();

        $result                 = [];
        $result['estimasiLingkup']               = $estimasiLingkup;
        $result['length']                        = count($data);
        $result['detail']                        = $data;
        $result['jumlahKalibrasiLingkupJakarta'] = $jumlahKalibrasiLingkupJakarta;
        $result['jumlahKalibrasiLingkupGresik']  = $jumlahKalibrasiLingkupGresik;
        $result['allIsVendor']                   = $allIsVendor;
        $result['as']                            = '@adit';

        return $this->respond($result);
    }


    public function detailProduk(Request $r)
    {
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
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.statusorderasman',
                'mtrd.statusorderpenyelia',
                'mtrd.statusorderpelaksana',
                'mtr.tglverifasman',
                'mtrd.tglverifpenyelia',
                'mtrd.tglverifpelaksana',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.pelaksanaisilembarkerjafk',
                'mtrd.tglisilembarkerjapenyelia',
                'mtrd.penyeliaisilembarkerjafk',
                'mtrd.tglisilaporanrepairpelaksana',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'pg4.id as pelaksanaisilembarkerjafk',
                'pg4.namalengkap as pelaksanaisilembarkerja',
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
                'pg5.id as penyeliasetujuilembarkerjafk',
                'pg5.namalengkap as penyeliasetujuilembarkerja',
                'pg6.id as asmansetujuilembarkerjafk',
                'pg6.namalengkap as asmansetujuilembarkerja',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.managersetujulembarkerjafk',
                'mtrd.tglisilaporanrepairpenyelia',
                'mtrd.tglsetujupenyelialaporanrepair',
                'mtrd.tglsetujuasmanlaporanrepair',
                'mtrd.tglsetujumanagerlaporanrepair',
                'pg7.id as managersetujuilembarkerjafk',
                'pg7.namalengkap as managersetujuilembarkerja',
                'pg8.id as pelaksanaisilaporanrepairfk',
                'pg8.namalengkap as pelaksanaisilaporanrepair',
                'pg9.id as penyeliaisilaporanrepairfk',
                'pg9.namalengkap as penyeliaisilaporanrepair',
                'pg10.id as penyeliasetujulaporanrepairfk',
                'pg10.namalengkap as penyeliasetujulaporanrepair',
                'pg11.id as asmansetujulaporanrepairfk',
                'pg11.namalengkap as asmansetujulaporanrepair',
                'pg12.id as managersetujulaporanrepairfk',
                'pg12.namalengkap as managersetujulaporanrepair',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_pd'])
            ->orderByDesc('mmp.namaproduk')
            ->get();

        $timeline = [];

        foreach ($data as $item) {
            if (!is_null($item->tglsetujumanagerlaporanrepair)) {
                $timeline[] = [
                    'date' => $item->tglsetujumanagerlaporanrepair,
                    'type' => 'Laporan Repair Disetujui Oleh Manager',
                    'nama' => $item->managersetujulaporanrepair ?? '-',
                ];
            }
            if (!is_null($item->tglsetujuasmanlaporanrepair)) {
                $timeline[] = [
                    'date' => $item->tglsetujuasmanlaporanrepair,
                    'type' => 'Laporan Repair Disetujui Oleh Asman',
                    'nama' => $item->asmansetujulaporanrepair ?? '-',
                ];
            }
            if (!is_null($item->tglsetujupenyelialaporanrepair)) {
                $timeline[] = [
                    'date' => $item->tglsetujupenyelialaporanrepair,
                    'type' => 'Laporan Repair Disetujui Oleh Penyelia',
                    'nama' => $item->penyeliasetujulaporanrepair ?? '-',
                ];
            }
            if (!is_null($item->tglisilaporanrepairpenyelia)) {
                $timeline[] = [
                    'date' => $item->tglisilaporanrepairpenyelia,
                    'type' => 'Diisi Laporan Repair Oleh Penyelia',
                    'nama' => $item->penyeliaisilaporanrepair ?? '-',
                ];
            }
            if (!is_null($item->tglisilaporanrepairpelaksana)) {
                $timeline[] = [
                    'date' => $item->tglisilaporanrepairpelaksana,
                    'type' => 'Diisi Laporan Repair Oleh Pelaksana',
                    'nama' => $item->pelaksanaisilaporanrepair ?? '-',
                ];
            }
            if (!is_null($item->tglsetujumanagerlembarkerja)) {
                $timeline[] = [
                    'date' => $item->tglsetujumanagerlembarkerja,
                    'type' => 'Sertifikat Di Setujui Oleh Manager',
                    'nama' => $item->managersetujuilembarkerja ?? '-',
                ];
            }
            if (!is_null($item->tglsetujuasmanlembarkerja)) {
                $timeline[] = [
                    'date' => $item->tglsetujuasmanlembarkerja,
                    'type' => 'Sertifikat Di Setujui Oleh Asman',
                    'nama' => $item->asmansetujuilembarkerja ?? '-',
                ];
            }
            if (!is_null($item->tglsetujupenyelialembarkerja)) {
                $timeline[] = [
                    'date' => $item->tglsetujupenyelialembarkerja,
                    'type' => 'Sertifikat Di Setujui Oleh Penyelia',
                    'nama' => $item->penyeliasetujuilembarkerja ?? '-',
                ];
            }
            if (!is_null($item->tglverifasman)) {
                $timeline[] = [
                    'date' => $item->tglverifasman,
                    'type' => 'Diverifikasi Oleh Asman',
                    'nama' => $item->asamanverifikasi ?? '-',
                ];
            }
            if (!is_null($item->tglverifpenyelia)) {
                $timeline[] = [
                    'date' => $item->tglverifpenyelia,
                    'type' => 'Diverifikasi Oleh Penyelia Teknik',
                    'nama' => $item->penyeliateknik ?? '-',
                ];
            }
            if (!is_null($item->tglverifpelaksana)) {
                $timeline[] = [
                    'date' => $item->tglverifpelaksana,
                    'type' => 'Diverifikasi Oleh Pelaksana Teknik',
                    'nama' => $item->pelaksanateknik ?? '-',
                ];
            }
            if (!is_null($item->tglisilembarkerjapelaksana)) {
                $timeline[] = [
                    'date' => $item->tglisilembarkerjapelaksana,
                    'type' => 'Diisi Lembar Kerja Oleh Pelaksana Teknik',
                    'nama' => $item->pelaksanateknik ?? '-',
                ];
            }
            if (!is_null($item->tglisilembarkerjapenyelia)) {
                $timeline[] = [
                    'date' => $item->tglisilembarkerjapenyelia,
                    'type' => 'Diisi Lembar Kerja Oleh Penyelia Teknik',
                    'nama' => $item->penyeliateknik ?? '-',
                ];
            }
        }

        usort($timeline, function ($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });

        $result = [
            'length' => count($data),
            'timeline' => $timeline,
            'as' => '@adit'
        ];

        return $this->respond($result);
    }

    public function saveVerifItem(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['veriItem'];

            if ($VI['lokasirepair'] != null) {
                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $VI['norec_detail'])
                    ->update([
                        'iskaji' => true,
                        'statusenabled' => true,
                        'lokasirepairfk' => $VI['lokasirepair'],
                        'penyeliateknikfk' => $VI['penyeliateknik'],
                        'pelaksanateknikfk' => $VI['pelaksana'],
                        'tanggalpenolakanregis' => null,
                        'alasanpenolakanregis' => null,
                        'vendorkalibrasifk' => $VI['vendorfk'],
                    ]);
            } else {
                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $VI['norec_detail'])
                    ->update([
                        'iskaji' => true,
                        'statusenabled' => true,
                        'lokasikajifk' => $VI['lokasikalibrasi'] ?? null,
                        'lingkupkalibrasifk' => $VI['lingkupkalibrasi'] ?? null,
                        'penyeliateknikfk' => $VI['penyeliateknik'] ?? null,
                        'pelaksanateknikfk' => $VI['pelaksana'] ?? null,
                        'durasikalbrasi' => $VI['durasikalbrasi'] ?? null,
                        'tanggalpenolakanregis' => null,
                        'alasanpenolakanregis' => null,
                        'vendorkalibrasifk' => $VI['vendorfk'],
                    ]);
            }

            $transMessage = "Simpan Verif Item Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function updatePaket(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r->input('updatePaket', []);

            if (empty($VI['norec'])) {
                throw new \Exception('norec registrasi tidak boleh kosong');
            }

            $norec     = $VI['norec'];
            $paketId   = $VI['paketkalibrasi'] ?? null;

            $durasikalibrasi = null;
            if (!empty($paketId)) {
                $paket = PaketKalibrasi::find($paketId);
                if ($paket) {
                    $durasikalibrasi = $paket->hari;
                }
            }

            DB::table('mitraregistrasi_t')
                ->where('norec', $norec)
                ->update([
                    'paketkalibrasi' => $paketId,
                ]);

            if ($durasikalibrasi !== null) {
                $detailUpdate['durasikalbrasi'] = $durasikalibrasi;
            }

            if ($durasikalibrasi !== null) {
                DB::table('mitraregistrasidetail_t')
                    ->where('noregistrasifk', $norec)
                    ->update(['durasikalbrasi' => $durasikalibrasi]);
            }


            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => "@adit",
                ],
            ];
            $transMessage = "Simpan Update Paket Sukses";
        } catch (\Exception $e) {
            DB::rollBack();

            $result = [
                "status" => 400,
                "result" => $e->getMessage(),
            ];
            $transMessage = "Simpan Gagal";
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function updateTglMulai(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r->input('updatePaket', []);

            if (empty($VI['norec'])) {
                throw new \Exception('norec registrasi tidak boleh kosong');
            }

            $norec     = $VI['norec'];
            $lingkupfk = $VI['lingkupfk'] ?? null;
            $tglMulai  = $VI['tanggalmulai'] ?? null;

            $detailUpdate = [];

            if (!empty($tglMulai)) {
                $detailUpdate['tanggalmulaiestimasi'] = $tglMulai;
            }

            if (!empty($detailUpdate)) {
                $query = DB::table('mitraregistrasidetail_t')
                    ->where('noregistrasifk', $norec);
                if (!empty($lingkupfk)) {
                    $query->where('lingkupkalibrasifk', $lingkupfk);
                }
                $query->update($detailUpdate);
            }

            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => "@adit",
                ],
            ];
            $transMessage = "Simpan Tanggal Mulai per Lingkup Sukses";
        } catch (\Exception $e) {
            DB::rollBack();

            $result = [
                "status" => 400,
                "result" => $e->getMessage(),
            ];
            $transMessage = "Simpan Gagal";
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }


    private function nullableIntegerId($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    public function saveVerif(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['verif'];
            DB::transaction(function () use ($VI) {
                $now   = now();
                $tahun = $now->format('y');
                $bulan = $now->format('m');

                $registrasi = DB::table('mitraregistrasi_t')
                    ->select('jenisorder', 'lokasikalibrasi', 'lokasirepair')
                    ->where('norec', $VI['norec'])
                    ->where('statusenabled', true)
                    ->lockForUpdate()
                    ->first();

                if (!$registrasi) {
                    throw new \Exception('Data registrasi tidak ditemukan');
                }

                $jenisOrder = strtolower(trim((string) $registrasi->jenisorder));
                if (!in_array($jenisOrder, ['kalibrasi', 'repair'], true)) {
                    throw new \Exception('Jenis order tidak valid');
                }

                $lokasiId = $this->nullableIntegerId(
                    $jenisOrder === 'repair'
                        ? $registrasi->lokasirepair
                        : $registrasi->lokasikalibrasi
                );

                if ($lokasiId === null) {
                    throw new \Exception('Lokasi ' . ($jenisOrder === 'repair' ? 'repair' : 'kalibrasi') . ' belum ditentukan');
                }

                $lokasi = $this->getLokasiNomor($lokasiId);
                $lokasiInisial = $lokasi->inisial_nomor;
                $servicePrefix = $jenisOrder === 'repair' ? 'R' : '';

                $details = DB::table('mitraregistrasidetail_t')
                    ->select('norec', 'lingkupkalibrasifk')
                    ->where('noregistrasifk', $VI['norec'])
                    ->where('statusenabled', true)
                    ->orderBy('norec')
                    ->get();

                $byLingkup = $details->groupBy('lingkupkalibrasifk');
                $lingkupIds = $byLingkup->keys()
                    ->map(function ($lingkupId) {
                        return $this->nullableIntegerId($lingkupId);
                    })
                    ->filter(function ($lingkupId) {
                        return $lingkupId !== null;
                    })
                    ->unique()
                    ->values();
                $lingkupRows = $lingkupIds->isNotEmpty()
                    ? DB::table('lingkupkalibrasi_m')
                        ->where('statusenabled', true)
                        ->whereIn('id', $lingkupIds)
                        ->get()
                        ->keyBy('id')
                    : collect();

                foreach ($byLingkup as $lingkupId => $group) {
                    $lingkupInisial = '';
                    $lingkupId = $this->nullableIntegerId($lingkupId);
                    if ($lingkupId === null || !($lingkup = $lingkupRows->get($lingkupId))) {
                        throw new \Exception('Lingkup kalibrasi detail belum ditentukan atau tidak valid');
                    }
                    if (strcasecmp($lingkup->lingkupkalibrasi, 'vendor') === 0) {
                        $lingkupInisial = strtoupper(mb_substr($lingkup->lingkupkalibrasi, 0, 2));
                    } else {
                        $lingkupInisial = $lingkup->inisial
                            ?? strtoupper(mb_substr($lingkup->lingkupkalibrasi, 0, 1));
                    }

                    $yearlyPrefix = "{$servicePrefix}{$lokasiInisial}{$lingkupInisial}-{$tahun}.";
                    $monthlyPrefix = "{$yearlyPrefix}{$bulan}.";

                    $this->lockNomorTransaksi('nomor-order', [$yearlyPrefix]);

                    $maxSuffix = DB::table('mitraregistrasidetail_t')
                        ->whereNotNull('noorderalat')
                        ->where('noorderalat', 'like', $yearlyPrefix . '%')
                        ->whereRaw("split_part(noorderalat, '.', 3) ~ '^[0-9]+$'")
                        ->max(DB::raw("CAST(split_part(noorderalat, '.', 3) AS INTEGER)"));

                    $start = $maxSuffix ? ($maxSuffix + 1) : 1;

                    foreach ($group->values() as $idx => $detail) {
                        $noUrut  = $start + $idx;
                        $urutStr = str_pad((string)$noUrut, 3, '0', STR_PAD_LEFT);
                        $noorderalat = $monthlyPrefix . $urutStr;

                        DB::table('mitraregistrasidetail_t')
                            ->where('norec', $detail->norec)
                            ->where('statusenabled', true)
                            ->update([
                                'noorderalat'        => $noorderalat,
                                'tglverifasman'      => $now,
                                'statusordermanager' => 0,
                            ]);
                    }
                }
            });

            DB::table('mitraregistrasi_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'statusorder'   => 1,
                    'asmanveriffk'  => $this->getPegawaiId(),
                    'tglverifasman' => now(),
                ]);

            DB::commit();

            $regis = DB::table('mitraregistrasi_t')->where('norec', $VI['norec'])->first();
            $jenisOrder = strtolower($regis->jenisorder ?? '');
            if ($jenisOrder == 'kalibrasi') {
                $textSPK = "SPK Kalibrasi alat Anda";
            } elseif ($jenisOrder == 'repair') {
                $textSPK = "SPK Repair alat Anda";
            } else {
                $textSPK = "SPK alat Anda";
            }
            $details = DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $VI['norec'])
                ->get();

            $penyeliaIds  = $details->pluck('penyeliateknikfk')->filter()->unique();
            $pelaksanaIds = $details->pluck('pelaksanateknikfk')->filter()->unique();
            $pegawaiIds = $penyeliaIds->concat($pelaksanaIds)->unique()->values();
            $pegawaiById = $pegawaiIds->isNotEmpty()
                ? DB::table('pegawai_m')->whereIn('id', $pegawaiIds)->get()->keyBy('id')
                : collect();

            $username = 'Mahzumi';
            $asman = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaAsman = $asman->namalengkap ?? '-';
            $waktuVerif = now();

            try {
                $waktuVerifStr = \Carbon\Carbon::parse($waktuVerif)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuVerifStr = date('d-m-Y H:i', strtotime($waktuVerif)) . ' WIB';
            }

            foreach ($penyeliaIds as $penyeliaId) {
                $pegawai = $pegawaiById->get($penyeliaId);
                if (! $pegawai) {
                    continue;
                }

                $profileId = $pegawai->kdprofile ?? 1;
                $token     = $this->createToken($username) . '.' . base64_encode((string)$profileId);
                $link      = url('/service/asman/cetak-spk') . '?pdf=true'
                    . '&norec=' . $VI['norec']
                    . '&penyeliateknikfk=' . $penyeliaId
                    . '&user=' . urlencode($username)
                    . '&kdprofile=' . $profileId
                    . '&token=' . $token;
                $shortLink = $this->shortLink($link);

                $pesan = "Halo Tim Hebat U-LAB ! 👋\n"
                    . "Hari ini kita punya tantangan baru yang jadi peluang untuk menunjukkan kualitas terbaik kita.\n\n"
                    . "Yth. {$pegawai->namalengkap},\n"
                    . "Bersama ini kami sampaikan tautan untuk meninjau dokumen Surat Perintah Kerja (SPK) Anda:\n"
                    . "{$shortLink}\n"
                    . "Silakan mengakses tautan tersebut untuk melihat dokumen secara langsung.\n\n"
                    . "Yuk, kita kerjakan dengan semangat, saling support, dan pastikan hasilnya membanggakan 💪\n"
                    . "Ingat, setiap tugas yang kita jalankan adalah bagian penting dari kemajuan tim ini.\n"
                    . "🔥Tetap semangat, jaga komunikasi, dan ayo kita capai target bersama!\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                if ($pegawai->nohandphone) {
                    $this->kirimWhatsappNotifikasi($pegawai->nohandphone, $pesan);
                }

                $notif = new \App\Models\Master\ListNotif();
                $notif->norec = (string) \Illuminate\Support\Str::uuid();
                $notif->norec_trans = $VI['norec'] ?? (string) \Illuminate\Support\Str::uuid();
                $notif->judul = 'SPK Baru Siap Ditinjau';
                $notif->jenis = 'SPK';
                $notif->pegawaifk = $pegawai->id;
                $notif->namapegawai = $pegawai->namalengkap ?? '-';
                $notif->keterangan = $textSPK . ' telah tersedia dan siap ditinjau.';
                $notif->tgl = now();
                $notif->tgl_string = now()->format('d-m-Y H:i');
                $notif->urlform = 'module-dashboard-penyelia';
                $notif->params = json_encode([
                    'norec' => $VI['norec'],
                    'penyeliateknikfk' => $penyeliaId,
                    'role_tujuan' => 'Penyelia Teknik',
                ]);
                $notif->dataarray = json_encode([
                    'registrasi_norec' => $VI['norec'],
                    'pegawai_id' => $pegawai->id,
                    'namaAsman' => $namaAsman,
                    'waktuVerif' => $waktuVerifStr,
                    'shortLink' => $shortLink,
                    'jenisOrder' => $jenisOrder,
                ]);
                $notif->statusenabled = true;
                $notif->isread = false;
                $notif->save();

                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->withHeaders([
                            'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                            'Accept' => 'application/json',
                        ])
                        ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                            'norec' => $notif->norec,
                            'norec_trans' => $notif->norec_trans,
                            'judul' => $notif->judul,
                            'jenis' => $notif->jenis,
                            'idPegawai' => $notif->pegawaifk,
                            'namapegawai' => $notif->namapegawai,
                            'pesanNotifikasi' => $notif->keterangan,
                            'tgl' => $notif->tgl,
                            'tgl_string' => $notif->tgl_string,
                            'urlForm' => $notif->urlform,
                            'params' => json_decode($notif->params, true),
                            'dataArray' => json_decode($notif->dataarray, true),
                        ]);
                } catch (\Exception $e) {
                    // abaikan jika socket gagal, notif DB tetap tersimpan
                }
            }

            foreach ($pelaksanaIds as $pelaksanaId) {
                $pegawaiPelaksana = $pegawaiById->get($pelaksanaId);
                if (! $pegawaiPelaksana) {
                    continue;
                }

                $profileId = $pegawaiPelaksana->kdprofile ?? 1;
                $token     = $this->createToken($username) . '.' . base64_encode((string)$profileId);
                $link      = url('/service/pelaksana/cetak-spk') . '?pdf=true'
                    . '&norec=' . $VI['norec']
                    . '&pelaksanateknikfk=' . $pelaksanaId
                    . '&user=' . urlencode($username)
                    . '&kdprofile=' . $profileId
                    . '&token=' . $token;
                $shortLink = $this->shortLink($link);

                $pesan = "Halo Tim Hebat U-LAB ! 👋\n"
                    . "Hari ini kita punya tantangan baru yang jadi peluang untuk menunjukkan kualitas terbaik kita.\n\n"
                    . "Yth. {$pegawaiPelaksana->namalengkap},\n"
                    . "Bersama ini kami sampaikan tautan untuk meninjau dokumen Surat Perintah Kerja (SPK) Anda:\n"
                    . "{$shortLink}\n"
                    . "Silakan mengakses tautan tersebut untuk melihat dokumen secara langsung.\n\n"
                    . "Yuk, kita kerjakan dengan semangat, saling support, dan pastikan hasilnya membanggakan 💪\n"
                    . "Ingat, setiap tugas yang kita jalankan adalah bagian penting dari kemajuan tim ini.\n"
                    . "🔥Tetap semangat, jaga komunikasi, dan ayo kita capai target bersama!\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                if ($pegawaiPelaksana->nohandphone) {
                    $this->kirimWhatsappNotifikasi($pegawaiPelaksana->nohandphone, $pesan);
                }

                $notif = new \App\Models\Master\ListNotif();
                $notif->norec = (string) \Illuminate\Support\Str::uuid();
                $notif->norec_trans = $VI['norec'] ?? (string) \Illuminate\Support\Str::uuid();
                $notif->judul = 'SPK Baru Siap Ditinjau';
                $notif->jenis = 'SPK';
                $notif->pegawaifk = $pegawaiPelaksana->id;
                $notif->namapegawai = $pegawaiPelaksana->namalengkap ?? '-';
                $notif->keterangan = $textSPK . ' telah tersedia dan siap ditinjau.';
                $notif->tgl = now();
                $notif->tgl_string = now()->format('d-m-Y H:i');
                $notif->urlform = 'module-dashboard-pelaksana';
                $notif->params = json_encode([
                    'norec' => $VI['norec'],
                    'pelaksanateknikfk' => $pelaksanaId,
                    'role_tujuan' => 'Pelaksana Teknik',
                ]);
                $notif->dataarray = json_encode([
                    'registrasi_norec' => $VI['norec'],
                    'pegawai_id' => $pegawaiPelaksana->id,
                    'namaAsman' => $namaAsman,
                    'waktuVerif' => $waktuVerifStr,
                    'shortLink' => $shortLink,
                    'jenisOrder' => $jenisOrder,
                ]);
                $notif->statusenabled = true;
                $notif->isread = false;
                $notif->save();

                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->withHeaders([
                            'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                            'Accept' => 'application/json',
                        ])
                        ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                            'norec' => $notif->norec,
                            'norec_trans' => $notif->norec_trans,
                            'judul' => $notif->judul,
                            'jenis' => $notif->jenis,
                            'idPegawai' => $notif->pegawaifk,
                            'namapegawai' => $notif->namapegawai,
                            'pesanNotifikasi' => $notif->keterangan,
                            'tgl' => $notif->tgl,
                            'tgl_string' => $notif->tgl_string,
                            'urlForm' => $notif->urlform,
                            'params' => json_decode($notif->params, true),
                            'dataArray' => json_decode($notif->dataarray, true),
                        ]);
                } catch (\Exception $e) {
                    // abaikan jika socket gagal, notif DB tetap tersimpan
                }
            }

            $transMessage = "Simpan Verif Sukses & SPK sudah dikirim ke teknisi.";
            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
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


    public function HeaderMitra(Request $r)
    {
        $data = DB::table('mitraregistrasi_t as mtr')
            ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('users as us', 'us.id', '=', 'mtr.customerfk')
            ->select(
                'mt.id',
                'mt.namaperusahaan',
                'mt.tgldaftar',
                'mt.email',
                'mt.nohp',
                'mt.foto',
                'mt.progress',
                'mtr.tglregistrasi',
                'mtr.jenisorder',
                'mtr.petugas',
                'mtr.nopendaftaran',
                'mtr.lokasikalibrasi',
                'mtr.norec as norec_pd',
                'us.name',
                'us.jabatan',
                'us.nowa',
                'us.id as iduser',
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtr.norec', $r['norec_pd'])
            ->get();

        $detailAll = DB::table('mitraregistrasidetail_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregistrasifk');

        foreach ($data as $item) {
            $detailList = $detailAll[$item->norec_pd] ?? collect();
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
        }

        $result['mitra'] = $data;
        $result['as'] = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function cetakSPK(Request $r)
    {

        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $res['identitas'] =  $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
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
                'lk.lokasi',
                'mtr.jabatanpenanggungjawab',
                'mtr.namapenanggungjawab'
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $r['norec'])
            ->first();

        $res['alat'] =  $data = DB::table('mitraregistrasi_t as mtr')
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
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtrd.asmanveriffk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg2.jabatan1fk')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.keterangan',
                'mtrd.namafile',
                'mtrd.pelaksanateknikfk',
                'mtrd.noorderalat',
                'mtrd.durasikalbrasi',
                'mtrd.namamanager',
                'mtrd.namaasman',
                'mtrd.tanggalmulaiestimasi',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg.nid',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'pg3.nid as nidasman',
                'jb1.namajabatanulab as jabatanpenyelia',
                'jb.namajabatanulab as jabatanpelaksana',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.norec', $r['norec'])
            ->where('mtrd.penyeliateknikfk', $r['penyeliateknikfk'])
            ->orderByDesc('mmp.namaproduk')
            ->get();

        $jabatanAsman = $this->settingFix('jabatanasman');
        $jabatanManager = $this->settingFix('jabatanmanager');
        $asman = DB::table('pegawai_m')->where('jabatan1fk', $jabatanAsman)->first();
        $manager = DB::table('pegawai_m')->where('jabatan1fk', $jabatanManager)->first();


        $res['totalDurasi'] = $res['alat']->sum('durasikalbrasi');
        $res['pdf']  = $r['pdf'];
        $res['asman']  = $asman;
        $res['manager']  = $manager;
        $res['ttdAsman'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            'Asman NMW' . "\n" . $asman->namalengkap . "\n" .  $asman->nid . "\n" . 'nosurat: FMMO-163-14.4.3.b-74.4'
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdManager'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            'Kepala Laboratorium Kalibrasi' . "\n" . $manager->namalengkap . "\n" .  $manager->nid . "\n" . 'nosurat: FMMO-163-14.4.3.b-74.4'
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdPenyelia'] = base64_encode(QrCode::format('svg')->size(75)->generate($res['alat'][0]->pelaksanateknik));
        $res['penyelia'] = true;


        $blade = 'report.asman.surat-perintah-kerja';

        if ($res['pdf'] == 'true') {
            $pdf = App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("isPhpEnabled", true);
            $pdf->setpaper('a4', 'landscape');
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
            $pdf->setpaper('a4', 'landscape');
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

    public function cetakSertifikatLembarKerja(Request $r)
    {

        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $res['identitas'] =  $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->select(
                'jb.id as idjabatan',
                'jb.namajabatanulab as namajabanpetugaskaji',
                'mtr.norec',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mt.namaperusahaan',
                'mt.alamatktr',
                'pg.id as petugaskajifk',
                'pg.namalengkap as namapetugaskaji',
                'lk.lokasi',
                'mtr.jabatanpenanggungjawab',
                'mtr.namapenanggungjawab'
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $r['norec'])
            ->first();

        $res['instruksikerja'] = DB::table('daftarinstruksikerja_t as dik')
            ->leftJoin('instruksikerja_m as ik', 'ik.id', '=', 'dik.idalatinstruksikerja')
            ->select('dik.detailregistrasifk', 'ik.id', 'ik.namainstruksikerja', 'ik.noisntruksikerja')
            ->where('dik.detailregistrasifk', $r['norec_detail'])
            ->where('dik.statusenabled', true)
            ->where('ik.statusenabled', true)
            ->get();

        $res['alastandar'] = DB::table('daftaralatstandar_t as das')
            ->leftJoin('mapalatstandar_m as pas', 'pas.id', '=', 'das.alatstandarfk')
            ->select(
                'das.detailregistrasifk',
                'pas.id as value',
                'pas.namaalatstandar',
                'pas.duedate',
                'pas.calldate',
                'pas.namamerk',
                'pas.namatipe',
                'pas.namaserialnumber'
            )
            ->where('das.detailregistrasifk', $r['norec_detail'])
            ->where('das.statusenabled', true)
            ->where('pas.statusenabled', true)
            ->get();

        $res['alat'] = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtr.asmanveriffk')
            ->leftJoin('pegawai_m as pg4', 'pg4.id', '=', 'mtrd.managersetujulembarkerjafk')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->leftJoin('jabatan_m as jb2', 'jb2.id', '=', 'pg2.jabatan1fk')
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
            ->leftJoin('sublingkupkalibrasi_m as slk', 'slk.id', '=', 'mtrd.sublingkupfk')
            ->select(
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg.nid as nidpenyelia',
                'jb1.namajabatanulab as jabatanpenyelia',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg2.nid as nidpelaksana',
                'jb2.namajabatanulab as jabatanpelaksana',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'pg3.nid as nidasman',
                'pg4.namalengkap as namamanager',
                'pg4.nid as nidmanager',
                'mtrd.noorderalat',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.setujuilembarkerjaasman',
                'mtrd.setujuilembarkerjapenyelia',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'slk.namasublingkup',
                'mtrd.sublingkupfk',
                'mtrd.lingkupkalibrasifk',
                'mtrd.kedalamalubang',
                'mtrd.lubangacuan',
                'mtrd.lubang1',
                'mtrd.lubang2',
                'mtrd.gambarsuhu',
                'mtrd.nosertifikat',
                'mtrd.statuskanfk',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.nosertifikatamandemen',
                'mtrd.kesejajaran',
                'mtrd.kerataan',
                'mtrd.kesejajaranluar',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_detail'])
            ->first();

        if ($res['alat']->lingkupkalibrasifk == 2 || $res['alat']->sublingkupfk == null) {
            $res['lembarKerja'] = DB::table('lembarkerjatekanan_t as lk')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                ->select(
                    'lk.*',
                    'mtrd.tglkalibrasilembarkerja',
                    'rm.namaruangan as tempatKalibrasilembarkerja',
                    'mtrd.kondisiRuanganlembarkerja',
                    'mtrd.rangelembarkerja',
                    'mtrd.rangelembarkerjasatuan',
                    'mtrd.mounting',
                    'mtrd.mediakalibrasi',
                    'mtrd.tekananruang',
                    'mtrd.gravitasi',
                    'mtrd.suhulembarkerja',
                    'mtrd.kelembabanRelatiflembarkerja',
                    'mtrd.noteslembarkerja',
                    'mtrd.sublingkupfk',
                    'mtrd.lingkupkalibrasifk',
                )
                ->where('mtrd.norec', $r['norec_detail'])
                ->where('mtrd.statusenabled', true)
                ->where('lk.statusenabled', true)
                ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                ->get();
        } else {
            if ($res['alat']->sublingkupfk == 1) {
                $res['lembarKerja'] = DB::table('lembarkerja_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 2) {
                $res['lembarKerja'] = DB::table('lembarkerjaclamp_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 3) {
                $res['lembarKerja'] = DB::table('lembarkerjasumber_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 4) {
                $res['lembarKerja'] = DB::table('lembarkerjavibration_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.rangelembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 5) {
                $res['lembarKerja'] = DB::table('lembarkerjaaccellerometer_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.rangelembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 6) {
                $res['lembarKerja'] = DB::table('lembarkerjaclampmeter_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.jenis', 'ASC')
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 7) {
                $res['lembarKerja'] = DB::table('lembarkerjametersumber_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.jenis', 'ASC')
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 8) {
                $res['lembarKerja'] = DB::table('lembarkerjathermometerinfrared_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.resolusi',
                        'mtrd.rentangukursuhu',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.jarakKalibrasi',
                        'mtrd.emisivitas',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 9) {
                $res['lembarKerja'] = DB::table('lembarkerjathermalimager_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.jarakKalibrasi',
                        'mtrd.emisivitas',
                        'mtrd.resolusi',
                        'mtrd.rentangukursuhu',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 10) {
                $res['lembarKerja'] = DB::table('lembarkerjathermohygrometer_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.resolusi',
                        'mtrd.resolusipersen',
                        'mtrd.rentangukursuhu',
                        'mtrd.rentangukursuhupersen',
                        'mtrd.lingkupkalibrasifk',
                        'mtrd.kondisipengukuransuhu',
                        'mtrd.kondisipengukurankelembaban',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 11) {
                $res['lembarKerja'] = DB::table('lembarkerjadryblock_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.resolusi',
                        'mtrd.rentangukursuhu',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.jenis', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 12) {
                $res['lembarKerja'] = DB::table('lembarkerjavibrationcalibrator_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.rangelembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.jenis', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 13) {
                $res['lembarKerja'] = DB::table('lembarkerjacalipermicrometer_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.rangelembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.kesejajaranluar',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.resolusilembarkerjasatuan'
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 14) {
                $res['lembarKerja'] = DB::table('lembarkerjadialindicator_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.resolusi',
                        'mtrd.resolusilembarkerjasatuan',
                        'mtrd.rangelembarkerja',
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 15) {
                $res['lembarKerja'] = DB::table('lembarkerjaboregauge_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.merekdial',
                        'mtrd.tipedial',
                        'mtrd.sndial',
                        'mtrd.rangedial',
                        'mtrd.resolusidial',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 16) {
                $res['lembarKerja'] = DB::table('lembarkerjaclampmetersumber_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.jenis', 'ASC')
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif (in_array($res['alat']->namasublingkup, ['KALIBRASI AI', 'KALIBRASI AI VIBRASI', 'KALIBRASI AI SUHU'], true)) {
                $res['lembarKerja'] = DB::table('kalibrasi_ai as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noorderalat', '=', 'lk.noorder')
                    ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.rangelembarkerja',
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.mounting',
                        'mtrd.mediakalibrasi',
                        'mtrd.tekananruang',
                        'mtrd.gravitasi',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 18) {
                $res['lembarKerja'] = DB::table('lembarkerjacontinuitysource_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.jenis', 'ASC')
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 19) {
                $res['lembarKerja'] = DB::table('lembarkerjatachometer_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 20) {
                $res['lembarKerja'] = DB::table('lembarkerjainsulationmeterresistansi_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 21) {
                $res['lembarKerja'] = DB::table('lembarkerjatorquewrench_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.resolusi',
                        'mtrd.rangelembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 22) {
                $res['lembarKerja'] = DB::table('lembarkerjatemperatureindicator_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.jenissensor',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 23) {
                $res['lembarKerja'] = DB::table('lembarkerjaultrasonicthickness_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                        'mtrd.kecepatan',
                        'mtrd.standarmaterial',
                        'mtrd.rangelembarkerja',
                        'mtrd.resolusi',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 24) {
                $res['lembarKerja'] = DB::table('lembarkerjathicknessgauge_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                        'mtrd.rangelembarkerja',
                        'mtrd.resolusi',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 33) {
                $res['lembarKerja'] = DB::table('lembarkerjafeelergauge_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                        'mtrd.rangelembarkerja',
                        'mtrd.resolusi',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 25) {
                $res['lembarKerja'] = DB::table('lembarkerjainsulationtester_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 26) {
                $res['lembarKerja'] = DB::table('lembarkerjainsulationsource_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.jenis', 'ASC')
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 27) {
                $res['lembarKerja'] = DB::table('lembarkerjamicrometerhead_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.rangelembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.resolusilembarkerjasatuan'
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 28) {
                $res['lembarKerja'] = DB::table('lembarkerjaoutsidemicrometer_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.rangelembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.resolusilembarkerjasatuan'
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true)
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 30) {
                $res['lembarKerja'] = DB::table('lembarkerjathermocouplertdsimulator_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            } elseif ($res['alat']->sublingkupfk == 29) {
                $res['lembarKerja'] = DB::table('lembarkerjaoscilloscope_t as lk')
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
                    ->select(
                        'lk.*',
                        'mtrd.tglkalibrasilembarkerja',
                        'rm.namaruangan as tempatKalibrasilembarkerja',
                        'mtrd.kondisiRuanganlembarkerja',
                        'mtrd.suhulembarkerja',
                        'mtrd.kelembabanRelatiflembarkerja',
                        'mtrd.noteslembarkerja',
                        'mtrd.sublingkupfk',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
                    ->where('lk.statusenabled', true)
                    ->orderBy('lk.group', 'ASC')
                    ->orderByRaw('CAST(lk.no AS INTEGER) ASC')
                    ->get();
            }
        }

        if (!empty($res['lembarKerja'][0]->noteslembarkerja)) {
            app(CalibrationNotesTranslationService::class)
                ->prepareWorksheetForPrint($r['norec_detail'], $res['lembarKerja'][0]);
        }
        $res['pdf']  = $r['pdf'];
        // $res['ttdPelaksana'] = base64_encode(QrCode::format('svg')->size(75)->generate(
        //     $res['alat']->jabatanpelaksana . "\n" . $res['alat']->pelaksanateknik . "\n" .  $res['alat']->nidpelaksana . "\n" . 'nosurat:' . $res['alat']->nosertifikat
        //         . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        // ));
        // $res['ttdPenyelia'] = base64_encode(QrCode::format('svg')->size(75)->generate(
        //     $res['alat']->jabatanpenyelia . "\n" . $res['alat']->penyeliateknik . "\n" .  $res['alat']->nidpenyelia . "\n" . 'nosurat:' . $res['alat']->nosertifikat
        //         . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        // ));
        // $res['ttdAsman'] = base64_encode(QrCode::format('svg')->size(75)->generate(
        //     'Asman NMW' . "\n" . $res['alat']->asamanverifikasi . "\n" .  $res['alat']->nidasman . "\n" . 'nosurat:' . $res['alat']->nosertifikat
        //         . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        // ));
        // $res['ttdManager'] = base64_encode(QrCode::format('svg')->size(75)->generate(
        //     'Manager Repair' . "\n" . $res['alat']->namamanager . "\n" .  $res['alat']->nidmanager . "\n" . 'nosurat:' . $res['alat']->nosertifikat
        //         . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        // ));
        $validateBaseUrl = rtrim(env('PBJ_VALIDATE_FRONTEND_URL', 'https://ulabumro.id'), '/');
        $validateUrlBase = $validateBaseUrl
            . '/validasi?jenis=sertifikat'
            . '&norec_detail=' . urlencode($r['norec_detail']);

        if (!empty($r['norec'])) {
            $validateUrlBase .= '&norec=' . urlencode($r['norec']);
        }
        $res['validateUrl'] = $validateUrlBase;
        $res['validateUrlPelaksana'] = $validateUrlBase . '&ttd=pelaksana';
        $res['validateUrlPenyelia'] = $validateUrlBase . '&ttd=penyelia';
        $res['validateUrlAsman'] = $validateUrlBase . '&ttd=asman';
        $res['validateUrlManager'] = $validateUrlBase . '&ttd=manager';
        $makeTtd = function (string $ttdKey) use ($validateUrlBase) {
            $url = $validateUrlBase . '&ttd=' . urlencode($ttdKey);

            return base64_encode(
                QrCode::format('svg')->size(75)->generate($url)
            );
        };
        $res['ttdPelaksana'] = $makeTtd('pelaksana');
        $res['ttdPenyelia'] = $makeTtd('penyelia');
        $res['ttdAsman'] = $makeTtd('asman');
        $res['ttdManager'] = $makeTtd('manager');
        $res['halamanPertama'] = true;

        $blade = 'report.pelaksana.sertifikat-lembar-kerja';

        if ($res['pdf'] == 'true') {
            $pdfDummy = App::make('dompdf.wrapper');
            $pdfDummy->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => null,
                )
            );
            $dompdfDummy = $pdfDummy->getDomPDF();
            $dompdfDummy->render();
            $jumlahHalaman = $dompdfDummy->getCanvas()->get_page_count();

            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => $jumlahHalaman,
                )
            );


            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->get_canvas();
            $canvas->page_text(230, 780, "Halaman ke {PAGE_NUM} dari {PAGE_COUNT} halaman", null, 8, array(0, 0, 0));
            $font = $dompdf->getFontMetrics()->getFont('Helvetica', 'italic');
            $canvas->page_text(260, 788, "Page {PAGE_NUM} of {PAGE_COUNT} pages", $font, 7, array(0, 0, 0));

            return $pdf->stream();
        }

        if (isset($r['storage'])) {
            $res['storage']  = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->setpaper('a4', 'landscape');
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

    public function legacyDownloadFileTerunggah(Request $request)
    {
        $norec = $request->get('norec');
        $isLaporanVerifikasi = $request->get('laporan') === 'verifikasi';
        $column = $isLaporanVerifikasi ? 'excellaporanverif' : 'namafileexcel';
        $folder = $isLaporanVerifikasi ? 'berkas-verifikasi' : 'berkas-mitra-excel';

        $data = DB::table('mitraregistrasidetail_t')
            ->select($column)
            ->where('norec', $norec)
            ->first();

        if (!$data || empty($data->{$column})) {
            return '
            <script language="javascript">
                alert("File tidak ditemukan di database.");
                window.close();
            </script>';
        }

        $filename = $data->{$column};
        $pathbundle = $folder . '/' . $filename;
        $path = public_path($pathbundle);

        if (File::exists($path)) {
            $file = File::get($path);
            $type = File::mimeType($path);

            $response = response()->make($file, 200);
            $response->header("Content-Type", $type)
                ->header('Content-disposition', 'attachment; filename="' . $filename . '"');

            return $response;
        } else {
            return '
            <script language="javascript">
                alert("File tidak ditemukan di direktori.");
                window.close();
            </script>';
        }
    }


    public function legacyGetExcelLength(Request $request)
    {
        $norec = $request['norec'];
        $data = DB::table('mitraregistrasidetail_t')
            ->select('namafileexcel')
            ->where('norec', $norec)
            ->first();

        $result['data'] = $data;
        $result['as'] = '@adit';

        return $this->respond($result);
    }

    private function normalizeFotoRepairFilename($filename)
    {
        if (empty($filename)) {
            return null;
        }

        $filename = trim((string) $filename);

        if ($filename === '') {
            return null;
        }

        if (strpos($filename, 'http://') === 0 || strpos($filename, 'https://') === 0) {
            $path = parse_url($filename, PHP_URL_PATH);
            return basename($path);
        }

        if (strpos($filename, 'berkas-laporan-repair/') !== false) {
            return basename($filename);
        }

        if (strpos($filename, 'storage/berkas-laporan-repair/') !== false) {
            return basename($filename);
        }

        return basename($filename);
    }

    private function buildLegacyFotoRepairCollection($filename, $caption = null)
    {
        $filename = $this->normalizeFotoRepairFilename($filename);

        if (empty($filename)) {
            return collect();
        }

        return collect([
            (object) [
                'norec' => null,
                'namafile' => $filename,
                'keterangan_gambar' => $caption,
                'urut' => 1,
                'is_legacy' => true,
            ],
        ]);
    }

    private function attachFotoRepairFallback($rows, $fotoMap, $legacyColumnName)
    {
        foreach ($rows as $row) {
            $fotoBaru = $fotoMap->get($row->norec, collect())->values();

            if (!empty($fotoBaru) && count($fotoBaru) > 0) {
                $row->fotos = $fotoBaru;
                continue;
            }

            $row->fotos = $this->buildLegacyFotoRepairCollection($row->{$legacyColumnName} ?? null);
        }

        return $rows;
    }

    public function getLaporanRepairAsman(Request $request)
    {
        $norecDetail = $request->norecdetail;

        $data = DB::table('laporanrepair_t as lp')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lp.detailregistrasifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->leftJoin('statusrepair_m as spr', 'spr.id', '=', 'mtrd.statusrepairfk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'lp.petugas')
            ->select(
                'lp.*',
                'mtr.norec as norecregis',
                'mtrd.kesimpulanrepair',
                'spr.id as statusrepairfk',
                'spr.statusrepair',
                'pg.namalengkap as petugasrepair'
            )
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('lp.statusenabled', true)
            ->orderBy('lp.created_at', 'ASC')
            ->get();

        $dataStatus = DB::table('statusalatrepair_t as sp')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'sp.detailregistrasifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'sp.petugas')
            ->select(
                'sp.*',
                'mtr.norec as norecregis',
                'mtrd.kesimpulanrepair',
                'pg.namalengkap as petugasisistatus'
            )
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('sp.statusenabled', true)
            ->orderBy('sp.created_at', 'ASC')
            ->get();

        $dataHasil = DB::table('daftarhasilrepair_t as hr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'hr.detailregistrasifk')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'hr.petugas')
            ->select(
                'hr.*',
                'mtr.norec as norecregis',
                'mtrd.kesimpulanrepair',
                'pg.namalengkap as petugashasilrepair'
            )
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('hr.statusenabled', true)
            ->orderBy('hr.created_at', 'ASC')
            ->get();

        $statusIds = $dataStatus->pluck('norec')->filter()->values()->toArray();
        $laporanIds = $data->pluck('norec')->filter()->values()->toArray();
        $hasilIds = $dataHasil->pluck('norec')->filter()->values()->toArray();

        $fotoStatusMap = collect();
        if (count($statusIds) > 0) {
            $fotoStatusMap = DB::table('statusalatrepair_foto_t')
                ->whereIn('statusalatrepairfk', $statusIds)
                ->where('statusenabled', true)
                ->orderBy('urut', 'ASC')
                ->orderBy('created_at', 'ASC')
                ->get()
                ->groupBy('statusalatrepairfk');
        }

        $fotoLaporanMap = collect();
        if (count($laporanIds) > 0) {
            $fotoLaporanMap = DB::table('laporanrepair_foto_t')
                ->whereIn('laporanrepairfk', $laporanIds)
                ->where('statusenabled', true)
                ->orderBy('urut', 'ASC')
                ->orderBy('created_at', 'ASC')
                ->get()
                ->groupBy('laporanrepairfk');
        }

        $fotoHasilMap = collect();
        if (count($hasilIds) > 0) {
            $fotoHasilMap = DB::table('hasilrepair_foto_t')
                ->whereIn('hasilrepairfk', $hasilIds)
                ->where('statusenabled', true)
                ->orderBy('urut', 'ASC')
                ->orderBy('created_at', 'ASC')
                ->get()
                ->groupBy('hasilrepairfk');
        }

        $dataStatus = $this->attachFotoRepairFallback(
            $dataStatus,
            $fotoStatusMap,
            'fotoalatstatus'
        );

        $data = $this->attachFotoRepairFallback(
            $data,
            $fotoLaporanMap,
            'fotoalatrepair'
        );

        $dataHasil = $this->attachFotoRepairFallback(
            $dataHasil,
            $fotoHasilMap,
            'fotohasilrepair'
        );

        $detail = DB::table('mitraregistrasidetail_t as mtrd')
            ->leftJoin('statusrepair_m as spr', 'spr.id', '=', 'mtrd.statusrepairfk')
            ->select(
                'mtrd.norec',
                'mtrd.noregistrasifk',
                'mtrd.kesimpulanrepair',
                'mtrd.statusrepairfk',
                'spr.statusrepair'
            )
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->first();

        $result['data'] = $data;
        $result['datastatus'] = $dataStatus;
        $result['datahasil'] = $dataHasil;
        $result['detail'] = $detail;
        $result['as'] = '@adit';

        return $this->respond($result);
    }

    public function cetakLaporanRepairAsman(Request $r)
    {
        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $norec = $r['norec'] ?? null;
        $norecDetail = $r['norec_detail'] ?? null;

        if (empty($norec) || empty($norecDetail)) {
            abort(404, 'Parameter laporan repair tidak lengkap.');
        }

        $res['identitas'] = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->select(
                'jb.id as idjabatan',
                'jb.namajabatanulab as namajabanpetugaskaji',
                'mtr.norec',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mt.namaperusahaan',
                'mt.alamatktr',
                'pg.id as petugaskajifk',
                'pg.namalengkap as namapetugaskaji',
                'lk.lokasi',
                'mtr.jabatanpenanggungjawab',
                'mtr.namapenanggungjawab'
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $norec)
            ->first();

        $res['lembarKerja'] = DB::table('lembarkerja_t as lk')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
            ->select(
                'lk.*',
                'mtrd.tglkalibrasilembarkerja',
                'mtrd.tempatKalibrasilembarkerja',
                'mtrd.kondisiRuanganlembarkerja',
                'mtrd.suhulembarkerja',
                'mtrd.kelembabanRelatiflembarkerja'
            )
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('lk.statusenabled', true)
            ->get();

        $res['statusRepair'] = DB::table('statusalatrepair_t as sp')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'sp.detailregistrasifk')
            ->select('sp.*')
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('sp.statusenabled', true)
            ->orderBy('sp.created_at', 'ASC')
            ->get();

        $res['laporanRepair'] = DB::table('laporanrepair_t as lp')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lp.detailregistrasifk')
            ->select('lp.*')
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('lp.statusenabled', true)
            ->orderBy('lp.created_at', 'ASC')
            ->get();

        $res['hasilRepair'] = DB::table('daftarhasilrepair_t as hr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'hr.detailregistrasifk')
            ->select('hr.*')
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('hr.statusenabled', true)
            ->orderBy('hr.created_at', 'ASC')
            ->get();

        $statusIds = $res['statusRepair']->pluck('norec')->filter()->values()->toArray();
        $laporanIds = $res['laporanRepair']->pluck('norec')->filter()->values()->toArray();
        $hasilIds = $res['hasilRepair']->pluck('norec')->filter()->values()->toArray();

        $fotoStatusMap = collect();
        if (count($statusIds) > 0) {
            $fotoStatusMap = DB::table('statusalatrepair_foto_t')
                ->whereIn('statusalatrepairfk', $statusIds)
                ->where('statusenabled', true)
                ->orderBy('urut', 'ASC')
                ->orderBy('created_at', 'ASC')
                ->get()
                ->groupBy('statusalatrepairfk');
        }

        $fotoLaporanMap = collect();
        if (count($laporanIds) > 0) {
            $fotoLaporanMap = DB::table('laporanrepair_foto_t')
                ->whereIn('laporanrepairfk', $laporanIds)
                ->where('statusenabled', true)
                ->orderBy('urut', 'ASC')
                ->orderBy('created_at', 'ASC')
                ->get()
                ->groupBy('laporanrepairfk');
        }

        $fotoHasilMap = collect();
        if (count($hasilIds) > 0) {
            $fotoHasilMap = DB::table('hasilrepair_foto_t')
                ->whereIn('hasilrepairfk', $hasilIds)
                ->where('statusenabled', true)
                ->orderBy('urut', 'ASC')
                ->orderBy('created_at', 'ASC')
                ->get()
                ->groupBy('hasilrepairfk');
        }

        $res['statusRepair'] = $this->attachFotoRepairFallback(
            $res['statusRepair'],
            $fotoStatusMap,
            'fotoalatstatus'
        );

        $res['laporanRepair'] = $this->attachFotoRepairFallback(
            $res['laporanRepair'],
            $fotoLaporanMap,
            'fotoalatrepair'
        );

        $res['hasilRepair'] = $this->attachFotoRepairFallback(
            $res['hasilRepair'],
            $fotoHasilMap,
            'fotohasilrepair'
        );

        $res['alat'] = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtr.asmanveriffk')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->leftJoin('jabatan_m as jb2', 'jb2.id', '=', 'pg2.jabatan1fk')
            ->leftJoin('statusrepair_m as sr', 'sr.id', '=', 'mtrd.statusrepairfk')
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
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasirepair')
            ->select(
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg.nid as nidpenyelia',
                'jb1.namajabatanulab as jabatanpenyelia',

                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg2.nid as nidpelaksana',
                'jb2.namajabatanulab as jabatanpelaksana',

                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'pg3.nid as nidasman',

                'mtrd.noorderalat',
                'mtrd.namamanager',
                'mtrd.penyeliasetujulaporanrepairfk',
                'mtrd.asmansetujulaporanrepairfk',
                'mtrd.managersetujulaporanrepairfk',
                'mtrd.kesimpulanrepair',
                'mtrd.keterangan',
                'mtrd.namafile',
                'mtrd.nolaporanrepair',
                'mtrd.statusrepairfk',
                'sr.statusrepair',

                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),

                'lk.lokasi as lokasirepair',
                'mtrd.tglsetujumanagerlaporanrepair'
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $norecDetail)
            ->first();

        if (!$res['alat']) {
            abort(404, 'Data alat laporan repair tidak ditemukan.');
        }

        try {
            $res['alat']->kesimpulanrepair_en = app(CalibrationNotesTranslationService::class)
                ->translate($res['alat']->kesimpulanrepair ?? '') ?? '';
        } catch (\Exception $e) {
            $res['alat']->kesimpulanrepair_en = '';
        }

        $res['pdf'] = $r['pdf'] ?? 'false';

        $res['ttdPelaksana'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            ($res['alat']->jabatanpelaksana ?? 'Pelaksana Repair') . "\n" .
                ($res['alat']->pelaksanateknik ?? '-') . "\n" .
                ($res['alat']->nidpelaksana ?? '-') . "\n" .
                'nosurat:' . ($res['alat']->nolaporanrepair ?? '-') . "\n" .
                'Dokumen Ini Diproduksi oleh :' . "\n" .
                'https://ulabumro.id/'
        ));

        $res['ttdPenyelia'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            ($res['alat']->jabatanpenyelia ?? 'Penyelia Repair') . "\n" .
                ($res['alat']->penyeliateknik ?? '-') . "\n" .
                ($res['alat']->nidpenyelia ?? '-') . "\n" .
                'nosurat:' . ($res['alat']->nolaporanrepair ?? '-') . "\n" .
                'Dokumen Ini Diproduksi oleh :' . "\n" .
                'https://ulabumro.id/'
        ));

        $res['ttdAsman'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            'Asman NMW' . "\n" .
                ($res['alat']->asamanverifikasi ?? '-') . "\n" .
                ($res['alat']->nidasman ?? '-') . "\n" .
                'nosurat:' . ($res['alat']->nolaporanrepair ?? '-') . "\n" .
                'Dokumen Ini Diproduksi oleh :' . "\n" .
                'https://ulabumro.id/'
        ));

        $res['ttdManager'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            'Manager Repair' . "\n" .
                ($res['alat']->namamanager ?? '-') . "\n" .
                '8208045JA' . "\n" .
                'nosurat:' . ($res['alat']->nolaporanrepair ?? '-') . "\n" .
                'Dokumen Ini Diproduksi oleh :' . "\n" .
                'https://ulabumro.id/'
        ));

        $res['halamanPertama'] = true;

        $blade = 'report.pelaksana.laporan-repair';

        if ($res['pdf'] == 'true') {
            $pdfDummy = App::make('dompdf.wrapper');
            $pdfDummy->loadView(
                $blade . '-dom',
                [
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => null,
                ]
            );

            $dompdfDummy = $pdfDummy->getDomPDF();
            $dompdfDummy->render();
            $jumlahHalaman = $dompdfDummy->getCanvas()->get_page_count();

            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView(
                $blade . '-dom',
                [
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => $jumlahHalaman,
                ]
            );

            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->get_canvas();

            $canvas->page_text(
                230,
                780,
                "Halaman ke {PAGE_NUM} dari {PAGE_COUNT} halaman",
                null,
                8,
                [0, 0, 0]
            );

            $font = $dompdf->getFontMetrics()->getFont('Helvetica', 'italic');

            $canvas->page_text(
                260,
                788,
                "Page {PAGE_NUM} of {PAGE_COUNT} pages",
                $font,
                7,
                [0, 0, 0]
            );

            return $pdf->stream();
        }

        if (isset($r['storage'])) {
            $res['storage'] = true;

            $pdfDummy = App::make('dompdf.wrapper');
            $pdfDummy->loadView(
                $blade . '-dom',
                [
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => null,
                ]
            );

            $dompdfDummy = $pdfDummy->getDomPDF();
            $dompdfDummy->render();
            $jumlahHalaman = $dompdfDummy->getCanvas()->get_page_count();

            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView(
                $blade . '-dom',
                [
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => $jumlahHalaman,
                ]
            );

            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->get_canvas();

            $canvas->page_text(
                230,
                780,
                "Halaman ke {PAGE_NUM} dari {PAGE_COUNT} halaman",
                null,
                8,
                [0, 0, 0]
            );

            $font = $dompdf->getFontMetrics()->getFont('Helvetica', 'italic');

            $canvas->page_text(
                260,
                788,
                "Page {PAGE_NUM} of {PAGE_COUNT} pages",
                $font,
                7,
                [0, 0, 0]
            );

            return $pdf;
        }

        return view(
            $blade,
            compact('profile', 'pageWidth', 'print', 'res')
        );
    }

    public function detailAlatRepairAsman(Request $r)
    {
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
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtrd.asmanveriffk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtrd.lokasikajifk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.statusorderasman',
                'mtrd.statusorderpenyelia',
                'mtrd.statusorderpelaksana',
                'mtrd.tglverifasman',
                'mtrd.tglverifpenyelia',
                'mtrd.tglverifpelaksana',
                'mtrd.tglkalibrasilembarkerja',
                'mtrd.tempatKalibrasilembarkerja',
                'mtrd.kondisiRuanganlembarkerja',
                'mtrd.suhulembarkerja',
                'mtrd.kelembabanRelatiflembarkerja',
                'mtrd.noorderalat',
                'mtrd.kesimpulanrepair',
                'mtr.catatan',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi'
            )
            ->where('mmp.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_pd'])
            ->orderByDesc('mmp.namaproduk')
            ->first();

        if ($data) {
            $fotoDetail = DB::table('mitraregistrasidetailfoto_t')
                ->where('mitraregistrasidetailfk', $r['norec_pd'])
                ->where('statusenabled', true)
                ->orderBy('created_at', 'ASC')
                ->get(['namafile', 'keterangan']);

            $data->foto_files = $fotoDetail->pluck('namafile')->filter()->values();
            $data->foto_detail = $fotoDetail;

            if (empty($data->namafile) && $data->foto_files->count() > 0) {
                $data->namafile = $data->foto_files->first();
            }

            if (empty($data->keterangan) && $fotoDetail->count() > 0) {
                $data->keterangan = $fotoDetail->first()->keterangan ?? null;
            }
        }

        $result = [
            'data' => $data,
            'as' => '@adit'
        ];

        return $this->respond($result);
    }

    public function hapusLaporanRepairAsman(Request $r)
    {
        DB::beginTransaction();
        try {
            $laporan = DB::table('laporanrepair_t')
                ->where('norec', $r['norec'])
                ->first();

            if ($laporan && $laporan->fotoalatrepair) {
                $filePath = public_path('berkas-laporan-repair/' . $laporan->fotoalatrepair);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            DB::table('laporanrepair_t')
                ->where('norec', $r['norec'])
                ->delete();

            $transMessage = "Hapus Laporan Repair Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Hapus Laporan Repair Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function setujuiLaporanRepairAsman(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['verif'];
            $timestamp = now();

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'asmansetujulaporanrepairfk' => $this->getPegawaiId(),
                    'tglsetujuasmanlaporanrepair' => now(),
                    'statusorderasman' => 2,
                ]);

            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $VI['norec'])->first();
            $noorderalat = $detail->noorderalat ?? '-';
            $waktuVerif = $detail->tglsetujuasmanlaporanrepair ?? $timestamp;

            try {
                $waktuVerifStr = \Carbon\Carbon::parse($waktuVerif)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuVerifStr = date('d-m-Y H:i', strtotime($waktuVerif)) . ' WIB';
            }

            $asman = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaAsman = $asman->namalengkap ?? '-';

            $recipients = [];

            // PENYELIA
            $penyeliaId = $detail->penyeliateknikfk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                if ($penyelia) {
                    $recipients[$penyelia->id] = [
                        'id' => $penyelia->id,
                        'nama' => $penyelia->namalengkap ?? '-',
                        'nohp' => $penyelia->nohandphone ?? null,
                        'role' => 'Penyelia Teknik',
                        'urlform' => 'module-dashboard-penyelia',
                        'pesan' => "Yth. {$penyelia->namalengkap},\n\n"
                            . "Laporan Repair pada order No. Order Alat: *$noorderalat* telah disetujui oleh Asman $namaAsman pada $waktuVerifStr.\n\n"
                            . "Terima kasih atas kerjasama Anda.\n\n"
                            . "Salam,\nLaboratorium Kalibrasi ULAB UMRO",
                    ];
                }
            }

            // PELAKSANA
            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            if ($pelaksanaId) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanaId)->first();
                if ($pelaksana) {
                    $recipients[$pelaksana->id] = [
                        'id' => $pelaksana->id,
                        'nama' => $pelaksana->namalengkap ?? '-',
                        'nohp' => $pelaksana->nohandphone ?? null,
                        'role' => 'Pelaksana Teknik',
                        'urlform' => 'module-dashboard-pelaksana',
                        'pesan' => "Yth. {$pelaksana->namalengkap},\n\n"
                            . "Laporan Repair pada order No. Order Alat: *$noorderalat* telah disetujui oleh Asman $namaAsman pada $waktuVerifStr.\n\n"
                            . "Terima kasih atas kerjasama Anda.\n\n"
                            . "Salam,\nLaboratorium Kalibrasi ULAB UMRO",
                    ];
                }
            }

            // MANAGER
            $namaManager = $detail->namamanager ?? null;
            if ($namaManager) {
                $manager = DB::table('pegawai_m')->where('namalengkap', $namaManager)->first();
                if ($manager) {
                    $recipients[$manager->id] = [
                        'id' => $manager->id,
                        'nama' => $manager->namalengkap ?? '-',
                        'nohp' => $manager->nohandphone ?? null,
                        'role' => 'Manager',
                        'urlform' => 'module-dashboard-manager',
                        'pesan' => "Yth. {$manager->namalengkap},\n\n"
                            . "Laporan Repair pada order No. Order Alat: *$noorderalat* telah disetujui oleh Asman $namaAsman pada $waktuVerifStr.\n\n"
                            . "Mohon segera diproses sesuai prosedur yang berlaku.\n\n"
                            . "Salam,\nLaboratorium Kalibrasi ULAB UMRO",
                    ];
                }
            }

            foreach ($recipients as $recipient) {
                $namaPenerima = $recipient['nama'] ?? '-';
                $nohpPenerima = $recipient['nohp'] ?? null;
                $rolePenerima = $recipient['role'] ?? '-';
                $urlForm = $recipient['urlform'] ?? 'module-dashboard';
                $pesan = $recipient['pesan'] ?? '';

                // Kirim WhatsApp
                if ($nohpPenerima) {
                    $this->kirimWhatsappNotifikasi($nohpPenerima, $pesan);
                }

                // Simpan notif DB
                $notif = new \App\Models\Master\ListNotif();
                $notif->norec = (string) \Illuminate\Support\Str::uuid();
                $notif->norec_trans = $VI['norec'] ?? (string) \Illuminate\Support\Str::uuid();
                $notif->judul = 'Persetujuan Laporan Repair';
                $notif->jenis = 'Laporan Repair';
                $notif->pegawaifk = $recipient['id'];
                $notif->namapegawai = $namaPenerima;
                $notif->keterangan = 'Laporan Repair No Order ' . $noorderalat . ' telah disetujui oleh Asman ' . $namaAsman . '.';
                $notif->tgl = now();
                $notif->tgl_string = now()->format('d-m-Y H:i');
                $notif->urlform = $urlForm;
                $notif->params = json_encode([
                    'norec_detail' => $VI['norec'],
                    'noorderalat' => $noorderalat,
                    'role_tujuan' => $rolePenerima,
                ]);
                $notif->dataarray = json_encode([
                    'detail_norec' => $VI['norec'],
                    'noorderalat' => $noorderalat,
                    'namaAsman' => $namaAsman,
                    'waktuVerif' => $waktuVerifStr,
                ]);
                $notif->statusenabled = true;
                $notif->isread = false;
                $notif->save();

                // Emit socket
                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->withHeaders([
                            'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                            'Accept' => 'application/json',
                        ])
                        ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                            'norec' => $notif->norec,
                            'norec_trans' => $notif->norec_trans,
                            'judul' => $notif->judul,
                            'jenis' => $notif->jenis,
                            'idPegawai' => $notif->pegawaifk,
                            'namapegawai' => $notif->namapegawai,
                            'pesanNotifikasi' => $notif->keterangan,
                            'tgl' => $notif->tgl,
                            'tgl_string' => $notif->tgl_string,
                            'urlForm' => $notif->urlform,
                            'params' => json_decode($notif->params, true),
                            'dataArray' => json_decode($notif->dataarray, true),
                        ]);
                } catch (\Exception $e) {
                    // abaikan jika socket gagal, notif DB tetap tersimpan
                }
            }

            $transMessage = "Simpan Setujui Laporan Repair Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function hapusLaporanStatus(Request $r)
    {
        DB::beginTransaction();
        try {
            $status = DB::table('statusalatrepair_t')
                ->where('norec', $r['norec'])
                ->first();

            if ($status && $status->fotoalatstatus) {
                $filePath = public_path('berkas-laporan-repair/' . $status->fotoalatstatus);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            DB::table('statusalatrepair_t')
                ->where('norec', $r['norec'])
                ->delete();

            $transMessage = "Hapus Status Alat Repair Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Hapus Status ALat Repair Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function dataChartIntegrasi(Request $request)
    {
        ini_set('max_execution_time', 100000);
        ini_set('memory_limit', '2048M');

        // --- FILTERING TAHUN ---
        $tglAwalRaw = (string) ($request->tglAwal ?? '');
        $years = collect(explode(',', $tglAwalRaw))
            ->map(fn($v) => (int) trim($v))
            ->filter(fn($y) => $y > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $yearMin = min($years);
        $yearMax = max($years);

        $startDate = sprintf('%04d-01-01 00:00:00', $yearMin);
        $endDate   = sprintf('%04d-01-01 00:00:00', $yearMax + 1);

        $unitfk = $request->input('unitfk') ?: $request->input('id');
        $lingkupFilter = trim((string) $request->input('lingkup', ''));
        $ulabFilter = strtoupper(trim((string) $request->input('ulab', '')));
        $locationId = $ulabFilter === 'JKT' ? 1 : ($ulabFilter === 'GRK' ? 2 : null);
        $locationExpression = "CASE
            WHEN LOWER(COALESCE(mtr.jenisorder, '')) = 'repair' THEN mtr.lokasirepair
            ELSE mtr.lokasikalibrasi
        END";
        $completedExpression = "CASE
            WHEN LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'
                THEN CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1 ELSE 0 END
            ELSE CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1 ELSE 0 END
        END";

        $applyRegistrationFilters = function ($query) use ($startDate, $endDate, $years, $unitfk, $locationId, $locationExpression) {
            $query
                ->where('mtr.tglregistrasi', '>=', $startDate)
                ->where('mtr.tglregistrasi', '<', $endDate)
                ->whereIn(DB::raw('EXTRACT(YEAR FROM mtr.tglregistrasi)::int'), $years);

            if (!empty($unitfk)) {
                $query->where('mtr.nomitrafk', $unitfk);
            }

            if ($locationId !== null) {
                $query->whereRaw("{$locationExpression} = ?", [$locationId]);
            }

            return $query;
        };

        $applyLingkupFilter = function ($query, string $alias = 'lp') use ($lingkupFilter) {
            if ($lingkupFilter !== '') {
                $query
                    ->where("{$alias}.statusenabled", true)
                    ->whereRaw("LOWER(COALESCE({$alias}.lingkupkalibrasi, '')) = ?", [strtolower($lingkupFilter)]);
            }

            return $query;
        };

        // --- 1. DATA MAP & MITRA ---
        $mitraEnabled = DB::table('mitra_m')
            ->select('id', 'namaperusahaan', 'alamatktr')
            ->where('statusenabled', true);

        if (!empty($unitfk)) {
            $mitraEnabled->where('id', $unitfk);
        }

        $mitraEnabled = $mitraEnabled->get();

        $jumlahMitra = $mitraEnabled->count();
        $jumlahMitraDetail = $mitraEnabled;

        $dataMap = DB::table('mitra_m')
            ->select('id', 'namaperusahaan', 'kapasitaspembangkit', 'alamatktr', 'lat', 'lng')
            ->where('statusenabled', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        // --- 2. DATA PROGRESS ALAT ---
        $progresQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->when($lingkupFilter !== '', function ($query) {
                $query
                    ->join('mitraregistrasidetail_t as filter_mtrd', 'filter_mtrd.noregistrasifk', '=', 'mtr.norec')
                    ->leftJoin('lingkupkalibrasi_m as filter_lp', 'filter_lp.id', '=', 'filter_mtrd.lingkupkalibrasifk')
                    ->where('filter_mtrd.statusenabled', true);
            })
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true);

        $applyRegistrationFilters($progresQuery);
        $applyLingkupFilter($progresQuery, 'filter_lp');

        $jumlahProgresAlat = (clone $progresQuery)
            ->distinct('mtr.nomitrafk')
            ->count('mtr.nomitrafk');

        $jumlahProgresAlatDetail = (clone $progresQuery)
            ->select('mt.namaperusahaan', 'mtr.jenisorder', 'mtr.tglregistrasi')
            ->distinct()
            ->get();

        $jenisAgg = (clone $progresQuery)
            ->selectRaw("
            COUNT(DISTINCT CASE WHEN LOWER(mtr.jenisorder) = 'kalibrasi' THEN mtr.norec END) as jumlah_alat_kalibrasi,
            COUNT(DISTINCT CASE WHEN LOWER(mtr.jenisorder) = 'repair' THEN mtr.norec END) as jumlah_alat_repair
        ")
            ->first();

        $jumlahAlatKalibrasi = (int) ($jenisAgg->jumlah_alat_kalibrasi ?? 0);
        $jumlahAlatRepair    = (int) ($jenisAgg->jumlah_alat_repair ?? 0);

        $jumlahAlatKalibrasiDetail = $jumlahProgresAlatDetail
            ->filter(fn($r) => strtolower((string)$r->jenisorder) === 'kalibrasi')
            ->values();

        $jumlahAlatRepairDetail = $jumlahProgresAlatDetail
            ->filter(fn($r) => strtolower((string)$r->jenisorder) === 'repair')
            ->values();

        // --- 3. DATA UNIT HARIAN ---
        $dataUnitQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->when($lingkupFilter !== '', function ($query) {
                $query
                    ->join('mitraregistrasidetail_t as filter_mtrd', 'filter_mtrd.noregistrasifk', '=', 'mtr.norec')
                    ->leftJoin('lingkupkalibrasi_m as filter_lp', 'filter_lp.id', '=', 'filter_mtrd.lingkupkalibrasifk')
                    ->where('filter_mtrd.statusenabled', true);
            })
            ->select(
                DB::raw("DATE(mtr.tglregistrasi) as tanggal"),
                'mtr.jenisorder',
                DB::raw("COUNT(DISTINCT mtr.norec) as jumlah")
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->groupBy(DB::raw("DATE(mtr.tglregistrasi)"), 'mtr.jenisorder');

        $applyRegistrationFilters($dataUnitQuery);
        $applyLingkupFilter($dataUnitQuery, 'filter_lp');

        $dataUnit = $dataUnitQuery->get();

        $dataPivot = [];
        foreach ($dataUnit as $row) {
            $tgl = $row->tanggal;
            $jenis = strtolower((string)$row->jenisorder);
            $jumlah = (int) $row->jumlah;

            if (!isset($dataPivot[$tgl])) {
                $dataPivot[$tgl] = [
                    'tanggal' => $tgl,
                    'kalibrasi' => 0,
                    'repair' => 0,
                ];
            }

            if (array_key_exists($jenis, $dataPivot[$tgl])) {
                $dataPivot[$tgl][$jenis] = $jumlah;
            }
        }
        $dataunit = array_values($dataPivot);

        // --- 4. DATA LINGKUP ---
        $daftarLingkupTetap = DB::table('lingkupkalibrasi_m')
            ->where('statusenabled', true)
            ->pluck('lingkupkalibrasi')
            ->map(fn($item) => strtolower((string)$item))
            ->toArray();

        $lingkupRows = DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mitraregistrasi_t as mtr', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                DB::raw("{$locationExpression} as lokasi"),
                'lp.lingkupkalibrasi as lingkup',
                DB::raw('COUNT(*) as jumlah')
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('lp.statusenabled', true)
            ->whereNotNull('mtrd.lingkupkalibrasifk');

        $applyRegistrationFilters($lingkupRows);
        $applyLingkupFilter($lingkupRows);

        $lingkupRows = $lingkupRows
            ->groupBy(DB::raw($locationExpression), 'lp.lingkupkalibrasi')
            ->get();

        $pivotJakarta = [0 => ['jumlahalat' => 0]];
        $pivotGresik  = [0 => ['jumlahalat' => 0]];
        foreach ($daftarLingkupTetap as $lingkup) {
            $pivotJakarta[0][$lingkup] = 0;
            $pivotGresik[0][$lingkup]  = 0;
        }

        foreach ($lingkupRows as $row) {
            $lokasi = (int) $row->lokasi;
            $lingkupLower = strtolower((string)$row->lingkup);
            $jumlah = (int) $row->jumlah;

            if ($lokasi === 1) {
                if (array_key_exists($lingkupLower, $pivotJakarta[0])) {
                    $pivotJakarta[0][$lingkupLower] = $jumlah;
                }
                $pivotJakarta[0]['jumlahalat'] += $jumlah;
            } elseif ($lokasi === 2) {
                if (array_key_exists($lingkupLower, $pivotGresik[0])) {
                    $pivotGresik[0][$lingkupLower] = $jumlah;
                }
                $pivotGresik[0]['jumlahalat'] += $jumlah;
            }
        }

        $jumlahAlatLingkup = array_values($pivotJakarta);
        $jumlahAlatLingkupGresik = array_values($pivotGresik);

        // --- 5. DATA ORDER & REPAIR PER UNIT ---
        $orderUnitRows = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                DB::raw("{$locationExpression} as lokasi"),
                'mt.namaperusahaan',
                DB::raw("COUNT(*) as jumlah"),
                DB::raw("COUNT(CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1 END) as jumlahselesai"),
                DB::raw("COUNT(*) - COUNT(CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1 END) as jumlahbelumselesai")
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereRaw("LOWER(COALESCE(mtr.jenisorder, '')) = 'kalibrasi'");

        $applyRegistrationFilters($orderUnitRows);
        $applyLingkupFilter($orderUnitRows);

        $orderUnitRows = $orderUnitRows
            ->groupBy(DB::raw($locationExpression), 'mt.namaperusahaan')
            ->get();

        $jumlahOrderUnitJakarta = $orderUnitRows->filter(fn($r) => (int)$r->lokasi === 1)->values();
        $jumlahOrderUnitGresik  = $orderUnitRows->filter(fn($r) => (int)$r->lokasi === 2)->values();

        $repairUnitRows = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                DB::raw("{$locationExpression} as lokasi"),
                'mt.namaperusahaan',
                DB::raw("COUNT(*) as jumlah"),
                DB::raw("COUNT(CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1 END) as jumlahselesai"),
                DB::raw("COUNT(*) - COUNT(CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1 END) as jumlahbelumselesai")
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereRaw("LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'");

        $applyRegistrationFilters($repairUnitRows);
        $applyLingkupFilter($repairUnitRows);

        $repairUnitRows = $repairUnitRows
            ->groupBy(DB::raw($locationExpression), 'mt.namaperusahaan')
            ->get();

        $jumlahRepairUnitJakarta = $repairUnitRows->filter(fn($r) => (int)$r->lokasi === 1)->values();
        $jumlahRepairUnitGresik  = $repairUnitRows->filter(fn($r) => (int)$r->lokasi === 2)->values();

        // --- 6. DATA STATUS KALIBRASI LINGKUP & PELAKSANA ---
        $kalLingkupRows = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                DB::raw("{$locationExpression} as lokasi"),
                'lp.lingkupkalibrasi as lingkup',
                DB::raw('COUNT(*) as jumlah'),
                DB::raw("SUM({$completedExpression}) as jumlahselesai"),
                DB::raw("COUNT(*) - SUM({$completedExpression}) as jumlahbelumselesai")
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('lp.statusenabled', true)
            ->whereNotNull('mtrd.lingkupkalibrasifk');

        $applyRegistrationFilters($kalLingkupRows);
        $applyLingkupFilter($kalLingkupRows);

        $kalLingkupRows = $kalLingkupRows
            ->groupBy(DB::raw($locationExpression), 'lp.lingkupkalibrasi')
            ->get();

        $jumlahKalibrasiLingkupJakarta = $kalLingkupRows->filter(fn($r) => (int)$r->lokasi === 1)->values();
        $jumlahKalibrasiLingkupGresik  = $kalLingkupRows->filter(fn($r) => (int)$r->lokasi === 2)->values();

        $pelKalRows = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'mtr.lokasikalibrasi as lokasi',
                'pg2.id as pelaksana_id',
                DB::raw("COALESCE(pg2.namalengkap, '(Belum ditetapkan)') as pelaksana"),
                DB::raw('COUNT(*) as jumlah'),
                DB::raw("COUNT(CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1 END) as jumlahselesai"),
                DB::raw("COUNT(*) - COUNT(CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1 END) as jumlahbelumselesai")
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereRaw("LOWER(COALESCE(mtr.jenisorder, '')) = 'kalibrasi'");

        $applyRegistrationFilters($pelKalRows);
        $applyLingkupFilter($pelKalRows);

        $pelKalRows = $pelKalRows
            ->whereIn('mtr.lokasikalibrasi', [1, 2])
            ->groupBy('mtr.lokasikalibrasi', 'pg2.id', 'pg2.namalengkap')
            ->orderByDesc('jumlah')
            ->get();

        $jumlahPerPelaksanaJKT = $pelKalRows->filter(fn($r) => (int)$r->lokasi === 1)->values();
        $jumlahPerPelaksanaGRK = $pelKalRows->filter(fn($r) => (int)$r->lokasi === 2)->values();

        $pelRepRows = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'mtr.lokasirepair as lokasi',
                'pg2.id as pelaksana_id',
                DB::raw("COALESCE(pg2.namalengkap, '(Belum ditetapkan)') as pelaksana"),
                DB::raw('COUNT(*) as jumlah'),
                DB::raw("COUNT(CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1 END) as jumlahselesai"),
                DB::raw("COUNT(*) - COUNT(CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1 END) as jumlahbelumselesai")
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereRaw("LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'");

        $applyRegistrationFilters($pelRepRows);
        $applyLingkupFilter($pelRepRows);

        $pelRepRows = $pelRepRows
            ->whereIn('mtr.lokasirepair', [1, 2])
            ->groupBy('mtr.lokasirepair', 'pg2.id', 'pg2.namalengkap')
            ->orderByDesc('jumlah')
            ->get();

        $jumlahPerPelaksanaRepairJKT = $pelRepRows->filter(fn($r) => (int)$r->lokasi === 1)->values();
        $jumlahPerPelaksanaRepairGRK = $pelRepRows->filter(fn($r) => (int)$r->lokasi === 2)->values();


        // =========================================================================
        // START: ANALISIS WAKTU & PERFORMA PELAKSANA
        // =========================================================================

        $analysisQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftJoin('pegawai_m as pg_tech', 'pg_tech.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                DB::raw("{$locationExpression} as lokasi"),
                'mtr.jenisorder',
                'mtr.tglregistrasi',
                'mtr.tglkajiulang',
                'mtr.tglverifasman',
                'mtrd.tglverifpelaksana',
                'mtrd.tglisilembarkerjapelaksana',
                'mtrd.tglsetujupenyelialembarkerja',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglisilaporanrepairpelaksana',
                'mtrd.tglsetujupenyelialaporanrepair',
                'mtrd.tglsetujuasmanlaporanrepair',
                'mtrd.tglsetujumanagerlaporanrepair',
                'pg_tech.id as teknisi_id',
                'pg_tech.namalengkap as teknisi_nama'
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.iskaji', true);

        $applyRegistrationFilters($analysisQuery);
        $applyLingkupFilter($analysisQuery);

        $analysisData = $analysisQuery->get();
        $stages = [
            'admin_review',
            'asman_verification',
            'executor_verification',
            'technical_work',
            'supervisor_approval',
            'asman_approval',
            'manager_approval',
        ];
        $categoriesLabel = [
            'Kaji Ulang Admin',
            'Verifikasi Asman (SPK)',
            'Verifikasi Pelaksana',
            'Pengerjaan Alat (Teknis)',
            'Approval Penyelia',
            'Approval Asman',
            'Approval Manager',
        ];

        $jktStats = array_fill_keys($stages, ['total_hours' => 0, 'count' => 0]);
        $grkStats = array_fill_keys($stages, ['total_hours' => 0, 'count' => 0]);

        $techPerformance = [];
        $verificationPerformance = [];
        $holidayDates = DB::table('master_hari_libur_m')
            ->where('statusenabled', true)
            ->pluck('tanggal')
            ->map(fn($tanggal) => Carbon::parse($tanggal)->format('Y-m-d'))
            ->flip()
            ->all();

        $calcWorkingHours = function ($start, $end) use ($holidayDates): ?float {
            if (empty($start) || empty($end)) {
                return null;
            }

            try {
                $startAt = Carbon::parse($start);
                $endAt = Carbon::parse($end);
            } catch (\Throwable $e) {
                return null;
            }

            if ($endAt->lessThan($startAt)) {
                return null;
            }

            $totalSeconds = 0;
            $cursor = $startAt->copy()->startOfDay();
            $lastDay = $endAt->copy()->startOfDay();

            while ($cursor->lte($lastDay)) {
                $currentDate = $cursor->format('Y-m-d');
                $isWeekend = $cursor->isoWeekday() >= 6;

                if (!$isWeekend && !isset($holidayDates[$currentDate])) {
                    $dayWorkStart = $cursor->copy()->setTime(7, 30);
                    $dayWorkEnd = $cursor->copy()->setTime(16, 0);
                    $effectiveStart = $startAt->copy()->max($dayWorkStart);
                    $effectiveEnd = $endAt->copy()->min($dayWorkEnd);

                    if ($effectiveEnd->greaterThan($effectiveStart)) {
                        $totalSeconds += $effectiveEnd->diffInSeconds($effectiveStart);
                    }
                }

                $cursor->addDay();
            }

            return $totalSeconds / 3600;
        };

        foreach ($analysisData as $row) {
            $isJkt = ((int) $row->lokasi === 1);
            $isGrk = ((int) $row->lokasi === 2);

            if ($isJkt || $isGrk) {
                $isRepair = strtolower((string) $row->jenisorder) === 'repair';
                $technicalFinishedAt = $isRepair
                    ? $row->tglisilaporanrepairpelaksana
                    : $row->tglisilembarkerjapelaksana;
                $supervisorApprovedAt = $isRepair
                    ? $row->tglsetujupenyelialaporanrepair
                    : $row->tglsetujupenyelialembarkerja;
                $asmanApprovedAt = $isRepair
                    ? $row->tglsetujuasmanlaporanrepair
                    : $row->tglsetujuasmanlembarkerja;
                $managerApprovedAt = $isRepair
                    ? $row->tglsetujumanagerlaporanrepair
                    : $row->tglsetujumanagerlembarkerja;

                $durations = [
                    'admin_review' => $calcWorkingHours($row->tglregistrasi, $row->tglkajiulang),
                    'asman_verification' => $calcWorkingHours($row->tglkajiulang, $row->tglverifasman),
                    'executor_verification' => $calcWorkingHours($row->tglverifasman, $row->tglverifpelaksana),
                    'technical_work' => $calcWorkingHours($row->tglverifpelaksana, $technicalFinishedAt),
                    'supervisor_approval' => $calcWorkingHours($technicalFinishedAt, $supervisorApprovedAt),
                    'asman_approval' => $calcWorkingHours($supervisorApprovedAt, $asmanApprovedAt),
                    'manager_approval' => $calcWorkingHours($asmanApprovedAt, $managerApprovedAt),
                ];

                if ($isJkt) {
                    $stats =& $jktStats;
                } else {
                    $stats =& $grkStats;
                }

                foreach ($durations as $stage => $duration) {
                    if ($duration !== null) {
                        $stats[$stage]['total_hours'] += $duration;
                        $stats[$stage]['count']++;
                    }
                }
                unset($stats);

                if (!empty($row->teknisi_nama)) {
                    $lokasi = $isJkt ? 'Jakarta' : 'Gresik';
                    $performanceKey = !empty($row->teknisi_id)
                        ? 'id:' . $row->teknisi_id
                        : 'nama:' . strtolower(trim($row->teknisi_nama));

                    $performanceDurations = [
                        'technical' => $durations['technical_work'],
                        'verification' => $durations['executor_verification'],
                    ];

                    foreach ($performanceDurations as $performanceType => $duration) {
                        if ($duration === null) {
                            continue;
                        }

                        if ($performanceType === 'technical') {
                            $performance =& $techPerformance;
                        } else {
                            $performance =& $verificationPerformance;
                        }

                        if (!isset($performance[$performanceKey])) {
                            $performance[$performanceKey] = [
                                'pelaksana_id' => $row->teknisi_id,
                                'nama' => $row->teknisi_nama,
                                'total' => 0,
                                'count' => 0,
                                'lokasi' => [],
                            ];
                        }

                        $performance[$performanceKey]['total'] += $duration;
                        $performance[$performanceKey]['count']++;
                        $performance[$performanceKey]['lokasi'][$lokasi] = true;
                        unset($performance);
                    }
                }
            }
        }

        $seriesJkt = [];
        foreach ($stages as $stg) {
            $count = $jktStats[$stg]['count'];
            $total = $jktStats[$stg]['total_hours'];
            $seriesJkt[] = $count > 0 ? round($total / $count, 1) : 0;
        }

        $seriesGrk = [];
        foreach ($stages as $stg) {
            $count = $grkStats[$stg]['count'];
            $total = $grkStats[$stg]['total_hours'];
            $seriesGrk[] = $count > 0 ? round($total / $count, 1) : 0;
        }

        $dataAnalisiJKT = [
            'series' => [['name' => 'Rata-rata Durasi (Jam)', 'data' => $seriesJkt]],
            'categories' => $categoriesLabel
        ];
        $dataAnalisiGRK = [
            'series' => [['name' => 'Rata-rata Durasi (Jam)', 'data' => $seriesGrk]],
            'categories' => $categoriesLabel
        ];

        $listPelaksanaLambat = [];
        foreach ($techPerformance as $data) {
            if ($data['count'] > 0) {
                $avg = round($data['total'] / $data['count'], 1);
                $listPelaksanaLambat[] = [
                    'pelaksana_id' => $data['pelaksana_id'],
                    'nama' => $data['nama'],
                    'rata_rata_jam' => $avg,
                    'jumlah_alat' => $data['count'],
                    'lokasi' => implode(' & ', array_keys($data['lokasi']))
                ];
            }
        }

        usort($listPelaksanaLambat, function ($a, $b) {
            return $b['rata_rata_jam'] <=> $a['rata_rata_jam'];
        });

        $listVerifikasiPelaksana = [];
        foreach ($verificationPerformance as $data) {
            if ($data['count'] > 0) {
                $listVerifikasiPelaksana[] = [
                    'pelaksana_id' => $data['pelaksana_id'],
                    'nama' => $data['nama'],
                    'rata_rata_jam' => round($data['total'] / $data['count'], 1),
                    'jumlah_alat' => $data['count'],
                    'lokasi' => implode(' & ', array_keys($data['lokasi']))
                ];
            }
        }

        usort($listVerifikasiPelaksana, function ($a, $b) {
            return $b['rata_rata_jam'] <=> $a['rata_rata_jam'];
        });

        // --- 7. RINGKASAN STATUS PENYELESAIAN & PENGAMBILAN PER ULAB ---
        $statusSummaryQuery = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                DB::raw("{$locationExpression} as lokasi"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM({$completedExpression}) as selesai"),
                DB::raw("COUNT(*) - SUM({$completedExpression}) as belum_selesai"),
                DB::raw("SUM(CASE WHEN {$completedExpression} = 1 AND COALESCE(mtrd.isterima, false) = true THEN 1 ELSE 0 END) as sudah_diambil"),
                DB::raw("SUM(CASE WHEN {$completedExpression} = 1 AND COALESCE(mtrd.isterima, false) = false THEN 1 ELSE 0 END) as belum_diambil")
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true);

        $applyRegistrationFilters($statusSummaryQuery);
        $applyLingkupFilter($statusSummaryQuery);

        $statusSummaryRows = $statusSummaryQuery
            ->groupBy(DB::raw($locationExpression))
            ->get()
            ->keyBy(fn($row) => (int) $row->lokasi);

        $formatStatusSummary = function (int $locationId) use ($statusSummaryRows) {
            $row = $statusSummaryRows->get($locationId);

            return [
                'total' => (int) ($row->total ?? 0),
                'selesai' => (int) ($row->selesai ?? 0),
                'belum_selesai' => (int) ($row->belum_selesai ?? 0),
                'sudah_diambil' => (int) ($row->sudah_diambil ?? 0),
                'belum_diambil' => (int) ($row->belum_diambil ?? 0),
            ];
        };

        $result = array(
            'data' => $dataMap,
            'dataunit' => $dataunit,
            'jumlahMitra' => $jumlahMitra,
            'jumlahMitraDetail' => $jumlahMitraDetail,
            'jumlahProgresAlat' => $jumlahProgresAlat,
            'jumlahProgresAlatDetail' => $jumlahProgresAlatDetail,
            'jumlahAlatKalibrasi' => $jumlahAlatKalibrasi,
            'jumlahAlatKalibrasiDetail' => $jumlahAlatKalibrasiDetail,
            'jumlahAlatRepair' => $jumlahAlatRepair,
            'jumlahAlatRepairDetail' => $jumlahAlatRepairDetail,
            'jumlahAlatLingkup' => $jumlahAlatLingkup,
            'jumlahAlatLingkupGresik' => $jumlahAlatLingkupGresik,
            'jumlahOrderUnitJakarta' => $jumlahOrderUnitJakarta,
            'jumlahOrderUnitGresik' => $jumlahOrderUnitGresik,
            'jumlahRepairUnitJakarta' => $jumlahRepairUnitJakarta,
            'jumlahRepairUnitGresik' => $jumlahRepairUnitGresik,
            'jumlahKalibrasiLingkupJakarta' => $jumlahKalibrasiLingkupJakarta,
            'jumlahKalibrasiLingkupGresik' => $jumlahKalibrasiLingkupGresik,
            'jumlahPerPelaksanaJKT' => $jumlahPerPelaksanaJKT,
            'jumlahPerPelaksanaGRK' => $jumlahPerPelaksanaGRK,
            'jumlahPerPelaksanaRepairJKT' => $jumlahPerPelaksanaRepairJKT,
            'jumlahPerPelaksanaRepairGRK' => $jumlahPerPelaksanaRepairGRK,
            'dataAnalisiJKT' => $dataAnalisiJKT,
            'dataAnalisiGRK' => $dataAnalisiGRK,
            'analisisPelaksanaLambat' => $listPelaksanaLambat,
            'analisisVerifikasiPelaksana' => $listVerifikasiPelaksana,
            'statusRingkasJakarta' => $formatStatusSummary(1),
            'statusRingkasGresik' => $formatStatusSummary(2),
        );

        return $this->respond($result);
    }

    public function detailChartIntegrasi(Request $request)
    {
        ini_set('max_execution_time', 100000);
        ini_set('memory_limit', '2048M');

        $years = collect(explode(',', (string) ($request->tglAwal ?? '')))
            ->map(fn($value) => (int) trim($value))
            ->filter(fn($year) => $year > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $chart = strtolower(trim((string) $request->input('chart', '')));
        $category = trim((string) $request->input('category', ''));
        $status = strtolower(trim((string) $request->input('status', 'total')));
        $unitfk = $request->input('unitfk');
        $lingkupFilter = trim((string) $request->input('lingkup', ''));
        $effectiveUlab = strtoupper(trim((string) ($request->input('chart_ulab') ?: $request->input('ulab', ''))));
        $locationId = $effectiveUlab === 'JKT' ? 1 : ($effectiveUlab === 'GRK' ? 2 : null);

        $locationExpression = "CASE
            WHEN LOWER(COALESCE(mtr.jenisorder, '')) = 'repair' THEN mtr.lokasirepair
            ELSE mtr.lokasikalibrasi
        END";
        $completedExpression = "CASE
            WHEN LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'
                THEN CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1 ELSE 0 END
            ELSE CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1 ELSE 0 END
        END";

        $query = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.pelaksanateknikfk')
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
            ->select(
                'mtrd.norec as detail_norec',
                'mtrd.noorderalat',
                'mtr.tglregistrasi',
                'mtr.jenisorder',
                'mt.id as unit_id',
                'mt.namaperusahaan',
                'lp.lingkupkalibrasi',
                'pg.id as pelaksana_id',
                DB::raw("COALESCE(pg.namalengkap, '(Belum ditetapkan)') as pelaksana"),
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk, '(Nama alat belum tersedia)') as namaalat"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk, '-') as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe, '-') as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber, '-') as namaserialnumber"),
                DB::raw("{$locationExpression} as lokasi_id"),
                DB::raw("CASE WHEN {$locationExpression} = 1 THEN 'Jakarta' WHEN {$locationExpression} = 2 THEN 'Gresik' ELSE '-' END as lokasi"),
                DB::raw("CASE WHEN {$completedExpression} = 1 THEN 'Selesai' ELSE 'Belum Selesai' END as status_penyelesaian"),
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
                'mtrd.isterima'
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mt.statusenabled', true)
            ->whereIn(DB::raw('EXTRACT(YEAR FROM mtr.tglregistrasi)::int'), $years);

        if (!empty($unitfk)) {
            $query->where('mtr.nomitrafk', $unitfk);
        }

        if ($locationId !== null) {
            $query->whereRaw("{$locationExpression} = ?", [$locationId]);
        }

        if ($lingkupFilter !== '') {
            $query->whereRaw("LOWER(COALESCE(lp.lingkupkalibrasi, '')) = ?", [strtolower($lingkupFilter)]);
        }

        if (in_array($chart, ['kalibrasi_unit', 'kalibrasi_pelaksana'], true)) {
            $query->whereRaw("LOWER(COALESCE(mtr.jenisorder, '')) = 'kalibrasi'");
        } elseif (in_array($chart, ['repair_unit', 'repair_pelaksana'], true)) {
            $query->whereRaw("LOWER(COALESCE(mtr.jenisorder, '')) = 'repair'");
        }

        if ($category !== '') {
            if (in_array($chart, ['kalibrasi_unit', 'repair_unit'], true)) {
                $query->whereRaw('LOWER(mt.namaperusahaan) = ?', [strtolower($category)]);
            } elseif (in_array($chart, ['ruang_lingkup', 'lingkup_pie'], true)) {
                $query->whereRaw("LOWER(COALESCE(lp.lingkupkalibrasi, '')) = ?", [strtolower($category)]);
            } elseif (in_array($chart, ['kalibrasi_pelaksana', 'repair_pelaksana', 'durasi_pelaksana', 'durasi_verifikasi_pelaksana'], true)) {
                $query->whereRaw("LOWER(COALESCE(pg.namalengkap, '(Belum ditetapkan)')) = ?", [strtolower($category)]);
            }
        }

        if (!empty($request->input('pelaksana_id'))) {
            $query->where('pg.id', $request->input('pelaksana_id'));
        }

        if ($chart === 'status_pengambilan') {
            $query->whereRaw("{$completedExpression} = 1");
        }

        if ($status === 'selesai') {
            $query->whereRaw("{$completedExpression} = 1");
        } elseif ($status === 'belum_selesai') {
            $query->whereRaw("{$completedExpression} = 0");
        } elseif ($status === 'sudah_diambil') {
            $query->whereRaw('COALESCE(mtrd.isterima, false) = true');
        } elseif ($status === 'belum_diambil') {
            $query->whereRaw('COALESCE(mtrd.isterima, false) = false');
        }

        $rows = $query
            ->orderByDesc('mtr.tglregistrasi')
            ->limit(min(max((int) $request->input('limit', 3000), 1), 5000))
            ->get();

        $stage = strtolower(trim((string) $request->input('stage', '')));
        if (in_array($chart, ['durasi_pelaksana', 'durasi_verifikasi_pelaksana'], true) || ($chart === 'analisis_tahap' && $stage !== '')) {
            $holidayDates = DB::table('master_hari_libur_m')
                ->where('statusenabled', true)
                ->pluck('tanggal')
                ->map(fn($tanggal) => Carbon::parse($tanggal)->format('Y-m-d'))
                ->flip()
                ->all();

            $calcWorkingHours = function ($start, $end) use ($holidayDates): ?float {
                if (empty($start) || empty($end)) {
                    return null;
                }

                try {
                    $startAt = Carbon::parse($start);
                    $endAt = Carbon::parse($end);
                } catch (\Throwable $e) {
                    return null;
                }

                if ($endAt->lessThan($startAt)) {
                    return null;
                }

                $totalSeconds = 0;
                $cursor = $startAt->copy()->startOfDay();
                $lastDay = $endAt->copy()->startOfDay();

                while ($cursor->lte($lastDay)) {
                    $date = $cursor->format('Y-m-d');
                    if ($cursor->isoWeekday() < 6 && !isset($holidayDates[$date])) {
                        $workStart = $cursor->copy()->setTime(7, 30);
                        $workEnd = $cursor->copy()->setTime(16, 0);
                        $effectiveStart = $startAt->copy()->max($workStart);
                        $effectiveEnd = $endAt->copy()->min($workEnd);

                        if ($effectiveEnd->greaterThan($effectiveStart)) {
                            $totalSeconds += $effectiveEnd->diffInSeconds($effectiveStart);
                        }
                    }
                    $cursor->addDay();
                }

                return round($totalSeconds / 3600, 1);
            };

            $rows = $rows->map(function ($row) use ($chart, $stage, $calcWorkingHours) {
                $isRepair = strtolower((string) $row->jenisorder) === 'repair';
                $technicalFinishedAt = $isRepair ? $row->tglisilaporanrepairpelaksana : $row->tglisilembarkerjapelaksana;
                $supervisorApprovedAt = $isRepair ? $row->tglsetujupenyelialaporanrepair : $row->tglsetujupenyelialembarkerja;
                $asmanApprovedAt = $isRepair ? $row->tglsetujuasmanlaporanrepair : $row->tglsetujuasmanlembarkerja;
                $managerApprovedAt = $isRepair ? $row->tglsetujumanagerlaporanrepair : $row->tglsetujumanagerlembarkerja;

                $stageDates = [
                    'admin_review' => [$row->tglregistrasi, $row->tglkajiulang],
                    'asman_verification' => [$row->tglkajiulang, $row->tglverifasman],
                    'executor_verification' => [$row->tglverifasman, $row->tglverifpelaksana],
                    'technical_work' => [$row->tglverifpelaksana, $technicalFinishedAt],
                    'supervisor_approval' => [$technicalFinishedAt, $supervisorApprovedAt],
                    'asman_approval' => [$supervisorApprovedAt, $asmanApprovedAt],
                    'manager_approval' => [$asmanApprovedAt, $managerApprovedAt],
                ];

                $selectedStage = $chart === 'durasi_pelaksana'
                    ? 'technical_work'
                    : ($chart === 'durasi_verifikasi_pelaksana' ? 'executor_verification' : $stage);
                $dates = $stageDates[$selectedStage] ?? [null, null];
                $row->durasi_jam = $calcWorkingHours($dates[0], $dates[1]);

                return $row;
            })->filter(fn($row) => $row->durasi_jam !== null)->values();
        }

        $rows = $rows->map(function ($row) {
            $isRepair = strtolower((string) $row->jenisorder) === 'repair';
            $isCompleted = $row->status_penyelesaian === 'Selesai';

            $isPickedUp = filter_var($row->isterima, FILTER_VALIDATE_BOOLEAN);
            $row->status_pengambilan = $isPickedUp ? 'Sudah Diambil' : 'Belum Diambil';

            if ($isCompleted) {
                $row->status_terakhir = $isRepair
                    ? 'Selesai - Laporan repair disetujui Manager'
                    : 'Selesai - Sertifikat disetujui Manager';
                return $row;
            }

            if ($isRepair) {
                if (!empty($row->tglsetujuasmanlaporanrepair)) {
                    $row->status_terakhir = 'Menunggu persetujuan Manager';
                } elseif (!empty($row->tglsetujupenyelialaporanrepair)) {
                    $row->status_terakhir = 'Menunggu persetujuan Asman';
                } elseif (!empty($row->tglisilaporanrepairpenyelia)) {
                    $row->status_terakhir = 'Menunggu persetujuan Penyelia';
                } elseif (!empty($row->tglisilaporanrepairpelaksana)) {
                    $row->status_terakhir = 'Menunggu pemeriksaan/pengisian Penyelia';
                } elseif (!empty($row->tglverifpelaksana)) {
                    $row->status_terakhir = 'Laporan repair belum diisi Pelaksana';
                } elseif (!empty($row->tglverifpenyelia)) {
                    $row->status_terakhir = 'Menunggu verifikasi Pelaksana';
                } elseif (!empty($row->tglverifasman)) {
                    $row->status_terakhir = 'Menunggu verifikasi Penyelia';
                } elseif (!empty($row->tglkajiulang)) {
                    $row->status_terakhir = 'Menunggu verifikasi Asman';
                } else {
                    $row->status_terakhir = 'Menunggu kaji ulang Admin';
                }
            } else {
                if (!empty($row->tglsetujuasmanlembarkerja)) {
                    $row->status_terakhir = 'Menunggu persetujuan Manager';
                } elseif (!empty($row->tglsetujupenyelialembarkerja)) {
                    $row->status_terakhir = 'Menunggu persetujuan Asman';
                } elseif (!empty($row->tglisilembarkerjapenyelia)) {
                    $row->status_terakhir = 'Menunggu persetujuan Penyelia';
                } elseif (!empty($row->tglisilembarkerjapelaksana)) {
                    $row->status_terakhir = 'Menunggu pemeriksaan/pengisian Penyelia';
                } elseif (!empty($row->tglverifpelaksana)) {
                    $row->status_terakhir = 'Sertifikat/lembar kerja belum diisi Pelaksana';
                } elseif (!empty($row->tglverifpenyelia)) {
                    $row->status_terakhir = 'Menunggu verifikasi Pelaksana';
                } elseif (!empty($row->tglverifasman)) {
                    $row->status_terakhir = 'Menunggu verifikasi Penyelia';
                } elseif (!empty($row->tglkajiulang)) {
                    $row->status_terakhir = 'Menunggu verifikasi Asman';
                } else {
                    $row->status_terakhir = 'Menunggu kaji ulang Admin';
                }
            }

            return $row;
        })->values();

        return $this->respond([
            'data' => $rows,
            'total' => $rows->count(),
        ]);
    }

    public function dataChartDashboard(Request $request)
    {
        ini_set('max_execution_time', 100000);
        ini_set('memory_limit', '2048M');
        $tglAwal = $request->tglAwal;

        $daftarLingkupTetap = DB::table('lingkupkalibrasi_m')
            ->where('statusenabled', true)
            ->pluck('lingkupkalibrasi')
            ->map(fn($item) => strtolower($item))
            ->toArray();
        $rawData = DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mitraregistrasi_t as mtr', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'lp.lingkupkalibrasi as lingkup',
                DB::raw('COUNT(*) as jumlah')
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereRaw('COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?', [1])
            ->whereNotNull('mtrd.lingkupkalibrasifk')
            // ->whereRaw("TO_CHAR(mtr.tglregistrasi, 'YYYY') = ?", [$tglAwal])
            ->groupBy('lp.lingkupkalibrasi');

        if (!empty($request['unitfk'])) {
            $rawData = $rawData->where('mtr.nomitrafk', $request['unitfk']);
        }
        $rawData = $rawData->get();
        $pivoted = [];
        $pivoted[0] = ['jumlahalat' => 0];
        foreach ($daftarLingkupTetap as $lingkup) {
            $pivoted[0][$lingkup] = 0;
        }
        foreach ($rawData as $row) {
            $lingkupLower = strtolower($row->lingkup);
            $pivoted[0][$lingkupLower] = $row->jumlah;
            $pivoted[0]['jumlahalat'] += $row->jumlah;
        }
        $jumlahAlatLingkup = array_values($pivoted);

        $rawDataGresik = DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mitraregistrasi_t as mtr', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->select(
                'lp.lingkupkalibrasi as lingkup',
                DB::raw('COUNT(*) as jumlah')
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->whereRaw('COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?', [2])
            ->whereNotNull('mtrd.lingkupkalibrasifk')
            // ->whereRaw("TO_CHAR(mtr.tglregistrasi, 'YYYY') = ?", [$tglAwal])
            ->groupBy('lp.lingkupkalibrasi');

        if (!empty($request['unitfk'])) {
            $rawDataGresik = $rawDataGresik->where('mtr.nomitrafk', $request['unitfk']);
        }
        $rawDataGresik = $rawDataGresik->get();
        $pivotedGresik = [];
        $pivotedGresik[0] = ['jumlahalat' => 0];
        foreach ($daftarLingkupTetap as $lingkup) {
            $pivotedGresik[0][$lingkup] = 0;
        }
        foreach ($rawDataGresik as $row) {
            $lingkupLower = strtolower($row->lingkup);
            $pivotedGresik[0][$lingkupLower] = $row->jumlah;
            $pivotedGresik[0]['jumlahalat'] += $row->jumlah;
        }
        $jumlahAlatLingkupGresik = array_values($pivotedGresik);


        $result = array(
            'jumlahAlatLingkup' => $jumlahAlatLingkup,
            'jumlahAlatLingkupGresik' => $jumlahAlatLingkupGresik,
            'message' => 'as@aditwiran19@gmail.com',
        );

        return $this->respond($result);
    }

    public function dataUnitPerId(Request $r)
    {
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
            ->leftjoin('lokasikalibrasi_m as lk1', 'lk1.id', '=', 'mtr.lokasirepair')
            ->leftjoin('lingkupkalibrasi_m as lp', 'lp.id', '=', 'mtrd.lingkupkalibrasifk')
            ->leftjoin('sublingkupkalibrasi_m as slk', 'slk.id', '=', 'mtrd.sublingkupfk')
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
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtr.jenisorder',
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
            ->where('mtr.iskaji', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mt.id', $r['id'])
            ->get();

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

        $dataPivot = [];
        foreach ($data as $item) {
            $tanggal = \Carbon\Carbon::parse($item->tglregistrasi)->format('Y-m-d');
            if (!isset($dataPivot[$tanggal])) {
                $dataPivot[$tanggal] = [
                    'tanggal' => $tanggal,
                    'disetujui' => 0,
                    'belum' => 0,
                ];
            }
            if ($item->jenisorder == 'kalibrasi') {
                if ($item->tglsetujumanagerlembarkerja != null) {
                    $dataPivot[$tanggal]['disetujui'] += 1;
                } else {
                    $dataPivot[$tanggal]['belum'] += 1;
                }
            }
        }

        $chartStatusPerTanggal = array_values($dataPivot);

        $alatprogres = 0;
        $alatselesai = 0;

        foreach ($data as $item) {
            if ($item->jenisorder == 'kalibrasi') {
                if ($item->tglsetujumanagerlembarkerja == null) {
                    $alatprogres++;
                } else {
                    $alatselesai++;
                }
            } else {
                if ($item->tglsetujumanagerlaporanrepair == null) {
                    $alatprogres++;
                } else {
                    $alatselesai++;
                }
            }
        }


        $res['data'] = $data;
        $res['dataAlatUnit'] = $chartStatusPerTanggal;
        $res['alatprogres'] = $alatprogres;
        $res['alatselesai'] = $alatselesai;
        return $this->respond($res);
    }

    public function historyOrderKelompok(Request $r)
    {

        $data  = DB::table('mitra_m as mt')
            ->leftjoin('mitraregistrasi_t as mtr', 'mtr.nomitrafk', '=', 'mt.id')
            ->leftjoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->leftJoin('users as us', DB::raw('CAST(us.mitrafk AS TEXT)'), '=', 'mt.id')
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
                'mtr.lokasirepair',
                'mtr.norec as iddetail',
                'mtr.statusorder',
                'mtr.jenisorder',
                'mtr.verifregiscustomer',
            )
            ->distinct()
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true);

        if (isset($r['mtrauser']) && $r['mtrauser'] != '') {
            $data = $data->where('mt.id', '=', $r['mtrauser']);
        }

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mt.namaperusahaan', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm);
            });
        }
        $page = 1;
        if (isset($r['page']) && $r['page'] != '') {
            $page = $r['page'];
        }
        $data = $data->orderByDesc('mtr.tglregistrasi');
        $data = $data->paginate(isset($r['limit']) ? $r['limit'] : 10, ['*'], 'page', $page);

        $detailAll = DB::table('mitraregistrasidetail_t')
            ->where('statusenabled', true)
            ->get()
            ->groupBy('noregistrasifk');

        foreach ($data as $item) {
            $detailList = $detailAll[$item->iddetail] ?? collect();
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
        }

        return $this->respond($data);
    }

    public function historyOrder(Request $r)
    {
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
                'mtrd.noorderalat',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                DB::raw("COALESCE(mmps.id, mmp.id) as idalat"),
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtr.jenisorder',
                'mtr.verifregiscustomer',
                'mtr.tanggalverifregiscustomer',
                'mtrd.tglverifasman',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlaporanrepair',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true);

        if (isset($r['mtrauser']) && $r['mtrauser'] != '') {
            $data = $data->where('mt.id', '=', $r['mtrauser']);
        }

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';
            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mmp.namaproduk', 'ilike', $searchTerm)
                    ->orWhere('mmp.id', 'ilike', $searchTerm)
                    ->orWhere('mmp.namamerk', 'ilike', $searchTerm)
                    ->orWhere('mmp.namatipe', 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm)
                    ->orWhere('mtrd.noorderalat', 'ilike', $searchTerm)
                    ->orWhere('mmp.namaserialnumber', 'ilike', $searchTerm);
            });
        }
        $page = 1;
        if (isset($r['page']) && $r['page'] != '') {
            $page = $r['page'];
        }

        $totalQuery = clone $data;
        $totalData = $totalQuery->count();

        $totalVerifQuery = clone $data;
        $countVerif = $totalVerifQuery->where('mtr.verifregiscustomer', true)->count();

        $totalBelumVerifQuery = clone $data;
        $countBelumVerif = $totalBelumVerifQuery->whereNull('mtr.verifregiscustomer')->count();

        $data = $data->orderBy('mtr.jenisorder', 'desc')
            ->orderBy('mtr.tglregistrasi', 'desc');
        $data = $data->paginate(isset($r['limit']) ? $r['limit'] : 10, ['*'], 'page', $page);

        $res['data'] = $data;
        $res['totalData'] = $totalData;
        $res['countVerif'] = $countVerif;
        $res['countBelumVerif'] = $countBelumVerif;
        return $this->respond($res);
    }

    public function getLaporanVerifikasi(Request $request)
    {
        $data = DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('mtrd.excellaporanverif', 'mtrd.norec as detailregistraifk', 'mtr.norec as norecregis')
            ->where('mtrd.norec', $request->norecdetail)
            ->where('mtrd.statusenabled', true)
            ->get();

        return $this->respond($data);
    }

    public function cetakLaporanVerifikasi(Request $r)
    {
        $profile = $this->profile();
        $print = false;
        $pageWidth = 950;

        $res['identitas'] = $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtr.petugaskaji')
            ->leftJoin('jabatan_m as jb', 'jb.id', '=', 'pg.jabatan1fk')
            ->leftJoin('lokasikalibrasi_m as lk', 'lk.id', '=', 'mtr.lokasikalibrasi')
            ->select(
                'jb.id as idjabatan',
                'jb.namajabatanulab as namajabanpetugaskaji',
                'mtr.norec',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mt.namaperusahaan',
                'mt.alamatktr',
                'pg.id as petugaskajifk',
                'pg.namalengkap as namapetugaskaji',
                'lk.lokasi',
                'mtr.jabatanpenanggungjawab',
                'mtr.namapenanggungjawab'
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtr.norec', $r['norec'])
            ->first();

        $res['alastandar'] = DB::table('daftaralatstandar_t as das')
            ->leftJoin('mapalatstandar_m as pas', 'pas.id', '=', 'das.alatstandarfk')
            ->select(
                'das.detailregistrasifk',
                'pas.id as value',
                'pas.namaalatstandar',
                'pas.duedate',
                'pas.calldate',
                'pas.namamerk',
                'pas.namatipe',
                'pas.namaserialnumber'
            )
            ->where('das.detailregistrasifk', $r['norec_detail'])
            ->where('das.statusenabled', true)
            ->where('pas.statusenabled', true)
            ->get();

        $res['alat'] = $data = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->leftJoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftJoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->leftJoin('pegawai_m as pg3', 'pg3.id', '=', 'mtr.asmanveriffk')
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->leftJoin('jabatan_m as jb2', 'jb2.id', '=', 'pg2.jabatan1fk')
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
            ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
            ->select(
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg.nid as nidpenyelia',
                'jb1.namajabatanulab as jabatanpenyelia',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg2.nid as nidpelaksana',
                'jb2.namajabatanulab as jabatanpelaksana',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'pg3.nid as nidasman',
                'mtrd.noorderalat',
                'mtrd.namamanager',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.setujuilembarkerjaasman',
                'mtrd.setujuilembarkerjapenyelia',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mtrd.sublingkupfk',
                'mtrd.lingkupkalibrasifk',
                'mtrd.kedalamalubang',
                'mtrd.lubangacuan',
                'mtrd.lubang1',
                'mtrd.lubang2',
                'mtrd.gambarsuhu',
                'mtrd.nosertifikat',
                'mtrd.statuskanfk',
                'mtrd.excellaporanverif',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglkalibrasilembarkerja',
                'rm.namaruangan as tempatKalibrasilembarkerja',
                'mtrd.kondisiRuanganlembarkerja',
                'mtrd.suhulembarkerja',
                'mtrd.kelembabanRelatiflembarkerja',
                'mtrd.evalverifikasi',
                'mtrd.catatanverifikasi',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_detail'])
            ->first();

        $res['alat']->excellaporanverif_pdf = null;
        $res['alat']->excellaporanverif_imgs = [];

        if (!empty($res['alat']->excellaporanverif)) {
            $excelPath = public_path('berkas-verifikasi/' . $res['alat']->excellaporanverif);

            $pdfName = (string) Str::of($res['alat']->excellaporanverif)
                ->replaceMatches('/\.(xlsx|xlsm|xls)$/i', '.pdf');

            $outputDir = storage_path('app/tmp/lo_convert_' . (string) Str::uuid());
            $loProfile = storage_path('app/lo-profile');

            if (!is_dir($outputDir)) {
                @mkdir($outputDir, 0777, true);
            }
            if (!is_dir($loProfile)) {
                @mkdir($loProfile, 0777, true);
            }

            $outputFile = $outputDir . DIRECTORY_SEPARATOR . $pdfName;

            $preparedExcelPath = $excelPath;
            $tmpPreparedExcel = null;

            try {
                // SIAPKAN EXCEL AGAR PRINT AREA AMAN
                $rowsPerPage = max(45, min(95, (int) $r->input('excel_rows_per_page', 72)));
                $tmpPreparedExcel = $this->prepareVerificationExcelForPdf($excelPath, $rowsPerPage);
                if (!empty($tmpPreparedExcel) && file_exists($tmpPreparedExcel)) {
                    $preparedExcelPath = $tmpPreparedExcel;
                }

                if (PHP_OS_FAMILY === 'Windows') {
                    $loPath = env('LIBREOFFICE_PATH', 'C:\PROGRA~1\LibreOffice\program\soffice.exe');
                    $loProfileUrl = 'file:///' . str_replace('\\', '/', $loProfile);

                    $cmd = '"' . $loPath . '" --headless --nologo --nodefault --norestore --nolockcheck '
                        . '-env:UserInstallation=' . $loProfileUrl . ' '
                        . '--convert-to pdf:calc_pdf_Export --outdir "' . $outputDir . '" "' . $preparedExcelPath . '" 2>&1';
                } else {
                    $loPath = env('LIBREOFFICE_PATH');
                    if (!$loPath) {
                        $loPath = trim(shell_exec('command -v soffice || command -v libreoffice'));
                    }
                    if (!$loPath) {
                        Log::error('LibreOffice tidak ditemukan di PATH dan LIBREOFFICE_PATH tidak di-set.');
                    }

                    $loProfileUrl = '-env:UserInstallation=file:///' . ltrim(str_replace('\\', '/', $loProfile), '/');

                    $cmd = escapeshellarg($loPath) . ' --headless --nologo --nodefault --norestore --nolockcheck '
                        . escapeshellarg($loProfileUrl) . ' '
                        . '--convert-to pdf:calc_pdf_Export --outdir ' . escapeshellarg($outputDir) . ' '
                        . escapeshellarg($preparedExcelPath) . ' 2>&1';
                }

                if (!empty($cmd)) {
                    $out = [];
                    $code = 0;
                    exec($cmd, $out, $code);

                    Log::debug('LibreOffice convert', [
                        'exit' => $code,
                        'out' => implode("\n", $out),
                        'cmd' => $cmd,
                        'source_excel' => $preparedExcelPath,
                    ]);
                }

                if (!file_exists($outputFile) && !empty($preparedExcelPath)) {
                    $preparedPdfName = pathinfo($preparedExcelPath, PATHINFO_FILENAME) . '.pdf';
                    $preparedOutputFile = $outputDir . DIRECTORY_SEPARATOR . $preparedPdfName;

                    if (file_exists($preparedOutputFile)) {
                        @rename($preparedOutputFile, $outputFile);
                    }
                }

                if (file_exists($outputFile)) {
                    $res['alat']->excellaporanverif_pdf = $outputFile;
                }
            } catch (\Throwable $e) {
                Log::error('Gagal konversi Excel->PDF: ' . $e->getMessage(), [
                    'file' => $excelPath,
                    'trace' => $e->getTraceAsString(),
                ]);
            } finally {
                if (!empty($tmpPreparedExcel) && file_exists($tmpPreparedExcel)) {
                    @unlink($tmpPreparedExcel);
                }
            }

            if (!empty($res['alat']->excellaporanverif_pdf) && file_exists($res['alat']->excellaporanverif_pdf)) {
                $dpi = (int) ($r->input('excel_img_dpi', 144));
                $useJpeg = (bool) $r->input('excel_img_jpeg', false);
                $quality = max(1, min(100, (int) $r->input('excel_img_quality', 85)));
                $maxPages = (int) $r->input('excel_img_max_pages', 0);

                // DEFAULT DIMATIKAN, karena sheet sederhana sering salah dianggap blank
                $trimBlank = $r->boolean('excel_img_trim_blank', false);

                if (!isset($res['alat']->excellaporanverif_imgs) || !is_array($res['alat']->excellaporanverif_imgs)) {
                    $res['alat']->excellaporanverif_imgs = [];
                }

                $imgDir = storage_path('app/tmp/gs_imgs_' . (string) Str::uuid());
                @mkdir($imgDir, 0777, true);

                $ext = $useJpeg ? 'jpg' : 'png';
                $pattern = 'excel_page_%03d.' . $ext;
                $outGlob = $imgDir . DIRECTORY_SEPARATOR . 'excel_page_*.' . $ext;

                try {
                    if (PHP_OS_FAMILY === 'Windows') {
                        $gs = env('GS_PATH');
                        if (empty($gs)) {
                            $gs = trim(shell_exec('where gswin64c 2>NUL'));
                            if (empty($gs)) {
                                $gs = trim(shell_exec('where gswin32c 2>NUL'));
                            }
                        }
                        if (empty($gs)) {
                            throw new \RuntimeException('Ghostscript tidak ditemukan. Set .env GS_PATH ke gswin64c.exe');
                        }

                        $device = $useJpeg ? 'jpeg' : 'png16m';
                        $jpegQ = $useJpeg ? (' -dJPEGQ=' . $quality) : '';
                        $pageOpt = ($maxPages > 0) ? (' -dFirstPage=1 -dLastPage=' . $maxPages) : '';
                        $output = $imgDir . DIRECTORY_SEPARATOR . $pattern;

                        $cmd2 = '"' . $gs . '"'
                            . ' -dSAFER -dBATCH -dNOPAUSE -dTextAlphaBits=4 -dGraphicsAlphaBits=4'
                            . ' -sDEVICE=' . $device . $jpegQ
                            . ' -r' . (int) $dpi . $pageOpt
                            . ' -sOutputFile="' . $output . '"'
                            . ' "' . $res['alat']->excellaporanverif_pdf . '" 2>&1';
                    } else {
                        $gs = env('GS_PATH', 'gs');
                        $gsPath = trim(shell_exec('command -v ' . escapeshellcmd($gs) . ' 2>/dev/null'));
                        if (!$gsPath) {
                            $gsPath = trim(shell_exec('command -v gs 2>/dev/null'));
                        }
                        if (!$gsPath) {
                            throw new \RuntimeException('Ghostscript (gs) tidak ditemukan. Install: sudo apt install ghostscript');
                        }

                        $device = $useJpeg ? 'jpeg' : 'png16m';
                        $jpegQ = $useJpeg ? (' -dJPEGQ=' . $quality) : '';
                        $pageOpt = ($maxPages > 0) ? (' -dFirstPage=1 -dLastPage=' . $maxPages) : '';
                        $output = $imgDir . DIRECTORY_SEPARATOR . $pattern;

                        $cmd2 = $gsPath
                            . ' -dSAFER -dBATCH -dNOPAUSE -dTextAlphaBits=4 -dGraphicsAlphaBits=4'
                            . ' -sDEVICE=' . $device . $jpegQ
                            . ' -r' . (int) $dpi . $pageOpt
                            . ' -sOutputFile=' . escapeshellarg($output)
                            . ' ' . escapeshellarg($res['alat']->excellaporanverif_pdf) . ' 2>&1';
                    }

                    $out2 = [];
                    $code2 = 0;
                    exec($cmd2, $out2, $code2);

                    Log::debug('ghostscript pdf->img', [
                        'exit' => $code2,
                        'cmd' => $cmd2,
                        'out' => implode("\n", $out2)
                    ]);

                    $files = glob($outGlob) ?: [];
                    natsort($files);

                    $kept = [];

                    foreach ($files as $fp) {
                        if ($trimBlank) {
                            // DETEKSI BLANK BERDASARKAN PIXEL, BUKAN FILESIZE
                            if (!$this->imageHasVisibleContent($fp)) {
                                @unlink($fp);
                                continue;
                            }
                        }
                        $kept[] = $fp;
                    }

                    foreach ($kept as $fp) {
                        $mime = $useJpeg ? 'image/jpeg' : 'image/png';
                        $res['alat']->excellaporanverif_imgs[] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fp));
                        @unlink($fp);
                    }

                    @rmdir($imgDir);
                } catch (\Throwable $e) {
                    Log::error('Gagal konversi PDF->IMG (Ghostscript): ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $this->deleteVerificationDirectoryIfExists($imgDir);
                }
            }

            if (!empty($res['alat']->excellaporanverif_pdf) && file_exists($res['alat']->excellaporanverif_pdf)) {
                @unlink($res['alat']->excellaporanverif_pdf);
            }

            if (!empty($outputDir)) {
                $this->deleteVerificationDirectoryIfExists($outputDir);
            }
        }
        if (!empty($res['alat']->evalverifikasi)) {
            $translated = app(CalibrationNotesTranslationService::class)
                ->translate($res['alat']->evalverifikasi);
            $res['alat']->evalverifikasi_en = $translated;
        }
        if (!empty($res['alat']->catatanverifikasi)) {
            $translated = app(CalibrationNotesTranslationService::class)
                ->translate($res['alat']->catatanverifikasi);
            $res['alat']->catatanverifikasi_en = $translated;
        }
        $res['pdf']  = $r['pdf'];
        $res['ttdPelaksana'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            $res['alat']->jabatanpelaksana . "\n" . $res['alat']->pelaksanateknik . "\n" .  $res['alat']->nidpelaksana . "\n" . 'nosurat:' . $res['alat']->nosertifikat
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdPenyelia'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            $res['alat']->jabatanpenyelia . "\n" . $res['alat']->penyeliateknik . "\n" .  $res['alat']->nidpenyelia . "\n" . 'nosurat:' . $res['alat']->nosertifikat
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdAsman'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            'Asman NMW' . "\n" . $res['alat']->asamanverifikasi . "\n" .  $res['alat']->nidasman . "\n" . 'nosurat:' . $res['alat']->nosertifikat
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['ttdManager'] = base64_encode(QrCode::format('svg')->size(75)->generate(
            'Manager Repair' . "\n" . $res['alat']->namamanager . "\n" .  '8208045JA' . "\n" . 'nosurat:' . $res['alat']->nosertifikat
                . "\n" . 'Dokumen Ini Diproduksi oleh :' . "\n" . 'https://ulabumro.id/'
        ));
        $res['halamanPertama'] = false;

        $blade = 'report.pelaksana.laporan-verifikasi';

        if ($res['pdf'] == 'true') {
            $pdfDummy = App::make('dompdf.wrapper');
            $pdfDummy->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => null,
                )
            );
            $dompdfDummy = $pdfDummy->getDomPDF();
            $dompdfDummy->render();
            $jumlahHalaman = $dompdfDummy->getCanvas()->get_page_count();

            $pdf = App::make('dompdf.wrapper');
            $pdf->loadView(
                $blade . '-dom',
                array(
                    'profile' => $profile,
                    'pageWidth' => $pageWidth,
                    'print' => $print,
                    'res' => $res,
                    'jumlahHalaman' => $jumlahHalaman,
                )
            );
            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->get_canvas();
            $canvas->page_text(230, 780, "Halaman ke {PAGE_NUM} dari {PAGE_COUNT} halaman", null, 8, array(0, 0, 0));
            $font = $dompdf->getFontMetrics()->getFont('Helvetica', 'italic');
            $canvas->page_text(260, 788, "Page {PAGE_NUM} of {PAGE_COUNT} pages", $font, 7, array(0, 0, 0));
            return $pdf->stream();
        }

        if (isset($r['storage'])) {
            $res['storage']  = true;
            $pdf = App::make('dompdf.wrapper');
            $pdf->setpaper('a4', 'landscape');
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

    private function prepareExcelForPdf(string $excelPath, int $rowsPerPage = 72): ?string
    {
        if (empty($excelPath) || !file_exists($excelPath)) {
            return null;
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }

        try {
            $reader = IOFactory::createReaderForFile($excelPath);
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($excelPath);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $bounds = $this->detectWorksheetBounds($sheet);

                if (!$bounds) {
                    continue;
                }

                $range = $bounds['start_col'] . $bounds['start_row'] . ':' . $bounds['end_col'] . $bounds['end_row'];

                $this->clearVerificationWorksheetPageBreaks($sheet);

                $sheet->getPageSetup()->setPrintArea($range);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

                $totalCols = $bounds['end_col_index'] - $bounds['start_col_index'] + 1;
                $totalRows = $bounds['end_row'] - $bounds['start_row'] + 1;

                if ($totalCols >= 7 || $totalCols > $totalRows) {
                    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                } else {
                    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                }

                if (method_exists($sheet->getPageSetup(), 'setFitToPage')) {
                    $sheet->getPageSetup()->setFitToPage(true);
                }

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $this->protectVerificationChannelPageBreaks($sheet, $bounds, $rowsPerPage);

                $sheet->getPageMargins()->setTop(0.20);
                $sheet->getPageMargins()->setRight(0.20);
                $sheet->getPageMargins()->setLeft(0.20);
                $sheet->getPageMargins()->setBottom(0.20);

                $sheet->getPageMargins()->setHeader(0.10);
                $sheet->getPageMargins()->setFooter(0.10);

                $sheet->getPageSetup()->setHorizontalCentered(true);
                $sheet->getPageSetup()->setVerticalCentered(false);
                $sheet->setSelectedCell('A1');
            }

            $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'prepared_' . Str::uuid() . '.xlsx';
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tmpFile);

            return $tmpFile;
        } catch (\Throwable $e) {
            Log::error('prepareExcelForPdf gagal: ' . $e->getMessage(), [
                'file' => $excelPath,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function detectWorksheetBounds(Worksheet $sheet): ?array
    {
        $minRow = PHP_INT_MAX;
        $maxRow = 0;
        $minColIndex = PHP_INT_MAX;
        $maxColIndex = 0;

        foreach ($sheet->getCellCollection()->getCoordinates() as $coord) {
            $cell = $sheet->getCell($coord);
            $value = $cell->getValue();

            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }

            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            [$col, $row] = Coordinate::coordinateFromString($coord);
            $colIndex = Coordinate::columnIndexFromString($col);

            $minRow = min($minRow, (int) $row);
            $maxRow = max($maxRow, (int) $row);
            $minColIndex = min($minColIndex, $colIndex);
            $maxColIndex = max($maxColIndex, $colIndex);
        }

        /** @var array<string, mixed> $mergeCells */
        $mergeCells = $sheet->getMergeCells();

        foreach (array_keys($mergeCells) as $mergedRange) {
            if (!is_string($mergedRange) || strpos($mergedRange, ':') === false) {
                continue;
            }

            [$start, $end] = explode(':', $mergedRange, 2);

            [$startCol, $startRow] = Coordinate::coordinateFromString($start);
            [$endCol, $endRow] = Coordinate::coordinateFromString($end);

            $startColIndex = Coordinate::columnIndexFromString($startCol);
            $endColIndex = Coordinate::columnIndexFromString($endCol);

            $topLeftValue = $sheet->getCell($start)->getValue();
            if ($topLeftValue instanceof RichText) {
                $topLeftValue = $topLeftValue->getPlainText();
            }

            if ($topLeftValue !== null && (!is_string($topLeftValue) || trim($topLeftValue) !== '')) {
                $minRow = min($minRow, (int) $startRow);
                $maxRow = max($maxRow, (int) $endRow);
                $minColIndex = min($minColIndex, $startColIndex);
                $maxColIndex = max($maxColIndex, $endColIndex);
            }
        }

        if (method_exists($sheet, 'getDrawingCollection')) {
            foreach ($sheet->getDrawingCollection() as $drawing) {
                $coord = $drawing->getCoordinates();
                if (!$coord) {
                    continue;
                }

                [$col, $row] = Coordinate::coordinateFromString($coord);
                $colIndex = Coordinate::columnIndexFromString($col);

                $minRow = min($minRow, (int) $row);
                $maxRow = max($maxRow, (int) $row);
                $minColIndex = min($minColIndex, $colIndex);
                $maxColIndex = max($maxColIndex, $colIndex);
            }
        }

        if ($maxRow === 0 || $maxColIndex === 0) {
            return null;
        }

        return [
            'start_row' => $minRow,
            'end_row' => $maxRow,
            'start_col_index' => $minColIndex,
            'end_col_index' => $maxColIndex,
            'start_col' => Coordinate::stringFromColumnIndex($minColIndex),
            'end_col' => Coordinate::stringFromColumnIndex($maxColIndex),
        ];
    }

    private function imageHasVisibleContent(string $imagePath, int $step = 10, int $whiteTolerance = 245): bool
    {
        if (!file_exists($imagePath)) {
            return false;
        }

        $info = @getimagesize($imagePath);
        if (!$info) {
            return true;
        }

        $img = null;

        switch ($info[2]) {
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($imagePath);
                }
                break;
        }

        if (!$img) {
            return true;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        $step = max(4, $step);
        $darkPixelFound = 0;

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $rgb = imagecolorat($img, $x, $y);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // kalau bukan hampir putih, anggap ada isi
                if ($r < $whiteTolerance || $g < $whiteTolerance || $b < $whiteTolerance) {
                    $darkPixelFound++;
                    if ($darkPixelFound >= 10) {
                        imagedestroy($img);
                        return true;
                    }
                }
            }
        }

        imagedestroy($img);
        return false;
    }

    public function getSensor(Request $r)
    {
        $latestSub = DB::table('sensor_readings')
            ->select('room_id', DB::raw('MAX(created_at) AS max_created_at'))
            ->where('statusenabled', true)
            ->groupBy('room_id');

        $rows = DB::table('sensor_readings as sr')
            ->joinSub($latestSub, 'latest', function ($join) {
                $join->on('sr.room_id', '=', 'latest.room_id')
                    ->on('sr.created_at', '=', 'latest.max_created_at');
            })
            ->join('mapruangantosensor_m as mrs', 'mrs.sensorfk', '=', 'sr.room_id')
            ->where('sr.statusenabled', true)
            ->select(
                'sr.id',
                'sr.room_id',
                'sr.temperature',
                'sr.humidity',
                'sr.pressure',
                'sr.light',
                'sr.uv',
                'sr.created_at',
                'mrs.namaruangan'
            )
            ->orderBy('sr.room_id');

        if (!empty($r['ruanganfk'])) {
            $rows = $rows->where('sr.room_id', $r['ruanganfk']);
        }
        $rows = $rows->get();


        $perRoom10SubSql = <<<SQL
        SELECT
            id,
            room_id,
            temperature,
            humidity,
            pressure,
            light,
            uv,
            created_at,
            ROW_NUMBER() OVER (PARTITION BY room_id ORDER BY created_at DESC) AS rn
        FROM sensor_readings
        WHERE statusenabled = true
    SQL;

        $dataChartFlat = DB::table(DB::raw("({$perRoom10SubSql}) AS t"))
            ->where('t.rn', '<=', 10)
            ->orderBy('t.room_id')
            ->orderByDesc('t.created_at')
            ->get();

        $dataChart = $dataChartFlat
            ->groupBy('room_id')
            ->map(function ($items) {
                return $items->sortByDesc('created_at')->values();
            });

        return $this->respond([
            'data'      => $rows,
            'dataChart' => $dataChart,
            'as'        => '@aditwiran19@gmail.com',
        ]);
    }

    public function saveBatalOrderAlat(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_batal = $request['itembatal'];
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_batal['norec_detail'])
                ->update([
                    'statusenabled' => false,
                    'alasanpembatalanorderalat' => $r_batal['alasanpembatalan']
                ]);

            $message = 'Pemmbatalan atau Penolakan Berhasil';

            DB::commit();

            $lokasiId = $r_batal['lokasikalibrasifk'] ?? null;
            $nopendaftaran = $r_batal['nopendaftaran'] ?? null;
            $alasanpembatalan = $r_batal['alasanpembatalan'] ?? null;
            $idAdmin = null;
            if ($lokasiId == 1) {
                $idAdmin = 16;
            } elseif ($lokasiId == 2) {
                $idAdmin = 20;
            }

            if ($idAdmin) {
                $admin = DB::table('pegawai_m')->where('id', $idAdmin)->first();
                $nohpAdmin = $admin->nohandphone ?? null;
                $namaAdmin = $admin->namalengkap ?? '-';

                $pesanAdmin = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAdmin,\n\n"
                    . "Pemberitahuan: Alat pada No Pendaftaran *$nopendaftaran* Telah Ditolak Atau Dibatalkan Oleh Asman.\n"
                    . "Dengan Keterangan Berikut : *$alasanpembatalan*.\n"
                    . "\nMohon untuk segera melakukan verifikasi dan proses lebih lanjut di aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                if ($nohpAdmin) {
                    $this->kirimWhatsappNotifikasi($nohpAdmin, $pesanAdmin);
                }
            }

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

    public function savePenolakanSerti(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_tolak = $request['itemtolak'];

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_tolak['norecregis'])
                ->update([
                    'istolaksertiasman' => true,
                    'alasanpenolakansertiasman' => $r_tolak['alasanpenolakanserti'],
                    'tgltolaksertiasman' => now(),
                    'setujuilembarkerjapenyelia' => null,
                    'tglsetujupenyelialembarkerja' => null,
                    'penyeliasetujulembarkerjafk' => null,
                    'statusorderpenyelia' => 1,
                ]);

            $message = 'Penolakan Berhasil';

            DB::commit();

            $regis = DB::table('mitraregistrasidetail_t')->where('norec', $r_tolak['norecregis'])->first();
            $noorderalat = $regis->noorderalat ?? null;
            $alasanpenolakanserti = $r_tolak['alasanpenolakanserti'] ?? null;
            $waktuTolak = $regis->tgltolaksertiasman ?? now();

            try {
                $waktuTolakStr = \Carbon\Carbon::parse($waktuTolak)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuTolakStr = date('d-m-Y H:i', strtotime($waktuTolak)) . ' WIB';
            }

            $asman = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaAsman = $asman->namalengkap ?? '-';

            $recipients = [];

            $penyeliateknikfk = $regis->penyeliateknikfk ?? null;
            if ($penyeliateknikfk) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliateknikfk)->first();
                if ($penyelia) {
                    $recipients[$penyelia->id] = [
                        'id' => $penyelia->id,
                        'nama' => $penyelia->namalengkap ?? '-',
                        'nohp' => $penyelia->nohandphone ?? null,
                        'role' => 'Penyelia Teknik',
                        'urlform' => 'module-dashboard-penyelia',
                        'pesan' => "Halo Tim Hebat U-LAB ! 👋\n\n"
                            . "Yth. {$penyelia->namalengkap}\n"
                            . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Sertifikat Telah ditolak Oleh Asman.\n"
                            . "Dengan Keterangan Berikut : *$alasanpenolakanserti*.\n\n"
                            . "Salam,\n"
                            . "U-LAB ! Cepat, Tepat, Akurat 💯",
                    ];
                }
            }

            $pelaksanateknikfk = $regis->pelaksanateknikfk ?? null;
            if ($pelaksanateknikfk) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanateknikfk)->first();
                if ($pelaksana) {
                    $recipients[$pelaksana->id] = [
                        'id' => $pelaksana->id,
                        'nama' => $pelaksana->namalengkap ?? '-',
                        'nohp' => $pelaksana->nohandphone ?? null,
                        'role' => 'Pelaksana Teknik',
                        'urlform' => 'module-dashboard-pelaksana',
                        'pesan' => "Halo Tim Hebat U-LAB ! 👋\n\n"
                            . "Yth. {$pelaksana->namalengkap}\n"
                            . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Sertifikat Telah ditolak Oleh Asman.\n"
                            . "Dengan Keterangan Berikut : *$alasanpenolakanserti*.\n\n"
                            . "Salam,\n"
                            . "U-LAB ! Cepat, Tepat, Akurat 💯",
                    ];
                }
            }

            foreach ($recipients as $recipient) {
                $namaPenerima = $recipient['nama'] ?? '-';
                $nohpPenerima = $recipient['nohp'] ?? null;
                $rolePenerima = $recipient['role'] ?? '-';
                $urlForm = $recipient['urlform'] ?? 'module-dashboard';
                $pesan = $recipient['pesan'] ?? '';

                if ($nohpPenerima) {
                    $this->kirimWhatsappNotifikasi($nohpPenerima, $pesan);
                }

                $notif = new \App\Models\Master\ListNotif();
                $notif->norec = (string) \Illuminate\Support\Str::uuid();
                $notif->norec_trans = $r_tolak['norecregis'] ?? (string) \Illuminate\Support\Str::uuid();
                $notif->judul = 'Penolakan Sertifikat oleh Asman';
                $notif->jenis = 'Sertifikat Kalibrasi';
                $notif->pegawaifk = $recipient['id'];
                $notif->namapegawai = $namaPenerima;
                $notif->keterangan = 'Sertifikat untuk No Order ' . $noorderalat . ' ditolak oleh Asman ' . $namaAsman . '. Alasan: ' . $alasanpenolakanserti;
                $notif->tgl = now();
                $notif->tgl_string = now()->format('d-m-Y H:i');
                $notif->urlform = $urlForm;
                $notif->params = json_encode([
                    'norec_detail' => $r_tolak['norecregis'],
                    'noorderalat' => $noorderalat,
                    'role_tujuan' => $rolePenerima,
                ]);
                $notif->dataarray = json_encode([
                    'detail_norec' => $r_tolak['norecregis'],
                    'noorderalat' => $noorderalat,
                    'namaAsman' => $namaAsman,
                    'alasanPenolakan' => $alasanpenolakanserti,
                    'waktuTolak' => $waktuTolakStr,
                ]);
                $notif->statusenabled = true;
                $notif->isread = false;
                $notif->save();

                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->withHeaders([
                            'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                            'Accept' => 'application/json',
                        ])
                        ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                            'norec' => $notif->norec,
                            'norec_trans' => $notif->norec_trans,
                            'judul' => $notif->judul,
                            'jenis' => $notif->jenis,
                            'idPegawai' => $notif->pegawaifk,
                            'namapegawai' => $notif->namapegawai,
                            'pesanNotifikasi' => $notif->keterangan,
                            'tgl' => $notif->tgl,
                            'tgl_string' => $notif->tgl_string,
                            'urlForm' => $notif->urlform,
                            'params' => json_decode($notif->params, true),
                            'dataArray' => json_decode($notif->dataarray, true),
                        ]);
                } catch (\Exception $e) {
                    // abaikan jika socket gagal, notif DB tetap tersimpan
                }
            }

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

    public function savePenolakanLapRepair(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_tolak = $request['itemtolak'];

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_tolak['norecregis'])
                ->update([
                    'istolakrepairasman' => true,
                    'alasanpenolakanrepairasman' => $r_tolak['alasanpenolakan'],
                    'tgltolakrepairasman' => now(),
                    'penyeliasetujulaporanrepairfk' => null,
                    'tglsetujupenyelialaporanrepair' => null,
                    'statusorderpenyelia' => 1,
                ]);

            $message = 'Penolakan Berhasil';

            DB::commit();

            $regis = DB::table('mitraregistrasidetail_t')->where('norec', $r_tolak['norecregis'])->first();
            $noorderalat = $regis->noorderalat ?? null;
            $alasanpenolakan = $r_tolak['alasanpenolakan'] ?? null;
            $waktuTolak = $regis->tgltolakrepairasman ?? now();

            try {
                $waktuTolakStr = \Carbon\Carbon::parse($waktuTolak)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuTolakStr = date('d-m-Y H:i', strtotime($waktuTolak)) . ' WIB';
            }

            $asman = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaAsman = $asman->namalengkap ?? '-';

            $recipients = [];

            $penyeliateknikfk = $regis->penyeliateknikfk ?? null;
            if ($penyeliateknikfk) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliateknikfk)->first();
                if ($penyelia) {
                    $recipients[$penyelia->id] = [
                        'id' => $penyelia->id,
                        'nama' => $penyelia->namalengkap ?? '-',
                        'nohp' => $penyelia->nohandphone ?? null,
                        'role' => 'Penyelia Teknik',
                        'urlform' => 'module-dashboard-penyelia',
                        'pesan' => "Halo Tim Hebat U-LAB ! 👋\n\n"
                            . "Yth. {$penyelia->namalengkap}\n"
                            . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Laporan Repair Telah ditolak Oleh Asman.\n"
                            . "Dengan Keterangan Berikut : *$alasanpenolakan*.\n\n"
                            . "Salam,\n"
                            . "U-LAB ! Cepat, Tepat, Akurat 💯",
                    ];
                }
            }

            $pelaksanateknikfk = $regis->pelaksanateknikfk ?? null;
            if ($pelaksanateknikfk) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanateknikfk)->first();
                if ($pelaksana) {
                    $recipients[$pelaksana->id] = [
                        'id' => $pelaksana->id,
                        'nama' => $pelaksana->namalengkap ?? '-',
                        'nohp' => $pelaksana->nohandphone ?? null,
                        'role' => 'Pelaksana Teknik',
                        'urlform' => 'module-dashboard-pelaksana',
                        'pesan' => "Halo Tim Hebat U-LAB ! 👋\n\n"
                            . "Yth. {$pelaksana->namalengkap}\n"
                            . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Laporan Repair Telah ditolak Oleh Asman.\n"
                            . "Dengan Keterangan Berikut : *$alasanpenolakan*.\n\n"
                            . "Salam,\n"
                            . "U-LAB ! Cepat, Tepat, Akurat 💯",
                    ];
                }
            }

            foreach ($recipients as $recipient) {
                $namaPenerima = $recipient['nama'] ?? '-';
                $nohpPenerima = $recipient['nohp'] ?? null;
                $rolePenerima = $recipient['role'] ?? '-';
                $urlForm = $recipient['urlform'] ?? 'module-dashboard';
                $pesan = $recipient['pesan'] ?? '';

                if ($nohpPenerima) {
                    $this->kirimWhatsappNotifikasi($nohpPenerima, $pesan);
                }

                $notif = new \App\Models\Master\ListNotif();
                $notif->norec = (string) \Illuminate\Support\Str::uuid();
                $notif->norec_trans = $r_tolak['norecregis'] ?? (string) \Illuminate\Support\Str::uuid();
                $notif->judul = 'Penolakan Laporan Repair oleh Asman';
                $notif->jenis = 'Laporan Repair';
                $notif->pegawaifk = $recipient['id'];
                $notif->namapegawai = $namaPenerima;
                $notif->keterangan = 'Laporan Repair untuk No Order ' . $noorderalat . ' ditolak oleh Asman ' . $namaAsman . '. Alasan: ' . $alasanpenolakan;
                $notif->tgl = now();
                $notif->tgl_string = now()->format('d-m-Y H:i');
                $notif->urlform = $urlForm;
                $notif->params = json_encode([
                    'norec_detail' => $r_tolak['norecregis'],
                    'noorderalat' => $noorderalat,
                    'role_tujuan' => $rolePenerima,
                ]);
                $notif->dataarray = json_encode([
                    'detail_norec' => $r_tolak['norecregis'],
                    'noorderalat' => $noorderalat,
                    'namaAsman' => $namaAsman,
                    'alasanPenolakan' => $alasanpenolakan,
                    'waktuTolak' => $waktuTolakStr,
                ]);
                $notif->statusenabled = true;
                $notif->isread = false;
                $notif->save();

                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->withHeaders([
                            'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                            'Accept' => 'application/json',
                        ])
                        ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                            'norec' => $notif->norec,
                            'norec_trans' => $notif->norec_trans,
                            'judul' => $notif->judul,
                            'jenis' => $notif->jenis,
                            'idPegawai' => $notif->pegawaifk,
                            'namapegawai' => $notif->namapegawai,
                            'pesanNotifikasi' => $notif->keterangan,
                            'tgl' => $notif->tgl,
                            'tgl_string' => $notif->tgl_string,
                            'urlForm' => $notif->urlform,
                            'params' => json_decode($notif->params, true),
                            'dataArray' => json_decode($notif->dataarray, true),
                        ]);
                } catch (\Exception $e) {
                    // abaikan jika socket gagal, notif DB tetap tersimpan
                }
            }

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

    public function getPengajuanAmandemen(Request $r)
    {
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
            ->leftjoin('pegawai_m as pg', 'pg.id', '=', 'mtrd.penyeliateknikfk')
            ->leftjoin('pegawai_m as pg2', 'pg2.id', '=', 'mtrd.pelaksanateknikfk')
            ->join('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->select(
                'mtr.norec',
                'mtr.isstandarulab',
                'mtr.iskalibrasiinternal',
                'mtrd.norec as norec_detail',
                'mtr.tglregistrasi',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                'mt.namaperusahaan',
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'mtrd.noorderalat',
                'mtrd.alasanpengajuan',
                'mtrd.tglpengajuanamandemen',
                'mtrd.isamandemen',
                'mtrd.statusamandemen',
            )
            ->where('mtrd.isamandemen', true)
            ->where('mtrd.statusamandemen', '!=', 2)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true);

        $data = $data->orderByDesc('mtrd.tglpengajuanamandemen');
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function saveApprovalPengajuanAmandemen(Request $r)
    {
        DB::beginTransaction();
        try {
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r['norec'])
                ->update([
                    'statusamandemen' => $r['status'],
                    'tglapprovelpengajuamamandemen' => now(),
                ]);

            $transMessage = "Simpan Pengajuan Amandemen Sukses";
            DB::commit();

            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $r['norec'])->first();
            $penyeliaId = $detail->penyeliateknikfk ?? null;
            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            $asmanId = $detail->asmansetujulembarkerjafk ?? $detail->asmansetujulaporanrepairfk ?? null;
            $managerId = $detail->managersetujulembarkerjafk ?? $detail->managersetujulaporanrepairfk ?? null;

            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $namaAsman = $asman->namalengkap ?? '-';

            $waktuPengajuanRaw = $detail->tglapprovelpengajuamamandemen ?? now();
            try {
                $waktuPengajuan = \Carbon\Carbon::parse($waktuPengajuanRaw)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuPengajuan = date('d-m-Y H:i', strtotime($waktuPengajuanRaw)) . ' WIB';
            }

            $noorderalat = $r['noorderalat'] ?? ($detail->noorderalat ?? null);

            if ($r['status'] == 3) {
                $statusapprov = 'Pengajuan Disetujui ✅';
            } else {
                $statusapprov = 'Pengajuan Ditolak ❌';
            }

            $recipients = [];

            // manager hanya jika status disetujui
            if ($r['status'] == 3 && $managerId) {
                $manager = DB::table('pegawai_m')->where('id', $managerId)->first();
                if ($manager) {
                    $recipients[$manager->id] = [
                        'id' => $manager->id,
                        'nama' => $manager->namalengkap ?? '-',
                        'nohp' => $manager->nohandphone ?? null,
                        'role' => 'Manager',
                        'urlform' => 'module-dashboard-manager',
                    ];
                }
            }

            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                if ($penyelia) {
                    $recipients[$penyelia->id] = [
                        'id' => $penyelia->id,
                        'nama' => $penyelia->namalengkap ?? '-',
                        'nohp' => $penyelia->nohandphone ?? null,
                        'role' => 'Penyelia Teknik',
                        'urlform' => 'module-dashboard-penyelia',
                    ];
                }
            }

            if ($pelaksanaId) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanaId)->first();
                if ($pelaksana) {
                    $recipients[$pelaksana->id] = [
                        'id' => $pelaksana->id,
                        'nama' => $pelaksana->namalengkap ?? '-',
                        'nohp' => $pelaksana->nohandphone ?? null,
                        'role' => 'Pelaksana Teknik',
                        'urlform' => 'module-dashboard-pelaksana',
                    ];
                }
            }

            foreach ($recipients as $recipient) {
                $namaPenerima = $recipient['nama'] ?? '-';
                $nohpPenerima = $recipient['nohp'] ?? null;
                $rolePenerima = $recipient['role'] ?? '-';
                $urlForm = $recipient['urlform'] ?? 'module-dashboard';

                $pesan = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenerima\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "Alat dengan No Order : *$noorderalat*\n"
                    . "Telah dilakukan Approvel Pengajuan Amandemen oleh Asman : *$namaAsman*\n"
                    . "Pada $waktuPengajuan\n"
                    . "Dengan status Pengajuan: $statusapprov\n\n"
                    . "Silahkan cek dan review daftar pengajuan amandemen pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                // WhatsApp
                if ($nohpPenerima) {
                    $this->kirimWhatsappNotifikasi($nohpPenerima, $pesan);
                }

                // Simpan notif DB
                $notif = new \App\Models\Master\ListNotif();
                $notif->norec = (string) \Illuminate\Support\Str::uuid();
                $notif->norec_trans = $r['norec'] ?? (string) \Illuminate\Support\Str::uuid();
                $notif->judul = 'Approval Pengajuan Amandemen';
                $notif->jenis = 'Pengajuan Amandemen';
                $notif->pegawaifk = $recipient['id'];
                $notif->namapegawai = $namaPenerima;
                $notif->keterangan = 'Pengajuan amandemen untuk No Order ' . $noorderalat . ' telah diproses oleh Asman ' . $namaAsman . ' dengan status: ' . $statusapprov;
                $notif->tgl = now();
                $notif->tgl_string = now()->format('d-m-Y H:i');
                $notif->urlform = $urlForm;
                $notif->params = json_encode([
                    'norec_detail' => $r['norec'],
                    'noorderalat' => $noorderalat,
                    'role_tujuan' => $rolePenerima,
                    'statusamandemen' => $r['status'],
                ]);
                $notif->dataarray = json_encode([
                    'detail_norec' => $r['norec'],
                    'noorderalat' => $noorderalat,
                    'namaAsman' => $namaAsman,
                    'statusapprov' => $statusapprov,
                    'waktuPengajuan' => $waktuPengajuan,
                ]);
                $notif->statusenabled = true;
                $notif->isread = false;
                $notif->save();

                // Emit socket
                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->withHeaders([
                            'x-socket-secret' => env('SOCKET_SERVER_SECRET'),
                            'Accept' => 'application/json',
                        ])
                        ->post(rtrim(env('SOCKET_SERVER_URL'), '/') . '/emit-notification', [
                            'norec' => $notif->norec,
                            'norec_trans' => $notif->norec_trans,
                            'judul' => $notif->judul,
                            'jenis' => $notif->jenis,
                            'idPegawai' => $notif->pegawaifk,
                            'namapegawai' => $notif->namapegawai,
                            'pesanNotifikasi' => $notif->keterangan,
                            'tgl' => $notif->tgl,
                            'tgl_string' => $notif->tgl_string,
                            'urlForm' => $notif->urlform,
                            'params' => json_decode($notif->params, true),
                            'dataArray' => json_decode($notif->dataarray, true),
                        ]);
                } catch (\Exception $e) {
                    // abaikan jika socket gagal, notif DB tetap tersimpan
                }
            }

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@aditwiran19@gmail.com',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Simpan Pengajuan Amandemen Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
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

    private function statusSuratJalanAsmanText($status)
    {
        if ((int)$status === 2) {
            return 'Disetujui Asman';
        }

        if ((int)$status === 3) {
            return 'Ditolak Asman';
        }

        return 'Diajukan';
    }

    private function statusSuratJalanAsmanColor($status)
    {
        if ((int)$status === 2) {
            return 'success';
        }

        if ((int)$status === 3) {
            return 'danger';
        }

        return 'warning';
    }

    public function getSuratJalanAsman(Request $r)
    {
        $data = DB::table('suratjalan_t as sj')
            ->leftJoin('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'sj.noregistrasifk')
            ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg_bawa', 'pg_bawa.id', '=', 'sj.pegawaibawafk')
            ->leftJoin('pegawai_m as pg_petugas', 'pg_petugas.id', '=', 'sj.petugasfk')
            ->leftJoin('pegawai_m as pg_asman', 'pg_asman.id', '=', 'sj.asmanfk')
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
                'sj.asmanfk',
                'sj.namaasman',

                'sj.petugasfk',
                'sj.namapetugas',

                'sj.created_at',
                'sj.updated_at',

                'mt.namaperusahaan',
                'pg_bawa.namalengkap as pegawaibawa_master',
                'pg_petugas.namalengkap as petugas_master',
                'pg_asman.namalengkap as asman_master',

                DB::raw("(SELECT COUNT(1) FROM suratjalandetail_t sjd WHERE sjd.suratjalanfk = sj.norec AND sjd.statusenabled = true) as jumlahbarang")
            )
            ->where('sj.statusenabled', true);

        if (isset($r['status']) && $r['status'] !== '' && $r['status'] !== 'all') {
            $data = $data->where('sj.statussuratjalan', (int)$r['status']);
        }

        if (isset($r['dari']) && $r['dari'] != '') {
            $data = $data->where('sj.tanggalsurat', '>=', $r['dari']);
        }

        if (isset($r['sampai']) && $r['sampai'] != '') {
            $data = $data->where('sj.tanggalsurat', '<=', $r['sampai']);
        }

        if (isset($r['search']) && $r['search'] != '') {
            $search = '%' . strtolower($r['search']) . '%';

            $data = $data->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(sj.nosuratjalan) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.nopendaftaran) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.diberikankepada) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.tujuan) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.namapegawaibawa) like ?', [$search])
                    ->orWhereRaw('LOWER(sj.namapetugas) like ?', [$search])
                    ->orWhereRaw('LOWER(mt.namaperusahaan) like ?', [$search]);
            });
        }

        $data = $data->orderBy('sj.statussuratjalan')
            ->orderByDesc('sj.created_at')
            ->get();

        foreach ($data as $index => $item) {
            $item->no = $index + 1;
            $item->status = $this->statusSuratJalanAsmanText($item->statussuratjalan);
            $item->color = $this->statusSuratJalanAsmanColor($item->statussuratjalan);
            $item->namapegawaibawa = $item->namapegawaibawa ?: $item->pegawaibawa_master;
            $item->namapetugas = $item->namapetugas ?: $item->petugas_master;
            $item->namaasman = $item->namaasman ?: $item->asman_master;
        }

        return $this->respond($data);
    }

    public function detailSuratJalanAsman(Request $r)
    {
        $head = DB::table('suratjalan_t as sj')
            ->leftJoin('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'sj.noregistrasifk')
            ->leftJoin('mitra_m as mt', 'mt.id', '=', 'mtr.nomitrafk')
            ->leftJoin('pegawai_m as pg_bawa', 'pg_bawa.id', '=', 'sj.pegawaibawafk')
            ->leftJoin('pegawai_m as pg_petugas', 'pg_petugas.id', '=', 'sj.petugasfk')
            ->leftJoin('pegawai_m as pg_asman', 'pg_asman.id', '=', 'sj.asmanfk')
            ->select(
                'sj.*',
                'mt.namaperusahaan',
                'pg_bawa.namalengkap as pegawaibawa_master',
                'pg_petugas.namalengkap as petugas_master',
                'pg_asman.namalengkap as asman_master'
            )
            ->where('sj.statusenabled', true)
            ->where('sj.norec', $r['norec'])
            ->first();

        if (!$head) {
            return $this->respond([
                'status' => 404,
                'message' => 'Data surat jalan tidak ditemukan.',
                'head' => null,
                'detail' => []
            ]);
        }

        $detail = DB::table('suratjalandetail_t as sjd')
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
                'sjd.ket',
                'sjd.created_at',
                'sjd.updated_at'
            )
            ->where('sjd.statusenabled', true)
            ->where('sjd.suratjalanfk', $r['norec'])
            ->orderBy('sjd.nourut')
            ->get();

        $fotos = $detail->isNotEmpty()
            ? DB::table('suratjalanfoto_t')
                ->where('statusenabled', true)
                ->where('suratjalanfk', $r['norec'])
                ->whereIn('suratjalandetailfk', $detail->pluck('norec'))
                ->orderBy('created_at')
                ->get()
                ->groupBy('suratjalandetailfk')
            : collect();

        foreach ($detail as $item) {
            $item->foto = $fotos->get($item->norec, collect())->values();
        }

        $head->status = $this->statusSuratJalanAsmanText($head->statussuratjalan);
        $head->color = $this->statusSuratJalanAsmanColor($head->statussuratjalan);
        $head->namapegawaibawa = $head->namapegawaibawa ?: $head->pegawaibawa_master;
        $head->namapetugas = $head->namapetugas ?: $head->petugas_master;
        $head->namaasman = $head->namaasman ?: $head->asman_master;

        return $this->respond([
            'status' => 200,
            'head' => $head,
            'detail' => $detail,
            'as' => 'aditwiran19@gmail.com'
        ]);
    }

    public function kirimWaHasilApprovalSuratJalan($norecSuratJalan, $statusApproval, $alasanTolak = null)
    {
        $head = DB::table('suratjalan_t as sj')
            ->leftJoin('pegawai_m as pg_bawa', 'pg_bawa.id', '=', 'sj.pegawaibawafk')
            ->leftJoin('pegawai_m as pg_admin', 'pg_admin.id', '=', 'sj.petugasfk')
            ->leftJoin('pegawai_m as pg_asman', 'pg_asman.id', '=', 'sj.asmanfk')
            ->select(
                'sj.norec',
                'sj.nosuratjalan',
                'sj.nopendaftaran',
                'sj.diberikankepada',
                'sj.tujuan',
                'sj.tanggalsurat',
                'sj.pegawaibawafk',
                'sj.namapegawaibawa',
                'sj.petugasfk',
                'sj.namapetugas',
                'sj.asmanfk',
                'sj.namaasman',
                'pg_bawa.namalengkap as nama_pembawa_master',
                'pg_bawa.nohandphone as nohp_pembawa',
                'pg_admin.namalengkap as nama_admin_master',
                'pg_admin.nohandphone as nohp_admin',
                'pg_asman.namalengkap as nama_asman_master'
            )
            ->where('sj.statusenabled', true)
            ->where('sj.norec', $norecSuratJalan)
            ->first();

        if (!$head) {
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

        $namaAsman = $head->namaasman ?: ($head->nama_asman_master ?: '-');
        $namaPembawa = $head->namapegawaibawa ?: ($head->nama_pembawa_master ?: '-');
        $namaAdmin = $head->namapetugas ?: ($head->nama_admin_master ?: '-');

        $linkDokumen = url('service/registrasi/cetak-surat-jalan?pdf=true&norec=' . $norecSuratJalan);

        $statusText = ((int) $statusApproval === 2) ? 'DISETUJUI' : 'DITOLAK';

        $pesanAdmin = "Halo Tim Hebat U-LAB ! 👋\n\n"
            . "Yth. *" . $namaAdmin . "*\n\n"
            . "Pengajuan *Surat Jalan* telah *" . $statusText . "* oleh Asman.\n\n"
            . "No Surat Jalan : *" . ($head->nosuratjalan ?? '-') . "*\n"
            . "No Pendaftaran : *" . ($head->nopendaftaran ?? '-') . "*\n"
            . "Diberikan Kepada : *" . ($head->diberikankepada ?? '-') . "*\n"
            . "Tujuan : *" . ($head->tujuan ?? '-') . "*\n"
            . "Tanggal : *" . $tanggalSurat . "*\n"
            . "Jumlah Barang : *" . $jumlahBarang . " barang*\n"
            . "Asman : *" . $namaAsman . "*\n";

        if ((int) $statusApproval === 3) {
            $pesanAdmin .= "Alasan Penolakan : *" . ($alasanTolak ?: '-') . "*\n";
        }

        $pesanAdmin .= "\nSalam,\n"
            . "U-LAB ! Cepat, Tepat, Akurat 💯";

        $pesanPembawa = "Halo Tim Hebat U-LAB ! 👋\n\n"
            . "Yth. *" . $namaPembawa . "*\n\n"
            . "Surat Jalan untuk proses pembawaan barang telah *" . $statusText . "* oleh Asman.\n\n"
            . "No Surat Jalan : *" . ($head->nosuratjalan ?? '-') . "*\n"
            . "No Pendaftaran : *" . ($head->nopendaftaran ?? '-') . "*\n"
            . "Diberikan Kepada : *" . ($head->diberikankepada ?? '-') . "*\n"
            . "Tujuan : *" . ($head->tujuan ?? '-') . "*\n"
            . "Tanggal : *" . $tanggalSurat . "*\n"
            . "Jumlah Barang : *" . $jumlahBarang . " barang*\n"
            . "Asman : *" . $namaAsman . "*\n";

        if ((int) $statusApproval === 3) {
            $pesanPembawa .= "Alasan Penolakan : *" . ($alasanTolak ?: '-') . "*\n";
        }

        $pesanPembawa .= "\nSalam,\n"
            . "U-LAB ! Cepat, Tepat, Akurat 💯";

        if (!empty($head->nohp_admin)) {
            $this->kirimWhatsappNotifikasi($head->nohp_admin, $pesanAdmin);
        }

        if (!empty($head->nohp_pembawa)) {
            $this->kirimWhatsappNotifikasi($head->nohp_pembawa, $pesanPembawa);
        }
    }

    public function saveSetujuiSuratJalanAsman(Request $r)
    {
        DB::beginTransaction();

        try {
            if (!isset($r['norec']) || $r['norec'] == '') {
                throw new \Exception('Norec surat jalan tidak ditemukan.');
            }

            if (!isset($r['asmanfk']) || $r['asmanfk'] == '') {
                throw new \Exception('Id Asman tidak ditemukan.');
            }

            if (!isset($r['namaasman']) || $r['namaasman'] == '') {
                throw new \Exception('Nama Asman tidak ditemukan.');
            }

            $asmanfk = $r['asmanfk'] ?? null;
            $namaasman = $r['namaasman'] ?? null;

            if ($asmanfk) {
                $pegawai = DB::table('pegawai_m')
                    ->where('id', $asmanfk)
                    ->first();

                if ($pegawai) {
                    $namaasman = $pegawai->namalengkap;
                }
            }

            DB::table('suratjalan_t')
                ->where('norec', $r['norec'])
                ->update([
                    'statussuratjalan' => 2,
                    'keteranganstatus' => 'Disetujui Asman',
                    'tglasman' => Carbon::now(),
                    'asmanfk' => $asmanfk,
                    'namaasman' => $namaasman,
                    'updated_at' => Carbon::now()
                ]);

            DB::commit();

            try {
                $this->kirimWaHasilApprovalSuratJalan($r['norec'], 2, null);
            } catch (\Exception $e) {
                // WA gagal tidak menggagalkan approval
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Surat jalan berhasil disetujui Asman.',
                'norec' => $r['norec']
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function saveTolakSuratJalanAsman(Request $r)
    {
        DB::beginTransaction();

        try {
            if (!isset($r['norec']) || $r['norec'] == '') {
                throw new \Exception('Norec surat jalan tidak ditemukan.');
            }

            if (!isset($r['keteranganstatus']) || trim($r['keteranganstatus']) == '') {
                throw new \Exception('Alasan penolakan wajib diisi.');
            }

            if (!isset($r['asmanfk']) || $r['asmanfk'] == '') {
                throw new \Exception('Id Asman tidak ditemukan.');
            }

            if (!isset($r['namaasman']) || $r['namaasman'] == '') {
                throw new \Exception('Nama Asman tidak ditemukan.');
            }

            $asmanfk = $r['asmanfk'] ?? null;
            $namaasman = $r['namaasman'] ?? null;
            $alasan = trim($r['keteranganstatus']);


            if ($asmanfk) {
                $pegawai = DB::table('pegawai_m')
                    ->where('id', $asmanfk)
                    ->first();

                if ($pegawai) {
                    $namaasman = $pegawai->namalengkap;
                }
            }

            DB::table('suratjalan_t')
                ->where('norec', $r['norec'])
                ->update([
                    'statussuratjalan' => 3,
                    'keteranganstatus' => 'Ditolak Asman: ' . $alasan,
                    'tglasman' => Carbon::now(),
                    'asmanfk' => $asmanfk,
                    'namaasman' => $namaasman,
                    'updated_at' => Carbon::now()
                ]);

            DB::commit();

            try {
                $this->kirimWaHasilApprovalSuratJalan($r['norec'], 3, $alasan);
            } catch (\Exception $e) {
                // WA gagal tidak menggagalkan penolakan
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Surat jalan berhasil ditolak Asman.',
                'norec' => $r['norec']
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }
    }
}
