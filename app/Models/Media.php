<?php

namespace App\Models;

use App\Traits\ImageUrlFormatter;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{

    protected $table = 'media';

    protected $fillable = [
        'filename',
        'mime_type',
        'file_path',
        'mediable_id',
        'mediable_type',
        'order',
        'description'
    ];

    public function mediable()
    {
        return $this->morphTo();
    }
}
