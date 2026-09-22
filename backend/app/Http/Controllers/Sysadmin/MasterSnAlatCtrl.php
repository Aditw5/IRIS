<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\SnAlat;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterSnAlatCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterSnAlat(Request $r)
    {
        $data  = DB::table('serialnumber_m')
            ->select(
                'id',
                'statusenabled',
                'namaserialnumber',
            )
            ->where('kdprofile', $this->kdProfile);
            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('id', '=',  $r['id']);
            }
            if (isset($r['namaserialnumber']) && $r['namaserialnumber'] != '') {
                $data = $data->where('namaserialnumber', 'ilike', '%' . $r['namaserialnumber'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
                $data = $data->where('statusenabled', '=', $r['statusenabled']);
            }
            if (isset($r['_total']) && $r['_total'] != '') {
            }
    
        $data = $data->orderByDesc('namaserialnumber');
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

    public function saveSnAlat(Request $r)
    {
        DB::beginTransaction();
        try {
            $PSN =  $r['namaserialnumber'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new SnAlat(),'id',$this->kdProfile);
                $dataPS = new SnAlat();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = SnAlat::where('id', $PSN['id'])->first();
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->namaserialnumber =  $PSN['namaserialnumber'];
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
    public function deleteSnAlat(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = SnAlat::where('id', $r['id'])
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
