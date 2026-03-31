<?php
// Developer context: This Eloquent model represents reusable DM-side records like NPCs, scenes, quests, and encounters, and it is shared by the DM records API and DM wizard save/load flows.
// Clear explanation: This file is the database model for saved DM records.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmRecord extends Model
{
    protected $fillable = [
        'kind',
        'status',
        'name',
        'summary',
        'campaign',
        'session_label',
        'tags',
        'payload',
        'linked_homebrew_entry_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'payload' => 'array',
    ];

    // Developer context: This relation links a DM record to the Homebrew entry created from an explicit export action.
    // Clear explanation: This lets the app look up the homebrew entry that came from this DM record.
    public function linkedHomebrewEntry(): BelongsTo
    {
        return $this->belongsTo(HomebrewEntry::class, 'linked_homebrew_entry_id');
    }
}
