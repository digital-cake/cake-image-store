<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = ['shop', 'secret'];

    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
