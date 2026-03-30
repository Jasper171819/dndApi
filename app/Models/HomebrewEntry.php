<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomebrewEntry extends Model
{
    protected $fillable = [
        'category',
        'status',
        'name',
        'summary',
        'details',
        'source_notes',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];
}
