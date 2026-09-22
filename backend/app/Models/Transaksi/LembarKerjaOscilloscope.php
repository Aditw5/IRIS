<?php

namespace App\Models\Transaksi;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class LembarKerjaOscilloscope extends _BaseModel
{
    use HasFactory;

    protected $table = "lembarkerjaoscilloscope_t";
    protected $fillable = [];
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = "norec";
}
