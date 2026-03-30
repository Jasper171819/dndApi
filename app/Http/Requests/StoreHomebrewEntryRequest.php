<?php

namespace App\Http\Requests;

use App\Support\PlainTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomebrewEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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

    public function rules(): array
    {
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

    public function entryData(): array
    {
        $validated = $this->validated();
        $validated['tags'] = $validated['tags'] ?? [];

        return $validated;
    }

    private function normalizeSlug(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $slug = trim(strtolower($value));

        return $slug === '' ? null : $slug;
    }

    private function normalizeTags(mixed $value): array
    {
        $normalizer = app(PlainTextNormalizer::class);

        $items = match (true) {
            is_array($value) => $value,
            is_string($value) => preg_split('/[,|\n\r]+/', $value) ?: [],
            default => [],
        };

        $tags = [];

        foreach ($items as $item) {
            $normalized = $normalizer->normalize($item);

            if ($normalized === null) {
                continue;
            }

            $tags[] = strtolower($normalized);
        }

        return array_values(array_unique($tags));
    }
}
