<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisPendidikan;
use App\Models\Master\Pendidikan;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterPendidikanCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterPendidikan(Request $r)
    {
        $data  = DB::table('pendidikan_m as pe')
        ->join('jenispendidikan_m as jp', 'pe.objectjenispendidikanfk', '=', 'jp.id')
        ->select(
            'pe.id',
            'pe.statusenabled',
            'pe.pendidikan',
            'jp.jenispendidikan',
            'pe.objectjenispendidikanfk',
            'pe.namaexternal',
            'pe.reportdisplay'
        )
        ->where('pe.kdprofile', $this->kdProfile);

    if (isset($r['id']) && $r['id'] != '') {
        $data = $data->where('pe.id', '=',  $r['id']);
    }
    
    if (isset($r['pendidikan']) && $r['pendidikan'] != '') {
        $data = $data->where('pe.pendidikan', 'ilike', '%' . $r['pendidikan'] . '%');
    }
    if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
        $data = $data->where('pe.statusenabled', '=', $r['statusenabled']);
    }

    $data = $data->orderByDesc('pe.pendidikan');
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
public function masterPendidikandropdown(Request $r)
{
    $res['jenispendidikan'] = JenisPendidikan::mine()->get();

    return $this->respond($res);
}

    public function savePendidikan(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Pendidikan

            $PSN =  $r['pendidikan'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Pendidikan(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new Pendidikan();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Pendidikan::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->pendidikan =  $PSN['pendidikan'];
            $dataPS->objectjenispendidikanfk =  $PSN['objectjenispendidikanfk'];
            $dataPS->namaexternal =  $PSN['pendidikan'];
            $dataPS->reportdisplay =  $PSN['pendidikan'];

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
    public function deletePendidikan(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Pendidikan::where('id', $r['id'])
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
