<?php

namespace App\Models\Standar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapLoginUserToRuangan extends Model
{
    use HasFactory;
    
    protected $table = "maploginusertoruangan_s";
    protected $fillable = [];
    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = "id";


}
