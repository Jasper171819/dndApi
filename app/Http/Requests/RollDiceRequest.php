<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Requests;

use App\Support\PlainTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class RollDiceRequest extends FormRequest
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

        $this->replace([
            'expression' => $normalizer->normalize($this->input('expression')),
            'mode' => $normalizer->normalize($this->input('mode')),
        ]);
    }

    // Developer context: Rules handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rules(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'expression' => ['required', 'string', 'max:50'],
            'mode' => ['nullable', 'string', 'in:advantage,disadvantage'],
        ];
    }
}
