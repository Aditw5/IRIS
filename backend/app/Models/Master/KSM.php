<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KSM extends Model
{
    use HasFactory;

    protected $table = "kelompokstafmedis_m";

    // Allow mass assignment on 'id' and other required fields
    protected $fillable = ['id', 'kelompokstafmedis', 'namaexternal', 'reportdisplay', 'kdprofile', 'statusenabled'];

    public $timestamps = true;
    public $incrementing = false; // Since you're using a custom primary key
    protected $primaryKey = "id";

    // Scope to filter by 'kdProfile'
    public function scopeMine(Builder $builder, int $kdProfile = null)
    {
        if (!empty(request()->session()->get('kdProfile'))) {
            $kdProfile = request()->session()->get('kdProfile');
        } else {
            $kdProfile = Profile::where('statusenabled', true)->first()->id;
        }

        return $builder->where("kdprofile", $kdProfile)
                       ->where('statusenabled', true)
                       ->select('id', 'kelompokstafmedis');
    }

    // Scope to handle search functionality
    public function scopeSearch(Builder $builder, string $search = null)
    {
        if (empty($search)) {
            return $builder;
        }

        return $builder->where(function (Builder $sql) use ($search) {
            $sql->where("kelompokstafmedis", "like", "%{$search}%")
                ->orWhere("reportdisplay", "like", "%{$search}%");
        });
    }
}
