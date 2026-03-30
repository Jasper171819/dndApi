<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiceController extends Controller
{
    public function roll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expression' => ['required', 'string', 'max:50'],
            'mode' => ['nullable', 'string', 'in:advantage,disadvantage'],
        ]);

        $parsed = $this->parseDiceExpression($validated['expression']);

        if ($parsed === null) {
            return response()->json([
                'message' => 'The dice expression could not be parsed.',
            ], 422);
        }

        $result = $this->evaluateDiceExpression($parsed, $validated['mode'] ?? null);

        return response()->json([
            'expression' => Str::of($validated['expression'])->lower()->squish()->toString(),
            'mode' => $validated['mode'] ?? null,
            'total' => $result['total'],
            'detail' => $result['detail'],
        ]);
    }

    public function rollStats(): JsonResponse
    {
        $details = [];
        foreach (['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $field) {
            $details[$field] = $this->rollAbilityScoreDetail();
        }

        $stats = array_map(static fn (array $detail): int => $detail['total'], $details);
        $stats['details'] = $details;

        return response()->json($stats);
    }

    private function rollAbilityScore(): int
    {
        return $this->rollAbilityScoreDetail()['total'];
    }

    private function rollAbilityScoreDetail(): array
    {
        $rolls = [
            random_int(1, 6),
            random_int(1, 6),
            random_int(1, 6),
            random_int(1, 6),
        ];

        $sortedRolls = $rolls;
        sort($sortedRolls);
        $dropped = array_shift($sortedRolls);

        return [
            'rolls' => $rolls,
            'kept' => array_values($sortedRolls),
            'dropped' => $dropped,
            'total' => array_sum($sortedRolls),
        ];
    }

    private function parseDiceExpression(string $expression): ?array
    {
        $normalized = preg_replace('/\s+/', '', $expression);

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        if ($normalized[0] !== '+' && $normalized[0] !== '-') {
            $normalized = '+'.$normalized;
        }

        preg_match_all('/([+-])((?:\d*)d\d+|\d+)/i', $normalized, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $terms = [];
        $rebuilt = '';

        foreach ($matches as $match) {
            $terms[] = [
                'sign' => $match[1],
                'token' => Str::of($match[2])->lower()->toString(),
            ];
            $rebuilt .= $match[1].$match[2];
        }

        return $rebuilt === $normalized ? $terms : null;
    }

    private function evaluateDiceExpression(array $terms, ?string $mode = null): array
    {
        $total = 0;
        $parts = [];
        $advUsed = false;
        $advantageEligible = $mode !== null
            && count(array_filter($terms, static function (array $term): bool {
                return preg_match('/^(\d*)d(\d+)$/', $term['token']) === 1
                    && (((int) preg_replace('/d.*/', '', $term['token'])) ?: 1) === 1
                    && (int) substr(strrchr($term['token'], 'd'), 1) === 20;
            })) === 1
            && count(array_filter($terms, static function (array $term): bool {
                return preg_match('/^(\d*)d(\d+)$/', $term['token']) === 1
                    && ! preg_match('/^(\d*)d20$/', $term['token']);
            })) === 0;

        foreach ($terms as $index => $term) {
            $sign = $term['sign'] === '-' ? -1 : 1;
            $token = $term['token'];

            if (preg_match('/^(\d*)d(\d+)$/', $token, $matches) === 1) {
                $count = $matches[1] === '' ? 1 : (int) $matches[1];
                $sides = (int) $matches[2];

                if ($count < 1 || $sides < 2) {
                    return ['total' => 0, 'detail' => 'Invalid dice term'];
                }

                if (! $advUsed && $advantageEligible && $count === 1 && $sides === 20) {
                    $first = random_int(1, 20);
                    $second = random_int(1, 20);
                    $roll = $mode === 'advantage' ? max($first, $second) : min($first, $second);
                    $total += $sign * $roll;
                    $parts[] = sprintf('%s(%d,%d=>%d)', $mode === 'advantage' ? 'adv' : 'dis', $first, $second, $roll);
                    $advUsed = true;
                    continue;
                }

                $rolls = [];
                for ($i = 0; $i < $count; $i++) {
                    $rolls[] = random_int(1, $sides);
                }

                $value = array_sum($rolls);
                $total += $sign * $value;
                $prefix = $sign < 0 ? '-' : ($index === 0 ? '' : '+');
                $parts[] = "{$prefix}{$token}[".implode(',', $rolls).']';
            } else {
                $value = (int) $token;
                $total += $sign * $value;
                $prefix = $sign < 0 ? '-' : ($index === 0 ? '' : '+');
                $parts[] = "{$prefix}{$value}";
            }
        }

        return [
            'total' => $total,
            'detail' => implode(' ', $parts),
        ];
    }
}
