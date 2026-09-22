<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\KelompokUser;
use App\Models\Master\Pegawai;
use App\Models\Standar\KelompokUser as StandarKelompokUser;
use App\Models\Standar\LoginUser as StandarLoginUser;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterTambahLoginUserCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterTambahLoginUser(Request $r)
    {
        $statusenabled =$r->statusenabled;
        $data  = DB::table('loginuser_s as ru')
            ->join('kelompokuser_s as dp', 'ru.objectkelompokuserfk', '=', 'dp.id')
            ->join('pegawai_m as pg', 'ru.objectpegawaifk', '=', 'pg.id')
            ->select(
                'ru.id',
                'ru.statusenabled',
                'ru.namauser',
                'dp.kelompokuser',
                'ru.objectpegawaifk',
                'ru.objectkelompokuserfk',
                'pg.namalengkap',
            )
            ->where('ru.kdprofile', $this->kdProfile)
            ->limit($r['rows'])
            ->when($statusenabled, function ($query) use ($statusenabled) {
                return $query->where('ru.statusenabled', $statusenabled);
            });
            
        if (isset($r['namapegawai']) && $r['namapegawai'] != '') {
            $data = $data->where('pg.namalengkap', 'ilike', '%' . $r['namapegawai'] . '%');
        }
        if (isset($r['kelompokuser']) && $r['kelompokuser'] != '') {
            $data = $data->where('dp.id', '=', $r['kelompokuser']);
        }
        if (isset($r['objectkelompokuserfk']) && $r['objectkelompokuserfk'] != '') {
            $data = $data->where('ru.objectkelompokuserfk', '=', $r['objectkelompokuserfk'] );
        }
        if (isset($r['kelompokuser']) && $r['kelompokuser'] != '') {
            $data = $data->where('dp.kelompokuser', 'ilike', '%'.$r['kelompokuser'].'%');
        }
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }
    public function masterTambahLoginUserDropdown(Request $r)
    {
        $res['namalengkap'] = Pegawai::mine()->get();
        $res['kelompokuser'] = StandarKelompokUser::mine()->get();

        return $this->respond($res);
    }
    public function saveLoginUser(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Login User

            $PSN =  $r['loginuser'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new StandarLoginUser(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new StandarLoginUser();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = StandarLoginUser::where('id', $PSN['id'])
                    // ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->namauser =  $PSN['namauser'];
            $dataPS->objectpegawaifk =  $PSN['objectpegawaifk'];
            $dataPS->objectkelompokuserfk =  $PSN['objectkelompokuserfk'];
            $dataPS->katasandi = $this->hashing_password( $PSN['katasandi']);
            $dataPS->statuslogin = 1;
            $dataPS->statusenabled = true;
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
                "result"  =>  $e->getMessage() . ' ' . $e->getLine()
            );
        }
        return $this->respond($result, $result['status'], $transMessage);
    }
    public function deleteLoginUser(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = StandarLoginUser::where('id', $r['id'])
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
