<?php
// Developer context: This helper cleans the nested wizard state before the service uses or saves it; it relies on CharacterDataValidator for character fields and PlainTextNormalizer for dungeon-side text values.
// Clear explanation: This file cleans the wizard's saved state so broken or unexpected data does not corrupt the wizard flow.

namespace App\Support;

use Illuminate\Support\Str;

class RulesWizardStateSanitizer
{
    private const PREVIEW_STAT_FIELDS = [
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    private const DUNGEON_INT_FIELDS = [
        'max_hp',
        'current_hp',
        'hp_adjustment',
        'temp_hp',
        'ac',
        'exhaustion',
        'initiative_bonus',
        'last_initiative',
        'death_successes',
        'death_failures',
        'hit_dice_remaining',
    ];

    // Developer context: Laravel injects the shared character validator and text normalizer so wizard state cleanup follows the same rules as direct API saves.
    // Clear explanation: This sets up the helpers that keep the wizard state clean and consistent.
    public function __construct(
        private readonly CharacterDataValidator $characterData,
        private readonly PlainTextNormalizer $plainText,
    ) {}

    // Developer context: This method whitelists the top-level wizard state keys, normalizes the pending field, cleans skipped-field lists, and delegates character and dungeon cleanup to the right helpers.
    // Clear explanation: This method filters the wizard state down to the safe fields and cleans each part before the wizard keeps using it.
    public function sanitize(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $pendingField = $this->normalizePendingField($state['pending_field'] ?? null);
        $optionalFields = $this->characterData->optionalFields();

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'pending_field' => $pendingField,
            'skipped_optional_fields' => array_values(array_filter(
                is_array($state['skipped_optional_fields'] ?? null) ? $state['skipped_optional_fields'] : [],
                static fn ($value): bool => is_string($value) && in_array($value, $optionalFields, true),
            )),
            'random_preview' => $this->sanitizeRandomPreview(
                is_array($state['random_preview'] ?? null) ? $state['random_preview'] : [],
            ),
            'character' => $this->characterData->normalizeDraft(
                is_array($state['character'] ?? null) ? $state['character'] : [],
            ),
            'dungeon' => $this->sanitizeDungeon(
                is_array($state['dungeon'] ?? null) ? $state['dungeon'] : [],
            ),
        ];
    }

    // Developer context: Sanitizerandompreview keeps the wizard's temporary random suggestions safe so reroll and keep actions can survive a round trip through the browser.
    // Clear explanation: This cleans the temporary random suggestion the wizard is holding onto.
    private function sanitizeRandomPreview(array $preview): ?array
    {
        $kind = $this->plainText->normalize($preview['kind'] ?? null);
        $resumeField = $this->normalizePendingField($preview['resume_field'] ?? null);

        if (! is_string($kind)) {
            return null;
        }

        $kind = Str::lower($kind);

        if ($kind === 'field') {
            $field = $this->normalizePendingField($preview['field'] ?? null);

            if ($field === null) {
                return null;
            }

            $value = $this->sanitizePreviewFieldValue($field, $preview['value'] ?? null);

            if (! is_string($value) || $value === '') {
                return null;
            }

            return [
                'kind' => 'field',
                'field' => $field,
                'value' => $value,
                'resume_field' => $resumeField,
            ];
        }

        if ($kind !== 'stats') {
            return null;
        }

        $stats = is_array($preview['stats'] ?? null) ? $preview['stats'] : [];
        $sanitizedStats = [];

        foreach (self::PREVIEW_STAT_FIELDS as $field) {
            $value = $this->normalizeInteger($stats[$field] ?? null);

            if ($value === null || $value < 3 || $value > 18) {
                return null;
            }

            $sanitizedStats[$field] = $value;
        }

        return [
            'kind' => 'stats',
            'stats' => $sanitizedStats,
            'resume_field' => $resumeField,
        ];
    }

    // Developer context: Sanitizepreviewfieldvalue reuses the shared character normalizer so temporary wizard suggestions follow the same text-cleaning rules as saved character data.
    // Clear explanation: This cleans one temporary wizard suggestion the same way the app cleans saved character fields.
    private function sanitizePreviewFieldValue(string $field, mixed $value): ?string
    {
        $normalized = $this->characterData->normalizeDraft([$field => $value])[$field] ?? null;

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    // Developer context: Sanitizedungeon handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function sanitizeDungeon(array $dungeon): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $sanitized = [
            'max_hp' => null,
            'current_hp' => null,
            'hp_adjustment' => 0,
            'rolled_hit_points' => false,
            'temp_hp' => 0,
            'ac' => null,
            'conditions' => [],
            'exhaustion' => 0,
            'initiative_bonus' => null,
            'last_initiative' => null,
            'death_successes' => 0,
            'death_failures' => 0,
            'stable' => false,
            'concentration' => null,
            'spell_slots_remaining' => [],
            'hit_dice_remaining' => null,
        ];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach (self::DUNGEON_INT_FIELDS as $field) {
            if (array_key_exists($field, $dungeon)) {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $sanitized[$field] = $this->normalizeInteger($dungeon[$field]);
            }
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $sanitized['conditions'] = array_values(array_unique(array_filter(array_map(
            function ($entry): ?string {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $normalized = $this->plainText->normalize($entry);

                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if (! is_string($normalized)) {
                    return null;
                }

                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return in_array($normalized, config('dnd.conditions', []), true) ? $normalized : null;
            },
            is_array($dungeon['conditions'] ?? null) ? $dungeon['conditions'] : [],
        ))));

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $sanitized['stable'] = filter_var($dungeon['stable'] ?? false, FILTER_VALIDATE_BOOL);
        $sanitized['rolled_hit_points'] = filter_var($dungeon['rolled_hit_points'] ?? false, FILTER_VALIDATE_BOOL);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $sanitized['concentration'] = $this->limitText(
            $this->plainText->normalize($dungeon['concentration'] ?? null),
            120,
        );

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $slots = is_array($dungeon['spell_slots_remaining'] ?? null) ? $dungeon['spell_slots_remaining'] : [];
        foreach ($slots as $level => $count) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $slotLevel = $this->normalizeInteger($level);
            $slotCount = $this->normalizeInteger($count);

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($slotLevel === null || $slotCount === null || $slotLevel < 1 || $slotLevel > 9) {
                continue;
            }

            $sanitized['spell_slots_remaining'][(string) $slotLevel] = max(0, $slotCount);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $sanitized;
    }

    // Developer context: Normalizependingfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizePendingField(mixed $value): ?string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = $this->plainText->normalize($value);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($normalized)) {
            return null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return in_array($normalized, $this->characterData->knownFields(), true) ? $normalized : null;
    }

    // Developer context: Normalizeinteger handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeInteger(mixed $value): ?int
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($value === null || $value === '') {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = trim((string) $value);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^-?\d+$/', $normalized) !== 1) {
            return null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return (int) $normalized;
    }

    // Developer context: Limittext handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function limitText(?string $value, int $limit): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($value === null) {
            return null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return Str::length($value) > $limit ? Str::substr($value, 0, $limit) : $value;
    }
}
