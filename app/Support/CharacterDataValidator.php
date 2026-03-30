<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CharacterDataValidator
{
    private const ARRAY_FIELDS = [
        'languages',
        'skill_proficiencies',
        'skill_expertise',
    ];

    private const INTEGER_FIELDS = [
        'level',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    private const SHORT_TEXT_LIMITS = [
        'name' => 255,
        'species' => 255,
        'class' => 255,
        'subclass' => 255,
        'background' => 255,
        'alignment' => 255,
        'origin_feat' => 255,
        'age' => 255,
        'height' => 255,
        'weight' => 255,
        'eyes' => 255,
        'hair' => 255,
        'skin' => 255,
    ];

    private const LONG_TEXT_LIMITS = [
        'personality_traits' => 1000,
        'ideals' => 1000,
        'bonds' => 1000,
        'flaws' => 1000,
        'notes' => 2000,
    ];

    private const OPTIONAL_FIELDS = [
        'alignment',
        'skill_expertise',
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
        'notes',
    ];

    private const KNOWN_FIELDS = [
        'name',
        'species',
        'class',
        'subclass',
        'skill_proficiencies',
        'skill_expertise',
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

    public function __construct(
        private readonly PlainTextNormalizer $plainText,
    ) {}

    public function normalizeDraft(array $input): array
    {
        $input = $this->normalizeAliases($input);

        return [
            'name' => $this->normalizeText($input['name'] ?? null),
            'species' => $this->normalizeText($input['species'] ?? null),
            'class' => $this->normalizeText($input['class'] ?? null),
            'subclass' => $this->normalizeText($input['subclass'] ?? null),
            'skill_proficiencies' => $this->normalizeList($input['skill_proficiencies'] ?? []),
            'skill_expertise' => $this->normalizeList($input['skill_expertise'] ?? []),
            'background' => $this->normalizeText($input['background'] ?? null),
            'alignment' => $this->normalizeText($input['alignment'] ?? null),
            'origin_feat' => $this->normalizeText($input['origin_feat'] ?? null),
            'languages' => $this->normalizeList($input['languages'] ?? []),
            'personality_traits' => $this->normalizeText($input['personality_traits'] ?? null, true),
            'ideals' => $this->normalizeText($input['ideals'] ?? null, true),
            'bonds' => $this->normalizeText($input['bonds'] ?? null, true),
            'flaws' => $this->normalizeText($input['flaws'] ?? null, true),
            'age' => $this->normalizeText($input['age'] ?? null),
            'height' => $this->normalizeText($input['height'] ?? null),
            'weight' => $this->normalizeText($input['weight'] ?? null),
            'eyes' => $this->normalizeText($input['eyes'] ?? null),
            'hair' => $this->normalizeText($input['hair'] ?? null),
            'skin' => $this->normalizeText($input['skin'] ?? null),
            'level' => $this->normalizeInteger($input['level'] ?? null),
            'strength' => $this->normalizeInteger($input['strength'] ?? null),
            'dexterity' => $this->normalizeInteger($input['dexterity'] ?? null),
            'constitution' => $this->normalizeInteger($input['constitution'] ?? null),
            'intelligence' => $this->normalizeInteger($input['intelligence'] ?? null),
            'wisdom' => $this->normalizeInteger($input['wisdom'] ?? null),
            'charisma' => $this->normalizeInteger($input['charisma'] ?? null),
            'notes' => $this->normalizeText($input['notes'] ?? null, true),
        ];
    }

    public function validateForSave(array $input): array
    {
        $normalized = $this->normalizeDraft($input);

        return Validator::make($normalized, $this->rules($normalized))->validate();
    }

    public function rules(array $input): array
    {
        $classDetails = config('dnd.class_details', []);

        return [
            'name' => ['required', 'string', 'max:'.$this->textLimit('name')],
            'species' => ['required', 'string', Rule::in(config('dnd.species', []))],
            'class' => ['required', 'string', Rule::in(config('dnd.classes', []))],
            'subclass' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($input, $classDetails): void {
                    $class = $input['class'] ?? null;
                    $subclasses = $classDetails[$class]['subclasses'] ?? [];

                    if (! in_array($value, $subclasses, true)) {
                        $fail('The selected subclass is not valid for the chosen class.');
                    }
                },
            ],
            'skill_proficiencies' => ['required', 'array', 'min:1'],
            'skill_proficiencies.*' => ['string', Rule::in(config('dnd.skills', []))],
            'skill_expertise' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail) use ($input): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $proficiencies = is_array($input['skill_proficiencies'] ?? null)
                        ? $input['skill_proficiencies']
                        : [];

                    foreach ($value as $entry) {
                        if (! in_array((string) $entry, $proficiencies, true)) {
                            $fail('Skill expertise can only be assigned to selected skill proficiencies.');

                            return;
                        }
                    }
                },
            ],
            'skill_expertise.*' => ['string', Rule::in(config('dnd.skills', []))],
            'background' => ['required', 'string', Rule::in(config('dnd.backgrounds', []))],
            'alignment' => ['nullable', 'string', Rule::in(config('dnd.alignments', []))],
            'origin_feat' => ['required', 'string', Rule::in(config('dnd.origin_feats', []))],
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => ['string', Rule::in(config('dnd.languages', []))],
            'personality_traits' => ['nullable', 'string', 'max:'.$this->textLimit('personality_traits')],
            'ideals' => ['nullable', 'string', 'max:'.$this->textLimit('ideals')],
            'bonds' => ['nullable', 'string', 'max:'.$this->textLimit('bonds')],
            'flaws' => ['nullable', 'string', 'max:'.$this->textLimit('flaws')],
            'age' => ['nullable', 'string', 'max:'.$this->textLimit('age')],
            'height' => ['nullable', 'string', 'max:'.$this->textLimit('height')],
            'weight' => ['nullable', 'string', 'max:'.$this->textLimit('weight')],
            'eyes' => ['nullable', 'string', 'max:'.$this->textLimit('eyes')],
            'hair' => ['nullable', 'string', 'max:'.$this->textLimit('hair')],
            'skin' => ['nullable', 'string', 'max:'.$this->textLimit('skin')],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'strength' => ['required', 'integer', 'min:3', 'max:18'],
            'dexterity' => ['required', 'integer', 'min:3', 'max:18'],
            'constitution' => ['required', 'integer', 'min:3', 'max:18'],
            'intelligence' => ['required', 'integer', 'min:3', 'max:18'],
            'wisdom' => ['required', 'integer', 'min:3', 'max:18'],
            'charisma' => ['required', 'integer', 'min:3', 'max:18'],
            'notes' => ['nullable', 'string', 'max:'.$this->textLimit('notes')],
        ];
    }

    public function knownFields(): array
    {
        return self::KNOWN_FIELDS;
    }

    public function optionalFields(): array
    {
        return self::OPTIONAL_FIELDS;
    }

    public function arrayFields(): array
    {
        return self::ARRAY_FIELDS;
    }

    public function textLimit(string $field): ?int
    {
        return self::SHORT_TEXT_LIMITS[$field]
            ?? self::LONG_TEXT_LIMITS[$field]
            ?? null;
    }

    private function normalizeAliases(array $input): array
    {
        if (! array_key_exists('species', $input) && array_key_exists('race', $input)) {
            $input['species'] = $input['race'];
        }

        foreach ([
            'language' => 'languages',
            'skill' => 'skill_proficiencies',
            'skills' => 'skill_proficiencies',
            'expertise' => 'skill_expertise',
        ] as $legacyField => $normalizedField) {
            if (array_key_exists($legacyField, $input) && ! array_key_exists($normalizedField, $input)) {
                $input[$normalizedField] = $input[$legacyField];
            }
        }

        return array_intersect_key($input, array_flip(self::KNOWN_FIELDS));
    }

    private function normalizeText(mixed $value, bool $multiline = false): ?string
    {
        return $this->plainText->normalize($value, $multiline);
    }

    private function normalizeList(mixed $value): array
    {
        $entries = match (true) {
            is_string($value) => preg_split('/[,|\n\r]+/', $value) ?: [],
            is_array($value) => $value,
            default => [],
        };

        $normalized = array_values(array_filter(array_map(
            fn (mixed $entry): ?string => $this->normalizeText($entry),
            $entries,
        )));

        return array_values(array_unique($normalized));
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) || is_float($value)) {
            $normalized = trim((string) $value);

            if (preg_match('/^-?\d+$/', $normalized) === 1) {
                return (int) $normalized;
            }
        }

        return null;
    }
}
