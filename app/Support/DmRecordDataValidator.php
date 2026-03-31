<?php
// Developer context: This support class is the shared contract for DM records; the DM records API and DM wizard both call into it so normalization and validation stay consistent.
// Clear explanation: This file cleans DM-side records like NPCs, scenes, and encounters before the app saves or reuses them.

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DmRecordDataValidator
{
    // Developer context: Laravel injects the shared plain-text normalizer so every DM record field is cleaned in one consistent way before validation happens.
    // Clear explanation: This sets up the text-cleaning helper that keeps DM records readable and safe to store.
    public function __construct(
        private readonly PlainTextNormalizer $plainText,
    ) {}

    // Developer context: This method whitelists the known DM record fields and normalizes top-level text, tags, IDs, and the nested payload.
    // Clear explanation: This method turns raw DM record input into one clean, predictable shape before the app checks or saves it.
    public function normalizeDraft(array $input): array
    {
        $kind = $this->normalizeSlug($input['kind'] ?? null);

        return [
            'id' => $this->normalizeInteger($input['id'] ?? null),
            'kind' => $kind,
            'status' => $this->normalizeSlug($input['status'] ?? 'draft') ?? 'draft',
            'name' => $this->normalizeText($input['name'] ?? null),
            'summary' => $this->normalizeText($input['summary'] ?? null, true),
            'campaign' => $this->normalizeText($input['campaign'] ?? null),
            'session_label' => $this->normalizeText($input['session_label'] ?? null),
            'tags' => $this->normalizeTags($input['tags'] ?? []),
            'payload' => $this->normalizePayload(
                $kind,
                is_array($input['payload'] ?? null) ? $input['payload'] : [],
            ),
            'linked_homebrew_entry_id' => $this->normalizeInteger($input['linked_homebrew_entry_id'] ?? null),
        ];
    }

    // Developer context: This method is the shared save path used by the DM records API and the DM wizard; it normalizes first and then runs the Laravel validator.
    // Clear explanation: This method is the final check that decides whether a DM record is complete enough to save.
    public function validateForSave(array $input): array
    {
        $normalized = $this->normalizeDraft($input);
        $validated = Validator::make($normalized, $this->rules($normalized))->validate();
        $validated['tags'] = $validated['tags'] ?? [];
        $validated['payload'] = $validated['payload'] ?? [];

        return $validated;
    }

    // Developer context: This method builds the validation rules for the top-level record and the selected record-kind payload.
    // Clear explanation: This method lists the rules a DM record must follow, like valid kinds, clean text, and the required fields for each record type.
    public function rules(array $input): array
    {
        $kind = $input['kind'] ?? null;
        $monsterNames = $this->monsterNames();

        return array_merge([
            'kind' => ['required', 'string', Rule::in($this->knownKinds())],
            'status' => ['required', 'string', Rule::in($this->knownStatuses())],
            'name' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:800'],
            'campaign' => ['nullable', 'string', 'max:120'],
            'session_label' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['string', 'max:32'],
            'payload' => ['required', 'array'],
            'linked_homebrew_entry_id' => ['nullable', 'integer', 'exists:homebrew_entries,id'],
        ], match ($kind) {
            'npc' => [
                'payload.role' => ['required', 'string', 'max:120'],
                'payload.species' => ['nullable', 'string', 'max:120'],
                'payload.alignment' => ['nullable', 'string', Rule::in(config('dnd.alignments', []))],
                'payload.attitude' => ['required', 'string', Rule::in(array_keys(config('dm.npc_attitudes', [])))],
                'payload.first_impression' => ['required', 'string', 'max:220'],
                'payload.appearance' => ['nullable', 'string', 'max:600'],
                'payload.voice' => ['required', 'string', 'max:180'],
                'payload.mannerism' => ['required', 'string', 'max:180'],
                'payload.goal' => ['required', 'string', 'max:600'],
                'payload.secret' => ['required', 'string', 'max:600'],
                'payload.leverage' => ['required', 'string', 'max:400'],
                'payload.fear' => ['required', 'string', 'max:400'],
                'payload.bond' => ['nullable', 'string', 'max:400'],
                'payload.faction' => ['nullable', 'string', 'max:160'],
                'payload.party_relationship' => ['nullable', 'string', 'max:240'],
                'payload.party_hook' => ['required', 'string', 'max:600'],
                'payload.clue_hooks' => ['nullable', 'string', 'max:800'],
                'payload.loot_hooks' => ['nullable', 'string', 'max:800'],
                'payload.combat_mode' => ['required', 'string', Rule::in(array_keys(config('dm.npc_combat_modes', [])))],
                'payload.linked_monster_name' => [
                    'nullable',
                    'string',
                    'max:160',
                    function (string $attribute, mixed $value, Closure $fail) use ($input, $monsterNames): void {
                        $mode = $input['payload']['combat_mode'] ?? 'narrative_only';

                        if ($mode !== 'monster_backed') {
                            return;
                        }

                        if (! is_string($value) || ! in_array($value, $monsterNames, true)) {
                            $fail('Choose a valid local monster when the NPC uses monster-backed combat.');
                        }
                    },
                ],
                'payload.quick_stats' => [
                    'nullable',
                    'array',
                    function (string $attribute, mixed $value, Closure $fail) use ($input): void {
                        $mode = $input['payload']['combat_mode'] ?? 'narrative_only';

                        if ($mode !== 'quick_stats') {
                            return;
                        }

                        if (! is_array($value)) {
                            $fail('Quick stats are required when the NPC uses quick-stat combat.');

                            return;
                        }

                        if (($value['ac'] ?? null) === null || ($value['max_hp'] ?? null) === null) {
                            $fail('Quick-stat NPCs need at least AC and max HP.');
                        }
                    },
                ],
                'payload.quick_stats.initiative_bonus' => ['nullable', 'integer', 'min:-20', 'max:20'],
                'payload.quick_stats.ac' => ['nullable', 'integer', 'min:1', 'max:40'],
                'payload.quick_stats.max_hp' => ['nullable', 'integer', 'min:1', 'max:999'],
                'payload.quick_stats.attack_note' => ['nullable', 'string', 'max:220'],
                'payload.quick_stats.damage_note' => ['nullable', 'string', 'max:220'],
                'payload.quick_stats.spell_note' => ['nullable', 'string', 'max:220'],
            ],
            'scene' => [
                'payload.location' => ['required', 'string', 'max:120'],
                'payload.purpose' => ['required', 'string', 'max:500'],
                'payload.stakes' => ['required', 'string', 'max:600'],
                'payload.pressure' => ['required', 'string', 'max:400'],
                'payload.active_threats' => ['required', 'string', 'max:600'],
                'payload.clues' => ['required', 'string', 'max:800'],
                'payload.hidden_info' => ['nullable', 'string', 'max:800'],
                'payload.obstacles' => ['nullable', 'string', 'max:800'],
                'payload.suggested_checks' => ['nullable', 'string', 'max:800'],
                'payload.linked_npcs' => ['nullable', 'string', 'max:800'],
                'payload.linked_encounter_notes' => ['nullable', 'string', 'max:800'],
                'payload.aftermath_notes' => ['nullable', 'string', 'max:800'],
            ],
            'quest' => [
                'payload.patron' => ['required', 'string', 'max:160'],
                'payload.hook' => ['required', 'string', 'max:600'],
                'payload.objective' => ['required', 'string', 'max:600'],
                'payload.complications' => ['required', 'string', 'max:800'],
                'payload.antagonists' => ['nullable', 'string', 'max:600'],
                'payload.milestones' => ['nullable', 'string', 'max:800'],
                'payload.reward' => ['required', 'string', 'max:600'],
                'payload.next_steps' => ['nullable', 'string', 'max:800'],
            ],
            'location' => [
                'payload.overview' => ['required', 'string', 'max:700'],
                'payload.sensory_details' => ['required', 'string', 'max:700'],
                'payload.exits' => ['required', 'string', 'max:500'],
                'payload.hazards' => ['required', 'string', 'max:700'],
                'payload.factions_present' => ['nullable', 'string', 'max:600'],
                'payload.secrets' => ['nullable', 'string', 'max:800'],
                'payload.scene_hooks' => ['nullable', 'string', 'max:800'],
            ],
            'encounter' => [
                'payload.initiative_snapshot' => ['nullable', 'array'],
                'payload.initiative_snapshot.*.name' => ['required_with:payload.initiative_snapshot', 'string', 'max:120'],
                'payload.initiative_snapshot.*.side' => ['nullable', 'string', Rule::in(['party', 'ally', 'enemy', 'npc', 'hazard'])],
                'payload.initiative_snapshot.*.initiative' => ['nullable', 'integer', 'min:-5', 'max:50'],
                'payload.initiative_snapshot.*.initiative_bonus' => ['nullable', 'integer', 'min:-20', 'max:20'],
                'payload.initiative_snapshot.*.ac' => ['nullable', 'string', 'max:30'],
                'payload.initiative_snapshot.*.current_hp' => ['nullable', 'integer', 'min:0', 'max:9999'],
                'payload.initiative_snapshot.*.max_hp' => ['nullable', 'integer', 'min:0', 'max:9999'],
                'payload.initiative_snapshot.*.temp_hp' => ['nullable', 'integer', 'min:0', 'max:9999'],
                'payload.initiative_snapshot.*.conditions' => ['nullable', 'array'],
                'payload.initiative_snapshot.*.conditions.*' => ['string', 'max:60'],
                'payload.initiative_snapshot.*.note' => ['nullable', 'string', 'max:400'],
                'payload.terrain_notes' => ['required', 'string', 'max:800'],
                'payload.enemy_plan' => ['required', 'string', 'max:800'],
                'payload.objectives' => ['required', 'string', 'max:800'],
                'payload.reinforcements' => ['required', 'string', 'max:800'],
                'payload.aftermath_notes' => ['nullable', 'string', 'max:800'],
            ],
            'loot' => [
                'payload.reward_type' => ['required', 'string', 'max:160'],
                'payload.holder_source' => ['required', 'string', 'max:220'],
                'payload.summary_note' => ['required', 'string', 'max:700'],
                'payload.clue_tie_in' => ['nullable', 'string', 'max:600'],
                'payload.item_list' => ['required', 'string', 'max:1000'],
                'payload.currency_favor_text' => ['nullable', 'string', 'max:700'],
                'payload.notes' => ['nullable', 'string', 'max:800'],
            ],
            default => [],
        });
    }

    // Developer context: This helper exposes the valid record kinds to requests and services that need the same whitelist.
    // Clear explanation: This gives the rest of the app the list of record types the DM tools support.
    public function knownKinds(): array
    {
        return array_keys(config('dm.kinds', []));
    }

    // Developer context: This helper exposes the valid record statuses to requests and services that need the same whitelist.
    // Clear explanation: This gives the rest of the app the list of save statuses the DM tools support.
    public function knownStatuses(): array
    {
        return array_keys(config('dm.statuses', []));
    }

    // Developer context: This helper provides a normalized starter record for the requested kind so the wizard can begin from a predictable payload shape.
    // Clear explanation: This creates a clean empty DM record that matches the chosen type, like NPC or encounter.
    public function starterRecord(string $kind): array
    {
        return $this->normalizeDraft([
            'kind' => $kind,
            'status' => 'draft',
            'tags' => [],
            'payload' => [],
        ]);
    }

    // Developer context: This helper normalizes payload fields based on the current record kind and keeps the payload schema stable for the wizard and the page.
    // Clear explanation: This cleans the nested record details so each record type always has the right fields available.
    private function normalizePayload(?string $kind, array $payload): array
    {
        return match ($kind) {
            'npc' => [
                'role' => $this->normalizeText($payload['role'] ?? null),
                'species' => $this->normalizeText($payload['species'] ?? null),
                'alignment' => $this->normalizeText($payload['alignment'] ?? null),
                'attitude' => array_key_exists('attitude', $payload)
                    ? $this->normalizeSlug($payload['attitude'])
                    : 'indifferent',
                'first_impression' => $this->normalizeText($payload['first_impression'] ?? null),
                'appearance' => $this->normalizeText($payload['appearance'] ?? null, true),
                'voice' => $this->normalizeText($payload['voice'] ?? null),
                'mannerism' => $this->normalizeText($payload['mannerism'] ?? null),
                'goal' => $this->normalizeText($payload['goal'] ?? null, true),
                'secret' => $this->normalizeText($payload['secret'] ?? null, true),
                'leverage' => $this->normalizeText($payload['leverage'] ?? null, true),
                'fear' => $this->normalizeText($payload['fear'] ?? null, true),
                'bond' => $this->normalizeText($payload['bond'] ?? null, true),
                'faction' => $this->normalizeText($payload['faction'] ?? null),
                'party_relationship' => $this->normalizeText($payload['party_relationship'] ?? null, true),
                'party_hook' => $this->normalizeText($payload['party_hook'] ?? null, true),
                'clue_hooks' => $this->normalizeText($payload['clue_hooks'] ?? null, true),
                'loot_hooks' => $this->normalizeText($payload['loot_hooks'] ?? null, true),
                'combat_mode' => array_key_exists('combat_mode', $payload)
                    ? $this->normalizeSlug($payload['combat_mode'])
                    : 'narrative_only',
                'linked_monster_name' => $this->normalizeText($payload['linked_monster_name'] ?? null),
                'quick_stats' => $this->normalizeQuickStats(
                    is_array($payload['quick_stats'] ?? null) ? $payload['quick_stats'] : [],
                ),
            ],
            'scene' => [
                'location' => $this->normalizeText($payload['location'] ?? null),
                'purpose' => $this->normalizeText($payload['purpose'] ?? null, true),
                'stakes' => $this->normalizeText($payload['stakes'] ?? null, true),
                'pressure' => $this->normalizeText($payload['pressure'] ?? null, true),
                'active_threats' => $this->normalizeText($payload['active_threats'] ?? null, true),
                'clues' => $this->normalizeText($payload['clues'] ?? null, true),
                'hidden_info' => $this->normalizeText($payload['hidden_info'] ?? null, true),
                'obstacles' => $this->normalizeText($payload['obstacles'] ?? null, true),
                'suggested_checks' => $this->normalizeText($payload['suggested_checks'] ?? null, true),
                'linked_npcs' => $this->normalizeText($payload['linked_npcs'] ?? null, true),
                'linked_encounter_notes' => $this->normalizeText($payload['linked_encounter_notes'] ?? null, true),
                'aftermath_notes' => $this->normalizeText($payload['aftermath_notes'] ?? null, true),
            ],
            'quest' => [
                'patron' => $this->normalizeText($payload['patron'] ?? null),
                'hook' => $this->normalizeText($payload['hook'] ?? null, true),
                'objective' => $this->normalizeText($payload['objective'] ?? null, true),
                'complications' => $this->normalizeText($payload['complications'] ?? null, true),
                'antagonists' => $this->normalizeText($payload['antagonists'] ?? null, true),
                'milestones' => $this->normalizeText($payload['milestones'] ?? null, true),
                'reward' => $this->normalizeText($payload['reward'] ?? null, true),
                'next_steps' => $this->normalizeText($payload['next_steps'] ?? null, true),
            ],
            'location' => [
                'overview' => $this->normalizeText($payload['overview'] ?? null, true),
                'sensory_details' => $this->normalizeText($payload['sensory_details'] ?? null, true),
                'exits' => $this->normalizeText($payload['exits'] ?? null, true),
                'hazards' => $this->normalizeText($payload['hazards'] ?? null, true),
                'factions_present' => $this->normalizeText($payload['factions_present'] ?? null, true),
                'secrets' => $this->normalizeText($payload['secrets'] ?? null, true),
                'scene_hooks' => $this->normalizeText($payload['scene_hooks'] ?? null, true),
            ],
            'encounter' => [
                'initiative_snapshot' => $this->normalizeInitiativeSnapshot(
                    is_array($payload['initiative_snapshot'] ?? null) ? $payload['initiative_snapshot'] : [],
                ),
                'terrain_notes' => $this->normalizeText($payload['terrain_notes'] ?? null, true),
                'enemy_plan' => $this->normalizeText($payload['enemy_plan'] ?? null, true),
                'objectives' => $this->normalizeText($payload['objectives'] ?? null, true),
                'reinforcements' => $this->normalizeText($payload['reinforcements'] ?? null, true),
                'aftermath_notes' => $this->normalizeText($payload['aftermath_notes'] ?? null, true),
            ],
            'loot' => [
                'reward_type' => $this->normalizeText($payload['reward_type'] ?? null),
                'holder_source' => $this->normalizeText($payload['holder_source'] ?? null),
                'summary_note' => $this->normalizeText($payload['summary_note'] ?? null, true),
                'clue_tie_in' => $this->normalizeText($payload['clue_tie_in'] ?? null, true),
                'item_list' => $this->normalizeText($payload['item_list'] ?? null, true),
                'currency_favor_text' => $this->normalizeText($payload['currency_favor_text'] ?? null, true),
                'notes' => $this->normalizeText($payload['notes'] ?? null, true),
            ],
            default => [],
        };
    }

    // Developer context: This helper normalizes a quick-stat block for NPCs that need light combat data without a full monster stat block.
    // Clear explanation: This cleans the small combat section used by quick-stat NPCs.
    private function normalizeQuickStats(array $stats): array
    {
        return [
            'initiative_bonus' => $this->normalizeInteger($stats['initiative_bonus'] ?? null),
            'ac' => $this->normalizeInteger($stats['ac'] ?? null),
            'max_hp' => $this->normalizeInteger($stats['max_hp'] ?? null),
            'attack_note' => $this->normalizeText($stats['attack_note'] ?? null),
            'damage_note' => $this->normalizeText($stats['damage_note'] ?? null),
            'spell_note' => $this->normalizeText($stats['spell_note'] ?? null),
        ];
    }

    // Developer context: This helper normalizes encounter snapshot combatants so the DM page can safely reuse them in the live tracker later on.
    // Clear explanation: This cleans the encounter lineup entries so saved encounter records can feed back into the combat tracker.
    private function normalizeInitiativeSnapshot(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->normalizeText($item['name'] ?? null);

            if ($name === null) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'side' => $this->normalizeSlug($item['side'] ?? 'enemy') ?? 'enemy',
                'initiative' => $this->normalizeInteger($item['initiative'] ?? null),
                'initiative_bonus' => $this->normalizeInteger($item['initiative_bonus'] ?? null),
                'ac' => $this->normalizeText($item['ac'] ?? null),
                'current_hp' => $this->normalizeInteger($item['current_hp'] ?? null),
                'max_hp' => $this->normalizeInteger($item['max_hp'] ?? null),
                'temp_hp' => $this->normalizeInteger($item['temp_hp'] ?? null),
                'conditions' => $this->normalizeSimpleList($item['conditions'] ?? []),
                'note' => $this->normalizeText($item['note'] ?? null, true),
            ];
        }

        return $normalized;
    }

    // Developer context: This helper turns mixed tag input into a unique lowercase array so filtering stays consistent.
    // Clear explanation: This cleans tags so searches and filters are easier to use later.
    private function normalizeTags(mixed $value): array
    {
        $items = is_array($value)
            ? $value
            : (is_string($value) ? preg_split('/[,|\n\r]+/', $value) ?: [] : []);

        $tags = [];

        foreach ($items as $item) {
            $normalized = $this->normalizeText($item);

            if ($normalized === null) {
                continue;
            }

            $tags[] = strtolower($normalized);
        }

        return array_values(array_unique($tags));
    }

    // Developer context: This helper normalizes simple list input such as conditions and removes duplicates.
    // Clear explanation: This cleans a plain list into tidy individual entries.
    private function normalizeSimpleList(mixed $value): array
    {
        $items = is_array($value)
            ? $value
            : (is_string($value) ? preg_split('/[,|\n\r]+/', $value) ?: [] : []);

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $item): ?string => $this->normalizeText($item),
            $items,
        ))));
    }

    // Developer context: This helper normalizes human text fields with either single-line or multi-line cleanup.
    // Clear explanation: This cleans normal text values like names, notes, and summaries.
    private function normalizeText(mixed $value, bool $multiline = false): ?string
    {
        return $this->plainText->normalize($value, $multiline);
    }

    // Developer context: This helper normalizes slug-like options such as record kinds, statuses, attitudes, and combat modes.
    // Clear explanation: This turns option-style text into a clean lowercase key.
    private function normalizeSlug(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $slug = trim(strtolower($value));

        return $slug === '' ? null : $slug;
    }

    // Developer context: This helper accepts only whole-number values and rejects empty or malformed numeric input.
    // Clear explanation: This converts number-like input into a real integer only when it is valid.
    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $normalized = trim((string) $value);

        if (preg_match('/^-?\d+$/', $normalized) !== 1) {
            return null;
        }

        return (int) $normalized;
    }

    // Developer context: This helper reads the local monster compendium and returns the exact monster names for validation and wizard matching.
    // Clear explanation: This collects the names of saved local monsters so the DM wizard can link an NPC to a real compendium creature.
    private function monsterNames(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $monster): ?string => is_string($monster['name'] ?? null) ? $monster['name'] : null,
            config('dnd.compendium.monsters.items', []),
        )));
    }
}
