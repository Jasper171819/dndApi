<?php

namespace App\Support;

class PlainTextNormalizer
{
    public function normalize(mixed $value, bool $multiline = false): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $text = (string) $value;
        $text = preg_replace('@<(script|style)\b[^>]*>.*?</\1>@is', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $text) ?? $text;
        $text = str_replace("\t", ' ', $text);

        if ($multiline) {
            $lines = array_map(
                static function (string $line): string {
                    $trimmed = preg_replace('/[ ]{2,}/u', ' ', trim($line));

                    return $trimmed ?? trim($line);
                },
                explode("\n", $text),
            );

            $text = implode("\n", $lines);
            $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        } else {
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        }

        $text = trim($text);

        return $text === '' ? null : $text;
    }
}
