<?php

namespace App\Http\Requests;

use App\Support\CharacterDataValidator;
use App\Support\PlainTextNormalizer;
use App\Support\RulesWizardStateSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RulesWizardMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizer = app(PlainTextNormalizer::class);
        $state = $this->input('state');

        $this->replace([
            'message' => $normalizer->normalize($this->input('message')),
            'state' => is_array($state)
                ? app(RulesWizardStateSanitizer::class)->sanitize($state)
                : $state,
        ]);
    }

    public function rules(): array
    {
        $characterData = app(CharacterDataValidator::class);

        return [
            'message' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'array'],
            'state.pending_field' => ['nullable', 'string', Rule::in($characterData->knownFields())],
            'state.skipped_optional_fields' => ['nullable', 'array'],
            'state.skipped_optional_fields.*' => ['string', Rule::in($characterData->optionalFields())],
            'state.character' => ['nullable', 'array'],
            'state.dungeon' => ['nullable', 'array'],
        ];
    }

    public function sanitizedState(): array
    {
        return is_array($this->validated('state')) ? $this->validated('state') : [];
    }

    public function sanitizedMessage(): string
    {
        return (string) ($this->validated('message') ?? '');
    }
}
