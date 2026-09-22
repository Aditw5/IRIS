<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Master\Pegawai;
use App\Traits\Valet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Master\ListNotif;
// use App\Models\Master\Printer;
use App\Models\Standar\LoginUser;
use Illuminate\Support\Facades\Http;

class GeneralCtrl extends Controller
{
    use Valet;

    public function showFileGeneral(Request $r)
    {

        return response()->fShow($r['path'], 'ftp');
    }
    public function saveLoggingAll(Request $r)
    {
        try {
            $this->LOGGING(
                $r['jenislog'],
                $r['noreff'],
                $r['referensi'],
                $r['keterangan']
            );

            $transMessage = "Sukses";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        } catch (Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => $e->getMessage()

            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function getDataKdProfile(Request $request)
    {
        $dataLogin = $request->all();
        $idUser = $dataLogin['userData']['id'];
        $data = LoginUser::where('id', $idUser)->first();
        if (!empty($data)) {
            $idKdProfile = (int)$data->kdprofile;
            $Query = DB::table('profile_m')
                ->where('id', '=', $idKdProfile)
                ->first();
            $Profile = $Query;
            return (int)$Profile->id;
        } else {
            $data = Pasien::where('id', $idUser)->first();
            if (!empty($data)) {
                $idKdProfile = (int)$data->kdprofile;
                $Query = DB::table('profile_m')
                    ->where('id', '=', $idKdProfile)
                    ->first();
                $Profile = $Query;
                return (int)$Profile->id;
            } else {
                return null;
            }
        }
    }

    public function settingFixData($setting)
    {
        $set = $this->settingFix($setting);
        return $this->respondV2($set);
    }

    public function dropdownGeneral(Request $r, $table)
    {
        try {
            $search = $r['query'] ?? '';
            $data = DB::table($table);
            if (isset($r['select']) && $r['select'] != '') {
                if ($table == 'diagnosa_m') {
                    $data = $data->select(DB::raw("id as value,kddiagnosa || ' - ' || namadiagnosa as label"));
                } else if ($table == 'diagnosatindakan_m') {
                    $data = $data->select(DB::raw("id as value,kddiagnosatindakan || ' - ' || namadiagnosatindakan as label"));
                } else {
                    $select = [];
                    $exp = explode(',', $r['select']);
                    foreach ($exp as $items) {
                        if ($items == 'id') {
                            $items = $items . ' as value';
                        }
                        $select[] = $items;
                    }
                    if (isset($exp[1]) && $exp[1] != 'id') {
                        $select[] = $exp[1] . ' as label';
                        foreach ($select as $k => $sel) {
                            if ($sel == $exp[1]) {
                                array_splice($select, $k, 1);
                            }
                        }
                    }
                    $data = $data->select($select);
                }
            }
            if (isset($r['param_search']) && $r['param_search'] != '') {
                $where = [];
                $exp = explode(',', $r['param_search']);
                foreach ($exp as $items) {
                    $where[] = [$items, 'ILIKE', '%' . $search . '%'];
                }
                $data = $data->where($where);
            }
            if (isset($r['settingdatafix']) && $r['settingdatafix'] != '') {
                $exp = explode(',', $r['settingdatafix']);
                $arrayDataFix = $this->settingFix($exp[1]);
                if ($table == 'ruangan_m') {
                    if (count($exp) > 2) {
                        $arrayDataFix = $arrayDataFix . ',' . $this->settingFix($exp[2]);
                    }
                }
                $valueSet = explode(',', $arrayDataFix);
                $data = $data->whereIn($exp[0], $valueSet);
            }
            if (isset($r['kondisi']) && $r['kondisi'] != '') {
                $exp = explode(',', $r['kondisi']);
                $data = $data->where($exp[0], $exp[1]);
            }
            $data = $data->where('statusenabled', true);

            if (isset($r['orderby']) && $r['orderby'] != '') {
                $data = $data->orderBy($r['orderby']);
            }
            if (isset($r['limit']) && $r['limit'] != '') {
                $data = $data->limit($r['limit']);
            }
            $data = $data->get();
        } catch (Exception $e) {
            $data = $e->getMessage() . ' ' . $e->getLine();
        }

        return $this->respond($data);
    }

    // public function masterPrinter(Request $r)
    // {
    //     $data  = DB::table('printer_m')
    //         ->select(
    //             '*'

    //         )
    //         ->where('kdprofile', $this->kdProfile)
    //         ->where('statusenabled', true);
    //     if (isset($r['id']) && $r['id'] != '') {
    //         $data = $data->where('id', '=',  $r['id']);
    //     }
    //     if (isset($r['namaexternal']) && $r['namaexternal'] != '') {
    //         $data = $data->where('namaexternal', '=',  $r['namaexternal']);
    //     }
    //     if (isset($r['printerdefault']) && $r['printerdefault'] != '') {
    //         $data = $data->where('printerdefault', 'ilike', '%' . $r['printerdefault'] . '%');
    //     }
    //     if (isset($r['device']) && $r['device'] != '') {
    //         $data = $data->where('devicename', '=', $r['device']);
    //     }

    //     $data = $data->orderByDesc('id', 'desc');
    //     $data = $data->get();

    //     $res['data'] = $data;
    //     return $this->respond($res);
    // }

    // public function savePrinter(Request $r)
    // {
    //     DB::beginTransaction();
    //     try {

    //         if ($r['id'] == '') {
    //             $id = $this->SEQUENCE_MASTER(new Printer(), 'id', $this->kdProfile);
    //             $dataPS = new Printer();
    //             $dataPS->id = $id;
    //             $dataPS->kdprofile = (int)$this->kdProfile;
    //             $dataPS->statusenabled = true;
    //         } else {
    //             $dataPS = Printer::where('id', $r['id'])->first();
    //             $id =  $dataPS->id;
    //         }
    //         $dataPS->namaexternal =  $r['namaexternal'];
    //         $dataPS->printerdefault =  $r['printerdefault'];
    //         $dataPS->orientation =  $r['orientation'];
    //         $dataPS->keterangan =  $r['keterangan'] ? $r['keterangan'] : null;
    //         $dataPS->height =  $r['height'];
    //         $dataPS->width =  $r['width'];
    //         $dataPS->devicename =  isset($r['devicename']) ? $r['devicename'] : gethostname();
    //         $dataPS->save();


    //         $transStatus = 'true';
    //     } catch (\Exception $e) {
    //         $transStatus = 'false';
    //     }

    //     if ($transStatus == 'true') {
    //         $transMessage = "Sukses";
    //         DB::commit();

    //         $result = array(
    //             "status" => 200,
    //             "result" => array(
    //                 "data"  => $dataPS,
    //                 "as" => 'aditwiran19@gmail.com',
    //             ),
    //         );
    //     } else {
    //         $transMessage = "Simpan Gagal";
    //         DB::rollBack();

    //         $result = array(
    //             "status" => 400,
    //             "result"  => $e->getMessage()

    //         );
    //     }
    //     return $this->respond($result['result'], $result['status'], $transMessage);
    // }
    // public function deletePrinter(Request $r)
    // {
    //     DB::beginTransaction();
    //     try {

    //         $dataPS = Printer::where('id', $r['id'])
    //             ->update([
    //                 'statusenabled' => false
    //             ]);

    //         $transStatus = 'true';
    //     } catch (\Exception $e) {
    //         $transStatus = 'false';
    //     }

    //     if ($transStatus == 'true') {
    //         $transMessage = "Sukses";
    //         DB::commit();

    //         $result = array(
    //             "status" => 200,
    //             "result" => array(
    //                 "data"  => $dataPS,
    //                 "as" => 'aditwiran19@gmail.com',
    //             ),
    //         );
    //     } else {
    //         $transMessage = "Hapus Gagal";
    //         DB::rollBack();

    //         $result = array(
    //             "status" => 400,
    //             "result"  => null

    //         );
    //     }
    //     return $this->respond($result['result'], $result['status'], $transMessage);
    // }

    public function getLogUser(Request $r)
    {
        $kdProfile      = (int)$this->getDataKdProfile($r);
        $tglAwal = date('Y-m-d 00:00:00', strtotime($r->tglAwal));
        $tglAkhir = date('Y-m-d 23:59:59', strtotime($r->tglAkhir));
        $nama           = $r->nama;
        $keterangan     = $r->keterangan;
        $rows           = $r->rows;
        $data = DB::table('logginguser_t as lo')
            ->select(
                'lo.norec',
                'lo.namauser as username',
                'lo.namapegawai as namalengkap',
                'lo.tanggal as tanggal',
                'lo.jenislog as jenis',
                'lo.keterangan as keterangan',
            )
            ->where('lo.kdprofile', $kdProfile)
            ->whereBetween('lo.tanggal', [$tglAwal, $tglAkhir]);
        if ($nama !== '' && $nama !== 'undefined') {
            $data->whereRaw('LOWER(lo.namapegawai) LIKE ?', ["%{$nama}%"]);
        }

        if ($keterangan !== '' && $keterangan !== 'undefined') {
            $data->whereRaw('LOWER(lo.keterangan) LIKE ?', ["%{$keterangan}%"]);
        }

        $logData = $data
            ->orderByDesc('lo.tanggal')
            ->limit($rows)
            ->get();

        return $this->respond([
            'data' => $logData,
            'message' => 'aditwiran19@gmail.com',
        ]);
    }

    public function storeNotif(Request $request)
    {
        DB::beginTransaction();
        try {
            $cek = null;

            if ($request->method === 'save') {
                $tgl = $request->tgl ? $request->tgl : now();

                $da = new ListNotif();
                $da->norec = $this->Uuid4();
                $da->norec_trans = $request->norec;
                $da->judul = $request->judul;
                $da->jenis = $request->jenis;
                $da->pegawaifk = $request->idPegawai;
                $da->namapegawai = $request->namapegawai;
                $da->keterangan = $request->pesanNotifikasi;
                $da->tgl = $tgl;
                $da->tgl_string = $request->tgl_string ?? date('d-m-Y H:i', strtotime($tgl));
                $da->urlform = $request->urlForm ?? null;
                $da->params = isset($request->params) ? json_encode($request->params) : null;
                $da->dataarray = isset($request->dataArray) ? json_encode($request->dataArray) : null;
                $da->statusenabled = true;
                $da->isread = false;
                $da->save();

                $cek = $da;

                $this->sendRealtimeNotification([
                    'norec' => $da->norec,
                    'norec_trans' => $da->norec_trans,
                    'judul' => $da->judul,
                    'jenis' => $da->jenis,
                    'idPegawai' => $da->pegawaifk,
                    'namapegawai' => $da->namapegawai,
                    'pesanNotifikasi' => $da->keterangan,
                    'tgl' => $da->tgl,
                    'tgl_string' => $da->tgl_string,
                    'urlForm' => $da->urlform,
                    'params' => $request->params ?? null,
                    'dataArray' => $request->dataArray ?? null,
                ]);
            }

            if ($request->method === 'delete') {
                $cek = ListNotif::where('norec_trans', $request->norec)->delete();
            }

            if ($request->method === 'get') {
                $idPegawai = $this->getPegawaiId();

                if (empty($idPegawai)) {
                    throw new Exception('Pegawai login tidak ditemukan');
                }

                $cek = ListNotif::where('statusenabled', true)
                    ->where('pegawaifk', $idPegawai)
                    ->orderBy('tgl', 'desc')
                    ->get();
            }

            if ($request->method === 'mark-read') {
                $cek = $this->deactivateNotification(
                    $request->norec,
                    $this->getPegawaiId(),
                    true
                );
            }

            if ($request->method === 'delete-item') {
                $cek = $this->deactivateNotification(
                    $request->norec,
                    $this->getPegawaiId()
                );
            }

            $transStatus = true;
        } catch (Exception $e) {
            $transStatus = false;
            $errMsg = $e->getMessage() . ' ' . $e->getLine();
        }

        if ($transStatus) {
            DB::commit();
            return $this->respond([
                'data' => $cek
            ], 200, 'Sukses');
        } else {
            DB::rollBack();
            return $this->respond($errMsg, 400, 'Simpan Gagal');
        }
    }

    protected function deactivateNotification($norec, $pegawaiId, $markRead = false)
    {
        if (empty($pegawaiId)) {
            throw new Exception('Pegawai login tidak ditemukan');
        }

        $notif = ListNotif::where('norec', $norec)
            ->where('pegawaifk', $pegawaiId)
            ->first();

        if (empty($notif)) {
            throw new Exception('Notifikasi tidak ditemukan');
        }

        $notif->statusenabled = false;
        if ($markRead) {
            $notif->isread = true;
        }
        $notif->save();

        $this->sendRealtimeNotification([
            'action' => 'deleted',
            'norec' => $notif->norec,
            'idPegawai' => $pegawaiId,
        ]);

        return 1;
    }

    protected function sendRealtimeNotification(array $payload)
    {
        $socketUrl = env('SOCKET_SERVER_URL');
        $socketSecret = env('SOCKET_SERVER_SECRET');

        if (empty($socketUrl) || empty($socketSecret)) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeaders([
                    'x-socket-secret' => $socketSecret,
                    'Accept' => 'application/json',
                ])
                ->post(rtrim($socketUrl, '/') . '/emit-notification', $payload);
        } catch (Exception $e) {
            // sengaja diabaikan agar simpan notif DB tetap sukses walau socket gagal
        }
    }
}
