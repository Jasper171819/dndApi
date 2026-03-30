<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHomebrewEntryRequest;
use App\Models\HomebrewEntry;
use Illuminate\Http\JsonResponse;

class HomebrewController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'official_note' => config('homebrew.official_note'),
            'categories' => config('homebrew.categories', []),
            'statuses' => config('homebrew.statuses', []),
            'entries' => HomebrewEntry::query()
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreHomebrewEntryRequest $request): JsonResponse
    {
        $entry = HomebrewEntry::create($request->entryData());

        return response()->json([
            'message' => 'Homebrew entry saved.',
            'data' => $entry,
        ], 201);
    }

    public function destroy(HomebrewEntry $homebrewEntry): JsonResponse
    {
        $homebrewEntry->delete();

        return response()->json([
            'message' => 'Homebrew entry removed.',
        ]);
    }
}
