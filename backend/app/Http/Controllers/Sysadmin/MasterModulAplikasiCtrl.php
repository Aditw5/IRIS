<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Standar\MapObjekModulAplikasiToModulAplikasi;
use App\Models\Standar\ModulAplikasi;
use App\Models\Standar\ObjekModulAplikasi;
use App\Traits\Valet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterModulAplikasiCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterModulAplikasi(Request $r)
    {
        $data  = DB::table('modulaplikasi_s')
            ->select(
                'id',
                'statusenabled',
                'modulaplikasi',
                'namaexternal',
                'reportdisplay',
                'kdmodulaplikasi',
            )
            ->where('kdprofile', $this->kdProfile)
            ->where('reportdisplay', '=', 'Modul');
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('id', '=',  $r['id']);
        }
        if (isset($r['modulaplikasi']) && $r['modulaplikasi'] != '') {
            $data = $data->where('modulaplikasi', 'ilike', '%' . $r['modulaplikasi'] . '%');
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '' && $r['statusenabled'] == 'true') {
            $data = $data->where('statusenabled', '=', $r['statusenabled']);
        }
        if (isset($r['_total']) && $r['_total'] != '') {
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }


        $data = $data->orderBy('id');
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function masterObjekModulAplikasi(Request $r)
    {
        $data  = DB::table('objekmodulaplikasi_s')
            ->select(
                'id',
                'statusenabled',
                'objekmodulaplikasi',
                'alamaturlform',
                'keterangan',
                'fungsi',
                'reportdisplay',
                'kdobjekmodulaplikasihead',
            )
            ->where('kdprofile', $this->kdProfile);
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('id', '=',  $r['id']);
        }
        if (isset($r['objekmodulaplikasi']) && $r['objekmodulaplikasi'] != '') {
            $data = $data->where('objekmodulaplikasi', 'ilike', '%' . $r['objekmodulaplikasi'] . '%');
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '' && $r['statusenabled'] == 'true') {
            $data = $data->where('statusenabled', '=', $r['statusenabled']);
        }
        if (isset($r['_total']) && $r['_total'] != '') {
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }

        $data = $data->orderByDesc('objekmodulaplikasi');
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }


    public function saveModulAplikasi(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Modul Aplikasi

            $PSN =  $r['modulaplikasi'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new ModulAplikasi(), 'id', $this->kdProfile); //$this->Uuid4();
                $dataPS = new ModulAplikasi();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = ModulAplikasi::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->kdmodulaplikasi = $id;
            $dataPS->kdmodulaplikasihead =  $PSN['kdmodulaplikasihead'];
            $dataPS->modulaplikasi =  $PSN['modulaplikasi'];
            $dataPS->namaexternal  =  $PSN['modulaplikasi'];
            $dataPS->reportdisplay =  $PSN['reportdisplay'];
            $dataPS->statusenabled =  $PSN['statusenabled'];
            $dataPS->save();
            //endregion

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
                "result"  => $e->getMessage() . ' ' . $e->getLine()

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
    public function saveObjekModulAplikasi(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Objek Modul Aplikasi

            $PSN =  $r['objekmodulaplikasi'];
            if ($PSN['id'] == '') {
                $id = $this->Uuid4();
                $dataPS = new ObjekModulAplikasi();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = ObjekModulAplikasi::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->objekmodulaplikasi =  $PSN['objekmodulaplikasi'];
            $dataPS->namaexternal  =  $PSN['objekmodulaplikasi'];
            $dataPS->reportdisplay =  $PSN['objekmodulaplikasi'];
            $dataPS->fungsi  =  $PSN['fungsi'];
            $dataPS->nourut  =  $PSN['nourut'];
            $dataPS->keterangan  =  $PSN['keterangan'];
            $dataPS->statusenabled =  $PSN['statusenabled'];
            $dataPS->alamaturlform  =  $PSN['alamaturlform'];


            $dataPS->save();
            //endregion

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
    public function deleteModulAplikasi(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = ModulAplikasi::where('id', $r['id'])
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
    public function deleteObjekModulAplikasi(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = ObjekModulAplikasi::where('id', $r['id'])
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
    public function masterModulAplikasiHead(Request $request)
    {
        $data = DB::table('modulaplikasi_s as ma')
            ->where('ma.statusenabled', true)
            ->where('ma.kdprofile', $this->kdProfile)
            ->orderBy('ma.id')
            ->whereNotNull('ma.kdmodulaplikasihead');
        if (isset($request['id'])) {
            $data = $data->where('ma.kdmodulaplikasihead', $request['id']);
        }
        $data = $data->get();
        return $this->respond($data);
    }
    public function masterMenuObjek(Request $request)
    {
        $dataRaw = DB::table('objekmodulaplikasi_s as oma')
            ->join('mapobjekmodulaplikasitomodulaplikasi_s as acdc', 'acdc.objekmodulaplikasiid', '=', 'oma.id')
            ->join('modulaplikasi_s as ma', 'ma.id', '=', 'acdc.modulaplikasiid')
            ->where('oma.statusenabled', true)
            ->where('ma.statusenabled', true)
            ->where('acdc.statusenabled', true)
            ->where('ma.kdprofile', $this->kdProfile)
            ->where('acdc.modulaplikasiid', $request['id'])
            ->select(
                'oma.id as key',
                'oma.kdobjekmodulaplikasihead',
                'oma.objekmodulaplikasi as label',
                'acdc.modulaplikasiid',
                'oma.nourut',
                'oma.kodeexternal',
                'oma.alamaturlform',
                'oma.keterangan',
                'oma.fungsi'
            )
            ->orderBy('oma.nourut');
        $dataRaw = $dataRaw->get();
        $dataraw3 = [];
        foreach ($dataRaw as $dataRaw2) {
            if ($dataRaw2->kodeexternal == 'H') {
                $dataraw3[] = array(
                    'key' => $dataRaw2->key,
                    'label' => $dataRaw2->label,
                    'data' => $dataRaw2,
                    'nourut' => $dataRaw2->nourut,
                    'parent_id' => 0,
                    'icon' => 'pi pi-fw pi-folder',
                );
            } else {
                if ($dataRaw2->kodeexternal != 'H') {
                    $dataraw3[] = array(
                        'key' => $dataRaw2->key,
                        'label' => $dataRaw2->label,
                        'data' => $dataRaw2,
                        'nourut' => $dataRaw2->nourut,
                        'parent_id' => $dataRaw2->kdobjekmodulaplikasihead,
                        'icon' => 'pi pi-fw pi-folder',
                    );
                } else {
                    if ($dataRaw2->modulaplikasiid == $request['id']) {
                        $dataraw3[] = array(
                            'key' => $dataRaw2->key,
                            'label' => $dataRaw2->label,
                            'data' => $dataRaw2,
                            'nourut' => $dataRaw2->nourut,
                            'parent_id' => $dataRaw2->kdobjekmodulaplikasihead,
                            'icon' => 'pi pi-fw pi-folder',
                        );
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
                $id = $element['key'];
                $parent_id = $element['parent_id'];

                $elements[$id] = &$element;
                if (isset($elements[$parent_id])) {
                    $elements[$parent_id]['children'][] = &$element;
                } else {
                    if ($parent_id <= 10) {
                        $tree[] = &$element;
                    }
                }
                //}
            }
            return $tree;
        }
        $res['data'] = $dataRaw;
        $res['tree'] = recursiveElements($data);
        return $this->respond($res);
    }
    public function saveObjekModulAplikasiMap(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();
        // try {
            if ($request['id'] == '') {
                $objekmodulaplikasi = new ObjekModulAplikasi();
                $newID =  $this->SEQUENCE_MASTER(new ObjekModulAplikasi(), 'id', $this->kdProfile);
                $objekmodulaplikasi->id = $newID;
                $objekmodulaplikasi->kdprofile = $kdProfile;
                $objekmodulaplikasi->statusenabled = true;
            } else {
                $objekmodulaplikasi = ObjekModulAplikasi::where('id', $request['id'])->first();
                MapObjekModulAplikasiToModulAplikasi::where('objekmodulaplikasiid', $objekmodulaplikasi->id)->update([
                    'statusenabled' => false
                ]);
                $newID = $objekmodulaplikasi->id;
            }

            $objekmodulaplikasi->kodeexternal = is_null($request['kdobjekmodulaplikasihead']) ? 'H' : null;
            $objekmodulaplikasi->norec = substr($this->Uuid4(), 0, 32);
            $objekmodulaplikasi->fungsi = $request['fungsi'];
            $objekmodulaplikasi->kdobjekmodulaplikasi = $newID;
            $objekmodulaplikasi->keterangan = $request['keterangan'];
            $objekmodulaplikasi->objekmodulaplikasi = $request['objekmodulaplikasi'];
            $objekmodulaplikasi->nourut = $request['nourut'];
            $objekmodulaplikasi->kdobjekmodulaplikasihead = $request['kdobjekmodulaplikasihead'];
            $objekmodulaplikasi->alamaturlform = $request['alamaturlform'];
            $objekmodulaplikasi->save();


            $newData = new MapObjekModulAplikasiToModulAplikasi();
            $newId2 = $this->SEQUENCE_MASTER(new MapObjekModulAplikasiToModulAplikasi(), 'id', $this->kdProfile);
            $newData->id = $newId2;
            $newData->kdprofile = $kdProfile;
            $newData->statusenabled = true;
            $newData->norec = substr($this->Uuid4(), 0, 32);
            $newData->modulaplikasiid = $request['modulaplikasiid'];
            $newData->objekmodulaplikasiid = $newID;
            $newData->save();



            DB::commit();
            $transMessage = "Sukses ";
            $result = array(
                "status" => 200,
                "result" => array(
                    "as" => 'aditwiran19@gmail.com',
                ),
            );
        // } catch (Exception $e) {
        //     DB::rollback();
        //     $transMessage = "Simpan Gagal";
        //     $result = array(
        //         "status" => 400,
        //         "result" => array(
        //             "as" => 'aditwiran19@gmail.com',
        //             "ex" => $e->getMessage() . ' ' . $e->getLine(),
        //         ),
        //     );
        // }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
    public function hapusObjekModulAplikasiMap(Request $request)
    {
        $kdProfile = $this->kdProfile;
        DB::beginTransaction();
        try {

            ObjekModulAplikasi::where('id', $request['id'])->update([
                'statusenabled' => false
            ]);
            MapObjekModulAplikasiToModulAplikasi::where('objekmodulaplikasiid', $request['id'])->update([
                'statusenabled' => false
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
    public function lastNourutObjekModul(Request $r)
    {

        $dataPS = ObjekModulAplikasi::where('statusenabled',true)->orderByDesc('nourut')->first();

        return $this->respond($dataPS);
    }
}
