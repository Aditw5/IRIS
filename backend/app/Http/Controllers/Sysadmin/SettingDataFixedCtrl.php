<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\SettingDataFixed;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class SettingDataFixedCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

    public function getDataFixed(Request $r) {
        $dataDataFixed = DB::table('settingdatafixed_m as sf')
            ->select(
                'sf.id', 
                'sf.keteranganfungsi',
                'sf.namafield',
                'sf.nilaifield',
                'sf.tabelrelasi',
                'sf.typefield',
                'sf.statusenabled',
                'sf.kelompok'
                )
                ->where('sf.kdprofile', $this->kdProfile);

            if (isset($r['id']) && $r['id'] != '') {
                $dataDataFixed =  $dataDataFixed ->where('sf.id', '=',  $r['id']);
            }
            if (isset($r['namafield']) && $r['namafield'] != '') {
                $dataDataFixed = $dataDataFixed->where('sf.namafield', 'ilike', '%' . $r['namafield'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
                $dataDataFixed = $dataDataFixed->where('sf.statusenabled',$r['statusenabled']);
            }
     
            $dataDataFixed = $dataDataFixed ->orderByDesc('sf.kelompok');
            $dataDataFixed = $dataDataFixed ->get();
    
            $res['data'] = $dataDataFixed;
            return $this->respond($res);
        }

    public function getSettingById(Request $request, $id){
        $kdProfile = (int) $this->kdProfile;
//        $data = \DB::table('setting as rek')
//            ->select('rek.*','jr.jenisrekanan','mp.id as idmap','mp.objectkelompokpasienfk')
//            ->where('rek.id','=',$id)
//            ->get();

        $datas= DB::select(DB::raw("select * from settingdatafixed_m
              where kdprofile =  $kdProfile and id='$id'
              and statusenabled=true"));

        return $this->respond($datas);
    }

    public function SaveSettingDataFixed(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Setting Data Fixed

            $PSN =  $r['settingdatafixed'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new SettingDataFixed(),'id',$this->kdProfile);//(string)Uuid::uuid4();;
                $dataPS = new SettingDataFixed();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = SettingDataFixed::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->namafield =  $PSN['namafield'];
            $dataPS->typefield =  $PSN['typefield'];
            $dataPS->nilaifield = $PSN['nilaifield'];
            $dataPS->tabelrelasi = $PSN['tabelrelasi'];
            $dataPS->keteranganfungsi = $PSN['keteranganfungsi'];
            $dataPS->kelompok = $PSN['kelompok'];
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

    // public function SaveSettingDataFixed(Request $request) {
    //     $kdProfile = (int) $this->kdProfile;
    //     DB::beginTransaction();

    //     $idDataFixed = SettingDataFixed::max('id');
    //     $idDataFixed = $idDataFixed + 1;

    //     if ($request['datafixed']['iddatafixed']==''){
    //         $newDF = new SettingDataFixed();
    //         $newDF->id = $idDataFixed;
    //         $newDF->norec = $idDataFixed;
    //         $newDF->kodeexternal = $idDataFixed;
    //         $newDF->kdprofile = $kdProfile;
    //         $newDF->statusenabled = true;
    //     }else{
    //         $newDF =  SettingDataFixed::where('id',$request['datafixed']['iddatafixed'])::where('kdprofile', $kdProfile)->first();
    //     }
    //     $newDF->kodeexternal = $request['datafixed']['kodeexternal'];
    //     $newDF->namaexternal = $request['datafixed']['namaexternal'];
    //     $newDF->reportdisplay = $request['datafixed']['reportdisplay'];
    //     $newDF->fieldkeytabelrelasi = $request['datafixed']['fieldkeytabelrelasi'];
    //     $newDF->fieldreportdisplaytabelrelasi = $request['datafixed']['fieldreportdisplaytabelrelasi'];
    //     $newDF->keteranganfungsi = $request['datafixed']['keteranganfungsi'];
    //     $newDF->namafield = $request['datafixed']['namafield'];
    //     $newDF->nilaifield = $request['datafixed']['nilai'];
    //     $newDF->tabelrelasi = $request['datafixed']['tabelrelasi'];
    //     $newDF->typefield = $request['datafixed']['typefield'];
    //     if(isset($request['datafixed']['kelompok'])){
    //         $newDF->kelompok = $request['datafixed']['kelompok'];
    //     }
    //     try {
    //         $newDF->save();
    //         $transStatus = 'true';
    //     } catch (\Exception $e) {
    //         $transStatus = 'false';

    //     }

    //     if ($transStatus == 'true') {
    //         $transMessage = "Simpan Data Fixed Berhasil";
    //         DB::commit();
    //         $result = array(
    //             "status" => 200,
    //             "message" => $transMessage,
    //             "result" => $newDF,//$noResep,,//$noResep,
    //             "as" => 'uhman',
    //         );
    //     } else {
    //         $transMessage = "Simpan Gagal";
    //         DB::rollBack();
    //         $result = array(
    //             "status" => 400,
    //             "message"  => $transMessage,
    //             "SaveSettingDataFixed" => $newDF,//$noResep,
    //             "as" => 'uhman',
    //         );
    //     }
    //     return $this->setStatusCode($result['status'])->respond($result, $transMessage);
    // }


    // public function HapusSettingDataFixed(Request $request) {
    //     $kdProfile = (int) $this->kdProfile;
    //     DB::beginTransaction();

    //     try {
    //         $newDF = SettingDataFixed::where('id',$request['iddatafixed'])
    //             ->where('kdprofile', $kdProfile)
    //             ->update(['statusenabled' => 'f']);
    //         $transStatus = 'true';
    //     } catch (\Exception $e) {
    //         $transStatus = 'false';

    //     }

    //     if ($transStatus == 'true') {
    //         $transMessage = "Hapus Data Fixed Berhasil";
    //         DB::commit();
    //         $result = array(
    //             "status" => 200,
    //             "message" => $transMessage,
    //             "datRekanan" => $newDF,//$noResep,,//$noResep,
    //             "as" => 'titi',
    //         );
    //     } else {
    //         $transMessage = "Hapus Data Fixed Gagal";
    //         DB::rollBack();
    //         $result = array(
    //             "status" => 400,
    //             "message"  => $transMessage,
    //             "datRekanan" => $newDF,//$noResep,
    //             "as" => 'titi',
    //         );
    //     }
    //     return $this->respond($result['result'], $result['status'], $transMessage);
    // }

	// public function deleteSetting(Request $request) {
    //     $kdProfile = (int) $this->kdProfile;
	// 	DB::beginTransaction();
	// 	try {
	// 		$newDF = SettingDataFixed::where('id',$request['id'])
    //             ->where('kdprofile', $kdProfile)
    //             ->delete();
	// 		$transStatus = 'true';
	// 	} catch (\Exception $e) {
	// 		$transStatus = 'false';

	// 	}
	// 	if ($transStatus == 'true') {
	// 		$transMessage = "Hapus Data Fixed Berhasil";
	// 		DB::commit();
	// 		$result = array(
	// 			"status" => 200,
	// 			"message" => $transMessage,
	// 			"as" => 'inhumanaditwiran19@gmail.com',
	// 		);
	// 	} else {
	// 		$transMessage = "Hapus Data Fixed Gagal";
	// 		DB::rollBack();
	// 		$result = array(
	// 			"status" => 400,
	// 			"message"  => $transMessage,
	// 			"as" => 'inhumanaditwiran19@gmail.com',
	// 		);
	// 	}
    //     return $this->respond($result['result'], $result['status'], $transMessage);
	// }

    public function deleteSettingDataFix(Request $r)
    {
        DB::beginTransaction();
        try {
            $dataPS = SettingDataFixed::where('id', $r['id'])
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

    public function updateStatuEnabled(Request $request) {
        $kdProfile = (int) $this->kdProfile;
        DB::beginTransaction();
        try {
            $newDF = SettingDataFixed::where('id',$request['id'])
                ->where('kdprofile', $kdProfile)
                ->update(
                ['statusenabled' => $request['statusenabled'] ]
            );
            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';

        }
        if ($transStatus == 'true') {
            $transMessage = "Sukses";
            DB::commit();
            $result = array(
                "status" => 200,
                "message" => $transMessage,
                "as" => 'inhumanaditwiran19@gmail.com',
            );
        } else {
            $transMessage = "Terjadi Kesalahan";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "message"  => $transMessage,
                "as" => 'inhumanaditwiran19@gmail.com',
            );
        }
        return $this->setStatusCode($result['status'])->respond($result, $transMessage);
    }

    public function getKelompokSettingDataFix(Request $request){
        $kdProfile = (int) $this->kdProfile;
        $data = DB::table('settingdatafixed_m')
            ->select(DB::raw("case when kelompok is null then 'Lain-lain' else kelompok end as kelompok"))
            ->where('kdprofile', $kdProfile)
            ->groupBy('kelompok')
            ->orderBy('kelompok')
            ->get();
        $result = array(
          'data' => $data,
          'as' => 'inhumanaditwiran19@gmail.com'
        );
        return $this->respond($result);
    }
    public function getSettingDetail(Request $request) {
        $kdProfile = (int) $this->kdProfile;
        if($request['kelompok'] == 'Lain-lain'){
            $request['kelompok']  = null;
        }
        $dataRaw = DB::table('settingdatafixed_m')
            ->where('kelompok', $request['kelompok'])
            ->where('kdprofile', $kdProfile)
            ->select('*','keteranganfungsi as caption','nilaifield')
            ->orderBy('id');
        $dataRaw = $dataRaw->get();
        $dataraw3A =[];

        foreach ($dataRaw as $dataRaw2) {
            $head = '';
            $type =  $dataRaw2->typefield;
            if(stripos( $dataRaw2->typefield, 'Str') !== FALSE
                || stripos( $dataRaw2->typefield, 'Int') !== FALSE
                    ||stripos( $dataRaw2->typefield, 'Char') !== FALSE ){
                if($dataRaw2->tabelrelasi != null){
                    $type = 'combobox';
                }else{
                    $type = 'textbox';
                }
            }elseif($dataRaw2->typefield == 'combobox') {
                $type = 'combobox';
            }else{
                $type = 'textbox';
            }

            $dataraw3A[] = array(
                'kdprofile' => $dataRaw2->kdprofile,
                'statusenabled' => $dataRaw2->statusenabled,
                'kodeexternal'=> $dataRaw2->kodeexternal,
                'namaexternal' => $dataRaw2->namaexternal,
                'reportdisplay' => $dataRaw2->reportdisplay,
                'fieldkeytabelrelasi' => $dataRaw2->fieldkeytabelrelasi,
                'caption' => $head . $dataRaw2->caption  ,

                'cbotable' => $dataRaw2->tabelrelasi,
                'fieldreportdisplaytabelrelasi' => $dataRaw2->fieldreportdisplaytabelrelasi,
                'keteranganfungsi' => $dataRaw2->keteranganfungsi,
                'namafield' => $dataRaw2->namafield,
                'id' => $dataRaw2->id ,
                'nilaifield' => $dataRaw2->nilaifield ,
                'tabelrelasi' => $dataRaw2->tabelrelasi,
                'typefield' => $dataRaw2->typefield,
                'type' =>$type ,
                'kelompok' => $dataRaw2->kelompok,
                'value' => $dataRaw2->nilaifield ,
                'text' => $dataRaw2->reportdisplay,
            );
        }

        $result = array(
            'kolom1' => $dataraw3A,
            'message' => 'inhumanaditwiran19@gmail.com',
        );

        return $this->respond($result);
    }
    public function getComboPart(Request $request) {
        $kdProfile = (int) $this->kdProfile;
        $id = $request['id'];
        $req= $request->all();
        $setting = DB::table('settingdatafixed_m')
            ->select('*')
            ->where('kdprofile', $kdProfile)
            ->where('id',$id)
            ->get();

        $table =  $setting[0]->tabelrelasi;
        $namaField = strtolower ($setting[0]->fieldreportdisplaytabelrelasi);
        $keyField = strtolower ($setting[0]->fieldkeytabelrelasi);
        $table = strtolower ($table);
        $data  = DB::table("$table")
            ->select("$namaField as text" ,"$keyField as value")
            ->where('statusenabled',true)
            ->orderBy("$keyField");

        if(isset($req['filter']['filters'][0]['value']) &&
            $req['filter']['filters'][0]['value']!="" &&
            $req['filter']['filters'][0]['value']!="undefined"){
                $data = $data->where("$namaField",'ilike','%'. $req['filter']['filters'][0]['value'].'%' );

        };
        $data = $data->take(10);
        $data = $data->get();

        return $this->respond($data);
    }
    public function updateSettingDataFix(Request $request) {
        DB::beginTransaction();
        $dataReq = $request->all();
        $kdProfile = (int) $this->kdProfile;
        $data = $dataReq['data'];

        try {

            $i=0;
            $settingIds = collect($data)->pluck('id')->filter()->unique()->values();
            $settings = $settingIds->isNotEmpty()
                ? SettingDataFixed::whereIn('id', $settingIds)
                    ->where('kdprofile', $kdProfile)
                    ->get()
                    ->keyBy('id')
                : collect();
            foreach ($data as $item) {
                if (is_array($item['values'])){
                    $value = $item['values']['value'] ;
                    $text = $item['values']['text'] ;
                }else{
                    $value = $item['values'];
                    $text = '';
                }

                $EMRD = $settings->get($item['id']);
                $EMRD->statusenabled = $dataReq['head'];
                $EMRD->nilaifield = $value;
                $EMRD->reportdisplay = $text;
                $EMRD->save();
                $i = $i + 1;
            }
            $transStatus = 'true';
        } catch (\Exception $e) {
            $transStatus = 'false';
        }
        $transMessage = 'Update Setting ';

        if ($transStatus == 'true') {
            $transMessage = $transMessage . "Sukses";
            DB::commit();
            $result = array(
                "status" => 200,
                "as" => 'inhumanaditwiran19@gmail.com',
            );
        }else{
            $transMessage = $transMessage ." Gagal!!";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "as" => 'inhumanaditwiran19@gmail.com',
            );
        }

        return $this->setStatusCode($result['status'])->respond($result, $transMessage);
    }

    public function getTable(Request $request){
        $kdProfile = (int) $this->kdProfile;
        $req= $request->all();
        $data  = DB::table("information_schema.tables")
            ->select("table_name")
            ->where('table_schema','=','public')
            ->where('table_type','=','BASE TABLE')
            ->orderBy("table_name");

        if(isset($req['filter']['filters'][0]['value']) &&
            $req['filter']['filters'][0]['value']!="" &&
            $req['filter']['filters'][0]['value']!="undefined"){
            $data = $data->where("table_name",'ilike','%'. $req['filter']['filters'][0]['value'].'%' );

        };
        $data = $data->take(10);
        $data = $data->get();

        return $this->respond($data);
    }
    public function getFieldTable(Request $request){;
        $table = $request['tablename'];
        $data  = DB::select(DB::raw("SELECT
            COLUMN_NAME
            FROM
            information_schema.COLUMNS
            WHERE
            TABLE_NAME = '$table';"));
        $result = array(
            "data" => $data,
            "as" => 'asaditwiran19@gmail.com',
        );
        return $this->respond($result);
    }
    public function getDataFromTable(Request $request){;
        $table = $request['table_name'];
        $column = $request['column_name'];
        $data  = DB::select(DB::raw("
            select $column as name from $table
             "));
        $result = array(
            "data" => $data,
            "as" => 'asaditwiran19@gmail.com',
        );
        return $this->respond($result);
    }
    public function getReportDisplayTable(Request $request){;
        $table = $request['table_name'];
        $column = $request['column_name'];
        $nilai = $request['nilai'];
        $data  = DB::select(DB::raw("
            select $column as id from $table
             "));
        $result = array(
            "data" => $data,
            "as" => 'asaditwiran19@gmail.com',
        );
        return $this->respond($result);
    }
    protected function getSettingDataFixedGeneric($namaField, Request $request){
        $kdProfile = (int) $this->kdProfile;
        $set = SettingDataFixed::where('namafield', $namaField)->where('kdprofile', $kdProfile)->first();
        return $set->nilaifield;
    }
};
