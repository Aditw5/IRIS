<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapKsmToRuangan extends Model
{
    use HasFactory;

    protected $table = "mapkelompokstafmedistoruangan_m";

    // Allow mass assignment on 'id' and other required fields
    protected $fillable = [];

    public $timestamps = true;
    public $incrementing = false; // Since you're using a custom primary key
    protected $primaryKey = "id";
}
