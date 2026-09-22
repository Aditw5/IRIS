<?php

namespace App\Models\Standar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UlabDigitalRepo extends Model
{
    use HasFactory;
    
    protected $table = "ulabdigitalrepo_m";
    protected $fillable = [];
    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = "id";


}
