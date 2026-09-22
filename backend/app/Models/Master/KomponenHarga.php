<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KomponenHarga extends Model
{
    use HasFactory;
    protected $table = "komponenharga_m";
    protected $fillable = [];
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
        ->select('id','komponenharga','objectdepartemenfk');
    }
    public function scopeSearch(Builder $builder, string $search = null)
    {
        if (empty($search)) {
            return $builder;
        }

        return $builder->where(function (Builder $sql) use ($search) {
            $sql->where("komponenharga", "like", "%{$search}%")
                ->orWhere("reportdisplay", "like", "%{$search}%");
        });
    }
}
