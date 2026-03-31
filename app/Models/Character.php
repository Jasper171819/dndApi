<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

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
        'advancement_method',
        'languages',
        'skill_proficiencies',
        'skill_expertise',
        'personality_traits',
        'ideals',
        'goals',
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
        'hp_adjustment',
        'rolled_hit_points',
        'notes',
    ];

    protected $casts = [
        'languages' => 'array',
        'skill_proficiencies' => 'array',
        'skill_expertise' => 'array',
        'rolled_hit_points' => 'boolean',
    ];
}
