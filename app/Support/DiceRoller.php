<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Support;

use Illuminate\Support\Str;

class DiceRoller
{
    // Developer context: Rollexpression handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rollExpression(string $expression, ?string $mode = null): ?array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $parsed = $this->parseDiceExpression($expression);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($parsed === null) {
            return null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->evaluateDiceExpression($parsed, $mode);
    }

    // Developer context: Rollabilityscore handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rollAbilityScore(): int
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->rollAbilityScoreDetail()['total'];
    }

    // Developer context: Rollabilityscoredetail handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rollAbilityScoreDetail(): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $rolls = [
            random_int(1, 6),
            random_int(1, 6),
            random_int(1, 6),
            random_int(1, 6),
        ];

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $sortedRolls = $rolls;
        sort($sortedRolls);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dropped = array_shift($sortedRolls);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'rolls' => $rolls,
            'kept' => array_values($sortedRolls),
            'dropped' => $dropped,
            'total' => array_sum($sortedRolls),
        ];
    }

    // Developer context: Rolld20 handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rollD20(int $modifier, ?string $mode = null): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $first = random_int(1, 20);
        $second = $mode ? random_int(1, 20) : null;
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $roll = match ($mode) {
            'advantage' => max($first, (int) $second),
            'disadvantage' => min($first, (int) $second),
            default => $first,
        };

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $detail = $mode
            ? sprintf('%d and %d -> %d %s %d', $first, $second, $roll, $modifier >= 0 ? '+' : '-', abs($modifier))
            : sprintf('%d %s %d', $roll, $modifier >= 0 ? '+' : '-', abs($modifier));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'roll' => $roll,
            'total' => $roll + $modifier,
            'detail' => $detail,
        ];
    }

    // Developer context: Rollhitpointdice handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function rollHitPointDice(int $count, int $sides, int $modifier, int $minimumPerDie = 1): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entries = [];
        $total = 0;

        for ($index = 0; $index < $count; $index++) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $roll = random_int(1, $sides);
            $value = max($minimumPerDie, $roll + $modifier);

            $entries[] = [
                'roll' => $roll,
                'modifier' => $modifier,
                'total' => $value,
            ];

            $total += $value;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $detail = implode(', ', array_map(
            static fn (array $entry): string => sprintf(
                'd%d[%d] %s %d = %d',
                $sides,
                $entry['roll'],
                $modifier >= 0 ? '+' : '-',
                abs($modifier),
                $entry['total'],
            ),
            $entries,
        ));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'entries' => $entries,
            'total' => $total,
            'detail' => $detail,
        ];
    }

    // Developer context: Parsediceexpression handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function parseDiceExpression(string $expression): ?array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = preg_replace('/\s+/', '', $expression);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($normalized[0] !== '+' && $normalized[0] !== '-') {
            $normalized = '+'.$normalized;
        }

        preg_match_all('/([+-])((?:\d*)d\d+|\d+)/i', $normalized, $matches, PREG_SET_ORDER);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($matches === []) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $terms = [];
        $rebuilt = '';

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($matches as $match) {
            $terms[] = [
                'sign' => $match[1],
                'token' => Str::of($match[2])->lower()->toString(),
            ];
            $rebuilt .= $match[1].$match[2];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $rebuilt === $normalized ? $terms : null;
    }

    // Developer context: Evaluatediceexpression handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function evaluateDiceExpression(array $terms, ?string $mode = null): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $total = 0;
        $parts = [];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $advUsed = false;
        $advantageEligible = $mode !== null
            && count(array_filter($terms, static function (array $term): bool {
                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return preg_match('/^(\d*)d(\d+)$/', $term['token']) === 1
                    && (((int) preg_replace('/d.*/', '', $term['token'])) ?: 1) === 1
                    && (int) substr(strrchr($term['token'], 'd'), 1) === 20;
            })) === 1
            && count(array_filter($terms, static function (array $term): bool {
                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return preg_match('/^(\d*)d(\d+)$/', $term['token']) === 1
                    && ! preg_match('/^(\d*)d20$/', $term['token']);
            })) === 0;

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($terms as $index => $term) {
            $sign = $term['sign'] === '-' ? -1 : 1;
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $token = $term['token'];

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (preg_match('/^(\d*)d(\d+)$/', $token, $matches) === 1) {
                $count = $matches[1] === '' ? 1 : (int) $matches[1];
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $sides = (int) $matches[2];

                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if ($count < 1 || $sides < 2) {
                    return ['total' => 0, 'detail' => 'Invalid dice term'];
                }

                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if (! $advUsed && $advantageEligible && $count === 1 && $sides === 20) {
                    $first = random_int(1, 20);
                    // Developer context: This assignment stores a working value that the next lines reuse.
                    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                    $second = random_int(1, 20);
                    $roll = $mode === 'advantage' ? max($first, $second) : min($first, $second);
                    $total += $sign * $roll;
                    $parts[] = sprintf('%s(%d,%d=>%d)', $mode === 'advantage' ? 'adv' : 'dis', $first, $second, $roll);
                    // Developer context: This assignment stores a working value that the next lines reuse.
                    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                    $advUsed = true;

                    continue;
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $rolls = [];
                for ($i = 0; $i < $count; $i++) {
                    $rolls[] = random_int(1, $sides);
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $value = array_sum($rolls);
                $total += $sign * $value;
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $prefix = $sign < 0 ? '-' : ($index === 0 ? '' : '+');
                $parts[] = "{$prefix}{$token}[".implode(',', $rolls).']';
            } else {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $value = (int) $token;
                $total += $sign * $value;
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $prefix = $sign < 0 ? '-' : ($index === 0 ? '' : '+');
                $parts[] = "{$prefix}{$value}";
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'total' => $total,
            'detail' => implode(' ', $parts),
        ];
    }
}
