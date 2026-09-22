<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Pekerjaan;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterPekerjaanCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterPekerjaan(Request $r)
    {
        $data  = DB::table('pekerjaan_m')
            ->select(
                'id',
                'statusenabled',
                'pekerjaan',
                'namaexternal',
                'reportdisplay',
            )
            ->where('kdprofile', $this->kdProfile);
            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('id', '=',  $r['id']);
            }
            if (isset($r['pekerjaan']) && $r['pekerjaan'] != '') {
                $data = $data->where('pekerjaan', 'ilike', '%' . $r['pekerjaan'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '' && $r['statusenabled'] == 'true') {
                $data = $data->where('statusenabled', '=', $r['statusenabled']);
            }
            if (isset($r['_total']) && $r['_total'] != '') {
            }
    
        $data = $data->orderByDesc('pekerjaan');
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

    public function savePekerjaan(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save pekerjaan

            $PSN =  $r['pekerjaan'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Pekerjaan(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new Pekerjaan();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Pekerjaan::where('id', $PSN['id'])->first();
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->pekerjaan =  $PSN['pekerjaan'];
            $dataPS->namaexternal =  $PSN['pekerjaan'];
            $dataPS->reportdisplay =  $PSN['pekerjaan'];
           
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
    public function deletePekerjaan(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Pekerjaan::where('id', $r['id'])
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
