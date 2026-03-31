<?php
// Developer context: This service is the rules wizard's main engine; the API controller passes it a message and prior state, and it uses CharacterDataValidator, DiceRoller, RulesWizardStateSanitizer, config data, and Character records to decide the next response.
// Clear explanation: This file is the wizard's brain: it reads the current wizard state, understands the command, and returns the next wizard message and updated state.

namespace App\Services;

use App\Models\Character;
use App\Support\CharacterDataValidator;
use App\Support\DiceRoller;
use App\Support\OfficialRulesWarningService;
use App\Support\RulesWizardStateSanitizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RulesWizardService
{
    // Developer context: Laravel injects the validator, dice helper, and state sanitizer here so wizard logic can reuse the same validation, rolling, and cleanup rules used elsewhere in the app.
    // Clear explanation: This sets up the helpers the wizard needs for validation, dice rolling, and safe state cleanup.
    public function __construct(
        private readonly CharacterDataValidator $characterDataValidator,
        private readonly DiceRoller $diceRoller,
        private readonly OfficialRulesWarningService $officialRulesWarningService,
        private readonly RulesWizardStateSanitizer $wizardStateSanitizer,
    ) {}

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
        'skill_proficiencies',
        'background',
        'origin_feat',
        'advancement_method',
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
        'advancement_method',
        'subclass',
        'skill_proficiencies',
        'skill_expertise',
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
        'goals',
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
        'skill_expertise',
        'alignment',
        'personality_traits',
        'ideals',
        'goals',
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

    private const RANDOMIZABLE_STEP_FIVE_FIELDS = [
        'name',
        'personality_traits',
        'ideals',
        'goals',
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
        'skill_proficiencies' => 'Skill Proficiencies',
        'skill_expertise' => 'Skill Expertise',
        'background' => 'Background',
        'alignment' => 'Alignment',
        'origin_feat' => 'Origin Feat',
        'advancement_method' => 'Advancement Method',
        'languages' => 'Languages',
        'personality_traits' => 'Personality Traits',
        'ideals' => 'Ideals',
        'goals' => 'Goals',
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

    // Developer context: This is the main wizard entry point; it receives the latest user message plus stored wizard state, normalizes both, then routes the command into the correct branch or helper.
    // Clear explanation: This method reads what the user typed, checks the current wizard state, and decides what the wizard should say or do next.
    public function handle(?string $message, array $state = []): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state = $this->normalizeState($state);
        $message = trim((string) $message);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $command = Str::of($message)->lower()->squish()->toString();

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($message === '') {
            return $this->handleEmptyMessage($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'help' || $command === 'commands') {
            return $this->response($state, $this->helpMessage(), $this->defaultQuickActions($state));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($state['pending_field'] !== null && in_array($command, ['skip', 'skip this', 'skip field', 'next'], true)) {
            return $this->skipPendingField($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['skip all details', 'finish details', 'done with details'], true)) {
            return $this->finishOptionalGuidance($state);
        }

        // Developer context: These branches handle temporary random previews before normal command routing so the user can reroll or keep a suggestion without losing the wizard flow.
        // Clear explanation: These lines let the wizard preview random suggestions, reroll them, and accept them.
        if ($state['random_preview'] !== null && in_array($command, ['keep this', 'use this', 'accept this', 'keep these scores', 'use these scores'], true)) {
            return $this->acceptRandomPreview($state);
        }

        if ($state['random_preview'] !== null && in_array($command, ['reroll random', 'reroll this', 'again'], true)) {
            return $this->rerollRandomPreview($state);
        }

        if ($command === 'reroll ability scores') {
            return $this->previewRolledAbilityScores($state, true);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['new', 'new character', 'start', 'start wizard', 'create', 'create character'], true)) {
            return $this->startNewCharacter();
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'list characters' || $command === 'show characters') {
            return $this->listCharacters($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'load latest') {
            return $this->loadLatestCharacter($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^load(?: character)?\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->loadCharacterReference((int) $matches[1], $state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'roll stats') {
            return $this->previewRolledAbilityScores($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'show summary' || $command === 'summary') {
            return $this->showSummary($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'show status' || $command === 'status') {
            return $this->showStatus($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['what did i gain', 'gains', 'show gains'], true)) {
            return $this->showGains($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['show next', 'next level', 'what do i get next'], true)) {
            return $this->showNextLevelPreview($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'level up') {
            return $this->levelUp($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['show spells', 'spell options', 'spell list'], true)) {
            return $this->showSpells($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['show slots', 'spell slots', 'show spell slots'], true)) {
            return $this->showSpellSlots($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'save character' || $command === 'save') {
            return $this->saveCharacter($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set )?hp\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->setHitPoints($state, (int) $matches[1]);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:take\s+)?(?:(critical|crit)\s+)?damage\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->applyDamage($state, (int) $matches[2], ! empty($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^heal\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->heal($state, (int) $matches[1]);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set\s+)?temp(?:orary)? hp\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->setTempHitPoints($state, (int) $matches[1]);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'clear temp hp' || $command === 'remove temp hp') {
            return $this->setTempHitPoints($state, 0);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^set ac\s+(\d+)$/i', $message, $matches) === 1) {
            return $this->setArmorClass($state, (int) $matches[1]);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^apply condition\s+(.+)$/i', $message, $matches) === 1) {
            return $this->applyCondition($state, trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^remove condition\s+(.+)$/i', $message, $matches) === 1) {
            return $this->removeCondition($state, trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'clear conditions' || $command === 'remove all conditions') {
            return $this->clearConditions($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^set exhaustion\s+([0-6])$/i', $message, $matches) === 1) {
            return $this->setExhaustion($state, (int) $matches[1]);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'roll initiative') {
            return $this->rollInitiative($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^roll skill\s+(.+?)(?:\s+(proficient|expertise))?$/i', $message, $matches) === 1) {
            return $this->rollSkillCheck($state, trim($matches[1]), $matches[2] ?? null);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^roll save\s+(.+?)(?:\s+(advantage|disadvantage))?$/i', $message, $matches) === 1) {
            return $this->rollSavingThrow($state, trim($matches[1]), $matches[2] ?? null);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^roll ability\s+(.+?)(?:\s+(advantage|disadvantage))?$/i', $message, $matches) === 1) {
            return $this->rollAbilityCheck($state, trim($matches[1]), $matches[2] ?? null);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'roll death save') {
            return $this->rollDeathSave($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'death save success') {
            return $this->recordDeathSave($state, true);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'death save failure') {
            return $this->recordDeathSave($state, false);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^roll\s+(.+?)(?:\s+(advantage|disadvantage))?$/i', $message, $matches) === 1) {
            return $this->rollExpression($state, trim($matches[1]), $matches[2] ?? null);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^short rest(?:\s+(\d+))?$/i', $message, $matches) === 1) {
            return $this->shortRest($state, isset($matches[1]) ? (int) $matches[1] : 0);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'long rest') {
            return $this->longRest($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^use slot\s+([1-9])$/i', $message, $matches) === 1) {
            return $this->useSpellSlot($state, (int) $matches[1]);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^cast(?: spell)?\s+(.+)$/i', $message, $matches) === 1) {
            return $this->castSpell($state, trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:concentrate|start concentration)\s+(.+)$/i', $message, $matches) === 1) {
            return $this->startConcentration($state, trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($command === 'drop concentration' || $command === 'end concentration') {
            return $this->endConcentration($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:show|inspect) monster\s+(.+)$/i', $message, $matches) === 1) {
            return $this->showMonster($state, trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['help me roleplay', 'roleplay help', 'show roleplay help'], true)) {
            return $this->showRoleplayHelp($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($command, ['show appearance help', 'appearance help', 'help me with looks'], true)) {
            return $this->showAppearanceHelp($state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+alignment\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'alignment', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+origin(?: |_)?feat\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'origin_feat', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+(?:advancement(?: |_)?method|level(?:ing)?(?: |_)?method)\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'advancement_method', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose|add)\s+(?:skill(?: |_)?proficiencies|skills?)\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'skill_proficiencies', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose|add)\s+(?:skill(?: |_)?expertise|expertise)\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'skill_expertise', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+languages?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'languages', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+personality(?: |_)?traits?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'personality_traits', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+ideals?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'ideals', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+goals?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'goals', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+bonds?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'bonds', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+flaws?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'flaws', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+(age|height|weight|eyes|hair|skin)\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, strtolower($matches[1]), trim($matches[2]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|add|write)\s+notes?\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, 'notes', trim($matches[1]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (preg_match('/^(?:set|choose)\s+(name|species|class|subclass|background|level|strength|dexterity|constitution|intelligence|wisdom|charisma)\s+(?:to\s+)?(.+)$/i', $message, $matches) === 1) {
            return $this->handleFieldInput($state, strtolower($matches[1]), trim($matches[2]));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($state['pending_field'] !== null) {
            return $this->handleFieldInput($state, $state['pending_field'], $message);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            'I did not recognize that command. Try `new character`, `show summary`, `show status`, `roll initiative`, `short rest`, `long rest`, `show monster goblin`, or `help`.',
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Handleemptymessage handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function handleEmptyMessage(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($state['pending_field'] !== null) {
            return $this->askForField($state, $state['pending_field']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                'The rules wizard is ready. Ask for `show summary`, `show status`, `what did I gain`, `show next`, `level up`, `show spells`, `help me roleplay`, `show appearance help`, `roll initiative`, `short rest`, `long rest`, or `save character`.',
                $this->defaultQuickActions($state),
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            'Welcome to the rules wizard. I am a deterministic D&D 2024 guide, not AI generation. I can build a character step by step, explain each choice in plain language, load a saved character, track dungeon-state math, calculate level gains, and show spell access from your local rules data.',
            ['new character', 'list characters', 'load latest', 'help'],
        );
    }

    // Developer context: Startnewcharacter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function startNewCharacter(): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state = $this->normalizeState([]);
        $state['pending_field'] = 'class';

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Starting a new character draft. We will follow the handbook flow as closely as this app can: Step 1 class, level, and advancement setup, Step 2 origin, Step 3 ability scores, Step 4 alignment, Step 5 the extra sheet details. Core sheet mechanics cannot be skipped. Expertise, roleplay, appearance, and notes can.\n\n".$this->guidedFieldHeading('class')."\n".$this->fieldPrompt('class', $state),
            $this->quickActionsForField('class', $state),
        );
    }

    // Developer context: Listcharacters handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function listCharacters(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $characters = Character::query()
            ->latest()
            ->limit(8)
            ->get(['id', 'name', 'class', 'subclass', 'level']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($characters->isEmpty()) {
            return $this->response(
                $state,
                'Your roster is empty right now. Start with `new character` and I will guide the build.',
                ['new character', 'help'],
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
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

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Saved characters:\n- ".implode("\n- ", $lines),
            array_map(
                static fn (int $index): string => 'load character '.($index + 1),
                array_keys($lines),
            ),
        );
    }

    // Developer context: Loadlatestcharacter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function loadLatestCharacter(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = Character::query()->latest()->first();

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character) {
            return $this->response(
                $state,
                'There is no saved character to load yet.',
                ['new character', 'help'],
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->loadCharacter($character->id, $state);
    }

    // Developer context: Loadcharacterreference handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function loadCharacterReference(int $reference, array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = Character::query()
            ->latest()
            ->skip(max($reference - 1, 0))
            ->first();

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($character) {
            return $this->loadCharacter($character->id, $state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (Character::find($reference)) {
            return $this->loadCharacter($reference, $state);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "I could not find roster character {$reference}. Try `list characters` first.",
            ['list characters', 'new character'],
        );
    }

    // Developer context: Loadcharacter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function loadCharacter(int $id, array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = Character::find($id);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character) {
            return $this->response(
                $state,
                'I could not find that saved character. Try `list characters` first.',
                ['list characters', 'new character'],
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state = $this->normalizeState([
            'character' => $this->characterToState($character),
            'dungeon' => [
                'hp_adjustment' => (int) $character->hp_adjustment,
                'rolled_hit_points' => (bool) $character->rolled_hit_points,
            ],
            'pending_field' => null,
        ]);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Loaded {$character->name}. You can now ask `show summary`, `show status`, `what did I gain`, `show next`, `level up`, `show spells`, `roll initiative`, or `show monster goblin`.",
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Handlefieldinput handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function handleFieldInput(array $state, string $field, string $value): array
    {
        if ($this->isRandomPreviewRequest($field, $value)) {
            return $this->previewRandomField($state, $field);
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $assignment = $this->assignField($state, $field, $value);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $assignment['ok']) {
            return $this->response(
                $assignment['state'],
                $assignment['message'],
                $this->quickActionsForField($field, $assignment['state']),
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state = $assignment['state'];
        $state['random_preview'] = null;
        $state['skipped_optional_fields'] = array_values(array_filter(
            $state['skipped_optional_fields'] ?? [],
            static fn (string $entry): bool => $entry !== $field,
        ));
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $nextField = $this->nextGuidedField($state);
        $state['pending_field'] = $nextField;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($nextField !== null) {
            return $this->response(
                $state,
                $assignment['message']."\n\n".$this->guidedFieldHeading($nextField)."\n".$this->fieldPrompt($nextField, $state).$this->skipHintForField($nextField),
                $this->quickActionsForField($nextField, $state),
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            $assignment['message']."\n\nThe draft is complete. Ask `show summary`, `show status`, `what did I gain`, `show next`, `show spells`, or `save character`.",
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Acceptrandompreview commits the wizard's current random suggestion so the guided flow can continue from that accepted value.
    // Clear explanation: This accepts the current random suggestion and moves the wizard forward.
    private function acceptRandomPreview(array $state): array
    {
        $preview = is_array($state['random_preview'] ?? null) ? $state['random_preview'] : null;

        if (! is_array($preview) || ! is_string($preview['kind'] ?? null)) {
            return $this->response($state, 'There is no random preview waiting right now.', $this->defaultQuickActions($state));
        }

        if ($preview['kind'] === 'field') {
            $state['random_preview'] = null;

            return $this->handleFieldInput($state, (string) $preview['field'], (string) $preview['value']);
        }

        if ($preview['kind'] !== 'stats' || ! is_array($preview['stats'] ?? null)) {
            return $this->response($state, 'There is no random preview waiting right now.', $this->defaultQuickActions($state));
        }

        $character = $this->markAsDraft($state['character']);
        foreach (self::STAT_FIELDS as $field) {
            $character[$field] = (int) $preview['stats'][$field];
        }

        $state['character'] = $character;
        $state['random_preview'] = null;
        $state['pending_field'] = is_string($preview['resume_field'] ?? null) && $preview['resume_field'] !== ''
            ? $preview['resume_field']
            : $this->nextGuidedField($state);

        $summary = implode(', ', array_map(
            fn (string $field): string => strtoupper(substr($field, 0, 3)).' '.(int) $character[$field],
            self::STAT_FIELDS,
        ));

        if ($state['pending_field'] !== null) {
            return $this->response(
                $state,
                "Ability scores kept: {$summary}.\n\n".$this->guidedFieldHeading($state['pending_field'])."\n".$this->fieldPrompt($state['pending_field'], $state).$this->skipHintForField($state['pending_field']),
                $this->quickActionsForField($state['pending_field'], $state),
            );
        }

        return $this->response(
            $state,
            "Ability scores kept: {$summary}.\n\nThe build is ready. You can review it with `show summary` or save it now.",
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Rerollrandompreview regenerates the current preview without committing it, which lets the user keep rerolling until the suggestion feels right.
    // Clear explanation: This gives another random suggestion for the current preview.
    private function rerollRandomPreview(array $state): array
    {
        $preview = is_array($state['random_preview'] ?? null) ? $state['random_preview'] : null;

        if (! is_array($preview) || ! is_string($preview['kind'] ?? null)) {
            return $this->response($state, 'There is no random preview waiting right now.', $this->defaultQuickActions($state));
        }

        if ($preview['kind'] === 'field' && is_string($preview['field'] ?? null)) {
            return $this->previewRandomField($state, $preview['field'], true);
        }

        if ($preview['kind'] === 'stats') {
            return $this->previewRolledAbilityScores($state, true);
        }

        return $this->response($state, 'There is no random preview waiting right now.', $this->defaultQuickActions($state));
    }

    // Developer context: Previewrandomfield builds a temporary random suggestion for one step-five field so the user can keep or reroll it without immediately locking it in.
    // Clear explanation: This shows a fitting random suggestion for the current field before the wizard saves it.
    private function previewRandomField(array $state, string $field, bool $reroll = false): array
    {
        $value = $this->randomFieldValue($field, $state['character']);

        if (! is_string($value) || trim($value) === '') {
            return $this->response($state, 'I could not build a fitting random suggestion for that field yet.', $this->quickActionsForField($field, $state));
        }

        $state['random_preview'] = [
            'kind' => 'field',
            'field' => $field,
            'value' => $value,
            'resume_field' => $state['pending_field'],
        ];

        $reply = ($reroll ? 'New random suggestion' : 'Random suggestion').' for '.Str::lower(self::FIELD_LABELS[$field]).": {$value}\nThis uses the choices already locked into the sheet, including earlier step 5 details you kept. Use `keep this` to accept it or `reroll random` to try again.";

        return $this->response($state, $reply, $this->quickActionsForField($field, $state));
    }

    // Developer context: Israndompreviewrequest recognizes the button texts and typed aliases that should open the random preview flow for one wizard field.
    // Clear explanation: This checks whether the user asked for a fitting random suggestion for the current field.
    private function isRandomPreviewRequest(string $field, string $value): bool
    {
        if (! in_array($field, self::RANDOMIZABLE_STEP_FIVE_FIELDS, true)) {
            return false;
        }

        $command = Str::of($value)->lower()->squish()->toString();

        return in_array($command, ['random', 'random that fits', 'surprise me', 'random name', 'random trait', 'random ideal', 'random goal', 'random bond', 'random flaw', 'random age', 'random height', 'random weight', 'random eyes', 'random hair', 'random skin', 'random notes'], true);
    }

    // Developer context: Assignfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function assignField(array $state, string $field, string $value): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $message = '';

        switch ($field) {
            case 'name':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $normalized = $this->normalizedTextField($field, $value);
                if ($normalized === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'The character still needs a name.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['name'] = $normalized;
                $message = "Name set to {$character['name']}.";
                break;

            case 'species':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $match = $this->matchOption($value, config('dnd.species', []));
                if ($match === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'That species did not match the local 2024 list.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['species'] = $match;
                $message = "Species set to {$match}.\n".$this->speciesGuidance($match);
                break;

            case 'class':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $match = $this->matchOption($value, config('dnd.classes', []));
                if ($match === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'That class did not match the local 2024 list.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['class'] = $match;
                $character['subclass'] = null;
                $state['dungeon']['hp_adjustment'] = 0;
                $state['dungeon']['rolled_hit_points'] = false;
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $message = "Class set to {$match}.\n".$this->classGuidance($match);
                break;

            case 'subclass':
                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if (! $character['class']) {
                    return ['ok' => false, 'state' => $state, 'message' => 'Choose a class before choosing a subclass.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $subclasses = config("dnd.class_details.{$character['class']}.subclasses", []);
                $match = $this->matchOption($value, $subclasses);
                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if ($match === null) {
                    return ['ok' => false, 'state' => $state, 'message' => "That subclass is not valid for {$character['class']}."];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['subclass'] = $match;
                $message = "Subclass set to {$match}.\nThis is your specialization inside {$character['class']}, so many later features will point back to this choice.";
                break;

            case 'skill_proficiencies':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $matches = $this->parseSkillList($value);

                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if ($matches === []) {
                    return ['ok' => false, 'state' => $state, 'message' => 'I could not match any of those skills from the local rules list.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['skill_proficiencies'] = $matches;
                $character['skill_expertise'] = array_values(array_intersect(
                    is_array($character['skill_expertise'] ?? null) ? $character['skill_expertise'] : [],
                    $character['skill_proficiencies'],
                ));

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $message = 'Skill proficiencies set to '.implode(', ', $character['skill_proficiencies']).".\n".$this->skillProficiencyGuidance($character);
                break;

            case 'skill_expertise':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $matches = $this->parseSkillList($value);

                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if ($matches === []) {
                    return ['ok' => false, 'state' => $state, 'message' => 'I could not match any expertise skills from the local rules list.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $proficiencies = is_array($character['skill_proficiencies'] ?? null) ? $character['skill_proficiencies'] : [];
                foreach ($matches as $match) {
                    // Developer context: This branch checks a rule before the workflow continues down one path.
                    // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                    if (! in_array($match, $proficiencies, true)) {
                        return ['ok' => false, 'state' => $state, 'message' => "Expertise only works on skills the character already has proficiency in. Add {$match} to skill proficiencies first."];
                    }
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['skill_expertise'] = $matches;
                $message = 'Skill expertise set to '.implode(', ', $character['skill_expertise']).".\nExpertise doubles the proficiency bonus on those skill checks.";
                break;

            case 'background':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $match = $this->matchOption($value, config('dnd.backgrounds', []));
                if ($match === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'That background did not match the local 2024 list.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['background'] = $match;
                $message = "Background set to {$match}.\n".$this->backgroundGuidance($match);
                break;

            case 'alignment':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $match = $this->matchOption($value, config('dnd.alignments', []));
                if ($match === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'That alignment did not match the local list.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['alignment'] = $match;
                $message = "Alignment set to {$match}.\n".$this->alignmentGuidance($match);
                break;

            case 'origin_feat':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $match = $this->matchOption($value, config('dnd.origin_feats', []));
                if ($match === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'That origin feat did not match the local 2024 list.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['origin_feat'] = $match;
                $message = "Origin feat set to {$match}.\n".(config("dnd.origin_feat_details.{$match}") ?: 'Origin feats add a small early gameplay twist.');
                break;

            case 'advancement_method':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $match = $this->matchOption($value, config('dnd.advancement_methods', []));
                if ($match === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'That advancement method did not match the local table options.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['advancement_method'] = $match;
                $message = "Advancement method set to {$match}.\n".$this->advancementMethodGuidance($match);
                break;

            case 'languages':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $matches = array_values(array_filter(array_map(
                    function (string $entry): ?string {
                        // Developer context: This assignment stores a working value that the next lines reuse.
                        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                        $value = $this->matchOption($entry, config('dnd.languages', []));

                        // Developer context: This return hands the finished value or response back to the caller.
                        // Clear explanation: This line sends the result back so the rest of the app can use it.
                        return $value ?: null;
                    },
                    preg_split('/[,|\n\r]+/', $value) ?: [],
                )));

                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if ($matches === []) {
                    return ['ok' => false, 'state' => $state, 'message' => 'I could not match any of those languages from the local list.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['languages'] = array_values(array_unique($matches));
                $message = 'Languages set to '.implode(', ', $character['languages']).".\nLanguages mostly matter for travel, NPC interaction, and lore access.";
                break;

            case 'personality_traits':
            case 'ideals':
            case 'goals':
            case 'bonds':
            case 'flaws':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $normalized = $this->normalizedTextField($field, $value);
                if ($normalized === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => self::FIELD_LABELS[$field].' cannot be empty once you choose to set it.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character[$field] = $normalized;
                $message = self::FIELD_LABELS[$field]." set.\n".$this->roleplayFieldGuidance($field, $character);
                break;

            case 'age':
            case 'height':
            case 'weight':
            case 'eyes':
            case 'hair':
            case 'skin':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $normalized = $this->normalizedTextField($field, $value);
                if ($normalized === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => self::FIELD_LABELS[$field].' cannot be empty once you choose to set it.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character[$field] = $normalized;
                $message = self::FIELD_LABELS[$field]." set to {$character[$field]}.\n".$this->appearanceFieldGuidance($field, $character);
                break;

            case 'notes':
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $normalized = $this->normalizedTextField($field, $value);
                if ($normalized === null) {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return ['ok' => false, 'state' => $state, 'message' => 'Notes cannot be empty once you choose to set them.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['notes'] = $normalized;
                $message = "Notes set.\nUse notes for campaign reminders, secrets, goals, or anything the sheet should remember.";
                break;

            case 'level':
                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if (! ctype_digit($value) || (int) $value < 1 || (int) $value > 20) {
                    return ['ok' => false, 'state' => $state, 'message' => 'Level must be a number from 1 to 20.'];
                }
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character['level'] = (int) $value;
                $state['dungeon']['hp_adjustment'] = 0;
                $state['dungeon']['rolled_hit_points'] = false;
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $message = "Level set to Level {$character['level']}.\n".((int) $character['level'] === 1
                    ? 'Level 1 is the easiest place to learn the class from the ground up.'
                    : 'Higher levels give more features, so there is more to keep track of during play.');
                break;

            default:
                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if (! in_array($field, self::STAT_FIELDS, true)) {
                    return ['ok' => false, 'state' => $state, 'message' => 'That field is not supported by the rules wizard.'];
                }

                // Developer context: This branch checks a rule before the workflow continues down one path.
                // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
                if (! ctype_digit($value) || (int) $value < 3 || (int) $value > 18) {
                    return ['ok' => false, 'state' => $state, 'message' => self::FIELD_LABELS[$field].' must be a number from 3 to 18.'];
                }

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $character[$field] = (int) $value;
                $message = self::FIELD_LABELS[$field]." set to {$character[$field]}.\n".$this->abilityGuidance($field, (int) $character[$field], $character);
                break;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state['character'] = $this->markAsDraft($this->normalizeCharacterDraft($character));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'ok' => true,
            'state' => $state,
            'message' => $message,
        ];
    }

    // Developer context: Rollstats handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function previewRolledAbilityScores(array $state, bool $reroll = false): array
    {
        $details = [];
        $stats = [];

        foreach (self::STAT_FIELDS as $field) {
            $details[$field] = $this->diceRoller->rollAbilityScoreDetail();
            $stats[$field] = $details[$field]['total'];
        }

        $resumeField = $state['pending_field'] !== null && ! in_array($state['pending_field'], self::STAT_FIELDS, true)
            ? $state['pending_field']
            : null;

        $state['random_preview'] = [
            'kind' => 'stats',
            'stats' => $stats,
            'resume_field' => $resumeField,
        ];

        $lines = [];
        foreach (self::STAT_FIELDS as $field) {
            $detail = $details[$field];
            $lines[] = sprintf(
                '%s %d (%s, drop %d)',
                strtoupper(substr($field, 0, 3)),
                $stats[$field],
                implode(', ', $detail['rolls']),
                $detail['dropped'],
            );
        }

        $reply = ($reroll ? 'Rerolled ability scores: ' : 'Rolled ability scores: ').implode(', ', $lines).".\nUse `keep these scores` to lock them in or `reroll ability scores` until you are happy with them.";

        if ($resumeField !== null) {
            $reply .= "\nWhen you keep them, the wizard will return to ".$this->guidedFieldHeading($resumeField).'.';
        }

        return $this->response($state, $reply, $this->quickActionsForField($state['pending_field'] ?? 'strength', $state));
    }

    // Developer context: Showsummary handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showSummary(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                'There is no active character draft yet. Start with `new character` or `list characters`.',
                ['new character', 'list characters'],
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $snapshot = $this->buildSnapshot($state);

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [
            $snapshot['identity'],
            'Proficiency Bonus: '.($snapshot['proficiency_bonus'] ?? 'n/a'),
        ];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['hit_point_value'] ?? null) !== null) {
            $lines[] = "{$snapshot['hit_point_label']}: {$snapshot['hit_point_value']}";
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($snapshot['missing_fields'] !== []) {
            $lines[] = 'Missing: '.implode(', ', $snapshot['missing_fields']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($snapshot['spellcasting_summary'] !== null) {
            $lines[] = $snapshot['spellcasting_summary'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['character_details'] ?? []) !== []) {
            $lines[] = 'Details: '.implode(' / ', $snapshot['character_details']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['languages'] ?? []) !== []) {
            $lines[] = 'Languages: '.implode(', ', $snapshot['languages']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['skill_proficiencies'] ?? []) !== []) {
            $lines[] = 'Skill Proficiencies: '.implode(', ', $snapshot['skill_proficiencies']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['skill_expertise'] ?? []) !== []) {
            $lines[] = 'Skill Expertise: '.implode(', ', $snapshot['skill_expertise']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['roleplay'] ?? []) !== []) {
            $lines[] = 'Roleplay: '.implode(' | ', $snapshot['roleplay']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['appearance'] ?? []) !== []) {
            $lines[] = 'Appearance: '.implode(' | ', $snapshot['appearance']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['notes'] ?? null) !== null) {
            $lines[] = 'Notes: '.$snapshot['notes'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['dungeon_status'] ?? null) !== null) {
            $lines[] = $snapshot['dungeon_status'];
        }

        $lines[] = 'Stats: '.implode(', ', array_map(
            static fn (array $stat): string => $stat['score'] === null ? "{$stat['label']} -" : "{$stat['label']} {$stat['score']} ({$stat['modifier']})",
            $snapshot['stats'],
        ));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Showstatus handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showStatus(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                'There is no active character draft yet. Start with `new character` or `list characters`.',
                ['new character', 'list characters'],
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $snapshot = $this->buildSnapshot($state);
        $lines = [
            $snapshot['identity'],
            $snapshot['dungeon_status'] ?? 'Dungeon state is not ready yet.',
        ];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['conditions'] ?? []) !== []) {
            $lines[] = 'Conditions: '.implode(', ', $snapshot['conditions']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['resources'] ?? []) !== []) {
            $lines[] = 'Resources: '.implode(' | ', $snapshot['resources']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['concentration'] ?? null) !== null) {
            $lines[] = 'Concentration: '.$snapshot['concentration'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($snapshot['death_track'] ?? null) !== null) {
            $lines[] = $snapshot['death_track'];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Showgains handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showGains(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $class = $character['class'];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $level = $character['level'];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $class || ! $level) {
            return $this->response(
                $state,
                'I need at least a class and level before I can calculate gains.',
                ['new character', 'show summary'],
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, $this->describeLevelGains($character, (int) $level), $this->defaultQuickActions($state));
    }

    // Developer context: Shownextlevelpreview handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showNextLevelPreview(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $class = $character['class'];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $level = $character['level'];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $class || ! $level) {
            return $this->response(
                $state,
                'I need at least a class and level before I can preview the next level.',
                ['new character', 'show summary'],
            );
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) $level >= 20) {
            return $this->response($state, 'This character is already at level 20.', $this->defaultQuickActions($state));
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            $this->describeLevelGains($character, (int) $level + 1, true),
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Levelup handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function levelUp(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $this->markAsDraft($state['character']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character['class'] || ! $character['level']) {
            return $this->response(
                $state,
                'I need a class and current level before I can level the character up.',
                ['new character', 'show summary'],
            );
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) $character['level'] >= 20) {
            return $this->response($state, 'This character is already at level 20.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character['level'] = (int) $character['level'] + 1;
        $state['character'] = $character;
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $hpLine = $this->applyLevelUpHitPoints($state);
        $advancementLine = is_string($character['advancement_method'] ?? null) && $character['advancement_method'] !== ''
            ? 'Advancement check: '.(config("dnd.advancement_method_details.{$character['advancement_method']}.level_up_note")
                ?: $this->advancementMethodGuidance($character['advancement_method']))
            : null;

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Level increased to {$character['level']}.\n".($advancementLine ? "{$advancementLine}\n" : '')."{$hpLine}\n\n".$this->describeLevelGains($character, (int) $character['level']),
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Showspells handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showSpells(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $classTag = $this->spellClassTag($character);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($classTag === null) {
            return $this->response(
                $state,
                'This build does not currently have a supported spell list in the local wizard.',
                $this->defaultQuickActions($state),
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $maxSpellLevel = $this->maxSpellLevel($character);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($maxSpellLevel < 0) {
            return $this->response(
                $state,
                'I need a class and level before I can determine spell access.',
                $this->defaultQuickActions($state),
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $spells = collect(config('dnd.compendium.spells.items', []))
            ->filter(static function (array $spell) use ($classTag, $maxSpellLevel): bool {
                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return in_array($classTag, $spell['classes'] ?? [], true) && ($spell['level'] ?? 99) <= $maxSpellLevel;
            })
            ->groupBy(static fn (array $spell): string => (string) $spell['level'])
            ->sortKeys()
            ->map(function (Collection $levelSpells, string $level): string {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $names = $levelSpells
                    ->sortBy('name')
                    ->pluck('name')
                    ->values()
                    ->all();

                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $preview = array_slice($names, 0, 10);
                $suffix = count($names) > 10 ? sprintf(' (+%d more)', count($names) - 10) : '';

                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return sprintf('%s: %s%s', $this->spellLevelLabel((int) $level, true), implode(', ', $preview), $suffix);
            })
            ->values()
            ->all();

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($spells === []) {
            return $this->response(
                $state,
                'No spell entries matched this build in the local compendium yet.',
                $this->defaultQuickActions($state),
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $slotSummary = $this->spellcastingSummary($character) ?? 'Spellcasting data not available.';

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Spell access for {$character['class']} at level {$character['level']}:\n{$slotSummary}\n\n".implode("\n", $spells),
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Showspellslots handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showSpellSlots(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $remaining = $state['dungeon']['spell_slots_remaining'] ?? [];
        $maximum = $this->maxSpellSlotsForCharacter($state['character']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($maximum === []) {
            return $this->response(
                $state,
                'This build does not currently track spell slots.',
                $this->defaultQuickActions($state),
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $subject = $state['character']['name'] ?: 'this build';
        $lines = ["Spell slots for {$subject}:"];
        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($maximum as $level => $count) {
            $lines[] = sprintf(
                '- %s level: %d / %d',
                $this->spellLevelLabel((int) $level),
                (int) ($remaining[(string) $level] ?? $remaining[$level] ?? 0),
                $count,
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Sethitpoints handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function setHitPoints(array $state, int $hitPoints): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['max_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I know the HP total.', ['new character', 'load latest']);
        }

        $state['dungeon']['current_hp'] = max(0, min($hitPoints, (int) $state['dungeon']['max_hp']));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($state['dungeon']['current_hp'] > 0) {
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
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

    // Developer context: Applydamage handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function applyDamage(array $state, int $damage, bool $critical = false): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['current_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track damage.', ['new character', 'load latest']);
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [];
        $tempAbsorbed = min((int) $state['dungeon']['temp_hp'], $damage);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $remainingDamage = $damage - $tempAbsorbed;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($tempAbsorbed > 0) {
            $state['dungeon']['temp_hp'] -= $tempAbsorbed;
            $lines[] = "Temporary HP absorbed {$tempAbsorbed}.";
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $wasAtZero = (int) $state['dungeon']['current_hp'] === 0;
        $state['dungeon']['current_hp'] = max(0, (int) $state['dungeon']['current_hp'] - $remainingDamage);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $wasAtZero && (int) $state['dungeon']['current_hp'] === 0) {
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;
            $lines[] = 'The character dropped to 0 HP.';
        } elseif ($wasAtZero && $remainingDamage > 0) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
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

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($state['dungeon']['concentration']) {
            $lines[] = 'Concentration check DC '.max(10, (int) ceil($damage / 2)).' if the character was concentrating.';
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) $state['dungeon']['death_failures'] >= 3) {
            $lines[] = 'The death save track has reached 3 failures.';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Heal handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function heal(array $state, int $healing): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['current_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track healing.', ['new character', 'load latest']);
        }

        $state['dungeon']['current_hp'] = min(
            (int) $state['dungeon']['max_hp'],
            (int) $state['dungeon']['current_hp'] + $healing,
        );

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) $state['dungeon']['current_hp'] > 0) {
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
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

    // Developer context: Settemphitpoints handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function setTempHitPoints(array $state, int $temporaryHitPoints): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response($state, 'Load or build a character first so I can track temporary HP.', ['new character', 'load latest']);
        }

        $state['dungeon']['temp_hp'] = max(0, $temporaryHitPoints);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Temporary HP set to {$state['dungeon']['temp_hp']}.",
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Setarmorclass handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function setArmorClass(array $state, int $armorClass): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response($state, 'Load or build a character first so I can track Armor Class.', ['new character', 'load latest']);
        }

        $state['dungeon']['ac'] = max(1, $armorClass);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Armor Class set to {$state['dungeon']['ac']}.",
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Applycondition handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function applyCondition(array $state, string $input): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $condition = $this->matchOption($input, config('dnd.conditions', []));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($condition === null) {
            return $this->response(
                $state,
                'That condition did not match the local rules list.',
                ['show status', 'help'],
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $conditions = $state['dungeon']['conditions'];
        if (! in_array($condition, $conditions, true)) {
            $conditions[] = $condition;
            sort($conditions);
        }

        $state['dungeon']['conditions'] = $conditions;
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $summary = config("dnd.condition_details.{$condition}", '');
        $reply = "Applied {$condition}.";
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($summary !== '') {
            $reply .= ' '.$summary;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, $reply, $this->defaultQuickActions($state));
    }

    // Developer context: Removecondition handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function removeCondition(array $state, string $input): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $condition = $this->matchOption($input, $state['dungeon']['conditions']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($condition === null) {
            return $this->response($state, 'That condition is not active right now.', $this->defaultQuickActions($state));
        }

        $state['dungeon']['conditions'] = array_values(array_filter(
            $state['dungeon']['conditions'],
            static fn (string $entry): bool => $entry !== $condition,
        ));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, "Removed {$condition}.", $this->defaultQuickActions($state));
    }

    // Developer context: Clearconditions handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function clearConditions(array $state): array
    {
        $state['dungeon']['conditions'] = [];

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, 'All tracked conditions were cleared.', $this->defaultQuickActions($state));
    }

    // Developer context: Setexhaustion handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function setExhaustion(array $state, int $level): array
    {
        $state['dungeon']['exhaustion'] = max(0, min(6, $level));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Exhaustion set to level {$state['dungeon']['exhaustion']}.",
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Shortrest handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function shortRest(array $state, int $hitDiceToSpend): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['current_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track rests.', ['new character', 'load latest']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) $state['dungeon']['current_hp'] === 0) {
            return $this->response($state, 'A character at 0 HP needs healing before taking a normal short rest.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = ['Short rest complete.'];
        $availableHitDice = (int) ($state['dungeon']['hit_dice_remaining'] ?? 0);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $spend = min($hitDiceToSpend, $availableHitDice);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($spend > 0) {
            $hitDie = $this->hitDieForClass((string) ($state['character']['class'] ?? '')) ?? 8;
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $conModifier = $state['character']['constitution'] === null
                ? 0
                : $this->abilityModifier((int) $state['character']['constitution']);
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $result = $this->diceRoller->rollHitPointDice($spend, $hitDie, $conModifier);
            $missingHitPoints = max(0, ((int) $state['dungeon']['max_hp']) - ((int) $state['dungeon']['current_hp']));
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $recovered = min($missingHitPoints, $result['total']);
            $wasted = max(0, $result['total'] - $recovered);

            $state['dungeon']['current_hp'] += $recovered;
            $state['dungeon']['hit_dice_remaining'] = max(0, $availableHitDice - $spend);
            $lines[] = "Spent {$spend} Hit Dice: {$result['detail']}.";
            $lines[] = "Recovered {$recovered} HP.".($wasted > 0 ? " {$wasted} more would have been wasted at max HP." : '');
        } elseif ($hitDiceToSpend > 0) {
            $lines[] = 'No Hit Dice remained to spend.';
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($state['character']['class'] ?? null) === 'Warlock') {
            $state['dungeon']['spell_slots_remaining'] = $this->maxSpellSlotsForCharacter($state['character']);
            $lines[] = 'Warlock Pact Magic slots were refreshed.';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Longrest handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function longRest(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character']) || $state['dungeon']['max_hp'] === null) {
            return $this->response($state, 'Load or build a character first so I can track rests.', ['new character', 'load latest']);
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
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

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [
            'Long rest complete.',
            sprintf('HP restored to %d/%d.', $state['dungeon']['current_hp'], $state['dungeon']['max_hp']),
            'Spell slots and Hit Dice were refreshed.',
        ];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($previousExhaustion !== (int) $state['dungeon']['exhaustion']) {
            $lines[] = "Exhaustion reduced to {$state['dungeon']['exhaustion']}.";
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Usespellslot handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function useSpellSlot(array $state, int $slotLevel): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $maximum = $this->maxSpellSlotsForCharacter($state['character']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! isset($maximum[$slotLevel])) {
            return $this->response($state, "This build does not have {$this->spellLevelLabel($slotLevel)}-level spell slots.", $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $remaining = (int) ($state['dungeon']['spell_slots_remaining'][(string) $slotLevel] ?? $state['dungeon']['spell_slots_remaining'][$slotLevel] ?? 0);
        if ($remaining <= 0) {
            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return $this->response($state, "No {$this->spellLevelLabel($slotLevel)}-level slots remain.", $this->defaultQuickActions($state));
        }

        $state['dungeon']['spell_slots_remaining'][(string) $slotLevel] = $remaining - 1;

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
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

    // Developer context: Castspell handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function castSpell(array $state, string $spellName): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $classTag = $this->spellClassTag($character);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($classTag === null) {
            return $this->response($state, 'This build does not currently have a supported spell list in the local wizard.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $spell = collect(config('dnd.compendium.spells.items', []))
            ->first(function (array $entry) use ($spellName, $classTag): bool {
                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return Str::lower($entry['name'] ?? '') === Str::lower($spellName)
                    && in_array($classTag, $entry['classes'] ?? [], true);
            });

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_array($spell)) {
            return $this->response($state, "I could not find a local spell entry for {$spellName} on this class list.", $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $level = (int) ($spell['level'] ?? 0);
        $summary = $spell['summary'] ?? '';

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($level > 0) {
            $usableLevel = $this->lowestAvailableSpellSlot($state['dungeon']['spell_slots_remaining'], $level);

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($usableLevel === null) {
                return $this->response(
                    $state,
                    "No spell slot of {$this->spellLevelLabel($level)} level or higher remains for {$spell['name']}.",
                    $this->defaultQuickActions($state),
                );
            }

            $state['dungeon']['spell_slots_remaining'][(string) $usableLevel]--;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! empty($spell['concentration'])) {
            $state['dungeon']['concentration'] = $spell['name'];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = ["Cast {$spell['name']}."];
        if ($summary !== '') {
            $lines[] = $summary;
        }
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($level > 0) {
            $lines[] = 'Slot spent: '.$this->spellLevelLabel($usableLevel).' level.';
        } else {
            $lines[] = 'Cantrip cast. No slot spent.';
        }
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! empty($spell['concentration'])) {
            $lines[] = "Concentration started on {$spell['name']}.";
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Startconcentration handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function startConcentration(array $state, string $subject): array
    {
        $state['dungeon']['concentration'] = Str::limit($subject, 120, '');

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Concentration started on {$state['dungeon']['concentration']}.",
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Endconcentration handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function endConcentration(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $state['dungeon']['concentration']) {
            return $this->response($state, 'No concentration effect is being tracked right now.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $spell = $state['dungeon']['concentration'];
        $state['dungeon']['concentration'] = null;

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, "Concentration ended on {$spell}.", $this->defaultQuickActions($state));
    }

    // Developer context: Rollinitiative handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollInitiative(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response($state, 'Load or build a character first so I can roll initiative.', ['new character', 'load latest']);
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $modifier = (int) ($state['dungeon']['initiative_bonus'] ?? 0);
        $result = $this->rollD20($modifier);
        $state['dungeon']['last_initiative'] = $result['total'];

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
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

    // Developer context: Rollskillcheck handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollSkillCheck(array $state, string $skillInput, ?string $training): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $skillName = $this->matchOption($skillInput, config('dnd.skills', []));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($skillName === null) {
            return $this->response($state, 'That skill did not match the local rules list.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $ability = config("dnd.skill_details.{$skillName}.ability");
        $abilityField = is_string($ability) ? Str::of($ability)->lower()->toString() : '';

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! isset($state['character'][$abilityField]) || $state['character'][$abilityField] === null) {
            return $this->response($state, "I need {$ability} on the active character before rolling {$skillName}.", $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $modifier = $this->abilityModifier((int) $state['character'][$abilityField]);
        $pb = $state['character']['level'] ? $this->proficiencyBonus((int) $state['character']['level']) : 0;
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $selectedExpertise = is_array($state['character']['skill_expertise'] ?? null)
            && in_array($skillName, $state['character']['skill_expertise'], true);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $selectedProficiency = $selectedExpertise
            || (is_array($state['character']['skill_proficiencies'] ?? null)
                && in_array($skillName, $state['character']['skill_proficiencies'], true));
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $effectiveTraining = $training ?? ($selectedExpertise ? 'expertise' : ($selectedProficiency ? 'proficient' : null));
        $trainingBonus = match ($effectiveTraining) {
            'proficient' => $pb,
            'expertise' => $pb * 2,
            default => 0,
        };

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $result = $this->rollD20($modifier + $trainingBonus);
        $trainingLabel = match ($effectiveTraining) {
            'proficient' => 'with proficiency',
            'expertise' => 'with expertise',
            default => 'without added proficiency',
        };
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $trainingSource = $training === null
            ? ($effectiveTraining === null ? 'No saved training was applied.' : 'Using the saved sheet training.')
            : 'Using the training override from the command.';

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            sprintf(
                '%s check: %d (%d %s %d) %s. %s',
                $skillName,
                $result['total'],
                $result['roll'],
                ($modifier + $trainingBonus) >= 0 ? '+' : '-',
                abs($modifier + $trainingBonus),
                $trainingLabel,
                $trainingSource,
            ),
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Rollsavingthrow handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollSavingThrow(array $state, string $abilityInput, ?string $mode): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $abilityField = $this->normalizeAbilityField($abilityInput);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($abilityField === null || $state['character'][$abilityField] === null) {
            return $this->response($state, 'I need a valid ability score on the active character before I can roll that save.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $modifier = $this->abilityModifier((int) $state['character'][$abilityField]);
        if ($this->isSavingThrowProficient($state['character'], $abilityField) && $state['character']['level']) {
            $modifier += $this->proficiencyBonus((int) $state['character']['level']);
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $result = $this->rollD20($modifier, $mode);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
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

    // Developer context: Rollabilitycheck handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollAbilityCheck(array $state, string $abilityInput, ?string $mode): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $abilityField = $this->normalizeAbilityField($abilityInput);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($abilityField === null || $state['character'][$abilityField] === null) {
            return $this->response($state, 'I need a valid ability score on the active character before I can roll that check.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $modifier = $this->abilityModifier((int) $state['character'][$abilityField]);
        $result = $this->rollD20($modifier, $mode);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
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

    // Developer context: Rollexpression handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollExpression(array $state, string $expression, ?string $mode): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $result = $this->diceRoller->rollExpression($expression, $mode);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($result === null) {
            return $this->response(
                $state,
                'I could not parse that roll. Try `roll d20+5`, `roll 2d6+3`, or use `roll skill stealth`.',
                $this->defaultQuickActions($state),
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            sprintf('Roll %s%s: %d (%s).', $expression, $mode ? " with {$mode}" : '', $result['total'], $result['detail']),
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Rolldeathsave handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollDeathSave(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) ($state['dungeon']['current_hp'] ?? 0) > 0) {
            return $this->response($state, 'Death saves only matter while the character is at 0 HP.', $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $die = random_int(1, 20);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($die === 1) {
            return $this->recordDeathSave($state, false, 2, "Rolled a natural 1 on the death save ({$die}).");
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($die === 20) {
            $state['dungeon']['current_hp'] = 1;
            $state['dungeon']['death_successes'] = 0;
            $state['dungeon']['death_failures'] = 0;
            $state['dungeon']['stable'] = false;

            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return $this->response($state, 'Rolled a natural 20 on the death save. The character regains 1 HP.', $this->defaultQuickActions($state));
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->recordDeathSave(
            $state,
            $die >= 10,
            1,
            "Rolled {$die} on the death save.",
        );
    }

    // Developer context: Recorddeathsave handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function recordDeathSave(array $state, bool $success, int $steps = 1, ?string $prefix = null): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) ($state['dungeon']['current_hp'] ?? 0) > 0) {
            return $this->response($state, 'Death saves only matter while the character is at 0 HP.', $this->defaultQuickActions($state));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($success) {
            $state['dungeon']['death_successes'] = min(3, (int) $state['dungeon']['death_successes'] + $steps);
        } else {
            $state['dungeon']['death_failures'] = min(3, (int) $state['dungeon']['death_failures'] + $steps);
            $state['dungeon']['stable'] = false;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = array_filter([$prefix]);
        $lines[] = sprintf(
            'Death saves: %d success, %d failure.',
            $state['dungeon']['death_successes'],
            $state['dungeon']['death_failures'],
        );

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) $state['dungeon']['death_successes'] >= 3) {
            $state['dungeon']['stable'] = true;
            $lines[] = 'The character is stable at 0 HP.';
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) $state['dungeon']['death_failures'] >= 3) {
            $lines[] = 'The death save track has reached 3 failures.';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Showmonster handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showMonster(array $state, string $monsterName): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $monsters = collect(config('dnd.compendium.monsters.items', []));
        $monster = $monsters
            ->first(function (array $entry) use ($monsterName): bool {
                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return Str::lower($entry['name'] ?? '') === Str::lower($monsterName);
            });

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_array($monster)) {
            $match = $this->matchOption($monsterName, array_map(
                static fn (array $entry): string => $entry['name'],
                config('dnd.compendium.monsters.items', []),
            ));

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($match !== null) {
                return $this->showMonster($state, $match);
            }

            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $suggestions = $monsters
                ->filter(static function (array $entry) use ($monsterName): bool {
                    // Developer context: This return hands the finished value or response back to the caller.
                    // Clear explanation: This line sends the result back so the rest of the app can use it.
                    return Str::contains(Str::lower($entry['name'] ?? ''), Str::lower($monsterName));
                })
                ->pluck('name')
                ->take(4)
                ->values()
                ->all();

            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $message = "I could not find a monster named {$monsterName} in the local compendium.";
            if ($suggestions !== []) {
                $message .= ' Try: '.implode(', ', $suggestions).'.';
            }

            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return $this->response($state, $message, $this->defaultQuickActions($state));
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
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

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($monster['trait_names'] ?? []) !== []) {
            $lines[] = 'Traits: '.implode(', ', array_slice($monster['trait_names'], 0, 6));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($monster['action_names'] ?? []) !== []) {
            $lines[] = 'Actions: '.implode(', ', array_slice($monster['action_names'], 0, 6));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($monster['legendary_action_names'] ?? []) !== []) {
            $lines[] = 'Legendary Actions: '.implode(', ', array_slice($monster['legendary_action_names'], 0, 4));
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", array_filter($lines)), $this->defaultQuickActions($state));
    }

    // Developer context: Showroleplayhelp handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showRoleplayHelp(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                "Roleplay can stay simple. Start with four anchors: how the character comes across, what they believe, who or what they care about, and what usually gets them into trouble.\n\nStart with `new character`, then come back here once you have at least a class, background, or alignment.",
                ['new character', 'help'],
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $starter = $this->combinedRoleplayStarter($character);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [
            'You do not need a novel. A beginner-safe roleplay core is just five short lines:',
            '- Personality Trait: how the character feels in the first five minutes',
            '- Ideal: the principle they try to live by',
            '- Goal: the next meaningful thing they want to achieve, protect, prove, or uncover',
            '- Bond: the person, place, or oath they care about',
            '- Flaw: the weakness that sometimes complicates good choices',
        ];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($starter['summary'] ?? '') !== '') {
            $lines[] = '';
            $lines[] = 'Quick read: '.$starter['summary'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($starter['watch_out'] ?? '') !== '') {
            $lines[] = 'Watch out: '.$starter['watch_out'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $starterPrompts = $this->roleplayStarterPrompts($character);
        if ($starterPrompts !== []) {
            $lines[] = '';
            $lines[] = 'Suggested lines:';
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $labels = ['Trait', 'Ideal', 'Goal', 'Bond', 'Flaw'];
            foreach ($starterPrompts as $index => $prompt) {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $label = $labels[$index] ?? 'Prompt';
                $lines[] = sprintf('- %s: %s', $label, $prompt);
            }
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $currentRoleplay = array_filter([
            $character['personality_traits'] ? 'Trait: '.$character['personality_traits'] : null,
            $character['ideals'] ? 'Ideal: '.$character['ideals'] : null,
            $character['goals'] ? 'Goal: '.$character['goals'] : null,
            $character['bonds'] ? 'Bond: '.$character['bonds'] : null,
            $character['flaws'] ? 'Flaw: '.$character['flaws'] : null,
        ]);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($currentRoleplay !== []) {
            $lines[] = '';
            $lines[] = 'Already on the sheet:';
            // Developer context: This loop applies the same step to each entry in the current list.
            // Clear explanation: This line repeats the same work for every item in a group.
            foreach ($currentRoleplay as $entry) {
                $lines[] = '- '.$entry;
            }
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $tableNotes = array_values(array_filter([
            ($starter['progression'] ?? '') !== '' ? 'Campaign pace: '.$starter['progression'] : null,
            ...array_map(
                static fn (string $line): string => 'Table note: '.$line,
                $this->roleplayReferenceLines(),
            ),
        ]));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($tableNotes !== []) {
            $lines[] = '';
            $lines[] = 'Table notes:';
            foreach ($tableNotes as $note) {
                $lines[] = '- '.$note;
            }
        }

        $lines[] = '';
        $lines[] = 'Use commands like `set personality traits ...`, `set ideals ...`, `set goals ...`, `set bonds ...`, or `set flaws ...` when one of those lines clicks.';

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Showappearancehelp handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function showAppearanceHelp(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->hasCharacterData($state['character'])) {
            return $this->response(
                $state,
                'Appearance can stay light. Pick a few anchors like age, height, eyes, hair, and one memorable feature. Start with `new character`, and I can help once the build exists.',
                ['new character', 'help'],
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [
            'You do not need a full portrait. Three to six appearance anchors are enough for most tables:',
        ];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach (['age', 'height', 'weight', 'eyes', 'hair', 'skin'] as $field) {
            $current = $state['character'][$field] ?? null;
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $label = self::FIELD_LABELS[$field];
            $help = config("dnd.appearance_field_help.{$field}", '');
            $lines[] = sprintf(
                '- %s: %s',
                $label,
                $current ? "{$current} (already set)" : $help,
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $cues = $this->appearanceCueLines($state['character']);
        if ($cues !== []) {
            $lines[] = '';
            $lines[] = 'Ability-based look cues:';
            // Developer context: This loop applies the same step to each entry in the current list.
            // Clear explanation: This line repeats the same work for every item in a group.
            foreach ($cues as $cue) {
                $lines[] = '- '.$cue;
            }
        }

        $lines[] = '';
        $lines[] = 'Use commands like `set age 23`, `set height 173 cm`, `set eyes gray`, or `set hair black braid`.';

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, implode("\n", $lines), $this->defaultQuickActions($state));
    }

    // Developer context: Skippendingfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function skipPendingField(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $field = $state['pending_field'];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($field) || $field === '') {
            return $this->response($state, 'There is nothing waiting for input right now.', $this->defaultQuickActions($state));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->isOptionalField($field)) {
            return $this->response(
                $state,
                self::FIELD_LABELS[$field].' is part of the core build, so it cannot be skipped.',
                $this->quickActionsForField($field, $state),
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $skipped = $state['skipped_optional_fields'] ?? [];
        if (! in_array($field, $skipped, true)) {
            $skipped[] = $field;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state['skipped_optional_fields'] = array_values(array_unique($skipped));
        $state['random_preview'] = null;
        $nextField = $this->nextGuidedField($state);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state['pending_field'] = $nextField;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($nextField !== null) {
            return $this->response(
                $state,
                self::FIELD_LABELS[$field]." skipped for now.\n\n".$this->guidedFieldHeading($nextField)."\n".$this->fieldPrompt($nextField, $state).$this->skipHintForField($nextField),
                $this->quickActionsForField($nextField, $state),
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            self::FIELD_LABELS[$field].' skipped. All optional details are now skipped, so the draft is ready for review or saving.',
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Finishoptionalguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function finishOptionalGuidance(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($this->missingFields($state['character']) !== []) {
            return $this->response(
                $state,
                'The core build is not finished yet, so optional details cannot be closed out. Finish the required fields first.',
                $this->defaultQuickActions($state),
            );
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state['pending_field'] = null;
        $state['skipped_optional_fields'] = self::OPTIONAL_FIELDS;
        $state['random_preview'] = null;

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            'Optional details closed for now. You can still set any field later with commands like `set alignment ...`, `set notes ...`, or `set eyes ...`.',
            $this->defaultQuickActions($state),
        );
    }

    // Developer context: Savecharacter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function saveCharacter(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = Character::create([
            ...$this->characterDataValidator->validateForSave($state['character']),
            'hp_adjustment' => (int) ($state['dungeon']['hp_adjustment'] ?? 0),
            'rolled_hit_points' => (bool) ($state['dungeon']['rolled_hit_points'] ?? false),
        ]);

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state['character'] = $this->characterToState($character);
        $state['dungeon']['hp_adjustment'] = (int) $character->hp_adjustment;
        $state['dungeon']['rolled_hit_points'] = (bool) $character->rolled_hit_points;
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state['pending_field'] = null;
        $state['skipped_optional_fields'] = [];

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response(
            $state,
            "Saved {$character->name} to the roster. Use `load latest` any time to jump back to this sheet.",
            ['show summary', 'what did I gain', 'load latest', 'new character'],
        );
    }

    // Developer context: Describelevelgains handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function describeLevelGains(array $character, int $targetLevel, bool $preview = false): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = $this->levelEntry((string) $character['class'], $targetLevel);
        $previous = $targetLevel > 1 ? $this->levelEntry((string) $character['class'], $targetLevel - 1) : null;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($entry === null) {
            return 'I could not find progression data for that class level.';
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [];
        $heading = $preview ? "Preview for level {$targetLevel}" : "Level {$targetLevel} gains";
        $lines[] = $heading.':';

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_string($character['advancement_method'] ?? null) && $character['advancement_method'] !== '') {
            $advancementNote = config("dnd.advancement_method_details.{$character['advancement_method']}.level_up_note")
                ?: $this->advancementMethodGuidance($character['advancement_method']);
            $lines[] = '- Advancement: '.$character['advancement_method'].'. '.$advancementNote;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $features = array_map(fn (string $feature): string => $this->displayFeature($feature, $character), $entry['features'] ?? []);
        if ($features !== []) {
            $lines[] = '- Features: '.implode(', ', $features);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($previous === null || ($entry['proficiency_bonus'] ?? null) !== ($previous['proficiency_bonus'] ?? null)) {
            $lines[] = '- Proficiency Bonus: +'.$entry['proficiency_bonus'];
        }

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($this->resourceChanges($entry, $previous) as $change) {
            $lines[] = "- {$change}";
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $slotChange = $this->slotChange($character, $entry, $previous);
        if ($slotChange !== null) {
            $lines[] = "- {$slotChange}";
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (count($lines) === 1) {
            $lines[] = '- No new tracked changes were found in the local progression data.';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return implode("\n", $lines);
    }

    // Developer context: Response handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function response(array $state, string $reply, array $quickActions): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state = $this->normalizeState($state);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'reply' => $reply,
            'state' => $state,
            'quick_actions' => array_values(array_unique(array_filter($quickActions))),
            'snapshot' => $this->buildSnapshot($state),
        ];
    }

    // Developer context: Buildsnapshot handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function buildSnapshot(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $dungeon = $state['dungeon'];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $stats = [];
        foreach (self::STAT_FIELDS as $field) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $score = $character[$field];
            $stats[] = [
                'label' => strtoupper(substr($field, 0, 3)),
                'score' => $score,
                'modifier' => $score === null ? null : $this->formatModifier($this->abilityModifier((int) $score)),
            ];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $identityParts = array_filter([
            $character['name'] ?: 'Unnamed hero',
            $character['species'],
            $character['class'],
            $character['subclass'],
            $character['background'],
            $character['level'] ? 'Level '.$character['level'] : null,
        ]);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'identity' => implode(' / ', $identityParts),
            'missing_fields' => array_map(fn (string $field): string => self::FIELD_LABELS[$field], $this->missingFields($character)),
            'proficiency_bonus' => $character['class'] && $character['level'] ? '+'.$this->proficiencyBonus((int) $character['level']) : null,
            'estimated_hit_points' => $this->estimatedHitPoints($character),
            'hit_point_label' => ($dungeon['rolled_hit_points'] ?? false) ? 'Rolled Max HP' : 'Estimated HP',
            'hit_point_value' => $dungeon['max_hp'] ?? $this->estimatedHitPoints($character),
            'character_details' => array_values(array_filter([
                $character['alignment'] ?? null,
                $character['origin_feat'] ?? null,
                ($character['advancement_method'] ?? null) ? 'Advancement: '.$character['advancement_method'] : null,
            ])),
            'advancement_method' => $character['advancement_method'] ?? null,
            'languages' => is_array($character['languages'] ?? null) ? $character['languages'] : [],
            'skill_proficiencies' => is_array($character['skill_proficiencies'] ?? null) ? $character['skill_proficiencies'] : [],
            'skill_expertise' => is_array($character['skill_expertise'] ?? null) ? $character['skill_expertise'] : [],
            'roleplay' => array_values(array_filter([
                $character['personality_traits'] ? 'Trait: '.$character['personality_traits'] : null,
                $character['ideals'] ? 'Ideal: '.$character['ideals'] : null,
                $character['goals'] ? 'Goal: '.$character['goals'] : null,
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
            'official_rules_warnings' => $this->officialRulesWarningService->forCharacter($character),
            'dungeon_status' => $this->dungeonStatusLine($dungeon),
            'conditions' => $dungeon['conditions'],
            'concentration' => $dungeon['concentration'],
            'resources' => $this->resourceSnapshot($state),
            'death_track' => $this->deathTrackLine($dungeon),
        ];
    }

    // Developer context: Dungeonstatusline handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function dungeonStatusLine(array $dungeon): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($dungeon['current_hp'] === null || $dungeon['max_hp'] === null) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $parts = [
            sprintf('HP %d/%d', $dungeon['current_hp'], $dungeon['max_hp']),
            sprintf('Temp HP %d', $dungeon['temp_hp']),
        ];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($dungeon['ac'] !== null) {
            $parts[] = 'AC '.$dungeon['ac'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($dungeon['exhaustion'] > 0) {
            $parts[] = 'Exhaustion '.$dungeon['exhaustion'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($dungeon['last_initiative'] !== null) {
            $parts[] = 'Last Initiative '.$dungeon['last_initiative'];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return implode(' | ', $parts);
    }

    // Developer context: Resourcesnapshot handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function resourceSnapshot(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon = $state['dungeon'];
        $resources = [];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($dungeon['hit_dice_remaining'] !== null && $state['character']['level']) {
            $resources[] = sprintf('Hit Dice %d/%d', $dungeon['hit_dice_remaining'], $state['character']['level']);
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $maximumSlots = $this->maxSpellSlotsForCharacter($state['character']);
        foreach ($maximumSlots as $level => $count) {
            $resources[] = sprintf(
                '%s slots %d/%d',
                $this->spellLevelLabel((int) $level),
                (int) ($dungeon['spell_slots_remaining'][(string) $level] ?? $dungeon['spell_slots_remaining'][$level] ?? 0),
                $count,
            );
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $resources;
    }

    // Developer context: Deathtrackline handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function deathTrackLine(array $dungeon): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) ($dungeon['current_hp'] ?? 1) > 0 && ! $dungeon['stable']) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $line = sprintf(
            'Death saves %d success / %d failure',
            (int) $dungeon['death_successes'],
            (int) $dungeon['death_failures'],
        );

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($dungeon['stable']) {
            $line .= ' | Stable';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $line;
    }

    // Developer context: Currentfeatures handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function currentFeatures(array $character): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character['class'] || ! $character['level']) {
            return [];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($entry === null) {
            return [];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_map(fn (string $feature): string => $this->displayFeature($feature, $character), $entry['features'] ?? []);
    }

    // Developer context: Nextgains handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function nextGains(array $character): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character['class'] || ! $character['level'] || (int) $character['level'] >= 20) {
            return [];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $next = $this->levelEntry((string) $character['class'], (int) $character['level'] + 1);
        if ($next === null) {
            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return [];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_map(fn (string $feature): string => $this->displayFeature($feature, $character), $next['features'] ?? []);
    }

    // Developer context: Spellcastingsummary handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function spellcastingSummary(array $character): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character['class'] || ! $character['level']) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($entry === null) {
            return null;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['spell_slots'] ?? []) !== []) {
            $pairs = [];
            // Developer context: This loop applies the same step to each entry in the current list.
            // Clear explanation: This line repeats the same work for every item in a group.
            foreach ($entry['spell_slots'] as $level => $slots) {
                $pairs[] = $this->spellLevelLabel((int) $level).' '.$slots;
            }

            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $preparedSpells = $entry['resources']['prepared_spells'] ?? null;
            $cantrips = $entry['resources']['cantrips'] ?? null;
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $prefix = [];
            if ($cantrips !== null) {
                $prefix[] = "Cantrips {$cantrips}";
            }
            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($preparedSpells !== null) {
                $prefix[] = "Prepared {$preparedSpells}";
            }

            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $parts = [];
            if ($prefix !== []) {
                $parts[] = implode(' / ', $prefix);
            }
            $parts[] = 'Slots: '.implode(', ', $pairs);

            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return implode(' / ', $parts);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['resources']['spell_slots'] ?? null) !== null && ($entry['resources']['slot_level'] ?? null) !== null) {
            return sprintf(
                'Pact Magic: %d slots at %s level',
                $entry['resources']['spell_slots'],
                $this->spellLevelLabel((int) $entry['resources']['slot_level']),
            );
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($this->spellClassTag($character) === 'Wizard' && in_array($character['subclass'], ['Arcane Trickster', 'Eldritch Knight'], true)) {
            $maxLevel = $this->maxSpellLevel($character);

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($maxLevel > 0) {
                return 'Third-caster access through '.$this->spellLevelLabel($maxLevel).' spells.';
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return null;
    }

    // Developer context: Slotchange handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function slotChange(array $character, array $entry, ?array $previous): ?string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $current = $this->spellcastingSummaryFromEntry($character, $entry);
        $before = $previous ? $this->spellcastingSummaryFromEntry($character, $previous) : null;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($current === null || $current === $before) {
            return null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $current;
    }

    // Developer context: Spellcastingsummaryfromentry handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function spellcastingSummaryFromEntry(array $character, array $entry): ?string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['spell_slots'] ?? []) !== []) {
            $pairs = [];
            // Developer context: This loop applies the same step to each entry in the current list.
            // Clear explanation: This line repeats the same work for every item in a group.
            foreach ($entry['spell_slots'] as $level => $slots) {
                $pairs[] = $this->spellLevelLabel((int) $level).' '.$slots;
            }

            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return 'Spell Slots: '.implode(', ', $pairs);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['resources']['spell_slots'] ?? null) !== null && ($entry['resources']['slot_level'] ?? null) !== null) {
            return sprintf(
                'Pact Magic: %d slots at %s level',
                $entry['resources']['spell_slots'],
                $this->spellLevelLabel((int) $entry['resources']['slot_level']),
            );
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($this->spellClassTag($character) === 'Wizard' && in_array($character['subclass'], ['Arcane Trickster', 'Eldritch Knight'], true)) {
            $maxLevel = self::THIRD_CASTER_MAX_SPELL_LEVEL[(int) ($character['level'] ?? 0)] ?? 0;

            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return $maxLevel > 0 ? 'Third-caster access: '.$this->spellLevelLabel($maxLevel) : null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return null;
    }

    // Developer context: Resourcechanges handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function resourceChanges(array $entry, ?array $previous): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $changes = [];
        $currentResources = $entry['resources'] ?? [];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $previousResources = $previous['resources'] ?? [];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($currentResources as $key => $value) {
            $before = $previousResources[$key] ?? null;

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($previous === null || $before !== $value) {
                $changes[] = sprintf('%s: %s', $this->humanizeKey($key), $value);
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $changes;
    }

    // Developer context: Humanizekey handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function humanizeKey(string $key): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return Str::of($key)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    // Developer context: Askforfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function askForField(array $state, string $field): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->response($state, $this->fieldPrompt($field, $state), $this->quickActionsForField($field, $state));
    }

    // Developer context: Guidedfieldheading handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function guidedFieldHeading(string $field): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return match ($field) {
            'class', 'level', 'advancement_method', 'subclass', 'skill_proficiencies', 'skill_expertise' => 'Step 1: Choose a Class',
            'background', 'species', 'origin_feat', 'languages' => 'Step 2: Determine Origin',
            'strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma' => 'Step 3: Determine Ability Scores',
            'alignment' => 'Step 4: Choose an Alignment',
            'notes' => 'Step 5: Fill in Details - Final Optional Piece',
            default => 'Step 5: Fill in Details',
        };
    }

    // Developer context: Skiphintforfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function skipHintForField(string $field): string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $this->isOptionalField($field)) {
            return '';
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($field === 'notes') {
            return "\nType `skip` to finish without notes, or `skip all details` to close out the remaining optional details now.";
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return "\nType `skip` to leave this blank or `skip all details` to finish the draft now.";
    }

    // Developer context: Fieldprompt handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function fieldPrompt(string $field, array $state): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $name = $state['character']['name'] ?: 'your character';

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return match ($field) {
            'name' => "What name goes on the sheet for {$name}?\nThis is part of the final detail pass, so a simple working name is completely fine. Use `random that fits` if you want a rerollable name suggestion.",
            'species' => "Choose a species for {$name}.\nThis is still the origin step. Each option bubble includes a short summary so you can compare them there.",
            'class' => "Choose a class for {$name}.\nThis is the first handbook step. Each option bubble includes the class summary and main ability focus.",
            'subclass' => "Choose a subclass for {$name}.\nThis finishes the class step by locking in the specialization that shapes later features.",
            'skill_proficiencies' => "Choose the skills {$name} is proficient in.\nList one or more skills separated by commas. ".$this->skillProficiencyPrompt($state['character']),
            'skill_expertise' => "Choose any skills that have expertise for {$name}.\nOnly list skills the sheet already marks as proficient. If none apply yet, type `skip`.",
            'background' => "Choose a background for {$name}.\nThis begins the origin step and helps explain who the character was before adventuring.",
            'level' => "What level are we building? Enter a number from 1 to 20, and I will show it back as Level X.\nThis is part of the class step. Level 1 is the easiest place to learn. Level 3 is where many classes feel more complete.",
            'advancement_method' => "Choose how {$name} levels up at the table.\nThis is campaign-facing sheet context, so I use it in level-up reminders, pacing notes, and roleplay prompts.",
            'alignment' => "Choose an alignment for {$name}.\nThis is the handbook's fourth step. Each option bubble includes the short alignment summary, and I will help with the roleplay side afterward.",
            'origin_feat' => "Choose an origin feat for {$name}.\nThis is still part of origin and counts as core sheet data. Each option bubble includes what the feat broadly does.",
            'languages' => "Choose one or more languages for {$name}.\nThis closes out origin and counts as core sheet data. Languages mostly matter for conversations, travel, and lore.",
            'personality_traits' => "Add one short personality line for {$name}.\n".config('dnd.roleplay_field_help.personality_traits')."\nUse `random that fits` if you want a rerollable suggestion based on the sheet so far.",
            'ideals' => "Add an ideal for {$name}.\n".config('dnd.roleplay_field_help.ideals')."\nUse `random that fits` if you want a rerollable suggestion based on the sheet so far.",
            'goals' => "Add a goal for {$name}.\n".config('dnd.roleplay_field_help.goals')."\nUse `random that fits` if you want a rerollable suggestion based on the sheet so far.",
            'bonds' => "Add a bond for {$name}.\n".config('dnd.roleplay_field_help.bonds')."\nUse `random that fits` if you want a rerollable suggestion based on the sheet so far.",
            'flaws' => "Add a flaw for {$name}.\n".config('dnd.roleplay_field_help.flaws')."\nUse `random that fits` if you want a rerollable suggestion based on the sheet so far.",
            'age', 'height', 'weight', 'eyes', 'hair', 'skin' => 'Add '.Str::lower(self::FIELD_LABELS[$field])." for {$name}.\n".(config("dnd.appearance_field_help.{$field}") ?: 'A short descriptive note is enough.')."\nUse `random that fits` if you want a rerollable suggestion based on the current build.",
            'notes' => "Add notes for {$name}.\nThis is the last optional wizard step. Use it for campaign reminders, secrets, goals, gear notes, or anything else you want the sheet to remember.\nUse `random that fits` if you want a rerollable starter note.",
            default => 'Set '.self::FIELD_LABELS[$field]." from 3 to 18, or type `roll stats` to fill all six scores at once.\nThis is part of the handbook ability-score step.\n".$this->abilityPromptHelp($field),
        };
    }

    // Developer context: Speciesguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function speciesGuidance(string $species): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return config("dnd.species_details.{$species}.summary") ?: 'Species mostly affects flavor, movement, and innate traits.';
    }

    // Developer context: Classguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function classGuidance(string $class): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $summary = config("dnd.class_details.{$class}.summary") ?: 'Class drives your main features and playstyle.';
        $focus = config("dnd.class_details.{$class}.primary_focus", []);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_array($focus) && $focus !== []) {
            $summary .= ' Main abilities often include '.implode(' and ', $focus).'.';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $summary;
    }

    // Developer context: Backgroundguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function backgroundGuidance(string $background): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return config("dnd.background_details.{$background}.summary") ?: 'Background mainly supports story identity and life before adventuring.';
    }

    // Developer context: Advancementmethodguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function advancementMethodGuidance(string $method): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $summary = config("dnd.advancement_method_details.{$method}.summary") ?: 'This is how the table expects leveling to happen.';
        $playNote = config("dnd.advancement_method_details.{$method}.play_note");

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return is_string($playNote) && $playNote !== ''
            ? $summary.' '.$playNote
            : $summary;
    }

    // Developer context: Skillproficiencyguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function skillProficiencyGuidance(array $character): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $guidance = $this->skillProficiencyPrompt($character);
        $expertise = is_array($character['skill_expertise'] ?? null) && $character['skill_expertise'] !== []
            ? ' Expertise already marked on '.implode(', ', $character['skill_expertise']).'.'
            : '';

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return trim($guidance.$expertise);
    }

    // Developer context: Skillproficiencyprompt handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function skillProficiencyPrompt(array $character): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $class = $character['class'] ?? null;
        $choiceCount = $this->classSkillChoiceCount($class);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $options = $this->classSkillOptions($class);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($class && $choiceCount !== null && $options !== []) {
            $displayOptions = count($options) > 8
                ? implode(', ', array_slice($options, 0, 8)).', and more'
                : implode(', ', $options);

            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return sprintf(
                '%s usually starts with %d skill choice%s from: %s. I do not hard-lock table variations, but this is the local class reference.',
                $class,
                $choiceCount,
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $choiceCount === 1 ? '' : 's',
                $displayOptions,
            );
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($class) {
            return "{$class} skill training is tracked here. Pick the skills the sheet should treat as proficient.";
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return 'Pick the skills the sheet should treat as proficient.';
    }

    // Developer context: Alignmentguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function alignmentGuidance(string $alignment): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $summary = config("dnd.alignment_details.{$alignment}") ?: 'Alignment is a roleplay cue, not a hard rule.';
        $playWell = config("dnd.alignment_roleplay.{$alignment}.play_well");
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $watchOut = config("dnd.alignment_roleplay.{$alignment}.watch_out");

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [$summary];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_string($playWell) && $playWell !== '') {
            $lines[] = 'Play it well: '.$playWell;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_string($watchOut) && $watchOut !== '') {
            $lines[] = 'Watch out for: '.$watchOut;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return implode("\n", $lines);
    }

    // Developer context: Roleplayfieldguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function roleplayFieldGuidance(string $field, array $character): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $help = config("dnd.roleplay_field_help.{$field}") ?: 'A short line is enough.';
        $starter = $this->combinedRoleplayStarter($character);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return match ($field) {
            'personality_traits' => ($starter['trait'] ?? '') !== '' ? $help.' Example: '.$starter['trait'] : $help,
            'ideals' => ($starter['ideal'] ?? '') !== '' ? $help.' Example: '.$starter['ideal'] : $help,
            'goals' => ($starter['goal'] ?? '') !== '' ? $help.' Example: '.$starter['goal'] : $help,
            'bonds' => ($starter['bond'] ?? '') !== '' ? $help.' Example: '.$starter['bond'] : $help,
            'flaws' => ($starter['flaw'] ?? '') !== '' ? $help.' Example: '.$starter['flaw'] : $help,
            default => $help,
        };
    }

    // Developer context: Appearancefieldguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function appearanceFieldGuidance(string $field, array $character): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $help = config("dnd.appearance_field_help.{$field}") ?: 'A short descriptive note is enough.';
        $cues = $this->appearanceCueLines($character);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $cues !== []
            ? $help.' Cue: '.$cues[0]
            : $help;
    }

    // Developer context: Roleplaystarterprompts handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function roleplayStarterPrompts(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $starter = $this->combinedRoleplayStarter($character);

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $prompts = [
            $starter['trait'] ?? null,
            $starter['ideal'] ?? null,
            $starter['goal'] ?? null,
            $starter['bond'] ?? null,
            $starter['flaw'] ?? null,
        ];

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_filter(array_map(
            static fn ($entry): ?string => is_string($entry) && $entry !== '' ? $entry : null,
            $prompts,
        )));
    }

    // Developer context: Roleplayreferencelines handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function roleplayReferenceLines(): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $reference = config('dnd.roleplay_reference', []);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_filter(array_map(
            static fn (mixed $entry): ?string => is_array($entry) && is_string($entry['summary'] ?? null) && $entry['summary'] !== ''
                ? $entry['summary']
                : null,
            is_array($reference) ? $reference : [],
        )));
    }

    // Developer context: Combinedroleplaystarter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function combinedRoleplayStarter(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $class = is_string($character['class'] ?? null) ? $character['class'] : '';
        $species = is_string($character['species'] ?? null) ? $character['species'] : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $background = is_string($character['background'] ?? null) ? $character['background'] : '';
        $alignment = is_string($character['alignment'] ?? null) ? $character['alignment'] : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $originFeat = is_string($character['origin_feat'] ?? null) ? $character['origin_feat'] : '';
        $advancementMethod = is_string($character['advancement_method'] ?? null) ? $character['advancement_method'] : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $languageValues = array_values(array_filter(
            is_array($character['languages'] ?? null) ? $character['languages'] : [],
            static fn ($entry): bool => is_string($entry) && trim($entry) !== '',
        ));
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $skillValues = array_values(array_filter(
            is_array($character['skill_proficiencies'] ?? null) ? $character['skill_proficiencies'] : [],
            static fn ($entry): bool => is_string($entry) && trim($entry) !== '',
        ));

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $classDetail = is_array(config("dnd.class_details.{$class}")) ? config("dnd.class_details.{$class}") : [];
        $speciesDetail = is_array(config("dnd.species_details.{$species}")) ? config("dnd.species_details.{$species}") : [];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $backgroundDetail = is_array(config("dnd.background_details.{$background}")) ? config("dnd.background_details.{$background}") : [];
        $alignmentDetail = is_string(config("dnd.alignment_details.{$alignment}")) ? config("dnd.alignment_details.{$alignment}") : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $alignmentRoleplay = is_array(config("dnd.alignment_roleplay.{$alignment}")) ? config("dnd.alignment_roleplay.{$alignment}") : [];
        $originFeatDetail = is_string(config("dnd.origin_feat_details.{$originFeat}")) ? config("dnd.origin_feat_details.{$originFeat}") : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $advancementDetail = is_array(config("dnd.advancement_method_details.{$advancementMethod}")) ? config("dnd.advancement_method_details.{$advancementMethod}") : [];

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $focusAbilities = is_array($classDetail['primary_focus'] ?? null) ? $classDetail['primary_focus'] : [];
        $speciesTraits = is_array($speciesDetail['traits'] ?? null) ? array_slice($speciesDetail['traits'], 0, 2) : [];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $backgroundTheme = is_string($backgroundDetail['theme'] ?? null) ? Str::lower($backgroundDetail['theme']) : '';
        $advancementCue = is_string($advancementDetail['roleplay_cue'] ?? null) ? $advancementDetail['roleplay_cue'] : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $speciesTraitText = $speciesTraits !== []
            ? Str::lower($this->naturalJoin($speciesTraits))
            : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $trainedSkillText = $skillValues !== []
            ? Str::lower($this->naturalJoin(array_slice($skillValues, 0, 2)))
            : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $extremes = $this->abilityExtremes($character);
        $highestLabel = $extremes['highest'];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lowestLabel = $extremes['lowest'];

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $titleParts = array_values(array_filter([$alignment, $species, $background, $class]));
        $sourceParts = array_values(array_filter([
            $alignment !== '' ? "alignment ({$alignment})" : '',
            $species !== '' ? "species ({$species})" : '',
            $background !== '' ? "background ({$background})" : '',
            $class !== '' ? "class ({$class})" : '',
            $originFeat !== '' ? "origin feat ({$originFeat})" : '',
            $advancementMethod !== '' ? "advancement ({$advancementMethod})" : '',
            $highestLabel !== null ? Str::lower($highestLabel).' as the strongest score' : '',
        ]));

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $focusText = $focusAbilities !== []
            ? $this->naturalJoin(array_map(static fn (string $ability): string => Str::lower($ability), $focusAbilities))
            : '';
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $languageText = $languageValues !== []
            ? $this->naturalJoin(array_map(static fn (string $language): string => Str::lower($language), $languageValues))
            : '';
        $advancementSummary = $this->advancementRoleplaySummary($advancementMethod, $advancementCue);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'title' => $titleParts !== []
                ? implode(' ', $titleParts).' roleplay starter'
                : 'Beginner roleplay help',
            'summary' => $this->joinSentences([
                $titleParts !== [] ? 'This build reads like a '.implode(' ', $titleParts).'.' : '',
                is_string($alignmentRoleplay['play_well'] ?? null) && $alignmentRoleplay['play_well'] !== ''
                    ? $alignmentRoleplay['play_well']
                    : ($alignmentDetail !== '' ? 'At heart, the character '.$this->lowerFirst($alignmentDetail).'.' : ''),
                $backgroundTheme !== '' ? "{$background} gives their choices a {$backgroundTheme} pull." : '',
                $class !== '' && $focusText !== '' ? "{$class} nudges them toward {$focusText} when things get tense." : '',
                $species !== '' && $speciesTraitText !== '' ? "{$species} leaves a trace in details like {$speciesTraitText}." : '',
                $originFeat !== '' && $originFeatDetail !== '' ? "{$originFeat} shapes the way they first come across: ".rtrim($this->lowerFirst($originFeatDetail), '.').'.' : '',
                $advancementSummary,
            ]),
            'trait' => $this->compactRoleplayStarter(
                is_string($alignmentRoleplay['starter_trait'] ?? null) ? $alignmentRoleplay['starter_trait'] : $this->classDrivenTraitStarter($class),
                [
                    $class !== '' && $focusText !== '' ? 'When pressure hits, my '.Str::lower($class)." instincts push me toward {$focusText}." : ($class !== '' ? 'My '.Str::lower($class).' training still shows whenever the room gets tense.' : ''),
                    $backgroundTheme !== '' ? "Years shaped by {$backgroundTheme} still show in the way I carry myself." : '',
                    $advancementMethod !== '' && $advancementCue !== '' ? 'The way I talk about growth is shaped by '.Str::lower($advancementMethod).': '.$advancementCue : '',
                    $trainedSkillText !== '' ? "People quickly notice that I approach problems through {$trainedSkillText}." : '',
                    $highestLabel !== null ? Str::title(Str::lower($highestLabel)).' is usually the first part of me people notice.' : '',
                    $species !== '' ? ($speciesTraitText !== '' ? 'You can still hear my '.Str::lower($species)." roots in the {$speciesTraitText} side of me." : 'Being '.Str::lower($species).' still shapes how I come across to other people.') : '',
                ],
                'Short first-impression notes like calm, curious, or dry humor.',
            ),
            'ideal' => $this->compactRoleplayStarter(
                is_string($alignmentRoleplay['starter_ideal'] ?? null) ? $alignmentRoleplay['starter_ideal'] : ($backgroundTheme !== '' ? "{$backgroundTheme} matters more than comfort." : 'I want to live by a principle that actually means something.'),
                [
                    $class !== '' ? 'Being a '.Str::lower($class).' keeps asking what my gifts are actually for.' : '',
                    $backgroundTheme !== '' ? "To me, {$backgroundTheme} only matters if it means something in the real world." : '',
                    $originFeat !== '' ? "{$originFeat} feels like something I should use with purpose, not vanity." : '',
                    $advancementMethod !== '' ? 'I measure progress through '.Str::lower($advancementMethod).' rather than chasing empty motion.' : '',
                ],
                'Pick a principle that feels worth protecting.',
            ),
            'goal' => $this->compactRoleplayStarter(
                $backgroundTheme !== '' ? "I want to turn my {$backgroundTheme} past into something that still matters." : 'I want to accomplish something that will still matter after this adventure ends.',
                [
                    $class !== '' ? 'Part of me wants to prove what a '.Str::lower($class).' can really accomplish.' : '',
                    $originFeat !== '' ? "{$originFeat} feels like the start of something I am meant to build on." : '',
                    $advancementMethod !== '' ? 'I want my progress to feel earned through '.Str::lower($advancementMethod).'.' : '',
                    $highestLabel !== null ? 'My strongest '.Str::lower($highestLabel).' keeps pulling me toward that goal.' : '',
                ],
                'What are they trying to achieve, protect, prove, or uncover next?',
            ),
            'bond' => $this->compactRoleplayStarter(
                is_string($alignmentRoleplay['starter_bond'] ?? null) ? $alignmentRoleplay['starter_bond'] : $this->backgroundDrivenBondStarter($backgroundTheme),
                [
                    $background !== '' ? "{$background} roots still tie me to the people and places that made me." : '',
                    $languageText !== '' ? "Speaking {$languageText} keeps me connected to more than one corner of the world." : '',
                    $species !== '' ? 'Part of me still feels answerable to what it means to be '.Str::lower($species).'.' : '',
                    $originFeat !== '' ? "I still remember who first helped me turn {$originFeat} into something useful." : '',
                    $advancementMethod !== '' ? 'How I grow now is tied to the table through '.Str::lower($advancementMethod).'.' : '',
                ],
                'Who or what matters enough to change their decisions?',
            ),
            'flaw' => $this->compactRoleplayStarter(
                is_string($alignmentRoleplay['starter_flaw'] ?? null) ? $alignmentRoleplay['starter_flaw'] : ($class !== '' ? 'The harder I lean into being a '.Str::lower($class).', the easier it is for one bad habit to take over.' : 'Under pressure, one bad habit can steer the moment.'),
                [
                    $lowestLabel !== null ? 'Under strain, my lower '.Str::lower($lowestLabel).' is usually the first crack to show.' : '',
                    $class !== '' ? 'When I lean too hard on my '.Str::lower($class).' instincts, I can miss gentler answers.' : '',
                    $backgroundTheme !== '' ? "Old habits from a life shaped by {$backgroundTheme} can make me dig in too hard." : '',
                ],
                'Pick a weakness that creates believable trouble without breaking the party.',
            ),
            'watch_out' => is_string($alignmentRoleplay['watch_out'] ?? null) ? $alignmentRoleplay['watch_out'] : '',
            'progression' => $advancementMethod !== '' && $advancementCue !== ''
                ? $advancementMethod.': '.$advancementCue
                : null,
            'sources' => $sourceParts,
        ];
    }

    // Developer context: Advancementroleplaysummary handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function advancementRoleplaySummary(string $method, string $cue): string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($method === '' || $cue === '') {
            return '';
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $trimmedCue = trim($cue);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (str_starts_with(Str::lower($trimmedCue), 'growth feels tied to ')) {
            return $method.' ties growth to '.preg_replace('/^growth feels tied to\s+/i', '', $trimmedCue);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $method.' means '.$this->lowerFirst($trimmedCue);
    }

    // Developer context: Classdriventraitstarter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function classDrivenTraitStarter(string $class): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return match ($class) {
            'Barbarian' => 'Blunt, intense, protective, and hard to intimidate.',
            'Bard' => 'Warm, theatrical, teasing, and always half a story ahead.',
            'Cleric' => 'Steady, observant, compassionate, and quietly certain.',
            'Druid' => 'Grounded, patient, weather-wise, and hard to rush.',
            'Fighter' => 'Disciplined, practical, alert, and built for pressure.',
            'Monk' => 'Calm, focused, restrained, and always measuring the room.',
            'Paladin' => 'Earnest, resolute, inspiring, and impossible to ignore.',
            'Ranger' => 'Watchful, dry-humored, capable, and always tracking something.',
            'Rogue' => 'Quick-eyed, guarded, clever, and never fully off-balance.',
            'Sorcerer' => 'Intense, instinctive, magnetic, and never far from the spark.',
            'Warlock' => 'Measured, uncanny, confident, and carrying a private edge.',
            'Wizard' => 'Curious, precise, distracted, and always connecting patterns.',
            default => 'Short first-impression notes like calm, curious, or dry humor.',
        };
    }

    // Developer context: Backgrounddrivenbondstarter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function backgroundDrivenBondStarter(string $backgroundTheme): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $backgroundTheme !== ''
            ? "A person, place, or promise tied to {$backgroundTheme} still shapes my choices."
            : 'Who or what matters enough to change their decisions?';
    }

    // Developer context: Compactroleplaystarter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function compactRoleplayStarter(?string $base, array $extras, string $fallback = ''): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $parts = array_values(array_filter(array_map(
            static fn ($entry): ?string => is_string($entry) && trim($entry) !== '' ? trim($entry) : null,
            array_merge([$base], $extras),
        )));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($parts === [] && $fallback !== '') {
            $parts[] = $fallback;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->joinSentences($parts, 3);
    }

    // Developer context: Randomfieldvalue centralizes the wizard's fitting-random generators so step-five quick actions can reuse the same logic for preview and reroll flows.
    // Clear explanation: This picks the right random generator for the current step-five field.
    private function randomFieldValue(string $field, array $character): ?string
    {
        return match ($field) {
            'name' => $this->randomCharacterName($character),
            'personality_traits', 'ideals', 'goals', 'bonds', 'flaws' => $this->randomRoleplayFieldValue($field, $character),
            'age', 'height', 'weight', 'eyes', 'hair', 'skin' => $this->randomAppearanceFieldValue($field, $character),
            'notes' => $this->randomNotesValue($character),
            default => null,
        };
    }

    // Developer context: Randomroleplayfieldvalue creates rerollable roleplay suggestions that start from the current build and then let already-kept step-five details influence later suggestions.
    // Clear explanation: This makes a fitting random roleplay line that matches the current character and any earlier detail choices you already kept.
    private function randomRoleplayFieldValue(string $field, array $character): string
    {
        $starter = $this->combinedRoleplayStarter($character);
        $class = is_string($character['class'] ?? null) ? $character['class'] : '';
        $species = is_string($character['species'] ?? null) ? $character['species'] : '';
        $background = is_string($character['background'] ?? null) ? $character['background'] : '';
        $originFeat = is_string($character['origin_feat'] ?? null) ? $character['origin_feat'] : '';
        $advancementMethod = is_string($character['advancement_method'] ?? null) ? $character['advancement_method'] : '';
        $backgroundTheme = is_string(config("dnd.background_details.{$background}.theme")) ? Str::lower((string) config("dnd.background_details.{$background}.theme")) : '';
        $extremes = $this->abilityExtremes($character);
        $highestLabel = $extremes['highest'];
        $lowestLabel = $extremes['lowest'];

        $personality = is_string($character['personality_traits'] ?? null) ? $character['personality_traits'] : '';
        $ideal = is_string($character['ideals'] ?? null) ? $character['ideals'] : '';
        $goal = is_string($character['goals'] ?? null) ? $character['goals'] : '';
        $bond = is_string($character['bonds'] ?? null) ? $character['bonds'] : '';

        return match ($field) {
            'personality_traits' => $this->compactRoleplayStarter(
                $starter['trait'] ?? null,
                $this->randomUniqueChoices(array_values(array_filter([
                    $class !== '' ? 'My '.Str::lower($class).' habits are obvious the moment pressure shows up.' : '',
                    $species !== '' ? 'There is still a very '.Str::lower($species).' edge to the way I move and speak.' : '',
                    $backgroundTheme !== '' ? "Years shaped by {$backgroundTheme} still show before I even finish a sentence." : '',
                    $originFeat !== '' ? "{$originFeat} is part of why I come across as ready before I even explain myself." : '',
                    $highestLabel !== null ? "{$highestLabel} is usually the first thing people notice about me." : '',
                ])), 2),
                'Short first-impression notes like calm, curious, or dry humor.',
            ),
            'ideals' => $this->compactRoleplayStarter(
                $starter['ideal'] ?? null,
                $this->randomUniqueChoices(array_values(array_filter([
                    $class !== '' ? 'Being a '.Str::lower($class).' keeps forcing me to ask what my gifts are really for.' : '',
                    $backgroundTheme !== '' ? "My {$backgroundTheme} roots only matter if they stand for something real." : '',
                    $originFeat !== '' ? "{$originFeat} should be used with purpose, not just style." : '',
                    $advancementMethod !== '' ? 'Even the way I grow through '.Str::lower($advancementMethod).' should point back to something I believe in.' : '',
                    $personality !== '' ? 'Whatever people notice first about me, I want it to point toward something worth standing for.' : '',
                ])), 2),
                'I want to live by a principle that actually means something.',
            ),
            'goals' => $this->compactRoleplayStarter(
                $starter['goal'] ?? null,
                $this->randomUniqueChoices(array_values(array_filter([
                    $class !== '' ? 'Part of me wants to prove what a '.Str::lower($class).' can really accomplish when it matters.' : '',
                    $originFeat !== '' ? "{$originFeat} feels like the start of something I am meant to build on." : '',
                    $highestLabel !== null ? 'My strongest '.Str::lower($highestLabel).' keeps pulling me toward that next big aim.' : '',
                    $ideal !== '' ? 'If I claim an ideal, I should chase something that proves I mean it.' : '',
                    $personality !== '' ? 'Even my first impression should line up with the direction I am trying to move.' : '',
                ])), 2),
                'I want to accomplish something that will still matter after this adventure ends.',
            ),
            'bonds' => $this->compactRoleplayStarter(
                $starter['bond'] ?? null,
                $this->randomUniqueChoices(array_values(array_filter([
                    $background !== '' ? "{$background} still ties me to the people and places that first shaped me." : '',
                    $species !== '' ? 'Part of me still feels answerable to what it means to be '.Str::lower($species).'.' : '',
                    $originFeat !== '' ? "I still remember who first helped me turn {$originFeat} into something real." : '',
                    $goal !== '' ? 'The next big thing I want still circles back to someone or something I cannot shrug off.' : '',
                    $ideal !== '' ? 'My ideals matter more once they are tied to someone I can actually lose.' : '',
                ])), 2),
                'Who or what matters enough to change their decisions?',
            ),
            'flaws' => $this->compactRoleplayStarter(
                $starter['flaw'] ?? null,
                $this->randomUniqueChoices(array_values(array_filter([
                    $lowestLabel !== null ? 'Under pressure, my lower '.Str::lower($lowestLabel).' is usually the first crack to show.' : '',
                    $class !== '' ? 'When I lean too hard on my '.Str::lower($class).' instincts, I can miss gentler answers.' : '',
                    $backgroundTheme !== '' ? "The old {$backgroundTheme} reflex in me can take over before I slow down." : '',
                    $bond !== '' ? 'The more something matters to me, the easier it is for my worst habits to take over around it.' : '',
                    $goal !== '' ? 'Chasing what I want can make me push too hard or overlook the cost.' : '',
                ])), 2),
                'Under pressure, one bad habit can steer the moment.',
            ),
            default => '',
        };
    }

    // Developer context: Randomappearancefieldvalue uses the local species placeholder profiles as a suggestion pool so rerolls feel lore-aware without hard-limiting custom input.
    // Clear explanation: This picks a fitting random appearance detail based on the current species examples.
    private function randomAppearanceFieldValue(string $field, array $character): string
    {
        $species = is_string($character['species'] ?? null) ? $character['species'] : '';
        $defaultProfile = is_array(config('dnd.form_placeholder_profiles.default')) ? config('dnd.form_placeholder_profiles.default') : [];
        $speciesProfile = is_array(config("dnd.form_placeholder_profiles.species.{$species}")) ? config("dnd.form_placeholder_profiles.species.{$species}") : [];
        $profile = array_merge($defaultProfile, $speciesProfile);

        return $this->randomFromSuggestion((string) ($profile[$field] ?? ''), match ($field) {
            'age' => '25',
            'height' => '173 cm',
            'weight' => '72 kg',
            'eyes' => 'Gray',
            'hair' => 'Dark braid',
            'skin' => 'Weathered',
            default => 'Unspecified',
        });
    }

    // Developer context: Randomcharactername picks a suggestion from the same lore-flavored profile data the form placeholders already use, which keeps wizard naming aligned with the rest of the builder.
    // Clear explanation: This picks a fitting random name based on the current species examples.
    private function randomCharacterName(array $character): string
    {
        $species = is_string($character['species'] ?? null) ? $character['species'] : '';
        $speciesProfile = is_array(config("dnd.form_placeholder_profiles.species.{$species}")) ? config("dnd.form_placeholder_profiles.species.{$species}") : [];

        return $this->randomFromSuggestion((string) ($speciesProfile['name'] ?? ''), ($species !== '' ? "{$species} Wanderer" : 'New Adventurer'));
    }

    // Developer context: Randomnotesvalue turns the current sheet into a short reminder note so the last optional field can also benefit from fitting rerolls.
    // Clear explanation: This makes a fitting starter note from the current build and any details already chosen.
    private function randomNotesValue(array $character): string
    {
        $name = is_string($character['name'] ?? null) && $character['name'] !== '' ? $character['name'] : 'This character';
        $class = is_string($character['class'] ?? null) ? $character['class'] : '';
        $subclass = is_string($character['subclass'] ?? null) ? $character['subclass'] : '';
        $background = is_string($character['background'] ?? null) ? $character['background'] : '';
        $advancementMethod = is_string($character['advancement_method'] ?? null) ? $character['advancement_method'] : '';
        $goal = is_string($character['goals'] ?? null) ? $character['goals'] : '';
        $bond = is_string($character['bonds'] ?? null) ? $character['bonds'] : '';
        $languages = array_values(array_filter(
            is_array($character['languages'] ?? null) ? $character['languages'] : [],
            static fn ($entry): bool => is_string($entry) && trim($entry) !== '',
        ));

        $base = trim(implode(' ', array_filter([
            $name,
            'is',
            is_int($character['level'] ?? null) ? 'Level '.$character['level'] : null,
            $subclass !== '' ? $subclass : null,
            $class !== '' ? $class : null,
            $background !== '' ? 'with a '.Str::lower($background).' background.' : null,
        ])));

        return $this->joinSentences(array_filter([
            $base !== '' ? $base : null,
            $advancementMethod !== '' ? 'The table uses '.Str::lower($advancementMethod).' advancement.' : null,
            $languages !== [] ? 'Speaks '.$this->naturalJoin($languages).'.' : null,
            $goal !== '' ? 'Current goal: '.$goal : null,
            $bond !== '' ? 'Important tie: '.$bond : null,
        ]), 4);
    }

    // Developer context: Randomchoice and its helpers keep all wizard rerolls using real randomness without scattering small array-picking logic across the service.
    // Clear explanation: These helper methods let the wizard pick and reroll fitting random suggestions.
    private function randomChoice(array $items): ?string
    {
        $items = array_values(array_filter(array_map(
            static fn ($entry): ?string => is_string($entry) && trim($entry) !== '' ? trim($entry) : null,
            $items,
        )));

        if ($items === []) {
            return null;
        }

        return $items[random_int(0, count($items) - 1)];
    }

    private function randomUniqueChoices(array $items, int $count): array
    {
        $pool = array_values(array_filter(array_map(
            static fn ($entry): ?string => is_string($entry) && trim($entry) !== '' ? trim($entry) : null,
            $items,
        )));
        $picks = [];

        while ($pool !== [] && count($picks) < $count) {
            $index = random_int(0, count($pool) - 1);
            $picks[] = $pool[$index];
            array_splice($pool, $index, 1);
        }

        return $picks;
    }

    private function splitSuggestionPool(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $entry): ?string => ($clean = trim(preg_replace('/^or\s+/i', '', $entry) ?? '')) !== '' ? $clean : null,
            explode(',', str_replace('...', '', $value)),
        )));
    }

    private function randomFromSuggestion(string $value, string $fallback = ''): string
    {
        return $this->randomChoice($this->splitSuggestionPool($value)) ?? $fallback;
    }

    // Developer context: Joinsentences handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function joinSentences(array $parts, ?int $limit = null): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $cleanParts = array_values(array_filter(array_map(
            static fn ($entry): ?string => is_string($entry) && trim($entry) !== '' ? trim($entry) : null,
            $parts,
        )));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($limit !== null) {
            $cleanParts = array_slice($cleanParts, 0, $limit);
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return implode(' ', $cleanParts);
    }

    // Developer context: Naturaljoin handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function naturalJoin(array $items): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $cleanItems = array_values(array_filter(array_map(
            static fn ($entry): ?string => is_string($entry) && trim($entry) !== '' ? trim($entry) : null,
            $items,
        )));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return match (count($cleanItems)) {
            0 => '',
            1 => $cleanItems[0],
            2 => $cleanItems[0].' and '.$cleanItems[1],
            default => implode(', ', array_slice($cleanItems, 0, -1)).', and '.$cleanItems[array_key_last($cleanItems)],
        };
    }

    // Developer context: Lowerfirst handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function lowerFirst(string $value): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return Str::lcfirst(trim($value));
    }

    // Developer context: Abilityextremes handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function abilityExtremes(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $scores = [];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach (self::STAT_FIELDS as $field) {
            if ($character[$field] !== null) {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $scores[$field] = (int) $character[$field];
            }
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($scores === []) {
            return ['highest' => null, 'lowest' => null];
        }

        arsort($scores);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $highestField = array_key_first($scores);
        asort($scores);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lowestField = array_key_first($scores);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'highest' => $highestField !== null ? self::FIELD_LABELS[$highestField] : null,
            'lowest' => $lowestField !== null ? self::FIELD_LABELS[$lowestField] : null,
        ];
    }

    // Developer context: Appearancecuelines handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function appearanceCueLines(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $scores = [];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach (self::STAT_FIELDS as $field) {
            if ($character[$field] !== null) {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $scores[$field] = (int) $character[$field];
            }
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($scores === []) {
            return [];
        }

        arsort($scores);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $highestField = array_key_first($scores);
        asort($scores);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lowestField = array_key_first($scores);

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $lines = [];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($highestField !== null) {
            $highestLabel = self::FIELD_LABELS[$highestField];
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $highWords = config("dnd.ability_appearance_cues.{$highestLabel}.high", []);
            if (is_array($highWords) && $highWords !== []) {
                $lines[] = "{$highestLabel} leans high, so cues like ".implode(', ', $highWords).' fit naturally.';
            }
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($lowestField !== null && $lowestField !== $highestField) {
            $lowestLabel = self::FIELD_LABELS[$lowestField];
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $lowWords = config("dnd.ability_appearance_cues.{$lowestLabel}.low", []);
            if (is_array($lowWords) && $lowWords !== []) {
                $lines[] = "{$lowestLabel} is the softer edge, so cues like ".implode(', ', $lowWords).' can add texture.';
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $lines;
    }

    // Developer context: Abilityprompthelp handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function abilityPromptHelp(string $field): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $label = self::FIELD_LABELS[$field] ?? Str::title($field);
        $summary = config("dnd.ability_details.{$label}");

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return is_string($summary) && $summary !== ''
            ? $summary
            : 'Higher scores make related checks, attacks, or spellcasting better.';
    }

    // Developer context: Abilityguidance handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function abilityGuidance(string $field, int $score, array $character): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $modifier = $this->formatModifier($this->abilityModifier($score));
        $guidance = $this->abilityPromptHelp($field);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $focus = is_string($character['class'] ?? null)
            ? config("dnd.class_details.{$character['class']}.primary_focus", [])
            : [];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $isFocus = is_array($focus) && in_array(self::FIELD_LABELS[$field], $focus, true);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return trim($guidance.' Modifier '.$modifier.'.'.($isFocus ? ' This is one of the main abilities for the chosen class.' : ''));
    }

    // Developer context: Hasallabilityscores checks whether the wizard already holds a full six-stat set so later steps can offer a reroll shortcut without guessing.
    // Clear explanation: This checks whether all six ability scores are already filled in.
    private function hasAllAbilityScores(array $character): bool
    {
        foreach (self::STAT_FIELDS as $field) {
            if (! is_int($character[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    // Developer context: Quickactionsforfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function quickActionsForField(string $field, array $state): array
    {
        $preview = is_array($state['random_preview'] ?? null) ? $state['random_preview'] : null;

        if (($preview['kind'] ?? null) === 'field' && ($preview['field'] ?? null) === $field) {
            $actions = ['keep this', 'reroll random'];

            if (in_array($field, ['personality_traits', 'ideals', 'goals', 'bonds', 'flaws'], true)) {
                $actions[] = 'help me roleplay';
            }

            if (in_array($field, ['age', 'height', 'weight', 'eyes', 'hair', 'skin'], true)) {
                $actions[] = 'show appearance help';
            }

            if ($this->isOptionalField($field)) {
                $actions[] = 'skip';
                $actions[] = 'skip all details';
            }

            return array_values(array_unique(array_filter($actions)));
        }

        if (($preview['kind'] ?? null) === 'stats') {
            return ['keep these scores', 'reroll ability scores'];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $actions = match ($field) {
            'species' => config('dnd.species', []),
            'class' => config('dnd.classes', []),
            'advancement_method' => config('dnd.advancement_methods', []),
            'subclass' => config("dnd.class_details.{$state['character']['class']}.subclasses", []),
            'skill_proficiencies' => $this->skillProficiencyQuickActions($state['character']),
            'skill_expertise' => $this->skillExpertiseQuickActions($state['character']),
            'background' => config('dnd.backgrounds', []),
            'alignment' => config('dnd.alignments', []),
            'origin_feat' => config('dnd.origin_feats', []),
            'languages' => ['Common, Elvish', 'Common, Dwarvish', 'Common, Draconic'],
            'name' => ['random that fits'],
            'personality_traits', 'ideals', 'goals', 'bonds', 'flaws' => ['random that fits', 'help me roleplay'],
            'age', 'height', 'weight', 'eyes', 'hair', 'skin' => ['random that fits', 'show appearance help'],
            'notes' => ['random that fits'],
            'level' => ['1', '3', '5', '10'],
            default => in_array($field, self::STAT_FIELDS, true) ? ['roll stats'] : [],
        };

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($this->isOptionalField($field)) {
            array_unshift($actions, 'skip');
            $actions[] = 'skip all details';
        }

        if (
            in_array($field, array_merge(['alignment'], self::RANDOMIZABLE_STEP_FIVE_FIELDS), true)
            && $this->hasAllAbilityScores($state['character'])
        ) {
            $actions[] = 'reroll ability scores';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_unique(array_filter($actions)));
    }

    // Developer context: Defaultquickactions handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function defaultQuickActions(array $state): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($state['pending_field'] !== null) {
            return $this->quickActionsForField($state['pending_field'], $state);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
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

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return ['new character', 'list characters', 'load latest', 'help'];
    }

    // Developer context: Helpmessage handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function helpMessage(): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return implode("\n", [
            'Wizard commands:',
            '- You do not need to memorize everything. Use the quick buttons when they appear.',
            '- new character follows the handbook flow: class and class-side training, origin, ability scores, alignment, then details',
            '- choose how leveling works at your table with `set advancement method milestone` or another listed option',
            '- core sheet mechanics cannot be skipped; expertise, roleplay, appearance, and notes can',
            '- new character',
            '- list characters',
            '- load character 1',
            '- load latest',
            '- set class wizard',
            '- set level 5',
            '- set advancement method milestone',
            '- set skills perception, survival',
            '- set expertise stealth',
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
            '- class-side training: set skills perception, survival / set expertise stealth',
            '- campaign pacing: set advancement method experience points / set advancement method milestone',
            '- core origin details: set origin feat alert / set languages common, elvish',
            '- optional roleplay setup: set alignment lawful good',
            '- optional roleplay details: set personality traits curious but blunt / set ideals freedom / set goals map the haunted coast / set bonds my sister / set flaws reckless',
            '- optional appearance details: set age 23 / set height 173 cm / set weight 72 kg / set eyes gray / set hair black braid / set skin olive with freckles',
            '- optional notes: set notes owes the thieves guild a favor',
            '- save character',
        ]);
    }

    // Developer context: Parseskilllist handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function parseSkillList(string $value): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $matches = array_values(array_filter(array_map(
            function (string $entry): ?string {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $match = $this->matchOption($entry, config('dnd.skills', []));

                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return $match ?: null;
            },
            preg_split('/[,|\n\r]+/', $value) ?: [],
        )));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_unique($matches));
    }

    // Developer context: Classskillchoicecount handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function classSkillChoiceCount(?string $class): ?int
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $guidance = is_string($class) ? config("dnd_progressions.classes.{$class}.traits.skill_proficiencies") : null;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($guidance) || preg_match('/choose(?: any)?\s+(\d+)/i', $guidance, $matches) !== 1) {
            return null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return (int) $matches[1];
    }

    // Developer context: Classskilloptions handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function classSkillOptions(?string $class): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $allSkills = config('dnd.skills', []);
        $guidance = is_string($class) ? config("dnd_progressions.classes.{$class}.traits.skill_proficiencies") : null;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($guidance) || $guidance === '') {
            return $allSkills;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (Str::contains(Str::lower($guidance), 'choose any')) {
            return $allSkills;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $matches = array_values(array_filter($allSkills, static function (string $skill) use ($guidance): bool {
            return Str::contains($guidance, $skill);
        }));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $matches !== [] ? $matches : $allSkills;
    }

    // Developer context: Skillproficiencyquickactions handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function skillProficiencyQuickActions(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $options = $this->classSkillOptions($character['class'] ?? null);
        $count = $this->classSkillChoiceCount($character['class'] ?? null) ?? min(2, max(1, count($options)));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($options === []) {
            return [];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $combinations = [];
        $slices = [
            array_slice($options, 0, $count),
            array_slice($options, max(0, intdiv(count($options), 2) - intdiv($count, 2)), $count),
            array_slice(array_reverse($options), 0, $count),
        ];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($slices as $slice) {
            if ($slice !== []) {
                $combinations[] = implode(', ', $slice);
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_unique($combinations));
    }

    // Developer context: Skillexpertisequickactions handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function skillExpertiseQuickActions(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $proficiencies = array_values(array_filter(
            is_array($character['skill_proficiencies'] ?? null) ? $character['skill_proficiencies'] : [],
            static fn ($entry): bool => is_string($entry) && $entry !== '',
        ));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($proficiencies === []) {
            return [];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $actions = [
            $proficiencies[0],
            isset($proficiencies[1]) ? implode(', ', array_slice($proficiencies, 0, 2)) : null,
            count($proficiencies) > 2 ? implode(', ', array_slice($proficiencies, -2)) : null,
        ];

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return array_values(array_unique(array_filter($actions)));
    }

    // Developer context: Matchoption handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function matchOption(string $input, array $options): ?string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $input = Str::of($input)->lower()->squish()->toString();

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($options as $option) {
            if (Str::of((string) $option)->lower()->toString() === $input) {
                // Developer context: This return hands the finished value or response back to the caller.
                // Clear explanation: This line sends the result back so the rest of the app can use it.
                return (string) $option;
            }
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $prefixMatches = array_values(array_filter($options, static function (string $option) use ($input): bool {
            return Str::startsWith(Str::of($option)->lower()->toString(), $input);
        }));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (count($prefixMatches) === 1) {
            return $prefixMatches[0];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $containsMatches = array_values(array_filter($options, static function (string $option) use ($input): bool {
            return Str::contains(Str::of($option)->lower()->toString(), $input);
        }));

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return count($containsMatches) === 1 ? $containsMatches[0] : null;
    }

    // Developer context: Missingfields handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function missingFields(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $missing = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! $this->fieldHasValue($character[$field] ?? null)) {
                $missing[] = $field;
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $missing;
    }

    // Developer context: Nextguidedfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function nextGuidedField(array $state): ?string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $skippedOptionalFields = $state['skipped_optional_fields'] ?? [];

        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach (self::GUIDED_FIELDS as $field) {
            if ($this->isOptionalField($field) && in_array($field, $skippedOptionalFields, true)) {
                continue;
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! $this->fieldHasValue($state['character'][$field] ?? null)) {
                return $field;
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return null;
    }

    // Developer context: Fieldhasvalue handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function fieldHasValue(mixed $value): bool
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_array($value)) {
            return $value !== [];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_string($value)) {
            return trim($value) !== '';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $value !== null;
    }

    // Developer context: Isoptionalfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function isOptionalField(string $field): bool
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return in_array($field, self::OPTIONAL_FIELDS, true);
    }

    // Developer context: Normalizestate handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeState(array $state): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $sanitized = $this->wizardStateSanitizer->sanitize($state);
        $character = array_replace($this->blankCharacter(), $sanitized['character']);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character['id'] = $this->normalizeCharacterId($state['character']['id'] ?? null);

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon = array_replace($this->blankDungeon(), $sanitized['dungeon']);

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon = $this->syncDungeonState($character, $dungeon);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'pending_field' => $sanitized['pending_field'],
            'skipped_optional_fields' => $sanitized['skipped_optional_fields'],
            'random_preview' => $sanitized['random_preview'],
            'character' => $character,
            'dungeon' => $dungeon,
        ];
    }

    // Developer context: Blankcharacter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function blankCharacter(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'id' => null,
            'name' => null,
            'species' => null,
            'class' => null,
            'subclass' => null,
            'skill_proficiencies' => null,
            'skill_expertise' => null,
            'background' => null,
            'alignment' => null,
            'origin_feat' => null,
            'advancement_method' => null,
            'languages' => null,
            'personality_traits' => null,
            'ideals' => null,
            'goals' => null,
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

    // Developer context: Blankdungeon handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function blankDungeon(): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'max_hp' => null,
            'current_hp' => null,
            'hp_adjustment' => 0,
            'rolled_hit_points' => false,
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

    // Developer context: Syncdungeonstate handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function syncDungeonState(array $character, array $dungeon): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $estimatedHp = $this->estimatedHitPoints($character);
        $hpAdjustment = (int) ($dungeon['hp_adjustment'] ?? 0);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $oldMaxHp = $dungeon['max_hp'];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($estimatedHp !== null) {
            $computedMaxHp = max(1, $estimatedHp + $hpAdjustment);
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $dungeon['max_hp'] = $computedMaxHp;

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($dungeon['current_hp'] === null || $oldMaxHp === null || (int) $dungeon['current_hp'] === (int) $oldMaxHp) {
                $dungeon['current_hp'] = $computedMaxHp;
            } else {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $dungeon['current_hp'] = min((int) $dungeon['current_hp'], $computedMaxHp);
            }
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($character['dexterity'] !== null) {
            $dexModifier = $this->abilityModifier((int) $character['dexterity']);
            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($dungeon['ac'] === null) {
                $dungeon['ac'] = 10 + $dexModifier;
            }
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $dungeon['initiative_bonus'] = $dexModifier;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($character['level'] !== null) {
            $dungeon['hit_dice_remaining'] = $dungeon['hit_dice_remaining'] === null
                ? (int) $character['level']
                : min((int) $dungeon['hit_dice_remaining'], (int) $character['level']);
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $maximumSlots = $this->maxSpellSlotsForCharacter($character);
        if ($maximumSlots === []) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $dungeon['spell_slots_remaining'] = [];
        } else {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $remaining = [];
            foreach ($maximumSlots as $level => $count) {
                // Developer context: This assignment stores a working value that the next lines reuse.
                // Clear explanation: This line saves a piece of information so the next steps can keep using it.
                $previous = $dungeon['spell_slots_remaining'][(string) $level] ?? $dungeon['spell_slots_remaining'][$level] ?? $count;
                $remaining[(string) $level] = min((int) $previous, $count);
            }
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $dungeon['spell_slots_remaining'] = $remaining;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon['conditions'] = array_values(array_unique(array_filter(
            is_array($dungeon['conditions']) ? $dungeon['conditions'] : [],
            static fn ($value): bool => is_string($value) && $value !== '',
        )));

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon['exhaustion'] = max(0, min(6, (int) $dungeon['exhaustion']));
        $dungeon['temp_hp'] = max(0, (int) $dungeon['temp_hp']);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon['hp_adjustment'] = (int) ($dungeon['hp_adjustment'] ?? 0);
        $dungeon['rolled_hit_points'] = (bool) ($dungeon['rolled_hit_points'] ?? false);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon['death_successes'] = max(0, min(3, (int) $dungeon['death_successes']));
        $dungeon['death_failures'] = max(0, min(3, (int) $dungeon['death_failures']));
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $dungeon['stable'] = (bool) $dungeon['stable'];
        $dungeon['concentration'] = is_string($dungeon['concentration']) && $dungeon['concentration'] !== '' ? $dungeon['concentration'] : null;

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ((int) ($dungeon['current_hp'] ?? 0) > 0) {
            $dungeon['death_successes'] = 0;
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $dungeon['death_failures'] = 0;
            $dungeon['stable'] = false;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $dungeon;
    }

    // Developer context: Charactertostate handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function characterToState(Character $character): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [
            'id' => $character->id,
            'name' => $character->name,
            'species' => $character->species,
            'class' => $character->class,
            'subclass' => $character->subclass,
            'skill_proficiencies' => $character->skill_proficiencies,
            'skill_expertise' => $character->skill_expertise,
            'background' => $character->background,
            'alignment' => $character->alignment,
            'origin_feat' => $character->origin_feat,
            'advancement_method' => $character->advancement_method,
            'languages' => $character->languages,
            'personality_traits' => $character->personality_traits,
            'ideals' => $character->ideals,
            'goals' => $character->goals,
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

    // Developer context: Markasdraft handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function markAsDraft(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character['id'] = null;

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $character;
    }

    // Developer context: Hascharacterdata handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function hasCharacterData(array $character): bool
    {
        // Developer context: This loop applies the same step to each entry in the current list.
        // Clear explanation: This line repeats the same work for every item in a group.
        foreach ($character as $key => $value) {
            if ($key === 'id' || $key === 'notes') {
                continue;
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($this->fieldHasValue($value)) {
                return true;
            }
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return false;
    }

    // Developer context: Normalizecharacterdraft handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeCharacterDraft(array $character): array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = array_replace(
            $this->blankCharacter(),
            $this->characterDataValidator->normalizeDraft($character),
        );

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized['id'] = $this->normalizeCharacterId($character['id'] ?? null);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $normalized;
    }

    // Developer context: Normalizecharacterid handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeCharacterId(mixed $value): ?int
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($value === null || $value === '') {
            return null;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $id = (int) $value;

            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return $id > 0 ? $id : null;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return null;
    }

    // Developer context: Normalizedtextfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizedTextField(string $field, string $value): ?string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = $this->characterDataValidator->normalizeDraft([
            $field => $value,
        ]);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $normalized[$field] ?? null;
    }

    // Developer context: Normalizeabilityfield handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function normalizeAbilityField(string $input): ?string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $normalized = Str::of($input)->lower()->squish()->toString();

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return self::ABILITY_LOOKUP[$normalized] ?? null;
    }

    // Developer context: Issavingthrowproficient handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function isSavingThrowProficient(array $character, string $abilityField): bool
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $saves = config("dnd_progressions.classes.{$character['class']}.traits.saving_throw_proficiencies");

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($saves) || $saves === '') {
            return false;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $abilityLabel = self::FIELD_LABELS[$abilityField];

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return Str::contains($saves, $abilityLabel);
    }

    // Developer context: Maxspellslotsforcharacter handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function maxSpellSlotsForCharacter(array $character): array
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character['class'] || ! $character['level']) {
            return [];
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);
        if ($entry === null) {
            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return [];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['spell_slots'] ?? []) !== []) {
            return array_map('intval', $entry['spell_slots']);
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['resources']['spell_slots'] ?? null) !== null && ($entry['resources']['slot_level'] ?? null) !== null) {
            return [
                (int) $entry['resources']['slot_level'] => (int) $entry['resources']['spell_slots'],
            ];
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return [];
    }

    // Developer context: Lowestavailablespellslot handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function lowestAvailableSpellSlot(array $remainingSlots, int $minimumLevel): ?int
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $available = [];
        foreach ($remainingSlots as $level => $count) {
            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ((int) $level >= $minimumLevel && (int) $count > 0) {
                $available[] = (int) $level;
            }
        }

        sort($available);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $available[0] ?? null;
    }

    // Developer context: Rolld20 handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollD20(int $modifier, ?string $mode = null): array
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->diceRoller->rollD20($modifier, $mode);
    }

    // Developer context: Proficiencybonus handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function proficiencyBonus(int $level): int
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return (int) floor(($level - 1) / 4) + 2;
    }

    // Developer context: Abilitymodifier handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function abilityModifier(int $score): int
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return (int) floor(($score - 10) / 2);
    }

    // Developer context: Formatmodifier handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function formatModifier(int $modifier): string
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $modifier >= 0 ? "+{$modifier}" : (string) $modifier;
    }

    // Developer context: Estimatedhitpoints handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function estimatedHitPoints(array $character): ?int
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character['class'] || ! $character['level'] || ! $character['constitution']) {
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $hitDie = $this->hitDieForClass((string) $character['class']);
        if ($hitDie === null) {
            // Developer context: This return hands the finished value or response back to the caller.
            // Clear explanation: This line sends the result back so the rest of the app can use it.
            return null;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $conModifier = $this->abilityModifier((int) $character['constitution']);
        $level = (int) $character['level'];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $firstLevel = $hitDie + $conModifier;
        $laterLevels = max(0, $level - 1) * (((int) floor($hitDie / 2)) + 1 + $conModifier);

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $firstLevel + $laterLevels;
    }

    // Developer context: Applyleveluphitpoints handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function applyLevelUpHitPoints(array &$state): string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $character = $state['character'];
        $hitDie = $this->hitDieForClass((string) ($character['class'] ?? ''));

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($hitDie === null) {
            return 'HP gain could not be rolled because this class has no tracked Hit Die.';
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $conModifier = $character['constitution'] === null
            ? 0
            : $this->abilityModifier((int) $character['constitution']);
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $rolled = $this->diceRoller->rollHitPointDice(1, $hitDie, $conModifier);
        $rolledGain = $rolled['total'];
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $fixedGain = max(1, ((int) floor($hitDie / 2)) + 1 + $conModifier);

        $state['dungeon']['hp_adjustment'] = (int) ($state['dungeon']['hp_adjustment'] ?? 0) + ($rolledGain - $fixedGain);
        $state['dungeon']['rolled_hit_points'] = true;

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return sprintf(
            'Rolled HP gain: %s. Fixed gain for this class would have been %d.',
            $rolled['detail'],
            $fixedGain,
        );
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

    // Developer context: Levelentry handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function levelEntry(string $class, int $level): ?array
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = config("dnd_progressions.classes.{$class}.levels.{$level}");

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return is_array($entry) ? $entry : null;
    }

    // Developer context: Displayfeature handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function displayFeature(string $feature, array $character): string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (str_contains($feature, 'Subclass feature')) {
            return $character['subclass'] ? "{$character['subclass']} feature" : 'Subclass feature';
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (str_contains($feature, 'Subclass')) {
            return $character['subclass']
                ? "Subclass choice: {$character['subclass']}"
                : $feature;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $feature;
    }

    // Developer context: Spellclasstag handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function spellClassTag(array $character): ?string
    {
        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $class = $character['class'];
        $subclass = $character['subclass'];

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! is_string($class) || $class === '') {
            return null;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($class, ['Bard', 'Cleric', 'Druid', 'Paladin', 'Ranger', 'Sorcerer', 'Warlock', 'Wizard'], true)) {
            return $class;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($class === 'Fighter' && $subclass === 'Eldritch Knight') {
            return 'Wizard';
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($class === 'Rogue' && $subclass === 'Arcane Trickster') {
            return 'Wizard';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return null;
    }

    // Developer context: Maxspelllevel handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function maxSpellLevel(array $character): int
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! $character['class'] || ! $character['level']) {
            return -1;
        }

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $entry = $this->levelEntry((string) $character['class'], (int) $character['level']);

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($entry === null) {
            return -1;
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['spell_slots'] ?? []) !== []) {
            return max(array_map('intval', array_keys($entry['spell_slots'])));
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (($entry['resources']['slot_level'] ?? null) !== null) {
            return (int) $entry['resources']['slot_level'];
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (in_array($character['subclass'], ['Arcane Trickster', 'Eldritch Knight'], true)) {
            return self::THIRD_CASTER_MAX_SPELL_LEVEL[(int) $character['level']] ?? 0;
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return 0;
    }

    // Developer context: Spelllevellabel handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function spellLevelLabel(int $level, bool $plural = false): string
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if ($level === 0) {
            return $plural ? 'Cantrips' : 'Cantrip';
        }

        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return match ($level) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => "{$level}th",
        };
    }

    // Developer context: Rollabilityscore handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    private function rollAbilityScore(): int
    {
        // Developer context: This return hands the finished value or response back to the caller.
        // Clear explanation: This line sends the result back so the rest of the app can use it.
        return $this->diceRoller->rollAbilityScore();
    }
}
