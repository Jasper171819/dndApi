<?php
// Developer context: This form request validates the separate DM wizard message endpoint and sanitizes its nested state before the DM service sees it.
// Clear explanation: This file checks DM wizard messages and keeps the DM wizard state clean.

namespace App\Http\Requests;

use App\Support\DmRecordDataValidator;
use App\Support\DmWizardStateSanitizer;
use App\Support\PlainTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DmWizardMessageRequest extends FormRequest
{
    // Developer context: This request stays open to the current app because access control is not being split further in this local project.
    // Clear explanation: This allows the request to be used by the app without an extra permission step.
    public function authorize(): bool
    {
        return true;
    }

    // Developer context: This hook cleans the free-text message and whitelists the nested DM wizard state before Laravel validates the payload.
    // Clear explanation: This cleans what the DM typed and the wizard state before the app checks it.
    protected function prepareForValidation(): void
    {
        $normalizer = app(PlainTextNormalizer::class);
        $state = $this->input('state');
        $sanitizedState = is_array($state)
            ? app(DmWizardStateSanitizer::class)->sanitize($state)
            : $state;

        if (is_array($state) && array_key_exists('draft_record', $state) && ! is_array($state['draft_record'])) {
            $sanitizedState['draft_record'] = $state['draft_record'];
        }

        if (is_array($state) && array_key_exists('page_linkage', $state) && ! is_array($state['page_linkage'])) {
            $sanitizedState['page_linkage'] = $state['page_linkage'];
        }

        if (is_array($state) && array_key_exists('skipped_optional_fields', $state) && ! is_array($state['skipped_optional_fields'])) {
            $sanitizedState['skipped_optional_fields'] = $state['skipped_optional_fields'];
        }

        $this->replace([
            'message' => $normalizer->normalize($this->input('message')),
            'state' => $sanitizedState,
        ]);
    }

    // Developer context: This request validates only the DM wizard message envelope; the service still owns flow-specific save decisions and command handling.
    // Clear explanation: This checks the DM wizard request shape without forcing every wizard step to look like a finished record.
    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'array'],
            'state.flow_kind' => ['nullable', 'string', Rule::in(app(DmRecordDataValidator::class)->knownKinds())],
            'state.pending_field' => ['nullable', 'string', 'max:80'],
            'state.skipped_optional_fields' => ['nullable', 'array'],
            'state.skipped_optional_fields.*' => ['string', 'max:80'],
            'state.draft_record' => ['nullable', 'array'],
            'state.page_linkage' => ['nullable', 'array'],
        ];
    }

    // Developer context: This helper returns the sanitized state block so the controller can pass it directly to the service.
    // Clear explanation: This hands the cleaned wizard state back to the controller.
    public function sanitizedState(): array
    {
        return is_array($this->validated('state')) ? $this->validated('state') : [];
    }

    // Developer context: This helper returns the normalized wizard message string in a consistent way.
    // Clear explanation: This hands the cleaned wizard message back to the controller.
    public function sanitizedMessage(): string
    {
        return (string) ($this->validated('message') ?? '');
    }
}
