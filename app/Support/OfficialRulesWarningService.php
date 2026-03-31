<?php
// Developer context: This support class turns a loose character draft into non-blocking official-rules warnings, and both the builder-facing APIs and the wizard snapshot can reuse it to stay consistent.
// Clear explanation: This file checks whether the current character setup drifts away from the official 2024 default and returns warnings without blocking the build.

namespace App\Support;

class OfficialRulesWarningService
{
    private const STAT_FIELDS = [
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    // Developer context: This is the public entry point; pass it a partial or complete character draft and it returns the warning lines the UI should surface.
    // Clear explanation: This method looks at the current sheet and builds a short list of rule warnings when something no longer matches the official 2024 baseline.
    public function forCharacter(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $warnings = [];

        $warnings = array_merge(
            $warnings,
            $this->advancementWarnings($character),
            $this->alignmentWarnings($character),
            $this->languageWarnings($character),
            $this->builderFlexWarnings($character),
        );

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_unique(array_filter($warnings)));
    }

    // Developer context: Advancementwarnings handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part checks whether the build is using a level-up method outside the official baseline.
    private function advancementWarnings(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $method = $this->stringValue($character['advancement_method'] ?? null);
        $baselineMethods = config('dnd.official_rules.baseline_advancement_methods', []);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $method || ! is_array($baselineMethods) || in_array($method, $baselineMethods, true)) {
            return [];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            sprintf(
                '%s is supported here as a table variant, but the official 2024 baseline in this app is %s.',
                $method,
                $this->naturalJoin($baselineMethods),
            ),
        ];
    }

    // Developer context: Alignmentwarnings handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part checks the special alignment case that the 2024 rules call out for DM approval.
    private function alignmentWarnings(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $alignment = $this->stringValue($character['alignment'] ?? null);
        $evilAlignments = config('dnd.official_rules.evil_alignments', []);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $alignment || ! is_array($evilAlignments) || ! in_array($alignment, $evilAlignments, true)) {
            return [];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [config('dnd.official_rules.evil_alignment_warning')];
    }

    // Developer context: Languagewarnings handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part checks whether the current language setup still looks like the official 2024 starting package.
    private function languageWarnings(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $languages = array_values(array_filter(array_map(
            fn (mixed $language): ?string => $this->stringValue($language),
            is_array($character['languages'] ?? null) ? $character['languages'] : [],
        )));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($languages === []) {
            return [];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $rareLanguages = array_values(array_intersect(
            $languages,
            is_array(config('dnd.official_rules.rare_languages', [])) ? config('dnd.official_rules.rare_languages', []) : [],
        ));

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $reasons = [];

        // Developer context: These branches build the human-readable reasons that explain why the language warning appeared.
        // Clear explanation: These lines collect the exact language details that made the build drift away from the normal 2024 starting package.
        if (! in_array('Common', $languages, true)) {
            $reasons[] = 'Common is missing';
        }

        if (count($languages) < 3) {
            $reasons[] = sprintf('only %d language%s %s selected', count($languages), count($languages) === 1 ? '' : 's', count($languages) === 1 ? 'is' : 'are');
        }

        if ($rareLanguages !== []) {
            $reasons[] = sprintf('%s %s rare', $this->naturalJoin($rareLanguages), count($rareLanguages) === 1 ? 'is' : 'are');
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($reasons === []) {
            return [];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [ucfirst(implode(', ', $reasons)).'. '.config('dnd.official_rules.language_warning')];
    }

    // Developer context: Builderflexwarnings handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part warns about the places where the current character sheet is intentionally looser than the printed 2024 package.
    private function builderFlexWarnings(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $warnings = [];
        $background = $this->stringValue($character['background'] ?? null);
        $class = $this->stringValue($character['class'] ?? null);
        $hasOriginFeat = $this->stringValue($character['origin_feat'] ?? null) !== null;
        $hasSkills = is_array($character['skill_proficiencies'] ?? null) && $character['skill_proficiencies'] !== [];
        $hasLanguages = is_array($character['languages'] ?? null) && $character['languages'] !== [];
        $hasStats = $this->hasAnyAbilityScore($character);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($background && ($hasOriginFeat || $hasSkills || $hasLanguages || $hasStats)) {
            $warnings[] = config('dnd.official_rules.background_package_warning');
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($background && $hasSkills) {
            $warnings[] = config('dnd.official_rules.skill_package_warning');
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($background || $class) {
            $warnings[] = config('dnd.official_rules.tool_equipment_warning');
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $warnings;
    }

    // Developer context: Hasanyabilityscore handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part checks whether the build has already started filling in ability scores.
    private function hasAnyAbilityScore(array $character): bool
    {
        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach (self::STAT_FIELDS as $field) {
            $value = $character[$field] ?? null;

            if (is_int($value) || (is_string($value) && trim($value) !== '')) {
                return true;
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return false;
    }

    // Developer context: Stringvalue handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part turns a possible text value into either a clean string or nothing.
    private function stringValue(mixed $value): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = trim((string) $value);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $normalized === '' ? null : $normalized;
    }

    // Developer context: Naturaljoin handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part joins a short list into human-friendly text.
    private function naturalJoin(array $items): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $items = array_values(array_filter(array_map(
            fn (mixed $item): ?string => $this->stringValue($item),
            $items,
        )));

        // Developer context: These branches shape the join text based on how many items exist.
        // Clear explanation: These lines format one item, two items, or a longer list in a way people can read easily.
        if ($items === []) {
            return '';
        }

        if (count($items) === 1) {
            return $items[0];
        }

        if (count($items) === 2) {
            return $items[0].' and '.$items[1];
        }

        $last = array_pop($items);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return implode(', ', $items).', and '.$last;
    }
}
