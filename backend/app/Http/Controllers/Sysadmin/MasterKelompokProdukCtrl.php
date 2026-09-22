<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisTransaksi;
use App\Models\Master\KelompokProduk;
use App\Traits\Valet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Faker\Factory;
use Ramsey\Uuid\Uuid;

class MasterKelompokProdukCtrl extends Controller
{
    use Valet;
    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }
    public function masterKelompokProduk(Request $r)
    {
        $data  = DB::table('kelompokproduk_m as kp')
            ->leftJoin('jenistransaksi_m as jt', 'kp.objectjenistransaksifk', '=', 'jt.id')
            ->select(
                'kp.id',
                'kp.kelompokproduk',
                'kp.ishavingstok',
                'kp.statusenabled',
                DB::raw('null as namadepartemen'),
                'kp.objectdepartemenfk',
                'kp.objectjenistransaksifk',
                'jt.jenistransaksi',
                'kp.ishavingstok',
                'kp.ishavingprice',
                'kp.kodeexternal'
            )
            ->where('kp.kdprofile', $this->kdProfile)
            ->orderByDesc('kp.kelompokproduk');

        if (isset($r['id']) && $r['id'] != '') {
            $data = $data->where('sk.id', '=',  $r['id']);
        }
        if (isset($r['kelompokproduk']) && $r['kelompokproduk'] != '') {
            $data = $data->where('kp.kelompokproduk', 'ilike', '%' . $r['kelompokproduk'] . '%');
        }
        if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
            $data = $data->where('kp.statusenabled', '=', $r['statusenabled']);
        }


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
        // return response()->json($res);
        return $this->respond($res);
    }

    public function masterKelompokProdukdropdown()
    {
        $res['namadepartemen'] = [];
        $res['jenistransaksi'] = JenisTransaksi::mine()->get();

        return $this->respond($res);
    }
    public function saveKelompokProduk(Request $request)
    {
        DB::beginTransaction();
        try {
            //region Save Jenis Kelamin
            $faker = Factory::create();
            $count = KelompokProduk::count();
            $PSN =  $request['kelompokproduk'];
            $codeUnique = $count < 1 ? 1 : $count + 1 ;  
               
            if ($PSN['id'] == '') {
                $data = new KelompokProduk();
                $data->id = $this->SEQUENCE_MASTER(new KelompokProduk(),'id',$this->kdProfile);//(string)Uuid::uuid4();
                $data->kodeexternal = $faker->regexify('[A-Z]{2}-').$codeUnique;
                $data->statusenabled = true;
                $data->kdkelompokproduk =  $codeUnique;
                $data->qkelompokproduk = $codeUnique;
                $transMessage = "Proses Simpan Data Berhasil";

            } else {
                $data = KelompokProduk::where('id', $PSN['id'])->first();
                $data->statusenabled = $PSN['statusenabled'];
                $transMessage = "Proses Update Data Berhasil";
                $id =  $data->id;
            }
            $data->kdprofile = (int)$this->kdProfile;
            $data->kelompokproduk =  $PSN['kelompokproduk']; 
            $data->objectjenistransaksifk =  $PSN['objectjenistransaksifk']; 
            $data->ishavingprice =  $PSN['ishavingprice']; 
            $data->ishavingstok =  $PSN['ishavingstok']; 
            $data->objectdepartemenfk =  $PSN['objectdepartemenfk'] ?? null; 
            $data->namaexternal =  $PSN['kelompokproduk'];
            $data->reportdisplay =  $PSN['kelompokproduk'];
            $data->save();

            DB::commit();

            $result = [
                "status" => 200,
                "result" => [
                    "data"  => $data,
                    "as" => 'aditwiran19@gmail.com',
                ],
            ];
            
        } catch (\Exception $e) {
            $transMessage = "Simpan Gagal";
            DB::rollBack();
            $result = array(
                "status" => 400,
                "result"  => null

            );
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }
    public function deleteKelompokProduk(Request $r)
    {
        DB::beginTransaction();
        try {
            $data = KelompokProduk::where('id', $r['id'])
                ->update([
                    'statusenabled' => false
                ]);
            DB::commit();
            $result = [
                "status" => 200,
                "result" => [
                    "data"  => $data,
                    "as" => 'aditwiran19@gmail.com',
                    ]
                ];
                $transMessage = "Proses Hapus Data Berhasil";
        } catch (\Exception $e) {
            DB::rollBack();
            $transMessage = "Something Went Wrong";
            $result = [
                "status" => 400,
                "result"  => [
                    "e"  => $e->getLine() . ' ' . $e->getMessage(),
                    ]
                ];
        }
        return $this->respond($result['result'], $result['status'], $transMessage);
    }
}
