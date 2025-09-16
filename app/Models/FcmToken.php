<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    protected $fillable = [
        'token',
        'tokenable_id',
        'tokenable_type',
    ];

    public function tokenable()
    {
        return $this->morphTo();
    }

}
