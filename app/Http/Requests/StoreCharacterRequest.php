<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Requests;

use App\Support\CharacterDataValidator;
use Illuminate\Foundation\Http\FormRequest;

class StoreCharacterRequest extends FormRequest
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
        $this->replace(
            app(CharacterDataValidator::class)->normalizeDraft($this->all()),
        );
    }

    // Developer context: Rules handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rules(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return app(CharacterDataValidator::class)->rules($this->all());
    }

    // Developer context: Characterdata handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function characterData(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->validated();
    }
}
