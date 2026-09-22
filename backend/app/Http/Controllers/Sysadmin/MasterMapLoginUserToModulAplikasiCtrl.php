<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Standar\LoginUser;
use App\Models\Standar\MapLoginUserToModulAplikasi;
use App\Models\Standar\ModulAplikasi;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterMapLoginUserToModulAplikasiCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterMapLoginUserToModulAplikasi(Request $r)
    {
        $data  = DB::table('maploginusertomodulaplikasi_s as mp')
        ->join('loginuser_s as pr', 'mp.objectloginuserfk', '=', 'pr.id')
        ->join('modulaplikasi_s as ru', 'mp.objectmodulaplikasifk', '=', 'ru.id')
        ->select(
            'mp.id',
            'mp.objectloginuserfk',
            'mp.objectmodulaplikasifk',
            'pr.namauser',
            'ru.modulaplikasi',
        )
            ->where('mp.statusenabled', true)
            ->where('mp.kdprofile', $this->kdProfile);
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('mp.id', '=',  $r['id']);
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('mp.statusenabled', '=',  $r['statusenabled']);
        }
        if (isset($r['objectloginuserfk']) && $r['objectloginuserfk'] != '') {
            $data = $data->where('mp.objectloginuserfk', '=', $r['objectloginuserfk'] );
        }
        if (isset($r['_total']) && $r['_total'] != '') {
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }
        if (isset($r['modulaplikasi']) && $r['modulaplikasi'] != '') {
            $data = $data->where('pr.modulaplikasi', 'ilike', '%'.$r['modulaplikasi'].'%');
        }
        if (isset($r['rows']) && $r['rows'] != '') {
            $data = $data->limit($r['rows']);
        }
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function saveMapLoginUserToModulAplikasi(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Login User To Modul Aplikasi

            $delete = MapLoginUserToModulAplikasi::where('objectloginuserfk', $r['objectloginuserfk'])
            ->update(['statusenabled'=> false]);
            foreach($r['detail'] as $item){
                $dataPS = new MapLoginUserToModulAplikasi();
                $dataPS->id = $this->SEQUENCE_MASTER(new MapLoginUserToModulAplikasi(),'id',$this->kdProfile);// $this->Uuid4();
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
                $dataPS->objectloginuserfk =  $r['objectloginuserfk'];
                $dataPS->objectmodulaplikasifk = $item['modulaplikasifk'];
                $dataPS->save();
            }

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
                "result"  => $e->getMessage()

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function deleteMapLoginUserToModulAplikasi(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = MapLoginUserToModulAplikasi::where('id', $r['id'])
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
    public function masterMapLoginUserToModulAplikasidropdown(Request $r)
    {

        $res['modulaplikasi'] = ModulAplikasi::mine()->where('reportdisplay','Menu')->get();
        $res['namauser'] = LoginUser::mine()->get();


        return $this->respond($res);
    }

    // public function listRuangan(Request $r)
    // {
    //     $res['namaruangan'] = Ruangan::mine();
    //     if (isset($r['rufk']) && $r['rufk'] != '') {
    //         $res['namaruangan'] = $res['namaruangan']->where('objectdepartemenfk', $r['rufk']);
    //     }
    //     if (isset($r['objectdepartemenfk']) && $r['objectdepartemenfk'] != '') {
    //         $res['namaruangan'] = $res['namaruangan']->where('objectdepartemenfk', $r['objectdepartemenfk']);
    //     }
    //     $res['namaruangan'] = $res['namaruangan']
    //         ->orderby('namaruangan')
    //         ->get();
    //     return $this->respond($res);
    // }


}

