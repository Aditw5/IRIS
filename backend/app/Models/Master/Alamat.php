<?php

namespace App\Models\Master;

use App\Models\Transaksi\_BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alamat extends _BaseModel
{
    use HasFactory;
    protected $table = "alamat_m";
    protected $fillable = [];
    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = "id";
}
