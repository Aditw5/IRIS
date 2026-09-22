<?php

namespace App\Models\Master;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $table = "users";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = 'string';

    // Sesuaikan field yang boleh di-mass-assign
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];

    // Sembunyikan field sensitif dari JSON output
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Cast kolom timestamp dan email_verified_at ke DateTime
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function scopeMine(Builder $builder, int $kdProfile = null)
    {
        if (!empty(request()->session()->get('kdProfile'))) {
            $kdProfile = request()->session()->get('kdProfile');
        } else {
            $kdProfile = Profile::where('statusenabled', true)->first()->id;
        }
        return $builder->where("kdprofile", $kdProfile)
            ->where('statusenabled', true)
            ->select('id', 'name');
    }
    public function scopeSearch(Builder $builder, string $search = null)
    {
        if (empty($search)) {
            return $builder;
        }

        return $builder->where(function (Builder $sql) use ($search) {
            $sql->where("name", "like", "%{$search}%");
        });
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\CustomVerifyEmailNotification);
    }
}
