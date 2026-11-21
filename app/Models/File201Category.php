<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File201Category extends Model
{
    protected $table = 'file_201_categories';

    protected $fillable = [
        'slug',
        'label',
    ];
}
