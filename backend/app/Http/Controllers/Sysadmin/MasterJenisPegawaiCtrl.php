<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\DetailKelompokPegawai;
use App\Models\Master\JenisPegawai;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterJenisPegawaiCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterJenisPegawai(Request $r)
    {
        $data  = DB::table('jenispegawai_m as ru')
            ->join('detailkelompokpegawai_m as dp', 'ru.objectdetailkelompokpegawaifk', '=', 'dp.id')
            ->select(
                'ru.id',
                'ru.statusenabled',
                'ru.jenispegawai',
                'dp.detailkelompokpegawai',
                'ru.objectdetailkelompokpegawaifk',
                'ru.kdjenispegawai',
            )
            ->where('ru.kdprofile', $this->kdProfile);

        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('ru.id', '=',  $r['id']);
        }
        if (isset($r['jenispegawai']) && $r['jenispegawai'] != '') {
            $data = $data->where('ru.jenispegawai', 'ilike', '%' . $r['jenispegawai'] . '%');
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('ru.statusenabled', '=', $r['statusenabled']);
        }
        if (isset($r['objectdetailkelompokpegawaifk']) && $r['objectdetailkelompokpegawaifk'] != '') {
            $data = $data->where('ru.objectdetailkelompokpegawaifk', '=', $r['objectdetailkelompokpegawaifk'] );
        }

        $data = $data->orderByDesc('ru.jenispegawai');
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
    public function masterJenisPegawaidropdown(Request $r)
    {
        $res['detailkelompokpegawai'] = DetailKelompokPegawai::mine()->get();

        return $this->respond($res);
    }
    public function saveJenisPegawai(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save JeniscPegawai

            $PSN =  $r['jenispegawai'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new JenisPegawai(),'id',$this->kdProfile);//$this->Uuid4();
                // return $id;
                $dataPS = new JenisPegawai();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = JenisPegawai::where('id', $PSN['id'])->first();
                $dataPS->statusenabled = $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->jenispegawai =  $PSN['jenispegawai'];
            $dataPS->objectdetailkelompokpegawaifk =  $PSN['objectdetailkelompokpegawaifk'];

            $dataPS->save();
            //endregion

            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
            $message = $e->getMessage()." at line ".$e->getLine();
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
                "result"  => $message

            );
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
    public function deleteJenisPegawai(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = JenisPegawai::where('id', $r['id'])
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
