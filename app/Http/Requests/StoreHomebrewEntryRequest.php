<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Http\Requests;

use App\Support\PlainTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomebrewEntryRequest extends FormRequest
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
            'category' => $this->normalizeSlug($this->input('category')),
            'status' => $this->normalizeSlug($this->input('status')) ?? 'draft',
            'name' => $normalizer->normalize($this->input('name')),
            'summary' => $normalizer->normalize($this->input('summary'), multiline: true),
            'details' => $normalizer->normalize($this->input('details'), multiline: true),
            'source_notes' => $normalizer->normalize($this->input('source_notes'), multiline: true),
            'tags' => $this->normalizeTags($this->input('tags')),
        ]);
    }

    // Developer context: Rules handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rules(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'category' => ['required', 'string', Rule::in(array_keys(config('homebrew.categories', [])))],
            'status' => ['required', 'string', Rule::in(array_keys(config('homebrew.statuses', [])))],
            'name' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:800'],
            'details' => ['nullable', 'string', 'max:4000'],
            'source_notes' => ['nullable', 'string', 'max:1200'],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['string', 'max:32'],
        ];
    }

    // Developer context: Entrydata handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function entryData(): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $validated = $this->validated();
        $validated['tags'] = $validated['tags'] ?? [];

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $validated;
    }

    // Developer context: Normalizeslug handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeSlug(mixed $value): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($value)) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $slug = trim(strtolower($value));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $slug === '' ? null : $slug;
    }

    // Developer context: Normalizetags handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeTags(mixed $value): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalizer = app(PlainTextNormalizer::class);

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $items = match (true) {
            is_array($value) => $value,
            is_string($value) => preg_split('/[,|\n\r]+/', $value) ?: [],
            default => [],
        };

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $tags = [];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($items as $item) {
            $normalized = $normalizer->normalize($item);

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($normalized === null) {
                continue;
            }

            $tags[] = strtolower($normalized);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_unique($tags));
    }
}
