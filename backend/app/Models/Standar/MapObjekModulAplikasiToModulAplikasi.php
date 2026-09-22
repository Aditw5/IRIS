<?php
namespace App\Models\Standar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapObjekModulAplikasiToModulAplikasi extends Model
{
    use HasFactory;
    
    protected $table = "mapobjekmodulaplikasitomodulaplikasi_s";
    protected $fillable = [];
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = "id";


}
