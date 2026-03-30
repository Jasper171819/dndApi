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
        $characters = Character::latest()->get();

        return response()->json($characters);
    }

    public function show(int $id): JsonResponse
    {
        $character = Character::find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden',
            ], 404);
        }

        return response()->json($character);
    }

    public function store(StoreCharacterRequest $request): JsonResponse
    {
        $character = Character::create($request->characterData());

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
                'message' => 'Character niet gevonden',
            ], 404);
        }

        $character->delete();

        return response()->json([
            'message' => 'Character succesvol verwijderd',
        ]);
    }
}
