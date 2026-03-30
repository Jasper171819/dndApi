<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RulesWizardMessageRequest;
use App\Services\RulesWizardService;
use Illuminate\Http\JsonResponse;

class RulesWizardController extends Controller
{
    public function __construct(
        private readonly RulesWizardService $rulesWizard,
    ) {}

    public function message(RulesWizardMessageRequest $request): JsonResponse
    {
        return response()->json(
            $this->rulesWizard->handle(
                $request->sanitizedMessage(),
                $request->sanitizedState(),
            )
        );
    }
}
