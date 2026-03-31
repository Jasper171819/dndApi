<?php
// Developer context: This API controller is called by the character routes, receives already-validated request data, uses the Character model for database writes, and asks CharacterHitPointRoller for derived HP metadata.
// Clear explanation: This file handles the API requests for listing, viewing, saving, updating, and deleting characters.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacterRequest;
use App\Models\Character;
use App\Support\CharacterHitPointRoller;
use Illuminate\Http\JsonResponse;

class CharacterController extends Controller
{
    // Developer context: Laravel injects CharacterHitPointRoller here so HP-roll logic stays in one reusable place instead of being duplicated inside controller actions.
    // Clear explanation: This sets up the helper that calculates rolled hit point details for saved characters.
    public function __construct(
        private readonly CharacterHitPointRoller $characterHitPointRoller,
    ) {}

    // Developer context: This action pulls the latest Character records through Eloquent and returns them as JSON for the roster and builder pages.
    // Clear explanation: This method sends back the saved characters list.
    public function index(): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $characters = Character::latest()->get();

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json($characters);
    }

    // Developer context: This action looks up one Character by id and returns either the record or a 404 JSON response when it does not exist.
    // Clear explanation: This method sends back one saved character, or an error if that character cannot be found.
    public function show(int $id): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = Character::find($id);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden',
            ], 404);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json($character);
    }

    // Developer context: This action reads the normalized payload from StoreCharacterRequest, derives rolled HP metadata through CharacterHitPointRoller, and persists the final record with the Character model.
    // Clear explanation: This method saves a new character after the request data has already been checked and cleaned.
    public function store(StoreCharacterRequest $request): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $characterData = $request->characterData();
        $metadata = $this->characterHitPointRoller->metadataForCharacter($characterData);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = Character::create([...$characterData, ...$metadata]);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'message' => 'Character succesvol aangemaakt',
            'data' => $character,
        ], 201);
    }

    // Developer context: This action reuses the same validated payload and HP metadata flow as create, but applies it to the existing Character that route-model binding already loaded.
    // Clear explanation: This method updates an existing character with the cleaned form data.
    public function update(StoreCharacterRequest $request, Character $character): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $characterData = $request->characterData();
        $metadata = $this->characterHitPointRoller->metadataForCharacter($characterData, $character);
        $character->update([...$characterData, ...$metadata]);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'message' => 'Character succesvol bijgewerkt',
            'data' => $character->fresh(),
        ]);
    }

    // Developer context: This action finds the requested Character and deletes it, or returns a 404 JSON response when the id does not exist.
    // Clear explanation: This method removes a saved character.
    public function destroy(int $id): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = Character::find($id);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character) {
            return response()->json([
                'message' => 'Character niet gevonden',
            ], 404);
        }

        $character->delete();

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'message' => 'Character succesvol verwijderd',
        ]);
    }
}
