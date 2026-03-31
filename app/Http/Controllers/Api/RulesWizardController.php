<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RulesWizardMessageRequest;
use App\Services\RulesWizardService;
use Illuminate\Http\JsonResponse;

class RulesWizardController extends Controller
{
    // Developer context: Construct handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function __construct(
        private readonly RulesWizardService $rulesWizard,
    ) {}

    // Developer context: Message handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function message(RulesWizardMessageRequest $request): JsonResponse
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return response()->json(
            $this->rulesWizard->handle(
                $request->sanitizedMessage(),
                $request->sanitizedState(),
            )
        );
    }
}
