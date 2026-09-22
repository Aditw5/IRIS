<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Jabatan;
use App\Models\Master\JenisJabatan;
use App\Models\Master\KelompokJabatan;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterJabatanCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterJabatan(Request $r)
    {
        $data  = DB::table('jabatan_m as pe')
        ->leftjoin('jenisjabatan_m as jp', 'jp.id' , '=','pe.objectjenisjabatanfk' )
        ->leftjoin('kelompokjabatan_m as pp', 'pp.id' , '=', 'pe.objectkelompokjabatanfk' )
        ->select(
            'pe.id',
            'pe.statusenabled',
            'pe.namajabatan',
            'jp.jenisjabatan',
            'pe.objectjenisjabatanfk',
            'pe.objectkelompokjabatanfk',
            'pe.usiapensiun',
            'pp.namakelompokjabatan'
        )
        ->where('pe.kdprofile', $this->kdProfile);
    if (isset($r['id']) && $r['id'] != '') {
        $data = $data->where('pe.id', '=',  $r['id']);
    }
    if (isset($r['namajabatan']) && $r['namajabatan'] != '') {
        $data = $data->where('pe.namajabatan', 'ilike', '%' . $r['namajabatan'] . '%');
    }
    if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
        $data = $data->where('pe.statusenabled', '=', $r['statusenabled']);
    }
    if (isset($r['objectjenisjabatanfk']) && $r['objectjenisjabatanfk'] != '') {
        $data = $data->where('pe.objectjenisjabatanfk', '=', $r['objectjenisjabatanfk'] );
    }
    if (isset($r['_total']) && $r['_total'] != '') {
    }
    // if (isset($r['offset']) && $r['offset'] != '') {
    //     $data = $data->offset($r['offset']);
    // }
    // if (isset($r['limit']) && $r['limit'] != '') {
    //     $data = $data->limit($r['limit']);
    // }

    $data = $data->orderByDesc('pe.namajabatan');
    $data = $data->get();

    foreach ($data as $d) {
        $d->statusenabled;
        $d->status = 'Aktif';
        $d->status_c = 'purple';
        if ($d->statusenabled != 'false') {
            $d->status = 'Nonaktif';
            $d->status_c = 'danger';
        }

    }

    $res['data'] = $data;
    return $this->respond($res);
}

public function masterJabatandropdown(Request $r)
{
    $res['jenisjabatan'] = JenisJabatan::mine()->get();
    $res['namakelompokjabatan'] = KelompokJabatan::mine()->get();

    return $this->respond($res);
}

    public function saveJabatan(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Jabatan

            $PSN =  $r['namajabatan'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Jabatan(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new Jabatan();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Jabatan::where('id', $PSN['id'])->first();
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->namajabatan =  $PSN['namajabatan'];
            $dataPS->objectjenisjabatanfk =  $PSN['objectjenisjabatanfk'];
            $dataPS->objectkelompokjabatanfk =  $PSN['objectkelompokjabatanfk'];
            $dataPS->usiapensiun =  $PSN['usiapensiun'];
           
            $dataPS->masajabatan =  $PSN['masajabatan'];

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
    public function deleteJabatan(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Jabatan::where('id', $r['id'])
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
