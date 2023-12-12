<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $fillable = ['path'];

    protected $appends = ['src'];

    public function getSrcAttribute()
    {
        return Storage::disk('s3')->url($this->attributes['path']);
    }
}
