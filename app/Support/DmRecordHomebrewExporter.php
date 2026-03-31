<?php
// Developer context: This support class owns the explicit one-way export from DM records into normal homebrew entries so the mapping logic stays out of controllers and the wizard.
// Clear explanation: This file turns a saved DM record into a separate homebrew entry when the DM chooses to export it.

namespace App\Support;

use App\Models\DmRecord;
use App\Models\HomebrewEntry;

class DmRecordHomebrewExporter
{
    // Developer context: This method maps the saved DM record onto a homebrew entry, reusing an existing linked entry when possible.
    // Clear explanation: This creates or updates the matching homebrew entry for the chosen DM record.
    public function export(DmRecord $record): HomebrewEntry
    {
        $entry = $record->linkedHomebrewEntry ?: new HomebrewEntry();

        $entry->fill([
            'category' => $this->categoryFor($record),
            'status' => $this->statusFor($record),
            'name' => $record->name,
            'summary' => $record->summary,
            'details' => $this->detailsFor($record),
            'source_notes' => sprintf('Exported from DM record #%d (%s).', $record->id, strtoupper($record->kind)),
            'tags' => $this->tagsFor($record),
        ]);

        $entry->save();

        $record->linked_homebrew_entry_id = $entry->id;
        $record->save();

        return $entry->fresh();
    }

    // Developer context: This helper decides which homebrew category should receive the exported DM record.
    // Clear explanation: This chooses whether the export becomes a monster, item, or general homebrew note.
    private function categoryFor(DmRecord $record): string
    {
        if ($record->kind === 'loot') {
            return 'item';
        }

        if ($record->kind === 'npc') {
            $combatMode = $record->payload['combat_mode'] ?? 'narrative_only';

            return in_array($combatMode, ['quick_stats', 'monster_backed'], true) ? 'monster' : 'other';
        }

        return 'other';
    }

    // Developer context: This helper maps DM record statuses onto the smaller homebrew status set already used by the workshop.
    // Clear explanation: This chooses the closest homebrew status for the exported record.
    private function statusFor(DmRecord $record): string
    {
        return match ($record->status) {
            'ready', 'active' => 'table-ready',
            'archived' => 'playtest',
            default => 'draft',
        };
    }

    // Developer context: This helper composes a readable details block from the DM record payload so the export remains useful outside the DM wizard.
    // Clear explanation: This turns the record details into a readable notes block inside the homebrew entry.
    private function detailsFor(DmRecord $record): string
    {
        $lines = [];

        foreach ($record->payload as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if ($key === 'quick_stats' && is_array($value)) {
                foreach ($value as $statKey => $statValue) {
                    if ($statValue === null || $statValue === '') {
                        continue;
                    }

                    $lines[] = $this->label($statKey).': '.$this->stringify($statValue);
                }

                continue;
            }

            $lines[] = $this->label($key).': '.$this->stringify($value);
        }

        return implode("\n", $lines);
    }

    // Developer context: This helper merges the record kind and existing tags so the export stays searchable in the homebrew workshop.
    // Clear explanation: This adds a few useful tags to the export so it is easier to find later.
    private function tagsFor(DmRecord $record): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [$record->kind, 'dm-export'],
            is_array($record->tags) ? $record->tags : [],
        ))));
    }

    // Developer context: This helper formats stored values for export, handling arrays in a readable way instead of serializing raw JSON.
    // Clear explanation: This turns saved values into readable text for the homebrew entry.
    private function stringify(mixed $value): string
    {
        if (is_array($value)) {
            $items = array_map(
                fn (mixed $item): string => is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) ?: '' : (string) $item,
                $value,
            );

            return implode(', ', array_filter($items));
        }

        return (string) $value;
    }

    // Developer context: This helper turns snake-case keys into human-readable labels for the export details block.
    // Clear explanation: This makes field names look readable in the exported notes.
    private function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
