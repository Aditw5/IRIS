<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\KelompokUser;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterKelompokUserCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterKelompokUser(Request $r)
    {
        $data  = DB::table('kelompokuser_s')
            ->select(
                'id',
                'statusenabled',
                'kelompokuser',
                'namaexternal',
                'reportdisplay',
                'kdkelompokuser'
            )
            ->where('kdprofile', $this->kdProfile);
            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('id', '=',  $r['id']);
            }
            if (isset($r['kelompokuser']) && $r['kelompokuser'] != '') {
                $data = $data->where('kelompokuser', 'ilike', '%' . $r['kelompokuser'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '' && $r['statusenabled'] == 'true') {
                $data = $data->where('statusenabled', '=', $r['statusenabled']);
            }
            if (isset($r['_total']) && $r['_total'] != '') {
            }
            if (isset($r['offset']) && $r['offset'] != '') {
                $data = $data->offset($r['offset']);
            }
            if (isset($r['limit']) && $r['limit'] != '') {
                $data = $data->limit($r['limit']);
            }

        $data = $data->orderByDesc('kelompokuser');
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function saveKelompokUser(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Kelompok User
            $delete = KelompokUser::where('id', $r['id'])
            ->update(['statusenabled'=> false]);
            $PSN =  $r['kelompokuser'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new KelompokUser(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new KelompokUser();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = KelompokUser ::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->kelompokuser =  $PSN['kelompokuser'];
            $dataPS->statusenabled =  $PSN['statusenabled'];
            $dataPS->namaexternal =  $PSN['reportdisplay'];
            $dataPS->reportdisplay =  $PSN['reportdisplay'];
            $dataPS->kdkelompokuser =  $PSN['kdkelompokuser'];
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
                "result"  => $e->getMessage() . ' '.$e->getLine()

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
    public function deleteKelompokUser(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = KelompokUser::where('id', $r['id'])
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
}
