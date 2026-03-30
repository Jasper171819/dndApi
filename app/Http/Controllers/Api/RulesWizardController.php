<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RulesWizardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RulesWizardController extends Controller
{
    public function __construct(
        private readonly RulesWizardService $rulesWizard,
    ) {
    }

    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'array'],
        ]);

        return response()->json(
            $this->rulesWizard->handle(
                $validated['message'] ?? '',
                $validated['state'] ?? [],
            )
        );
    }
}
