<?php 

namespace App\Http\Controllers\Sysadmin;

use Faker\Factory;
use App\Traits\Valet;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Master\JenisKemasan;

class MasterJenisKemasanCtrl extends Controller
{
    use Valet;

    public function __construct()
    {
        parent::__construct($is_encrypt = true);
    }

        public function index(Request $r)
    {
        $data = DB::table('jeniskemasan_m as jk')
                ->join('produk_m as prod','jk.objectprodukfk','=','prod.id')
                ->select(
                    'jk.id',
                    'jk.statusenabled',
                    'jk.namaexternal',
                    'jk.reportdisplay',
                    'jk.kodeexternal',
                    'prod.namaproduk',
                    'jk.jeniskemasan',
                    'jk.isracikan',
                )
            ->where('kdprofile',$this->kdProfile)
            ->orderByDesc('asalsukucadang');
    
            if (isset($r['id']) && $r['id'] != '') {
                $data = $data->where('id', '=',  $r['id']);
            }
            if (isset($r['jeniskemasan']) && $r['jeniskemasan'] != '') {
                $data = $data->where('jeniskemasan', 'ilike', '%' . $r['jeniskemasan'] . '%');
            }
            if (isset($r['statusenabled']) && $r['statusenabled'] != '') {
                $data = $data->where('statusenabled', '=', $r['statusenabled']);
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
            
        $res = [
            'data' => $data,
            'count' =>$data->count()
        ];
        return $this->respond($res);
    }

   
    public function store(Request $r)
    {
        DB::beginTransaction();
        try {
            $faker = Factory::create();
            $count = JenisKemasan::count();
            $PSN =  $r['jeniskemasan'];
            $codeUnique = $count < 1 ? 1 : $count + 1 ;  
               
            if ($PSN['id'] == '') {
                $data = new JenisKemasan();
                $data->id = $this->SEQUENCE_MASTER(new JenisKemasan(),'id',$this->kdProfile);//(string)Uuid::uuid4();
                $data->statusenabled = true;
                $data->kodeexternal = $faker->regexify('[A-Z]{2}-').$codeUnique; 
                $transMessage = "Proses Simpan Data Berhasil";
            } else {
                $data = JenisKemasan::where('id', $PSN['id'])->first();
                $data->statusenabled = $PSN['statusenabled'];
                $transMessage = "Proses Update Data Berhasil";
                $id =  $data->id;
            }
                $data->kdprofile = (int)$this->kdProfile;
                $data->jeniskemasan =  $PSN['jeniskemasan']; 
                $data->objectprodukfk =  $PSN['objectprodukfk']; 
                $data->namaexternal =  $PSN['jeniskemasan'];
                $data->reportdisplay =  $PSN['jeniskemasan'];           
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
            $transMessage = "Something Went Wrong";
            DB::rollBack();
            $result = [
                "status" => 400,
                "result"  => [
                    "e"  => $e->getLine() . ' ' . $e->getMessage(),
                    ]
                ];
        }

        return $this->respond($result['result'], $result['status'], $transMessage);
    }


    public function delete(Request $r)
    {
        DB::beginTransaction();
        try {
            $dataPS = JenisKemasan::where('id', $r['id'])
                ->update([
                    'statusenabled' => false
                ]);
            DB::commit();
            $result = [
                "status" => 200,
                "result" => [
                    "data"  => $dataPS,
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