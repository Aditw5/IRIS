<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\AlatStandar;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MasterAlatStandarCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterAlatStandar(Request $r)
    {
        $data  = DB::table('mapalatstandar_m as mp')
            ->leftJoin('mitra_m as mt', function ($join) {
                $join->on(DB::raw('CAST(mt.id AS INTEGER)'), '=', 'mp.standarmilikfk');
            })
            ->leftJoin('lingkupkalibrasi_m as lk', 'lk.id', '=', 'mp.lingkupfk')
            ->select(
                'mp.id',
                'mp.statusenabled',
                'mp.pengingat_rekalibrasi_aktif',
                'mp.namaalatstandar',
                'mp.namamerk',
                'mp.namatipe',
                'mp.namaserialnumber',
                'mp.duedate',
                'mp.calldate',
                'mp.lingkupfk',
                'lk.lingkupkalibrasi',
                'mt.id as idunit',
                'mt.namaperusahaan',
            )
            ->where('mp.kdprofile', $this->kdProfile);
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('mp.id', '=',  $r['id']);
        }
        if (isset($r['namaalatstandar']) && $r['namaalatstandar'] != '') {
            $data = $data->where('mp.namaalatstandar', 'ilike', '%' . $r['namaalatstandar'] . '%');
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('mp.statusenabled', '=', $r['statusenabled']);
        }
        if (isset($r['_total']) && $r['_total'] != '') {
        }

        $data = $data->orderByDesc('mp.created_at');
        $data = $data->get();

        foreach ($data as $d) {
            $d->statusenabled;
            $d->status = 'Aktif';
            $d->status_c = 'info';
            if ($d->statusenabled != 'false') {
                $d->status = 'Nonaktif';
                $d->status_c = 'danger';
            }
        }

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function saveAlatStandar(Request $r)
    {
        DB::beginTransaction();
        try {
            $PSN =  $r['datastandar'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new AlatStandar(), 'id', $this->kdProfile);
                $dataPS = new AlatStandar();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
                $dueDateChanged = false;
            } else {
                $dataPS = AlatStandar::where('id', $PSN['id'])
                    ->where('kdprofile', $this->kdProfile)
                    ->firstOrFail();
                $dueDateChanged = empty($dataPS->duedate)
                    || Carbon::parse($dataPS->duedate)->format('Y-m-d H:i:s')
                        !== Carbon::parse($PSN['duedate'])->format('Y-m-d H:i:s');
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->namaalatstandar =  $PSN['namaalatstandar'];
            $dataPS->namamerk =  $PSN['namamerk'];
            $dataPS->namaserialnumber =  $PSN['namaserialnumber'];
            $dataPS->standarmilikfk =  $PSN['standarmilikfk'];
            $dataPS->lingkupfk =  $PSN['lingkupfk'] ?? null;
            $dataPS->duedate =  $PSN['duedate'];
            $dataPS->calldate =  $PSN['calldate'];
            $dataPS->namatipe =  $PSN['namatipe'];
            $dataPS->pengingat_rekalibrasi_aktif = $dueDateChanged
                ? true
                : filter_var(
                    $PSN['pengingat_rekalibrasi_aktif'] ?? true,
                    FILTER_VALIDATE_BOOLEAN
                );
            $dataPS->save();

            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        if ($transStatus == 'true') {
            $transMessage = "Sukses";
            DB::commit();

            $result = array(
                "status" => 200,
                "result" => array(
                    "data"  => $dataPS,
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } else {
            $transMessage = "Simpan Gagal";
            DB::rollBack();

            $result = array(
                "status" => 400,
                "result"  => null

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
    public function deleteAlatStandar(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = AlatStandar::where('id', $r['id'])
                ->update([
                    'statusenabled' => false
                ]);

            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }

        if ($transStatus == 'true') {
            $transMessage = "Sukses";
            DB::commit();

            $result = array(
                "status" => 200,
                "result" => array(
                    "data"  => $dataPS,
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } else {
            $transMessage = "Hapus Gagal";
            DB::rollBack();

            $result = array(
                "status" => 400,
                "result"  => null

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function uploadSertifikatStandar(Request $r)
    {
        $validator = Validator::make($r->all(), [
            'alatstandarfk' => 'required|integer',
            'calldate' => 'required|date',
            'duedate' => 'required|date',
            'sertifikat' => 'required|file|mimes:pdf|max:10240',
        ], [
            'alatstandarfk.required' => 'Alat standar harus dipilih.',
            'calldate.required' => 'Cal Date harus diisi.',
            'duedate.required' => 'Due Date harus diisi.',
            'sertifikat.required' => 'File sertifikat harus dipilih.',
            'sertifikat.mimes' => 'Sertifikat harus berupa file PDF.',
            'sertifikat.max' => 'Ukuran sertifikat maksimal 10 MB.',
        ]);

        if ($validator->fails()) {
            return $this->respond(null, 400, $validator->errors()->first());
        }

        $calDate = Carbon::parse($r->input('calldate'));
        $dueDate = Carbon::parse($r->input('duedate'));
        if ($dueDate->lt($calDate)) {
            return $this->respond(null, 400, 'Due Date tidak boleh lebih awal dari Cal Date.');
        }

        $storedFilePath = null;
        DB::beginTransaction();
        try {
            $alat = DB::table('mapalatstandar_m')
                ->where('id', (int) $r->input('alatstandarfk'))
                ->where('kdprofile', $this->kdProfile)
                ->where('statusenabled', true)
                ->lockForUpdate()
                ->first();

            if (!$alat) {
                throw new \Exception('Data alat standar tidak ditemukan atau sudah nonaktif.');
            }

            $file = $r->file('sertifikat');
            $uploadDirectory = public_path('sertifikat-standar');
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                throw new \Exception('Folder penyimpanan sertifikat tidak dapat dibuat.');
            }

            $storedName = sprintf(
                'sertifikat_standar_%s_%s.pdf',
                (int) $alat->id,
                (string) Str::uuid()
            );
            $file->move($uploadDirectory, $storedName);
            $storedFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedName;

            $historyId = DB::table('sertifikat_standar_log')->insertGetId([
                'kdprofile' => (int) $this->kdProfile,
                'statusenabled' => true,
                'alatstandarfk' => (int) $alat->id,
                'calldate' => $calDate->format('Y-m-d H:i:s'),
                'duedate' => $dueDate->format('Y-m-d H:i:s'),
                'file_path' => 'sertifikat-standar/' . $storedName,
                'file_name' => basename($file->getClientOriginalName()),
                'created_by' => $this->getPegawaiId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('mapalatstandar_m')
                ->where('id', (int) $alat->id)
                ->where('kdprofile', $this->kdProfile)
                ->update([
                    'calldate' => $calDate->format('Y-m-d H:i:s'),
                    'duedate' => $dueDate->format('Y-m-d H:i:s'),
                    'pengingat_rekalibrasi_aktif' => true,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return $this->respond([
                'id' => $historyId,
                'alatstandarfk' => (int) $alat->id,
            ], 200, 'Sertifikat standar berhasil diunggah dan riwayat rekalibrasi telah ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($storedFilePath && is_file($storedFilePath)) {
                @unlink($storedFilePath);
            }

            return $this->respond(null, 400, $e->getMessage());
        }
    }

    public function viewSertifikatStandar(Request $r)
    {
        $data = DB::table('sertifikat_standar_log')
            ->where('id', (int) $r->input('id'))
            ->where('kdprofile', $this->kdProfile)
            ->where('statusenabled', true)
            ->first();

        if (!$data || !$data->file_path) {
            abort(404, 'Sertifikat standar tidak ditemukan.');
        }

        $basePath = realpath(public_path('sertifikat-standar'));
        $filePath = realpath(public_path($data->file_path));
        if (!$basePath || !$filePath || !is_file($filePath)) {
            abort(404, 'File sertifikat standar tidak ditemukan di server.');
        }

        $basePathWithSeparator = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($filePath, $basePathWithSeparator) !== 0) {
            abort(403, 'Path sertifikat tidak valid.');
        }

        $downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($data->file_name));
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }

    public function fetchAlat(Request $r)
    {
        $data = DB::table('mapalatstandar_m as mmp')
            ->select(
                'mmp.namaalatstandar as namaproduk',
                'mmp.id as idalat',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
                'mmp.fotoproduk',
            )
            ->where('mmp.statusenabled', true)
            ->where('mmp.id', $r['id_alat']);

        $data = $data->first();

        $result['data'] = $data;
        $result['as'] = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }

    public function historyAlat(Request $r)
    {
        $isOwner = true;

        $query = DB::table('mitraregistrasi_t as mtr')
            ->join('mitraregistrasidetail_t as mtrd', 'mtrd.noregistrasifk', '=', 'mtr.norec')
            ->join('mapalatstandar_m as mmp', 'mmp.id', '=', 'mtrd.namaalatfk')
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
                'mtrd.durasikalbrasi',
                'mtrd.namafile',
                'mtrd.keterangan',
                'mtrd.alasanpenolakanregis',
                'mtrd.tanggalpenolakanregis',
                'mtrd.noorderalat',
                'mmp.namaalatstandar as namaproduk',
                'mtr.tglregistrasi',
                'mtr.nopendaftaran',
                'mtr.catatan',
                'mtr.jenisorder',
                'mmp.id as idalat',
                'mmp.namamerk',
                'mmp.namatipe',
                'mmp.namaserialnumber',
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
                'pk.hari as hari_paket',
                'mtr.isikepuasanpelanggan',
                'mtr.verifregiscustomer',
                'mtr.tanggalverifregiscustomer',
                'mtrd.tglverifasman',
                'mtrd.tglsetujumanagerlembarkerja',
                'mtrd.tglsetujumanagerlaporanrepair',
                'mtrd.isVendor',
                'mtrd.fileSertiVendor',
                'mtrd.tglupladosertivendor',
                'mtrd.isireviewalat',
                'mtrd.isverifikasi',
                'mtrd.bintangpenilaian'
            )
            ->where('mtr.statusenabled', true)
            ->where('mtr.isstandarulab', true)
            ->where('mmp.statusenabled', true)
            ->where('mtrd.statusenabled', true)
            ->where('mmp.id', $r['id_alat']);

        $kelompokUserId = $this->getKelompokUserId();
        $mitraFk = null;

        if ($kelompokUserId == null) {
            $userId = $this->getPegawaiId();

            $mitraFk = DB::table('users')
                ->where('id', $userId)
                ->value('mitrafk');

            if ($mitraFk) {
                $query->where('mtr.nomitrafk', $mitraFk);
            } else {
                $query->whereRaw('1 = 0');
                $isOwner = false;
            }
        }

        $data = $query->orderByDesc('mtr.tglregistrasi')->get();

        if ($kelompokUserId == null && $mitraFk && $data->isEmpty()) {
            $isOwner = false;
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

        foreach ($data as $item) {
            $item->is_external = false;
            $item->history_source = 'internal';
            $item->history_date = $item->tglsetujumanagerlembarkerja
                ?: $item->tglregistrasi;

            $tglVerifAsman = $item->tglverifasman
                ? Carbon::parse($item->tglverifasman)
                : null;

            $tglSetujuManager = $item->tglsetujumanagerlembarkerja
                ? Carbon::parse($item->tglsetujumanagerlembarkerja)
                : null;

            $diffInSeconds = $calcWorkingSeconds($tglVerifAsman, $tglSetujuManager);

            if ($diffInSeconds !== null) {
                $workdaySeconds = (8 * 60 * 60) + (30 * 60);

                $days = intdiv($diffInSeconds, $workdaySeconds);
                $rem  = $diffInSeconds % $workdaySeconds;

                $hours = intdiv($rem, 3600);
                $rem   = $rem % 3600;

                $minutes = intdiv($rem, 60);
                $seconds = $rem % 60;

                $item->durasi_proses = "{$days} hari kerja {$hours} jam {$minutes} menit {$seconds} detik";
                $item->durasi_detik  = $diffInSeconds;
            } else {
                $item->durasi_proses = '-';
                $item->durasi_detik  = null;
            }
        }

        $externalHistory = DB::table('sertifikat_standar_log as ssl')
            ->leftJoin('pegawai_m as pg_upload', 'pg_upload.id', '=', 'ssl.created_by')
            ->select(
                'ssl.id',
                'ssl.alatstandarfk as idalat',
                'ssl.calldate',
                'ssl.duedate',
                'ssl.file_name as certificate_file_name',
                'ssl.created_at as uploaded_at',
                'pg_upload.namalengkap as uploaded_by'
            )
            ->where('ssl.kdprofile', $this->kdProfile)
            ->where('ssl.statusenabled', true)
            ->where('ssl.alatstandarfk', $r['id_alat'])
            ->get()
            ->map(function ($history) {
                return (object) [
                    'norec' => 'external-' . $history->id,
                    'norec_detail' => 'external-' . $history->id,
                    'certificate_history_id' => $history->id,
                    'idalat' => $history->idalat,
                    'jenisorder' => 'kalibrasi_eksternal',
                    'is_external' => true,
                    'history_source' => 'external',
                    'history_date' => $history->calldate,
                    'tglregistrasi' => $history->calldate,
                    'calldate' => $history->calldate,
                    'duedate' => $history->duedate,
                    'certificate_file_name' => $history->certificate_file_name,
                    'uploaded_at' => $history->uploaded_at,
                    'uploaded_by' => $history->uploaded_by,
                ];
            });

        $data = $data
            ->concat($externalHistory)
            ->sortByDesc(function ($history) {
                return $history->history_date ?: $history->tglregistrasi;
            })
            ->values();

        $result['length']   = count($data);
        $result['detail']   = $data;
        $result['is_owner'] = $isOwner;
        $result['as']       = '@aditwiran19@gmail.com';

        return $this->respond($result);
    }
}
