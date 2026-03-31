<?php
// Developer context: This controller exposes the separate DM wizard endpoint and keeps it isolated from the player wizard route and service.
// Clear explanation: This file handles the DM-only wizard messages.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DmWizardMessageRequest;
use App\Services\DmWizardService;
use Illuminate\Http\JsonResponse;

class DmWizardController extends Controller
{
    // Developer context: Laravel injects the separate DM wizard service so the controller stays as a thin handoff layer.
    // Clear explanation: This connects the controller to the DM wizard logic.
    public function __construct(
        private readonly DmWizardService $dmWizard,
    ) {}

    // Developer context: This endpoint accepts one DM wizard message plus the DM wizard state and returns the next DM wizard response payload.
    // Clear explanation: This sends the DM wizard message to the DM wizard service and returns the next reply.
    public function message(DmWizardMessageRequest $request): JsonResponse
    {
        return response()->json(
            $this->dmWizard->handle(
                $request->sanitizedMessage(),
                $request->sanitizedState(),
            )
        );
    }
}
