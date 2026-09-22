<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Ruangan;
use App\Models\Standar\LoginUser;
use App\Models\Standar\MapLoginUserToRuangan;
use App\Models\Standar\ModulAplikasi;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterMapLoginUserToRuanganCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterMapLoginUserToRuangan(Request $r)
    {
        $data  = DB::table('maploginusertoruangan_s as mp')
        ->join('loginuser_s as pr', 'mp.objectloginuserfk', '=', 'pr.id')
        ->join('ruangan_m as ru', 'mp.objectruanganfk', '=', 'ru.id')
        ->select(
            'mp.id',
            'mp.objectloginuserfk',
            'mp.objectruanganfk',
            'pr.namauser',
            'ru.namaruangan',
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
        if (isset($r['namaruangan']) && $r['namaruangan'] != '') {
            $data = $data->where('pr.namaruangan', 'ilike', '%'.$r['namaruangan'].'%');
        }
        if (isset($r['rows']) && $r['rows'] != '') {
            $data = $data->limit($r['rows']);
        }
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function saveMapLoginUserToRuangan(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Login User To Ruangan

            $delete = MapLoginUserToRuangan::where('objectloginuserfk', $r['objectloginuserfk'])
            ->update(['statusenabled'=> false]);
            foreach($r['detail'] as $item){
                $dataPS = new MapLoginUserToRuangan();
                $dataPS->id = $this->SEQUENCE_MASTER(new MapLoginUserToRuangan(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
                $dataPS->objectloginuserfk =  $r['objectloginuserfk'];
                $dataPS->objectruanganfk = $item['ruanganfk'];
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
                "result"  => null

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }

    public function deleteMapLoginUserToRuangan(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = MapLoginUserToRuangan::where('id', $r['id'])
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
    public function masterMapLoginUserToRuangandropdown(Request $r)
    {
 
        $res['namaruangan'] = Ruangan::mine()->get();
        $res['namauser'] = LoginUser::mine()->get();


        return $this->respond($res);
    }
    
}

