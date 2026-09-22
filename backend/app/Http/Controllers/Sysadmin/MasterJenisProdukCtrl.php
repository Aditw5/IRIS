<?php

namespace App\Http\Controllers\Sysadmin;
use App\Http\Controllers\Controller;
use App\Models\Master\KelompokProduk;
use App\Models\Master\JenisProduk;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterJenisProdukCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterJenisProduk(Request $r)
    {
        $data  = DB::table('jenisproduk_m as js')
            ->join('kelompokproduk_m as kp', 'js.objectkelompokprodukfk', '=', 'kp.id')
            ->select(
                'js.id',
                'js.jenisproduk',
                'js.objectdepartemenfk',
                'js.objectkelompokprodukfk',
                'kp.kelompokproduk',
                DB::raw('null as namadepartemen')
            )
                 ->where('js.statusenabled', true)
                ->where('js.kdprofile', $this->kdProfile);

            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('js.id', '=',  $r['id']);
            }

            if (isset($r['offset']) && $r['offset'] != '') {
                $data = $data->offset($r['offset']);
            }
            if (isset($r['kelompokproduk']) && $r['kelompokproduk'] != '') {
                $data = $data->where('kp.kelompokproduk', 'ilike', '%'.$r['kelompokproduk'].'%');
            }
            if (isset($r['rows']) && $r['rows'] != '') {
                $data = $data->limit($r['rows']);
            }

            $data = $data->orderByDesc('js.jenisproduk');
            $data = $data->get();
    
            $res['data'] = $data;
            return $this->respond($res);
        }
    

    public function masterJenisProdukdropdown(Request $r)
    {
        $res['namadepartemen'] = [];
        $res['kelompokproduk'] = KelompokProduk::mine()->get();
        $res['jenisproduk'] = JenisProduk::mine()->get();

        return $this->respond($res);
    }
    public function saveJenisProduk(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Ruangan

            $PSN =  $r['jenisproduk'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new JenisProduk(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS = new JenisProduk();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = JenisProduk::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->jenisproduk =  $PSN['jenisproduk'];
            $dataPS->objectkelompokprodukfk =  $PSN['objectkelompokprodukfk'];
            $dataPS->objectdepartemenfk =  $PSN['objectdepartemenfk'] ?? null;
            $dataPS->reportdisplay =  $PSN['jenisproduk'];
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
    public function deleteJenisProduk(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = JenisProduk::where('id', $r['id'])
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
