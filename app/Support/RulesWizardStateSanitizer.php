<?php

namespace App\Support;

use Illuminate\Support\Str;

class RulesWizardStateSanitizer
{
    private const DUNGEON_INT_FIELDS = [
        'max_hp',
        'current_hp',
        'temp_hp',
        'ac',
        'exhaustion',
        'initiative_bonus',
        'last_initiative',
        'death_successes',
        'death_failures',
        'hit_dice_remaining',
    ];

    public function __construct(
        private readonly CharacterDataValidator $characterData,
        private readonly PlainTextNormalizer $plainText,
    ) {}

    public function sanitize(array $state): array
    {
        $pendingField = $this->normalizePendingField($state['pending_field'] ?? null);
        $optionalFields = $this->characterData->optionalFields();

        return [
            'pending_field' => $pendingField,
            'skipped_optional_fields' => array_values(array_filter(
                is_array($state['skipped_optional_fields'] ?? null) ? $state['skipped_optional_fields'] : [],
                static fn ($value): bool => is_string($value) && in_array($value, $optionalFields, true),
            )),
            'character' => $this->characterData->normalizeDraft(
                is_array($state['character'] ?? null) ? $state['character'] : [],
            ),
            'dungeon' => $this->sanitizeDungeon(
                is_array($state['dungeon'] ?? null) ? $state['dungeon'] : [],
            ),
        ];
    }

    private function sanitizeDungeon(array $dungeon): array
    {
        $sanitized = [
            'max_hp' => null,
            'current_hp' => null,
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

        foreach (self::DUNGEON_INT_FIELDS as $field) {
            if (array_key_exists($field, $dungeon)) {
                $sanitized[$field] = $this->normalizeInteger($dungeon[$field]);
            }
        }

        $sanitized['conditions'] = array_values(array_unique(array_filter(array_map(
            function ($entry): ?string {
                $normalized = $this->plainText->normalize($entry);

                if (! is_string($normalized)) {
                    return null;
                }

                return in_array($normalized, config('dnd.conditions', []), true) ? $normalized : null;
            },
            is_array($dungeon['conditions'] ?? null) ? $dungeon['conditions'] : [],
        ))));

        $sanitized['stable'] = filter_var($dungeon['stable'] ?? false, FILTER_VALIDATE_BOOL);
        $sanitized['concentration'] = $this->limitText(
            $this->plainText->normalize($dungeon['concentration'] ?? null),
            120,
        );

        $slots = is_array($dungeon['spell_slots_remaining'] ?? null) ? $dungeon['spell_slots_remaining'] : [];
        foreach ($slots as $level => $count) {
            $slotLevel = $this->normalizeInteger($level);
            $slotCount = $this->normalizeInteger($count);

            if ($slotLevel === null || $slotCount === null || $slotLevel < 1 || $slotLevel > 9) {
                continue;
            }

            $sanitized['spell_slots_remaining'][(string) $slotLevel] = max(0, $slotCount);
        }

        return $sanitized;
    }

    private function normalizePendingField(mixed $value): ?string
    {
        $normalized = $this->plainText->normalize($value);

        if (! is_string($normalized)) {
            return null;
        }

        return in_array($normalized, $this->characterData->knownFields(), true) ? $normalized : null;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = trim((string) $value);

        if (preg_match('/^-?\d+$/', $normalized) !== 1) {
            return null;
        }

        return (int) $normalized;
    }

    private function limitText(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::length($value) > $limit ? Str::substr($value, 0, $limit) : $value;
    }
}
