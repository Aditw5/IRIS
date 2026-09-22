<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisKondisiPasien extends Model
{
    use HasFactory;
    protected $table = "jeniskondisipasien_m";
    protected $guarded = [];
    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = "id";
    public function scopeMine(Builder $builder, int $kdProfile = null)
    {
        if(!empty(request()->session()->get('kdProfile'))) {
            $kdProfile = request()->session()->get('kdProfile');
        }else{
            $kdProfile = Profile::where('statusenabled',true)->first()->id;
        }
        return $builder->where("kdprofile", $kdProfile)
        ->where('statusenabled',true)
        ->select('id','jeniskondisipasien');
    }
    public function scopeSearch(Builder $builder, string $search = null)
    {
        if (empty($search)) {
            return $builder;
        }

        return $builder->where(function (Builder $sql) use ($search) {
            $sql->where("jeniskondisipasien", "like", "%{$search}%")
                ->orWhere("reportdisplay", "like", "%{$search}%");
        });
    }

    // public function store(Request $r)
    // {
    //     DB::beginTransaction();
    //     try {
    //         //region Save Jenis Kelamin
    //         $faker = Factory::create();
    //         $count = JenisKondisiPasien::count();
    //         $PSN =  $r['jeniskondisipasien'];
    //         $codeUnique = $count < 1 ? 1 : $count+1 ;  
               
    //         if ($PSN['id'] == '') {
    //             $dataPS = [
    //                 'id' => (string)Uuid::uuid4(),
    //                 'kdprofile' => (int)$this->kdProfile,
    //                 'statusenabled' => true,
    //                 'jeniskondisipasien' =>  $PSN['jeniskondisipasien'], 
    //                 'norec' =>  $PSN['norec'],
    //                 'namaexternal' =>  $PSN['jeniskondisipasien'],
    //                 'reportdisplay' =>  $PSN['jeniskondisipasien'],
    //                 'kdjeniskondisipasien' =>  $codeUnique,
    //                 'qjeniskondisipasien' => $codeUnique,
    //                 'kodeexternal' => $faker->regexify('[A-Z]{2}-').$codeUnique,
    //             ];
                
    //         }else{
    //             $dataPS = JenisKondisiPasien::where('id', $PSN['id'])
    //                 ->where('statusenabled', true)
    //                 ->first();
    //             $id =  $dataPS->id;
    //         } 
    //         $cek = JenisKondisiPasien::create([$dataPS]);
    //         // $dataPS->kdprofile = (int)$this->kdProfile;
    //         // $dataPS->statusenabled = true;
    //         // $dataPS->jeniskondisipasien =  $PSN['jeniskondisipasien']; 
    //         // $dataPS->norec =  $PSN['norec'];
    //         // $dataPS->namaexternal =  $PSN['jeniskondisipasien'];
    //         // $dataPS->reportdisplay =  $PSN['jeniskondisipasien'];
    //         // $dataPS->kdjeniskondisipasien =  $codeUnique;
    //         // $dataPS->qjeniskondisipasien = $codeUnique;
    //         // $dataPS->kodeexternal = $faker->regexify('[A-Z]{2}-').$codeUnique;
    //         // $dataPS->save();
            
    //         $transMessage = "Sukses";
    //         DB::commit();

    //         $result = array(
    //             "status" => 200,
    //             "result" => array(
    //                 "data"  => $cek,
    //                 "as" => 'aditwiran19@gmail.com',
    //             ),
    //         );
    //         return $this->respond($result['result'], $result['status'], $transMessage);
    //     } catch (\Exception $e) {
    //         $transMessage = "Simpan Gagal";
    //         DB::rollBack();
    //         $result = array(
    //             "status" => 400,
    //             "result"  => array(
    //                 "e"  => $e->getLine() . ' ' . $e->getMessage(),
    //             ),

    //         );
    //         return $this->respond($result['result'], $result['status'], $transMessage);
    //     }
        
    // }

}