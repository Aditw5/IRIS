<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Suku;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterSukuCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterSuku(Request $r)
    {
        $data  = DB::table('suku_m')
            ->select(
                'id',
                'statusenabled',
                'suku',
                'namaexternal',
                'reportdisplay',
                'norec',
            )
            ->where('kdprofile', $this->kdProfile);

            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('id', '=',  $r['id']);
            }
            if (isset($r['suku']) && $r['suku'] != '') {
                $data = $data->where('suku', 'ilike', '%' . $r['suku'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
                $data = $data->where('statusenabled', '=', $r['statusenabled']);
            }

        $data = $data->orderByDesc('suku');
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

    public function saveSuku(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Suku

            $PSN =  $r['suku'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Suku(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new Suku();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Suku::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->suku =  $PSN['suku'];
            $dataPS->namaexternal = $PSN['namaexternal'];
            $dataPS->reportdisplay = $PSN['reportdisplay'];
            $dataPS->statusenabled = $PSN['statusenabled'];
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
    public function deleteSuku(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Suku::where('id', $r['id'])
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
