<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CompendiumController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'verified_at' => config('dnd.verified_at'),
            'source_note' => config('dnd.source_note'),
            'sections' => config('dnd.compendium_sections', []),
            'compendium' => config('dnd.compendium', []),
        ]);
    }

    public function show(string $section): JsonResponse
    {
        $entry = config("dnd.compendium.{$section}");

        if (! is_array($entry)) {
            return response()->json([
                'message' => 'Compendium section not found',
            ], 404);
        }

        return response()->json([
            'verified_at' => config('dnd.verified_at'),
            'section' => $entry,
        ]);
    }
}
