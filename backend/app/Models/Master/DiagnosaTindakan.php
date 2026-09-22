<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosaTindakan extends Model
{
    use HasFactory;
    protected $table = "diagnosatindakan_m";
    protected $fillable = [];
    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = "id";


    public function scopeMine(Builder $builder, int $kdProfile = null)
    {
        if(!empty(request()->session()->get('kdProfile'))) {
            $kdProfile = request()->session()->get('kdProfile');
        }else{
            $kdProfile = (Profile::where('statusenabled',true)->first())->id;
        }
        return $builder->where("kdprofile", $kdProfile)
        ->where('statusenabled',true)
        ->select('id','kddiagnosatindakan','namadiagnosatindakan','kddiagnosatindakan');
    }
    public function scopeSearch(Builder $builder, string $search = null)
    {
        if (empty($search)) {
            return $builder;
        }

        return $builder->where(function (Builder $sql) use ($search) {
            $sql->where("namadiagnosatindakan", "ilike", "%{$search}%");
            $sql->Orwhere("kddiagnosatindakan", "ilike", "%{$search}%");
        });
    }
    public function scopePaging(Builder $builder, $paging = null)
    {
        if (empty($paging)) {
            return $builder;
        }
        return $builder->limit($paging);
    }
}
