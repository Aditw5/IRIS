<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisRekanan;
use App\Models\Master\Rekanan;
use App\Models\Master\KelompokPasien;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterMappingCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterMapping(Request $r)
    {
        $data  = DB::table('rekanan_m as rk')
        ->select(
            'rk.namarekanan',
        )
            ->where('rk.statusenabled', true)
            ->where('rk.kdprofile', $this->kdProfile);
        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('rk.id', '=',  $r['id']);
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('rk.statusenabled', '=',  $r['statusenabled']);
        }
        if (isset($r['namarekanan']) && $r['namarekanan'] != '') {
            $data = $data->where('rk.namarekanan', 'ilike', '%' . $r['namarekanan'] . '%');
        }if (isset($r['_total']) && $r['_total'] != '') {
        }
        if (isset($r['offset']) && $r['offset'] != '') {
            $data = $data->offset($r['offset']);
        }
        if (isset($r['limit']) && $r['limit'] != '') {
            $data = $data->limit($r['limit']);
        }

        $data = $data->orderByDesc('rk.namarekanan');
        $data = $data->get();

        $res['data'] = $data;
        return $this->respond($res);
    }
    public function saveRekanan(Request $r)
    {
        DB::beginTransaction();
        try {
            //region Save Rekanan

            $PSN =  $r['rekanan'];
            if ($PSN['id'] == '') {
                $id = $this->SEQUENCE_MASTER(new Rekanan(),'id',$this->kdProfile);///$this->Uuid4();
                $dataPS = new Rekanan();
                $dataPS->id = $id;
                $dataPS->kdprofile = (int)$this->kdProfile;
                $dataPS->statusenabled = true;
            } else {
                $dataPS = Rekanan::where('id', $PSN['id'])
                    ->where('statusenabled', true)
                    ->first();
                $id =  $dataPS->id;
            }
            $dataPS->namarekanan =  $PSN['namarekanan'];
            $dataPS->objectjenisrekananfk =  $PSN['objectjenisrekananfk'];
            $dataPS->objectpegawaifk =  $PSN['objectpegawaifk'];
            $dataPS->alamatlengkap =  $PSN['alamatlengkap'];
            $dataPS->rtrw =  $PSN['rtrw'];
            $dataPS->kotakabupaten = isset($PSN['kotakabupaten']['namakotakabupaten'])?$PSN['kotakabupaten']['namakotakabupaten']:null;
            $dataPS->namakecamatan = isset($PSN['namakecamatan']['namakecamatan'])?$PSN['namakecamatan']['namakecamatan']:null;
            $dataPS->namadesakelurahan = isset($PSN['namadesakelurahan']['namadesakelurahan'])?$PSN['namadesakelurahan']['namadesakelurahan']:null;
            $dataPS->kodepos = $PSN['kodepos'];
            $dataPS->contactperson = $PSN['contactperson'];
            $dataPS->email = $PSN['email'];
            $dataPS->website = $PSN['website'];
            $dataPS->telepon = $PSN['telepon'];
            $dataPS->bankrekeningatasnama = $PSN['bankrekeningatasnama'];
            $dataPS->bankrekeningnomor = $PSN['bankrekeningnomor'];
            $dataPS->nopkp = $PSN['nopkp'];
            $dataPS->npwp = $PSN['npwp'];
            $dataPS->rekananmoupksfk = $PSN['rekananmoupksfk'];
            $dataPS->objectkelompokpasienfk = $PSN['objectkelompokpasienfk'];
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
    public function masterRekanandropdown(Request $r)
    {
        $res['jenisrekanan'] = JenisRekanan::mine()->get();
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

