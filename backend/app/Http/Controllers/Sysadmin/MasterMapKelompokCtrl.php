<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisRekanan;
use App\Models\Master\Pegawai;
use App\Models\Master\Provinsi;
use App\Models\Master\Rekanan;
use App\Models\Master\KelompokPasien;
use App\Models\Master\MapKelompokPasienToPenjamin;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterMapKelompokCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterMapKelompok(Request $r)
    {
        $data  = DB::table('mapkelompokpasientopenjamin_m as mp')
        ->join('kelompokpasien_m as kp', 'mp.objectkelompokpasienfk', '=', 'kp.id')
        ->leftJoin('rekanan_m as rk', 'mp.kdpenjaminpasien', '=', 'rk.id')
        ->select(
            'mp.id',
            'mp.objectkelompokpasienfk',
            'mp.kdpenjaminpasien',
            'kp.kelompokpasien',
            'rk.namarekanan'
        )
            ->where('mp.statusenabled', true)
            ->where('mp.kdprofile', $this->kdProfile);
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('mp.id', '=',  $r['id']);
        }
        if (isset($r['objectkelompokpasienfk']) && $r['objectkelompokpasienfk'] != '') {
            $data = $data->where('mp.objectkelompokpasienfk', '=', $r['objectkelompokpasienfk'] );
        }
        if (isset($r['_total']) && $r['_total'] != '') {
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }
        if (isset($r['limit']) && $r['limit'] != '') {
            $data = $data->limit($r['limit']);
        }

        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }
    public function saveMapKelompok(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Rekanan

            $delete = MapKelompokPasienToPenjamin::where('objectkelompokpasienfk', $r['objectkelompokpasienfk'])
            ->update(['statusenabled'=> false]);
            foreach($r['detail'] as $item){
                $dataPS = new MapKelompokPasienToPenjamin();
                $dataPS->id =   $this->SEQUENCE_MASTER(new MapKelompokPasienToPenjamin(),'id',$this->kdProfile);//$this->Uuid4();
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
                $dataPS->objectkelompokpasienfk =  $r['objectkelompokpasienfk'];
                $dataPS->kdpenjaminpasien = $item['objectrekananfk'];
                $dataPS->save();
            }
              
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
    
    public function deleteRekanan(Request $r)
    {
        DB::beginTransaction();
        try {

            $dataPS = Rekanan::where('id', $r['id'])
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
    public function masterMapKelompokdropdown(Request $r)
    {
        $res['kelompokpasien'] = KelompokPasien::mine()->get();
        $res['namarekanan'] = Rekanan::mine()->get();

        return $this->respond($res);
    }
    public function rekananByID(Request $r)
    {
        $data = DB::table('rekanan_m as rk')
            ->JOIN('jenisrekanan_m as jk', 'rk.objectjenisrekananfk', '=', 'jk.kdjenisrekanan')
            ->JOIN('pegawai_m as pg', 'rk.objectpegawaifk', '=', 'pg.bjectpegawaifk')
            ->leftjoin('desakelurahan_m as dsk', 'dsk.id', '=', 'alm.objectdesakelurahanfk')
            ->leftjoin('kecamatan_m as kcm', 'kcm.id', '=', 'alm.objectkecamatanfk')
            ->leftjoin('kotakabupaten_m as kkb', 'kkb.id', '=', 'alm.objectkotakabupatenfk')
            ->leftjoin('propinsi_m as prp', 'prp.id', '=', 'alm.objectpropinsifk')
            ->select(
                'rk.namarekanan',
                'jk.jenisrekanan',
                'pg.namapegawai',
                'rk.alamatlengkap',
                'rk.rtrw',
                'rk.kodepos',
                'rk.desakelurahan',
                'rk.namakecamatan',
                'rk.kotakabupaten',
                'rk.contactperson',
                'rk.email',
                'rk.faksimile',
                'rk.telepon',
                'rk.website',
                'rk.bankrekeningatasnama',
                'rk.bankrekeningnama',
                'rk.bankrekeningnomor',
                'rk.nopkp',
                'rk.npwp',
                'rk.rekananmoupksfk',
            
            )
            ->where('ps.kdprofile', (int)$this->kdProfile)
            ->where('ps.statusenabled', true);
        if (isset($r['id']) && $r['id'] != '' && $r['id'] != 'undefined') {
            $data = $data->where('rk.id', $r['id']);
        }
        if (isset($r['id']) && $r['id'] != '' && $r['id'] != 'undefined') {
            $data = $data->where('ps.id', $r['id']);
        }
        $result = array(
            'rekanan' => $data,
            'as' => 'aditwiran19@gmail.com',
        );
        return $this->respond($result);
    }
}

