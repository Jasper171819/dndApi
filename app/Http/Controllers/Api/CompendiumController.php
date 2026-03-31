<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CompendiumController extends Controller
{
    // Developer context: Index handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function index(): JsonResponse
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'verified_at' => config('dnd.verified_at'),
            'source_note' => config('dnd.source_note'),
            'sections' => config('dnd.compendium_sections', []),
            'compendium' => config('dnd.compendium', []),
        ]);
    }

    // Developer context: Show handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function show(string $section): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = config("dnd.compendium.{$section}");

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_array($entry)) {
            return response()->json([
                'message' => 'Compendium section not found',
            ], 404);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'verified_at' => config('dnd.verified_at'),
            'section' => $entry,
        ]);
    }
}
