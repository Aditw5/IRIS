<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\ListMaster;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterListCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterList(Request $r)
    {
        $data  = DB::table('listmaster_m')
            ->select(
                'id',
                'statusenabled',
                'label',
                'tablename',
                'link',
            )
            ->where('kdprofile', $this->kdProfile);
            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('id', '=',  $r['id']);
            }
            if (isset($r['label']) && $r['label'] != '') {
                $data = $data->where('label', 'ilike', '%' . $r['label'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '' && $r['statusenabled'] == 'true') {
                $data = $data->where('statusenabled', '=', $r['statusenabled']);
            }
            if (isset($r['_total']) && $r['_total'] != '') {
            }
    
        $data = $data->orderByDesc('label');
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

    public function saveList(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save List Master

            $PSN =  $r['listmaster'];
            if ($PSN['id'] == '') {
                $id = $this->Uuid4();
                $dataPS = new ListMaster();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = ListMaster::where('id', $PSN['id'])->first();
                $dataPS->statusenabled =  $PSN['statusenabled'];
                $id =  $dataPS->id;
            }
            $dataPS->label =  $PSN['label'];
            $dataPS->tabelname =  $PSN['tabelname'];
            $dataPS->link =  $PSN['link'];
           
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
    public function deleteList(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = ListMaster::where('id', $r['id'])
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
