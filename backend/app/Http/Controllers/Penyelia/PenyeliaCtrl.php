<?php

namespace App\Http\Controllers\Penyelia;

use App\Http\Controllers\Controller;
use App\Models\Master\Pegawai;
use App\Services\CalibrationNotesTranslationService;
use App\Traits\ProtectsVerificationExcelPages;
use App\Models\Transaksi\DaftarAlatStandar;
use App\Models\Transaksi\DaftarInstruksiKerja;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\File;
use App\Models\Transaksi\LembarKerja;
use App\Models\Transaksi\LaporanRepair;
use App\Models\Transaksi\LembarKerjaAccellerometer;
use App\Models\Transaksi\LembarKerjaBoreGauge;
use App\Models\Transaksi\LembarKerjaCaliperMicrometer;
use App\Models\Transaksi\LembarKerjaClamp;
use App\Models\Transaksi\LembarKerjaClampMeter;
use App\Models\Transaksi\LembarKerjaClampMeterSumber;
use App\Models\Transaksi\LembarKerjaContinuitySource;
use App\Models\Transaksi\LembarKerjaDialIndicator;
use App\Models\Transaksi\LembarKerjaDryBlock;
use App\Models\Transaksi\LembarKerjaInsulationMeterResistansi;
use App\Models\Transaksi\LembarKerjaInsulationSource;
use App\Models\Transaksi\LembarKerjaInsulationTester;
use App\Models\Transaksi\LembarKerjaMeterSumber;
use App\Models\Transaksi\LembarKerjaMicrometerHead;
use App\Models\Transaksi\LembarKerjaOscilloscope;
use App\Models\Transaksi\LembarKerjaOutsideMicrometer;
use App\Models\Transaksi\LembarKerjaSumber;
use App\Models\Transaksi\LembarKerjaTachometer;
use App\Models\Transaksi\LembarKerjaTekanan;
use App\Models\Transaksi\LembarKerjaTemperatureIndicator;
use App\Models\Transaksi\LembarKerjaThermalImager;
use App\Models\Transaksi\LembarKerjaThermocoupleRTDSimulator;
use App\Models\Transaksi\LembarKerjaThermohygrometer;
use App\Models\Transaksi\LembarKerjaThermometerInfrared;
use App\Models\Transaksi\LembarKerjaThicknessGauge;
use App\Models\Transaksi\LembarKerjaVibrationCalibrator;
use App\Models\Transaksi\LembarKerjaVibrationMeter;
use App\Models\Transaksi\LembarKerjaTorqueWrench;
use App\Models\Transaksi\LembarKerjaUltrasonicThickness;
use App\Models\Transaksi\StatusAlatRepair;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Builder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Support\Str;

class PenyeliaCtrl extends Controller
{
    use \App\Traits\HandlesWorksheetAttachments;
    use \App\Traits\UpdatesWorksheetMetadata;
    use Valet;
    use ProtectsVerificationExcelPages;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
        $this->middleware(\App\Http\Middleware\ValidateWorksheetInstructions::class);
    }

    public function getAlatPenyelia(Request $r)
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
                'mtrd.tglisilembarkerjapenyelia',
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
                'mtrd.setujuilembarkerjaasman',
                'mtrd.tglsetujuasmanlembarkerja',
                'mtrd.asmansetujulembarkerjafk',
                'mtrd.statusorderasman',
                'mtrd.tglverifpelaksana',
                'mtrd.noorderalat',
                'mtr.jenisorder',
                'mtrd.pelaksanaisilaporanrepairfk',
                'mtrd.tglisilaporanrepairpelaksana',
                'mtrd.penyeliasetujulaporanrepairfk',
                'mtrd.sublingkupfk',
                'mtrd.statusrepairfk',
                'mtrd.statuskanfk',
                'mtrd.isverifikasi',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.managersetujulaporanrepairfk',
                'mtrd.namafileexcel',
                'mtrd.istolakserti',
                'mtrd.alasanpenolakanserti',
                'mtrd.istolaksertiasman',
                'mtrd.alasanpenolakansertiasman',
                'mtrd.istolaksertimanager',
                'mtrd.alasanpenolakansertimanager',
                'mtrd.istolakrepair',
                'mtrd.alasanpenolakanrepair',
                'mtrd.istolakrepairasman',
                'mtrd.alasanpenolakanrepairasman',
                'mtrd.istolakrepairmanager',
                'mtrd.alasanpenolakanrepairmanager',
                'mtrd.tanggalmulaiestimasi',
                'mtrd.statusamandemen',
                'mtrd.isamandemen',
                'mtrd.pelaksanaveriffk',
                'mtrd.versisertifikat',
                'mtrd.versilaporanrepair',
                'slk.namasublingkup'
            )
            ->where('pg.id', $this->getPegawaiId())
            ->where('mtr.statusorder', 1)
            ->where('mtr.iskaji', true)
            ->where('mtr.statusenabled', true)
            ->where('mtrd.statusenabled', true);

        if (isset($r['dari']) && $r['dari'] != '') {
            $data = $data->where(DB::raw("mtr.tglverifasman::date"), '>=', $r->dari);
        }

        if (isset($r['sampai']) && $r['sampai'] != '') {
            $data = $data->where(DB::raw("mtr.tglverifasman::date"), '<=', $r->sampai);
        }

        if (isset($r['search']) && $r['search'] != '') {
            $searchTerm = '%' . $r['search'] . '%';

            $data = $data->where(function ($query) use ($searchTerm) {
                $query->where('mtrd.noorderalat', 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namamerk, mmp.namamerk)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namatipe, mmp.namatipe)"), 'ilike', $searchTerm)
                    ->orWhere(DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber)"), 'ilike', $searchTerm)
                    ->orWhere('mtr.nopendaftaran', 'ilike', $searchTerm);
            });
        }

        if (isset($r['statusorderpenyelia']) && $r['statusorderpenyelia'] != '') {
            $data = $data->where('mtrd.statusorderpenyelia', '=', $r['statusorderpenyelia']);
        }

        $page = 1;

        if (isset($r['page']) && $r['page'] != '') {
            $page = $r['page'];
        }

        $data = $data->orderByDesc('mtr.tglregistrasi');

        $data = $data->paginate(
            isset($r['limit']) ? $r['limit'] : 10,
            ['*'],
            'page',
            $page
        );

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
            $tglMulaiPenyelia = !empty($item->tglisilembarkerjapelaksana)
                ? Carbon::parse($item->tglisilembarkerjapelaksana)
                : null;

            $tglSelesaiManager = !empty($item->tglsetujumanagerlembarkerja)
                ? Carbon::parse($item->tglsetujumanagerlembarkerja)
                : null;

            $diffInSeconds = $calcWorkingSeconds($tglMulaiPenyelia, $tglSelesaiManager);

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

    public function LayananVerifPenyelia(Request $r)
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
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.noorderalat',
                'mtrd.keterangan',
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
                'lk1.id as lokasirepair',
                'lk1.lokasi as lokasirepair',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtr.jenisorder',
            )
            ->where('pg.id', $this->getPegawaiId())
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_pd']);

        $data = $data->orderByDesc('mtrd.noorderalat')->get();


        $result['length'] = count($data);
        $result['detail'] = $data;
        $result['as'] = '@adit';

        return $this->respond($result);
    }

    public function saveVerif(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['verif'];
            $timestamp = now();

            $detail = DB::table('mitraregistrasidetail_t')
                ->select('tanggalmulai')
                ->where('norec', $VI['norec_detail'])
                ->first();

            if ($detail && empty($detail->tanggalmulai)) {
                DB::table('mitraregistrasidetail_t')
                    ->where('noregistrasifk', $VI['norec'])
                    ->update([
                        'tanggalmulaiestimasi' => null,
                    ]);

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $VI['norec_detail'])
                    ->update([
                        'statusorderpenyelia' => 1,
                        'penyeliaveriffk' => $this->getPegawaiId(),
                        'tglverifpenyelia' => $timestamp,
                        'tanggalmulai' => $timestamp,
                    ]);
            } else {
                DB::table('mitraregistrasidetail_t')
                    ->where('noregistrasifk', $VI['norec'])
                    ->update([
                        'tanggalmulaiestimasi' => null,
                    ]);

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $VI['norec_detail'])
                    ->update([
                        'statusorderpenyelia' => 1,
                        'penyeliaveriffk' => $this->getPegawaiId(),
                        'tglverifpenyelia' => $timestamp,
                    ]);
            }

            $transMessage = "Simpan Verif Sukses";
            DB::commit();

            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $VI['norec'])->first();

            if ($detail) {
                $noorderalat = $detail->noorderalat ?? '-';
                $noregistrasifk = $detail->noregistrasifk ?? null;
                $penyeliaId = $detail->penyeliateknikfk ?? null;
                $waktuVerif = $detail->tglverifpelaksana ?? $timestamp;

                try {
                    $waktuVerifStr = \Carbon\Carbon::parse($waktuVerif)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
                } catch (\Exception $e) {
                    $waktuVerifStr = date('d-m-Y H:i', strtotime($waktuVerif)) . ' WIB';
                }
                $penyelia = null;
                if ($penyeliaId) {
                    $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                }
                $namaPenyelia = $penyelia->namalengkap ?? '-';

                $regis = null;
                if ($noregistrasifk) {
                    $regis = DB::table('mitraregistrasi_t')->where('norec', $noregistrasifk)->first();
                }

                $asmanId = $regis->asmanveriffk ?? null;
                $asman = null;
                if ($asmanId) {
                    $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                }
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';

                $pesan = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah Diverifikasi oleh Pelaksana Kalibrasi : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n"
                    . "Silahkan cek aplikasi untuk proses lebih lanjut.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                if ($nohpAsman) {
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesan);
                }
            }

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

    public function setujuiSertifikat(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['verif'];
            $timestamp = now();

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'setujuilembarkerjapenyelia' => true,
                    'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                    'tglsetujupenyelialembarkerja' => now(),
                    'statusorderpenyelia' => 2,
                    'statusorderasman' => 0,
                ]);

            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $VI['norec'])->first();
            $noorderalat = $detail->noorderalat ?? '-';
            $noregistrasifk = $detail->noregistrasifk ?? null;

            $penyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            $waktuVerif = $detail->tglsetujupenyelialembarkerja ?? $timestamp;
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

            $regis = $noregistrasifk ? DB::table('mitraregistrasi_t')->where('norec', $noregistrasifk)->first() : null;
            $asmanId = $regis->asmanveriffk ?? null;
            if ($asmanId) {
                $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';

                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *$noorderalat*\n"
                        . "Telah dilakukan persetujuan Draft Sertifikat Kalibrasi oleh Penyelia : *$namaPenyelia*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            if ($pelaksanaId) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanaId)->first();
                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';

                if ($nohpPelaksana) {
                    $pesanPelaksana = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPelaksana\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *$noorderalat*\n"
                        . "Telah dilakukan persetujuan Draft Sertifikat Kalibrasi oleh Penyelia : *$namaPenyelia*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpPelaksana, $pesanPelaksana);
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
                'pg5.id as penyeliasetujuilembarkerjafk',
                'pg5.namalengkap as penyeliasetujuilembarkerja',
                'pg6.id as asmansetujuilembarkerjafk',
                'pg6.namalengkap as asmansetujuilembarkerja',
                'mtrd.setujuilembarkerjamanager',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.managersetujulembarkerjafk',
                'mtrd.tglisilaporanrepairpelaksana',
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

    public function getLembarKerjaPenyelia(Request $request)
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

    public function downloadTemplateLembarKerjaPenyelia(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja.xlsx';
        $name = 'Template Lembar Kerja.xlsx';
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

    public function downloadTemplateLembarKerjaMeter(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_meter.xlsx';
        $name = 'Template_Lembar_Kerja_Meter.xlsx';
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

    public function downloadTemplateLembarKerjaClamp(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_clamp.xlsx';
        $name = 'Template_Lembar_Kerja_Clamp.xlsx';
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

    public function downloadTemplateLembarKerjaSumber(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_sumber.xlsx';
        $name = 'Template_Lembar_Kerja_Sumber.xlsx';
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

    public function downloadTemplateLembarKerjaVibrationMeter(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_vibration_meter.xlsx';
        $name = 'Template_Lembar_Kerja_Vibration_Meter.xlsx';
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

    public function downloadTemplateLembarKerjaVibrationCalibration(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_vibration_calibrator.xlsx';
        $name = 'Template_Lembar_Kerja_Vibration_Calibraator.xlsx';
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

    public function downloadTemplateLembarKerjaAccellerometer(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_accellerometer.xlsx';
        $name = 'Template_Lembar_Kerja_Accellerometer.xlsx';
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

    public function downloadTemplateLembarKerjaTekanan(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_tekanan.xlsx';
        $name = 'Template_Lembar_Kerja_Tekanan.xlsx';
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

    public function downloadTemplateLembarKerjaClampMeter(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_clamp_meter.xlsx';
        $name = 'Template_Lembar_Kerja_Clamp_Meter.xlsx';
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

    public function downloadTemplateLembarKerjaMeterSumber(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_meter_sumber.xlsx';
        $name = 'Template_Lembar_Kerja_Meter_Sumber.xlsx';
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

    public function downloadTemplateLembarKerjaClampMeterSumber(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_clamp_meter_sumber.xlsx';
        $name = 'Template_Lembar_Kerja_Clamp_Meter_Sumber.xlsx';
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

    public function downloadTemplateLembarKerjaThermometerInfrared(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_termometer_infrared.xlsx';
        $name = 'Template_Lembar_Kerja_Thermometer_Infrared.xlsx';
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

    public function downloadTemplateLembarKerjaThermalImager(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_thermal_imager.xlsx';
        $name = 'Template_Lembar_Kerja_Thermal_Imager.xlsx';
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

    public function downloadTemplateLembarKerjaThermohygrometer(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_thermohygrometer.xlsx';
        $name = 'Template_Lembar_Kerja_Thermohygrometer.xlsx';
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

    public function downloadTemplateLembarKerjaDryblock(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_dryblock.xlsx';
        $name = 'Template_Lembar_Kerja_Dryblock.xlsx';
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

    public function downloadTemplateLembarKerjaCaliperMicrometer(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_caliper_micrometer.xlsx';
        $name = 'Template_Lembar_Kerja_Caliper_Micrometer.xlsx';
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

    public function downloadTemplateLembarKerjaMicrometerHead(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_micrometer_head.xlsx';
        $name = 'Template_Lembar_Kerja_Micrometer_Head.xlsx';
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

    public function downloadTemplateLembarKerjaOutsideMicrometer(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_outside_micrometer.xlsx';
        $name = 'Template_Lembar_Kerja_Outside_Micrometer.xlsx';
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

    public function downloadTemplateLembarKerjaOscilloscope(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_oscilloscope.xlsx';
        $name = 'Template_Lembar_Kerja_Oscilloscope.xlsx';
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

    public function downloadTemplateLembarKerjaDialIndicator(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_dial_indicator.xlsx';
        $name = 'Template_Lembar_Kerja_Dial_Indicator.xlsx';
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

    public function downloadTemplateLembarKerjaBoreGauge(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_bore_gauge.xlsx';
        $name = 'Template_Lembar_Kerja_Bore_Gauge.xlsx';
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

    public function downloadTemplateLembarKerjaContinuitySource(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_continuity_source.xlsx';
        $name = 'Template_Lembar_Kerja_Continuity_Source.xlsx';
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

    public function downloadTemplateLembarKerjaInsulationSource(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_insulation_source.xlsx';
        $name = 'Template_Lembar_Kerja_Insulation_Source.xlsx';
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

    public function downloadTemplateLembarKerjaThermocoupleRTDSimulator(Request $request)
    {
        $pathbundle = 'import/lembarkerja/template_lembar_kerja_thermocouple_rtd_simulator.xlsx';
        $name = 'Template Lembar Kerja Thermocouple RTD Simulator.xlsx';
        $path = public_path($pathbundle);

        if (File::exists($path)) {
            $file = File::get($path);
            $type = File::mimeType($path);
            $response = response()->make($file, 200);
            $response->header("Content-Type", $type)
                ->header('Content-disposition', 'attachment; filename="' . $name . '"');
            return $response;
        }

        return '
            <script language="javascript">
                window.alert("Tidak ada data.");
                window.close()
            </script>';
    }

    public function downloadTemplateLembarKerjaTachometer(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_tachometer.xlsx';
        $name = 'Template Lembar Kerja Tachometer.xlsx';
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

    public function downloadTemplateLembarKerjaInsulationMeterResistansi(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_insulation_meter_resistansi.xlsx';
        $name = 'Template Lembar Kerja Insulation Meter Resistansi.xlsx';
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


    public function downloadTemplateLembarKerjaInsulationTester(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_insulation_tester.xlsx';
        $name = 'Template Lembar Kerja Insulation Tester.xlsx';
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

    public function downloadTemplateLembarKerjaTorqueWrench(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_torque_wrench.xlsx';
        $name = 'Template Lembar Kerja Torque Wrench.xlsx';
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

    public function downloadTemplateLembarKerjaTemperatureIndicator(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_temperature_indicator.xlsx';
        $name = 'Template Lembar Kerja Temperature Indicator.xlsx';
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

    public function downloadTemplateLembarKerjaUltrasonicThickness(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_ultrasonic_thickness.xlsx';
        $name = 'Template Lembar Kerja Ultrasonic Thickness.xlsx';
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

    public function downloadTemplateLembarKerjaThicknessGauge(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_thickness_gauge.xlsx';
        $name = 'Template Lembar Kerja Thickness Gauge.xlsx';
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

    public function downloadTemplateLembarKerjaFeelerGauge(Request $request)
    {

        $pathbundle = 'import/lembarkerja/template_lembar_kerja_feeler_gauge.xlsx';
        $name = 'Template Lembar Kerja Feeler Gauge.xlsx';
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

    public function editLembaKerja(Request $r)
    {
        DB::beginTransaction();
        try {
            $rows = $r->input('data', []);
            foreach ($rows as $row) {

                if ($r['sublingkupfk'] == null) {
                    DB::table('lembarkerjatekanan_t')
                        ->where('norec', $row['norec'])
                        ->update([
                            'tekanan_pada_standard'    => $row['tekanan_pada_standard'],
                            'tekanan_pada_uut'         => $row['tekanan_pada_uut'],
                            'koreksi'                  => $row['koreksi'],
                            'ketidakpastian'           => $row['ketidakpastian'],
                            'updated_at'               => Carbon::now(),
                            'pengisifk'                => $this->getPegawaiId()
                        ]);
                } else {
                    if ($r['sublingkupfk'] == 1) {
                        DB::table('lembarkerja_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'penunjukan_standar_2'     => $row['penunjukan_standar_2'] ?? null,
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 5) {
                        DB::table('lembarkerjaaccellerometer_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'frekuensi'                => $row['frekuensi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'uut'                      => $row['uut'] ?? null,
                                'vibrasi_reference'        => $row['vibrasi_reference'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 6) {
                        DB::table('lembarkerjaclampmeter_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penyetelan'               => $row['penyetelan'],
                                'keluaran'                 => $row['keluaran'] ?? null,
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'penunjukan_standar_2'     => $row['penunjukan_standar_2'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 2) {
                        DB::table('lembarkerjaclamp_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penyetelan'               => $row['penyetelan'],
                                'keluaran'                 => $row['keluaran'] ?? null,
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 11) {
                        DB::table('lembarkerjadryblock_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'set_poin'                 => $row['set_poin'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'] ?? null,
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'kedalaman_pencelupan'     => $row['kedalaman_pencelupan'],
                                'perbedaan_suhu'           => $row['perbedaan_suhu'],
                                'keseragaman_suhu'         => $row['keseragaman_suhu'],
                                'kestabilan_suhu'          => $row['kestabilan_suhu'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 7) {
                        DB::table('lembarkerjametersumber_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'pembacaan_standar'        => $row['pembacaan_standar'],
                                'pembacaan_standar_2'      => $row['pembacaan_standar_2'] ?? null,
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'penunjukan_standar_2'     => $row['penunjukan_standar_2'],
                                'penunjukan_alat'          => $row['penunjukan_alat'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 3) {
                        DB::table('lembarkerjasumber_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'pembacaan_standar'        => $row['pembacaan_standar'],
                                'pembacaan_standar_2'      => $row['pembacaan_standar_2'] ?? null,
                                'penunjukan_alat'          => $row['penunjukan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 8) {
                        DB::table('lembarkerjathermometerinfrared_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 9) {
                        DB::table('lembarkerjathermalimager_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'set_poin'                 => $row['set_poin'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 10) {
                        DB::table('lembarkerjathermohygrometer_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'set_poin'                 => $row['set_poin'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 4) {
                        DB::table('lembarkerjavibration_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'frekuensi'                => $row['frekuensi'],
                                'vibrasi_reference'        => $row['vibrasi_reference'],
                                'uut'                      => $row['uut'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 12) {
                        DB::table('lembarkerjavibrationcalibrator_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'frekuensi'                => $row['frekuensi'],
                                'vibrasi_reference'        => $row['vibrasi_reference'],
                                'uut'                      => $row['uut'],
                                'sensitivity_uut'          => $row['sensitivity_uut'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 13) {
                        DB::table('lembarkerjacalipermicrometer_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 14) {
                        DB::table('lembarkerjadialindicator_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'penyimpangan_naik'        => $row['penyimpangan_naik'],
                                'penyimpangan_turun'       => $row['penyimpangan_turun'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'retrace_error'            => $row['retrace_error'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 15) {
                        DB::table('lembarkerjaboregauge_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'koreksi'                  => $row['koreksi'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 16) {
                        DB::table('lembarkerjaclampmetersumber_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'pembacaan_standar'        => $row['pembacaan_standar'],
                                'pembacaan_standar_2'      => $row['pembacaan_standar_2'] ?? null,
                                'penyetelan_sumber'        => $row['penyetelan_sumber'] ?? null,
                                'penyetelan_sumber_satuan' => $row['penyetelan_sumber_satuan'] ?? null,
                                'penyetelan_sumber_2'      => $row['penyetelan_sumber_2'] ?? null,
                                'penyetelan_sumber_satuan_2' => $row['penyetelan_sumber_satuan_2'] ?? null,
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'penunjukan_standar_2'     => $row['penunjukan_standar_2'],
                                'penunjukan_alat'          => $row['penunjukan_alat'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 18) {
                        DB::table('lembarkerjacontinuitysource_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'penunjukan_uuc'           => $row['penunjukan_uuc'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'pembacaan_standar'        => $row['pembacaan_standar'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 19) {
                        DB::table('lembarkerjatachometer_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 20) {
                        DB::table('lembarkerjainsulationmeterresistansi_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'penunjukan_standar_2'     => $row['penunjukan_standar_2'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 21) {
                        DB::table('lembarkerjatorquewrench_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'set_poin'                 => $row['set_poin'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 22) {
                        DB::table('lembarkerjatemperatureindicator_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'set_poin'                 => $row['set_poin'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 23) {
                        DB::table('lembarkerjaultrasonicthickness_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 24) {
                        DB::table('lembarkerjathicknessgauge_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 33) {
                        DB::table('lembarkerjafeelergauge_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 25) {
                        DB::table('lembarkerjainsulationtester_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 26) {
                        DB::table('lembarkerjainsulationsource_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'] ?? null,
                                'penunjukan_standar'       => $row['penunjukan_standar'] ?? null,
                                'penunjukan_uuc'           => $row['penunjukan_uuc'] ?? null,
                                'pembacaan_alat'           => $row['pembacaan_alat'] ?? null,
                                'pembacaan_standar'        => $row['pembacaan_standar'] ?? null,
                                'koreksi'                  => $row['koreksi'] ?? null,
                                'ketidakpastian'           => $row['ketidakpastian'] ?? null,
                                'ketidakpastian_satuan'    => $row['ketidakpastian_satuan'] ?? null,
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 27) {
                        DB::table('lembarkerjamicrometerhead_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 28) {
                        DB::table('lembarkerjaoutsidemicrometer_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'rentang'                  => $row['rentang'],
                                'penunjukan_standar'       => $row['penunjukan_standar'],
                                'pembacaan_alat'           => $row['pembacaan_alat'],
                                'koreksi'                  => $row['koreksi'],
                                'ketidakpastian'           => $row['ketidakpastian'],
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    } elseif ($r['sublingkupfk'] == 29) {
                        DB::table('lembarkerjaoscilloscope_t')
                            ->where('norec', $row['norec'])
                            ->update([
                                'group'                    => $row['group'] ?? null,
                                'skala_instrumen'          => $row['skala_instrumen'] ?? null,
                                'skala_instrumen_satuan'   => $row['skala_instrumen_satuan'] ?? null,
                                'indikasi_standar'         => $row['indikasi_standar'] ?? null,
                                'indikasi_standar_satuan'  => $row['indikasi_standar_satuan'] ?? null,
                                'indikasi_instrumen'       => $row['indikasi_instrumen'] ?? null,
                                'indikasi_instrumen_satuan' => $row['indikasi_instrumen_satuan'] ?? null,
                                'koreksi'                  => $row['koreksi'] ?? null,
                                'koreksi_satuan'           => $row['koreksi_satuan'] ?? null,
                                'ketidakpastian'           => $row['ketidakpastian'] ?? null,
                                'ketidakpastian_standar'   => $row['ketidakpastian_standar'] ?? null,
                                'updated_at'               => Carbon::now(),
                                'pengisifk'                => $this->getPegawaiId()
                            ]);
                    }
                }
            }

            $transMessage = "Edit Lembar Kerja Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@adit',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Edit Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function simpanUploadLembaKerjaAI(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'pelaksanaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapelaksana' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'gambarsuhu' => $filename,
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolakserti' => null,
                        'alasanpenolakanserti' => null,
                        'tgltolakserti' => null,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];

            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = null;
        if ($norecReg) {
            $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
        }

        $username = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);
        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPelaksana = '-';
        $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $detail1 = $details->first();
        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $nohpPenyelia = $penyelia->nohandphone ?? null;
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            if ($nohpPenyelia) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyelia\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaPenyelia(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'gambarsuhu' => $filename,
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerja::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerja();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->penunjukan_standar_2 = $item['penunjukan_standar_2'];
                $data1->penunjukan_standar_satuan_2 = $item['penunjukan_standar_satuan_2'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaSumber(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaSumber::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaSumber();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->pembacaan_standar = $item['pembacaan_standar'];
                $data1->pembacaan_standar_satuan = $item['pembacaan_standar_satuan'];
                $data1->pembacaan_standar_2 = $item['pembacaan_standar_2'];
                $data1->pembacaan_standar_satuan_2 = $item['pembacaan_standar_satuan_2'];
                $data1->penunjukan_alat = $item['penunjukan_alat'];
                $data1->penunjukan_alat_satuan = $item['penunjukan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Sumber";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaClamp(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaClamp::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaClamp();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penyetelan = $item['penyetelan'];
                $data1->penyetelan_satuan = $item['penyetelan_satuan'];
                $data1->keluaran = $item['keluaran'];
                $data1->keluaran_satuan = $item['keluaran_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }
        $transMessage = "Simpan Lembar Kerja Clamp";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaVibrationMeter(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'rangelembarkerja' => $request['range'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaVibrationMeter::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaVibrationMeter();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->frekuensi = $item['frekuensi'];
                $data1->frekuensi_satuan = $item['frekuensi_satuan'];
                $data1->vibrasi_reference = $item['vibrasi_reference'];
                $data1->vibrasi_reference_satuan = $item['vibrasi_reference_satuan'];
                $data1->uut = $item['uut'];
                $data1->uut_satuan = $item['uut_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Vibration Meter";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaVibrationCalibrator(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request->input('daftarinstruksikerja', []);
            $DAS = $request->input('daftarperalatanstandar', []);
            $rows = $request->input('data', []);
            $fileName = $request->input('fileName');
            if (!is_array($rows) || count($rows) === 0) {
                return $this->respond([], 422, 'Data lembar kerja kosong.');
            }

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'pelaksanaisilembarkerjafk'    => $this->getPegawaiId(),
                        'tglisilembarkerjapelaksana'   => now(),
                        'tglkalibrasilembarkerja'      => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja'   => $request['tempatKalibrasi'],
                        'suhulembarkerja'              => $request['suhu'],
                        'rangelembarkerja'             => $request['range'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk'                 => $request['sublingkupfk'],
                        'nosertifikat'                 => $nosertifikat,
                        'statusorderpelaksana'         => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                if (!isset($instruksi['instruksikerja'])) continue;
                $m = new DaftarInstruksiKerja;
                $m->norec = $m->generateNewId();
                $m->statusenabled = true;
                $m->idalatinstruksikerja = $instruksi['instruksikerja'];
                $m->detailregistrasifk = $request['norec_detail'];
                $m->petugas = $this->getPegawaiId();
                $m->save();
            }

            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                if (!isset($standar['peralatanstandar'])) continue;
                $m = new DaftarAlatStandar;
                $m->norec = $m->generateNewId();
                $m->statusenabled = true;
                $m->alatstandarfk = $standar['peralatanstandar'];
                $m->detailregistrasifk = $request['norec_detail'];
                $m->petugas = $this->getPegawaiId();
                $m->save();
            }

            LembarKerjaVibrationCalibrator::where('detailregistraifk', $request['norec_detail'])->delete();

            $result = [];
            foreach ($rows as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaVibrationCalibrator();
                $data1->norec                      = $data1->generateNewId();
                $data1->statusenabled              = true;
                $data1->detailregistraifk          = $request['norec_detail'];
                $data1->group                      = $item['group'] ?? null;
                $data1->frekuensi                  = $item['frekuensi'] ?? null;
                $data1->frekuensi_satuan           = $item['frekuensi_satuan'] ?? null;
                $data1->vibrasi_reference          = $item['vibrasi_reference'] ?? null;
                $data1->vibrasi_reference_satuan   = $item['vibrasi_reference_satuan'] ?? null;
                if (isset($item['sensitivity_uut'])) {
                    $data1->sensitivity_uut               = $item['sensitivity_uut'];
                    $data1->sensitivity_uut_satuan        = $item['sensitivity_uut_satuan'];
                    $data1->jenis                         = 'accelometer';
                } else {
                    $data1->uut                           = $item['uut'];
                    $data1->uut_satuan                    = $item['uut_satuan'];
                    $data1->jenis                         = 'vibration';
                }
                $data1->koreksi                    = $item['koreksi'] ?? null;
                $data1->koreksi_satuan             = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian             = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_satuan      = $item['ketidakpastian_satuan'] ?? null;
                $data1->excelfilename              = $fileName ?? null;
                $data1->no                         = $item['no'] ?? null;
                $data1->tglupload                  = now();
                $data1->pengisifk                  = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }

            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = $norecReg ? DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first() : null;

        $username  = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token     = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link      = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;

        $shortLink = $this->shortLink($link);
        $detail1   = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        // Asman & Penyelia (tetap)
        $namaPelaksana = optional(DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first())->namalengkap ?? '-';
        if (($regis->asmanveriffk ?? null)) {
            $asman = DB::table('pegawai_m')->where('id', $regis->asmanveriffk)->first();
            if ($asman && $asman->nohandphone) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. " . ($asman->namalengkap ?? '-') . "\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($asman->nohandphone, $pesanAsman);
            }
        }

        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            if ($penyelia && $penyelia->nohandphone) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. " . ($penyelia->namalengkap ?? '-') . "\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($penyelia->nohandphone, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja Vibration Calibrator  ";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaAccellerometer(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'rangelembarkerja' => $request['range'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaAccellerometer::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                $data1 = new LembarKerjaAccellerometer();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->frekuensi = $item['frekuensi'];
                $data1->frekuensi_satuan = $item['frekuensi_satuan'];
                $data1->vibrasi_reference = $item['vibrasi_reference'];
                $data1->vibrasi_reference_satuan = $item['vibrasi_reference_satuan'];
                $data1->uut = $item['uut'];
                $data1->uut_satuan = $item['uut_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Accellerometer";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaTekanan(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'rangelembarkerja' => $request['range'],
                        'mounting' => $request['mounting'],
                        'mediakalibrasi' => $request['mediakalibrasi'],
                        'tekananruang' => $request['tekananruang'],
                        'gravitasi' => $request['gravitasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            LembarKerjaTekanan::where('detailregistraifk', $request['norec_detail'])->delete();
            $result = [];
            foreach ($request['data'] as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaTekanan();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = isset($item['group']) && $item['group'] !== '' ? $item['group'] : null;
                $data1->tekanan_pada_standard = $item['tekanan_pada_standard'] ?? null;
                $data1->tekanan_pada_standard_satuan = $item['tekanan_pada_standard_satuan'] ?? null;
                $data1->tekanan_pada_uut = $item['tekanan_pada_uut'] ?? null;
                $data1->tekanan_pada_uut_satuan = $item['tekanan_pada_uut_satuan'] ?? null;
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;

                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'] ?? null;
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->respond(
                [],
                400,
                'Simpan Lembar Kerja Tekanan Gagal: ' . $e->getMessage()
            );
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Tekanan";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaClampMeter(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaClampMeter::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }

                $data1 = new LembarKerjaClampMeter();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'] ?? null;
                $data1->rentang_satuan = $item['rentang_satuan'] ?? null;
                if (isset($item['penyetelan'])) {
                    $data1->penyetelan = $item['penyetelan'] ?? null;
                    $data1->penyetelan_satuan = $item['penyetelan_satuan'] ?? null;
                    $data1->keluaran = $item['keluaran'] ?? null;
                    $data1->keluaran_satuan = $item['keluaran_satuan'] ?? null;
                } else {
                    $data1->penunjukan_standar = $item['penunjukan_standar'] ?? null;
                    $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'] ?? null;
                    $data1->penunjukan_standar_2 = $item['penunjukan_standar_2'] ?? null;
                    $data1->penunjukan_standar_satuan_2 = $item['penunjukan_standar_satuan_2'] ?? null;
                }
                $data1->jenis = isset($item['penyetelan']) ? 'clamp' : 'meter';
                $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();

                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Clamp & Meter  ";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaMeterSumber(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaMeterSumber::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }

                $data1 = new LembarKerjaMeterSumber();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'] ?? null;
                $data1->rentang_satuan = $item['rentang_satuan'] ?? null;
                if (isset($item['penunjukan_standar'])) {
                    $data1->penunjukan_standar = $item['penunjukan_standar'] ?? null;
                    $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'] ?? null;
                    $data1->penunjukan_standar_2 = $item['penunjukan_standar_2'] ?? null;
                    $data1->penunjukan_standar_satuan_2 = $item['penunjukan_standar_satuan_2'] ?? null;
                    $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                    $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                } else {
                    $data1->pembacaan_standar = $item['pembacaan_standar'] ?? null;
                    $data1->pembacaan_standar_satuan = $item['pembacaan_standar_satuan'] ?? null;
                    $data1->pembacaan_standar_2 = $item['pembacaan_standar_2'] ?? null;
                    $data1->pembacaan_standar_satuan_2 = $item['pembacaan_standar_satuan_2'] ?? null;
                    $data1->penunjukan_alat = $item['penunjukan_alat'] ?? null;
                    $data1->penunjukan_alat_satuan = $item['penunjukan_alat_satuan'] ?? null;
                }
                $data1->jenis = isset($item['penunjukan_standar']) ? 'meter' : 'sumber';
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();

                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Meter & Sumber ";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaClampMeterSumber(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaClampMeterSumber::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }

                $data1 = new LembarKerjaClampMeterSumber();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'] ?? null;
                $data1->rentang_satuan = $item['rentang_satuan'] ?? null;
                if (isset($item['penunjukan_standar'])) {
                    $data1->penunjukan_standar = $item['penunjukan_standar'] ?? null;
                    $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'] ?? null;
                    $data1->penunjukan_standar_2 = $item['penunjukan_standar_2'] ?? null;
                    $data1->penunjukan_standar_satuan_2 = $item['penunjukan_standar_satuan_2'] ?? null;
                    $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                    $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                } elseif (isset($item['penyetelan_sumber'])) {
                    $data1->penyetelan_sumber = $item['penyetelan_sumber'] ?? null;
                    $data1->penyetelan_sumber_satuan = $item['penyetelan_sumber_satuan'] ?? null;
                    $data1->penyetelan_sumber_2 = $item['penyetelan_sumber_2'] ?? null;
                    $data1->penyetelan_sumber_satuan_2 = $item['penyetelan_sumber_satuan_2'] ?? null;
                    $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                    $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                } else {
                    $data1->pembacaan_standar = $item['pembacaan_standar'] ?? null;
                    $data1->pembacaan_standar_satuan = $item['pembacaan_standar_satuan'] ?? null;
                    $data1->pembacaan_standar_2 = $item['pembacaan_standar_2'] ?? null;
                    $data1->pembacaan_standar_satuan_2 = $item['pembacaan_standar_satuan_2'] ?? null;
                    $data1->penunjukan_alat = $item['penunjukan_alat'] ?? null;
                    $data1->penunjukan_alat_satuan = $item['penunjukan_alat_satuan'] ?? null;
                }
                if (isset($item['penyetelan_sumber'])) {
                    $data1->jenis = 'clamp';
                } elseif (isset($item['penunjukan_standar'])) {
                    $data1->jenis = 'meter';
                } else {
                    $data1->jenis = 'sumber';
                }
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();

                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = null;
        if ($norecReg) {
            $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
        }

        $username = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);
        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPelaksana = '-';
        $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $detail1 = $details->first();
        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $nohpPenyelia = $penyelia->nohandphone ?? null;
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            if ($nohpPenyelia) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyelia\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja Meter & Sumber ";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaThermometerInfrared(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'rentangukursuhu' => $request['rentangukursuhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'noteslembarkerja' => $request['notes'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'jarakKalibrasi' => $request['jarakKalibrasi'],
                        'emisivitas' => $request['emisivitas'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaThermometerInfrared::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaThermometerInfrared();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Thermometer Infrared";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaThermalImager(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'rentangukursuhu' => $request['rentangukursuhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'noteslembarkerja' => $request['notes'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'jarakKalibrasi' => $request['jarakKalibrasi'],
                        'emisivitas' => $request['emisivitas'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaThermalImager::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaThermalImager();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->set_poin = $item['set_poin'];
                $data1->set_poin_satuan = $item['set_poin_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Thermal Imager";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaThermohygrometer(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'resolusipersen' => $request['resolusipersen'],
                        'rentangukursuhu' => $request['rentangukursuhu'],
                        'rentangukursuhupersen' => $request['rentangukursuhupersen'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'kondisipengukuransuhu' => $request['kondisipengukuransuhu'],
                        'kondisipengukurankelembaban' => $request['kondisipengukurankelembaban'],
                        'noteslembarkerja' => $request['notes'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaThermohygrometer::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaThermohygrometer();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->set_poin = $item['set_poin'];
                $data1->set_poin_satuan = $item['set_poin_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Thermohygrometer";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaDryblock(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileSuhu')) {
                    $file = $request->file('fileSuhu');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'resolusi' => $request['resolusi'],
                        'rentangukursuhu' => $request['rentangukursuhu'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'kedalamalubang' => $request['kedalamalubang'],
                        'lubangacuan' => $request['lubangacuan'],
                        'lubang1' => $request['lubang1'],
                        'lubang2' => $request['lubang2'],
                        'noteslembarkerja' => $request['notes'],
                        'gambarsuhu' => $filename,
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaDryBlock::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                $data1 = new LembarKerjaDryBlock();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->set_poin = $item['set_poin'] ?? null;
                $data1->set_poin_satuan = $item['set_poin_satuan'] ?? null;
                $data1->penunjukan_standar = $item['penunjukan_standar'] ?? null;
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'] ?? null;
                if (isset($item['pembacaan_alat'])) {
                    $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                    $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                    $data1->koreksi = $item['koreksi'] ?? null;
                    $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                    $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                    $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                } else {
                    $data1->kedalaman_pencelupan = $item['kedalaman_pencelupan'] ?? null;
                    $data1->kedalaman_pencelupan_satuan = $item['kedalaman_pencelupan_satuan'] ?? null;
                    $data1->perbedaan_suhu = $item['perbedaan_suhu'] ?? null;
                    $data1->perbedaan_suhu_satuan = $item['perbedaan_suhu_satuan'] ?? null;
                    $data1->keseragaman_suhu = $item['keseragaman_suhu'] ?? null;
                    $data1->keseragaman_suhu_satuan = $item['keseragaman_suhu_satuan'] ?? null;
                    $data1->kestabilan_suhu = $item['kestabilan_suhu'] ?? null;
                    $data1->kestabilan_suhu_satuan = $item['kestabilan_suhu_satuan'] ?? null;
                }
                $data1->jenis = isset($item['pembacaan_alat']) ? 'kalibrasi' : 'karakteristik';
                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();

                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Dryblock ";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaCaliperMicrometer(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'rangelembarkerja' => $request['range'],
                        'rangelembarkerjasatuan' => $request['range_satuan'],
                        'resolusilembarkerjasatuan' => $request['resolusi_satuan'],
                        'kesejajaranluar' => $request['kesejajaranluar'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'noteslembarkerja' => $request['notes'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaCaliperMicrometer::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaCaliperMicrometer();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = null;
        if ($norecReg) {
            $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
        }

        $username = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);
        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPelaksana = '-';
        $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $detail1 = $details->first();
        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $nohpPenyelia = $penyelia->nohandphone ?? null;
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            if ($nohpPenyelia) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyelia\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaMicrometerHead(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'rangelembarkerja' => $request['range'],
                        'rangelembarkerjasatuan' => $request['range_satuan'],
                        'resolusilembarkerjasatuan' => $request['resolusi_satuan'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'noteslembarkerja' => $request['notes'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaMicrometerHead::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaMicrometerHead();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = null;
        if ($norecReg) {
            $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
        }

        $username = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);
        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPelaksana = '-';
        $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $detail1 = $details->first();
        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $nohpPenyelia = $penyelia->nohandphone ?? null;
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            if ($nohpPenyelia) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyelia\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja Micrometer Head";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaOutsideMicrometer(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'rangelembarkerja' => $request['range'],
                        'rangelembarkerjasatuan' => $request['range_satuan'],
                        'resolusilembarkerjasatuan' => $request['resolusi_satuan'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'noteslembarkerja' => $request['notes'],
                        'kesejajaran' => $request['kesejajaran'],
                        'kerataan' => $request['kerataan'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaOutsideMicrometer::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaOutsideMicrometer();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = null;
        if ($norecReg) {
            $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
        }

        $username = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);
        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPelaksana = '-';
        $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $detail1 = $details->first();
        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $nohpPenyelia = $penyelia->nohandphone ?? null;
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            if ($nohpPenyelia) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyelia\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja Outside Micrometer";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaOscilloscope(Request $request)
    {
        DB::beginTransaction();

        $result = [];
        $details = collect();
        $error = null;

        try {
            $decodeInput = function ($value) {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [];
                }

                return is_array($value) ? $value : [];
            };

            $DIK = $decodeInput($request['daftarinstruksikerja'] ?? []);
            $DAS = $decodeInput($request['daftarperalatanstandar'] ?? []);
            $dataExcel = $decodeInput($request['data'] ?? []);
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'noteslembarkerja' => $request['notes'],
                        'sublingkupfk' => $request['sublingkupfk'] ?? 29,
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen' => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $modelInstruksi = new DaftarInstruksiKerja;
                $modelInstruksi->norec = $modelInstruksi->generateNewId();
                $modelInstruksi->statusenabled = true;
                $modelInstruksi->idalatinstruksikerja = $instruksi['instruksikerja'] ?? null;
                $modelInstruksi->detailregistrasifk = $request['norec_detail'];
                $modelInstruksi->petugas = $this->getPegawaiId();
                $modelInstruksi->save();
            }

            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $modelStandar = new DaftarAlatStandar;
                $modelStandar->norec = $modelStandar->generateNewId();
                $modelStandar->statusenabled = true;
                $modelStandar->alatstandarfk = $standar['peralatanstandar'] ?? null;
                $modelStandar->detailregistrasifk = $request['norec_detail'];
                $modelStandar->petugas = $this->getPegawaiId();
                $modelStandar->save();
            }

            LembarKerjaOscilloscope::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (($item['isGroupHeader'] ?? false) === true) {
                    continue;
                }

                $data1 = new LembarKerjaOscilloscope();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'] ?? null;
                $data1->skala_instrumen = $item['skala_instrumen'] ?? null;
                $data1->skala_instrumen_satuan = $item['skala_instrumen_satuan'] ?? null;
                $data1->indikasi_standar = $item['indikasi_standar'] ?? null;
                $data1->indikasi_standar_satuan = $item['indikasi_standar_satuan'] ?? null;
                $data1->indikasi_instrumen = $item['indikasi_instrumen'] ?? null;
                $data1->indikasi_instrumen_satuan = $item['indikasi_instrumen_satuan'] ?? null;
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'] ?? null;
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'] ?? null;
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }

            $transStatus = 'true';
        } catch (Exception $e) {
            $error = $e;
            $transStatus = 'false';
        }

        if ($transStatus != 'false') {
            $norecReg = $request['norec_registrasi'] ?? null;
            if (!$norecReg && isset($request['norec_detail'])) {
                $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
                $norecReg = $detail->noregistrasifk ?? null;
            }

            $regis = $norecReg ? DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first() : null;
            $username = 'Mahzumi';
            $profileId = $regis->kdprofile ?? 1;
            $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
            $link = url('/service/penyelia/cetak-sertifikat-lembar-kerja') . '?pdf=true'
                . '&norec=' . $norecReg
                . '&norec_detail=' . $request['norec_detail']
                . '&user=' . urlencode($username)
                . '&kdprofile=' . $profileId
                . '&token=' . $token;
            $shortLink = $this->shortLink($link);

            $detail1 = $details->first();
            $noorderalat = $detail1->noorderalat ?? '-';
            $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

            try {
                $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
            }

            $asmanId = $regis->asmanveriffk ?? null;
            if ($asmanId) {
                $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                if ($asman && !empty($asman->nohandphone)) {
                    $pesanAsman = "Halo Tim Hebat U-LAB !\n\n"
                        . "Yth. " . ($asman->namalengkap ?? '-') . "\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *$noorderalat*\n"
                        . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat";

                    $this->kirimWhatsappNotifikasi($asman->nohandphone, $pesanAsman);
                }
            }

            DB::commit();
            return $this->respond($result, 201, 'Simpan Lembar Kerja Oscilloscope Berhasil');
        }

        DB::rollBack();
        return $this->respond($result, 400, 'Simpan Lembar Kerja Oscilloscope Gagal' . ($error ? $error->getMessage() : ''));
    }

    public function simpanUploadLembaKerjaDialIndicator(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'resolusilembarkerjasatuan' => $request['resolusi_satuan'],
                        'rangelembarkerja' => $request['range'],
                        'rangelembarkerjasatuan' => $request['range_satuan'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'noteslembarkerja' => $request['notes'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaDialIndicator::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                $jenisPenyimpangan = ($item['jenis_penyimpangan'] ?? $request['jenis_penyimpangan'] ?? 'naik_turun') === 'maju_mundur'
                    ? 'maju_mundur'
                    : 'naik_turun';

                $data1 = new LembarKerjaDialIndicator();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->penyimpangan_naik = $item['penyimpangan_naik'];
                $data1->penyimpangan_naik_satuan = $item['penyimpangan_naik_satuan'];
                $data1->penyimpangan_turun = $item['penyimpangan_turun'];
                $data1->penyimpangan_turun_satuan = $item['penyimpangan_turun_satuan'];
                $data1->retrace_error = $item['retrace_error'];
                $data1->retrace_error_satuan = $item['retrace_error_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'];
                $data1->jenis_penyimpangan = $jenisPenyimpangan;
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = null;
        if ($norecReg) {
            $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
        }

        $username = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);
        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPelaksana = '-';
        $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $detail1 = $details->first();
        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $nohpPenyelia = $penyelia->nohandphone ?? null;
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            if ($nohpPenyelia) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyelia\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaBoreGauge(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'merekdial' => $request['merekdial'],
                        'tipedial' => $request['tipedial'],
                        'sndial' => $request['sndial'],
                        'rangedial' => $request['rangedial'],
                        'resolusidial' => $request['resolusidial'],
                        'noteslembarkerja' => $request['notes'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaBoreGauge::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                $data1 = new LembarKerjaBoreGauge();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
            $norecReg = $detail->noregistrasifk ?? null;
        }
        $regis = null;
        if ($norecReg) {
            $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
        }

        $username = 'Mahzumi';
        $profileId = $regis->kdprofile ?? 1;
        $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
        $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
            . '&norec=' . $norecReg
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);
        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPelaksana = '-';
        $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";
                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $detail1 = $details->first();
        $penyeliaId = $detail1->penyeliateknikfk ?? null;
        if ($penyeliaId) {
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $nohpPenyelia = $penyelia->nohandphone ?? null;
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            if ($nohpPenyelia) {
                $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaPenyelia\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                    . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
            }
        }

        $transMessage = "Simpan Lembar Kerja";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaContinuitySource(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaContinuitySource::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }

                $data1 = new LembarKerjaContinuitySource();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'] ?? null;
                $data1->rentang_satuan = $item['rentang_satuan'] ?? null;
                if (isset($item['penunjukan_standar'])) {
                    $data1->penunjukan_standar = $item['penunjukan_standar'] ?? null;
                    $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'] ?? null;
                    $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                    $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                } else {
                    $data1->penunjukan_uuc = $item['penunjukan_uuc'] ?? null;
                    $data1->penunjukan_uuc_satuan = $item['penunjukan_uuc_satuan'] ?? null;
                    $data1->pembacaan_standar = $item['pembacaan_standar'] ?? null;
                    $data1->pembacaan_standar_satuan = $item['pembacaan_standar_satuan'] ?? null;
                }
                $data1->jenis = isset($item['penunjukan_standar']) ? 'resistance continuity' : 'source';
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();

                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Continuity +Source";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaInsulationSource(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaInsulationSource::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }

                $data1 = new LembarKerjaInsulationSource();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'] ?? null;
                $data1->rentang_satuan = $item['rentang_satuan'] ?? null;
                if (isset($item['penunjukan_standar'])) {
                    $data1->penunjukan_standar = $item['penunjukan_standar'] ?? null;
                    $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'] ?? null;
                    $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                    $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                    $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                    $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                } else {
                    $data1->penunjukan_uuc = $item['penunjukan_uuc'] ?? null;
                    $data1->penunjukan_uuc_satuan = $item['penunjukan_uuc_satuan'] ?? null;
                    $data1->pembacaan_standar = $item['pembacaan_standar'] ?? null;
                    $data1->pembacaan_standar_satuan = $item['pembacaan_standar_satuan'] ?? null;
                    $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                    $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                }
                $data1->jenis = isset($item['penunjukan_standar']) ? 'insulation' : 'source';
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();

                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Insulation Source";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembarKerjaThermocoupleRTDSimulator(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaThermocoupleRTDSimulator::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }

                $data1 = new LembarKerjaThermocoupleRTDSimulator();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;
                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->jenis = $item['jenis'] ?? (isset($item['setpoint']) ? 'setpoint' : 'rentang');
                $data1->setpoint = $item['setpoint'] ?? null;
                $data1->setpoint_satuan = $item['setpoint_satuan'] ?? null;
                $data1->rentang = $item['rentang'] ?? null;
                $data1->rentang_satuan = $item['rentang_satuan'] ?? null;
                $data1->penunjukan_standar = $item['penunjukan_standar'] ?? null;
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'] ?? null;
                $data1->pembacaan_alat = $item['pembacaan_alat'] ?? null;
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'] ?? null;
                $data1->koreksi = $item['koreksi'] ?? null;
                $data1->koreksi_satuan = $item['koreksi_satuan'] ?? null;
                $data1->ketidakpastian = $item['ketidakpastian'] ?? null;
                $data1->ketidakpastian_satuan = $item['ketidakpastian_satuan'] ?? null;
                $data1->excelfilename = $request['fileName'] ?? null;
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();

                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Thermocouple RTD Simulator";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaTachometer(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaTachometer::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaTachometer();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Tachometer";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaInsulationMeterResistansi(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaInsulationMeterResistansi::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaInsulationMeterResistansi();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->penunjukan_standar_2 = $item['penunjukan_standar_2'] ?? null;
                $data1->penunjukan_standar_satuan_2 = $item['penunjukan_standar_satuan_2'] ?? null;
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Insulation Meter Resistansi";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaTorqueWrench(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'rangelembarkerja' => $request['range'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'noteslembarkerja' => $request['notes'],
                        'noteslembarkerja' => $request['notes'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaTorqueWrench::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaTorqueWrench();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->set_poin = $item['set_poin'];
                $data1->set_poin_satuan = $item['set_poin_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Torque Wrench";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaTemperatureIndicator(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = $request['daftarinstruksikerja'];
            $DAS = $request['daftarperalatanstandar'];
            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $request['norec_detail'])
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'resolusi' => $request['resolusi'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'noteslembarkerja' => $request['notes'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'jenissensor' => $request['jenissensor'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaTemperatureIndicator::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($request['data'] as $item) {
                if ($item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaTemperatureIndicator();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->set_poin = $item['set_poin'];
                $data1->set_poin_satuan = $item['set_poin_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Temperature Indicator";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaUltrasonicThickness(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'rangelembarkerja' => $request['range'],
                        'resolusi' => $request['resolusi'],
                        'kecepatan' => $request['kecepatan'],
                        'standarmaterial' => $request['standarmaterial'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaUltrasonicThickness::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaUltrasonicThickness();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Ultrasonic Thickness";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaFeelerGauge(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'rangelembarkerja' => $request['range'],
                        'resolusi' => $request['resolusi'],
                        'kecepatan' => $request['kecepatan'],
                        'standarmaterial' => $request['standarmaterial'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaFeelerGauge::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaFeelerGauge();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Feeler Gauge";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaThicknesGauge(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'rangelembarkerja' => $request['range'],
                        'resolusi' => $request['resolusi'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaThicknessGauge::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaThicknessGauge();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Ultrasonic Thickness";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function simpanUploadLembaKerjaInsulationTester(Request $request)
    {
        DB::beginTransaction();
        try {
            $DIK = json_decode($request['daftarinstruksikerja'], true);
            $DAS = json_decode($request['daftarperalatanstandar'], true);
            $dataExcel = json_decode($request['data'], true);

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $request['norec_detail'])
                ->get();

            foreach ($details as $i => $detail) {
                $dataUpdate = [];
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);

                $filename = null;
                if ($request->hasFile('fileMeter')) {
                    $file = $request->file('fileMeter');
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions)) {
                        throw new \Exception("File harus berupa gambar (jpg, jpeg, png).");
                    }

                    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->move(public_path('gambar-suhu'), $filename);
                } else {
                    $filename = $request['namaFileLama'] ?? null;
                }

                if ($detail->amandemenke !== null) {
                    $amandemenKe = (int) $detail->amandemenke;
                    $nosertifikatDasar = $detail->nosertifikat;
                    if (!$nosertifikatDasar || trim($nosertifikatDasar) === '') {
                        $nosertifikatDasar = $nosertifikat;
                    }
                    if ($amandemenKe <= 1) {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A';
                    } else {
                        $nosertifikatAmandemen = $nosertifikatDasar . 'A' . ($amandemenKe - 1);
                    }
                    $dataUpdate['nosertifikatamandemen'] = $nosertifikatAmandemen;
                }

                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $request['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $request['tempatKalibrasi'],
                        'suhulembarkerja' => $request['suhu'],
                        'kelembabanRelatiflembarkerja' => $request['kelembabanRelatif'],
                        'sublingkupfk' => $request['sublingkupfk'],
                        'nosertifikat' => $nosertifikat,
                        'noteslembarkerja' => $request['notes'],
                        'statusorderpelaksana' => 2,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                        #jika penyelia yang upload sertifikat maka langsung setujui
                        'setujuilembarkerjapenyelia' => true,
                        'penyeliasetujulembarkerjafk' => $this->getPegawaiId(),
                        'tglsetujupenyelialembarkerja' => now(),
                        'statusorderpenyelia' => 2,
                        'statusorderasman' => 0,
                        'nosertifikatamandemen'        => $detail->amandemenke !== null
                            ? ($dataUpdate['nosertifikatamandemen'] ?? null)
                            : $detail->nosertifikatamandemen,
                    ]);
            }

            $dataInstruksi = [];
            DaftarInstruksiKerja::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DIK as $instruksi) {
                $model_Instruksi = new DaftarInstruksiKerja;
                $model_Instruksi->norec = $model_Instruksi->generateNewId();
                $model_Instruksi->statusenabled = true;
                $model_Instruksi->idalatinstruksikerja = $instruksi['instruksikerja'];
                $model_Instruksi->detailregistrasifk = $request['norec_detail'];
                $model_Instruksi->petugas = $this->getPegawaiId();
                $model_Instruksi->save();
                $dataInstruksi[] = $model_Instruksi;
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $request['norec_detail'])->delete();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $model_Standar->norec = $model_Standar->generateNewId();
                $model_Standar->statusenabled = true;
                $model_Standar->alatstandarfk = $standar['peralatanstandar'];
                $model_Standar->detailregistrasifk = $request['norec_detail'];
                $model_Standar->petugas = $this->getPegawaiId();
                $model_Standar->save();
                $dataAlatStandar[] = $model_Standar;
            }

            $result = [];
            LembarKerjaInsulationTester::where('detailregistraifk', $request['norec_detail'])->delete();
            foreach ($dataExcel as $item) {
                if (isset($item['isGroupHeader']) && $item['isGroupHeader'] === true) {
                    continue;
                }
                $data1 = new LembarKerjaInsulationTester();
                $data1->norec = $data1->generateNewId();
                $data1->statusenabled = true;

                $data1->detailregistraifk = $request['norec_detail'];
                $data1->group = $item['group'];
                $data1->rentang = $item['rentang'];
                $data1->rentang_satuan = $item['rentang_satuan'];
                $data1->penunjukan_standar = $item['penunjukan_standar'];
                $data1->penunjukan_standar_satuan = $item['penunjukan_standar_satuan'];
                $data1->pembacaan_alat = $item['pembacaan_alat'];
                $data1->pembacaan_alat_satuan = $item['pembacaan_alat_satuan'];
                $data1->koreksi = $item['koreksi'];
                $data1->koreksi_satuan = $item['koreksi_satuan'];
                $data1->ketidakpastian = $item['ketidakpastian'];
                $data1->ketidakpastian_standar = $item['ketidakpastian_standar'];
                $data1->excelfilename = $request['fileName'];
                $data1->no = $item['no'];
                $data1->tglupload = now();
                $data1->pengisifk = $this->getPegawaiId();
                $data1->save();
                $result[] = $data1;
            }
            $transStatus = 'true';
        } catch (Exception $e) {
            $transStatus = 'false';
        }

        $norecReg = $request['norec_registrasi'] ?? null;
        if (!$norecReg && isset($request['norec_detail'])) {
            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
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
            . '&norec_detail=' . $request['norec_detail']
            . '&user=' . urlencode($username)
            . '&kdprofile=' . $profileId
            . '&token=' . $token;
        $shortLink = $this->shortLink($link);

        $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $request['norec_detail'])->first();
        $noorderalat = $detail1->noorderalat ?? '-';
        $namaPenyelia = '-';
        $pegawaiPenyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
        $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

        try {
            $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
        } catch (\Exception $e) {
            $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
        }

        $asmanId = $regis->asmanveriffk ?? null;
        if ($asmanId) {
            $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
            $nohpAsman = $asman->nohandphone ?? null;
            $namaAsman = $asman->namalengkap ?? '-';

            if ($nohpAsman) {
                $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                    . "Yth. $namaAsman\n"
                    . "Kami informasikan bahwa,\n\n"
                    . "No Order : *$noorderalat*\n"
                    . "Telah dilakukan penyeliaan Draft Sertifikat Kalibrasi oleh Penyelia Teknik : *$namaPenyelia*\n"
                    . "Pada $waktuVerifStr\n\n"
                    . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                    . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.\n\n"
                    . "Salam,\n"
                    . "U-LAB ! Cepat, Tepat, Akurat 💯";

                $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
            }
        }

        $transMessage = "Simpan Lembar Kerja Insulation Tester";
        if ($transStatus != 'false') {
            DB::commit();
            $result = array(
                "status" => 201,
                "message" => $transMessage . ' Berhasil',
                "result" => $result
            );
        } else {
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message" => $transMessage . ' Gagal' . $e->getMessage(),
                "result" => $result
            );
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function detailProdukLembarKerjaPenyelia(Request $r)
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
            ->leftJoin('ruangan_m as rm', DB::raw('CAST("mtrd"."tempatKalibrasilembarkerja" AS INTEGER)'), '=', 'rm.id')
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
                'mtrd.noorderalat',
                'rm.id as idruangan',
                'rm.namaruangan as tempatKalibrasilembarkerja',
                'mtrd.kondisiRuanganlembarkerja',
                'mtrd.suhulembarkerja',
                'mtrd.kelembabanRelatiflembarkerja',
                'mtrd.noteslembarkerja',
                'mtrd.kedalamalubang',
                'mtrd.lubangacuan',
                'mtrd.lubang1',
                'mtrd.lubang2',
                'mtrd.gambarsuhu',
                'mtrd.rangelembarkerja',
                'mtrd.resolusi',
                'mtrd.rentangukursuhu',
                'mtrd.mounting',
                'mtrd.mediakalibrasi',
                'mtrd.tekananruang',
                'mtrd.gravitasi',
                'mtrd.kesejajaranluar',
                'mtrd.kondisipengukuransuhu',
                'mtrd.kondisipengukurankelembaban',
                'mtrd.resolusipersen',
                'mtrd.rentangukursuhupersen',
                'mtrd.jenissensor',
                'mtrd.kecepatan',
                'mtrd.standarmaterial',
                'mtr.catatan',
                DB::raw("COALESCE(mmps.namaalatstandar, mmp.namaproduk) as namaproduk"),
                DB::raw("COALESCE(mmps.namamerk, mmp.namamerk) as namamerk"),
                DB::raw("COALESCE(mmps.namatipe, mmp.namatipe) as namatipe"),
                DB::raw("COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber"),
                DB::raw("COALESCE(mmps.fotoproduk, mmp.fotoproduk) as fotoproduk"),
                'pg.id as penyeliateknikfk',
                'pg.namalengkap as penyeliateknik',
                'pg2.id as pelaksanateknikfk',
                'pg2.namalengkap as pelaksanateknik',
                'pg3.id as asmanfk',
                'pg3.namalengkap as asamanverifikasi',
                'lk.id as lokasikalibrasifk',
                'lk.lokasi',
                'lp.id as lingkupfk',
                'lp.lingkupkalibrasi',
                'mtrd.evalverifikasi',
                'mtrd.catatanverifikasi',
                'mtrd.resolusilembarkerjasatuan',
                'mtrd.rangelembarkerjasatuan',
                'mtrd.kesejajaran',
                'mtrd.kerataan',
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.iskaji', true)
            ->where('mtrd.statusenabled', true)
            ->where('mtrd.norec', $r['norec_pd'])
            ->orderByDesc('mtrd.noorderalat')
            ->get();

        $instruksiKerja = DB::table('daftarinstruksikerja_t as dik')
            ->leftJoin('instruksikerja_m as ik', 'ik.id', '=', 'dik.idalatinstruksikerja')
            ->select('dik.detailregistrasifk', 'ik.id as value', 'ik.namainstruksikerja as label')
            ->where('dik.detailregistrasifk', $r['norec_pd'])
            ->where('dik.statusenabled', true)
            ->where('ik.statusenabled', true)
            ->get();

        foreach ($data as $ds) {
            $ds->daftarinstruksikerja = [];
            foreach ($instruksiKerja as $sd) {
                if ($ds->norec_detail == $sd->detailregistrasifk) {
                    $ds->daftarinstruksikerja[] = $sd;
                }
            }
        }

        $alatstandar = DB::table('daftaralatstandar_t as das')
            ->leftJoin('mapalatstandar_m as pas', 'pas.id', '=', 'das.alatstandarfk')
            ->select(
                'das.detailregistrasifk',
                'pas.id as value',
                'pas.namaalatstandar',
                'pas.namamerk',
                'pas.namatipe',
                'pas.namaserialnumber',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(pas.namaalatstandar, ''), NULLIF(pas.namamerk, ''), NULLIF(pas.namatipe, ''), NULLIF(CONCAT('(', pas.namaserialnumber, ')'), '()'))) as label")
            )
            ->where('das.detailregistrasifk', $r['norec_pd'])
            ->where('das.statusenabled', true)
            ->where('pas.statusenabled', true)
            ->get();

        foreach ($data as $ds) {
            $ds->daftaralatstandar = [];
            foreach ($alatstandar as $sd) {
                if ($ds->norec_detail == $sd->detailregistrasifk) {
                    $ds->daftaralatstandar[] = $sd;
                }
            }
        }

        $result = [
            'length' => count($data),
            'data' => $data,
            'as' => '@adit'
        ];

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
            ->leftJoin('jabatan_m as jb1', 'jb1.id', '=', 'pg.jabatan1fk')
            ->select(
                'mtr.norec',
                'mtrd.norec as norec_detail',
                'mtrd.iskaji',
                'mtrd.keterangan',
                'mtrd.namafile',
                'mtrd.tanggalmulaiestimasi',
                'mtrd.pelaksanateknikfk',
                'mtrd.noorderalat',
                'mtrd.durasikalbrasi',
                'mtrd.namamanager',
                'mtrd.namaasman',
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

        $res['alat'] =  $data = DB::table('mitraregistrasi_t as mtr')
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
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.resolusilembarkerjasatuan',
                        'mtrd.sublingkupfk',
                        'mtrd.kesejajaranluar',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
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
                        'mtrd.kecepatan',
                        'mtrd.standarmaterial',
                        'mtrd.rangelembarkerja',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
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
                        'mtrd.rangelembarkerja',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
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
                        'mtrd.rangelembarkerja',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
                    )
                    ->where('mtrd.norec', $r['norec_detail'])
                    ->where('mtrd.statusenabled', true)
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
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.resolusilembarkerjasatuan',
                        'mtrd.sublingkupfk',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
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
                        'mtrd.rangelembarkerjasatuan',
                        'mtrd.resolusilembarkerjasatuan',
                        'mtrd.sublingkupfk',
                        'mtrd.resolusi',
                        'mtrd.lingkupkalibrasifk',
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
        $res['halamanPertama'] = false;

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

    public function getPegawaiPelaksana(Request $r)
    {
        $jabatanIds = [
            11,
            12,
            13,
            14,
            15,
            16,
            18
        ];

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
            ->where('pg.statuspegawaifk', 1)
            ->whereIn('jb.id', $jabatanIds)
            ->where('pg.lokasikalibrasifk', 1)
            ->where('jb.statusenabled', true);

        if (isset($r['limit']) && $r['limit'] != '') {
            $pegawaiJakarta->limit($r['limit']);
        }

        $pegawaiJakarta->orderBy('jb.namajabatanulab');
        $pegawaiJakarta = $pegawaiJakarta->get();


        $res['pegawaiJakarta'] = $pegawaiJakarta;
        return $this->respond($res);
    }

    private function generateNorecRepair(): string
    {
        return (string) \Illuminate\Support\Str::uuid();
    }

    private function simpanFotoMultipleRepair(Request $request, string $requestPath, string $tableName, string $parentColumn, string $parentNorec, string $detailregistrasifk): ?string
    {
        $firstFileName = null;
        $fotoRows = $request->input($requestPath, []);

        if (!is_array($fotoRows)) {
            $fotoRows = [];
        }

        foreach ($fotoRows as $fotoIndex => $fotoInfo) {
            $fileKey = $requestPath . '.' . $fotoIndex . '.file';

            if (!$request->hasFile($fileKey)) {
                continue;
            }

            $file = $request->file($fileKey);
            $filename = $this->uploadFotoRepairFile($file, $tableName);

            if ($firstFileName === null) {
                $firstFileName = $filename;
            }

            DB::table($tableName)->insert([
                'norec' => $this->generateNorecRepair(),
                'statusenabled' => true,
                $parentColumn => $parentNorec,
                'detailregistrasifk' => $detailregistrasifk,
                'namafile' => $filename,
                'keterangan_gambar' => $fotoInfo['keterangan_gambar'] ?? null,
                'urut' => ((int) $fotoIndex) + 1,
                'petugas' => $this->getPegawaiId(),
                'created_at' => now(),
                'updated_at' => null,
            ]);
        }

        return $firstFileName;
    }

    private function getFotoRepairMap(string $tableName, string $parentColumn, array $parentIds)
    {
        if (count($parentIds) === 0) {
            return collect();
        }

        return DB::table($tableName)
            ->whereIn($parentColumn, $parentIds)
            ->where('statusenabled', true)
            ->orderBy('urut', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->get()
            ->groupBy($parentColumn);
    }

    private function hapusFotoMultipleRepair(string $tableName, string $parentColumn, string $parentNorec): void
    {
        $fotos = DB::table($tableName)
            ->where($parentColumn, $parentNorec)
            ->get();

        foreach ($fotos as $foto) {
            if (!empty($foto->namafile)) {
                $filePath = public_path('berkas-laporan-repair/' . $foto->namafile);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        DB::table($tableName)
            ->where($parentColumn, $parentNorec)
            ->delete();
    }

    private function uploadFotoRepairFile($file, string $prefix = 'repair'): string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception("File harus berupa gambar JPG, JPEG, atau PNG.");
        }

        $safeOriginalName = preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $safeOriginalName = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $safeOriginalName);

        $filename = time() . '_' . $prefix . '_' . uniqid() . '_' . $safeOriginalName;
        $path = public_path('berkas-laporan-repair');

        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }

        $file->move($path, $filename);

        return $filename;
    }

    private function updateCaptionFotoRepair(Request $request, string $fotoTable): void
    {
        $existingFotos = $request->input('existing_fotos', []);

        if (!is_array($existingFotos)) {
            return;
        }

        foreach ($existingFotos as $foto) {
            if (empty($foto['norec'])) {
                continue;
            }

            DB::table($fotoTable)
                ->where('norec', $foto['norec'])
                ->update([
                    'keterangan_gambar' => $foto['keterangan_gambar'] ?? null,
                    'updated_at' => now(),
                ]);
        }
    }

    private function hapusFotoRepairTerpilih(Request $request, string $fotoTable): void
    {
        $hapusFotos = $request->input('hapus_fotos', []);

        if (!is_array($hapusFotos)) {
            return;
        }

        foreach ($hapusFotos as $fotoNorec) {
            if (empty($fotoNorec)) {
                continue;
            }

            $foto = DB::table($fotoTable)
                ->where('norec', $fotoNorec)
                ->first();

            if (!$foto) {
                continue;
            }

            if (!empty($foto->namafile)) {
                $filePath = public_path('berkas-laporan-repair/' . $foto->namafile);

                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            DB::table($fotoTable)
                ->where('norec', $fotoNorec)
                ->delete();
        }
    }

    private function tambahFotoRepairBaru(Request $request, string $fotoTable, string $parentColumn, string $parentNorec, string $detailregistrasifk, string $prefix): ?string
    {
        $firstFileName = null;
        $fotos = $request->input('fotos', []);

        if (!is_array($fotos)) {
            $fotos = [];
        }

        $lastUrut = DB::table($fotoTable)
            ->where($parentColumn, $parentNorec)
            ->max('urut');

        $lastUrut = $lastUrut ? (int) $lastUrut : 0;

        foreach ($fotos as $index => $fotoInfo) {
            $fileKey = 'fotos.' . $index . '.file';

            if (!$request->hasFile($fileKey)) {
                continue;
            }

            $file = $request->file($fileKey);
            $filename = $this->uploadFotoRepairFile($file, $prefix);

            if ($firstFileName === null) {
                $firstFileName = $filename;
            }

            DB::table($fotoTable)->insert([
                'norec' => (string) \Illuminate\Support\Str::uuid(),
                'statusenabled' => true,
                $parentColumn => $parentNorec,
                'detailregistrasifk' => $detailregistrasifk,
                'namafile' => $filename,
                'keterangan_gambar' => $fotoInfo['keterangan_gambar'] ?? null,
                'urut' => $lastUrut + $index + 1,
                'petugas' => $this->getPegawaiId(),
                'created_at' => now(),
                'updated_at' => null,
            ]);
        }

        return $firstFileName;
    }

    private function syncFotoUtamaRepair(string $masterTable, string $masterNorec, string $masterFotoColumn, string $fotoTable, string $parentColumn): void
    {
        $fotoPertama = DB::table($fotoTable)
            ->where($parentColumn, $masterNorec)
            ->where('statusenabled', true)
            ->orderBy('urut', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->first();

        DB::table($masterTable)
            ->where('norec', $masterNorec)
            ->update([
                $masterFotoColumn => $fotoPertama->namafile ?? null,
                'updated_at' => now(),
            ]);
    }

    private function repairRichTextHasContent($value): bool
    {
        if ($value === null) {
            return false;
        }

        $plainText = trim(str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $plainText !== '';
    }

    private function sanitizeRepairRichText($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4>';
        $clean = strip_tags($value, $allowedTags);
        $clean = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $clean);
        $clean = preg_replace('/\s*on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $clean);
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        $clean = trim($clean);

        return $this->repairRichTextHasContent($clean) ? $clean : null;
    }

    public function updateStatusRepair(Request $request)
    {
        DB::beginTransaction();

        try {
            $norec = $request->input('norec');

            if (empty($norec)) {
                throw new \Exception('Norec status repair tidak ditemukan.');
            }

            $status = DB::table('statusalatrepair_t')
                ->where('norec', $norec)
                ->first();

            if (!$status) {
                throw new \Exception('Data status alat repair tidak ditemukan.');
            }

            DB::table('statusalatrepair_t')
                ->where('norec', $norec)
                ->update([
                    'bagianalat' => $this->sanitizeRepairRichText($request->input('bagianalatstatus')),
                    'kondisi' => $this->sanitizeRepairRichText($request->input('kondisistatus')),
                    'updated_at' => now(),
                ]);

            $this->updateCaptionFotoRepair($request, 'statusalatrepair_foto_t');
            $this->hapusFotoRepairTerpilih($request, 'statusalatrepair_foto_t');

            $this->tambahFotoRepairBaru(
                $request,
                'statusalatrepair_foto_t',
                'statusalatrepairfk',
                $norec,
                $status->detailregistrasifk,
                'status_repair'
            );

            $this->syncFotoUtamaRepair(
                'statusalatrepair_t',
                $norec,
                'fotoalatstatus',
                'statusalatrepair_foto_t',
                'statusalatrepairfk'
            );

            DB::commit();

            return $this->respond([
                'as' => '@adit',
            ], 200, 'Update Status Alat Repair Berhasil');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'error' => $e->getMessage(),
            ], 400, 'Update Status Alat Repair Gagal');
        }
    }

    public function updateLaporanRepair(Request $request)
    {
        DB::beginTransaction();

        try {
            $norec = $request->input('norec');

            if (empty($norec)) {
                throw new \Exception('Norec laporan repair tidak ditemukan.');
            }

            $laporan = DB::table('laporanrepair_t')
                ->where('norec', $norec)
                ->first();

            if (!$laporan) {
                throw new \Exception('Data tindakan teknis repair tidak ditemukan.');
            }

            DB::table('laporanrepair_t')
                ->where('norec', $norec)
                ->update([
                    'bagianalatlaporan' => $this->sanitizeRepairRichText($request->input('bagianalatlaporan')),
                    'penanganan' => $this->sanitizeRepairRichText($request->input('penanganan')),
                    'status' => $this->sanitizeRepairRichText($request->input('status')),
                    'sparepart' => $this->sanitizeRepairRichText($request->input('sparepart')),
                    'updated_at' => now(),
                ]);

            $this->updateCaptionFotoRepair($request, 'laporanrepair_foto_t');
            $this->hapusFotoRepairTerpilih($request, 'laporanrepair_foto_t');

            $this->tambahFotoRepairBaru(
                $request,
                'laporanrepair_foto_t',
                'laporanrepairfk',
                $norec,
                $laporan->detailregistrasifk,
                'laporan_repair'
            );

            $this->syncFotoUtamaRepair(
                'laporanrepair_t',
                $norec,
                'fotoalatrepair',
                'laporanrepair_foto_t',
                'laporanrepairfk'
            );

            DB::commit();

            return $this->respond([
                'as' => '@adit',
            ], 200, 'Update Tindakan Teknis Repair Berhasil');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'error' => $e->getMessage(),
            ], 400, 'Update Tindakan Teknis Repair Gagal');
        }
    }

    public function updateHasilRepair(Request $request)
    {
        DB::beginTransaction();

        try {
            $norec = $request->input('norec');

            if (empty($norec)) {
                throw new \Exception('Norec hasil repair tidak ditemukan.');
            }

            $hasil = DB::table('daftarhasilrepair_t')
                ->where('norec', $norec)
                ->first();

            if (!$hasil) {
                throw new \Exception('Data hasil repair tidak ditemukan.');
            }

            DB::table('daftarhasilrepair_t')
                ->where('norec', $norec)
                ->update([
                    'hasil' => $this->sanitizeRepairRichText($request->input('hasil')),
                    'status' => $this->sanitizeRepairRichText($request->input('status')),
                    'updated_at' => now(),
                ]);

            $this->updateCaptionFotoRepair($request, 'hasilrepair_foto_t');
            $this->hapusFotoRepairTerpilih($request, 'hasilrepair_foto_t');

            $this->tambahFotoRepairBaru(
                $request,
                'hasilrepair_foto_t',
                'hasilrepairfk',
                $norec,
                $hasil->detailregistrasifk,
                'hasil_repair'
            );

            $this->syncFotoUtamaRepair(
                'daftarhasilrepair_t',
                $norec,
                'fotohasilrepair',
                'hasilrepair_foto_t',
                'hasilrepairfk'
            );

            DB::commit();

            return $this->respond([
                'as' => '@adit',
            ], 200, 'Update Hasil Repair Berhasil');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'error' => $e->getMessage(),
            ], 400, 'Update Hasil Repair Gagal');
        }
    }

    public function updateKesimpulanRepair(Request $request)
    {
        DB::beginTransaction();

        try {
            $norecDetail = $request->input('norec');
            $kesimpulan = $this->sanitizeRepairRichText($request->input('kesimpulan'));

            if (empty($norecDetail)) {
                throw new \Exception('Norec detail registrasi tidak ditemukan.');
            }

            if (!$this->repairRichTextHasContent($kesimpulan)) {
                throw new \Exception('Kesimpulan wajib diisi.');
            }

            $detail = DB::table('mitraregistrasidetail_t')
                ->where('norec', $norecDetail)
                ->where('statusenabled', true)
                ->first();

            if (!$detail) {
                throw new \Exception('Data detail registrasi tidak ditemukan.');
            }

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $norecDetail)
                ->update([
                    'kesimpulanrepair' => $kesimpulan,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return $this->respond([
                'as' => '@adit',
            ], 200, 'Update Kesimpulan Repair Berhasil');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'error' => $e->getMessage(),
            ], 400, 'Update Kesimpulan Repair Gagal');
        }
    }

    public function simpanLaporanRepairPenyelia(Request $request)
    {
        DB::beginTransaction();

        try {
            $daftarStatus = $request->input('daftarstatusrepair', []);
            $daftarRepair = $request->input('daftarlaporanrepair', []);
            $daftarHasil = $request->input('daftarhasilrepair', []);

            $norecDetail = $request->input('norec_detail');
            $kesimpulan = $this->sanitizeRepairRichText($request->input('kesimpulan'));
            $statusrepairfk = $request->input('statusrepairfk');

            if (empty($norecDetail)) {
                throw new \Exception('Norec detail registrasi tidak ditemukan.');
            }

            if (empty($statusrepairfk)) {
                throw new \Exception('Status keberhasilan repair wajib diisi.');
            }

            $detail = DB::table('mitraregistrasidetail_t')
                ->where('norec', $norecDetail)
                ->where('statusenabled', true)
                ->first();

            if (!$detail) {
                throw new \Exception('Data detail registrasi tidak ditemukan.');
            }

            $filledStatusRows = collect($daftarStatus)->filter(function ($item) {
                return $this->repairRichTextHasContent($item['bagianalatstatus'] ?? null)
                    || $this->repairRichTextHasContent($item['kondisistatus'] ?? null)
                    || !empty($item['fotos']);
            })->values();

            $filledRepairRows = collect($daftarRepair)->filter(function ($item) {
                return $this->repairRichTextHasContent($item['bagianalatlaporan'] ?? null)
                    || $this->repairRichTextHasContent($item['penanganan'] ?? null)
                    || $this->repairRichTextHasContent($item['status'] ?? null)
                    || $this->repairRichTextHasContent($item['sparepart'] ?? null)
                    || !empty($item['fotos']);
            })->values();

            $filledHasilRows = collect($daftarHasil)->filter(function ($item) {
                return $this->repairRichTextHasContent($item['hasil'] ?? null)
                    || $this->repairRichTextHasContent($item['status'] ?? null)
                    || !empty($item['fotos']);
            })->values();

            if ($filledStatusRows->isEmpty() && $filledRepairRows->isEmpty() && $filledHasilRows->isEmpty()) {
                throw new \Exception('Minimal isi salah satu bagian: Status Alat, Tindakan Teknis, atau Hasil Repair.');
            }

            foreach ($filledStatusRows as $index => $status) {
                if (!$this->repairRichTextHasContent($status['bagianalatstatus'] ?? null)) {
                    throw new \Exception('Status Alat baris ' . ($index + 1) . ': Bagian Alat wajib diisi.');
                }

                if (!$this->repairRichTextHasContent($status['kondisistatus'] ?? null)) {
                    throw new \Exception('Status Alat baris ' . ($index + 1) . ': Kondisi wajib diisi.');
                }

                if (!$request->hasFile("daftarstatusrepair.$index.fotos.0.file")) {
                    throw new \Exception('Status Alat baris ' . ($index + 1) . ': Dokumentasi gambar wajib diunggah minimal 1 gambar.');
                }
            }

            foreach ($filledRepairRows as $index => $repair) {
                if (!$this->repairRichTextHasContent($repair['bagianalatlaporan'] ?? null)) {
                    throw new \Exception('Tindakan Teknis baris ' . ($index + 1) . ': Bagian Alat wajib diisi.');
                }

                if (!$this->repairRichTextHasContent($repair['penanganan'] ?? null)) {
                    throw new \Exception('Tindakan Teknis baris ' . ($index + 1) . ': Penanganan wajib diisi.');
                }

                if (!$this->repairRichTextHasContent($repair['status'] ?? null)) {
                    throw new \Exception('Tindakan Teknis baris ' . ($index + 1) . ': Status wajib diisi.');
                }

                if (!$this->repairRichTextHasContent($repair['sparepart'] ?? null)) {
                    throw new \Exception('Tindakan Teknis baris ' . ($index + 1) . ': Sparepart & Material Consumable wajib diisi.');
                }

                if (!$request->hasFile("daftarlaporanrepair.$index.fotos.0.file")) {
                    throw new \Exception('Tindakan Teknis baris ' . ($index + 1) . ': Dokumentasi gambar wajib diunggah minimal 1 gambar.');
                }
            }

            foreach ($filledHasilRows as $index => $hasil) {
                if (!$this->repairRichTextHasContent($hasil['hasil'] ?? null)) {
                    throw new \Exception('Hasil Repair baris ' . ($index + 1) . ': Hasil wajib diisi.');
                }

                if (!$this->repairRichTextHasContent($hasil['status'] ?? null)) {
                    throw new \Exception('Hasil Repair baris ' . ($index + 1) . ': Status wajib diisi.');
                }

                if (!$request->hasFile("daftarhasilrepair.$index.fotos.0.file")) {
                    throw new \Exception('Hasil Repair baris ' . ($index + 1) . ': Dokumentasi gambar wajib diunggah minimal 1 gambar.');
                }
            }

            $tahunShort = date('y');
            $nolaporanrepair = $this->generateNomorLaporanRepair($detail, $tahunShort);

            $dataUpdate = [
                'penyeliaisilaporanrepairfk' => $this->getPegawaiId(),
                'tglisilaporanrepairpenyelia' => now(),
                'nolaporanrepair' => $nolaporanrepair,
                'statusrepairfk' => $statusrepairfk,
                'statusorderpelaksana' => 2,

                'istolakrepair' => null,
                'alasanpenolakanrepair' => null,
                'tgltolakrepair' => null,

                'istolakrepairasman' => null,
                'alasanpenolakanrepairasman' => null,
                'tgltolakrepairasman' => null,

                'istolakrepairmanager' => null,
                'alasanpenolakanrepairmanager' => null,
                'tgltolakrepairmanager' => null,

                'updated_at' => now(),
            ];

            if (!empty($kesimpulan)) {
                $dataUpdate['kesimpulanrepair'] = $kesimpulan;
            }

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $norecDetail)
                ->update($dataUpdate);

            $resultStatus = [];
            $resultRepair = [];
            $resultHasil = [];

            foreach ($filledStatusRows as $index => $status) {
                $modelStatus = new StatusAlatRepair;
                $modelStatus->norec = $modelStatus->generateNewId();
                $modelStatus->statusenabled = true;
                $modelStatus->bagianalat = $this->sanitizeRepairRichText($status['bagianalatstatus'] ?? null);
                $modelStatus->kondisi = $this->sanitizeRepairRichText($status['kondisistatus'] ?? null);
                $modelStatus->detailregistrasifk = $norecDetail;
                $modelStatus->fotoalatstatus = null;
                $modelStatus->petugas = $this->getPegawaiId();
                $modelStatus->save();

                $firstFileName = null;
                $fotos = $status['fotos'] ?? [];

                if (!is_array($fotos)) {
                    $fotos = [];
                }

                foreach ($fotos as $fotoIndex => $fotoInfo) {
                    $fileKey = "daftarstatusrepair.$index.fotos.$fotoIndex.file";

                    if (!$request->hasFile($fileKey)) {
                        continue;
                    }

                    $file = $request->file($fileKey);
                    $filename = $this->uploadFotoRepairFile($file, 'status_repair');

                    if ($firstFileName === null) {
                        $firstFileName = $filename;
                    }

                    DB::table('statusalatrepair_foto_t')->insert([
                        'norec' => (string) \Illuminate\Support\Str::uuid(),
                        'statusenabled' => true,
                        'statusalatrepairfk' => $modelStatus->norec,
                        'detailregistrasifk' => $norecDetail,
                        'namafile' => $filename,
                        'keterangan_gambar' => $fotoInfo['keterangan_gambar'] ?? null,
                        'urut' => $fotoIndex + 1,
                        'petugas' => $this->getPegawaiId(),
                        'created_at' => now(),
                        'updated_at' => null,
                    ]);
                }

                if ($firstFileName !== null) {
                    DB::table('statusalatrepair_t')
                        ->where('norec', $modelStatus->norec)
                        ->update([
                            'fotoalatstatus' => $firstFileName,
                            'updated_at' => now(),
                        ]);

                    $modelStatus->fotoalatstatus = $firstFileName;
                }

                $resultStatus[] = $modelStatus;
            }

            foreach ($filledRepairRows as $index => $repair) {
                $modelRepair = new LaporanRepair;
                $modelRepair->norec = $modelRepair->generateNewId();
                $modelRepair->statusenabled = true;
                $modelRepair->bagianalatlaporan = $this->sanitizeRepairRichText($repair['bagianalatlaporan'] ?? null);
                $modelRepair->penanganan = $this->sanitizeRepairRichText($repair['penanganan'] ?? null);
                $modelRepair->status = $this->sanitizeRepairRichText($repair['status'] ?? null);
                $modelRepair->sparepart = $this->sanitizeRepairRichText($repair['sparepart'] ?? null);
                $modelRepair->detailregistrasifk = $norecDetail;
                $modelRepair->fotoalatrepair = null;
                $modelRepair->petugas = $this->getPegawaiId();
                $modelRepair->save();

                $firstFileName = null;
                $fotos = $repair['fotos'] ?? [];

                if (!is_array($fotos)) {
                    $fotos = [];
                }

                foreach ($fotos as $fotoIndex => $fotoInfo) {
                    $fileKey = "daftarlaporanrepair.$index.fotos.$fotoIndex.file";

                    if (!$request->hasFile($fileKey)) {
                        continue;
                    }

                    $file = $request->file($fileKey);
                    $filename = $this->uploadFotoRepairFile($file, 'laporan_repair');

                    if ($firstFileName === null) {
                        $firstFileName = $filename;
                    }

                    DB::table('laporanrepair_foto_t')->insert([
                        'norec' => (string) \Illuminate\Support\Str::uuid(),
                        'statusenabled' => true,
                        'laporanrepairfk' => $modelRepair->norec,
                        'detailregistrasifk' => $norecDetail,
                        'namafile' => $filename,
                        'keterangan_gambar' => $fotoInfo['keterangan_gambar'] ?? null,
                        'urut' => $fotoIndex + 1,
                        'petugas' => $this->getPegawaiId(),
                        'created_at' => now(),
                        'updated_at' => null,
                    ]);
                }

                if ($firstFileName !== null) {
                    DB::table('laporanrepair_t')
                        ->where('norec', $modelRepair->norec)
                        ->update([
                            'fotoalatrepair' => $firstFileName,
                            'updated_at' => now(),
                        ]);

                    $modelRepair->fotoalatrepair = $firstFileName;
                }

                $resultRepair[] = $modelRepair;
            }

            foreach ($filledHasilRows as $index => $hasil) {
                $hasilNorec = (string) \Illuminate\Support\Str::uuid();

                DB::table('daftarhasilrepair_t')->insert([
                    'norec' => $hasilNorec,
                    'statusenabled' => true,
                    'detailregistrasifk' => $norecDetail,
                    'hasil' => $this->sanitizeRepairRichText($hasil['hasil'] ?? null),
                    'status' => $this->sanitizeRepairRichText($hasil['status'] ?? null),
                    'fotohasilrepair' => null,
                    'petugas' => $this->getPegawaiId(),
                    'created_at' => now(),
                    'updated_at' => null,
                ]);

                $firstFileName = null;
                $fotos = $hasil['fotos'] ?? [];

                if (!is_array($fotos)) {
                    $fotos = [];
                }

                foreach ($fotos as $fotoIndex => $fotoInfo) {
                    $fileKey = "daftarhasilrepair.$index.fotos.$fotoIndex.file";

                    if (!$request->hasFile($fileKey)) {
                        continue;
                    }

                    $file = $request->file($fileKey);
                    $filename = $this->uploadFotoRepairFile($file, 'hasil_repair');

                    if ($firstFileName === null) {
                        $firstFileName = $filename;
                    }

                    DB::table('hasilrepair_foto_t')->insert([
                        'norec' => (string) \Illuminate\Support\Str::uuid(),
                        'statusenabled' => true,
                        'hasilrepairfk' => $hasilNorec,
                        'detailregistrasifk' => $norecDetail,
                        'namafile' => $filename,
                        'keterangan_gambar' => $fotoInfo['keterangan_gambar'] ?? null,
                        'urut' => $fotoIndex + 1,
                        'petugas' => $this->getPegawaiId(),
                        'created_at' => now(),
                        'updated_at' => null,
                    ]);
                }

                if ($firstFileName !== null) {
                    DB::table('daftarhasilrepair_t')
                        ->where('norec', $hasilNorec)
                        ->update([
                            'fotohasilrepair' => $firstFileName,
                            'updated_at' => now(),
                        ]);
                }

                $resultHasil[] = DB::table('daftarhasilrepair_t')
                    ->where('norec', $hasilNorec)
                    ->first();
            }

            DB::commit();

            $detail = DB::table('mitraregistrasidetail_t')
                ->where('norec', $norecDetail)
                ->first();

            $norecReg = $detail->noregistrasifk ?? null;
            $noorderalat = $detail->noorderalat ?? '-';

            $regis = null;
            if ($norecReg) {
                $regis = DB::table('mitraregistrasi_t')
                    ->where('norec', $norecReg)
                    ->first();
            }

            $username = 'Mahzumi';
            $profileId = $regis->kdprofile ?? 1;
            $token = $this->createToken($username) . '.' . base64_encode((string) $profileId);

            $link = url('/service/penyelia/cetak-laporan-repair') . '?pdf=true'
                . '&norec=' . $norecReg
                . '&norec_detail=' . $norecDetail
                . '&user=' . urlencode($username)
                . '&kdprofile=' . $profileId
                . '&token=' . $token;

            $shortLink = $this->shortLink($link);

            $pegawaiPenyelia = DB::table('pegawai_m')
                ->where('id', $this->getPegawaiId())
                ->first();

            $namaPenyelia = $pegawaiPenyelia->namalengkap ?? '-';

            $asmanId = $regis->asmanveriffk ?? null;

            if ($asmanId) {
                $asman = DB::table('pegawai_m')
                    ->where('id', $asmanId)
                    ->first();

                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';

                if ($nohpAsman) {
                    $statusRepairMaster = DB::table('statusrepair_m')
                        ->where('id', $statusrepairfk)
                        ->first();

                    $statusRepairText = strtoupper($statusRepairMaster->statusrepair ?? '-');

                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman,\n"
                        . "Pemberitahuan: Alat dengan No. Order Alat: *$noorderalat* telah diisi Laporan Repair oleh Penyelia Repair *$namaPenyelia*.\n"
                        . "Status Repair: *$statusRepairText*.\n\n"
                        . "Tautan dokumen Laporan Repair:\n$shortLink\n\n"
                        . "Silahkan cek dan review pada aplikasi.\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";

                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            return $this->respond([
                'status' => [
                    'jumlah' => count($resultStatus),
                    'data' => $resultStatus,
                ],
                'repair' => [
                    'jumlah' => count($resultRepair),
                    'data' => $resultRepair,
                ],
                'hasil' => [
                    'jumlah' => count($resultHasil),
                    'data' => $resultHasil,
                ],
                'norec_detail' => $norecDetail,
                'nolaporanrepair' => $nolaporanrepair,
                'as' => '@adit',
            ], 201, 'Simpan Laporan Repair Penyelia Berhasil');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'error' => $e->getMessage(),
                'as' => '@adit',
            ], 400, 'Simpan Laporan Repair Penyelia Gagal: ' . $e->getMessage());
        }
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

    private function laporanRepairMasihDraftPelaksana($norecDetail): bool
    {
        return DB::table('mitraregistrasidetail_t as mtrd')
            ->join('mitraregistrasi_t as mtr', 'mtr.norec', '=', 'mtrd.noregistrasifk')
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('mtr.jenisorder', 'repair')
            ->where(function ($query) {
                $query->where('mtrd.statusorderpelaksana', 1)
                    ->orWhereNull('mtrd.statusorderpelaksana');
            })
            ->whereNull('mtrd.pelaksanaisilaporanrepairfk')
            ->whereNull('mtrd.tglisilaporanrepairpelaksana')
            ->whereNull('mtrd.tglisilaporanrepairpenyelia')
            ->where(function ($query) {
                $query->whereNotNull('mtrd.statusrepairfk')
                    ->orWhereRaw("NULLIF(BTRIM(COALESCE(mtrd.kesimpulanrepair, '')), '') IS NOT NULL")
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('statusalatrepair_t as sar')
                            ->whereColumn('sar.detailregistrasifk', 'mtrd.norec')
                            ->where('sar.statusenabled', true);
                    })
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('laporanrepair_t as lr')
                            ->whereColumn('lr.detailregistrasifk', 'mtrd.norec')
                            ->where('lr.statusenabled', true);
                    })
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('daftarhasilrepair_t as hr')
                            ->whereColumn('hr.detailregistrasifk', 'mtrd.norec')
                            ->where('hr.statusenabled', true);
                    });
            })
            ->exists();
    }

    public function getLaporanRepairPenyelia(Request $request)
    {
        $norecDetail = $request->norecdetail;

        if ($this->laporanRepairMasihDraftPelaksana($norecDetail)) {
            return $this->respond([
                'data' => collect(),
                'datastatus' => collect(),
                'datahasil' => collect(),
                'detail' => null,
                'as' => '@adit',
            ]);
        }

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

    public function cetakLaporanRepairPenyelia(Request $r)
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

        $res['laporanRepair'] = DB::table('laporanrepair_t as lp')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'lp.detailregistrasifk')
            ->select('lp.*')
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('lp.statusenabled', true)
            ->orderBy('lp.created_at', 'ASC')
            ->get();

        $res['statusRepair'] = DB::table('statusalatrepair_t as sp')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.norec', '=', 'sp.detailregistrasifk')
            ->select('sp.*')
            ->where('mtrd.norec', $norecDetail)
            ->where('mtrd.statusenabled', true)
            ->where('sp.statusenabled', true)
            ->orderBy('sp.created_at', 'ASC')
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

        $res['halamanPertama'] = false;

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

    public function detailAlatRepairPenyelia(Request $r)
    {
        if ($this->laporanRepairMasihDraftPelaksana($r['norec_pd'])) {
            return $this->respond([
                'data' => null,
                'as' => '@adit'
            ]);
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

    public function hapusLaporanRepairPenyelia(Request $r)
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

    public function setujuiLaporanRepair(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['verif'];
            $timestamp = now();
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec'])
                ->update([
                    'penyeliasetujulaporanrepairfk' => $this->getPegawaiId(),
                    'tglsetujupenyelialaporanrepair' => now(),
                    'statusorderpenyelia' => 2,
                    'statusorderasman' => 0,
                ]);

            $detail = DB::table('mitraregistrasidetail_t')->where('norec', $VI['norec'])->first();
            $noorderalat = $detail->noorderalat ?? '-';
            $noregistrasifk = $detail->noregistrasifk ?? null;

            $penyelia = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPenyelia = $penyelia->namalengkap ?? '-';

            $waktuVerif = $detail->tglsetujupenyelialembarkerja ?? $timestamp;
            try {
                $waktuVerifStr = \Carbon\Carbon::parse($waktuVerif)->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuVerifStr = date('d-m-Y H:i', strtotime($waktuVerif)) . ' WIB';
            }

            $regis = $noregistrasifk ? DB::table('mitraregistrasi_t')->where('norec', $noregistrasifk)->first() : null;
            $asmanId = $regis->asmanveriffk ?? null;
            if ($asmanId) {
                $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';

                if ($nohpAsman) {
                    $pesanAsman = "Yth. $namaAsman,\n\n"
                        . "Pemberitahuan: Penyelia *$namaPenyelia* telah menyetujui Laporan Repair pada order No. Order Alat: *$noorderalat*.\n"
                        . "Waktu persetujuan: $waktuVerifStr\n\n"
                        . "Mohon untuk segera memeriksa dan memproses Laporan Repair terkait.\n\n"
                        . "Salam,\nLaboratorium Kalibrasi ULAB UMRO";
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            if ($pelaksanaId) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanaId)->first();
                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';

                if ($nohpPelaksana) {
                    $pesanPelaksana = "Yth. $namaPelaksana,\n\n"
                        . "Laporan Repair pada order No. Order Alat: *$noorderalat* telah disetujui oleh penyelia pada $waktuVerifStr.\n\n"
                        . "Terima kasih atas kerjasama Anda.\n\n"
                        . "Salam,\nLaboratorium Kalibrasi ULAB UMRO";
                    $this->kirimWhatsappNotifikasi($nohpPelaksana, $pesanPelaksana);
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

    public function hapusHasilRepair(Request $r)
    {
        DB::beginTransaction();

        try {
            $hasil = DB::table('daftarhasilrepair_t')
                ->where('norec', $r['norec'])
                ->first();

            if ($hasil) {
                $this->hapusFotoMultipleRepair('hasilrepair_foto_t', 'hasilrepairfk', $hasil->norec);

                if (!empty($hasil->fotohasilrepair)) {
                    $filePath = public_path('berkas-laporan-repair/' . $hasil->fotohasilrepair);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            DB::table('daftarhasilrepair_t')
                ->where('norec', $r['norec'])
                ->delete();

            DB::commit();

            return $this->respond([
                'as' => '@adit',
            ], 200, 'Hapus Hasil Repair Sukses');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond([
                'error' => $e->getMessage(),
            ], 400, 'Hapus Hasil Repair Gagal');
        }
    }

    public function getAlatStandar(Request $r)
    {
        $search = $r['query'];

        $data = DB::table('mapalatstandar_m as mmp')
            ->select(
                'mmp.id',
                'mmp.namaalatstandar',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber'
            )
            ->where('mmp.statusenabled', true);

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
        $result['as'] = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function saveStatusKan(Request $r)
    {
        DB::beginTransaction();
        try {
            $VI = $r['status'];

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $VI['norec_detail'])
                ->update([
                    'statuskanfk' => $VI['statuskanfk']
                ]);

            $transMessage = "Simpan Status KAN Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => '@aditwiran19@gmail.com',
                ],
            ];
        } catch (\Exception $e) {
            $transMessage = "Simpan Status KAN Gagal";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => $e->getMessage()
            ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function saveExcelLaporanVerif(Request $r)
    {
        DB::beginTransaction();
        try {
            $filename = null;
            $DAS = json_decode($r['daftarperalatanstandar'], true);
            if ($r->hasFile('fileMitraExcel')) {
                $file = $r->file('fileMitraExcel');
                $allowedExtensions = ['xlsx', 'xls', 'csv'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    throw new \Exception("File harus berupa Excel (.xlsx, .xls) atau CSV.");
                }
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('berkas-verifikasi'), $filename);
            } else {
                $filename = $r['namaFileLama'] ?? null;
            }

            $tahunShort = date('Y');

            $details = DB::table('mitraregistrasidetail_t')
                ->where('norec', $r->norec)
                ->get();

            foreach ($details as $i => $detail) {
                $nosertifikat = $this->generateNomorSertifikat($detail, $tahunShort);
                DB::table('mitraregistrasidetail_t')
                    ->where('norec', $detail->norec)
                    ->update([
                        'penyeliaisilembarkerjafk' => $this->getPegawaiId(),
                        'tglisilembarkerjapenyelia' => now(),
                        'tglkalibrasilembarkerja' => $r['tglkalibrasi'],
                        'tempatKalibrasilembarkerja' => $r['tempatKalibrasi'],
                        'suhulembarkerja' => $r['suhu'],
                        'kelembabanRelatiflembarkerja' => $r['kelembabanRelatif'],
                        'evalverifikasi' => $r['evalverifikasi'],
                        'catatanverifikasi' => $r['catatanverifikasi'],
                        'nosertifikat' => $nosertifikat,
                        'statusorderpelaksana' => 2,
                        'excellaporanverif' => $filename,
                        'updated_at' => now(),
                        'isverifikasi' => true,
                        'istolakserti' => null,
                        'alasanpenolakanserti' => null,
                        'tgltolakserti' => null,
                        'istolaksertiasman' => null,
                        'alasanpenolakansertiasman' => null,
                        'tgltolaksertiasman' => null,
                        'istolaksertimanager' => null,
                        'alasanpenolakansertimanager' => null,
                        'tgltolaksertimanager' => null,
                    ]);
            }

            $dataAlatStandar = [];
            DaftarAlatStandar::where('detailregistrasifk', $r->norec)->delete();
            $petugasAlatStandar = $this->getPegawaiId();
            foreach ($DAS as $standar) {
                $model_Standar = new DaftarAlatStandar;
                $timestamp = now();
                $dataAlatStandar[] = [
                    'norec' => $model_Standar->generateNewId(),
                    'statusenabled' => true,
                    'alatstandarfk' => $standar['peralatanstandar'],
                    'detailregistrasifk' => $r->norec,
                    'petugas' => $petugasAlatStandar,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
            if (!empty($dataAlatStandar)) {
                DB::table('daftaralatstandar_t')->insert($dataAlatStandar);
            }

            $norecReg = $r->norec_registrasi ?? null;
            if (!$norecReg && isset($r->norec)) {
                $detail = DB::table('mitraregistrasidetail_t')->where('norec', $r->norec)->first();
                $norecReg = $detail->noregistrasifk ?? null;
            }
            $regis = null;
            if ($norecReg) {
                $regis = DB::table('mitraregistrasi_t')->where('norec', $norecReg)->first();
            }

            $username = 'Mahzumi';
            $profileId = $regis->kdprofile ?? 1;
            $token = $this->createToken($username) . '.' . base64_encode((string)$profileId);
            $link = url('/service/pelaksana/cetak-sertifikat-lembar-kerja') . '?pdf=true'
                . '&norec=' . $norecReg
                . '&norec_detail=' . $r->norec
                . '&user=' . urlencode($username)
                . '&kdprofile=' . $profileId
                . '&token=' . $token;
            $shortLink = $this->shortLink($link);
            $detail1 = DB::table('mitraregistrasidetail_t')->where('norec', $r->norec)->first();
            $noorderalat = $detail1->noorderalat ?? '-';
            $namaPelaksana = '-';
            $pegawaiPelaksana = DB::table('pegawai_m')->where('id', $this->getPegawaiId())->first();
            $namaPelaksana = $pegawaiPelaksana->namalengkap ?? '-';

            try {
                $waktuVerifStr = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            } catch (\Exception $e) {
                $waktuVerifStr = date('d-m-Y H:i') . ' WIB';
            }

            $asmanId = $regis->asmanveriffk ?? null;
            if ($asmanId) {
                $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                $nohpAsman = $asman->nohandphone ?? null;
                $namaAsman = $asman->namalengkap ?? '-';

                if ($nohpAsman) {
                    $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaAsman\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                        . "Telah dibuat Draft Laporan Verifikasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Tautan Draft Laporan Verifikasi :\n$shortLink\n\n"
                        . "Silahkan cek dan review Draft Laporan Verifikasi pada aplikasi.\n\n"
                        . "Salam,\n"
                        . "U-LAB ! Cepat, Tepat, Akurat 💯";
                    $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                }
            }

            $detail1 = $details->first();
            $penyeliaId = $detail1->penyeliateknikfk ?? null;
            if ($penyeliaId) {
                $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
                $nohpPenyelia = $penyelia->nohandphone ?? null;
                $namaPenyelia = $penyelia->namalengkap ?? '-';

                if ($nohpPenyelia) {
                    $pesanPenyelia = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPenyelia\n"
                        . "Kami informasikan bahwa,\n\n"
                        . "No Order : *" . ($noorderalat ?? '-') . "*\n"
                        . "Telah dibuat Draft Sertifikat Kalibrasi oleh Pelaksana Kalibrasi : *$namaPelaksana*\n"
                        . "Pada $waktuVerifStr\n\n"
                        . "Tautan Draft Sertifikat Kalibrasi :\n$shortLink\n\n"
                        . "Silahkan cek dan review Draft Sertifikat Kalibrasi pada aplikasi.";
                    $this->kirimWhatsappNotifikasi($nohpPenyelia, $pesanPenyelia);
                }
            }

            $transMessage = "Simpan Excel Laporan Verifikasi Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => 'aditwiran19@gmail.com',
                    "namafile" => $filename,
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

        $res['alat'] =  $data = DB::table('mitraregistrasi_t as mtr')
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
        $pegawaiId = $this->getPegawaiId();
        $roomid = Pegawai::where('id', $pegawaiId)
            ->where('statusenabled', true)
            ->value('roomfk') ?? 1;

        $data = DB::table('sensor_readings as sr')
            ->select(
                'sr.id',
                'sr.room_id',
                'sr.temperature',
                'sr.humidity',
                'sr.pressure',
                'sr.light',
                'sr.uv',
                'sr.created_at',
            )
            ->where('sr.statusenabled', true)
            ->where('sr.room_id', $roomid)
            ->orderByDesc('sr.created_at')
            ->first();

        $dataChart = DB::table('sensor_readings as sr')
            ->select(
                'sr.id',
                'sr.room_id',
                'sr.temperature',
                'sr.humidity',
                'sr.pressure',
                'sr.light',
                'sr.uv',
                'sr.created_at',
            )
            ->where('sr.statusenabled', true)
            ->where('sr.room_id', $roomid)
            ->orderByDesc('sr.created_at')
            ->take(10)
            ->get();

        $result['data'] = $data;
        $result['dataChart'] = $dataChart;
        $result['as'] = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function savePenolakanSerti(Request $request)
    {
        DB::beginTransaction();
        try {
            $r_tolak = $request['itemtolak'];
            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r_tolak['norecregis'])
                ->update([
                    'istolakserti' => true,
                    'alasanpenolakanserti' => $r_tolak['alasanpenolakanserti'],
                    'tgltolakserti' => now(),
                ]);

            $message = 'Penolakan Berhasil';

            DB::commit();

            $regis = DB::table('mitraregistrasidetail_t')->where('norec', $r_tolak['norecregis'])->first();
            $noorderalat = $regis->noorderalat ?? null;
            $alasanpenolakanserti = $r_tolak['alasanpenolakanserti'] ?? null;

            $pelaksanateknikfk = $regis->pelaksanateknikfk ?? null;
            if ($pelaksanateknikfk) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanateknikfk)->first();
                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';
                if ($nohpPelaksana) {
                    $pesanPelaksana = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPelaksana\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Sertifikat Telah ditolak Oleh Penyelia.\n"
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
                    'istolakrepair' => true,
                    'alasanpenolakanrepair' => $r_tolak['alasanpenolakan'],
                    'tgltolakrepair' => now(),
                ]);

            $message = 'Penolakan Berhasil';

            DB::commit();

            $regis = DB::table('mitraregistrasidetail_t')->where('norec', $r_tolak['norecregis'])->first();
            $noorderalat = $regis->noorderalat ?? null;
            $alasanpenolakan = $r_tolak['alasanpenolakan'] ?? null;

            $pelaksanateknikfk = $regis->pelaksanateknikfk ?? null;
            if ($pelaksanateknikfk) {
                $pelaksana = DB::table('pegawai_m')->where('id', $pelaksanateknikfk)->first();
                $nohpPelaksana = $pelaksana->nohandphone ?? null;
                $namaPelaksana = $pelaksana->namalengkap ?? '-';
                if ($nohpPelaksana) {
                    $pesanPelaksana = "Halo Tim Hebat U-LAB ! 👋\n\n"
                        . "Yth. $namaPelaksana\n"
                        . "Pemberitahuan: Alat dengan No OrderAlat *$noorderalat* Laporan Repair Telah ditolak Oleh Penyelia.\n"
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

    public function legacySaveExcelLembarKerja(Request $r)
    {
        DB::beginTransaction();
        try {
            $file = $r->file('fileMitraExcel');
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, $allowedExtensions)) {
                throw new \Exception("File harus berupa Excel (.xlsx, .xls) atau CSV.");
            }

            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('berkas-mitra-excel'), $filename);

            DB::table('mitraregistrasidetail_t')
                ->where('norec', $r->norec)
                ->update([
                    'namafileexcel' => $filename,
                    'updated_at' => now(),
                ]);

            $transMessage = "Simpan Excel Lembar Kerja Sukses";
            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "as" => 'aditwiran19@gmail.com',
                    "namafile" => $filename,
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
            ->where('pg.id', $this->getPegawaiId())
            ->where('mtrd.isamandemen', true)
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

            $detail     = DB::table('mitraregistrasidetail_t')->where('norec', $r['norec'])->first();
            $penyeliaId = $detail->penyeliateknikfk ?? null;
            $penyelia = DB::table('pegawai_m')->where('id', $penyeliaId)->first();
            $pelaksanaId = $detail->pelaksanateknikfk ?? null;
            $asmanId = $detail->asmansetujulembarkerjafk ?? $detail->asmansetujulaporanrepairfk ?? null;

            $namaPenyelia = $penyelia->namalengkap ?? '-';
            $waktuPengajuan = now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
            $noorderalat = $r['noorderalat'] ?? null;
            if ($r['status'] == 1) {
                $statusapprov = 'Pengajuan Disetujui ✅';
                if ($asmanId) {
                    $asman = DB::table('pegawai_m')->where('id', $asmanId)->first();
                    $nohpAsman = $asman->nohandphone ?? null;
                    $namaAsman = $asman->namalengkap ?? '-';
                    if ($nohpAsman) {
                        $pesanAsman = "Halo Tim Hebat U-LAB ! 👋\n\n"
                            . "Yth. $namaAsman\n"
                            . "Kami informasikan bahwa,\n\n"
                            . "Alat dengan No Order : *$noorderalat*\n"
                            . "Telah dilakukan Approvel Pengajuan Amandemen oleh Penyelia : *$namaPenyelia*\n"
                            . "Pada $waktuPengajuan\n"
                            . "Dengan status Pengajuan: $statusapprov\n\n"
                            . "Silahkan cek dan review daftar pengajuan amandemen pada aplikasi.\n\n"
                            . "Salam,\n"
                            . "U-LAB ! Cepat, Tepat, Akurat 💯";
                        $this->kirimWhatsappNotifikasi($nohpAsman, $pesanAsman);
                    }
                }
            } else {
                $statusapprov = 'Pengajuan Ditolak ❌';
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
                        . "Telah dilakukan Approvel Pengajuan Amandemen oleh Penyelia : *$namaPenyelia*\n"
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
}
