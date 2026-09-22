<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class MasterHariLibur extends Model
{
    protected $table = 'master_hari_libur_m';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'tanggal',
        'nama_libur',
        'jenis_libur',
        'source_api',
        'tahun',
        'statusenabled',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tahun' => 'integer',
        'statusenabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}