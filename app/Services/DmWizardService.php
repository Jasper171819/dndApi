<?php
// Developer context: This service owns the separate DM wizard flow, commands, save/export actions, and page-patch output while staying isolated from the player-facing rules wizard.
// Clear explanation: This file is the DM-only chat-style wizard that helps create reusable NPCs, scenes, quests, locations, encounters, and loot records.

namespace App\Services;

use App\Models\DmRecord;
use App\Support\DiceRoller;
use App\Support\DmRecordDataValidator;
use App\Support\DmRecordHomebrewExporter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DmWizardService
{
    // Developer context: Laravel injects the shared helpers so the DM wizard can validate records, export them, and reuse the same real dice logic as the rest of the app.
    // Clear explanation: This connects the DM wizard to the helpers it needs for records, exports, and dice rolls.
    public function __construct(
        private readonly DmRecordDataValidator $dmRecords,
        private readonly DmRecordHomebrewExporter $exporter,
        private readonly DiceRoller $diceRoller,
    ) {}

    // Developer context: This is the main DM wizard entry point; it receives one cleaned message plus the current DM wizard state and returns the next reply, state, quick actions, and snapshot.
    // Clear explanation: This method processes one DM wizard message and decides what the wizard should do next.
    public function handle(string $message, array $state): array
    {
        $state = $this->hydrateState($state);
        $trimmed = trim($message);
        $lower = strtolower($trimmed);

        if (preg_match('/^new (npc|scene|quest|location|encounter|loot)$/i', $trimmed, $matches) === 1) {
            return $this->startFlow(strtolower($matches[1]));
        }

        if ($lower === 'help') {
            return $this->helpResponse($state);
        }

        if ($lower === 'list dm records') {
            return $this->listRecordsResponse($state);
        }

        if ($lower === 'load latest dm record') {
            return $this->loadLatestRecord($state);
        }

        if (preg_match('/^load (?:dm )?record (\d+)$/i', $trimmed, $matches) === 1) {
            return $this->loadRecord((int) $matches[1], $state);
        }

        if (preg_match('/^edit (.+)$/i', $trimmed, $matches) === 1) {
            return $this->startEditingField($matches[1], $state);
        }

        if ($lower === 'show summary') {
            return $this->summaryResponse($state);
        }

        if ($lower === 'show social help') {
            return $this->socialHelpResponse($state);
        }

        if (preg_match('/^show monster (.+)$/i', $trimmed, $matches) === 1) {
            return $this->showMonster($matches[1], $state);
        }

        if (preg_match('/^set dc (\d{1,2})$/i', $trimmed, $matches) === 1) {
            return $this->setDc((int) $matches[1], $state);
        }

        if (preg_match('/^roll (.+)$/i', $trimmed, $matches) === 1) {
            return $this->rollCommand($matches[1], $state);
        }

        if ($lower === 'save record') {
            return $this->saveRecord($state);
        }

        if ($lower === 'duplicate record') {
            return $this->duplicateRecord($state);
        }

        if ($lower === 'export to homebrew') {
            return $this->exportToHomebrew($state);
        }

        if ($lower === 'skip') {
            return $this->skipPendingField($state);
        }

        if ($trimmed === '') {
            return $state['pending_field'] !== null
                ? $this->promptForPendingField($state)
                : $this->helpResponse($state);
        }

        if ($state['pending_field'] !== null) {
            return $this->applyPendingField($trimmed, $state);
        }

        return $this->respond(
            'Try `new npc`, `new scene`, `new encounter`, `list dm records`, or `help`.',
            $state,
        );
    }

    // Developer context: This helper starts a fresh DM wizard flow for the requested record kind and moves the wizard to the first required field.
    // Clear explanation: This starts a new DM record draft and opens the first step for that type.
    private function startFlow(string $kind): array
    {
        $draftRecord = $this->dmRecords->starterRecord($kind);

        if ($kind === 'npc') {
            $draftRecord['payload']['attitude'] = null;
            $draftRecord['payload']['combat_mode'] = null;
        }

        $state = $this->hydrateState([
            'flow_kind' => $kind,
            'pending_field' => $this->orderedFields($kind, $draftRecord)[0] ?? null,
            'skipped_optional_fields' => [],
            'draft_record' => $draftRecord,
            'page_linkage' => $this->emptyPageLinkage(),
        ]);

        return $this->promptForPendingField(
            $state,
            sprintf('DM Wizard ready. We are building a %s.', strtolower($this->recordLabel($kind))),
        );
    }

    // Developer context: This helper returns the DM wizard's top-level help response together with the current snapshot.
    // Clear explanation: This shows the DM wizard's main actions and reminds the DM what it can do.
    private function helpResponse(array $state): array
    {
        $reply = "DM Wizard actions:\n"
            ."• new npc\n"
            ."• new scene\n"
            ."• new quest\n"
            ."• new location\n"
            ."• new encounter\n"
            ."• new loot\n"
            ."• list dm records\n"
            ."• load latest dm record\n"
            ."• show monster <name>\n"
            ."• roll <expression>\n"
            ."• set dc <number>\n"
            ."• show social help";

        if (($state['draft_record']['kind'] ?? null) !== null) {
            $reply .= "\n\nCurrent draft: ".$this->draftHeadline($state['draft_record']).'.';
        }

        return $this->respond($reply, $state);
    }

    // Developer context: This helper lists the most recent DM records so the page and wizard can reload saved reusable content.
    // Clear explanation: This shows the latest saved DM records and how to load one back into the wizard.
    private function listRecordsResponse(array $state): array
    {
        $records = DmRecord::query()->latest()->take(8)->get();

        if ($records->isEmpty()) {
            return $this->respond('No DM records are saved yet. Start with `new npc`, `new scene`, or another `new ...` command.', $state);
        }

        $lines = $records->map(
            fn (DmRecord $record): string => sprintf(
                '#%d · %s · %s · %s',
                $record->id,
                $this->recordLabel($record->kind),
                $record->name,
                $this->statusLabel($record->status)
            )
        )->implode("\n");

        $quickActions = $records->take(5)->map(
            fn (DmRecord $record): string => "load dm record {$record->id}"
        )->values()->all();

        return $this->respond(
            "Saved DM records:\n".$lines,
            $state,
            array_merge($quickActions, ['load latest dm record', 'new npc', 'new encounter']),
        );
    }

    // Developer context: This helper loads the most recent saved DM record into the DM wizard state.
    // Clear explanation: This brings the newest saved DM record back into the wizard.
    private function loadLatestRecord(array $state): array
    {
        $record = DmRecord::query()->latest()->first();

        if (! $record) {
            return $this->respond('There is no saved DM record yet to load.', $state);
        }

        return $this->loadRecord($record->id, $state);
    }

    // Developer context: This helper loads a specific saved DM record into the DM wizard state so it can be reviewed, duplicated, or exported.
    // Clear explanation: This opens one saved DM record inside the DM wizard.
    private function loadRecord(int $recordId, array $state): array
    {
        $record = DmRecord::query()->find($recordId);

        if (! $record) {
            return $this->respond('That DM record could not be found.', $state);
        }

        $nextState = $this->hydrateState([
            'flow_kind' => $record->kind,
            'pending_field' => null,
            'skipped_optional_fields' => [],
            'draft_record' => $this->recordToDraft($record),
            'page_linkage' => [
                'last_saved_record_id' => $record->id,
                'session_patch_ready' => false,
                'encounter_patch_ready' => false,
                'npc_patch_ready' => false,
            ],
        ]);

        return $this->respond(
            sprintf('Loaded %s #%d: %s.', strtolower($this->recordLabel($record->kind)), $record->id, $record->name),
            $nextState,
        );
    }

    // Developer context: This helper puts an existing draft field back into edit mode so loaded records can be changed through the wizard instead of only viewed.
    // Clear explanation: This reopens one field from the current DM draft for editing.
    private function startEditingField(string $requestedField, array $state): array
    {
        $kind = $state['flow_kind'];

        if ($kind === null) {
            return $this->respond('Load or start a DM draft first, then choose a field to edit.', $state);
        }

        $field = $this->matchFieldName($kind, $requestedField, $state['draft_record']);

        if ($field === null) {
            return $this->respond('I could not match that field name in the current DM draft.', $state);
        }

        $state['pending_field'] = $field;

        return $this->promptForPendingField(
            $state,
            sprintf('Editing %s.', $this->fieldLabel($kind, $field)),
        );
    }

    // Developer context: This helper returns a compact summary of the current draft plus the next missing field when there still is one.
    // Clear explanation: This shows a quick summary of the current DM record draft.
    private function summaryResponse(array $state): array
    {
        if (($state['draft_record']['kind'] ?? null) === null) {
            return $this->respond('There is no DM draft open yet. Start with `new npc`, `new scene`, or another `new ...` command.', $state);
        }

        $reply = "Current draft: ".$this->draftHeadline($state['draft_record'])."\n";
        $reply .= $state['draft_record']['summary'] ?? 'The summary is still empty.';

        if ($state['pending_field'] !== null) {
            $reply .= "\n\nNext step: ".$this->fieldPrompt($state['flow_kind'], $state['pending_field']);
        }

        return $this->respond($reply, $state);
    }

    // Developer context: This helper returns the DM-facing social reminder based on the official roleplay reference already used elsewhere in the app.
    // Clear explanation: This gives the DM a quick reminder for handling social scenes at the table.
    private function socialHelpResponse(array $state): array
    {
        $reference = config('dnd.roleplay_reference', []);
        $styles = $reference['styles']['summary'] ?? 'Roleplay can be spoken in character or described from the outside.';
        $checks = $reference['checks']['summary'] ?? 'Start with the approach first and only call for a check if the scene needs one.';
        $influence = $reference['influence']['summary'] ?? 'Influence scenes often lean on Persuasion, Deception, Intimidation, Performance, or Animal Handling.';

        $reply = "Social play quick read:\n"
            ."• {$styles}\n"
            ."• {$checks}\n"
            ."• {$influence}\n"
            ."• NPC attitudes start Friendly, Indifferent, or Hostile and can shift as the scene changes.";

        return $this->respond($reply, $state, ['set dc 10', 'set dc 15', 'set dc 20', 'new npc']);
    }

    // Developer context: This helper resolves a local monster by name and returns a concise stat reminder for DM use inside the wizard.
    // Clear explanation: This looks up one local monster and summarizes the most useful details.
    private function showMonster(string $query, array $state): array
    {
        $monster = $this->matchMonster($query);

        if ($monster === null) {
            return $this->respond('I could not find that monster in the local compendium. Try a more exact name.', $state);
        }

        $reply = sprintf(
            "%s\nCR %s · %s · AC %s · HP %s · Initiative %s",
            $monster['name'],
            $monster['cr'] ?? '—',
            $monster['creature_type'] ?? 'Creature',
            $monster['ac'] ?? '—',
            $monster['hp'] ?? '—',
            $monster['initiative'] ?? '—',
        );

        return $this->respond($reply, $state, ['new encounter', 'new npc', 'show summary']);
    }

    // Developer context: This helper answers a DM request to set a target DC and returns a short difficulty note rather than mutating long-lived state.
    // Clear explanation: This tells the DM how that DC reads at the table.
    private function setDc(int $dc, array $state): array
    {
        $label = match (true) {
            $dc <= 5 => 'Very Easy',
            $dc <= 10 => 'Easy',
            $dc <= 15 => 'Moderate',
            $dc <= 20 => 'Hard',
            $dc <= 25 => 'Very Hard',
            default => 'Nearly Impossible',
        };

        return $this->respond(
            sprintf('Use DC %d for the next check. That reads as %s.', $dc, $label),
            $state,
            ['show social help', 'roll d20', 'set dc 15'],
        );
    }

    // Developer context: This helper runs the shared dice roller for DM-side commands and supports trailing advantage or disadvantage markers.
    // Clear explanation: This lets the DM roll dice directly from the DM wizard.
    private function rollCommand(string $expression, array $state): array
    {
        $mode = null;
        $normalizedExpression = trim($expression);

        if (preg_match('/\s+(advantage|disadvantage)$/i', $normalizedExpression, $matches) === 1) {
            $mode = strtolower($matches[1]);
            $normalizedExpression = trim(substr($normalizedExpression, 0, -strlen($matches[0])));
        }

        $result = $this->diceRoller->rollExpression($normalizedExpression, $mode);

        if ($result === null) {
            return $this->respond('That roll could not be parsed. Try formats like `d20`, `2d6+3`, or `d20 advantage`.', $state);
        }

        $modeText = $mode ? " ({$mode})" : '';

        return $this->respond(
            sprintf('Rolled %s%s = %d. Detail: %s', $normalizedExpression, $modeText, $result['total'], $result['detail']),
            $state,
            ['roll d20', 'roll 2d6+3', 'roll d20 advantage'],
        );
    }

    // Developer context: This helper skips the current pending field only when that field is marked optional in the current flow definition.
    // Clear explanation: This moves past the current wizard step only when that step is optional.
    private function skipPendingField(array $state): array
    {
        $kind = $state['flow_kind'];
        $pendingField = $state['pending_field'];

        if ($kind === null || $pendingField === null) {
            return $this->respond('There is no current wizard step to skip.', $state);
        }

        if (! $this->isOptionalField($kind, $pendingField)) {
            return $this->respond('That step is part of the core DM record, so it cannot be skipped.', $state);
        }

        $state['skipped_optional_fields'][] = $pendingField;
        $state['skipped_optional_fields'] = array_values(array_unique($state['skipped_optional_fields']));
        $state = $this->advanceToNextField($state, $pendingField);

        return $this->promptForPendingField(
            $state,
            sprintf('%s skipped for now.', $this->fieldLabel($kind, $pendingField)),
        );
    }

    // Developer context: This helper writes one answered field into the draft record, handles field-specific parsing, and advances the flow.
    // Clear explanation: This saves the current wizard answer into the draft and moves to the next step.
    private function applyPendingField(string $message, array $state): array
    {
        $kind = $state['flow_kind'];
        $field = $state['pending_field'];

        if ($kind === null || $field === null) {
            return $this->respond('There is no active DM wizard step right now.', $state);
        }

        $value = $this->parseFieldValue($kind, $field, $message);

        if ($value['ok'] === false) {
            return $this->respond($value['message'], $state, $this->quickActionsForState($state));
        }

        $state['draft_record'] = $this->setFieldValue($state['draft_record'], $field, $value['value']);
        $state['skipped_optional_fields'] = array_values(array_filter(
            $state['skipped_optional_fields'],
            fn (string $entry): bool => $entry !== $field,
        ));
        $state = $this->advanceToNextField($state, $field);

        return $this->promptForPendingField($state, $value['message']);
    }

    // Developer context: This helper saves the current draft into the dedicated DM records table and keeps the wizard state linked to that saved record.
    // Clear explanation: This stores the current DM draft as a reusable saved record.
    private function saveRecord(array $state): array
    {
        if (($state['draft_record']['kind'] ?? null) === null) {
            return $this->respond('There is no DM draft open yet to save.', $state);
        }

        try {
            $validated = $this->validatedDraftForSave($state['draft_record']);
        } catch (ValidationException $exception) {
            $firstMessage = $exception->validator->errors()->first() ?: 'The DM record is not ready to save yet.';

            return $this->respond($firstMessage, $state);
        }

        $record = ($state['draft_record']['id'] ?? null) !== null
            ? DmRecord::query()->find($state['draft_record']['id'])
            : null;

        if ($record) {
            $record->update($validated);
        } else {
            $record = DmRecord::query()->create($validated);
        }

        $nextState = $this->hydrateState([
            'flow_kind' => $record->kind,
            'pending_field' => null,
            'skipped_optional_fields' => [],
            'draft_record' => $this->recordToDraft($record->fresh()),
            'page_linkage' => array_merge($state['page_linkage'], [
                'last_saved_record_id' => $record->id,
            ]),
        ]);

        return $this->respond(
            sprintf('%s saved as record #%d.', $this->recordLabel($record->kind), $record->id),
            $nextState,
            array_merge(['duplicate record', 'export to homebrew'], $this->quickActionsForState($nextState)),
        );
    }

    // Developer context: This helper duplicates the current draft into a fresh unsaved record so the DM can branch ideas without overwriting the original.
    // Clear explanation: This makes a copy of the current DM draft so the DM can keep editing a new version.
    private function duplicateRecord(array $state): array
    {
        if (($state['draft_record']['kind'] ?? null) === null) {
            return $this->respond('There is no DM draft open yet to duplicate.', $state);
        }

        $draft = $state['draft_record'];
        $draft['id'] = null;
        $draft['linked_homebrew_entry_id'] = null;
        $draft['status'] = 'draft';
        $draft['name'] = trim(($draft['name'] ?? 'Untitled').' Copy');

        $nextState = $this->hydrateState([
            'flow_kind' => $draft['kind'],
            'pending_field' => null,
            'skipped_optional_fields' => [],
            'draft_record' => $draft,
            'page_linkage' => $this->emptyPageLinkage(),
        ]);

        return $this->respond('Draft duplicated. This copy is unsaved until you run `save record`.', $nextState);
    }

    // Developer context: This helper exports the current DM draft through the dedicated DM-record exporter, saving the record first when needed.
    // Clear explanation: This copies the current DM draft into the homebrew workshop as a separate entry.
    private function exportToHomebrew(array $state): array
    {
        if (($state['draft_record']['kind'] ?? null) === null) {
            return $this->respond('There is no DM draft open yet to export.', $state);
        }

        try {
            $validated = $this->validatedDraftForSave($state['draft_record']);
        } catch (ValidationException $exception) {
            $firstMessage = $exception->validator->errors()->first() ?: 'The DM record is not ready to export yet.';

            return $this->respond($firstMessage, $state);
        }

        $record = ($state['draft_record']['id'] ?? null) !== null
            ? DmRecord::query()->find($state['draft_record']['id'])
            : null;

        if ($record) {
            $record->update($validated);
        } else {
            $record = DmRecord::query()->create($validated);
        }

        $entry = $this->exporter->export($record);
        $record = $record->fresh();

        $nextState = $this->hydrateState([
            'flow_kind' => $record->kind,
            'pending_field' => null,
            'skipped_optional_fields' => [],
            'draft_record' => $this->recordToDraft($record),
            'page_linkage' => array_merge($state['page_linkage'], [
                'last_saved_record_id' => $record->id,
            ]),
        ]);

        return $this->respond(
            sprintf('Exported to homebrew as %s #%d.', $entry->category, $entry->id),
            $nextState,
            ['show summary', 'list dm records', 'duplicate record'],
        );
    }

    // Developer context: This helper returns the next wizard prompt, or the completion message when the flow has no more unanswered fields.
    // Clear explanation: This shows the next DM wizard question, or says the draft is ready when the flow is complete.
    private function promptForPendingField(array $state, ?string $prefix = null): array
    {
        $kind = $state['flow_kind'];
        $pendingField = $state['pending_field'];

        if ($kind === null) {
            return $this->helpResponse($state);
        }

        if ($pendingField === null) {
            $reply = 'This draft has every guided field filled in. You can `save record`, `duplicate record`, `export to homebrew`, or `show summary`.';

            if ($prefix) {
                $reply = $prefix."\n\n".$reply;
            }

            return $this->respond($reply, $state);
        }

        $reply = $this->fieldPrompt($kind, $pendingField);

        if ($prefix) {
            $reply = $prefix."\n\n".$reply;
        }

        return $this->respond($reply, $state, $this->quickActionsForState($state));
    }

    // Developer context: This helper produces the wizard response array in the same general shape as the player wizard: reply, state, quick actions, and snapshot.
    // Clear explanation: This packages the DM wizard reply into the format the page expects.
    private function respond(string $reply, array $state, ?array $quickActions = null): array
    {
        $state = $this->refreshState($state);

        return [
            'reply' => $reply,
            'state' => $state,
            'quick_actions' => $quickActions ?? $this->quickActionsForState($state),
            'snapshot' => $this->buildSnapshot($state),
        ];
    }

    // Developer context: This helper merges the incoming state into a stable default shape before other logic reads it.
    // Clear explanation: This makes sure the DM wizard always has a complete state structure to work with.
    private function hydrateState(array $state): array
    {
        $draftRecord = is_array($state['draft_record'] ?? null) ? $state['draft_record'] : [];

        $hydrated = [
            'flow_kind' => $state['flow_kind'] ?? ($draftRecord['kind'] ?? null),
            'pending_field' => $state['pending_field'] ?? null,
            'skipped_optional_fields' => array_values(array_filter(
                is_array($state['skipped_optional_fields'] ?? null) ? $state['skipped_optional_fields'] : [],
                'is_string',
            )),
            'draft_record' => array_merge($this->emptyDraftRecord(), $draftRecord),
            'page_linkage' => array_merge($this->emptyPageLinkage(), is_array($state['page_linkage'] ?? null) ? $state['page_linkage'] : []),
        ];

        return $this->refreshState($hydrated);
    }

    // Developer context: This helper recomputes derived pieces like the auto summary, current pending field, and page-patch readiness after every state change.
    // Clear explanation: This keeps the DM wizard state up to date after each change.
    private function refreshState(array $state): array
    {
        $kind = $state['flow_kind'] ?? null;

        if ($kind !== null && ($state['draft_record']['kind'] ?? null) === null) {
            $state['draft_record'] = array_merge(
                $this->dmRecords->starterRecord($kind),
                $state['draft_record'],
                ['kind' => $kind],
            );
        }

        if (($state['draft_record']['kind'] ?? null) !== null) {
            $state['draft_record']['summary'] = $this->buildSummary($state['draft_record']);
        }

        $state['page_linkage'] = array_merge(
            $this->emptyPageLinkage(),
            $state['page_linkage'] ?? [],
            $this->pageReadiness($state['draft_record']),
        );

        if ($kind === null) {
            $state['pending_field'] = null;
        } elseif ($state['pending_field'] !== null) {
            $orderedFields = $this->orderedFields($kind, $state['draft_record']);

            if (! in_array($state['pending_field'], $orderedFields, true)) {
                $state['pending_field'] = $this->firstIncompleteField($state);
            }
        }

        return $state;
    }

    // Developer context: This helper moves the wizard forward after the current field has been answered or skipped.
    // Clear explanation: This advances the wizard to the next unfinished step.
    private function advanceToNextField(array $state, string $currentField): array
    {
        $kind = $state['flow_kind'];

        if ($kind === null) {
            $state['pending_field'] = null;

            return $state;
        }

        $order = $this->orderedFields($kind, $state['draft_record']);
        $currentIndex = array_search($currentField, $order, true);
        $searchOrder = $currentIndex === false ? $order : array_slice($order, $currentIndex + 1);
        $state['pending_field'] = null;

        foreach ($searchOrder as $field) {
            if (! $this->fieldComplete($state, $field)) {
                $state['pending_field'] = $field;
                break;
            }
        }

        return $this->refreshState($state);
    }

    // Developer context: This helper finds the first guided field that is still missing in the current draft.
    // Clear explanation: This finds the next step the DM wizard still needs.
    private function firstIncompleteField(array $state): ?string
    {
        $kind = $state['flow_kind'];

        if ($kind === null) {
            return null;
        }

        foreach ($this->orderedFields($kind, $state['draft_record']) as $field) {
            if (! $this->fieldComplete($state, $field)) {
                return $field;
            }
        }

        return null;
    }

    // Developer context: This helper checks whether a guided field should count as done either because it has a value or because the DM explicitly skipped an optional step.
    // Clear explanation: This decides whether a wizard step is already handled.
    private function fieldComplete(array $state, string $field): bool
    {
        if (in_array($field, $state['skipped_optional_fields'], true)) {
            return true;
        }

        $value = $this->getFieldValue($state['draft_record'], $field);

        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null && $value !== '';
    }

    // Developer context: This helper parses the answer for the current field, including config-backed options, integers, tags, and linked monster names.
    // Clear explanation: This understands what the DM typed for the current wizard step.
    private function parseFieldValue(string $kind, string $field, string $message): array
    {
        $type = $this->fieldMeta($kind, $field)['type'] ?? 'text';

        return match ($type) {
            'attitude' => $this->parseConfigOptionValue($message, 'dm.npc_attitudes', 'That attitude did not match Friendly, Indifferent, or Hostile.'),
            'combat_mode' => $this->parseConfigOptionValue($message, 'dm.npc_combat_modes', 'Choose Narrative only, Quick stats, or Monster-backed.'),
            'status' => $this->parseConfigOptionValue($message, 'dm.statuses', 'That status did not match Draft, Ready, Active, or Archived.'),
            'alignment' => $this->parseSimpleOptionValue($message, config('dnd.alignments', []), 'That alignment did not match the local list.'),
            'integer' => $this->parseIntegerValue($message, 'Enter a whole number for this field.'),
            'tags' => [
                'ok' => true,
                'value' => preg_split('/[,|\n\r]+/', $message) ?: [],
                'message' => 'Tags noted.',
            ],
            'monster' => $this->parseMonsterValue($message),
            default => $this->parseTextValue(
                $message,
                $this->fieldLabel($kind, $field).' recorded.'
            ),
        };
    }

    // Developer context: This helper parses ordinary text answers and rejects empty values after cleaning.
    // Clear explanation: This accepts normal written answers and makes sure they are not empty.
    private function parseTextValue(string $message, string $successMessage): array
    {
        $value = trim($message);

        if ($value === '') {
            return [
                'ok' => false,
                'message' => 'That answer is still empty. Add a real value or use `skip` if the step is optional.',
            ];
        }

        return [
            'ok' => true,
            'value' => $value,
            'message' => $successMessage,
        ];
    }

    // Developer context: This helper parses a whole-number wizard answer for quick-stat and similar numeric fields.
    // Clear explanation: This accepts a numeric answer only when it is a whole number.
    private function parseIntegerValue(string $message, string $errorMessage): array
    {
        if (preg_match('/^-?\d+$/', trim($message)) !== 1) {
            return [
                'ok' => false,
                'message' => $errorMessage,
            ];
        }

        return [
            'ok' => true,
            'value' => (int) trim($message),
            'message' => 'Number recorded.',
        ];
    }

    // Developer context: This helper parses a key-or-label answer against one of the app's keyed config option groups.
    // Clear explanation: This matches what the DM typed against a known option list like attitudes or statuses.
    private function parseConfigOptionValue(string $message, string $configKey, string $errorMessage): array
    {
        $key = $this->matchConfigOptionKey($message, config($configKey, []));

        if ($key === null) {
            return [
                'ok' => false,
                'message' => $errorMessage,
            ];
        }

        return [
            'ok' => true,
            'value' => $key,
            'message' => Str::headline(str_replace('_', ' ', $key)).' selected.',
        ];
    }

    // Developer context: This helper parses a key-or-label answer against a flat option list.
    // Clear explanation: This matches what the DM typed against a simple known list.
    private function parseSimpleOptionValue(string $message, array $options, string $errorMessage): array
    {
        $matched = $this->matchSimpleOption($message, $options);

        if ($matched === null) {
            return [
                'ok' => false,
                'message' => $errorMessage,
            ];
        }

        return [
            'ok' => true,
            'value' => $matched,
            'message' => $matched.' selected.',
        ];
    }

    // Developer context: This helper parses a linked monster answer and requires the monster to exist in the local compendium.
    // Clear explanation: This accepts a monster name only when it matches a local monster.
    private function parseMonsterValue(string $message): array
    {
        $monster = $this->matchMonster($message);

        if ($monster === null) {
            return [
                'ok' => false,
                'message' => 'That monster was not found in the local compendium.',
            ];
        }

        return [
            'ok' => true,
            'value' => $monster['name'],
            'message' => $monster['name'].' linked as the combat base.',
        ];
    }

    // Developer context: This helper writes a parsed value into the correct top-level or nested payload path inside the draft record.
    // Clear explanation: This stores the wizard answer in the right place inside the current DM draft.
    private function setFieldValue(array $draftRecord, string $field, mixed $value): array
    {
        $path = explode('.', $this->fieldPath($draftRecord['kind'] ?? '', $field));

        $current = &$draftRecord;
        foreach (array_slice($path, 0, -1) as $segment) {
            if (! is_array($current[$segment] ?? null)) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current[end($path)] = $value;

        return $draftRecord;
    }

    // Developer context: This helper reads a field value back from the draft record using the same path rules as the setter.
    // Clear explanation: This looks up the current value for one wizard field.
    private function getFieldValue(array $draftRecord, string $field): mixed
    {
        $path = explode('.', $this->fieldPath($draftRecord['kind'] ?? '', $field));
        $current = $draftRecord;

        foreach ($path as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    // Developer context: This helper validates the finished draft through the shared DM record validator after first ensuring the auto-summary is up to date.
    // Clear explanation: This runs the final DM-record validation right before saving or exporting.
    private function validatedDraftForSave(array $draftRecord): array
    {
        $draftRecord['summary'] = $this->buildSummary($draftRecord);

        return $this->dmRecords->validateForSave($draftRecord);
    }

    // Developer context: This helper converts a saved model back into the normalized draft shape the wizard expects.
    // Clear explanation: This turns a saved DM record back into the wizard's working format.
    private function recordToDraft(DmRecord $record): array
    {
        $draft = $this->dmRecords->normalizeDraft([
            'id' => $record->id,
            'kind' => $record->kind,
            'status' => $record->status,
            'name' => $record->name,
            'summary' => $record->summary,
            'campaign' => $record->campaign,
            'session_label' => $record->session_label,
            'tags' => $record->tags ?? [],
            'payload' => $record->payload ?? [],
            'linked_homebrew_entry_id' => $record->linked_homebrew_entry_id,
        ]);

        $draft['id'] = $record->id;

        return $draft;
    }

    // Developer context: This helper builds the compact right-side snapshot payload the DM page can render and use for apply-to-page actions.
    // Clear explanation: This creates the summary card data the DM page shows next to the wizard.
    private function buildSnapshot(array $state): array
    {
        $record = $state['draft_record'];
        $kind = $record['kind'] ?? null;

        if ($kind === null) {
            return [
                'title' => 'No DM draft open',
                'summary' => 'Start with `new npc`, `new scene`, `new quest`, `new location`, `new encounter`, or `new loot`.',
                'fields' => [],
                'page_patch' => [
                    'session' => null,
                    'encounter' => null,
                    'npc_combatant' => null,
                ],
            ];
        }

        return [
            'title' => $this->draftHeadline($record),
            'summary' => $record['summary'],
            'status' => $record['status'],
            'pending_field' => $state['pending_field'],
            'fields' => $this->snapshotFields($record),
            'page_patch' => $this->pagePatch($record),
        ];
    }

    // Developer context: This helper turns the current draft into a readable headline for replies and snapshot titles.
    // Clear explanation: This makes a short title line for the current draft.
    private function draftHeadline(array $record): string
    {
        $label = $this->recordLabel($record['kind'] ?? 'record');
        $name = $record['name'] ?? 'Untitled';

        return "{$label}: {$name}";
    }

    // Developer context: This helper generates the top-level summary text used by saved records and by the live snapshot.
    // Clear explanation: This creates a short readable summary for the current DM record.
    private function buildSummary(array $record): string
    {
        $kind = $record['kind'] ?? null;
        $name = $record['name'] ?? 'Untitled';
        $payload = is_array($record['payload'] ?? null) ? $record['payload'] : [];

        return match ($kind) {
            'npc' => trim(sprintf(
                '%s is a %s %s%s%s.',
                $name,
                $this->optionLabel('dm.npc_attitudes', $payload['attitude'] ?? 'indifferent'),
                $payload['species'] ?? '',
                ($payload['species'] ?? null) ? ' ' : '',
                $payload['role'] ?? 'NPC'
            )),
            'scene' => sprintf('%s centres on %s at %s.', $name, Str::limit((string) ($payload['purpose'] ?? 'an active scene'), 60), $payload['location'] ?? 'an unnamed place'),
            'quest' => sprintf('%s asks the party to %s.', $name, Str::limit((string) ($payload['objective'] ?? 'follow the hook'), 70)),
            'location' => sprintf('%s is framed as %s.', $name, Str::limit((string) ($payload['overview'] ?? 'a place with secrets and hazards'), 70)),
            'encounter' => sprintf('%s puts pressure on the party through %s.', $name, Str::limit((string) ($payload['enemy_plan'] ?? 'an active enemy plan'), 70)),
            'loot' => sprintf('%s delivers %s.', $name, Str::limit((string) ($payload['summary_note'] ?? 'a reward with a clear hook'), 70)),
            default => $name,
        };
    }

    // Developer context: This helper builds the small visible field list for the DM page snapshot instead of dumping the full record payload every time.
    // Clear explanation: This chooses the most useful details to show in the DM wizard summary card.
    private function snapshotFields(array $record): array
    {
        $payload = is_array($record['payload'] ?? null) ? $record['payload'] : [];

        return array_values(array_filter(match ($record['kind']) {
            'npc' => [
                ['label' => 'Role', 'value' => $payload['role'] ?? null],
                ['label' => 'Attitude', 'value' => $this->optionLabel('dm.npc_attitudes', $payload['attitude'] ?? null)],
                ['label' => 'Combat', 'value' => $this->optionLabel('dm.npc_combat_modes', $payload['combat_mode'] ?? null)],
                ['label' => 'Goal', 'value' => $payload['goal'] ?? null],
            ],
            'scene' => [
                ['label' => 'Location', 'value' => $payload['location'] ?? null],
                ['label' => 'Purpose', 'value' => $payload['purpose'] ?? null],
                ['label' => 'Stakes', 'value' => $payload['stakes'] ?? null],
            ],
            'quest' => [
                ['label' => 'Patron', 'value' => $payload['patron'] ?? null],
                ['label' => 'Objective', 'value' => $payload['objective'] ?? null],
                ['label' => 'Reward', 'value' => $payload['reward'] ?? null],
            ],
            'location' => [
                ['label' => 'Overview', 'value' => $payload['overview'] ?? null],
                ['label' => 'Hazards', 'value' => $payload['hazards'] ?? null],
            ],
            'encounter' => [
                ['label' => 'Enemy plan', 'value' => $payload['enemy_plan'] ?? null],
                ['label' => 'Objectives', 'value' => $payload['objectives'] ?? null],
                ['label' => 'Reinforcements', 'value' => $payload['reinforcements'] ?? null],
            ],
            'loot' => [
                ['label' => 'Reward type', 'value' => $payload['reward_type'] ?? null],
                ['label' => 'Source', 'value' => $payload['holder_source'] ?? null],
                ['label' => 'Items', 'value' => $payload['item_list'] ?? null],
            ],
            default => [],
        }, fn (array $entry): bool => ($entry['value'] ?? null) !== null && ($entry['value'] ?? null) !== ''));
    }

    // Developer context: This helper builds the page-patch payload the DM page can apply to the session board, encounter tracker, or custom combatant entry points.
    // Clear explanation: This prepares the bits the DM page can drop directly into its live tools.
    private function pagePatch(array $record): array
    {
        $payload = is_array($record['payload'] ?? null) ? $record['payload'] : [];

        return [
            'session' => match ($record['kind']) {
                'scene' => [
                    'title' => $record['name'],
                    'location' => $payload['location'] ?? '',
                    'threat' => $payload['active_threats'] ?? '',
                    'scene' => $payload['purpose'] ?? '',
                    'objective' => $payload['stakes'] ?? '',
                    'threads' => $payload['clues'] ?? '',
                    'table_notes' => $payload['hidden_info'] ?? '',
                ],
                'quest' => [
                    'title' => $record['name'],
                    'objective' => $payload['objective'] ?? '',
                    'scene' => $payload['hook'] ?? '',
                    'threat' => $payload['antagonists'] ?? '',
                    'threads' => $payload['complications'] ?? '',
                    'table_notes' => $payload['next_steps'] ?? '',
                ],
                'location' => [
                    'title' => $record['name'],
                    'location' => $record['name'],
                    'scene' => $payload['overview'] ?? '',
                    'threat' => $payload['hazards'] ?? '',
                    'threads' => $payload['secrets'] ?? '',
                    'table_notes' => $payload['scene_hooks'] ?? '',
                ],
                default => null,
            },
            'encounter' => $record['kind'] === 'encounter'
                ? [
                    'round' => 1,
                    'activeId' => null,
                    'combatants' => $this->encounterCombatantsFromSnapshot($payload['initiative_snapshot'] ?? []),
                ]
                : null,
            'npc_combatant' => $record['kind'] === 'npc'
                ? $this->npcCombatantFromRecord($record)
                : null,
        ];
    }

    // Developer context: This helper converts a saved encounter snapshot into the combatant shape the DM page already knows how to render.
    // Clear explanation: This turns an encounter record's saved lineup into live combat tracker entries.
    private function encounterCombatantsFromSnapshot(array $items): array
    {
        $combatants = [];

        foreach ($items as $index => $item) {
            if (! is_array($item) || ! is_string($item['name'] ?? null)) {
                continue;
            }

            $combatants[] = [
                'id' => 'dm-record-'.Str::slug($item['name']).'-'.$index.'-'.time(),
                'order' => time() + $index,
                'source' => 'dm-record',
                'sourceLabel' => 'DM record',
                'name' => $item['name'],
                'side' => $item['side'] ?? 'enemy',
                'initiative' => $item['initiative'] ?? null,
                'initiative_bonus' => $item['initiative_bonus'] ?? 0,
                'ac' => (string) ($item['ac'] ?? ''),
                'current_hp' => $item['current_hp'] ?? ($item['max_hp'] ?? 0),
                'max_hp' => $item['max_hp'] ?? 0,
                'temp_hp' => $item['temp_hp'] ?? 0,
                'conditions' => $item['conditions'] ?? [],
                'note' => $item['note'] ?? '',
            ];
        }

        return $combatants;
    }

    // Developer context: This helper converts the current NPC draft into the custom combatant shape the DM page uses for the live encounter tracker.
    // Clear explanation: This turns an NPC record into a combat-tracker entry the DM can add with one click.
    private function npcCombatantFromRecord(array $record): array
    {
        $payload = is_array($record['payload'] ?? null) ? $record['payload'] : [];
        $mode = $payload['combat_mode'] ?? 'narrative_only';
        $monster = $mode === 'monster_backed'
            ? $this->matchMonster((string) ($payload['linked_monster_name'] ?? ''))
            : null;
        $quickStats = is_array($payload['quick_stats'] ?? null) ? $payload['quick_stats'] : [];

        return [
            'id' => 'dm-npc-'.Str::slug($record['name'] ?? 'npc').'-'.time(),
            'order' => time(),
            'source' => 'dm-record',
            'sourceLabel' => 'DM Wizard NPC',
            'name' => $record['name'] ?? 'Unnamed NPC',
            'side' => match ($payload['attitude'] ?? 'indifferent') {
                'friendly' => 'ally',
                'hostile' => 'enemy',
                default => 'npc',
            },
            'initiative' => null,
            'initiative_bonus' => $mode === 'quick_stats'
                ? (int) ($quickStats['initiative_bonus'] ?? 0)
                : $this->parseMonsterInitiativeBonus($monster['initiative'] ?? null),
            'ac' => $mode === 'quick_stats'
                ? (string) ($quickStats['ac'] ?? '')
                : (string) ($monster['ac'] ?? ''),
            'current_hp' => $mode === 'quick_stats'
                ? (int) ($quickStats['max_hp'] ?? 1)
                : $this->parseMonsterHp($monster['hp'] ?? null),
            'max_hp' => $mode === 'quick_stats'
                ? (int) ($quickStats['max_hp'] ?? 1)
                : $this->parseMonsterHp($monster['hp'] ?? null),
            'temp_hp' => 0,
            'conditions' => [],
            'note' => implode(' · ', array_filter([
                $payload['role'] ?? null,
                $payload['party_hook'] ?? null,
                $mode === 'monster_backed' ? ($payload['linked_monster_name'] ?? null) : null,
                $mode === 'quick_stats' ? ($quickStats['attack_note'] ?? null) : null,
            ])),
        ];
    }

    // Developer context: This helper calculates which DM-page patch actions should light up for the current draft.
    // Clear explanation: This decides whether the current draft can be pushed into the session board or encounter tracker.
    private function pageReadiness(array $record): array
    {
        $kind = $record['kind'] ?? null;

        return [
            'session_patch_ready' => in_array($kind, ['scene', 'quest', 'location'], true) && ! empty($record['name']),
            'encounter_patch_ready' => $kind === 'encounter' && ! empty($record['name']),
            'npc_patch_ready' => $kind === 'npc' && ! empty($record['name']),
        ];
    }

    // Developer context: This helper returns the quick actions that make sense for the current state, including field options while the flow is waiting for a choice.
    // Clear explanation: This decides which ready-made command buttons the DM should see right now.
    private function quickActionsForState(array $state): array
    {
        $kind = $state['flow_kind'];
        $pendingField = $state['pending_field'];

        if ($kind !== null && $pendingField !== null) {
            $actions = $this->fieldQuickActions($kind, $pendingField);

            if ($this->isOptionalField($kind, $pendingField)) {
                $actions[] = 'skip';
            }

            $actions[] = 'show summary';

            return array_values(array_unique(array_filter($actions)));
        }

        $actions = [
            'new npc',
            'new scene',
            'new encounter',
            'list dm records',
            'load latest dm record',
        ];

        if (($state['draft_record']['kind'] ?? null) !== null) {
            $actions = array_merge(
                $actions,
                $this->editQuickActions($state['draft_record']),
                ['save record', 'duplicate record', 'export to homebrew', 'show summary'],
            );
        }

        return array_values(array_unique($actions));
    }

    // Developer context: This helper defines the ordered guided fields for each record kind, including conditional NPC combat fields.
    // Clear explanation: This lists the wizard steps for each type of DM record.
    private function orderedFields(string $kind, array $draftRecord): array
    {
        $commonOptional = ['status', 'campaign', 'session_label', 'tags'];

        return match ($kind) {
            'npc' => array_merge(
                ['name', 'role', 'first_impression', 'voice', 'mannerism', 'goal', 'secret', 'leverage', 'fear', 'party_hook', 'attitude', 'combat_mode'],
                match ($draftRecord['payload']['combat_mode'] ?? 'narrative_only') {
                    'quick_stats' => ['initiative_bonus', 'ac', 'max_hp', 'attack_note', 'damage_note', 'spell_note'],
                    'monster_backed' => ['linked_monster_name'],
                    default => [],
                },
                ['species', 'alignment', 'appearance', 'bond', 'faction', 'party_relationship', 'clue_hooks', 'loot_hooks'],
                $commonOptional,
            ),
            'scene' => array_merge(
                ['name', 'location', 'purpose', 'stakes', 'pressure', 'active_threats', 'clues'],
                ['hidden_info', 'obstacles', 'suggested_checks', 'linked_npcs', 'linked_encounter_notes', 'aftermath_notes'],
                $commonOptional,
            ),
            'quest' => array_merge(
                ['name', 'patron', 'hook', 'objective', 'complications', 'reward'],
                ['antagonists', 'milestones', 'next_steps'],
                $commonOptional,
            ),
            'location' => array_merge(
                ['name', 'overview', 'sensory_details', 'exits', 'hazards'],
                ['factions_present', 'secrets', 'scene_hooks'],
                $commonOptional,
            ),
            'encounter' => array_merge(
                ['name', 'terrain_notes', 'enemy_plan', 'objectives', 'reinforcements'],
                ['aftermath_notes'],
                $commonOptional,
            ),
            'loot' => array_merge(
                ['name', 'reward_type', 'holder_source', 'summary_note', 'item_list'],
                ['clue_tie_in', 'currency_favor_text', 'notes'],
                $commonOptional,
            ),
            default => [],
        };
    }

    // Developer context: This helper exposes the field metadata for prompts, labels, input parsing, and option quick actions.
    // Clear explanation: This describes what each wizard step means and how it should behave.
    private function fieldMeta(string $kind, string $field): array
    {
        $common = [
            'name' => ['path' => 'name', 'label' => 'Name', 'prompt' => 'Give this record a name.', 'required' => true],
            'status' => ['path' => 'status', 'label' => 'Status', 'prompt' => 'Pick the record status.', 'required' => false, 'type' => 'status'],
            'campaign' => ['path' => 'campaign', 'label' => 'Campaign', 'prompt' => 'Add a campaign name if this belongs to a specific campaign.', 'required' => false],
            'session_label' => ['path' => 'session_label', 'label' => 'Session label', 'prompt' => 'Add a session label if this belongs to a specific session.', 'required' => false],
            'tags' => ['path' => 'tags', 'label' => 'Tags', 'prompt' => 'Add search tags separated by commas if you want them.', 'required' => false, 'type' => 'tags'],
        ];

        $meta = match ($kind) {
            'npc' => [
                'role' => ['path' => 'payload.role', 'label' => 'Role', 'prompt' => 'What role does this NPC fill in the world or the current scene?', 'required' => true],
                'species' => ['path' => 'payload.species', 'label' => 'Species', 'prompt' => 'Add a species if it matters for the table.', 'required' => false],
                'alignment' => ['path' => 'payload.alignment', 'label' => 'Alignment', 'prompt' => 'Add an alignment if it helps you frame the NPC.', 'required' => false, 'type' => 'alignment'],
                'attitude' => ['path' => 'payload.attitude', 'label' => 'Attitude', 'prompt' => 'How does this NPC currently feel about the party?', 'required' => true, 'type' => 'attitude'],
                'first_impression' => ['path' => 'payload.first_impression', 'label' => 'First impression', 'prompt' => 'What hits the party first when they meet this NPC?', 'required' => true],
                'appearance' => ['path' => 'payload.appearance', 'label' => 'Appearance', 'prompt' => 'Add a quick appearance note if you want one.', 'required' => false],
                'voice' => ['path' => 'payload.voice', 'label' => 'Voice', 'prompt' => 'How does this NPC sound?', 'required' => true],
                'mannerism' => ['path' => 'payload.mannerism', 'label' => 'Mannerism', 'prompt' => 'What repeated mannerism or habit stands out?', 'required' => true],
                'goal' => ['path' => 'payload.goal', 'label' => 'Goal', 'prompt' => 'What is this NPC trying to achieve right now?', 'required' => true],
                'secret' => ['path' => 'payload.secret', 'label' => 'Secret', 'prompt' => 'What is this NPC hiding from the party?', 'required' => true],
                'leverage' => ['path' => 'payload.leverage', 'label' => 'Leverage', 'prompt' => 'What pressure point, leverage, or temptation matters to this NPC?', 'required' => true],
                'fear' => ['path' => 'payload.fear', 'label' => 'Fear', 'prompt' => 'What does this NPC most want to avoid losing or facing?', 'required' => true],
                'bond' => ['path' => 'payload.bond', 'label' => 'Bond', 'prompt' => 'Add a bond if you want a strong personal tie on the sheet.', 'required' => false],
                'faction' => ['path' => 'payload.faction', 'label' => 'Faction', 'prompt' => 'Add a faction, guild, temple, crew, or other affiliation if it matters.', 'required' => false],
                'party_relationship' => ['path' => 'payload.party_relationship', 'label' => 'Party relationship', 'prompt' => 'How does this NPC connect to the party?', 'required' => false],
                'party_hook' => ['path' => 'payload.party_hook', 'label' => 'Party-facing hook', 'prompt' => 'Why should the party care about this NPC right away?', 'required' => true],
                'clue_hooks' => ['path' => 'payload.clue_hooks', 'label' => 'Clue hooks', 'prompt' => 'Add clue hooks if this NPC can point the party toward information.', 'required' => false],
                'loot_hooks' => ['path' => 'payload.loot_hooks', 'label' => 'Loot hooks', 'prompt' => 'Add loot hooks if this NPC can point the party toward rewards.', 'required' => false],
                'combat_mode' => ['path' => 'payload.combat_mode', 'label' => 'Combat mode', 'prompt' => 'Should this NPC stay narrative only, use quick stats, or borrow a monster block?', 'required' => true, 'type' => 'combat_mode'],
                'linked_monster_name' => ['path' => 'payload.linked_monster_name', 'label' => 'Linked monster', 'prompt' => 'Which local monster should this NPC borrow as a combat base?', 'required' => true, 'type' => 'monster'],
                'initiative_bonus' => ['path' => 'payload.quick_stats.initiative_bonus', 'label' => 'Initiative bonus', 'prompt' => 'For quick stats, what initiative bonus should this NPC use?', 'required' => true, 'type' => 'integer'],
                'ac' => ['path' => 'payload.quick_stats.ac', 'label' => 'AC', 'prompt' => 'For quick stats, what Armor Class should this NPC use?', 'required' => true, 'type' => 'integer'],
                'max_hp' => ['path' => 'payload.quick_stats.max_hp', 'label' => 'Max HP', 'prompt' => 'For quick stats, what max HP should this NPC use?', 'required' => true, 'type' => 'integer'],
                'attack_note' => ['path' => 'payload.quick_stats.attack_note', 'label' => 'Attack note', 'prompt' => 'Add a short attack note if you want one.', 'required' => false],
                'damage_note' => ['path' => 'payload.quick_stats.damage_note', 'label' => 'Damage note', 'prompt' => 'Add a short damage note if you want one.', 'required' => false],
                'spell_note' => ['path' => 'payload.quick_stats.spell_note', 'label' => 'Spell or trick note', 'prompt' => 'Add a spell or trick note if this NPC uses one.', 'required' => false],
            ],
            'scene' => [
                'location' => ['path' => 'payload.location', 'label' => 'Location', 'prompt' => 'Where is this scene taking place?', 'required' => true],
                'purpose' => ['path' => 'payload.purpose', 'label' => 'Purpose', 'prompt' => 'What is this scene trying to do at the table?', 'required' => true],
                'stakes' => ['path' => 'payload.stakes', 'label' => 'Stakes', 'prompt' => 'What is at risk if the party fails or hesitates?', 'required' => true],
                'pressure' => ['path' => 'payload.pressure', 'label' => 'Pressure or timer', 'prompt' => 'What pressure, timer, or cost keeps this scene moving?', 'required' => true],
                'active_threats' => ['path' => 'payload.active_threats', 'label' => 'Active threats', 'prompt' => 'What active threats are pressing on the scene right now?', 'required' => true],
                'clues' => ['path' => 'payload.clues', 'label' => 'Clues', 'prompt' => 'What clues should the party be able to find here?', 'required' => true],
                'hidden_info' => ['path' => 'payload.hidden_info', 'label' => 'Hidden info', 'prompt' => 'Add hidden information if there is something the players do not know yet.', 'required' => false],
                'obstacles' => ['path' => 'payload.obstacles', 'label' => 'Obstacles', 'prompt' => 'Add obstacles if the scene has specific blockers or complications.', 'required' => false],
                'suggested_checks' => ['path' => 'payload.suggested_checks', 'label' => 'Suggested checks', 'prompt' => 'Add suggested skills or DC ideas if you want quick reference.', 'required' => false],
                'linked_npcs' => ['path' => 'payload.linked_npcs', 'label' => 'Linked NPCs', 'prompt' => 'Add any linked NPC names if they matter here.', 'required' => false],
                'linked_encounter_notes' => ['path' => 'payload.linked_encounter_notes', 'label' => 'Linked encounter notes', 'prompt' => 'Add encounter notes if the scene can tip into combat.', 'required' => false],
                'aftermath_notes' => ['path' => 'payload.aftermath_notes', 'label' => 'Aftermath notes', 'prompt' => 'Add aftermath notes if you want a reminder for what follows.', 'required' => false],
            ],
            'quest' => [
                'patron' => ['path' => 'payload.patron', 'label' => 'Patron', 'prompt' => 'Who gives or anchors this quest?', 'required' => true],
                'hook' => ['path' => 'payload.hook', 'label' => 'Hook', 'prompt' => 'What pulls the party into this quest?', 'required' => true],
                'objective' => ['path' => 'payload.objective', 'label' => 'Objective', 'prompt' => 'What does success look like?', 'required' => true],
                'complications' => ['path' => 'payload.complications', 'label' => 'Complications', 'prompt' => 'What makes this quest harder than it first looks?', 'required' => true],
                'antagonists' => ['path' => 'payload.antagonists', 'label' => 'Antagonists', 'prompt' => 'Add antagonists if there is a clear opposing force.', 'required' => false],
                'milestones' => ['path' => 'payload.milestones', 'label' => 'Milestones', 'prompt' => 'Add milestones if you want the quest broken into beats.', 'required' => false],
                'reward' => ['path' => 'payload.reward', 'label' => 'Reward', 'prompt' => 'What does the party gain if they see this through?', 'required' => true],
                'next_steps' => ['path' => 'payload.next_steps', 'label' => 'Next-step notes', 'prompt' => 'Add next-step notes if you want the likely follow-up ready.', 'required' => false],
            ],
            'location' => [
                'overview' => ['path' => 'payload.overview', 'label' => 'Overview', 'prompt' => 'What is the short overview of this location?', 'required' => true],
                'sensory_details' => ['path' => 'payload.sensory_details', 'label' => 'Sensory details', 'prompt' => 'What do the party notice first through sight, sound, smell, or feel?', 'required' => true],
                'exits' => ['path' => 'payload.exits', 'label' => 'Exits', 'prompt' => 'What exits or routes matter here?', 'required' => true],
                'hazards' => ['path' => 'payload.hazards', 'label' => 'Hazards', 'prompt' => 'What hazards or pressures does this location carry?', 'required' => true],
                'factions_present' => ['path' => 'payload.factions_present', 'label' => 'Factions present', 'prompt' => 'Add any factions, crews, or groups present here if useful.', 'required' => false],
                'secrets' => ['path' => 'payload.secrets', 'label' => 'Secrets', 'prompt' => 'Add secrets if the location hides more than it shows.', 'required' => false],
                'scene_hooks' => ['path' => 'payload.scene_hooks', 'label' => 'Scene hooks', 'prompt' => 'Add scene hooks if this place naturally launches other scenes.', 'required' => false],
            ],
            'encounter' => [
                'terrain_notes' => ['path' => 'payload.terrain_notes', 'label' => 'Terrain notes', 'prompt' => 'What terrain details matter in this encounter?', 'required' => true],
                'enemy_plan' => ['path' => 'payload.enemy_plan', 'label' => 'Enemy plan', 'prompt' => 'What is the enemy plan or opening pressure?', 'required' => true],
                'objectives' => ['path' => 'payload.objectives', 'label' => 'Objectives', 'prompt' => 'What objectives matter besides simple defeat?', 'required' => true],
                'reinforcements' => ['path' => 'payload.reinforcements', 'label' => 'Reinforcements', 'prompt' => 'What reinforcements, escalations, or second-wave twists are ready?', 'required' => true],
                'aftermath_notes' => ['path' => 'payload.aftermath_notes', 'label' => 'Aftermath notes', 'prompt' => 'Add aftermath notes if you want the fallout ready.', 'required' => false],
            ],
            'loot' => [
                'reward_type' => ['path' => 'payload.reward_type', 'label' => 'Reward type', 'prompt' => 'What kind of reward is this?', 'required' => true],
                'holder_source' => ['path' => 'payload.holder_source', 'label' => 'Holder or source', 'prompt' => 'Who holds this reward, or where does it come from?', 'required' => true],
                'summary_note' => ['path' => 'payload.summary_note', 'label' => 'Summary note', 'prompt' => 'What is the short table-ready summary of this reward?', 'required' => true],
                'clue_tie_in' => ['path' => 'payload.clue_tie_in', 'label' => 'Clue tie-in', 'prompt' => 'Add a clue tie-in if this reward is linked to a larger mystery.', 'required' => false],
                'item_list' => ['path' => 'payload.item_list', 'label' => 'Item list', 'prompt' => 'What specific items, valuables, or favors are in the reward?', 'required' => true],
                'currency_favor_text' => ['path' => 'payload.currency_favor_text', 'label' => 'Currency or favor text', 'prompt' => 'Add a short note for coin, favors, or obligations if useful.', 'required' => false],
                'notes' => ['path' => 'payload.notes', 'label' => 'Notes', 'prompt' => 'Add any extra loot notes if you want them.', 'required' => false],
            ],
            default => [],
        };

        return array_merge($common, $meta)[$field] ?? [
            'path' => $field,
            'label' => Str::headline($field),
            'prompt' => 'Add a value for this field.',
            'required' => false,
        ];
    }

    // Developer context: This helper exposes the stored path for a field so both reads and writes stay in sync.
    // Clear explanation: This tells the wizard where a field lives inside the draft record.
    private function fieldPath(string $kind, string $field): string
    {
        return $this->fieldMeta($kind, $field)['path'] ?? $field;
    }

    // Developer context: This helper exposes the human-readable label for a field.
    // Clear explanation: This gives a clean label for one wizard step.
    private function fieldLabel(string $kind, string $field): string
    {
        return $this->fieldMeta($kind, $field)['label'] ?? Str::headline($field);
    }

    // Developer context: This helper matches a DM-typed field label like `enemy plan` or `reward type` back to the internal field key used by the wizard.
    // Clear explanation: This figures out which record field the DM wants to edit.
    private function matchFieldName(string $kind, string $requestedField, array $draftRecord): ?string
    {
        $needle = strtolower(trim($requestedField));

        foreach ($this->orderedFields($kind, $draftRecord) as $field) {
            $fieldLabel = strtolower($this->fieldLabel($kind, $field));
            $headline = strtolower(Str::headline($field));

            if ($needle === strtolower($field) || $needle === $fieldLabel || $needle === $headline) {
                return $field;
            }
        }

        return null;
    }

    // Developer context: This helper exposes the prompt text for a field.
    // Clear explanation: This gives the question text for one wizard step.
    private function fieldPrompt(string $kind, string $field): string
    {
        return $this->fieldMeta($kind, $field)['prompt'] ?? 'Add a value for this field.';
    }

    // Developer context: This helper checks whether a field is optional in the current flow definition.
    // Clear explanation: This tells the wizard whether the current step may be skipped.
    private function isOptionalField(string $kind, string $field): bool
    {
        return ! (bool) ($this->fieldMeta($kind, $field)['required'] ?? false);
    }

    // Developer context: This helper exposes the best quick-action buttons for the current field, especially for option-based questions.
    // Clear explanation: This decides which ready-to-click answers should appear for the current wizard step.
    private function fieldQuickActions(string $kind, string $field): array
    {
        return match ($this->fieldMeta($kind, $field)['type'] ?? 'text') {
            'attitude' => array_values(array_map(
                fn (array $entry): string => $entry['label'],
                config('dm.npc_attitudes', []),
            )),
            'combat_mode' => array_values(array_map(
                fn (array $entry): string => $entry['label'],
                config('dm.npc_combat_modes', []),
            )),
            'status' => array_values(array_map(
                fn (array $entry): string => $entry['label'],
                config('dm.statuses', []),
            )),
            'alignment' => array_values(array_slice(config('dnd.alignments', []), 0, 5)),
            'integer' => ['0', '2', '5', '10'],
            'monster' => $this->monsterNameSuggestions(),
            default => [],
        };
    }

    // Developer context: This helper offers a few useful edit shortcuts for whichever draft is currently loaded in the wizard.
    // Clear explanation: This creates quick edit buttons for the most important fields in the current DM draft.
    private function editQuickActions(array $draftRecord): array
    {
        return match ($draftRecord['kind'] ?? null) {
            'npc' => ['edit name', 'edit role', 'edit goal'],
            'scene' => ['edit name', 'edit location', 'edit stakes'],
            'quest' => ['edit name', 'edit objective', 'edit reward'],
            'location' => ['edit name', 'edit overview', 'edit hazards'],
            'encounter' => ['edit name', 'edit enemy plan', 'edit objectives'],
            'loot' => ['edit name', 'edit reward type', 'edit item list'],
            default => [],
        };
    }

    // Developer context: This helper returns a small list of monster suggestions for the monster-linking wizard step.
    // Clear explanation: This gives a few monster-name shortcuts when the wizard asks for a linked monster.
    private function monsterNameSuggestions(): array
    {
        return array_values(array_slice(array_map(
            static fn (array $monster): string => (string) $monster['name'],
            array_filter(config('dnd.compendium.monsters.items', []), static fn (array $monster): bool => isset($monster['name']))
        ), 0, 5));
    }

    // Developer context: This helper matches a typed answer against a keyed config option set by key or label.
    // Clear explanation: This tries to match what the DM typed to a known keyed option.
    private function matchConfigOptionKey(string $value, array $options): ?string
    {
        $needle = strtolower(trim($value));

        foreach ($options as $key => $entry) {
            $label = strtolower($entry['label'] ?? $key);

            if ($needle === strtolower($key) || $needle === $label) {
                return (string) $key;
            }
        }

        return null;
    }

    // Developer context: This helper matches a typed answer against a flat option list by case-insensitive value.
    // Clear explanation: This tries to match what the DM typed to a simple known option.
    private function matchSimpleOption(string $value, array $options): ?string
    {
        $needle = strtolower(trim($value));

        foreach ($options as $option) {
            if ($needle === strtolower((string) $option)) {
                return (string) $option;
            }
        }

        return null;
    }

    // Developer context: This helper matches a local monster by exact or partial name.
    // Clear explanation: This tries to find a monster in the local monster list.
    private function matchMonster(string $value): ?array
    {
        $needle = strtolower(trim($value));
        $monsters = config('dnd.compendium.monsters.items', []);

        foreach ($monsters as $monster) {
            if ($needle === strtolower((string) ($monster['name'] ?? ''))) {
                return $monster;
            }
        }

        foreach ($monsters as $monster) {
            if (str_contains(strtolower((string) ($monster['name'] ?? '')), $needle)) {
                return $monster;
            }
        }

        return null;
    }

    // Developer context: This helper returns the display label for a record kind from config.
    // Clear explanation: This turns a record kind like `npc` into a readable label.
    private function recordLabel(string $kind): string
    {
        return config("dm.kinds.{$kind}.label", Str::headline($kind));
    }

    // Developer context: This helper returns the display label for a record status from config.
    // Clear explanation: This turns a status key like `ready` into a readable label.
    private function statusLabel(string $status): string
    {
        return config("dm.statuses.{$status}.label", Str::headline($status));
    }

    // Developer context: This helper returns the display label for an option key from config.
    // Clear explanation: This turns a saved option key into the readable label shown on the page.
    private function optionLabel(string $configKey, ?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        return config("{$configKey}.{$key}.label", Str::headline((string) $key));
    }

    // Developer context: This helper provides the empty draft-record shell used before a real record kind is chosen.
    // Clear explanation: This creates the blank DM record structure the wizard starts from.
    private function emptyDraftRecord(): array
    {
        return [
            'id' => null,
            'kind' => null,
            'status' => 'draft',
            'name' => null,
            'summary' => null,
            'campaign' => null,
            'session_label' => null,
            'tags' => [],
            'payload' => [],
            'linked_homebrew_entry_id' => null,
        ];
    }

    // Developer context: This helper provides the empty page-linkage block the wizard uses to tell the DM page which apply buttons should be available.
    // Clear explanation: This creates the default set of page-connection flags for the DM wizard.
    private function emptyPageLinkage(): array
    {
        return [
            'last_saved_record_id' => null,
            'session_patch_ready' => false,
            'encounter_patch_ready' => false,
            'npc_patch_ready' => false,
        ];
    }

    // Developer context: This helper extracts the first number from a monster HP field so monster-backed NPCs can create usable combatant entries.
    // Clear explanation: This turns a monster HP note into a usable number for the encounter tracker.
    private function parseMonsterHp(mixed $value): int
    {
        preg_match('/(\d+)/', (string) ($value ?? ''), $matches);

        return isset($matches[1]) ? (int) $matches[1] : 1;
    }

    // Developer context: This helper extracts the signed initiative bonus from a monster initiative field.
    // Clear explanation: This turns a monster initiative note into a usable bonus for the encounter tracker.
    private function parseMonsterInitiativeBonus(mixed $value): int
    {
        preg_match('/([+-]\d+)/', (string) ($value ?? ''), $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }
}
