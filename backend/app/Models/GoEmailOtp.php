<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GoEmailOtp extends Model
{
    protected $table = 'go_email_otps';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'email', 'code', 'purpose',
        'expires_at', 'consumed_at', 'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function scopeActive($q)
    {
        return $q->whereNull('consumed_at')->where('expires_at', '>', Carbon::now());
    }
}
