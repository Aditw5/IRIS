<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\CalibrationNotesTranslationService;
use App\Traits\ProtectsVerificationExcelPages;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\App;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Builder;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Support\Str;

class ManagerCtrl extends Controller
{
    use \App\Traits\HandlesWorksheetAttachments;
    use Valet;
    use ProtectsVerificationExcelPages;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function getAlatManager(Request $r)
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
                'mtr.jenisorder',
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
                'mtrd.setujuilembarkerjamanager',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.managersetujulembarkerjafk',
                'mtrd.tglverifpelaksana',
                'mtrd.asmansetujulaporanrepairfk',
                'mtrd.tglsetujuasmanlaporanrepair',
                'mtrd.managersetujulaporanrepairfk',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.sublingkupfk',
                'mtrd.statusrepairfk',
                'mtrd.isverifikasi',
                'mtrd.versisertifikat',
                'mtrd.versilaporanrepair',
                'mtrd.statusamandemen',
                'mtrd.isamandemen',
                'slk.namasublingkup',
                'js.jenissurkes',
                'js.id as jenissurkesfk'
            )
            ->where('mtrd.statusorderasman', 2)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true);

        if (isset($r['dari']) && $r['dari'] != '') {
            $data = $data->where(DB::raw("COALESCE(mtrd.tglsetujuasmanlembarkerja, mtrd.tglsetujuasmanlaporanrepair)::date"), '>=', $r->dari);
        }

        if (isset($r['sampai']) && $r['sampai'] != '') {
            $data = $data->where(DB::raw("COALESCE(mtrd.tglsetujuasmanlembarkerja, mtrd.tglsetujuasmanlaporanrepair)::date"), '<=', $r->sampai);
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

        if (isset($r['statusordermanager']) && $r['statusordermanager'] != '') {
            $data = $data->where('mtrd.statusordermanager', '=', $r['statusordermanager']);
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
            $tglMulaiManager = !empty($item->tanggalmulai)
                ? Carbon::parse($item->tanggalmulai)
                : null;

            $tglSelesaiManager = !empty($item->tglsetujumanagerlembarkerja)
                ? Carbon::parse($item->tglsetujumanagerlembarkerja)
                : null;

            $diffInSeconds = $calcWorkingSeconds($tglMulaiManager, $tglSelesaiManager);

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

    public function getLembarKerjaManager(Request $request)
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

    private function quotePgIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException('Nama identifier database tidak valid.');
        }

        return '"' . $identifier . '"';
    }

    private function quotePgQualifiedIdentifier(string $identifier): string
    {
        return collect(explode('.', $identifier))
            ->map(fn ($part) => $this->quotePgIdentifier($part))
            ->implode('.');
    }

    private function getPgColumnSequenceName(string $table, string $column): ?string
    {
        $sequence = DB::selectOne(
            'SELECT pg_get_serial_sequence(?, ?) AS sequence_name',
            [$table, $column]
        );

        if ($sequence && !empty($sequence->sequence_name)) {
            return (string) $sequence->sequence_name;
        }

        $default = DB::selectOne(
            'SELECT column_default
             FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = ?
               AND column_name = ?',
            [$table, $column]
        );

        $defaultValue = (string) ($default->column_default ?? '');
        if (preg_match("/nextval\\('([^']+)'::regclass\\)/", $defaultValue, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function ensurePgPrimaryKeySequenceIsHealthy(string $table, string $column = 'id'): void
    {
        $defaultConnection = config('database.default');
        $driver = config("database.connections.{$defaultConnection}.driver");

        if ($driver !== 'pgsql') {
            return;
        }

        $sequenceName = $this->getPgColumnSequenceName($table, $column);
        if (empty($sequenceName)) {
            return;
        }

        DB::selectOne('SELECT pg_advisory_xact_lock(hashtext(?))', [$sequenceName]);

        $quotedTable = $this->quotePgIdentifier($table);
        $quotedColumn = $this->quotePgIdentifier($column);
        $quotedSequence = $this->quotePgQualifiedIdentifier($sequenceName);

        $tableState = DB::selectOne(
            "SELECT COALESCE(MAX({$quotedColumn}), 0) AS max_id FROM {$quotedTable}"
        );
        $sequenceState = DB::selectOne(
            "SELECT last_value, is_called FROM {$quotedSequence}"
        );

        $maxId = (int) ($tableState->max_id ?? 0);
        $lastValue = (int) ($sequenceState->last_value ?? 0);
        $isCalled = filter_var($sequenceState->is_called ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($maxId > 0 && ($lastValue < $maxId || (!$isCalled && $lastValue <= $maxId))) {
            DB::selectOne('SELECT setval(?::regclass, ?, true)', [$sequenceName, $maxId]);
        }
    }

    public function setujuiSertifikatManager(Request $r)
    {
        DB::beginTransaction();

        try {
            $VI = $r['verif'];
            $timestamp = now();

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'setujuilembarkerjamanager' => true,
                    'managersetujulembarkerjafk' => $this->getPegawaiId(),
                    'tglsetujumanagerlembarkerja' => $timestamp,
                    'statusordermanager' => 2,
                ]);

            $detail = DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->first();

            $noorderalat = $detail->noorderalat ?? '-';
            $norecReg = $detail->noregistrasifk ?? null;

            $this->lockNomorTransaksi('versi-sertifikat', [$VI['norec']]);

            $currentVersion = DB::table('sertifikat_log')
                ->where('norec_detail', $VI['norec'])
                ->max('version');

            $nextVersion = $currentVersion ? ($currentVersion + 1) : 1;

            $req = new Request([
                'norec'        => $norecReg,
                'norec_detail' => $VI['norec'],
                'storage'      => true,
            ]);

            if (is_array($VI) && array_key_exists('isverifikasi', $VI)) {
                $pdfWrapper = $this->cetakLaporanVerifikasi($req);
            } else {
                $pdfWrapper = $this->cetakSertifikatLembarKerja($req);
            }

            $pdfBinary = $pdfWrapper->output();

            $dir = public_path('sertifikat');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $filename = sprintf(
                'sertifikat_%s_%s_%s_v%d.pdf',
                (string) $norecReg,
                (string) $VI['norec'],
                date('Ym'),
                $nextVersion
            );

            $filePath = $dir . '/' . $filename;
            file_put_contents($filePath, $pdfBinary);

            $relativePath = 'sertifikat/' . $filename;

            $this->ensurePgPrimaryKeySequenceIsHealthy('sertifikat_log');

            DB::table('sertifikat_log')->insert([
                'norec_detail' => (string) $VI['norec'],
                'norec'        => (string) $norecReg,
                'version'      => $nextVersion,
                'file_path'    => $relativePath,
                'created_by'   => $this->getPegawaiId(),
                'created_at'   => now(),
            ]);

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'versisertifikat' => $nextVersion,
                ]);

            $data = DB::table('mitraregistrasi_t as mtr')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
                ->select(
                    'mtr.norec',
                    'mtrd.durasikalbrasi',
                    'mtrd.tanggalmulai',
                    'mtrd.tglsetujuasmanlembarkerja',
                    'mtrd.tglsetujumanagerlembarkerja',
                    'mtr.petugaskaji',
                    'mtr.customerfk',
                    'mtr.nomitrafk'
                )
                ->where('mtrd.norec', $VI['norec'])
                ->where('mtr.statusenabled', true)
                ->where('mtrd.statusenabled', true)
                ->first();

            $tglAwal = !empty($data->tanggalmulai)
                ? \Carbon\Carbon::parse($data->tanggalmulai)
                : null;

            $tglAkhir = !empty($data->tglsetujumanagerlembarkerja)
                ? \Carbon\Carbon::parse($data->tglsetujumanagerlembarkerja)
                : (!empty($data->tglsetujuasmanlembarkerja)
                    ? \Carbon\Carbon::parse($data->tglsetujuasmanlembarkerja)
                    : null);

            $statusPengerjaan = '-';

            $holidayDates = DB::table('master_hari_libur_m')
                ->where('statusenabled', true)
                ->pluck('tanggal')
                ->map(function ($tanggal) {
                    return \Carbon\Carbon::parse($tanggal)->format('Y-m-d');
                })
                ->toArray();

            $calcWorkingSeconds = function (?\Carbon\Carbon $start, ?\Carbon\Carbon $end) use ($holidayDates): ?int {
                if (!$start || !$end) {
                    return null;
                }

                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                $workStart = '07:30:00';
                $workEnd = '16:00:00';

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
                        $dayWorkEnd = $cursor->copy()->setTimeFromTimeString($workEnd);

                        $effectiveStart = $start->copy()->max($dayWorkStart);
                        $effectiveEnd = $end->copy()->min($dayWorkEnd);

                        if ($effectiveEnd->greaterThan($effectiveStart)) {
                            $totalSeconds += $effectiveEnd->diffInSeconds($effectiveStart);
                        }
                    }

                    $cursor->addDay();
                }

                return $totalSeconds;
            };

            if ($tglAwal && $tglAkhir) {
                $diffInSeconds = $calcWorkingSeconds($tglAwal, $tglAkhir);

                if ($diffInSeconds !== null) {
                    $workdaySeconds = (8 * 60 * 60) + (30 * 60);

                    $days = intdiv($diffInSeconds, $workdaySeconds);
                    $rem = $diffInSeconds % $workdaySeconds;

                    $hours = intdiv($rem, 3600);

                    $statusPengerjaan = "{$days} hari kerja {$hours} jam";
                }
            }

            $waktuVerif = $timestamp;

            try {
                $waktuVerifStr = \Carbon\Carbon::parse($waktuVerif)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuVerifStr = date('d-m-Y H:i', strtotime($waktuVerif)) . ' WIB';
            }

            $regis = DB::table('mitraregistrasi_t')
                ->where('norec', $detail->noregistrasifk)
                ->first();

            $username = 'Mahzumi';
            $profileId = $regis->kdprofile ?? 1;

            $token = $this->createToken($username) . '.' . base64_encode((string) $profileId);

            $link = url('/service/asman/cetak-sertifikat-lembar-kerja') . '?pdf=true'
                . '&norec=' . $norecReg
                . '&norec_detail=' . $VI['norec']
                . '&user=' . urlencode($username)
                . '&kdprofile=' . $profileId
                . '&token=' . $token;

            $shortLink = $this->shortLink($link);

            $manager = DB::table('pegawai_m')
                ->where('namalengkap', $detail->namamanager)
                ->first();

            $namaManagerReal = $manager->namalengkap ?? '-';

            $asmanId = $regis->asmanveriffk ?? null;
            if ($asmanId) {
                $asman = DB::table('pegawai_m')
                    ->where('id', $asmanId)
                    ->first();

                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';

                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *$noorderalat*\n"
                        . "Telah dilakukan pengesahan Sertifikat Kalibrasi oleh Manager Repair : *$namaManagerReal*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Status pengerjaan: $statusPengerjaan\n\n"
                        . "Tautan Sertifikat Kalibrasi :\n$shortLink\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            if ($pelaksanaId) {
                $pelaksana = DB::table('pegawai_m')
                    ->where('id', $pelaksanaId)
                    ->first();

                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';

                if ($nohpPelaksana) {
                    $pesanPelaksana = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPelaksana\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *$noorderalat*\n"
                        . "Telah dilakukan pengesahan Sertifikat Kalibrasi oleh Manager Repair : *$namaManagerReal*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Status pengerjaan: $statusPengerjaan\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpPelaksana, $pesanPelaksana);
                }
            }

            $penyeliaId = $detail->penyeliateknikfk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')
                    ->where('id', $penyeliaId)
                    ->first();

                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';

                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *$noorderalat*\n"
                        . "Telah dilakukan pengesahan Sertifikat Kalibrasi oleh Manager Repair : *$namaManagerReal*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Status pengerjaan: $statusPengerjaan\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $adminId = $regis->petugaskaji ?? null;
            if ($adminId) {
                $admin = DB::table('pegawai_m')
                    ->where('id', $adminId)
                    ->first();

                $nohpAdmin = $admin->nohandphone ?? null;
                $namaAdmin = $admin->namalengkap ?? '-';

                if ($nohpAdmin) {
                    $pesanAdmin = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAdmin\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *$noorderalat*\n"
                        . "Telah dilakukan pengesahan Sertifikat Kalibrasi oleh Manager Repair : *$namaManagerReal*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Status pengerjaan: $statusPengerjaan\n\n"
                        . "Tautan Sertifikat Kalibrasi :\n$shortLink\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpAdmin, $pesanAdmin);
                }
            }

            $idPenyeliaAdmin = $this->settingFix('idPenyeliaAdmin') ?? null;
            if ($idPenyeliaAdmin) {
                $penyeliaAdmin = DB::table('pegawai_m')
                    ->where('id', $idPenyeliaAdmin)
                    ->first();

                $nohpPenyeliaAdmin = $penyeliaAdmin->nohandphone ?? null;
                $namaPenyeliaAdmin = $penyeliaAdmin->namalengkap ?? '-';

                $pesanPenyeliaAdmin = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyeliaAdmin\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan pengesahan Sertifikat Kalibrasi oleh Manager Repair : *$namaManagerReal*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Status pengerjaan: $statusPengerjaan\n\n"
                    . "Tautan Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                if ($nohpPenyeliaAdmin) {
                    $this->kirimWhatsappNotifikasi($nohpPenyeliaAdmin, $pesanPenyeliaAdmin);
                }
            }

            $customer = null;

            if (!empty($regis->customerfk)) {
                $customer = DB::table('users')
                    ->where('id', $regis->customerfk)
                    ->first();
            }

            $nohpCustomer = $customer->nowa ?? ($regis->nohppenanggungjawab ?? null);
            $namaCustomer = $customer->name ?? ($regis->namapenanggungjawab ?? 'Customer');

            $pesanCustomer = "Yth. $namaCustomer,\n\n"
                . "Kami informasikan bahwa proses kalibrasi alat Anda pada order No. Order Alat: *$noorderalat* telah selesai.\n\n"
                . "Proses kalibrasi selesai Dalam: $statusPengerjaan\n\n"
                . "Sertifikat kalibrasi dapat dilihat dan diunduh melalui aplikasi U-LAB Mobile.\n"
                . "Jika belum memiliki aplikasinya, silakan unduh melalui Play Store:\n"
                . "https://play.google.com/store/apps/details?id=id.ulabumro.mobile\n\n"
                . "Hubungi admin kami jika memerlukan bantuan lebih lanjut.\n\n";

            $unit = '-';

            $mitraFkMobile = null;

            if (!empty($regis->nomitrafk)) {
                $mitraFkMobile = trim((string) $regis->nomitrafk);
            } elseif ($customer && !empty($customer->mitrafk)) {
                $mitraFkMobile = trim((string) $customer->mitrafk);
            }

            if (!empty($mitraFkMobile)) {
                $mitra = DB::table('mitra_m')
                    ->whereRaw('CAST(id AS TEXT) = ?', [(string) $mitraFkMobile])
                    ->first();

                $unit = $mitra->namaperusahaan ?? '-';
            }

            $pesanCustomer .= "Terima kasih Yth Bpk/Ibu $namaCustomer dari $unit\n"
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

            /** Push notification ke customer pemilik order; tidak menggagalkan approval. */
            if (!empty($regis->customerfk)) {
                try {
                    $pushResult = app(\App\Services\MobilePushNotificationService::class)->sendToUser(
                        $regis->customerfk,
                        'Sertifikat Kalibrasi Disetujui',
                        "Sertifikat kalibrasi untuk No Order $noorderalat telah disetujui Manager dan siap dilihat di U-LAB Mobile.",
                        [
                            'type' => 'sertifikat_disetujui_manager',
                            'screen' => 'history_detail_screen',
                            'target_screen' => 'history_detail_screen',
                            'action' => 'open_certificate',
                            'norec_registrasi' => (string) ($norecReg ?? ''),
                            'norec_detail' => (string) ($VI['norec'] ?? ''),
                            'noorderalat' => (string) ($noorderalat ?? ''),
                            'highlight_norec_detail' => (string) ($VI['norec'] ?? ''),
                            'highlight_noorderalat' => (string) ($noorderalat ?? ''),
                            'version' => (string) ($nextVersion ?? ''),
                            'status_pengerjaan' => (string) ($statusPengerjaan ?? ''),
                            'source' => 'setujuiSertifikatManager',
                        ]
                    );

                    \Illuminate\Support\Facades\Log::info('FCM setujui sertifikat manager berhasil diproses', [
                        'nomitrafk' => $regis->nomitrafk ?? null,
                        'customerfk' => $regis->customerfk ?? null,
                        'noorderalat' => $noorderalat,
                        'norec_registrasi' => $norecReg,
                        'norec_detail' => $VI['norec'] ?? null,
                        'push_result' => $pushResult,
                    ]);
                } catch (\Throwable $pushError) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim FCM setujui sertifikat manager', [
                        'message' => $pushError->getMessage(),
                        'nomitrafk' => $regis->nomitrafk ?? null,
                        'customerfk' => $regis->customerfk ?? null,
                        'noorderalat' => $noorderalat,
                        'norec_registrasi' => $norecReg,
                        'norec_detail' => $VI['norec'] ?? null,
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('FCM setujui sertifikat manager dilewati karena customerfk kosong', [
                    'customerfk' => $regis->customerfk ?? null,
                    'nomitrafk' => $regis->nomitrafk ?? null,
                    'noorderalat' => $noorderalat,
                    'norec_registrasi' => $norecReg,
                    'norec_detail' => $VI['norec'] ?? null,
                ]);
            }

            DB::commit();

            return $this->respond(
                ["message" => "Approved & saved as $relativePath (v$nextVersion)"],
                200,
                "Simpan Setujui Sertifikat Sukses"
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond($e->getMessage(), 400, "Simpan Gagal");
        }
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
                'mtr.norec as iddetail',
                'mtr.statusorder',
                'mtr.statusordermanager',
                'mtr.jenisorder',
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            ->where('mtr.statusorder', 1)
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
            $data = $data->where('mtr.statusordermanager', '=', $r['statusorder']);
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

        return $this->respond($data);
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
                'mtrd.noorderalat',
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
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.norec', $r['norec_pd']);

        if (isset($r['norecdetail']) && $r['norecdetail'] != "" && $r['norecdetail'] != "undefined") {
            $data = $data->where('mtrd.norec', '=', $r['norecdetail']);
        };

        $data = $data->orderByDesc('mtrd.noorderalat')->get();


        $result['length'] = count($data);
        $result['detail'] = $data;
        $result['as'] = '@adit';

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
                'mtrd.noorderalat',
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
                'mtrd.tglisilaporanrepairpelaksana',
                'mtrd.managersetujulembarkerjafk',
                'mtrd.tglisilaporanrepairpenyelia',
                'mtrd.tglsetujupenyelialaporanrepair',
                'mtrd.tglsetujuasmanlaporanrepair',
                'mtrd.tglsetujumanagerlaporanrepair',
                'pg5.id as penyeliasetujuilembarkerjafk',
                'pg5.namalengkap as penyeliasetujuilembarkerja',
                'pg6.id as asmansetujuilembarkerjafk',
                'pg6.namalengkap as asmansetujuilembarkerja',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.managersetujulembarkerjafk',
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
            ->orderByDesc('mtrd.noorderalat')
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

            $lokasi = DB::table('lokasikalibrasi_m')
                ->where('id', $VI['lokasikalibrasi'])
                ->first();
            $lokasiInisial = '';
            if ($lokasi) {
                $lokasiInisial = $lokasi->inisial ?? strtoupper(substr($lokasi->lokasi, 0, 1));
            }

            $lingkup = DB::table('lingkupkalibrasi_m')
                ->where('id', $VI['lingkupkalibrasi'])
                ->first();
            $lingkupInisial = '';
            if ($lingkup) {
                $lingkupInisial = $lingkup->inisial ?? strtoupper(substr($lingkup->lingkupkalibrasi, 0, 1));
            }

            $tahun = date('y');
            $bulan = date('m');

            $urut = DB::table('mitraregistrasidetail_t')
                ->where('noregistrasifk', $VI['norec'])
                ->whereYear('tglverifasman', date('Y'))
                ->whereMonth('tglverifasman', date('m'))
                ->count() + 1;

            $urutStr = str_pad($urut, 3, '0', STR_PAD_LEFT);
            $noorderalat = "{$lokasiInisial}{$lingkupInisial}-{$tahun}.{$bulan}.{$urutStr}";

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec_detail'])
                ->update([
                    'noorderalat' => $noorderalat,
                    'iskaji' => true,
                    'lokasikajifk' => $VI['lokasikalibrasi'],
                    'lingkupkalibrasifk' => $VI['lingkupkalibrasi'],
                    'penyeliateknikfk' => $VI['penyeliateknik'],
                    'pelaksanateknikfk' => $VI['pelaksana'],
                    'durasikalbrasi' => $VI['durasikalbrasi'],
                    'asmanveriffk' => $this->getPegawaiId(),
                    'tglverifasman' => now(),
                    'statusorderasman' => 1,
                    'statusorderpelaksana' => 0,
                    'statusorderpenyelia' => 0,
                ]);

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

    public function saveVerif(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['verif'];
            DB::table('mitraregistrasi_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'statusorder' => 1,
                    'asmanveriffk' => $this->getPegawaiId(),
                ]);

            $transMessage = "Simpan Verif Sukses";
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
            )
            ->where('mt.statusenabled', true)
            ->where('mtr.statusenabled', true)
            // ->where('mt.id', $r['nocmfk'])
            ->where('mtr.norec', $r['norec_pd'])
            ->first();

        $result['mitra'] = $data;
        $result['as'] = 'aditwiran19@gmail.com';

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
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'jb.namajabatanulab as jabatanpelaksana',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.norec', $r['norec'])
            ->where('mtrd.pelaksanateknikfk', $r['pelaksanateknikfk'])
            ->orderByDesc('mtrd.noorderalat')
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
        $profile   = $this->profile();
        $print     = false;
        $pageWidth = 950;

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
            ->where('mtr.norec', $r['norec'])
            ->first();

        /* ===================== INSTRUKSI KERJA ===================== */
        $res['instruksikerja'] = DB::table('daftarinstruksikerja_t as dik')
            ->leftJoin('instruksikerja_m as ik', 'ik.id', '=', 'dik.idalatinstruksikerja')
            ->select('dik.detailregistrasifk', 'ik.id', 'ik.namainstruksikerja', 'ik.noisntruksikerja')
            ->where('dik.detailregistrasifk', $r['norec_detail'])
            ->where('dik.statusenabled', true)
            ->where('ik.statusenabled', true)
            ->get();

        /* ===================== ALAT STANDAR ===================== */
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

        /* ===================== DATA ALAT ===================== */
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
                'mtrd.kesejajaranluar',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_detail'])
            ->first();

        /* ===================== LEMBAR KERJA (DIPERSINGKAT) ===================== */
        $rmJoin = ['ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id'];

        $selectBase = [
            'lk.*',
            'mtrd.tglkalibrasilembarkerja',
            'rm.namaruangan as tempatKalibrasilembarkerja',
            'mtrd.kondisiRuanganlembarkerja',
            'mtrd.suhulembarkerja',
            'mtrd.kelembabanRelatiflembarkerja',
            'mtrd.noteslembarkerja',
            'mtrd.jarakKalibrasi',
            'mtrd.emisivitas',
            'mtrd.sublingkupfk',
            'mtrd.lingkupkalibrasifk',
        ];

        $configs = [
            1  => ['lembarkerja_t as lk', [], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            2  => ['lembarkerjaclamp_t as lk', [], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            3  => ['lembarkerjasumber_t as lk', [], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            4  => ['lembarkerjavibration_t as lk', ['mtrd.rangelembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            5  => ['lembarkerjaaccellerometer_t as lk', ['mtrd.rangelembarkerja'], [
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            6  => ['lembarkerjaclampmeter_t as lk', [], [
                ['orderBy', ['lk.jenis', 'ASC']],
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            7  => ['lembarkerjametersumber_t as lk', [], [
                ['orderBy', ['lk.jenis', 'ASC']],
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            8  => ['lembarkerjathermometerinfrared_t as lk', ['mtrd.resolusi', 'mtrd.rentangukursuhu', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            9  => ['lembarkerjathermalimager_t as lk', ['mtrd.resolusi', 'mtrd.rentangukursuhu', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            10 => ['lembarkerjathermohygrometer_t as lk', [
                'mtrd.resolusi',
                'mtrd.resolusipersen',
                'mtrd.rentangukursuhu',
                'mtrd.rentangukursuhupersen',
                'mtrd.kondisipengukuransuhu',
                'mtrd.kondisipengukurankelembaban',
                'mtrd.noteslembarkerja',
            ], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            11 => ['lembarkerjadryblock_t as lk', ['mtrd.resolusi', 'mtrd.rentangukursuhu', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.jenis', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            12 => ['lembarkerjavibrationcalibrator_t as lk', ['mtrd.rangelembarkerja'], [
                ['orderBy', ['lk.jenis', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            13 => ['lembarkerjacalipermicrometer_t as lk', ['mtrd.rangelembarkerja', 'mtrd.kesejajaranluar', 'mtrd.resolusi', 'mtrd.rangelembarkerjasatuan', 'mtrd.resolusilembarkerjasatuan', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            14 => ['lembarkerjadialindicator_t as lk', ['mtrd.resolusi', 'mtrd.rangelembarkerja', 'mtrd.resolusilembarkerjasatuan', 'mtrd.rangelembarkerjasatuan'], [
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            15 => ['lembarkerjaboregauge_t as lk', [
                'mtrd.merekdial',
                'mtrd.tipedial',
                'mtrd.sndial',
                'mtrd.rangedial',
                'mtrd.resolusidial',
                'mtrd.noteslembarkerja',
            ], [
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            16 => ['lembarkerjaclampmetersumber_t as lk', [], [
                ['orderBy', ['lk.jenis', 'ASC']],
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            18  => ['lembarkerjacontinuitysource_t as lk', [], [
                ['orderBy', ['lk.jenis', 'ASC']],
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            19  => ['lembarkerjatachometer_t as lk', [], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            20  => ['lembarkerjainsulationmeterresistansi_t as lk', [], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            21 => ['lembarkerjatorquewrench_t as lk', [
                'mtrd.resolusi',
                'mtrd.rangelembarkerja',
                'mtrd.suhulembarkerja',
                'mtrd.kelembabanRelatiflembarkerja',
                'mtrd.noteslembarkerja',
            ], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            22  => ['lembarkerjatemperatureindicator_t as lk', ['mtrd.resolusi', 'mtrd.jenissensor', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            23  => ['lembarkerjaultrasonicthickness_t as lk', ['mtrd.resolusi', 'mtrd.kecepatan', 'mtrd.standarmaterial', 'mtrd.rangelembarkerja', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            24  => ['lembarkerjathicknessgauge_t as lk', ['mtrd.resolusi', 'mtrd.rangelembarkerja', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            33  => ['lembarkerjafeelergauge_t as lk', ['mtrd.resolusi', 'mtrd.rangelembarkerja', 'mtrd.noteslembarkerja'], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            25  => ['lembarkerjainsulationtester_t as lk', [], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            26  => ['lembarkerjainsulationsource_t as lk', [], [
                ['orderBy', ['lk.jenis', 'ASC']],
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            27 => ['lembarkerjamicrometerhead_t as lk', ['mtrd.rangelembarkerja', 'mtrd.resolusi', 'mtrd.rangelembarkerjasatuan', 'mtrd.resolusilembarkerjasatuan', 'mtrd.noteslembarkerja'], [
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            28 => ['lembarkerjaoutsidemicrometer_t as lk', ['mtrd.rangelembarkerja', 'mtrd.resolusi', 'mtrd.rangelembarkerjasatuan', 'mtrd.resolusilembarkerjasatuan', 'mtrd.noteslembarkerja'], [
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            29 => ['lembarkerjaoscilloscope_t as lk', [], [
                ['orderBy', ['lk.group', 'ASC']],
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
            30 => ['lembarkerjathermocouplertdsimulator_t as lk', [], [
                ['orderByRaw', ['CAST(lk.no AS INTEGER) ASC']],
            ]],
        ];

        if ($res['alat']->lingkupkalibrasifk == 2 || $res['alat']->sublingkupfk == null) {
            $res['lembarKerja'] = DB::table('lembarkerjatekanan_t as lk')
                ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                ->leftJoin(...$rmJoin)
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
        } else {
            $sk = (int) $res['alat']->sublingkupfk;

            if (isset($configs[$sk])) {
                [$table, $extraSelects, $orders] = $configs[$sk];

                $selects = $selectBase;

                // tambahan select khusus (tanpa mengubah field yang sudah ada)
                foreach ($extraSelects as $s) {
                    $selects[] = $s;
                }

                // beberapa sublingkup butuh field khusus yang tidak ada di base (tekanan sudah terpisah)
                // (base sudah sama dengan semua kebutuhan umum)

                $q = DB::table($table)
                    ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lk.detailregistraifk')
                    ->leftJoin(...$rmJoin)
                    ->select(...$selects)
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('lk.statusenabled', true);

                // sebagian query punya where('mtrd.statusenabled', true)
                // (ikuti pola aslinya: hampir semua pakai, beberapa tidak)
                // kita set sama persis seperti aslinya:
                if (!in_array($sk, [1, 13, 14, 15], true)) {
                    $q->where('mtrd.statusenabled', true);
                } else {
                    if ($sk === 1 || $sk === 13 || $sk === 14 || $sk === 15) {
                        // sk=1 tidak ada where mtrd.statusenabled pada kode asli
                    }
                }

                foreach ($orders as $o) {
                    $method = $o[0];
                    $args   = $o[1];
                    $q->{$method}(...$args);
                }

                $res['lembarKerja'] = $q->get();
            } else {
                $res['lembarKerja'] = collect([]);
            }
        }

        if (!empty($res['lembarKerja'][0]->noteslembarkerja)) {
            app(CalibrationNotesTranslationService::class)
                ->prepareWorksheetForPrint($r['norec_detail'], $res['lembarKerja'][0]);
        }

        $res['pdf'] = $r['pdf'];
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

        $renderPdf = function ($jumlahHalaman = null) use ($blade, $profile, $pageWidth, $print, $res) {
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

            if ($jumlahHalaman !== null) {
                $dompdf  = $pdf->getDomPDF();
                $canvas  = $dompdf->get_canvas();
                $canvas->page_text(230, 780, "Halaman ke {PAGE_NUM} dari {PAGE_COUNT} halaman", null, 8, [0, 0, 0]);
                $font = $dompdf->getFontMetrics()->getFont('Helvetica', 'italic');
                $canvas->page_text(260, 788, "Page {PAGE_NUM} of {PAGE_COUNT} pages", $font, 7, [0, 0, 0]);
            }

            return $pdf;
        };

        $countPages = function () use ($renderPdf) {
            $pdfDummy = $renderPdf(null);
            $dompdfDummy = $pdfDummy->getDomPDF();
            $dompdfDummy->render();
            return $dompdfDummy->getCanvas()->get_page_count();
        };

        if ($res['pdf'] == 'true') {
            $jumlahHalaman = $countPages();
            return $renderPdf($jumlahHalaman)->stream();
        }

        if (isset($r['storage'])) {
            $jumlahHalaman = $countPages();
            return $renderPdf($jumlahHalaman);
        }

        return view($blade, compact('profile', 'pageWidth', 'print', 'res'));
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

    public function getLaporanRepairManager(Request $request)
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

    public function cetakLaporanRepairManager(Request $r)
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

    public function detailAlatRepairManager(Request $r)
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
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_pd'])
            ->orderByDesc('mtrd.noorderalat')
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

    public function hapusLaporanRepairManager(Request $r)
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

    public function setujuiLaporanRepairManager(Request $r)
    {
        DB::beginTransaction();

        try {
            $VI = $r['verif'];
            $timestamp = now();

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'managersetujulaporanrepairfk'  => $this->getPegawaiId(),
                    'tglsetujumanagerlaporanrepair' => $timestamp,
                    'statusordermanager'            => 2,
                ]);

            $detail = DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->first();

            $noorderalat = $detail->noorderalat ?? '-';
            $norecReg = $detail->noregistrasifk ?? null;

            $tglAwal = $detail->tglverifpelaksana
                ? \Carbon\Carbon::parse($detail->tglverifpelaksana)
                : null;

            $tglAkhir = $detail->tglsetujumanagerlaporanrepair
                ? \Carbon\Carbon::parse($detail->tglsetujumanagerlaporanrepair)
                : null;

            $durasiString = '';

            if ($tglAwal && $tglAkhir) {
                $diff = $tglAkhir->diff($tglAwal);
                $bag = [];

                if ($diff->days > 0) {
                    $bag[] = "{$diff->days} hari";
                }

                if ($diff->h > 0) {
                    $bag[] = "{$diff->h} jam";
                }

                if ($diff->i > 0) {
                    $bag[] = "{$diff->i} menit";
                }

                if (!$bag) {
                    $bag[] = "< 1 menit";
                }

                $durasiString = "Laporan Repair telah selesai dalam waktu " . implode(' ', $bag)
                    . " (mulai {$tglAwal->format('d-m-Y H:i')} WIB s/d {$tglAkhir->format('d-m-Y H:i')} WIB).";
            }

            $this->lockNomorTransaksi('versi-laporan-repair', [$VI['norec']]);

            $currentVersion = DB::table('laporan_repair_log')
                ->where('norec_detail', $VI['norec'])
                ->max('version');

            $nextVersion = $currentVersion ? ($currentVersion + 1) : 1;

            $reqCetak = new Request([
                'norec'        => $norecReg,
                'norec_detail' => $VI['norec'],
                'storage'      => true,
            ]);

            $pdfWrapper = $this->cetakLaporanRepairManager($reqCetak);
            $pdfBinary = $pdfWrapper->output();

            $dir = public_path('laporan-repair');

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $filename = sprintf(
                'laporan_repair_%s_%s_%s_v%d.pdf',
                (string) $norecReg,
                (string) $VI['norec'],
                date('Ym'),
                $nextVersion
            );

            $filePath = $dir . '/' . $filename;
            file_put_contents($filePath, $pdfBinary);

            $relativePath = 'laporan-repair/' . $filename;

            DB::table('laporan_repair_log')->insert([
                'norec_detail' => (string) $VI['norec'],
                'norec'        => (string) $norecReg,
                'version'      => $nextVersion,
                'file_path'    => $relativePath,
                'created_by'   => $this->getPegawaiId(),
                'created_at'   => now(),
            ]);

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'versilaporanrepair' => $nextVersion,
                ]);

            $regis = DB::table('mitraregistrasi_t')
                ->where('norec', $detail->noregistrasifk)
                ->first();

            $idPenyeliaAdmin = $this->settingFix('idPenyeliaAdmin') ?? null;

            if ($regis->asmanveriffk ?? null) {
                $asman = DB::table('pegawai_m')
                    ->where('id', $regis->asmanveriffk)
                    ->first();

                if ($asman && $asman->nohandphone) {
                    $pesan = "Yth. {$asman->namalengkap},\n\n"
                        . "Laporan Repair pada order No. Order Alat: *$noorderalat* telah disetujui oleh Manager.\n\n"
                        . "$durasiString\n\n"
                        . "File: $relativePath\n\n"
                        . "Silakan lanjutkan proses berikutnya.\n\n"
                        . "Salam,\n"
                        . "Laboratorium Kalibrasi ULAB UMRO";

                    $this->kirimWhatsappNotifikasi($asman->nohandphone, $pesan);
                }
            }

            if ($detail->pelaksanateknikfk ?? null) {
                $pel = DB::table('pegawai_m')
                    ->where('id', $detail->pelaksanateknikfk)
                    ->first();

                if ($pel && $pel->nohandphone) {
                    $pesan = "Yth. {$pel->namalengkap},\n\n"
                        . "Laporan Repair untuk No. Order Alat: *$noorderalat* telah disetujui.\n\n"
                        . "$durasiString\n\n"
                        . "File: $relativePath\n\n"
                        . "Terima kasih atas kinerjanya.\n\n"
                        . "Salam,\n"
                        . "Laboratorium Kalibrasi ULAB UMRO";

                    $this->kirimWhatsappNotifikasi($pel->nohandphone, $pesan);
                }
            }

            if ($detail->penyeliateknikfk ?? null) {
                $pen = DB::table('pegawai_m')
                    ->where('id', $detail->penyeliateknikfk)
                    ->first();

                if ($pen && $pen->nohandphone) {
                    $pesan = "Yth. {$pen->namalengkap},\n\n"
                        . "Laporan Repair pada No. Order Alat: *$noorderalat* telah disetujui.\n\n"
                        . "$durasiString\n\n"
                        . "File: $relativePath\n\n"
                        . "Terima kasih atas kerjasamanya.\n\n"
                        . "Salam,\n"
                        . "Laboratorium Kalibrasi ULAB UMRO";

                    $this->kirimWhatsappNotifikasi($pen->nohandphone, $pesan);
                }
            }

            if ($regis->petugaskaji ?? null) {
                $adm = DB::table('pegawai_m')
                    ->where('id', $regis->petugaskaji)
                    ->first();

                if ($adm && $adm->nohandphone) {
                    $pesan = "Yth. {$adm->namalengkap},\n\n"
                        . "Laporan Repair No. Order Alat: *$noorderalat* telah disetujui oleh Manager.\n\n"
                        . "$durasiString\n\n"
                        . "File: $relativePath\n\n"
                        . "Mohon proses administrasinya.\n\n"
                        . "Salam,\n"
                        . "Laboratorium Kalibrasi ULAB UMRO";

                    $this->kirimWhatsappNotifikasi($adm->nohandphone, $pesan);
                }
            }

            if ($idPenyeliaAdmin) {
                $admPenyelia = DB::table('pegawai_m')
                    ->where('id', $idPenyeliaAdmin)
                    ->first();

                if ($admPenyelia && $admPenyelia->nohandphone) {
                    $pesan = "Yth. {$admPenyelia->namalengkap},\n\n"
                        . "Laporan Repair No. Order Alat: *$noorderalat* telah disetujui oleh Manager.\n\n"
                        . "$durasiString\n\n"
                        . "File: $relativePath\n\n"
                        . "Mohon proses administrasinya.\n\n"
                        . "Salam,\n"
                        . "Laboratorium Kalibrasi ULAB UMRO";

                    $this->kirimWhatsappNotifikasi($admPenyelia->nohandphone, $pesan);
                }
            }
            $cust = null;

            if ($regis->customerfk ?? null) {
                $cust = DB::table('users')
                    ->where('id', $regis->customerfk)
                    ->first();

                if ($cust && $cust->nowa) {
                    $pesan = "Yth. {$cust->name},\n\n"
                        . "Proses repair alat Anda (No. Order Alat: *$noorderalat*) telah selesai.\n\n"
                        . "$durasiString\n\n"
                        . "Laporan repair dapat dilihat dan diunduh melalui aplikasi U-LAB Mobile.\n"
                        . "Jika belum memiliki aplikasinya, silakan unduh melalui Play Store:\n"
                        . "https://play.google.com/store/apps/details?id=id.ulabumro.mobile\n\n"
                        . "Laporan juga dapat diakses melalui web: https://ulabumro.id/\n"
                        . "Jika butuh bantuan, hubungi admin kami.\n\n"
                        . "Terima kasih.\n"
                        . "Salam,\n"
                        . "Laboratorium Kalibrasi ULAB UMRO";

                    $this->kirimWhatsappNotifikasi($cust->nowa, $pesan);
                }
            }

            if (!empty($regis->customerfk)) {
                try {
                    $pushResult = app(\App\Services\MobilePushNotificationService::class)->sendToUser(
                        $regis->customerfk,
                        'Laporan Repair Disetujui',
                        "Laporan repair untuk No Order $noorderalat telah disetujui Manager dan siap dilihat di U-LAB Mobile.",
                        [
                            'type' => 'laporan_repair_disetujui_manager',
                            'screen' => 'history_detail_screen',
                            'target_screen' => 'history_detail_screen',
                            'action' => 'open_repair_report',
                            'norec_registrasi' => (string) ($norecReg ?? ''),
                            'norec_detail' => (string) ($VI['norec'] ?? ''),
                            'noorderalat' => (string) ($noorderalat ?? ''),
                            'highlight_norec_detail' => (string) ($VI['norec'] ?? ''),
                            'highlight_noorderalat' => (string) ($noorderalat ?? ''),
                            'version' => (string) ($nextVersion ?? ''),
                            'file_path' => (string) ($relativePath ?? ''),
                            'durasi' => (string) ($durasiString ?? ''),
                            'source' => 'setujuiLaporanRepairManager',
                        ]
                    );

                    \Illuminate\Support\Facades\Log::info('FCM laporan repair manager berhasil diproses', [
                        'nomitrafk' => $regis->nomitrafk ?? null,
                        'customerfk' => $regis->customerfk ?? null,
                        'noorderalat' => $noorderalat,
                        'norec_registrasi' => $norecReg,
                        'norec_detail' => $VI['norec'] ?? null,
                        'push_result' => $pushResult,
                    ]);
                } catch (\Throwable $pushError) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim FCM laporan repair manager', [
                        'message' => $pushError->getMessage(),
                        'nomitrafk' => $regis->nomitrafk ?? null,
                        'customerfk' => $regis->customerfk ?? null,
                        'noorderalat' => $noorderalat,
                        'norec_registrasi' => $norecReg,
                        'norec_detail' => $VI['norec'] ?? null,
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('FCM laporan repair manager dilewati karena customerfk kosong', [
                    'customerfk' => $regis->customerfk ?? null,
                    'nomitrafk' => $regis->nomitrafk ?? null,
                    'noorderalat' => $noorderalat,
                    'norec_registrasi' => $norecReg,
                    'norec_detail' => $VI['norec'] ?? null,
                ]);
            }

            DB::commit();

            return $this->respond(
                ["message" => "Approved & saved as $relativePath (v$nextVersion)"],
                200,
                "Simpan Setujui Laporan Repair Manager Sukses"
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond($e->getMessage(), 400, "Simpan Gagal");
        }
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

    public function getLaporanVerifikasi(Request $request)
    {
        $data = DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->select('mtrd.excellaporanverif', 'mtrd.norec as detailregistraifk', 'mtr.norec as norecregis', 'mtrd.isverifikasi')
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

    public function savePenolakanSerti(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_tolak = $request['itemtolak'];
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_tolak['norecregis'])
                ->update([
                    'istolaksertimanager' => true,
                    'alasanpenolakansertimanager' => $r_tolak['alasanpenolakanserti'],
                    'tgltolaksertimanager' => now(),
                    'setujuilembarkerjaasman' => null,
                    'tglsetujuasmanlembarkerja' => null,
                    'asmansetujulembarkerjafk' => null,
                    'statusorderasman' => 0,
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

            $asmanfk = $regis->asmansetujulembarkerjafk ?? null;
            if ($asmanfk) {
                $asman = DB::table('pegawai_m')->where('id', $asmanfk)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';
                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Sertifikat Telah ditolak Oleh Manager.\n"
                        . "Dengan Keterangan Berikut : *$alasanpenolakanserti*.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $penyeliateknikfk = $regis->penyeliateknikfk ?? null;
            if ($penyeliateknikfk) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliateknikfk)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Sertifikat Telah ditolak Oleh Manager.\n"
                        . "Dengan Keterangan Berikut : *$alasanpenolakanserti*.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $pelaksanateknikfk = $regis->pelaksanateknikfk ?? null;
            if ($pelaksanateknikfk) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanateknikfk)->first();
                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';
                if ($nohpPelaksana) {
                    $pesanPelaksana = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPelaksana\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Sertifikat Telah ditolak Oleh Manager.\n"
                        . "Dengan Keterangan Berikut : *$alasanpenolakanserti*.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPelaksana, $pesanPelaksana);
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
                    'istolakrepairmanager' => true,
                    'alasanpenolakanrepairmanager' => $r_tolak['alasanpenolakan'],
                    'tgltolakrepairmanager' => now(),
                    'asmansetujulaporanrepairfk' => null,
                    'tglsetujuasmanlaporanrepair' => null,
                    'statusorderasman' => 0,
                    'penyeliasetujulaporanrepairfk' => null,
                    'tglsetujupenyelialaporanrepair' => null,
                    'statusorderpenyelia' => 1,
                ]);

            $message = 'Penolakan Berhasil';

            DB::commit();

            $regis = DB::table('mitraregistrasidetail_t')->where('norec', $r_tolak['norecregis'])->first();
            $noorderalat = $regis->noorderalat ?? null;
            $alasanpenolakan = $r_tolak['alasanpenolakan'] ?? null;

            $asmanfk = $regis->asmansetujulembarkerjafk ?? null;
            if ($asmanfk) {
                $asman = DB::table('pegawai_m')->where('id', $asmanfk)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';
                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Laporan Repair Telah ditolak Oleh Manager.\n"
                        . "Dengan Keterangan Berikut : *$alasanpenolakan*.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $penyeliateknikfk = $regis->penyeliateknikfk ?? null;
            if ($penyeliateknikfk) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliateknikfk)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Laporan Repair Telah ditolak Oleh Manager.\n"
                        . "Dengan Keterangan Berikut : *$alasanpenolakan*.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $pelaksanateknikfk = $regis->pelaksanateknikfk ?? null;
            if ($pelaksanateknikfk) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanateknikfk)->first();
                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';
                if ($nohpPelaksana) {
                    $pesanPelaksana = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPelaksana\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Laporan Repair Telah ditolak Oleh Manager.\n"
                        . "Dengan Keterangan Berikut : *$alasanpenolakan*.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPelaksana, $pesanPelaksana);
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
            ->where('mtrd.statusamandemen', '!=', 4)
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
                    // Kalibrasi
                    'tglisilembarkerjapelaksana' => null,
                    'pelaksanaisilembarkerjafk' => null,
                    'setujuilembarkerjapenyelia' => null,
                    'tglsetujupenyelialembarkerja' => null,
                    'penyeliasetujulembarkerjafk' => null,
                    'setujuilembarkerjaasman' => null,
                    'tglsetujuasmanlembarkerja' => null,
                    'asmansetujulembarkerjafk' => null,
                    'setujuilembarkerjamanager' => null,
                    'managersetujulembarkerjafk' => null,
                    'tglsetujumanagerlembarkerja' => null,
                    // Repair
                    'pelaksanaisilaporanrepairfk' => null,
                    'tglisilaporanrepairpelaksana' => null,
                    'penyeliasetujulaporanrepairfk' => null,
                    'tglsetujupenyelialaporanrepair' => null,
                    'asmansetujulaporanrepairfk' => null,
                    'tglsetujuasmanlaporanrepair' => null,
                    'managersetujulaporanrepairfk' => null,
                    'tglsetujumanagerlaporanrepair' => null,

                    'statusorderpenyelia' => 1,
                    'statusordermanager' => 0,

                    'amandemenke' => DB::raw('COALESCE(amandemenke, 0) + 1'),
                ]);

            $transMessage = "Simpan Pengajuan Amandemen Sukses";
            DB::commit();

            $detail     = DB::table('mitraregistrasidetail_t')->where('norec', $r['norec'])->first();
            $penyeliaId = $detail->penyeliateknikfk ?? null;
            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            $asmanId = $detail->asmansetujulembarkerjafk ?? $detail->asmansetujulaporanrepairfk ?? null;

            $managerId = $detail->managersetujulembarkerjafk ?? $detail->managersetujulaporanrepairfk ?? null;
            $manager = DB::table('pegawai_m')->where('id', $managerId)->first();
            $namaManager = $manager->namalengkap ?? '-';

            $namaPenyelia = $penyelia->namalengkap ?? '-';
            $waktuPengajuan = now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            $noorderalat = $r['noorderalat'] ?? null;
            if ($r['status'] == 5) {
                $statusapprov = 'Pengajuan Disetujui ✅';
            } else {
                $statusapprov = 'Pengajuan Ditolak ❌';
            }

            if ($asmanId) {
                $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';
                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Alat dengan No Order : *$noorderalat*\n"
                        . "Telah dilakukan Approvel Pengajuan Amandemen oleh Manager : *$namaManager*\n"
                        . "Pada $waktuPengajuan\n"
                        . "Dengan status Pengajuan: $statusapprov\n\n"
                        . "Silahkan cek dan review daftar pengajuan amandemen pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';
                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Alat dengan No Order : *$noorderalat*\n"
                        . "Telah dilakukan Approvel Pengajuan Amandemen oleh Manager : *$namaManager*\n"
                        . "Pada $waktuPengajuan\n"
                        . "Dengan status Pengajuan: $statusapprov\n\n"
                        . "Silahkan cek dan review daftar pengajuan amandemen pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }
            if ($pelaksanaId) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanaId)->first();
                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';
                if ($nohpPelaksana) {
                    $pesanPelaksana = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPelaksana\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "Alat dengan No Order : *$noorderalat*\n"
                        . "Telah dilakukan Approvel Pengajuan Amandemen oleh Manager : *$namaManager*\n"
                        . "Pada $waktuPengajuan\n"
                        . "Dengan status Pengajuan: $statusapprov\n\n"
                        . "Silahkan cek dan review daftar pengajuan amandemen pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPelaksana, $pesanPelaksana);
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
}
