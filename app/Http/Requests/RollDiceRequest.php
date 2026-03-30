<?php

namespace App\Http\Requests;

use App\Support\PlainTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class RollDiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizer = app(PlainTextNormalizer::class);

        $this->replace([
            'expression' => $normalizer->normalize($this->input('expression')),
            'mode' => $normalizer->normalize($this->input('mode')),
        ]);
    }

    public function rules(): array
    {
        return [
            'expression' => ['required', 'string', 'max:50'],
            'mode' => ['nullable', 'string', 'in:advantage,disadvantage'],
        ];
    }
}
