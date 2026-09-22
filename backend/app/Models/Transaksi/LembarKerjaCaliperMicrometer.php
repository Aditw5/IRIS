<?php

namespace App\Models\Transaksi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LembarKerjaCaliperMicrometer extends _BaseModel
{
    use HasFactory;
    protected $table = "lembarkerjacalipermicrometer_t";
    protected $fillable = [];
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = "norec";
}
