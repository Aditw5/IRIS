<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\Vendor;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterVendorCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterVendor(Request $r)
    {
        $data  = DB::table('vendor_m')
            ->select(
                'id',
                'statusenabled',
                'namavendor',
                'alamatvendor',
                'nohp',
            )
            ->where('kdprofile', $this->kdProfile);
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('id', '=',  $r['id']);
        }
        if (isset($r['namavendor']) && $r['namavendor'] != '') {
            $data = $data->where('namavendor', 'ilike', '%' . $r['namavendor'] . '%');
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('statusenabled', '=', $r['statusenabled']);
        }
        if (isset($r['_total']) && $r['_total'] != '') {
        }

        $data = $data->orderByDesc('created_at');
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

    public function saveVendor(Request $r)
    {
        DB::beginTransaction();
        try {
            $PSN =  $r['datastandar'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Vendor(), 'id', $this->kdProfile);
                $dataPS = new Vendor();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Vendor::where('id', $PSN['id'])->first();
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->namavendor =  $PSN['namavendor'];
            $dataPS->alamatvendor =  $PSN['alamatvendor'];
            $dataPS->nohp =  $PSN['nohp'];
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
    public function deleteVendor(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Vendor::where('id', $r['id'])
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
