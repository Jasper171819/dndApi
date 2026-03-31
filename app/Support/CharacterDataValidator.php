<?php
// Developer context: This support class is the shared contract for character data; requests and the wizard both call into it so normalization and validation rules stay identical across create, update, and save flows.
// Clear explanation: This file cleans character input and checks whether the final character data is allowed to be saved.

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
        'advancement_method' => 255,
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
        'goals' => 1000,
        'bonds' => 1000,
        'flaws' => 1000,
        'notes' => 2000,
    ];

    private const OPTIONAL_FIELDS = [
        'alignment',
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
        'advancement_method',
        'languages',
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
        'notes',
    ];

    // Developer context: Laravel injects PlainTextNormalizer so every free-text field is cleaned in the same way before validation rules run.
    // Clear explanation: This sets up the text-cleaning helper used on names, notes, roleplay fields, and similar inputs.
    public function __construct(
        private readonly PlainTextNormalizer $plainText,
    ) {}

    // Developer context: This method whitelists the known character fields, maps old aliases onto the current field names, and normalizes strings, lists, and integers before any validation happens.
    // Clear explanation: This method cleans the raw character input into one consistent shape before the app checks or saves it.
    public function normalizeDraft(array $input): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $input = $this->normalizeAliases($input);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
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
            'advancement_method' => $this->normalizeText($input['advancement_method'] ?? null),
            'languages' => $this->normalizeList($input['languages'] ?? []),
            'personality_traits' => $this->normalizeText($input['personality_traits'] ?? null, true),
            'ideals' => $this->normalizeText($input['ideals'] ?? null, true),
            'goals' => $this->normalizeText($input['goals'] ?? null, true),
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

    // Developer context: This method is the shared save path used by the API and wizard; it normalizes the payload first and then runs the Laravel validator built from the official rules config.
    // Clear explanation: This method is the final check that decides whether a character payload is safe to save.
    public function validateForSave(array $input): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = $this->normalizeDraft($input);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return Validator::make($normalized, $this->rules($normalized))->validate();
    }

    // Developer context: This method builds the validation rules array, pulling allowed values from config and using closures where the rules depend on related fields like class, subclass, and expertise.
    // Clear explanation: This method lists the rules a character must follow, such as allowed classes, required fields, and valid skill choices.
    public function rules(array $input): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $classDetails = config('dnd.class_details', []);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'name' => ['required', 'string', 'max:'.$this->textLimit('name')],
            'species' => ['required', 'string', Rule::in(config('dnd.species', []))],
            'class' => ['required', 'string', Rule::in(config('dnd.classes', []))],
            'subclass' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($input, $classDetails): void {
                    // Developer context: This assignment stores a working value that the next lines reuse.
                    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                    $class = $input['class'] ?? null;
                    $subclasses = $classDetails[$class]['subclasses'] ?? [];

                    // Developer context: This branch checks a rule before the workflow continues down one path.
                    // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
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
                    // Developer context: This branch checks a rule before the workflow continues down one path.
                    // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                    if (! is_array($value)) {
                        return;
                    }

                    // Developer context: This assignment stores a working value that the next lines reuse.
                    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                    $proficiencies = is_array($input['skill_proficiencies'] ?? null)
                        ? $input['skill_proficiencies']
                        : [];

                    // Developer context: This loop applies the same step to each entry in the current list.
                    // Clear explanation: This line repeats the same work for every item in a group.
                    foreach ($value as $entry) {
                        if (! in_array((string) $entry, $proficiencies, true)) {
                            $fail('Skill expertise can only be assigned to selected skill proficiencies.');

                            // Developer context: This return hands the finished value or response back to the caller.
                            // Clear explanation: This line sends the result back so the rest of the app can use it.
                            return;
                        }
                    }
                },
            ],
            'skill_expertise.*' => ['string', Rule::in(config('dnd.skills', []))],
            'background' => ['required', 'string', Rule::in(config('dnd.backgrounds', []))],
            'alignment' => ['nullable', 'string', Rule::in(config('dnd.alignments', []))],
            'origin_feat' => ['required', 'string', Rule::in(config('dnd.origin_feats', []))],
            'advancement_method' => ['required', 'string', Rule::in(config('dnd.advancement_methods', []))],
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => ['string', Rule::in(config('dnd.languages', []))],
            'personality_traits' => ['nullable', 'string', 'max:'.$this->textLimit('personality_traits')],
            'ideals' => ['nullable', 'string', 'max:'.$this->textLimit('ideals')],
            'goals' => ['nullable', 'string', 'max:'.$this->textLimit('goals')],
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

    // Developer context: Knownfields handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function knownFields(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return self::KNOWN_FIELDS;
    }

    // Developer context: Optionalfields handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function optionalFields(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return self::OPTIONAL_FIELDS;
    }

    // Developer context: Arrayfields handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function arrayFields(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return self::ARRAY_FIELDS;
    }

    // Developer context: Textlimit handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function textLimit(string $field): ?int
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return self::SHORT_TEXT_LIMITS[$field]
            ?? self::LONG_TEXT_LIMITS[$field]
            ?? null;
    }

    // Developer context: Normalizealiases handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeAliases(array $input): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! array_key_exists('species', $input) && array_key_exists('race', $input)) {
            $input['species'] = $input['race'];
        }

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ([
            'language' => 'languages',
            'skill' => 'skill_proficiencies',
            'skills' => 'skill_proficiencies',
            'expertise' => 'skill_expertise',
        ] as $legacyField => $normalizedField) {
            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (array_key_exists($legacyField, $input) && ! array_key_exists($normalizedField, $input)) {
                $input[$normalizedField] = $input[$legacyField];
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_intersect_key($input, array_flip(self::KNOWN_FIELDS));
    }

    // Developer context: Normalizetext handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeText(mixed $value, bool $multiline = false): ?string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->plainText->normalize($value, $multiline);
    }

    // Developer context: Normalizelist handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeList(mixed $value): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entries = match (true) {
            is_string($value) => preg_split('/[,|\n\r]+/', $value) ?: [],
            is_array($value) => $value,
            default => [],
        };

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = array_values(array_filter(array_map(
            fn (mixed $entry): ?string => $this->normalizeText($entry),
            $entries,
        )));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_unique($normalized));
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

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_int($value)) {
            return $value;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_string($value) || is_float($value)) {
            $normalized = trim((string) $value);

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (preg_match('/^-?\d+$/', $normalized) === 1) {
                return (int) $normalized;
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return null;
    }
}
