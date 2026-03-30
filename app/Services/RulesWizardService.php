<?php

namespace App\Services;

use App\Models\Character;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RulesWizardService
{
    private const STAT_FIELDS = [
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    private const REQUIRED_FIELDS = [
        'name',
        'species',
        'class',
        'subclass',
        'background',
        'origin_feat',
        'languages',
        'level',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    private const GUIDED_FIELDS = [
        'class',
        'level',
        'subclass',
        'background',
        'species',
        'origin_feat',
        'languages',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'alignment',
        'name',
        'personality_traits',
        'ideals',
        'bonds',
        'flaws',
        'age',
        'height',
        'weight',
        'eyes',
        'hair',
        'skin',
        'notes',
    ];

    private const OPTIONAL_FIELDS = [
        'alignment',
        'personality_traits',
        'ideals',
        'bonds',
        'flaws',
        'age',
        'height',
        'weight',
        'eyes',
        'hair',
        'skin',
        'notes',
    ];

    private const FIELD_LABELS = [
        'name' => 'Name',
        'species' => 'Species',
        'class' => 'Class',
        'subclass' => 'Subclass',
        'background' => 'Background',
        'alignment' => 'Alignment',
        'origin_feat' => 'Origin Feat',
        'languages' => 'Languages',
        'personality_traits' => 'Personality Traits',
        'ideals' => 'Ideals',
        'bonds' => 'Bonds',
        'flaws' => 'Flaws',
        'age' => 'Age',
        'height' => 'Height',
        'weight' => 'Weight',
        'eyes' => 'Eyes',
        'hair' => 'Hair',
        'skin' => 'Skin',
        'level' => 'Level',
        'strength' => 'Strength',
        'dexterity' => 'Dexterity',
        'constitution' => 'Constitution',
        'intelligence' => 'Intelligence',
        'wisdom' => 'Wisdom',
        'charisma' => 'Charisma',
        'notes' => 'Notes',
    ];

    private const THIRD_CASTER_MAX_SPELL_LEVEL = [
        1 => 0,
        2 => 0,
        3 => 1,
        4 => 1,
        5 => 1,
        6 => 1,
        7 => 2,
        8 => 2,
        9 => 2,
        10 => 2,
        11 => 2,
        12 => 2,
        13 => 3,
        14 => 3,
        15 => 3,
        16 => 3,
        17 => 3,
        18 => 3,
        19 => 4,
        20 => 4,
    ];

    private const ABILITY_LOOKUP = [
        'str' => 'strength',
        'strength' => 'strength',
        'dex' => 'dexterity',
        'dexterity' => 'dexterity',
        'con' => 'constitution',
        'constitution' => 'constitution',
        'int' => 'intelligence',
        'intelligence' => 'intelligence',
        'wis' => 'wisdom',
        'wisdom' => 'wisdom',
        'cha' => 'charisma',
        'charisma' => 'charisma',
    ];

    public function handle(?string $message, array $state = []): array
    {
        $state = $this->normalizeState($state);
        $message = trim((string) $message);
        $command = Str::of($message)->lower()->squish()->toString();

        if ($message === '') {
            return $this->handleEmptyMessage($state);
        }

        if ($command === 'help' || $command === 'commands') {
            return $this->response($state, $this->helpMessage(), $this->defaultQuickActions($state));
        }

        if ($state['pending_field'] !== null && in_array($command, ['skip', 'skip this', 'skip field', 'next'], true)) {
            return $this->skipPendingField($state);
        }

        if (in_array($command, ['skip all details', 'finish details', 'done with details'], true)) {
            return $this->finishOptionalGuidance($state);
        }

        if (in_array($command, ['new', 'new character', 'start', 'start wizard', 'create', 'create character'], true)) {
            return $this->startNewCharacter();
        }

        if ($command === 'list characters' || $command === 'show characters') {
            return $this->listCharacters($state);
        }

        if ($command === 'load latest') {
            return $this->loadLatestCharacter($state);
        }

        if (preg_match('/^load(?: character)?\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->loadCharacterReference((int) $matches[1], $state);
        }

        if ($command === 'roll stats') {
            return $this->rollStats($state);
        }

        if ($command === 'show summary' || $command === 'summary') {
            return $this->showSummary($state);
        }

        if ($command === 'show status' || $command === 'status') {
            return $this->showStatus($state);
        }

        if (in_array($command, ['what did i gain', 'gains', 'show gains'], true)) {
            return $this->showGains($state);
        }

        if (in_array($command, ['show next', 'next level', 'what do i get next'], true)) {
            return $this->showNextLevelPreview($state);
        }

        if ($command === 'level up') {
            return $this->levelUp($state);
        }

        if (in_array($command, ['show spells', 'spell options', 'spell list'], true)) {
            return $this->showSpells($state);
        }

        if (in_array($command, ['show slots', 'spell slots', 'show spell slots'], true)) {
            return $this->showSpellSlots($state);
        }

        if ($command === 'save character' || $command === 'save') {
            return $this->saveCharacter($state);
        }

        if (preg_match('/^(?:set )?hp\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->setHitPoints($state, (int) $matches[1]);
        }

        if (preg_match('/^(?:take\s+)?(?:(critical|crit)\s+)?damage\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->applyDamage($state, (int) $matches[2], ! empty($matches[1]));
        }

        if (preg_match('/^heal\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->heal($state, (int) $matches[1]);
        }

        if (preg_match('/^(?:set\s+)?temp(?:orary)? hp\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->setTempHitPoints($state, (int) $matches[1]);
        }

        if ($command === 'clear temp hp' || $command === 'remove temp hp') {
            return $this->setTempHitPoints($state, 0);
        }

        if (preg_match('/^set ac\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->setArmorClass($state, (int) $matches[1]);
        }

        if (preg_match('/^apply condition\s+(.+)$/i', $message, $matches) === 1) {
            return $this->applyCondition($state, trim($matches[1]));
        }

        if (preg_match('/^remove condition\s+(.+)$/i', $message, $matches) === 1) {
            return $this->removeCondition($state, trim($matches[1]));
        }

        if ($command === 'clear conditions' || $command === 'remove all conditions') {
            return $this->clearConditions($state);
        }

        if (preg_match('/^set exhaustion\s+([0-6])$/i', $message, $matches) === 1) {
            return $this->setExhaustion($state, (int) $matches[1]);
        }

        if ($command === 'roll initiative') {
            return $this->rollInitiative($state);
        }

        if (preg_match('/^roll skill\s+(.+?)(?:\s+(proficient|expertise))?$/i', $message, $matches) === 1) {
            return $this->rollSkillCheck($state, trim($matches[1]), $matches[2] ?? null);
        }

        if (preg_match('/^roll save\s+(.+?)(?:\s+(advantage|disadvantage))?$/i', $message, $matches) === 1) {
            return $this->rollSavingThrow($state, trim($matches[1]), $matches[2] ?? null);
        }

        if (preg_match('/^roll ability\s+(.+?)(?:\s+(advantage|disadvantage))?$/i', $message, $matches) === 1) {
            return $this->rollAbilityCheck($state, trim($matches[1]), $matches[2] ?? null);
        }

        if ($command === 'roll death save') {
            return $this->rollDeathSave($state);
        }

        if ($command === 'death save success') {
            return $this->recordDeathSave($state, true);
        }

        if ($command === 'death save failure') {
            return $this->recordDeathSave($state, false);
        }

        if (preg_match('/^roll\s+(.+?)(?:\s+(advantage|disadvantage))?$/i', $message, $matches) === 1) {
            return $this->rollExpression($state, trim($matches[1]), $matches[2] ?? null);
        }

        if (preg_match('/^short rest(?:\s+(\d+))?$/i', $message, $matches) === 1) {
            return $this->shortRest($state, isset($matches[1]) ? (int) $matches[1] : 0);
        }

        if ($command === 'long rest') {
            return $this->longRest($state);
        }

        if (preg_match('/^use slot\s+([1-9])$/i', $message, $matches) === 1) {
            return $this->useSpellSlot($state, (int) $matches[1]);
        }

        if (preg_match('/^cast(?: spell)?\s+(.+)$/i', $message, $matches) === 1) {
            return $this->castSpell($state, trim($matches[1]));
        }

        if (preg_match('/^(?:concentrate|start concentration)\s+(.+)$/i', $message, $matches) === 1) {
            return $this->startConcentration($state, trim($matches[1]));
        }

        if ($command === 'drop concentration' || $command === 'end concentration') {
            return $this->endConcentration($state);
        }

        if (preg_match('/^(?:show|inspect) monster\s+(.+)$/i', $message, $matches) === 1) {
            return $this->showMonster($state, trim($matches[1]));
        }

        if (in_array($command, ['help me roleplay', 'roleplay help', 'show roleplay help'], true)) {
            return $this->showRoleplayHelp($state);
        }

        if (in_array($command, ['show appearance help', 'appearance help', 'help me with looks'], true)) {
            return $this->showAppearanceHelp($state);
        }

        if (preg_match('/^(?:set|choose)\s+alignment\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'alignment', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+origin(?: |_)?feat\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'origin_feat', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+languages?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'languages', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+personality(?: |_)?traits?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'personality_traits', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+ideals?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'ideals', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+bonds?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'bonds', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+flaws?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'flaws', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+(age|height|weight|eyes|hair|skin)\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, strtolower($matches[1]), trim($matches[2]));
        }

        if (preg_match('/^(?:set|add|write)\s+notes?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'notes', trim($matches[1]));
        }

        if (preg_match('/^(?:set|choose)\s+(name|species|class|subclass|background|level|strength|dexterity|constitution|intelligence|wisdom|charisma)\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, strtolower($matches[1]), trim($matches[2]));
        }

        if ($state['pending_field'] !== null) {
            return $this->handleFieldInput($state, $state['pending_field'], $message);
        }

        return $this->response(
            $state,
            "I did not recognize that command. Try `new character`, `show summary`, `show status`, `roll initiative`, `short rest`, `long rest`, `show monster goblin`, or `help`.",
            $this->defaultQuickActions($state),
        );
    }

    private function handleEmptyMessage(array $state): array
    {
        if ($state['pending_field'] !== null) {
            return $this->askForField($state, $state['pending_field']);
        }

        if ($this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                "The rules wizard is ready. Ask for `show summary`, `show status`, `what did I gain`, `show next`, `level up`, `show spells`, `help me roleplay`, `show appearance help`, `roll initiative`, `short rest`, `long rest`, or `save character`.",
                $this->defaultQuickActions($state),
            );
        }

        return $this->response(
            $state,
            "Welcome to the rules wizard. I am a deterministic D&D 2024 guide, not AI generation. I can build a character step by step, explain each choice in plain language, load a saved character, track dungeon-state math, calculate level gains, and show spell access from your local rules data.",
            ['new character', 'list characters', 'load latest', 'help'],
        );
    }

    private function startNewCharacter(): array
    {
        $state = $this->normalizeState([]);
        $state['pending_field'] = 'class';

        return $this->response(
            $state,
            "Starting a new character draft. We will follow the 2024 handbook flow as closely as this app can: Step 1 class, Step 2 origin, Step 3 ability scores, Step 4 alignment, Step 5 the extra sheet details. Core sheet mechanics cannot be skipped. Roleplay, appearance, and notes can.\n\n".$this->guidedFieldHeading('class')."\n".$this->fieldPrompt('class', $state),
            $this->quickActionsForField('class', $state),
        );
    }

    private function listCharacters(array $state): array
    {
        $characters = Character::query()
            ->latest()
            ->limit(8)
            ->get(['id', 'name', 'class', 'subclass', 'level']);

        if ($characters->isEmpty()) {
            return $this->response(
                $state,
                "Your roster is empty right now. Start with `new character` and I will guide the build.",
                ['new character', 'help'],
            );
        }

        $lines = $characters->values()->map(
            static fn (Character $character, int $index): string => sprintf(
                'Roster #%d: %s - %s%s, level %d',
                $index + 1,
                $character->name,
                $character->class,
                $character->subclass ? " ({$character->subclass})" : '',
                $character->level,
            )
        )->all();

        return $this->response(
            $state,
            "Saved characters:\n- ".implode("\n- ", $lines),
            array_map(
                static fn (int $index): string => 'load character '.($index + 1),
                array_keys($lines),
            ),
        );
    }

    private function loadLatestCharacter(array $state): array
    {
        $character = Character::query()->latest()->first();

        if (! $character) {
            return $this->response(
                $state,
                'There is no saved character to load yet.',
                ['new character', 'help'],
            );
        }

        return $this->loadCharacter($character->id, $state);
    }

    private function loadCharacterReference(int $reference, array $state): array
    {
        $character = Character::query()
            ->latest()
            ->skip(max($reference - 1, 0))
            ->first();

        if ($character) {
            return $this->loadCharacter($character->id, $state);
        }

        if (Character::find($reference)) {
            return $this->loadCharacter($reference, $state);
        }

        return $this->response(
            $state,
            "I could not find roster character {$reference}. Try `list characters` first.",
            ['list characters', 'new character'],
        );
    }

    private function loadCharacter(int $id, array $state): array
    {
        $character = Character::find($id);

        if (! $character) {
            return $this->response(
                $state,
                'I could not find that saved character. Try `list characters` first.',
                ['list characters', 'new character'],
            );
        }

        $state = $this->normalizeState([
            'character' => $this->characterToState($character),
            'pending_field' => null,
        ]);

        return $this->response(
            $state,
            "Loaded {$character->name}. You can now ask `show summary`, `show status`, `what did I gain`, `show next`, `level up`, `show spells`, `roll initiative`, or `show monster goblin`.",
            $this->defaultQuickActions($state),
        );
    }

    private function handleFieldInput(array $state, string $field, string $value): array
    {
        $assignment = $this->assignField($state, $field, $value);

        if (! $assignment['ok']) {
            return $this->response(
                $assignment['state'],
                $assignment['message'],
                $this->quickActionsForField($field, $assignment['state']),
            );
        }

        $state = $assignment['state'];
        $state['skipped_optional_fields'] = array_values(array_filter(
            $state['skipped_optional_fields'] ?? [],
            static fn (string $entry): bool => $entry !== $field,
        ));
        $nextField = $this->nextGuidedField($state);
        $state['pending_field'] = $nextField;

        if ($nextField !== null) {
            return $this->response(
                $state,
                $assignment['message']."\n\n".$this->guidedFieldHeading($nextField)."\n".$this->fieldPrompt($nextField, $state).$this->skipHintForField($nextField),
                $this->quickActionsForField($nextField, $state),
            );
        }

        return $this->response(
            $state,
            $assignment['message']."\n\nThe draft is complete. Ask `show summary`, `show status`, `what did I gain`, `show next`, `show spells`, or `save character`.",
            $this->defaultQuickActions($state),
        );
    }

    private function assignField(array $state, string $field, string $value): array
    {
        $character = $state['character'];
        $message = '';

        switch ($field) {
            case 'name':
                if ($value === '') {
                    return ['ok' => false, 'state' => $state, 'message' => 'The character still needs a name.'];
                }
                $character['name'] = Str::limit($value, 255, '');
                $message = "Name set to {$character['name']}.";
                break;

            case 'species':
                $match = $this->matchOption($value, config('dnd.species', []));
                if ($match === null) {
                    return ['ok' => false, 'state' => $state, 'message' => 'That species did not match the local 2024 list.'];
                }
                $character['species'] = $match;
                $message = "Species set to {$match}.\n".$this->speciesGuidance($match);
                break;

            case 'class':
                $match = $this->matchOption($value, config('dnd.classes', []));
                if ($match === null) {
                    return ['ok' => false, 'state' => $state, 'message' => 'That class did not match the local 2024 list.'];
                }
                $character['class'] = $match;
                $character['subclass'] = null;
                $message = "Class set to {$match}.\n".$this->classGuidance($match);
                break;

            case 'subclass':
                if (! $character['class']) {
                    return ['ok' => false, 'state' => $state, 'message' => 'Choose a class before choosing a subclass.'];
                }

                $subclasses = config("dnd.class_details.{$character['class']}.subclasses", []);
                $match = $this->matchOption($value, $subclasses);
                if ($match === null) {
                    return ['ok' => false, 'state' => $state, 'message' => "That subclass is not valid for {$character['class']}."]; 
                }
                $character['subclass'] = $match;
                $message = "Subclass set to {$match}.\nThis is your specialization inside {$character['class']}, so many later features will point back to this choice.";
                break;

            case 'background':
                $match = $this->matchOption($value, config('dnd.backgrounds', []));
                if ($match === null) {
                    return ['ok' => false, 'state' => $state, 'message' => 'That background did not match the local 2024 list.'];
                }
                $character['background'] = $match;
                $message = "Background set to {$match}.\n".$this->backgroundGuidance($match);
                break;

            case 'alignment':
                $match = $this->matchOption($value, config('dnd.alignments', []));
                if ($match === null) {
                    return ['ok' => false, 'state' => $state, 'message' => 'That alignment did not match the local list.'];
                }
                $character['alignment'] = $match;
                $message = "Alignment set to {$match}.\n".$this->alignmentGuidance($match);
                break;

            case 'origin_feat':
                $match = $this->matchOption($value, config('dnd.origin_feats', []));
                if ($match === null) {
                    return ['ok' => false, 'state' => $state, 'message' => 'That origin feat did not match the local 2024 list.'];
                }
                $character['origin_feat'] = $match;
                $message = "Origin feat set to {$match}.\n".(config("dnd.origin_feat_details.{$match}") ?: 'Origin feats add a small early gameplay twist.');
                break;

            case 'languages':
                $matches = array_values(array_filter(array_map(
                    function (string $entry): ?string {
                        $value = $this->matchOption($entry, config('dnd.languages', []));

                        return $value ?: null;
                    },
                    preg_split('/[,|\n\r]+/', $value) ?: [],
                )));

                if ($matches === []) {
                    return ['ok' => false, 'state' => $state, 'message' => 'I could not match any of those languages from the local list.'];
                }

                $character['languages'] = array_values(array_unique($matches));
                $message = 'Languages set to '.implode(', ', $character['languages']).".\nLanguages mostly matter for travel, NPC interaction, and lore access.";
                break;

            case 'personality_traits':
            case 'ideals':
            case 'bonds':
            case 'flaws':
                if ($value === '') {
                    return ['ok' => false, 'state' => $state, 'message' => self::FIELD_LABELS[$field].' cannot be empty once you choose to set it.'];
                }

                $character[$field] = Str::limit($value, 1000, '');
                $message = self::FIELD_LABELS[$field]." set.\n".$this->roleplayFieldGuidance($field, $character);
                break;

            case 'age':
            case 'height':
            case 'weight':
            case 'eyes':
            case 'hair':
            case 'skin':
                if ($value === '') {
                    return ['ok' => false, 'state' => $state, 'message' => self::FIELD_LABELS[$field].' cannot be empty once you choose to set it.'];
                }

                $character[$field] = Str::limit($value, 255, '');
                $message = self::FIELD_LABELS[$field]." set to {$character[$field]}.\n".$this->appearanceFieldGuidance($field, $character);
                break;

            case 'notes':
                if ($value === '') {
                    return ['ok' => false, 'state' => $state, 'message' => 'Notes cannot be empty once you choose to set them.'];
                }

                $character['notes'] = Str::limit($value, 2000, '');
                $message = "Notes set.\nUse notes for campaign reminders, secrets, goals, or anything the sheet should remember.";
                break;

            case 'level':
                if (! ctype_digit($value) || (int) $value < 1 || (int) $value > 20) {
                    return ['ok' => false, 'state' => $state, 'message' => 'Level must be a number from 1 to 20.'];
                }
                $character['level'] = (int) $value;
                $message = "Level set to {$character['level']}.\n".((int) $character['level'] === 1
                    ? 'Level 1 is the easiest place to learn the class from the ground up.'
                    : 'Higher levels give more features, so there is more to keep track of during play.');
                break;

            default:
                if (! in_array($field, self::STAT_FIELDS, true)) {
                    return ['ok' => false, 'state' => $state, 'message' => 'That field is not supported by the rules wizard.'];
                }

                if (! ctype_digit($value) || (int) $value < 3 || (int) $value > 18) {
                    return ['ok' => false, 'state' => $state, 'message' => self::FIELD_LABELS[$field].' must be a number from 3 to 18.'];
                }

                $character[$field] = (int) $value;
                $message = self::FIELD_LABELS[$field]." set to {$character[$field]}.\n".$this->abilityGuidance($field, (int) $character[$field], $character);
                break;
        }

        $state['character'] = $this->markAsDraft($character);

        return [
            'ok' => true,
            'state' => $state,
            'message' => $message,
        ];
    }

    private function rollStats(array $state): array
    {
        $character = $this->markAsDraft($state['character']);

        foreach (self::STAT_FIELDS as $field) {
            $character[$field] = $this->rollAbilityScore();
        }

        $state['character'] = $character;
        $state['pending_field'] = $this->nextGuidedField($state);

        $lines = [];
        foreach (self::STAT_FIELDS as $field) {
            $lines[] = sprintf('%s %d', strtoupper(substr($field, 0, 3)), $character[$field]);
        }

        return $this->response(
            $state,
            'Rolled ability scores: '.implode(', ', $lines).'.'.($state['pending_field']
                ? "\n\n".$this->guidedFieldHeading($state['pending_field'])."\n".$this->fieldPrompt($state['pending_field'], $state).$this->skipHintForField($state['pending_field'])
                : "\n\nThe build is ready. You can review it with `show summary` or save it now."),
            $state['pending_field'] ? $this->quickActionsForField($state['pending_field'], $state) : $this->defaultQuickActions($state),
        );
    }

    private function showSummary(array $state): array
    {
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                'There is no active character draft yet. Start with `new character` or `list characters`.',
                ['new character', 'list characters'],
            );
        }

        $snapshot = $this->buildSnapshot($state);

        $lines = [
            $snapshot['identity'],
            'Proficiency Bonus: '.($snapshot['proficiency_bonus'] ?? 'n/a'),
        ];

        if ($snapshot['estimated_hit_points'] !== null) {
            $lines[] = "Estimated Hit Points: {$snapshot['estimated_hit_points']}";
        }

        if ($snapshot['missing_fields'] !== []) {
            $lines[] = 'Missing: '.implode(', ', $snapshot['missing_fields']);
        }

        if ($snapshot['spellcasting_summary'] !== null) {
            $lines[] = $snapshot['spellcasting_summary'];
        }

        if (($snapshot['character_details'] ?? []) !== []) {
            $lines[] = 'Details: '.implode(' / ', $snapshot['character_details']);
        }

        if (($snapshot['languages'] ?? []) !== []) {
            $lines[] = 'Languages: '.implode(', ', $snapshot['languages']);
        }

        if (($snapshot['roleplay'] ?? []) !== []) {
            $lines[] = 'Roleplay: '.implode(' | ', $snapshot['roleplay']);
        }

        if (($snapshot['appearance'] ?? []) !== []) {
            $lines[] = 'Appearance: '.implode(' | ', $snapshot['appearance']);
        }

        if (($snapshot['notes'] ?? null) !== null) {
            $lines[] = 'Notes: '.$snapshot['notes'];
        }

        if (($snapshot['dungeon_status'] ?? null) !== null) {
            $lines[] = $snapshot['dungeon_status'];
        }

        $lines[] = 'Stats: '.implode(', ', array_map(
            static fn (array $stat): string => $stat['score'] === null ? "{$stat['label']} -" : "{$stat['label']} {$stat['score']} ({$stat['modifier']})",
            $snapshot['stats'],
        ));

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function showStatus(array $state): array
    {
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                'There is no active character draft yet. Start with `new character` or `list characters`.',
                ['new character', 'list characters'],
            );
        }

        $snapshot = $this->buildSnapshot($state);
        $lines = [
            $snapshot['identity'],
            $snapshot['dungeon_status'] ?? 'Dungeon state is not ready yet.',
        ];

        if (($snapshot['conditions'] ?? []) !== []) {
            $lines[] = 'Conditions: '.implode(', ', $snapshot['conditions']);
        }

        if (($snapshot['resources'] ?? []) !== []) {
            $lines[] = 'Resources: '.implode(' | ', $snapshot['resources']);
        }

        if (($snapshot['concentration'] ?? null) !== null) {
            $lines[] = 'Concentration: '.$snapshot['concentration'];
        }

        if (($snapshot['death_track'] ?? null) !== null) {
            $lines[] = $snapshot['death_track'];
        }

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function showGains(array $state): array
    {
        $character = $state['character'];
        $class = $character['class'];
        $level = $character['level'];

        if (! $class || ! $level) {
            return $this->response(
                $state,
                'I need at least a class and level before I can calculate gains.',
                ['new character', 'show summary'],
            );
        }

        return $this->response($state, $this->describeLevelGains($character, (int) $level), $this->defaultQuickActions($state));
    }

    private function showNextLevelPreview(array $state): array
    {
        $character = $state['character'];
        $class = $character['class'];
        $level = $character['level'];

        if (! $class || ! $level) {
            return $this->response(
                $state,
                'I need at least a class and level before I can preview the next level.',
                ['new character', 'show summary'],
            );
        }

        if ((int) $level >= 20) {
            return $this->response($state, 'This character is already at level 20.', $this->defaultQuickActions($state));
        }

        return $this->response(
            $state,
            $this->describeLevelGains($character, (int) $level + 1, true),
            $this->defaultQuickActions($state),
        );
    }

    private function levelUp(array $state): array
    {
        $character = $this->markAsDraft($state['character']);

        if (! $character['class'] || ! $character['level']) {
            return $this->response(
                $state,
                'I need a class and current level before I can level the character up.',
                ['new character', 'show summary'],
            );
        }

        if ((int) $character['level'] >= 20) {
            return $this->response($state, 'This character is already at level 20.', $this->defaultQuickActions($state));
        }

        $character['level'] = (int) $character['level'] + 1;
        $state['character'] = $character;

        return $this->response(
            $state,
            "Level increased to {$character['level']}.\n\n".$this->describeLevelGains($character, (int) $character['level']),
            $this->defaultQuickActions($state),
        );
    }

    private function showSpells(array $state): array
    {
        $character = $state['character'];
        $classTag = $this->spellClassTag($character);

        if ($classTag === null) {
            return $this->response(
                $state,
                'This build does not currently have a supported spell list in the local wizard.',
                $this->defaultQuickActions($state),
            );
        }

        $maxSpellLevel = $this->maxSpellLevel($character);

        if ($maxSpellLevel < 0) {
            return $this->response(
                $state,
                'I need a class and level before I can determine spell access.',
                $this->defaultQuickActions($state),
            );
        }

        $spells = collect(config('dnd.compendium.spells.items', []))
            ->filter(static function (array $spell) use ($classTag, $maxSpellLevel): bool {
                return in_array($classTag, $spell['classes'] ?? [], true) && ($spell['level'] ?? 99) <= $maxSpellLevel;
            })
            ->groupBy(static fn (array $spell): string => (string) $spell['level'])
            ->sortKeys()
            ->map(function (Collection $levelSpells, string $level): string {
                $names = $levelSpells
                    ->sortBy('name')
                    ->pluck('name')
                    ->values()
                    ->all();

                $preview = array_slice($names, 0, 10);
                $suffix = count($names) > 10 ? sprintf(' (+%d more)', count($names) - 10) : '';

                return sprintf('%s: %s%s', $this->spellLevelLabel((int) $level, true), implode(', ', $preview), $suffix);
            })
            ->values()
            ->all();

        if ($spells === []) {
            return $this->response(
                $state,
                'No spell entries matched this build in the local compendium yet.',
                $this->defaultQuickActions($state),
            );
        }

        $slotSummary = $this->spellcastingSummary($character) ?? 'Spellcasting data not available.';

        return $this->response(
            $state,
            "Spell access for {$character['class']} at level {$character['level']}:\n{$slotSummary}\n\n".implode("\n", $spells),
            $this->defaultQuickActions($state),
        );
    }

    private function showSpellSlots(array $state): array
    {
        $remaining = $state['dungeon']['spell_slots_remaining'] ?? [];
        $maximum = $this->maxSpellSlotsForCharacter($state['character']);

        if ($maximum === []) {
            return $this->response(
                $state,
                'This build does not currently track spell slots.',
                $this->defaultQuickActions($state),
            );
        }

        $subject = $state['character']['name'] ?: 'this build';
        $lines = ["Spell slots for {$subject}:"];
        foreach ($maximum as $level => $count) {
            $lines[] = sprintf(
                '- %s level: %d / %d',
                $this->spellLevelLabel((int) $level),
                (int) ($remaining[(string) $level] ?? $remaining[$level] ?? 0),
                $count,
            );
        }

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function setHitPoints(array $state, int $hitPoints): array
    {
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['max_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I know the HP total.', ['new character', 'load latest']);
        }

        $state['dungeon']['current_hp'] = max(0, min($hitPoints, (int) $state['dungeon']['max_hp']));

        if ($state['dungeon']['current_hp'] > 0) {
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;
        }

        return $this->response(
            $state,
            sprintf(
                'Current HP set to %d/%d.',
                $state['dungeon']['current_hp'],
                $state['dungeon']['max_hp'],
            ),
            $this->defaultQuickActions($state),
        );
    }

    private function applyDamage(array $state, int $damage, bool $critical = false): array
    {
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['current_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track damage.', ['new character', 'load latest']);
        }

        $lines = [];
        $tempAbsorbed = min((int) $state['dungeon']['temp_hp'], $damage);
        $remainingDamage = $damage - $tempAbsorbed;

        if ($tempAbsorbed > 0) {
            $state['dungeon']['temp_hp'] -= $tempAbsorbed;
            $lines[] = "Temporary HP absorbed {$tempAbsorbed}.";
        }

        $wasAtZero = (int) $state['dungeon']['current_hp'] === 0;
        $state['dungeon']['current_hp'] = max(0, (int) $state['dungeon']['current_hp'] - $remainingDamage);

        if (! $wasAtZero && (int) $state['dungeon']['current_hp'] === 0) {
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;
            $lines[] = 'The character dropped to 0 HP.';
        } elseif ($wasAtZero && $remainingDamage > 0) {
            $extraFailures = $critical ? 2 : 1;
            $state['dungeon']['death_failures'] = min(3, (int) $state['dungeon']['death_failures'] + $extraFailures);
            $state['dungeon']['stable'] = false;
            $lines[] = "Damage at 0 HP adds {$extraFailures} death save failure".($extraFailures > 1 ? 's.' : '.');
        }

        $lines[] = sprintf(
            'HP now %d/%d with %d temporary HP.',
            $state['dungeon']['current_hp'],
            $state['dungeon']['max_hp'],
            $state['dungeon']['temp_hp'],
        );

        if ($state['dungeon']['concentration']) {
            $lines[] = 'Concentration check DC '.max(10, (int) ceil($damage / 2)).' if the character was concentrating.';
        }

        if ((int) $state['dungeon']['death_failures'] >= 3) {
            $lines[] = 'The death save track has reached 3 failures.';
        }

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function heal(array $state, int $healing): array
    {
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['current_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track healing.', ['new character', 'load latest']);
        }

        $state['dungeon']['current_hp'] = min(
            (int) $state['dungeon']['max_hp'],
            (int) $state['dungeon']['current_hp'] + $healing,
        );

        if ((int) $state['dungeon']['current_hp'] > 0) {
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;
        }

        return $this->response(
            $state,
            sprintf(
                'Recovered %d HP. Current HP is now %d/%d.',
                $healing,
                $state['dungeon']['current_hp'],
                $state['dungeon']['max_hp'],
            ),
            $this->defaultQuickActions($state),
        );
    }

    private function setTempHitPoints(array $state, int $temporaryHitPoints): array
    {
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response($state, 'Load or build a character first so I can track temporary HP.', ['new character', 'load latest']);
        }

        $state['dungeon']['temp_hp'] = max(0, $temporaryHitPoints);

        return $this->response(
            $state,
            "Temporary HP set to {$state['dungeon']['temp_hp']}.",
            $this->defaultQuickActions($state),
        );
    }

    private function setArmorClass(array $state, int $armorClass): array
    {
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response($state, 'Load or build a character first so I can track Armor Class.', ['new character', 'load latest']);
        }

        $state['dungeon']['ac'] = max(1, $armorClass);

        return $this->response(
            $state,
            "Armor Class set to {$state['dungeon']['ac']}.",
            $this->defaultQuickActions($state),
        );
    }

    private function applyCondition(array $state, string $input): array
    {
        $condition = $this->matchOption($input, config('dnd.conditions', []));

        if ($condition === null) {
            return $this->response(
                $state,
                'That condition did not match the local rules list.',
                ['show status', 'help'],
            );
        }

        $conditions = $state['dungeon']['conditions'];
        if (! in_array($condition, $conditions, true)) {
            $conditions[] = $condition;
            sort($conditions);
        }

        $state['dungeon']['conditions'] = $conditions;
        $summary = config("dnd.condition_details.{$condition}", '');
        $reply = "Applied {$condition}.";
        if ($summary !== '') {
            $reply .= ' '.$summary;
        }

        return $this->response($state, $reply, $this->defaultQuickActions($state));
    }

    private function removeCondition(array $state, string $input): array
    {
        $condition = $this->matchOption($input, $state['dungeon']['conditions']);

        if ($condition === null) {
            return $this->response($state, 'That condition is not active right now.', $this->defaultQuickActions($state));
        }

        $state['dungeon']['conditions'] = array_values(array_filter(
            $state['dungeon']['conditions'],
            static fn (string $entry): bool => $entry !== $condition,
        ));

        return $this->response($state, "Removed {$condition}.", $this->defaultQuickActions($state));
    }

    private function clearConditions(array $state): array
    {
        $state['dungeon']['conditions'] = [];

        return $this->response($state, 'All tracked conditions were cleared.', $this->defaultQuickActions($state));
    }

    private function setExhaustion(array $state, int $level): array
    {
        $state['dungeon']['exhaustion'] = max(0, min(6, $level));

        return $this->response(
            $state,
            "Exhaustion set to level {$state['dungeon']['exhaustion']}.",
            $this->defaultQuickActions($state),
        );
    }

    private function shortRest(array $state, int $hitDiceToSpend): array
    {
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['current_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track rests.', ['new character', 'load latest']);
        }

        if ((int) $state['dungeon']['current_hp'] === 0) {
            return $this->response($state, 'A character at 0 HP needs healing before taking a normal short rest.', $this->defaultQuickActions($state));
        }

        $lines = ['Short rest complete.'];
        $availableHitDice = (int) ($state['dungeon']['hit_dice_remaining'] ?? 0);
        $spend = min($hitDiceToSpend, $availableHitDice);

        if ($spend > 0) {
            $perDie = max(1, $this->shortRestRecoveryPerDie($state['character']));
            $recovered = min(
                ((int) $state['dungeon']['max_hp']) - ((int) $state['dungeon']['current_hp']),
                $spend * $perDie,
            );
            $state['dungeon']['current_hp'] += max(0, $recovered);
            $state['dungeon']['hit_dice_remaining'] = max(0, $availableHitDice - $spend);
            $lines[] = "Spent {$spend} Hit Dice for about {$recovered} HP.";
        }

        if (($state['character']['class'] ?? null) === 'Warlock') {
            $state['dungeon']['spell_slots_remaining'] = $this->maxSpellSlotsForCharacter($state['character']);
            $lines[] = 'Warlock Pact Magic slots were refreshed.';
        }

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function longRest(array $state): array
    {
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['max_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track rests.', ['new character', 'load latest']);
        }

        $previousExhaustion = (int) $state['dungeon']['exhaustion'];
        $state['dungeon']['current_hp'] = $state['dungeon']['max_hp'];
        $state['dungeon']['temp_hp'] = 0;
        $state['dungeon']['hit_dice_remaining'] = (int) ($state['character']['level'] ?? 0);
        $state['dungeon']['spell_slots_remaining'] = $this->maxSpellSlotsForCharacter($state['character']);
        $state['dungeon']['death_successes'] = 0;
        $state['dungeon']['death_failures'] = 0;
        $state['dungeon']['stable'] = false;
        $state['dungeon']['concentration'] = null;
        $state['dungeon']['exhaustion'] = max(0, $previousExhaustion - 1);

        $lines = [
            'Long rest complete.',
            sprintf('HP restored to %d/%d.', $state['dungeon']['current_hp'], $state['dungeon']['max_hp']),
            'Spell slots and Hit Dice were refreshed.',
        ];

        if ($previousExhaustion !== (int) $state['dungeon']['exhaustion']) {
            $lines[] = "Exhaustion reduced to {$state['dungeon']['exhaustion']}.";
        }

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function useSpellSlot(array $state, int $slotLevel): array
    {
        $maximum = $this->maxSpellSlotsForCharacter($state['character']);

        if (! isset($maximum[$slotLevel])) {
            return $this->response($state, "This build does not have {$this->spellLevelLabel($slotLevel)}-level spell slots.", $this->defaultQuickActions($state));
        }

        $remaining = (int) ($state['dungeon']['spell_slots_remaining'][(string) $slotLevel] ?? $state['dungeon']['spell_slots_remaining'][$slotLevel] ?? 0);
        if ($remaining <= 0) {
            return $this->response($state, "No {$this->spellLevelLabel($slotLevel)}-level slots remain.", $this->defaultQuickActions($state));
        }

        $state['dungeon']['spell_slots_remaining'][(string) $slotLevel] = $remaining - 1;

        return $this->response(
            $state,
            sprintf(
                'Used one %s-level slot. Remaining: %d/%d.',
                $this->spellLevelLabel($slotLevel),
                $state['dungeon']['spell_slots_remaining'][(string) $slotLevel],
                $maximum[$slotLevel],
            ),
            $this->defaultQuickActions($state),
        );
    }

    private function castSpell(array $state, string $spellName): array
    {
        $character = $state['character'];
        $classTag = $this->spellClassTag($character);

        if ($classTag === null) {
            return $this->response($state, 'This build does not currently have a supported spell list in the local wizard.', $this->defaultQuickActions($state));
        }

        $spell = collect(config('dnd.compendium.spells.items', []))
            ->first(function (array $entry) use ($spellName, $classTag): bool {
                return Str::lower($entry['name'] ?? '') === Str::lower($spellName)
                    && in_array($classTag, $entry['classes'] ?? [], true);
            });

        if (! is_array($spell)) {
            return $this->response($state, "I could not find a local spell entry for {$spellName} on this class list.", $this->defaultQuickActions($state));
        }

        $level = (int) ($spell['level'] ?? 0);
        $summary = $spell['summary'] ?? '';

        if ($level > 0) {
            $usableLevel = $this->lowestAvailableSpellSlot($state['dungeon']['spell_slots_remaining'], $level);

            if ($usableLevel === null) {
                return $this->response(
                    $state,
                    "No spell slot of {$this->spellLevelLabel($level)} level or higher remains for {$spell['name']}.",
                    $this->defaultQuickActions($state),
                );
            }

            $state['dungeon']['spell_slots_remaining'][(string) $usableLevel]--;
        }

        if (! empty($spell['concentration'])) {
            $state['dungeon']['concentration'] = $spell['name'];
        }

        $lines = ["Cast {$spell['name']}."];
        if ($summary !== '') {
            $lines[] = $summary;
        }
        if ($level > 0) {
            $lines[] = 'Slot spent: '.$this->spellLevelLabel($usableLevel).' level.';
        } else {
            $lines[] = 'Cantrip cast. No slot spent.';
        }
        if (! empty($spell['concentration'])) {
            $lines[] = "Concentration started on {$spell['name']}.";
        }

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function startConcentration(array $state, string $subject): array
    {
        $state['dungeon']['concentration'] = Str::limit($subject, 120, '');

        return $this->response(
            $state,
            "Concentration started on {$state['dungeon']['concentration']}.",
            $this->defaultQuickActions($state),
        );
    }

    private function endConcentration(array $state): array
    {
        if (! $state['dungeon']['concentration']) {
            return $this->response($state, 'No concentration effect is being tracked right now.', $this->defaultQuickActions($state));
        }

        $spell = $state['dungeon']['concentration'];
        $state['dungeon']['concentration'] = null;

        return $this->response($state, "Concentration ended on {$spell}.", $this->defaultQuickActions($state));
    }

    private function rollInitiative(array $state): array
    {
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response($state, 'Load or build a character first so I can roll initiative.', ['new character', 'load latest']);
        }

        $modifier = (int) ($state['dungeon']['initiative_bonus'] ?? 0);
        $result = $this->rollD20($modifier);
        $state['dungeon']['last_initiative'] = $result['total'];

        return $this->response(
            $state,
            sprintf(
                'Initiative: %d (%d %s %s).',
                $result['total'],
                $result['roll'],
                $modifier >= 0 ? '+' : '-',
                abs($modifier),
            ),
            $this->defaultQuickActions($state),
        );
    }

    private function rollSkillCheck(array $state, string $skillInput, ?string $training): array
    {
        $skillName = $this->matchOption($skillInput, config('dnd.skills', []));

        if ($skillName === null) {
            return $this->response($state, 'That skill did not match the local rules list.', $this->defaultQuickActions($state));
        }

        $ability = config("dnd.skill_details.{$skillName}.ability");
        $abilityField = is_string($ability) ? Str::of($ability)->lower()->toString() : '';

        if (! isset($state['character'][$abilityField]) || $state['character'][$abilityField] === null) {
            return $this->response($state, "I need {$ability} on the active character before rolling {$skillName}.", $this->defaultQuickActions($state));
        }

        $modifier = $this->abilityModifier((int) $state['character'][$abilityField]);
        $pb = $state['character']['level'] ? $this->proficiencyBonus((int) $state['character']['level']) : 0;
        $trainingBonus = match ($training) {
            'proficient' => $pb,
            'expertise' => $pb * 2,
            default => 0,
        };

        $result = $this->rollD20($modifier + $trainingBonus);
        $trainingLabel = match ($training) {
            'proficient' => 'with proficiency',
            'expertise' => 'with expertise',
            default => 'without added proficiency',
        };

        return $this->response(
            $state,
            sprintf(
                '%s check: %d (%d %s %d) %s.',
                $skillName,
                $result['total'],
                $result['roll'],
                ($modifier + $trainingBonus) >= 0 ? '+' : '-',
                abs($modifier + $trainingBonus),
                $trainingLabel,
            ),
            $this->defaultQuickActions($state),
        );
    }

    private function rollSavingThrow(array $state, string $abilityInput, ?string $mode): array
    {
        $abilityField = $this->normalizeAbilityField($abilityInput);

        if ($abilityField === null || $state['character'][$abilityField] === null) {
            return $this->response($state, 'I need a valid ability score on the active character before I can roll that save.', $this->defaultQuickActions($state));
        }

        $modifier = $this->abilityModifier((int) $state['character'][$abilityField]);
        if ($this->isSavingThrowProficient($state['character'], $abilityField) && $state['character']['level']) {
            $modifier += $this->proficiencyBonus((int) $state['character']['level']);
        }

        $result = $this->rollD20($modifier, $mode);

        return $this->response(
            $state,
            sprintf(
                '%s save%s: %d (%s).',
                self::FIELD_LABELS[$abilityField],
                $mode ? " with {$mode}" : '',
                $result['total'],
                $result['detail'],
            ),
            $this->defaultQuickActions($state),
        );
    }

    private function rollAbilityCheck(array $state, string $abilityInput, ?string $mode): array
    {
        $abilityField = $this->normalizeAbilityField($abilityInput);

        if ($abilityField === null || $state['character'][$abilityField] === null) {
            return $this->response($state, 'I need a valid ability score on the active character before I can roll that check.', $this->defaultQuickActions($state));
        }

        $modifier = $this->abilityModifier((int) $state['character'][$abilityField]);
        $result = $this->rollD20($modifier, $mode);

        return $this->response(
            $state,
            sprintf(
                '%s check%s: %d (%s).',
                self::FIELD_LABELS[$abilityField],
                $mode ? " with {$mode}" : '',
                $result['total'],
                $result['detail'],
            ),
            $this->defaultQuickActions($state),
        );
    }

    private function rollExpression(array $state, string $expression, ?string $mode): array
    {
        $parsed = $this->parseDiceExpression($expression);

        if ($parsed === null) {
            return $this->response(
                $state,
                'I could not parse that roll. Try `roll d20+5`, `roll 2d6+3`, or use `roll skill stealth`.',
                $this->defaultQuickActions($state),
            );
        }

        $result = $this->evaluateDiceExpression($parsed, $mode);

        return $this->response(
            $state,
            sprintf('Roll %s%s: %d (%s).', $expression, $mode ? " with {$mode}" : '', $result['total'], $result['detail']),
            $this->defaultQuickActions($state),
        );
    }

    private function rollDeathSave(array $state): array
    {
        if ((int) ($state['dungeon']['current_hp'] ?? 0) > 0) {
            return $this->response($state, 'Death saves only matter while the character is at 0 HP.', $this->defaultQuickActions($state));
        }

        $die = random_int(1, 20);

        if ($die === 1) {
            return $this->recordDeathSave($state, false, 2, "Rolled a natural 1 on the death save ({$die}).");
        }

        if ($die === 20) {
            $state['dungeon']['current_hp'] = 1;
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;

            return $this->response($state, 'Rolled a natural 20 on the death save. The character regains 1 HP.', $this->defaultQuickActions($state));
        }

        return $this->recordDeathSave(
            $state,
            $die >= 10,
            1,
            "Rolled {$die} on the death save.",
        );
    }

    private function recordDeathSave(array $state, bool $success, int $steps = 1, ?string $prefix = null): array
    {
        if ((int) ($state['dungeon']['current_hp'] ?? 0) > 0) {
            return $this->response($state, 'Death saves only matter while the character is at 0 HP.', $this->defaultQuickActions($state));
        }

        if ($success) {
            $state['dungeon']['death_successes'] = min(3, (int) $state['dungeon']['death_successes'] + $steps);
        } else {
            $state['dungeon']['death_failures'] = min(3, (int) $state['dungeon']['death_failures'] + $steps);
            $state['dungeon']['stable'] = false;
        }

        $lines = array_filter([$prefix]);
        $lines[] = sprintf(
            'Death saves: %d success, %d failure.',
            $state['dungeon']['death_successes'],
            $state['dungeon']['death_failures'],
        );

        if ((int) $state['dungeon']['death_successes'] >= 3) {
            $state['dungeon']['stable'] = true;
            $lines[] = 'The character is stable at 0 HP.';
        }

        if ((int) $state['dungeon']['death_failures'] >= 3) {
            $lines[] = 'The death save track has reached 3 failures.';
        }

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function showMonster(array $state, string $monsterName): array
    {
        $monsters = collect(config('dnd.compendium.monsters.items', []));
        $monster = $monsters
            ->first(function (array $entry) use ($monsterName): bool {
                return Str::lower($entry['name'] ?? '') === Str::lower($monsterName);
            });

        if (! is_array($monster)) {
            $match = $this->matchOption($monsterName, array_map(
                static fn (array $entry): string => $entry['name'],
                config('dnd.compendium.monsters.items', []),
            ));

            if ($match !== null) {
                return $this->showMonster($state, $match);
            }

            $suggestions = $monsters
                ->filter(static function (array $entry) use ($monsterName): bool {
                    return Str::contains(Str::lower($entry['name'] ?? ''), Str::lower($monsterName));
                })
                ->pluck('name')
                ->take(4)
                ->values()
                ->all();

            $message = "I could not find a monster named {$monsterName} in the local compendium.";
            if ($suggestions !== []) {
                $message .= ' Try: '.implode(', ', $suggestions).'.';
            }

            return $this->response($state, $message, $this->defaultQuickActions($state));
        }

        $lines = [
            $monster['name'],
            $monster['summary'] ?? '',
            sprintf(
                'AC %s | HP %s | Speed %s | CR %s',
                $monster['ac'] ?? '?',
                $monster['hp'] ?? '?',
                $monster['speed'] ?? '?',
                $monster['cr'] ?? '?',
            ),
        ];

        if (($monster['trait_names'] ?? []) !== []) {
            $lines[] = 'Traits: '.implode(', ', array_slice($monster['trait_names'], 0, 6));
        }

        if (($monster['action_names'] ?? []) !== []) {
            $lines[] = 'Actions: '.implode(', ', array_slice($monster['action_names'], 0, 6));
        }

        if (($monster['legendary_action_names'] ?? []) !== []) {
            $lines[] = 'Legendary Actions: '.implode(', ', array_slice($monster['legendary_action_names'], 0, 4));
        }

        return $this->response($state, implode("\n", array_filter($lines)), $this->defaultQuickActions($state));
    }

    private function showRoleplayHelp(array $state): array
    {
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                "Roleplay can stay simple. Start with four anchors: how the character comes across, what they believe, who or what they care about, and what usually gets them into trouble.\n\nStart with `new character`, then come back here once you have at least a class, background, or alignment.",
                ['new character', 'help'],
            );
        }

        $character = $state['character'];
        $lines = [
            'You do not need a novel. A beginner-safe roleplay core is just four short lines:',
            '- Personality Trait: how the character feels in the first five minutes',
            '- Ideal: the principle they try to live by',
            '- Bond: the person, place, or oath they care about',
            '- Flaw: the weakness that sometimes complicates good choices',
        ];

        if ($character['alignment']) {
            $lines[] = '';
            $lines[] = $this->alignmentGuidance((string) $character['alignment']);
        }

        if ($character['class']) {
            $lines[] = '';
            $lines[] = 'Class lens: '.$this->classGuidance((string) $character['class']);
        }

        if ($character['background']) {
            $lines[] = 'Background lens: '.$this->backgroundGuidance((string) $character['background']);
        }

        $starterPrompts = $this->roleplayStarterPrompts($character);
        if ($starterPrompts !== []) {
            $lines[] = '';
            $lines[] = 'Starter prompts:';
            foreach ($starterPrompts as $prompt) {
                $lines[] = '- '.$prompt;
            }
        }

        $lines[] = '';
        $lines[] = 'Use commands like `set personality traits ...`, `set ideals ...`, `set bonds ...`, or `set flaws ...` when one of those lines clicks.';

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function showAppearanceHelp(array $state): array
    {
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                "Appearance can stay light. Pick a few anchors like age, height, eyes, hair, and one memorable feature. Start with `new character`, and I can help once the build exists.",
                ['new character', 'help'],
            );
        }

        $lines = [
            'You do not need a full portrait. Three to six appearance anchors are enough for most tables:',
        ];

        foreach (['age', 'height', 'weight', 'eyes', 'hair', 'skin'] as $field) {
            $current = $state['character'][$field] ?? null;
            $label = self::FIELD_LABELS[$field];
            $help = config("dnd.appearance_field_help.{$field}", '');
            $lines[] = sprintf(
                '- %s: %s',
                $label,
                $current ? "{$current} (already set)" : $help,
            );
        }

        $cues = $this->appearanceCueLines($state['character']);
        if ($cues !== []) {
            $lines[] = '';
            $lines[] = 'Ability-based look cues:';
            foreach ($cues as $cue) {
                $lines[] = '- '.$cue;
            }
        }

        $lines[] = '';
        $lines[] = 'Use commands like `set age 23`, `set height 173 cm`, `set eyes gray`, or `set hair black braid`.';

        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    private function skipPendingField(array $state): array
    {
        $field = $state['pending_field'];

        if (! is_string($field) || $field === '') {
            return $this->response($state, 'There is nothing waiting for input right now.', $this->defaultQuickActions($state));
        }

        if (! $this->isOptionalField($field)) {
            return $this->response(
                $state,
                self::FIELD_LABELS[$field].' is part of the core build, so it cannot be skipped.',
                $this->quickActionsForField($field, $state),
            );
        }

        $skipped = $state['skipped_optional_fields'] ?? [];
        if (! in_array($field, $skipped, true)) {
            $skipped[] = $field;
        }

        $state['skipped_optional_fields'] = array_values(array_unique($skipped));
        $nextField = $this->nextGuidedField($state);
        $state['pending_field'] = $nextField;

        if ($nextField !== null) {
            return $this->response(
                $state,
                self::FIELD_LABELS[$field]." skipped for now.\n\n".$this->guidedFieldHeading($nextField)."\n".$this->fieldPrompt($nextField, $state).$this->skipHintForField($nextField),
                $this->quickActionsForField($nextField, $state),
            );
        }

        return $this->response(
            $state,
            self::FIELD_LABELS[$field].' skipped. All optional details are now skipped, so the draft is ready for review or saving.',
            $this->defaultQuickActions($state),
        );
    }

    private function finishOptionalGuidance(array $state): array
    {
        if ($this->missingFields($state['character']) !== []) {
            return $this->response(
                $state,
                'The core build is not finished yet, so optional details cannot be closed out. Finish the required fields first.',
                $this->defaultQuickActions($state),
            );
        }

        $state['pending_field'] = null;
        $state['skipped_optional_fields'] = self::OPTIONAL_FIELDS;

        return $this->response(
            $state,
            'Optional details closed for now. You can still set any field later with commands like `set alignment ...`, `set notes ...`, or `set eyes ...`.',
            $this->defaultQuickActions($state),
        );
    }

    private function saveCharacter(array $state): array
    {
        $missing = $this->missingFields($state['character']);

        if ($missing !== []) {
            return $this->response(
                $state,
                'The draft is not ready to save yet. Missing: '.implode(', ', array_map(fn (string $field): string => self::FIELD_LABELS[$field], $missing)),
                $this->defaultQuickActions($state),
            );
        }

        $character = Character::create([
            'name' => $state['character']['name'],
            'species' => $state['character']['species'],
            'class' => $state['character']['class'],
            'subclass' => $state['character']['subclass'],
            'background' => $state['character']['background'],
            'alignment' => $state['character']['alignment'],
            'origin_feat' => $state['character']['origin_feat'],
            'languages' => $state['character']['languages'],
            'personality_traits' => $state['character']['personality_traits'],
            'ideals' => $state['character']['ideals'],
            'bonds' => $state['character']['bonds'],
            'flaws' => $state['character']['flaws'],
            'age' => $state['character']['age'],
            'height' => $state['character']['height'],
            'weight' => $state['character']['weight'],
            'eyes' => $state['character']['eyes'],
            'hair' => $state['character']['hair'],
            'skin' => $state['character']['skin'],
            'level' => $state['character']['level'],
            'strength' => $state['character']['strength'],
            'dexterity' => $state['character']['dexterity'],
            'constitution' => $state['character']['constitution'],
            'intelligence' => $state['character']['intelligence'],
            'wisdom' => $state['character']['wisdom'],
            'charisma' => $state['character']['charisma'],
            'notes' => $state['character']['notes'],
        ]);

        $state['character'] = $this->characterToState($character);

        return $this->response(
            $state,
            "Saved {$character->name} to the roster. Use `load latest` any time to jump back to this sheet.",
            ['show summary', 'what did I gain', 'load latest', 'new character'],
        );
    }

    private function describeLevelGains(array $character, int $targetLevel, bool $preview = false): string
    {
        $entry = $this->levelEntry((string) $character['class'], $targetLevel);
        $previous = $targetLevel > 1 ? $this->levelEntry((string) $character['class'], $targetLevel - 1) : null;

        if ($entry === null) {
            return 'I could not find progression data for that class level.';
        }

        $lines = [];
        $heading = $preview ? "Preview for level {$targetLevel}" : "Level {$targetLevel} gains";
        $lines[] = $heading.':';

        $features = array_map(fn (string $feature): string => $this->displayFeature($feature, $character), $entry['features'] ?? []);
        if ($features !== []) {
            $lines[] = '- Features: '.implode(', ', $features);
        }

        if ($previous === null || ($entry['proficiency_bonus'] ?? null) !== ($previous['proficiency_bonus'] ?? null)) {
            $lines[] = '- Proficiency Bonus: +'.$entry['proficiency_bonus'];
        }

        foreach ($this->resourceChanges($entry, $previous) as $change) {
            $lines[] = "- {$change}";
        }

        $slotChange = $this->slotChange($character, $entry, $previous);
        if ($slotChange !== null) {
            $lines[] = "- {$slotChange}";
        }

        if (count($lines) === 1) {
            $lines[] = '- No new tracked changes were found in the local progression data.';
        }

        return implode("\n", $lines);
    }

    private function response(array $state, string $reply, array $quickActions): array
    {
        $state['dungeon'] = $this->syncDungeonState(
            $state['character'],
            $state['dungeon'] ?? $this->blankDungeon(),
        );

        return [
            'reply' => $reply,
            'state' => $state,
            'quick_actions' => array_values(array_unique(array_filter($quickActions))),
            'snapshot' => $this->buildSnapshot($state),
        ];
    }

    private function buildSnapshot(array $state): array
    {
        $character = $state['character'];
        $dungeon = $state['dungeon'];
        $stats = [];
        foreach (self::STAT_FIELDS as $field) {
            $score = $character[$field];
            $stats[] = [
                'label' => strtoupper(substr($field, 0, 3)),
                'score' => $score,
                'modifier' => $score === null ? null : $this->formatModifier($this->abilityModifier((int) $score)),
            ];
        }

        $identityParts = array_filter([
            $character['name'] ?: 'Unnamed hero',
            $character['species'],
            $character['class'],
            $character['subclass'],
            $character['background'],
            $character['level'] ? 'Level '.$character['level'] : null,
        ]);

        return [
            'identity' => implode(' / ', $identityParts),
            'missing_fields' => array_map(fn (string $field): string => self::FIELD_LABELS[$field], $this->missingFields($character)),
            'proficiency_bonus' => $character['class'] && $character['level'] ? '+'.$this->proficiencyBonus((int) $character['level']) : null,
            'estimated_hit_points' => $this->estimatedHitPoints($character),
            'character_details' => array_values(array_filter([
                $character['alignment'] ?? null,
                $character['origin_feat'] ?? null,
            ])),
            'languages' => is_array($character['languages'] ?? null) ? $character['languages'] : [],
            'roleplay' => array_values(array_filter([
                $character['personality_traits'] ? 'Trait: '.$character['personality_traits'] : null,
                $character['ideals'] ? 'Ideal: '.$character['ideals'] : null,
                $character['bonds'] ? 'Bond: '.$character['bonds'] : null,
                $character['flaws'] ? 'Flaw: '.$character['flaws'] : null,
            ])),
            'appearance' => array_values(array_filter([
                $character['age'] ? 'Age: '.$character['age'] : null,
                $character['height'] ? 'Height: '.$character['height'] : null,
                $character['weight'] ? 'Weight: '.$character['weight'] : null,
                $character['eyes'] ? 'Eyes: '.$character['eyes'] : null,
                $character['hair'] ? 'Hair: '.$character['hair'] : null,
                $character['skin'] ? 'Skin: '.$character['skin'] : null,
            ])),
            'notes' => $character['notes'] !== '' ? $character['notes'] : null,
            'stats' => $stats,
            'current_features' => $this->currentFeatures($character),
            'next_gains' => $this->nextGains($character),
            'spellcasting_summary' => $this->spellcastingSummary($character),
            'dungeon_status' => $this->dungeonStatusLine($dungeon),
            'conditions' => $dungeon['conditions'],
            'concentration' => $dungeon['concentration'],
            'resources' => $this->resourceSnapshot($state),
            'death_track' => $this->deathTrackLine($dungeon),
        ];
    }

    private function dungeonStatusLine(array $dungeon): ?string
    {
        if ($dungeon['current_hp'] === null || $dungeon['max_hp'] === null) {
            return null;
        }

        $parts = [
            sprintf('HP %d/%d', $dungeon['current_hp'], $dungeon['max_hp']),
            sprintf('Temp HP %d', $dungeon['temp_hp']),
        ];

        if ($dungeon['ac'] !== null) {
            $parts[] = 'AC '.$dungeon['ac'];
        }

        if ($dungeon['exhaustion'] > 0) {
            $parts[] = 'Exhaustion '.$dungeon['exhaustion'];
        }

        if ($dungeon['last_initiative'] !== null) {
            $parts[] = 'Last Initiative '.$dungeon['last_initiative'];
        }

        return implode(' | ', $parts);
    }

    private function resourceSnapshot(array $state): array
    {
        $dungeon = $state['dungeon'];
        $resources = [];

        if ($dungeon['hit_dice_remaining'] !== null && $state['character']['level']) {
            $resources[] = sprintf('Hit Dice %d/%d', $dungeon['hit_dice_remaining'], $state['character']['level']);
        }

        $maximumSlots = $this->maxSpellSlotsForCharacter($state['character']);
        foreach ($maximumSlots as $level => $count) {
            $resources[] = sprintf(
                '%s slots %d/%d',
                $this->spellLevelLabel((int) $level),
                (int) ($dungeon['spell_slots_remaining'][(string) $level] ?? $dungeon['spell_slots_remaining'][$level] ?? 0),
                $count,
            );
        }

        return $resources;
    }

    private function deathTrackLine(array $dungeon): ?string
    {
        if ((int) ($dungeon['current_hp'] ?? 1) > 0 && ! $dungeon['stable']) {
            return null;
        }

        $line = sprintf(
            'Death saves %d success / %d failure',
            (int) $dungeon['death_successes'],
            (int) $dungeon['death_failures'],
        );

        if ($dungeon['stable']) {
            $line .= ' | Stable';
        }

        return $line;
    }

    private function currentFeatures(array $character): array
    {
        if (! $character['class'] || ! $character['level']) {
            return [];
        }

        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);

        if ($entry === null) {
            return [];
        }

        return array_map(fn (string $feature): string => $this->displayFeature($feature, $character), $entry['features'] ?? []);
    }

    private function nextGains(array $character): array
    {
        if (! $character['class'] || ! $character['level'] || (int) $character['level'] >= 20) {
            return [];
        }

        $next = $this->levelEntry((string) $character['class'], (int) $character['level'] + 1);
        if ($next === null) {
            return [];
        }

        return array_map(fn (string $feature): string => $this->displayFeature($feature, $character), $next['features'] ?? []);
    }

    private function spellcastingSummary(array $character): ?string
    {
        if (! $character['class'] || ! $character['level']) {
            return null;
        }

        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);

        if ($entry === null) {
            return null;
        }

        if (($entry['spell_slots'] ?? []) !== []) {
            $pairs = [];
            foreach ($entry['spell_slots'] as $level => $slots) {
                $pairs[] = $this->spellLevelLabel((int) $level).' '.$slots;
            }

            $preparedSpells = $entry['resources']['prepared_spells'] ?? null;
            $cantrips = $entry['resources']['cantrips'] ?? null;
            $prefix = [];
            if ($cantrips !== null) {
                $prefix[] = "Cantrips {$cantrips}";
            }
            if ($preparedSpells !== null) {
                $prefix[] = "Prepared {$preparedSpells}";
            }

            $parts = [];
            if ($prefix !== []) {
                $parts[] = implode(' / ', $prefix);
            }
            $parts[] = 'Slots: '.implode(', ', $pairs);

            return implode(' / ', $parts);
        }

        if (($entry['resources']['spell_slots'] ?? null) !== null && ($entry['resources']['slot_level'] ?? null) !== null) {
            return sprintf(
                'Pact Magic: %d slots at %s level',
                $entry['resources']['spell_slots'],
                $this->spellLevelLabel((int) $entry['resources']['slot_level']),
            );
        }

        if ($this->spellClassTag($character) === 'Wizard' && in_array($character['subclass'], ['Arcane Trickster', 'Eldritch Knight'], true)) {
            $maxLevel = $this->maxSpellLevel($character);

            if ($maxLevel > 0) {
                return 'Third-caster access through '.$this->spellLevelLabel($maxLevel).' spells.';
            }
        }

        return null;
    }

    private function slotChange(array $character, array $entry, ?array $previous): ?string
    {
        $current = $this->spellcastingSummaryFromEntry($character, $entry);
        $before = $previous ? $this->spellcastingSummaryFromEntry($character, $previous) : null;

        if ($current === null || $current === $before) {
            return null;
        }

        return $current;
    }

    private function spellcastingSummaryFromEntry(array $character, array $entry): ?string
    {
        if (($entry['spell_slots'] ?? []) !== []) {
            $pairs = [];
            foreach ($entry['spell_slots'] as $level => $slots) {
                $pairs[] = $this->spellLevelLabel((int) $level).' '.$slots;
            }

            return 'Spell Slots: '.implode(', ', $pairs);
        }

        if (($entry['resources']['spell_slots'] ?? null) !== null && ($entry['resources']['slot_level'] ?? null) !== null) {
            return sprintf(
                'Pact Magic: %d slots at %s level',
                $entry['resources']['spell_slots'],
                $this->spellLevelLabel((int) $entry['resources']['slot_level']),
            );
        }

        if ($this->spellClassTag($character) === 'Wizard' && in_array($character['subclass'], ['Arcane Trickster', 'Eldritch Knight'], true)) {
            $maxLevel = self::THIRD_CASTER_MAX_SPELL_LEVEL[(int) ($character['level'] ?? 0)] ?? 0;

            return $maxLevel > 0 ? 'Third-caster access: '.$this->spellLevelLabel($maxLevel) : null;
        }

        return null;
    }

    private function resourceChanges(array $entry, ?array $previous): array
    {
        $changes = [];
        $currentResources = $entry['resources'] ?? [];
        $previousResources = $previous['resources'] ?? [];

        foreach ($currentResources as $key => $value) {
            $before = $previousResources[$key] ?? null;

            if ($previous === null || $before !== $value) {
                $changes[] = sprintf('%s: %s', $this->humanizeKey($key), $value);
            }
        }

        return $changes;
    }

    private function humanizeKey(string $key): string
    {
        return Str::of($key)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function askForField(array $state, string $field): array
    {
        return $this->response($state, $this->fieldPrompt($field, $state), $this->quickActionsForField($field, $state));
    }

    private function guidedFieldHeading(string $field): string
    {
        return match ($field) {
            'class', 'level', 'subclass' => 'Step 1: Choose a Class',
            'background', 'species', 'origin_feat', 'languages' => 'Step 2: Determine Origin',
            'strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma' => 'Step 3: Determine Ability Scores',
            'alignment' => 'Step 4: Choose an Alignment',
            'notes' => 'Step 5: Fill in Details - Final Optional Piece',
            default => 'Step 5: Fill in Details',
        };
    }

    private function skipHintForField(string $field): string
    {
        if (! $this->isOptionalField($field)) {
            return '';
        }

        if ($field === 'notes') {
            return "\nType `skip` to finish without notes, or `skip all details` to close out the remaining optional details now.";
        }

        return "\nType `skip` to leave this blank or `skip all details` to finish the draft now.";
    }

    private function fieldPrompt(string $field, array $state): string
    {
        $name = $state['character']['name'] ?: 'your character';

        return match ($field) {
            'name' => "What name goes on the sheet for {$name}?\nThis is part of the final detail pass, so a simple working name is completely fine.",
            'species' => "Choose a species for {$name}.\nThis is still the origin step. Each option bubble includes a short summary so you can compare them there.",
            'class' => "Choose a class for {$name}.\nThis is the first handbook step. Each option bubble includes the class summary and main ability focus.",
            'subclass' => "Choose a subclass for {$name}.\nThis finishes the class step by locking in the specialization that shapes later features.",
            'background' => "Choose a background for {$name}.\nThis begins the origin step and helps explain who the character was before adventuring.",
            'level' => "What level are we building? Enter a number from 1 to 20.\nThis is part of the class step. Level 1 is the easiest place to learn. Level 3 is where many classes feel more complete.",
            'alignment' => "Choose an alignment for {$name}.\nThis is the handbook's fourth step. Each option bubble includes the short alignment summary, and I will help with the roleplay side afterward.",
            'origin_feat' => "Choose an origin feat for {$name}.\nThis is still part of origin and counts as core sheet data. Each option bubble includes what the feat broadly does.",
            'languages' => "Choose one or more languages for {$name}.\nThis closes out origin and counts as core sheet data. Languages mostly matter for conversations, travel, and lore.",
            'personality_traits' => "Add one short personality line for {$name}.\n".config('dnd.roleplay_field_help.personality_traits'),
            'ideals' => "Add an ideal for {$name}.\n".config('dnd.roleplay_field_help.ideals'),
            'bonds' => "Add a bond for {$name}.\n".config('dnd.roleplay_field_help.bonds'),
            'flaws' => "Add a flaw for {$name}.\n".config('dnd.roleplay_field_help.flaws'),
            'age', 'height', 'weight', 'eyes', 'hair', 'skin' => "Add ".Str::lower(self::FIELD_LABELS[$field])." for {$name}.\n".(config("dnd.appearance_field_help.{$field}") ?: 'A short descriptive note is enough.'),
            'notes' => "Add notes for {$name}.\nThis is the last optional wizard step. Use it for campaign reminders, secrets, goals, gear notes, or anything else you want the sheet to remember.",
            default => 'Set '.self::FIELD_LABELS[$field]." from 3 to 18, or type `roll stats` to fill all six scores at once.\nThis is part of the handbook ability-score step.\n".$this->abilityPromptHelp($field),
        };
    }

    private function speciesGuidance(string $species): string
    {
        return config("dnd.species_details.{$species}.summary") ?: 'Species mostly affects flavor, movement, and innate traits.';
    }

    private function classGuidance(string $class): string
    {
        $summary = config("dnd.class_details.{$class}.summary") ?: 'Class drives your main features and playstyle.';
        $focus = config("dnd.class_details.{$class}.primary_focus", []);

        if (is_array($focus) && $focus !== []) {
            $summary .= ' Main abilities often include '.implode(' and ', $focus).'.';
        }

        return $summary;
    }

    private function backgroundGuidance(string $background): string
    {
        return config("dnd.background_details.{$background}.summary") ?: 'Background mainly supports story identity and life before adventuring.';
    }

    private function alignmentGuidance(string $alignment): string
    {
        $summary = config("dnd.alignment_details.{$alignment}") ?: 'Alignment is a roleplay cue, not a hard rule.';
        $playWell = config("dnd.alignment_roleplay.{$alignment}.play_well");
        $watchOut = config("dnd.alignment_roleplay.{$alignment}.watch_out");

        $lines = [$summary];

        if (is_string($playWell) && $playWell !== '') {
            $lines[] = 'Play it well: '.$playWell;
        }

        if (is_string($watchOut) && $watchOut !== '') {
            $lines[] = 'Watch out for: '.$watchOut;
        }

        return implode("\n", $lines);
    }

    private function roleplayFieldGuidance(string $field, array $character): string
    {
        $help = config("dnd.roleplay_field_help.{$field}") ?: 'A short line is enough.';
        $starterPrompts = $this->roleplayStarterPrompts($character);

        if ($field === 'personality_traits' && isset($starterPrompts[0])) {
            return $help.' Example: '.$starterPrompts[0];
        }

        if ($field === 'ideals' && isset($starterPrompts[1])) {
            return $help.' Example: '.$starterPrompts[1];
        }

        if ($field === 'bonds' && isset($starterPrompts[2])) {
            return $help.' Example: '.$starterPrompts[2];
        }

        if ($field === 'flaws' && isset($starterPrompts[3])) {
            return $help.' Example: '.$starterPrompts[3];
        }

        return $help;
    }

    private function appearanceFieldGuidance(string $field, array $character): string
    {
        $help = config("dnd.appearance_field_help.{$field}") ?: 'A short descriptive note is enough.';
        $cues = $this->appearanceCueLines($character);

        return $cues !== []
            ? $help.' Cue: '.$cues[0]
            : $help;
    }

    private function roleplayStarterPrompts(array $character): array
    {
        $alignment = $character['alignment'] ?? null;

        if (! is_string($alignment) || $alignment === '') {
            return [];
        }

        $prompts = [
            config("dnd.alignment_roleplay.{$alignment}.starter_trait"),
            config("dnd.alignment_roleplay.{$alignment}.starter_ideal"),
            config("dnd.alignment_roleplay.{$alignment}.starter_bond"),
            config("dnd.alignment_roleplay.{$alignment}.starter_flaw"),
        ];

        return array_values(array_filter(array_map(
            static fn ($entry): ?string => is_string($entry) && $entry !== '' ? $entry : null,
            $prompts,
        )));
    }

    private function appearanceCueLines(array $character): array
    {
        $scores = [];

        foreach (self::STAT_FIELDS as $field) {
            if ($character[$field] !== null) {
                $scores[$field] = (int) $character[$field];
            }
        }

        if ($scores === []) {
            return [];
        }

        arsort($scores);
        $highestField = array_key_first($scores);
        asort($scores);
        $lowestField = array_key_first($scores);

        $lines = [];

        if ($highestField !== null) {
            $highestLabel = self::FIELD_LABELS[$highestField];
            $highWords = config("dnd.ability_appearance_cues.{$highestLabel}.high", []);
            if (is_array($highWords) && $highWords !== []) {
                $lines[] = "{$highestLabel} leans high, so cues like ".implode(', ', $highWords).' fit naturally.';
            }
        }

        if ($lowestField !== null && $lowestField !== $highestField) {
            $lowestLabel = self::FIELD_LABELS[$lowestField];
            $lowWords = config("dnd.ability_appearance_cues.{$lowestLabel}.low", []);
            if (is_array($lowWords) && $lowWords !== []) {
                $lines[] = "{$lowestLabel} is the softer edge, so cues like ".implode(', ', $lowWords).' can add texture.';
            }
        }

        return $lines;
    }

    private function abilityPromptHelp(string $field): string
    {
        $label = self::FIELD_LABELS[$field] ?? Str::title($field);
        $summary = config("dnd.ability_details.{$label}");

        return is_string($summary) && $summary !== ''
            ? $summary
            : 'Higher scores make related checks, attacks, or spellcasting better.';
    }

    private function abilityGuidance(string $field, int $score, array $character): string
    {
        $modifier = $this->formatModifier($this->abilityModifier($score));
        $guidance = $this->abilityPromptHelp($field);
        $focus = is_string($character['class'] ?? null)
            ? config("dnd.class_details.{$character['class']}.primary_focus", [])
            : [];
        $isFocus = is_array($focus) && in_array(self::FIELD_LABELS[$field], $focus, true);

        return trim($guidance.' Modifier '.$modifier.'.'.($isFocus ? ' This is one of the main abilities for the chosen class.' : ''));
    }

    private function quickActionsForField(string $field, array $state): array
    {
        $actions = match ($field) {
            'species' => config('dnd.species', []),
            'class' => config('dnd.classes', []),
            'subclass' => config("dnd.class_details.{$state['character']['class']}.subclasses", []),
            'background' => config('dnd.backgrounds', []),
            'alignment' => config('dnd.alignments', []),
            'origin_feat' => config('dnd.origin_feats', []),
            'languages' => ['Common, Elvish', 'Common, Dwarvish', 'Common, Draconic'],
            'personality_traits', 'ideals', 'bonds', 'flaws' => ['help me roleplay'],
            'age', 'height', 'weight', 'eyes', 'hair', 'skin' => ['show appearance help'],
            'notes' => [],
            'level' => ['1', '3', '5', '10'],
            default => in_array($field, self::STAT_FIELDS, true) ? ['roll stats'] : [],
        };

        if ($this->isOptionalField($field)) {
            array_unshift($actions, 'skip');
            $actions[] = 'skip all details';
        }

        return array_values(array_unique(array_filter($actions)));
    }

    private function defaultQuickActions(array $state): array
    {
        if ($state['pending_field'] !== null) {
            return $this->quickActionsForField($state['pending_field'], $state);
        }

        if ($this->hasCharacterData($state['character'])) {
            return [
                'show summary',
                'show status',
                'what did I gain',
                'show next',
                'level up',
                'show spells',
                'roll initiative',
                'short rest',
                'long rest',
                'help me roleplay',
                'show appearance help',
                'save character',
            ];
        }

        return ['new character', 'list characters', 'load latest', 'help'];
    }

    private function helpMessage(): string
    {
        return implode("\n", [
            'Wizard commands:',
            '- You do not need to memorize everything. Use the quick buttons when they appear.',
            '- new character follows the handbook flow: class, origin, ability scores, alignment, then details',
            '- core sheet mechanics cannot be skipped; roleplay, appearance, and notes can',
            '- new character',
            '- list characters',
            '- load character 1',
            '- load latest',
            '- set class wizard',
            '- set level 5',
            '- roll stats',
            '- show summary',
            '- show status',
            '- what did I gain',
            '- show next',
            '- level up',
            '- show spells',
            '- show slots',
            '- help me roleplay / show appearance help',
            '- skip / skip all details while the wizard is guiding optional fields',
            '- set hp 34 / damage 7 / heal 5 / temp hp 8 / set ac 16',
            '- apply condition poisoned / remove condition poisoned / set exhaustion 2',
            '- roll initiative / roll skill stealth proficient / roll save dex advantage / roll d20+5',
            '- roll death save / death save success / death save failure',
            '- short rest 2 / long rest / use slot 3 / cast fireball',
            '- concentrate bless / drop concentration',
            '- show monster goblin',
            '- core origin details: set origin feat alert / set languages common, elvish',
            '- optional roleplay setup: set alignment lawful good',
            '- optional roleplay details: set personality traits curious but blunt / set ideals freedom / set bonds my sister / set flaws reckless',
            '- optional appearance details: set age 23 / set height 173 cm / set weight 72 kg / set eyes gray / set hair black braid / set skin olive with freckles',
            '- optional notes: set notes owes the thieves guild a favor',
            '- save character',
        ]);
    }

    private function matchOption(string $input, array $options): ?string
    {
        $input = Str::of($input)->lower()->squish()->toString();

        foreach ($options as $option) {
            if (Str::of((string) $option)->lower()->toString() === $input) {
                return (string) $option;
            }
        }

        $prefixMatches = array_values(array_filter($options, static function (string $option) use ($input): bool {
            return Str::startsWith(Str::of($option)->lower()->toString(), $input);
        }));

        if (count($prefixMatches) === 1) {
            return $prefixMatches[0];
        }

        $containsMatches = array_values(array_filter($options, static function (string $option) use ($input): bool {
            return Str::contains(Str::of($option)->lower()->toString(), $input);
        }));

        return count($containsMatches) === 1 ? $containsMatches[0] : null;
    }

    private function missingFields(array $character): array
    {
        $missing = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            if (! $this->fieldHasValue($character[$field] ?? null)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function nextGuidedField(array $state): ?string
    {
        $skippedOptionalFields = $state['skipped_optional_fields'] ?? [];

        foreach (self::GUIDED_FIELDS as $field) {
            if ($this->isOptionalField($field) && in_array($field, $skippedOptionalFields, true)) {
                continue;
            }

            if (! $this->fieldHasValue($state['character'][$field] ?? null)) {
                return $field;
            }
        }

        return null;
    }
    private function fieldHasValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }

    private function isOptionalField(string $field): bool
    {
        return in_array($field, self::OPTIONAL_FIELDS, true);
    }

    private function normalizeState(array $state): array
    {
        $character = $this->blankCharacter();

        foreach ($character as $key => $value) {
            if (array_key_exists($key, $state['character'] ?? [])) {
                $character[$key] = $state['character'][$key];
            }
        }

        $dungeon = $this->blankDungeon();
        foreach ($dungeon as $key => $value) {
            if (array_key_exists($key, $state['dungeon'] ?? [])) {
                $dungeon[$key] = $state['dungeon'][$key];
            }
        }

        $dungeon = $this->syncDungeonState($character, $dungeon);

        return [
            'pending_field' => isset($state['pending_field']) && is_string($state['pending_field']) ? $state['pending_field'] : null,
            'skipped_optional_fields' => array_values(array_filter(
                is_array($state['skipped_optional_fields'] ?? null) ? $state['skipped_optional_fields'] : [],
                static fn ($value): bool => is_string($value) && in_array($value, self::OPTIONAL_FIELDS, true),
            )),
            'character' => $character,
            'dungeon' => $dungeon,
        ];
    }

    private function blankCharacter(): array
    {
        return [
            'id' => null,
            'name' => null,
            'species' => null,
            'class' => null,
            'subclass' => null,
            'background' => null,
            'alignment' => null,
            'origin_feat' => null,
            'languages' => null,
            'personality_traits' => null,
            'ideals' => null,
            'bonds' => null,
            'flaws' => null,
            'age' => null,
            'height' => null,
            'weight' => null,
            'eyes' => null,
            'hair' => null,
            'skin' => null,
            'level' => null,
            'strength' => null,
            'dexterity' => null,
            'constitution' => null,
            'intelligence' => null,
            'wisdom' => null,
            'charisma' => null,
            'notes' => '',
        ];
    }

    private function blankDungeon(): array
    {
        return [
            'max_hp' => null,
            'current_hp' => null,
            'temp_hp' => 0,
            'ac' => null,
            'conditions' => [],
            'exhaustion' => 0,
            'initiative_bonus' => null,
            'last_initiative' => null,
            'death_successes' => 0,
            'death_failures' => 0,
            'stable' => false,
            'concentration' => null,
            'spell_slots_remaining' => [],
            'hit_dice_remaining' => null,
        ];
    }

    private function syncDungeonState(array $character, array $dungeon): array
    {
        $estimatedHp = $this->estimatedHitPoints($character);
        $oldMaxHp = $dungeon['max_hp'];

        if ($estimatedHp !== null) {
            $dungeon['max_hp'] = $estimatedHp;

            if ($dungeon['current_hp'] === null || $oldMaxHp === null || (int) $dungeon['current_hp'] === (int) $oldMaxHp) {
                $dungeon['current_hp'] = $estimatedHp;
            } else {
                $dungeon['current_hp'] = min((int) $dungeon['current_hp'], $estimatedHp);
            }
        }

        if ($character['dexterity'] !== null) {
            $dexModifier = $this->abilityModifier((int) $character['dexterity']);
            if ($dungeon['ac'] === null) {
                $dungeon['ac'] = 10 + $dexModifier;
            }
            $dungeon['initiative_bonus'] = $dexModifier;
        }

        if ($character['level'] !== null) {
            $dungeon['hit_dice_remaining'] = $dungeon['hit_dice_remaining'] === null
                ? (int) $character['level']
                : min((int) $dungeon['hit_dice_remaining'], (int) $character['level']);
        }

        $maximumSlots = $this->maxSpellSlotsForCharacter($character);
        if ($maximumSlots === []) {
            $dungeon['spell_slots_remaining'] = [];
        } else {
            $remaining = [];
            foreach ($maximumSlots as $level => $count) {
                $previous = $dungeon['spell_slots_remaining'][(string) $level] ?? $dungeon['spell_slots_remaining'][$level] ?? $count;
                $remaining[(string) $level] = min((int) $previous, $count);
            }
            $dungeon['spell_slots_remaining'] = $remaining;
        }

        $dungeon['conditions'] = array_values(array_unique(array_filter(
            is_array($dungeon['conditions']) ? $dungeon['conditions'] : [],
            static fn ($value): bool => is_string($value) && $value !== '',
        )));

        $dungeon['exhaustion'] = max(0, min(6, (int) $dungeon['exhaustion']));
        $dungeon['temp_hp'] = max(0, (int) $dungeon['temp_hp']);
        $dungeon['death_successes'] = max(0, min(3, (int) $dungeon['death_successes']));
        $dungeon['death_failures'] = max(0, min(3, (int) $dungeon['death_failures']));
        $dungeon['stable'] = (bool) $dungeon['stable'];
        $dungeon['concentration'] = is_string($dungeon['concentration']) && $dungeon['concentration'] !== '' ? $dungeon['concentration'] : null;

        if ((int) ($dungeon['current_hp'] ?? 0) > 0) {
            $dungeon['death_successes'] = 0;
            $dungeon['death_failures'] = 0;
            $dungeon['stable'] = false;
        }

        return $dungeon;
    }

    private function characterToState(Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->name,
            'species' => $character->species,
            'class' => $character->class,
            'subclass' => $character->subclass,
            'background' => $character->background,
            'alignment' => $character->alignment,
            'origin_feat' => $character->origin_feat,
            'languages' => $character->languages,
            'personality_traits' => $character->personality_traits,
            'ideals' => $character->ideals,
            'bonds' => $character->bonds,
            'flaws' => $character->flaws,
            'age' => $character->age,
            'height' => $character->height,
            'weight' => $character->weight,
            'eyes' => $character->eyes,
            'hair' => $character->hair,
            'skin' => $character->skin,
            'level' => $character->level,
            'strength' => $character->strength,
            'dexterity' => $character->dexterity,
            'constitution' => $character->constitution,
            'intelligence' => $character->intelligence,
            'wisdom' => $character->wisdom,
            'charisma' => $character->charisma,
            'notes' => $character->notes,
        ];
    }

    private function markAsDraft(array $character): array
    {
        $character['id'] = null;

        return $character;
    }

    private function hasCharacterData(array $character): bool
    {
        foreach ($character as $key => $value) {
            if ($key === 'id' || $key === 'notes') {
                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function normalizeAbilityField(string $input): ?string
    {
        $normalized = Str::of($input)->lower()->squish()->toString();

        return self::ABILITY_LOOKUP[$normalized] ?? null;
    }

    private function isSavingThrowProficient(array $character, string $abilityField): bool
    {
        $saves = config("dnd_progressions.classes.{$character['class']}.traits.saving_throw_proficiencies");

        if (! is_string($saves) || $saves === '') {
            return false;
        }

        $abilityLabel = self::FIELD_LABELS[$abilityField];

        return Str::contains($saves, $abilityLabel);
    }

    private function shortRestRecoveryPerDie(array $character): int
    {
        $hitDie = $this->hitDieForClass((string) ($character['class'] ?? '')) ?? 8;
        $conModifier = $character['constitution'] === null ? 0 : $this->abilityModifier((int) $character['constitution']);

        return ((int) floor($hitDie / 2)) + 1 + $conModifier;
    }

    private function maxSpellSlotsForCharacter(array $character): array
    {
        if (! $character['class'] || ! $character['level']) {
            return [];
        }

        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);
        if ($entry === null) {
            return [];
        }

        if (($entry['spell_slots'] ?? []) !== []) {
            return array_map('intval', $entry['spell_slots']);
        }

        if (($entry['resources']['spell_slots'] ?? null) !== null && ($entry['resources']['slot_level'] ?? null) !== null) {
            return [
                (int) $entry['resources']['slot_level'] => (int) $entry['resources']['spell_slots'],
            ];
        }

        return [];
    }

    private function lowestAvailableSpellSlot(array $remainingSlots, int $minimumLevel): ?int
    {
        $available = [];
        foreach ($remainingSlots as $level => $count) {
            if ((int) $level >= $minimumLevel && (int) $count > 0) {
                $available[] = (int) $level;
            }
        }

        sort($available);

        return $available[0] ?? null;
    }

    private function rollD20(int $modifier, ?string $mode = null): array
    {
        $first = random_int(1, 20);
        $second = $mode ? random_int(1, 20) : null;
        $roll = match ($mode) {
            'advantage' => max($first, (int) $second),
            'disadvantage' => min($first, (int) $second),
            default => $first,
        };

        $detail = $mode
            ? sprintf('%d and %d -> %d %s %d', $first, $second, $roll, $modifier >= 0 ? '+' : '-', abs($modifier))
            : sprintf('%d %s %d', $roll, $modifier >= 0 ? '+' : '-', abs($modifier));

        return [
            'roll' => $roll,
            'total' => $roll + $modifier,
            'detail' => $detail,
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

    private function proficiencyBonus(int $level): int
    {
        return (int) floor(($level - 1) / 4) + 2;
    }

    private function abilityModifier(int $score): int
    {
        return (int) floor(($score - 10) / 2);
    }

    private function formatModifier(int $modifier): string
    {
        return $modifier >= 0 ? "+{$modifier}" : (string) $modifier;
    }

    private function estimatedHitPoints(array $character): ?int
    {
        if (! $character['class'] || ! $character['level'] || ! $character['constitution']) {
            return null;
        }

        $hitDie = $this->hitDieForClass((string) $character['class']);
        if ($hitDie === null) {
            return null;
        }

        $conModifier = $this->abilityModifier((int) $character['constitution']);
        $level = (int) $character['level'];
        $firstLevel = $hitDie + $conModifier;
        $laterLevels = max(0, $level - 1) * (((int) floor($hitDie / 2)) + 1 + $conModifier);

        return $firstLevel + $laterLevels;
    }

    private function hitDieForClass(string $class): ?int
    {
        $value = config("dnd_progressions.classes.{$class}.traits.hit_point_die");

        if (! is_string($value) || preg_match('/D(\d+)/i', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function levelEntry(string $class, int $level): ?array
    {
        $entry = config("dnd_progressions.classes.{$class}.levels.{$level}");

        return is_array($entry) ? $entry : null;
    }

    private function displayFeature(string $feature, array $character): string
    {
        if (str_contains($feature, 'Subclass feature')) {
            return $character['subclass'] ? "{$character['subclass']} feature" : 'Subclass feature';
        }

        if (str_contains($feature, 'Subclass')) {
            return $character['subclass']
                ? "Subclass choice: {$character['subclass']}"
                : $feature;
        }

        return $feature;
    }

    private function spellClassTag(array $character): ?string
    {
        $class = $character['class'];
        $subclass = $character['subclass'];

        if (! is_string($class) || $class === '') {
            return null;
        }

        if (in_array($class, ['Bard', 'Cleric', 'Druid', 'Paladin', 'Ranger', 'Sorcerer', 'Warlock', 'Wizard'], true)) {
            return $class;
        }

        if ($class === 'Fighter' && $subclass === 'Eldritch Knight') {
            return 'Wizard';
        }

        if ($class === 'Rogue' && $subclass === 'Arcane Trickster') {
            return 'Wizard';
        }

        return null;
    }

    private function maxSpellLevel(array $character): int
    {
        if (! $character['class'] || ! $character['level']) {
            return -1;
        }

        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);

        if ($entry === null) {
            return -1;
        }

        if (($entry['spell_slots'] ?? []) !== []) {
            return max(array_map('intval', array_keys($entry['spell_slots'])));
        }

        if (($entry['resources']['slot_level'] ?? null) !== null) {
            return (int) $entry['resources']['slot_level'];
        }

        if (in_array($character['subclass'], ['Arcane Trickster', 'Eldritch Knight'], true)) {
            return self::THIRD_CASTER_MAX_SPELL_LEVEL[(int) $character['level']] ?? 0;
        }

        return 0;
    }

    private function spellLevelLabel(int $level, bool $plural = false): string
    {
        if ($level === 0) {
            return $plural ? 'Cantrips' : 'Cantrip';
        }

        return match ($level) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => "{$level}th",
        };
    }

    private function rollAbilityScore(): int
    {
        $rolls = [
            random_int(1, 6),
            random_int(1, 6),
            random_int(1, 6),
            random_int(1, 6),
        ];

        sort($rolls);
        array_shift($rolls);

        return array_sum($rolls);
    }
}
