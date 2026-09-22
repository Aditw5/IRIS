<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Provinsi;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterProvinsiCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterProvinsi(Request $r)
    {
        $data  = DB::table('propinsi_m')
            ->select(
                'id',
                'statusenabled',
                'namapropinsi',
                'namaexternal',
                'reportdisplay',
                'kdpropinsi',
                'qpropinsi',
                'kdmap',
                'kodebpjs'
            )
            ->where('kdprofile', $this->kdProfile)
            ->where('statusenabled', true);

        $data = $data->orderByDesc('id');
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }

    public function saveProvinsi(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Provinsi

            $PSN =  $r['namapropinsi'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Provinsi(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new Provinsi();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Provinsi::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->namapropinsi =  $PSN['namapropinsi'];
            $dataPS->kodebpjs =  $PSN['kodebpjs'];
            $dataPS->namaexternal =  $PSN['namapropinsi'];
            $dataPS->reportdisplay =  $PSN['reportdisplay'];

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
    public function deleteProvinsi(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Provinsi::where('id', $r['id'])
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
