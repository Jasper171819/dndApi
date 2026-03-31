<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RollDiceRequest;
use App\Support\DiceRoller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DiceController extends Controller
{
    // Developer context: Construct handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function __construct(
        private readonly DiceRoller $diceRoller,
    ) {}

    // Developer context: Roll handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function roll(RollDiceRequest $request): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $validated = $request->validated();
        $result = $this->diceRoller->rollExpression($validated['expression'], $validated['mode'] ?? null);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($result === null) {
            return response()->json([
                'message' => 'The dice expression could not be parsed.',
            ], 422);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'expression' => Str::of($validated['expression'])->lower()->squish()->toString(),
            'mode' => $validated['mode'] ?? null,
            'total' => $result['total'],
            'detail' => $result['detail'],
        ]);
    }

    // Developer context: Rollstats handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rollStats(): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $details = [];
        foreach (['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $field) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $details[$field] = $this->diceRoller->rollAbilityScoreDetail();
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $stats = array_map(static fn (array $detail): int => $detail['total'], $details);
        $stats['details'] = $details;

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json($stats);
    }
}
