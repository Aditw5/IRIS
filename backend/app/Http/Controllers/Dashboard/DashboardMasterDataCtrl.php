<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Master\ListMaster;
use App\Models\Standar\MapLoginUserToModulAplikasi;
use App\Models\Standar\MapLoginUserToRuangan;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardMasterDataCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function dashboardMasterData(Request $r)
    {
        $kdProfile = $this->kdProfile;
        $totalMapLoginuserToRuangan = MapLoginUserToRuangan::where('statusenabled',true)->where('kdprofile',$kdProfile)->count();
        $totalMapLoginuserToModulAplikasi  = MapLoginUserToModulAplikasi::where('statusenabled',true)->where('kdprofile',$kdProfile)->count();
        $result['totalMapLoginuserToRuangan'] =  $totalMapLoginuserToRuangan;
        $result['totalMapLoginuserToModulAplikasi'] =  $totalMapLoginuserToModulAplikasi;
        return $this->respond($result);
    }

    public function DataMaster (Request $r)
    {
        $data = DB::select("
        select x.*,
	    mas.label ,
	    mas.link from (
		with tbl as (
		select
		table_schema, table_name 
        from
		information_schema.tables 
        where
		table_name not like 'pg_%' 
		and table_schema in ( 'public' ) 
		and table_name in ( select tablename from listmaster_m ) 
		) 
        select
        table_schema,
		table_name,
		( xpath ( '/row/c/text()', query_to_xml ( format ( 'select count(*) as c from %I.%I', table_schema, table_name ), false, true, '' ) ) ) [ 1 ]:: text :: int as rows_n 
        from tbl 
        order by
		rows_n desc 
        ) as x join listmaster_m as mas on mas.tablename = x.table_name
        ");

        $result['data'] = $data;

        return $this->respond($result);
    }
        public function saveListMaster(Request $r)
        {
            $kdProfile = $this->kdProfile;
            DB::beginTransaction();
            try {
                $PSN =  $r['listmaster'];
                if ($PSN['id'] == '') {
                    $id = $this->Uuid4();
                    $dataPS = new ListMaster();
                    $dataPS->id = $id;
                    $dataPS->kdprofile = (int)$this->kdProfile;
                    $dataPS->statusenabled = true;
                } else {
                    $dataPS = ListMaster::where('id', $PSN['id'])->where('kdprofile',$kdProfile)->first();
                    $dataPS->statusenabled =  $PSN['statusenabled'];
                    $id =  $dataPS->id;
                }
                $dataPS->label =  $PSN['label'];
                $dataPS->tablename =  $PSN['tablename'];
                $dataPS->link =  $PSN['link'];
               
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
                    "result"  => array(
                        "e"  => $e->getLine() . ' ' . $e->getMessage(),
                    ),
    
                );
            }
            return $this->respond($result['result'], $result['status'], $transMessage);
        }    
    }
