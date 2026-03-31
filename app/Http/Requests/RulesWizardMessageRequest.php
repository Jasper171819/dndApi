<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Requests;

use App\Support\CharacterDataValidator;
use App\Support\PlainTextNormalizer;
use App\Support\RulesWizardStateSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RulesWizardMessageRequest extends FormRequest
{
    // Developer context: Authorize handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function authorize(): bool
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return true;
    }

    // Developer context: Prepareforvalidation handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    protected function prepareForValidation(): void
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalizer = app(PlainTextNormalizer::class);
        $state = $this->input('state');

        $this->replace([
            'message' => $normalizer->normalize($this->input('message')),
            'state' => is_array($state)
                ? app(RulesWizardStateSanitizer::class)->sanitize($state)
                : $state,
        ]);
    }

    // Developer context: Rules handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rules(): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $characterData = app(CharacterDataValidator::class);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'message' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'array'],
            'state.pending_field' => ['nullable', 'string', Rule::in($characterData->knownFields())],
            'state.skipped_optional_fields' => ['nullable', 'array'],
            'state.skipped_optional_fields.*' => ['string', Rule::in($characterData->optionalFields())],
            'state.random_preview' => ['nullable', 'array'],
            'state.character' => ['nullable', 'array'],
            'state.dungeon' => ['nullable', 'array'],
        ];
    }

    // Developer context: Sanitizedstate handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function sanitizedState(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return is_array($this->validated('state')) ? $this->validated('state') : [];
    }

    // Developer context: Sanitizedmessage handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function sanitizedMessage(): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return (string) ($this->validated('message') ?? '');
    }
}
