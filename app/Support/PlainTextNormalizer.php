<?php
// Developer context: This helper is used by validation and wizard sanitizing to turn free-text input into safe plain text by stripping tags, control characters, and extra whitespace.
// Clear explanation: This file cleans user-written text so odd formatting, pasted HTML, or hidden characters do not get stored by the app.

namespace App\Support;

class PlainTextNormalizer
{
    // Developer context: This method accepts mixed input, rejects arrays and objects, strips risky markup, normalizes line breaks and spacing, and returns either cleaned text or null.
    // Clear explanation: This method turns messy pasted text into clean plain text, or returns nothing when the input is not usable.
    public function normalize(mixed $value, bool $multiline = false): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $text = (string) $value;
        $text = preg_replace('@<(script|style)\b[^>]*>.*?</\1>@is', ' ', $text) ?? $text;
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $text) ?? $text;
        $text = str_replace("\t", ' ', $text);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($multiline) {
            $lines = array_map(
                static function (string $line): string {
                    // Developer context: This assignment stores a working value that the next lines reuse.
                    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                    $trimmed = preg_replace('/[ ]{2,}/u', ' ', trim($line));

                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return $trimmed ?? trim($line);
                },
                explode("\n", $text),
            );

            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $text = implode("\n", $lines);
            $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        } else {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $text = trim($text);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $text === '' ? null : $text;
    }
}
