<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Support;

use App\Models\Character;

class CharacterHitPointRoller
{
    // Developer context: Construct handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function __construct(
        private readonly DiceRoller $diceRoller,
    ) {}

    // Developer context: Metadataforcharacter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function metadataForCharacter(array $characterData, ?Character $existing = null): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $class = (string) ($characterData['class'] ?? '');
        $level = (int) ($characterData['level'] ?? 0);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $constitution = isset($characterData['constitution']) ? (int) $characterData['constitution'] : null;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($class === '' || $level < 1 || $constitution === null) {
            return [
                'hp_adjustment' => 0,
                'rolled_hit_points' => false,
            ];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($level === 1) {
            return [
                'hp_adjustment' => 0,
                'rolled_hit_points' => false,
            ];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (
            $existing
            && $existing->rolled_hit_points
            && $existing->class === $class
            && (int) $existing->level === $level
        ) {
            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return [
                'hp_adjustment' => (int) $existing->hp_adjustment,
                'rolled_hit_points' => true,
            ];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $hitDie = $this->hitDieForClass($class);
        if ($hitDie === null) {
            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return [
                'hp_adjustment' => 0,
                'rolled_hit_points' => false,
            ];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $conModifier = $this->abilityModifier($constitution);
        $fixedGain = max(1, ((int) floor($hitDie / 2)) + 1 + $conModifier);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $adjustment = 0;

        for ($currentLevel = 2; $currentLevel <= $level; $currentLevel++) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $rolled = $this->diceRoller->rollHitPointDice(1, $hitDie, $conModifier);
            $adjustment += $rolled['total'] - $fixedGain;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'hp_adjustment' => $adjustment,
            'rolled_hit_points' => true,
        ];
    }

    // Developer context: Hitdieforclass handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function hitDieForClass(string $class): ?int
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $value = config("dnd_progressions.classes.{$class}.traits.hit_point_die");

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($value) || preg_match('/D(\d+)/i', $value, $matches) !== 1) {
            return null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return (int) $matches[1];
    }

    // Developer context: Abilitymodifier handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function abilityModifier(int $score): int
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return (int) floor(($score - 10) / 2);
    }
}
