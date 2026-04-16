<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'name',
        'species',
        'class',
        'subclass',
        'background',
        'alignment',
        'level',
        'notes',
    ];

    protected $casts = [
        'level' => 'integer',
    ];
}
