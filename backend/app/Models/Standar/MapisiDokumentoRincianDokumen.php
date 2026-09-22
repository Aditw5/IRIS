<?php
namespace App\Models\Standar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapisiDokumentoRincianDokumen extends Model
{
    use HasFactory;
    
    protected $table = "mapisidokumentorinciandokumen_t";
    protected $fillable = [];
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = "id";


}
