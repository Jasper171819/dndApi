<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHomebrewEntryRequest;
use App\Models\HomebrewEntry;
use Illuminate\Http\JsonResponse;

class HomebrewController extends Controller
{
    // Developer context: Index handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function index(): JsonResponse
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'official_note' => config('homebrew.official_note'),
            'categories' => config('homebrew.categories', []),
            'statuses' => config('homebrew.statuses', []),
            'entries' => HomebrewEntry::query()
                ->latest()
                ->get(),
        ]);
    }

    // Developer context: Store handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function store(StoreHomebrewEntryRequest $request): JsonResponse
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = HomebrewEntry::create($request->entryData());

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'message' => 'Homebrew entry saved.',
            'data' => $entry,
        ], 201);
    }

    // Developer context: Update handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function update(StoreHomebrewEntryRequest $request, HomebrewEntry $homebrewEntry): JsonResponse
    {
        $homebrewEntry->update($request->entryData());

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'message' => 'Homebrew entry updated.',
            'data' => $homebrewEntry->fresh(),
        ]);
    }

    // Developer context: Destroy handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function destroy(HomebrewEntry $homebrewEntry): JsonResponse
    {
        $homebrewEntry->delete();

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json([
            'message' => 'Homebrew entry removed.',
        ]);
    }
}
