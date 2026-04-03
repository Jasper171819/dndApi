<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacterRequest;
use App\Models\Character;
use Illuminate\Http\JsonResponse;

class CharacterController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Character::query()->latest()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $character = Character::query()->find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden.',
            ], 404);
        }

        return response()->json([
            'data' => $character,
        ]);
    }

    public function store(StoreCharacterRequest $request): JsonResponse
    {
        $character = Character::query()->create($request->characterData());

        return response()->json([
            'message' => 'Character opgeslagen.',
            'data' => $character,
        ], 201);
    }

    public function update(StoreCharacterRequest $request, int $id): JsonResponse
    {
        $character = Character::query()->find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden.',
            ], 404);
        }

        $character->update($request->characterData());

        return response()->json([
            'message' => 'Character bijgewerkt.',
            'data' => $character->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $character = Character::query()->find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden.',
            ], 404);
        }

        $character->delete();

        return response()->json([
            'message' => 'Character verwijderd.',
        ]);
    }
}
