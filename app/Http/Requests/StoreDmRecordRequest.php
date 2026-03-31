<?php
// Developer context: This form request is the shared write contract for DM records; both create and update routes use it so the same validator rules apply everywhere.
// Clear explanation: This file checks DM record data before the app saves or updates it.

namespace App\Http\Requests;

use App\Support\DmRecordDataValidator;
use Illuminate\Foundation\Http\FormRequest;

class StoreDmRecordRequest extends FormRequest
{
    private array $normalizedRecord = [];

    // Developer context: This request stays open to the current app because access control is not being split further in this local project.
    // Clear explanation: This allows the request to be used by the app without an extra permission step.
    public function authorize(): bool
    {
        return true;
    }

    // Developer context: This hook normalizes the incoming DM record before the Laravel validator evaluates the rules.
    // Clear explanation: This cleans the incoming DM record before the app checks whether it is valid.
    protected function prepareForValidation(): void
    {
        $this->normalizedRecord = app(DmRecordDataValidator::class)->normalizeDraft($this->all());
        $this->replace($this->normalizedRecord);
    }

    // Developer context: This request reuses the shared DM record validator so the API and DM wizard never drift apart on record rules.
    // Clear explanation: This uses the same rules everywhere the app saves a DM record.
    public function rules(): array
    {
        return app(DmRecordDataValidator::class)->rules($this->normalizedRecord);
    }

    // Developer context: This helper returns the validated record payload in the same shape the model expects.
    // Clear explanation: This hands the cleaned DM record back to the controller for saving.
    public function recordData(): array
    {
        $validated = $this->validated();
        $validated['tags'] = $validated['tags'] ?? [];
        $validated['payload'] = $validated['payload'] ?? [];

        unset($validated['id']);

        return $validated;
    }
}
