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
        'origin_feat',
        'languages',
        'personality_traits',
        'ideals',
        'bonds',
        'flaws',
        'age',
        'height',
        'weight',
        'eyes',
        'hair',
        'skin',
        'level',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'notes',
    ];

    protected $casts = [
        'languages' => 'array',
    ];
}
