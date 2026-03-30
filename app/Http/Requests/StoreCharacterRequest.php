<?php

namespace App\Http\Requests;

use App\Support\CharacterDataValidator;
use Illuminate\Foundation\Http\FormRequest;

class StoreCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace(
            app(CharacterDataValidator::class)->normalizeDraft($this->all()),
        );
    }

    public function rules(): array
    {
        return app(CharacterDataValidator::class)->rules($this->all());
    }

    public function characterData(): array
    {
        return $this->validated();
    }
}
