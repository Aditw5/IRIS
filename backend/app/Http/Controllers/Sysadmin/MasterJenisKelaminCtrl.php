<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisKelamin;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterJenisKelaminCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterJenisKelamin(Request $r)
    {
        $data  = DB::table('jeniskelamin_m')
            ->select(
                'id',
                'statusenabled',
                'jeniskelamin',
            )
            ->where('kdprofile', $this->kdProfile);
            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('id', '=',  $r['id']);
            }
            if (isset($r['jeniskelamin']) && $r['jeniskelamin'] != '') {
                $data = $data->where('jeniskelamin', 'ilike', '%' . $r['jeniskelamin'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
                $data = $data->where('statusenabled', '=', $r['statusenabled']);
            }
            if (isset($r['_total']) && $r['_total'] != '') {
            }
    
        $data = $data->orderByDesc('jeniskelamin');
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

    public function saveJenisKelamin(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save jeniskelamin

            $PSN =  $r['jeniskelamin'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new JenisKelamin(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new JenisKelamin();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = JenisKelamin::where('id', $PSN['id'])->first();
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->jeniskelamin =  $PSN['jeniskelamin'];
            $dataPS->namaexternal =  $PSN['jeniskelamin'];
            $dataPS->reportdisplay =  $PSN['jeniskelamin'];
           
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
    public function deleteJenisKelamin(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = JenisKelamin::where('id', $r['id'])
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
