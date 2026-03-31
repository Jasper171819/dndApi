<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

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
