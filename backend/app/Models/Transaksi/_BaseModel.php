<?php

namespace App\Models\Transaksi;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use App\Traits\Valet;
class _BaseModel extends Model
{
    use Valet;
    public function generateNewId()
    {
        return $this->Uuid4();
        // return Uuid::uuid4();
        /*
        | This generates a Version 1: Time-based UUID.
        */
    }
    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         $model->norec = (string)$model->generateNewId();
    //     });
    // }

}
