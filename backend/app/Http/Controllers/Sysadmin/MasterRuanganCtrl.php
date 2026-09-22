<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Ruangan;
use App\Traits\Valet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Translation\t;

class MasterRuanganCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterRuangan(Request $r)
    {
        $data  = DB::table('ruangan_m as ru')
            ->leftJoin('lokasikalibrasi_m as lk', 'ru.lokasifk', '=', 'lk.id')
            ->select(
                'ru.id',
                'ru.statusenabled',
                'ru.namaruangan',
                'ru.lokasifk',
                'lk.id as idlokasi',
                'lk.lokasi',
            )
            ->where('ru.kdprofile', $this->kdProfile);
            if($r['ruangan']){
                $data->where('ru.namaruangan', $r['ruangan']);
            }if($r['lokasifk']){
                $data->where('ru.lokasifk', $r['lokasifk']);
            }if($r['statusenabled']){
                $data->where('ru.statusenabled', $r['statusenabled']);
            }
            if (isset($r['ihs_id']) && $r['ihs_id'] != '') {
                $data = $data->whereNotNull('ru.ihs_id');
            }
            $data = $data->orderByDesc('ru.id', 'desc');
            $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }
   
    public function saveRuangan(Request $r)
    {
        DB::beginTransaction();
        try {
            $PSN =  $r['ruangan'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Ruangan(),'id',$this->kdProfile);
                $dataPS = new Ruangan();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
                $transMessage = "Proses Simpan Data Berhasil";
            } else {
                $dataPS = Ruangan::where('id', $PSN['id'])->first();
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
                $transMessage = "Proses Update Data Berhasil";
            }
            $dataPS->namaruangan =  $PSN['namaruangan'];
            $dataPS->lokasifk =  $PSN['lokasifk'];
            $dataPS->save();

            DB::commit();
            $result = [
                'status' => 201,
                'message' => $transMessage,
                'result' => $dataPS,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $result = [
                'status' => 400,
                'message' => "Something Want Wrong",
                'result' => $e->getMessage(),
            ];
        }
        return $this->respond($result['result'], $result['status'], $result['message']);
    }

    public function deleteRuangan(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Ruangan::where('id', $r['id'])->update(['statusenabled' => false]);
            DB::commit();

            $result = [
                'status' => 201,
                'message' => 'Proses Hapus Data Berhasil',
                'result' => $dataPS,
            ];

        }
        catch(Exception $e){
            $result = [
                'status' => 400,
                'message' => 'Something Want Wrong',
                'result' => $e->getMessage(),
            ];
        }

        return $this->respond($result['result'], $result['status'], $result['message']);
    }
}
