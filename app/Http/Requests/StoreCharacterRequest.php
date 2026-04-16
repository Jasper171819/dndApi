<?php

namespace App\Http\Requests;

use App\Support\PlainTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** @var PlainTextNormalizer $normalizer */
        $normalizer = app(PlainTextNormalizer::class);

        $this->merge([
            'name' => $normalizer->normalize($this->input('name')),
            'species' => $normalizer->normalize($this->input('species')),
            'class' => $normalizer->normalize($this->input('class')),
            'subclass' => $normalizer->normalize($this->input('subclass')),
            'background' => $normalizer->normalize($this->input('background')),
            'alignment' => $normalizer->normalize($this->input('alignment')),
            'notes' => $normalizer->normalize($this->input('notes'), true),
            'level' => is_numeric($this->input('level')) ? (int) $this->input('level') : $this->input('level'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'species' => ['required', 'string', 'max:50'],
            'class' => ['required', 'string', 'max:50'],
            'subclass' => ['nullable', 'string', 'max:50'],
            'background' => ['required', 'string', 'max:50'],
            'alignment' => ['nullable', 'string', 'max:30'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function characterData(): array
    {
        return $this->validated();
    }
}
