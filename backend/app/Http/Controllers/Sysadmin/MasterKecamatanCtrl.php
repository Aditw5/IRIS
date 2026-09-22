<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Provinsi;
use App\Models\Master\Kecamatan;
use App\Models\Master\KotaKabupaten;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterKecamatanCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterKecamatan(Request $r)
    {
        $data  = DB::table('kecamatan_m as pe')
        ->leftjoin('kotakabupaten_m as jp', 'pe.objectkotakabupatenfk', '=', 'jp.id')
        ->leftjoin('propinsi_m as pp', 'pe.objectpropinsifk', '=', 'pp.id')
        ->select(
            'pe.id',
            'pe.statusenabled',
            'pe.namakecamatan',
            'jp.namakotakabupaten',
            'pe.objectpropinsifk',
            'pe.objectkotakabupatenfk',
            'pp.namapropinsi'
        )
        ->where('pe.kdprofile', $this->kdProfile)
        ->where('pe.statusenabled', true);

    if (isset($r['id']) && $r['id'] != '') {
        $data = $data->where('pe.id', '=',  $r['id']);
    }
    
    if (isset($r['namakecamatan']) && $r['namakecamatan'] != '') {
        $data = $data->where('pe.namakecamatan', 'ilike', '%' . $r['namakecamatan'] . '%');
    }
    if (isset($r['objectpropinsifk']) && $r['objectpropinsifk'] != '') {
        $data = $data->where('pe.objectpropinsifk', '=',  $r['objectpropinsifk']);
    }
    if (isset($r['objectkotakabupatenfk']) && $r['objectkotakabupatenfk'] != '') {
        $data = $data->where('pe.objectkotakabupatenfk', '=',  $r['objectkotakabupatenfk']);
    }


    $data = $data->orderByDesc('pe.namakecamatan');
    $data = $data->get();

    $res['data'] = $data;
    return $this->respond($res);
}
public function masterKecamatandropdown(Request $r)
{
    $res['namapropinsi'] = Provinsi::mine()->get();
    $res['namakotakabupaten'] = KotaKabupaten::mine()->get();

    return $this->respond($res);
}

    public function saveKecamatan(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Kecamatan

            $PSN =  $r['namakecamatan'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Kecamatan(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new Kecamatan();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Kecamatan::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->namakecamatan =  $PSN['namakecamatan'];
            $dataPS->objectpropinsifk =  $PSN['objectpropinsifk'];
            $dataPS->objectkotakabupatenfk =  $PSN['objectkotakabupatenfk'];
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
    public function deleteKecamatan(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Kecamatan::where('id', $r['id'])
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
