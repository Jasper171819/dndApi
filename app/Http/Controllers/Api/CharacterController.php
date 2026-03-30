<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CharacterController extends Controller
{
    public function index(): JsonResponse
    {
        $characters = Character::latest()->get();

        return response()->json($characters);
    }

    public function show(int $id): JsonResponse
    {
        $character = Character::find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden'
            ], 404);
        }

        return response()->json($character);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->all();
        $classDetails = config('dnd.class_details', []);

        if (! array_key_exists('species', $input) && array_key_exists('race', $input)) {
            $input['species'] = $input['race'];
        }

        foreach ([
            'alignment',
            'origin_feat',
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
        ] as $nullableField) {
            if (($input[$nullableField] ?? null) === '') {
                $input[$nullableField] = null;
            }
        }

        if (array_key_exists('language', $input) && ! array_key_exists('languages', $input)) {
            $input['languages'] = [$input['language']];
        }

        if (isset($input['languages']) && is_string($input['languages'])) {
            $input['languages'] = array_values(array_filter(array_map(
                static fn (string $entry): string => trim($entry),
                preg_split('/[,|\n\r]+/', $input['languages']) ?: [],
            )));
        }

        if (isset($input['languages']) && is_array($input['languages'])) {
            $input['languages'] = array_values(array_unique(array_filter(array_map(
                static fn ($entry): string => trim((string) $entry),
                $input['languages'],
            ))));
        }

        if (! isset($input['languages']) || $input['languages'] === '') {
            $input['languages'] = [];
        }

        $validated = validator($input, [
            'name' => ['required', 'string', 'max:255'],
            'species' => ['required', 'string', Rule::in(config('dnd.species', []))],
            'class' => ['required', 'string', Rule::in(config('dnd.classes', []))],
            'subclass' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($input, $classDetails): void {
                    $class = $input['class'] ?? null;
                    $subclasses = $classDetails[$class]['subclasses'] ?? [];

                    if (! in_array($value, $subclasses, true)) {
                        $fail('The selected subclass is not valid for the chosen class.');
                    }
                },
            ],
            'background' => ['required', 'string', Rule::in(config('dnd.backgrounds', []))],
            'alignment' => ['nullable', 'string', Rule::in(config('dnd.alignments', []))],
            'origin_feat' => ['required', 'string', Rule::in(config('dnd.origin_feats', []))],
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => ['string', Rule::in(config('dnd.languages', []))],
            'personality_traits' => ['nullable', 'string', 'max:1000'],
            'ideals' => ['nullable', 'string', 'max:1000'],
            'bonds' => ['nullable', 'string', 'max:1000'],
            'flaws' => ['nullable', 'string', 'max:1000'],
            'age' => ['nullable', 'string', 'max:255'],
            'height' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'string', 'max:255'],
            'eyes' => ['nullable', 'string', 'max:255'],
            'hair' => ['nullable', 'string', 'max:255'],
            'skin' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1'],
            'strength' => ['required', 'integer', 'min:3', 'max:18'],
            'dexterity' => ['required', 'integer', 'min:3', 'max:18'],
            'constitution' => ['required', 'integer', 'min:3', 'max:18'],
            'intelligence' => ['required', 'integer', 'min:3', 'max:18'],
            'wisdom' => ['required', 'integer', 'min:3', 'max:18'],
            'charisma' => ['required', 'integer', 'min:3', 'max:18'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $character = Character::create($validated);

        return response()->json([
            'message' => 'Character succesvol aangemaakt',
            'data' => $character,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $character = Character::find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden'
            ], 404);
        }

        $character->delete();

        return response()->json([
            'message' => 'Character succesvol verwijderd'
        ]);
    }
}
