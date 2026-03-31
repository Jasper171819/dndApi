<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

use App\Models\Character;
use App\Models\DmRecord;
use App\Models\HomebrewEntry;

test('the homepage loads', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee("Adventurer's Ledger", false)
        ->assertSee('Character Builder', false);
});

test('the dm page loads', function () {
    $this->get('/dm')
        ->assertOk()
        ->assertSee('DM Desk', false)
        ->assertSee('Run the table without juggling six tabs.', false)
        ->assertSee('DM Wizard', false);
});

test('the roster page loads', function () {
    $this->get('/roster')
        ->assertOk()
        ->assertSee('Keep the whole party in view.', false)
        ->assertSee('Saved Characters', false);
});

test('the homebrew page loads', function () {
    $this->get('/homebrew')
        ->assertOk()
        ->assertSee('Homebrew Workshop', false)
        ->assertSee('Keep custom ideas separate from the verified sheet.', false);
});

test('the main pages share the same primary navigation shell', function () {
    foreach (['/', '/dm', '/roster', '/homebrew'] as $path) {
        $this->get($path)
            ->assertOk()
            ->assertSee('Builder', false)
            ->assertSee('API', false)
            ->assertSee('DM', false)
            ->assertSee('Roster', false)
            ->assertSee('Homebrew', false)
            ->assertSee('On This Page', false);
    }
});

test('the api root lists the main api entry points', function () {
    $this->getJson('/api')
        ->assertOk()
        ->assertJsonPath('name', "Adventurer's Ledger API")
        ->assertJsonPath('groups.0.title', 'Builder and sheet data')
        ->assertJsonPath('groups.0.routes.0.method', 'GET')
        ->assertJsonPath('groups.0.routes.0.path', '/api/configurator')
        ->assertJsonPath('groups.1.routes.2.path', '/api/rules-wizard/message')
        ->assertJsonPath('groups.2.routes.4.path', '/api/dm-records')
        ->assertJsonPath('processing_trees.2.name', 'Character save')
        ->assertJsonPath('processing_trees.2.tree.1.file', 'app/Http/Requests/StoreCharacterRequest.php')
        ->assertJsonPath('processing_trees.5.tree.4.file', 'app/Services/RulesWizardService.php')
        ->assertJsonPath('processing_trees.6.tree.4.file', 'app/Services/DmWizardService.php')
        ->assertJsonPath('full_reference.0.title', 'Character API')
        ->assertJsonPath('full_reference.0.routes.1.validation.request', 'app/Http/Requests/StoreCharacterRequest.php')
        ->assertJsonPath('full_reference.3.routes.0.validation.state_sanitizer', 'app/Support/RulesWizardStateSanitizer.php')
        ->assertJsonPath('full_reference.4.routes.1.reads_or_writes', 'Reads and writes the dm_records table.')
        ->assertJsonPath('endpoints.0', '/api/configurator')
        ->assertJsonFragment([
            'message' => 'This is the API entry point for characters, dice, wizards, homebrew, and DM records.',
        ]);
});

test('the configurator api returns the local rules payload', function () {
    $this->getJson('/api/configurator')
        ->assertOk()
        ->assertJsonStructure([
            'verified_at',
            'classes',
            'species',
            'backgrounds',
            'origin_feats',
            'compendium_sections',
        ]);
});

test('the compendium section api returns classes', function () {
    $this->getJson('/api/compendium/classes')
        ->assertOk()
        ->assertJsonPath('section.title', 'Classes')
        ->assertJsonCount(12, 'section.items');
});

test('homebrew entries can be saved without changing the official configurator', function () {
    $this->postJson('/api/homebrew', [
        'category' => 'class',
        'status' => 'playtest',
        'name' => '  <strong>Storm Scholar</strong>  ',
        'summary' => "A battlefield researcher who stores thunder in etched rods.\nBuilt for tactical casting.",
        'details' => '<script>nope</script> Keeps a weather log and turns storm patterns into spellwork.',
        'source_notes' => "Inspired by the wizard chassis,\n but tuned for coastal campaigns.",
        'tags' => 'arcane, storm, arcane, coastal',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Storm Scholar')
        ->assertJsonPath('data.summary', "A battlefield researcher who stores thunder in etched rods.\nBuilt for tactical casting.")
        ->assertJsonPath('data.details', 'Keeps a weather log and turns storm patterns into spellwork.')
        ->assertJsonPath('data.source_notes', "Inspired by the wizard chassis,\nbut tuned for coastal campaigns.")
        ->assertJsonPath('data.tags.0', 'arcane')
        ->assertJsonPath('data.tags.1', 'storm')
        ->assertJsonPath('data.tags.2', 'coastal');

    expect(HomebrewEntry::query()->count())->toBe(1);

    $this->getJson('/api/homebrew')
        ->assertOk()
        ->assertJsonCount(1, 'entries')
        ->assertJsonPath('entries.0.category', 'class')
        ->assertJsonPath('entries.0.status', 'playtest');

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $configurator = $this->getJson('/api/configurator')
        ->assertOk()
        ->assertJsonCount(12, 'classes');

    expect($configurator->json('classes'))->not->toContain('Storm Scholar');
});

test('homebrew entries can be updated from the workshop', function () {
    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $entryId = $this->postJson('/api/homebrew', [
        'category' => 'rule',
        'status' => 'draft',
        'name' => 'Night Watch',
        'summary' => 'A simple camp procedure for overnight danger.',
        'details' => 'Each watch rotates every two hours.',
        'tags' => 'rest, travel',
    ])->assertCreated()->json('data.id');

    $this->putJson("/api/homebrew/{$entryId}", [
        'category' => 'rule',
        'status' => 'table-ready',
        'name' => 'Night Watch Revised',
        'summary' => 'A cleaner camp procedure for overnight danger.',
        'details' => 'Each watch rotates every two hours, and the last watcher makes the dawn check.',
        'source_notes' => 'Used in the coastal campaign.',
        'tags' => 'rest, travel, procedure',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Night Watch Revised')
        ->assertJsonPath('data.status', 'table-ready')
        ->assertJsonPath('data.tags.2', 'procedure');

    expect(HomebrewEntry::query()->sole()->name)->toBe('Night Watch Revised');
});

test('the dice api rolls a generic expression', function () {
    $this->postJson('/api/roll-dice', [
        'expression' => '2d6+3',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'expression',
            'mode',
            'total',
            'detail',
        ]);
});

test('the rules wizard short rest rolls actual hit dice', function () {
    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'short rest 2',
        'state' => [
            'character' => [
                'name' => 'Rin',
                'species' => 'Human',
                'class' => 'Fighter',
                'subclass' => 'Champion',
                'skill_proficiencies' => ['Athletics'],
                'skill_expertise' => [],
                'background' => 'Guard',
                'origin_feat' => 'Alert',
                'advancement_method' => 'Milestone',
                'languages' => ['Common'],
                'level' => 3,
                'strength' => 16,
                'dexterity' => 12,
                'constitution' => 14,
                'intelligence' => 10,
                'wisdom' => 10,
                'charisma' => 10,
            ],
            'dungeon' => [
                'max_hp' => 28,
                'current_hp' => 8,
                'hit_dice_remaining' => 2,
            ],
        ],
    ]);

    $response
        ->assertOk()
        ->assertSeeText('Short rest complete.')
        ->assertSeeText('Spent 2 Hit Dice:')
        ->assertSeeText('Recovered');

    expect($response->json('state.dungeon.hit_dice_remaining'))->toBe(0);
    expect($response->json('state.dungeon.current_hp'))->toBeGreaterThan(8);
});

test('the rules wizard level up rolls hit points instead of only using the fixed gain', function () {
    $this->postJson('/api/rules-wizard/message', [
        'message' => 'level up',
        'state' => [
            'character' => [
                'name' => 'Rin',
                'species' => 'Human',
                'class' => 'Fighter',
                'subclass' => 'Champion',
                'skill_proficiencies' => ['Athletics'],
                'skill_expertise' => [],
                'background' => 'Guard',
                'origin_feat' => 'Alert',
                'advancement_method' => 'Milestone',
                'languages' => ['Common'],
                'level' => 1,
                'strength' => 16,
                'dexterity' => 12,
                'constitution' => 14,
                'intelligence' => 10,
                'wisdom' => 10,
                'charisma' => 10,
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('state.character.level', 2)
        ->assertSeeText('Rolled HP gain:')
        ->assertSeeText('Fixed gain for this class would have been');
});

test('character creation above level one stores rolled hit point metadata', function () {
    $this->postJson('/api/characters', [
        'name' => 'Rin',
        'species' => 'Human',
        'class' => 'Fighter',
        'subclass' => 'Champion',
        'skill_proficiencies' => ['Athletics'],
        'skill_expertise' => [],
        'background' => 'Guard',
        'origin_feat' => 'Alert',
        'advancement_method' => 'Milestone',
        'languages' => ['Common'],
        'level' => 3,
        'strength' => 16,
        'dexterity' => 12,
        'constitution' => 14,
        'intelligence' => 10,
        'wisdom' => 10,
        'charisma' => 10,
    ])
        ->assertCreated()
        ->assertJsonPath('data.rolled_hit_points', true);

    expect(Character::query()->sole()->rolled_hit_points)->toBeTrue();
});

test('the rules wizard help command responds', function () {
    $this->postJson('/api/rules-wizard/message', [
        'message' => 'help',
        'state' => [],
    ])
        ->assertOk()
        ->assertJsonStructure([
            'reply',
            'state',
            'quick_actions',
            'snapshot',
        ])
        ->assertJsonPath('quick_actions.0', 'new character');
});

test('the rules wizard previews and rerolls random step five suggestions before keeping them', function () {
    $preview = $this->postJson('/api/rules-wizard/message', [
        'message' => 'random that fits',
        'state' => [
            'pending_field' => 'personality_traits',
            'skipped_optional_fields' => ['skill_expertise'],
            'character' => [
                'name' => 'Rin',
                'species' => 'Human',
                'class' => 'Wizard',
                'subclass' => 'Evoker',
                'skill_proficiencies' => ['Arcana', 'History'],
                'skill_expertise' => [],
                'background' => 'Sage',
                'origin_feat' => 'Magic Initiate',
                'advancement_method' => 'Milestone',
                'languages' => ['Common', 'Draconic'],
                'level' => 3,
                'strength' => 8,
                'dexterity' => 14,
                'constitution' => 12,
                'intelligence' => 17,
                'wisdom' => 13,
                'charisma' => 10,
                'alignment' => 'Neutral Good',
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'personality_traits')
        ->assertJsonPath('state.random_preview.kind', 'field')
        ->assertJsonPath('state.random_preview.field', 'personality_traits')
        ->assertJsonPath('quick_actions.0', 'keep this')
        ->assertSeeText('Random suggestion for personality traits:');

    $kept = $this->postJson('/api/rules-wizard/message', [
        'message' => 'keep this',
        'state' => $preview->json('state'),
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'ideals')
        ->assertJsonPath('state.random_preview', null)
        ->assertSeeText('Personality Traits set.');

    expect($kept->json('state.character.personality_traits'))->toBeString()->not->toBe('');
});

test('the rules wizard previews rolled ability scores until the user keeps them', function () {
    $preview = $this->postJson('/api/rules-wizard/message', [
        'message' => 'roll stats',
        'state' => [
            'pending_field' => 'strength',
            'skipped_optional_fields' => ['skill_expertise'],
            'character' => [
                'name' => 'Rin',
                'class' => 'Fighter',
                'level' => 1,
                'advancement_method' => 'Milestone',
                'subclass' => 'Champion',
                'background' => 'Guard',
                'species' => 'Human',
                'origin_feat' => 'Alert',
                'languages' => ['Common'],
                'skill_proficiencies' => ['Athletics'],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'strength')
        ->assertJsonPath('state.random_preview.kind', 'stats')
        ->assertJsonPath('quick_actions.0', 'keep these scores')
        ->assertSeeText('Rolled ability scores:');

    $kept = $this->postJson('/api/rules-wizard/message', [
        'message' => 'keep these scores',
        'state' => $preview->json('state'),
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'alignment')
        ->assertJsonPath('state.random_preview', null)
        ->assertSeeText('Ability scores kept:');

    foreach (['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $field) {
        expect($kept->json("state.character.{$field}"))->toBeInt();
    }
});

test('the dm wizard help command responds with dm-specific actions', function () {
    $this->postJson('/api/dm-wizard/message', [
        'message' => 'help',
        'state' => [],
    ])
        ->assertOk()
        ->assertJsonStructure([
            'reply',
            'state',
            'quick_actions',
            'snapshot',
        ])
        ->assertSeeText('DM Wizard actions:')
        ->assertJsonPath('quick_actions.0', 'new npc');
});

test('the dm wizard validates nested state before handling messages', function () {
    $this->postJson('/api/dm-wizard/message', [
        'message' => 'help',
        'state' => [
            'draft_record' => 'broken',
        ],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['state.draft_record']);
});

test('the dm wizard can save a guided npc record', function () {
    $state = [];

    foreach ([
        'new npc',
        'Mira Vale',
        'Dockmaster',
        'Weathered, direct, and already measuring the party',
        'Low harbor rasp',
        'Drums fingers on any wooden rail',
        'Keep contraband away from the lower piers',
        'Secretly pays the lighthouse keeper for signal reports',
        'The safety of the dockworkers',
        'A public riot on her watch',
        'She can give the party quiet access to the quarantine pier',
        'Friendly',
        'Quick stats',
        '2',
        '15',
        '27',
        'Hooked boat pole',
        '1d6+2 bludgeoning',
        'Fog flare once per scene',
        'save record',
    ] as $message) {
        $response = $this->postJson('/api/dm-wizard/message', [
            'message' => $message,
            'state' => $state,
        ])->assertOk();

        $state = $response->json('state');
    }

    expect(DmRecord::query()->count())->toBe(1);

    $record = DmRecord::query()->sole();

    expect($record->kind)->toBe('npc');
    expect($record->name)->toBe('Mira Vale');
    expect($record->payload['role'])->toBe('Dockmaster');
    expect($record->payload['combat_mode'])->toBe('quick_stats');
    expect($record->payload['quick_stats']['ac'])->toBe(15);
});

test('the dm wizard can save scene and encounter records', function () {
    $state = [];

    foreach ([
        'new scene',
        'Sewer Parley',
        'Flooded sluice chapel',
        'Force a tense negotiation under rising water',
        'The smugglers leave with the key if the party loses control',
        'Water keeps climbing every few rounds',
        'Smuggler knives and unstable walkways',
        'A chalk map etched into the altar stones',
        'save record',
    ] as $message) {
        $response = $this->postJson('/api/dm-wizard/message', [
            'message' => $message,
            'state' => $state,
        ])->assertOk();

        $state = $response->json('state');
    }

    $state = [];

    foreach ([
        'new encounter',
        'Bridge Breakers',
        'Narrow rope bridge over a gorge',
        'Cut ropes and force the party to split',
        'Rescue the envoy before the bridge falls',
        'Goblin outriders arrive on round three',
        'save record',
    ] as $message) {
        $response = $this->postJson('/api/dm-wizard/message', [
            'message' => $message,
            'state' => $state,
        ])->assertOk();

        $state = $response->json('state');
    }

    expect(DmRecord::query()->count())->toBe(2);
    expect(DmRecord::query()->where('kind', 'scene')->exists())->toBeTrue();
    expect(DmRecord::query()->where('kind', 'encounter')->exists())->toBeTrue();
});

test('a loaded dm record can be reopened for editing in the dm wizard', function () {
    $record = DmRecord::query()->create([
        'kind' => 'scene',
        'status' => 'draft',
        'name' => 'Old Chapel',
        'summary' => 'A tense meeting place under the broken bell.',
        'campaign' => null,
        'session_label' => null,
        'tags' => [],
        'payload' => [
            'location' => 'Flooded chapel',
            'purpose' => 'Force a tense negotiation.',
            'stakes' => 'The key leaves with the smugglers if the party hesitates.',
            'pressure' => 'Water keeps rising.',
            'active_threats' => 'Smuggler knives and unstable walkways.',
            'clues' => 'A chalk map etched into the altar stones.',
        ],
    ]);

    $loadedState = $this->postJson('/api/dm-wizard/message', [
        'message' => "load dm record {$record->id}",
        'state' => [],
    ])->assertOk()->json('state');

    $this->postJson('/api/dm-wizard/message', [
        'message' => 'edit stakes',
        'state' => $loadedState,
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'stakes')
        ->assertSeeText('Editing Stakes.');
});

test('dm records api supports create update delete and export without changing official data', function () {
    $createResponse = $this->postJson('/api/dm-records', [
        'kind' => 'npc',
        'status' => 'ready',
        'name' => 'Graven Pike',
        'summary' => 'A wary mercenary captain with quick combat notes.',
        'campaign' => 'Salt March',
        'session_label' => 'Session 3',
        'tags' => 'captain, mercenary',
        'payload' => [
            'role' => 'Mercenary captain',
            'species' => 'Human',
            'alignment' => 'Lawful Neutral',
            'attitude' => 'indifferent',
            'first_impression' => 'Scarred, watchful, and never far from the ledgers.',
            'appearance' => 'Salt-stiff cloak and nicked gauntlets.',
            'voice' => 'Measured parade-ground tone',
            'mannerism' => 'Counts coin with one thumb while listening.',
            'goal' => 'Keep the docks profitable long enough to pay the company.',
            'secret' => 'Takes bribes to ignore one smuggling route.',
            'leverage' => 'His missing brother serves on a rival ship.',
            'fear' => 'A public mutiny in front of the harbor council.',
            'bond' => 'Still honors the banner of his first company.',
            'faction' => 'Harbor Wardens',
            'party_relationship' => 'Can become a paid contact if treated fairly.',
            'party_hook' => 'He can get the party through the customs gate after dark.',
            'clue_hooks' => 'Knows which manifests were altered before the fire.',
            'loot_hooks' => 'Keeps seized cargo locked in a side warehouse.',
            'combat_mode' => 'quick_stats',
            'quick_stats' => [
                'initiative_bonus' => 2,
                'ac' => 16,
                'max_hp' => 32,
                'attack_note' => 'Spear thrust',
                'damage_note' => '1d8+3 piercing',
                'spell_note' => 'Signal horn once per fight',
            ],
        ],
    ])->assertCreated();

    $recordId = $createResponse->json('data.id');

    $this->getJson('/api/dm-records')
        ->assertOk()
        ->assertJsonCount(1, 'records');

    $this->putJson("/api/dm-records/{$recordId}", [
        'kind' => 'npc',
        'status' => 'active',
        'name' => 'Graven Pike Revised',
        'summary' => 'A wary mercenary captain updated for tonight.',
        'tags' => 'captain, harbor',
        'payload' => [
            'role' => 'Mercenary captain',
            'species' => 'Human',
            'alignment' => 'Lawful Neutral',
            'attitude' => 'friendly',
            'first_impression' => 'Scarred and unexpectedly open with the party.',
            'appearance' => 'Salt-stiff cloak and nicked gauntlets.',
            'voice' => 'Measured parade-ground tone',
            'mannerism' => 'Counts coin with one thumb while listening.',
            'goal' => 'Keep the docks profitable long enough to pay the company.',
            'secret' => 'Still takes bribes to ignore one smuggling route.',
            'leverage' => 'His missing brother serves on a rival ship.',
            'fear' => 'A public mutiny in front of the harbor council.',
            'bond' => 'Still honors the banner of his first company.',
            'faction' => 'Harbor Wardens',
            'party_relationship' => 'Can become a paid contact if treated fairly.',
            'party_hook' => 'He can get the party through the customs gate after dark.',
            'clue_hooks' => 'Knows which manifests were altered before the fire.',
            'loot_hooks' => 'Keeps seized cargo locked in a side warehouse.',
            'combat_mode' => 'quick_stats',
            'quick_stats' => [
                'initiative_bonus' => 3,
                'ac' => 16,
                'max_hp' => 34,
                'attack_note' => 'Spear thrust',
                'damage_note' => '1d8+3 piercing',
                'spell_note' => 'Signal horn once per fight',
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Graven Pike Revised')
        ->assertJsonPath('data.status', 'active');

    $this->postJson("/api/dm-records/{$recordId}/export-homebrew")
        ->assertOk()
        ->assertJsonPath('homebrew_entry.category', 'monster')
        ->assertJsonPath('data.linked_homebrew_entry_id', 1);

    $configurator = $this->getJson('/api/configurator')
        ->assertOk()
        ->assertJsonCount(12, 'classes');

    expect($configurator->json('classes'))->not->toContain('Graven Pike Revised');

    $this->deleteJson("/api/dm-records/{$recordId}")
        ->assertOk();

    expect(DmRecord::query()->count())->toBe(0);
    expect(HomebrewEntry::query()->count())->toBe(1);
});

test('the rules wizard roleplay help blends the build into one starter', function () {
    $this->postJson('/api/rules-wizard/message', [
        'message' => 'help me roleplay',
        'state' => [
            'character' => [
                'name' => 'Liora',
                'species' => 'Elf',
                'class' => 'Wizard',
                'subclass' => 'Abjurer',
                'skill_proficiencies' => ['Arcana', 'History'],
                'skill_expertise' => [],
                'background' => 'Sage',
                'alignment' => 'Lawful Good',
                'origin_feat' => 'Alert',
                'advancement_method' => 'Milestone',
                'languages' => ['Common', 'Elvish'],
                'level' => 3,
                'strength' => 8,
                'dexterity' => 14,
                'constitution' => 12,
                'intelligence' => 17,
                'wisdom' => 13,
                'charisma' => 10,
            ],
        ],
    ])
        ->assertOk()
        ->assertSeeText('Quick read: This build reads like a Lawful Good Elf Sage Wizard.')
        ->assertSeeText('Milestone ties growth to major turning points, promises kept, and moments that change the shape of the story.')
        ->assertSeeText('Table notes:')
        ->assertSeeText('Either approach still counts as roleplay.')
        ->assertSeeText('Friendly, Indifferent, or Hostile.')
        ->assertSeeText('Trait:')
        ->assertSeeText('Goal:')
        ->assertSeeText('Speaking common and elvish keeps me connected to more than one corner of the world.');
});

test('the rules wizard starts a new character in handbook order', function () {
    $this->postJson('/api/rules-wizard/message', [
        'message' => 'new character',
        'state' => [],
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'class')
        ->assertJsonPath('quick_actions.0', 'Barbarian')
        ->assertJsonPath('quick_actions.11', 'Wizard')
        ->assertSeeTextInOrder([
            'Step 1: Choose a Class',
            'Choose a class',
        ]);
});

test('the rules wizard asks for the advancement method right after level', function () {
    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $state = [];

    // Developer context: This loop applies the same step to each entry in the current list.
    // Clear explanation: This line repeats the same work for every item in a group.
    foreach (['new character', 'Barbarian', '1'] as $message) {
        $response = $this->postJson('/api/rules-wizard/message', [
            'message' => $message,
            'state' => $state,
        ])->assertOk();

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state = $response->json('state');
    }

    expect($state['pending_field'])->toBe('advancement_method');

    $this->postJson('/api/rules-wizard/message', [
        'message' => '',
        'state' => $state,
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'advancement_method')
        ->assertSeeText('Choose how')
        ->assertSeeText('Milestone');
});

test('character creation requires core origin mechanics', function () {
    $this->postJson('/api/characters', [
        'name' => 'Rin',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics'],
        'background' => 'Soldier',
        'advancement_method' => 'Milestone',
        'level' => 1,
        'strength' => 15,
        'dexterity' => 14,
        'constitution' => 13,
        'intelligence' => 12,
        'wisdom' => 10,
        'charisma' => 8,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['origin_feat', 'languages']);
});

test('character creation requires expertise to match selected skill proficiencies', function () {
    $this->postJson('/api/characters', [
        'name' => 'Rin',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics'],
        'skill_expertise' => ['Stealth'],
        'background' => 'Soldier',
        'origin_feat' => 'Alert',
        'advancement_method' => 'Milestone',
        'languages' => ['Common'],
        'level' => 1,
        'strength' => 15,
        'dexterity' => 14,
        'constitution' => 13,
        'intelligence' => 12,
        'wisdom' => 10,
        'charisma' => 8,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['skill_expertise']);
});

test('characters can be updated from the roster editor', function () {
    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $characterId = $this->postJson('/api/characters', [
        'name' => 'Rin',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics', 'Perception'],
        'skill_expertise' => [],
        'background' => 'Soldier',
        'alignment' => 'Lawful Good',
        'origin_feat' => 'Alert',
        'advancement_method' => 'Milestone',
        'languages' => ['Common', 'Elvish'],
        'level' => 1,
        'strength' => 15,
        'dexterity' => 14,
        'constitution' => 13,
        'intelligence' => 12,
        'wisdom' => 10,
        'charisma' => 8,
    ])->assertCreated()->json('data.id');

    $this->putJson("/api/characters/{$characterId}", [
        'name' => 'Rin of the Watch',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => 'Athletics, Survival',
        'skill_expertise' => '',
        'background' => 'Guard',
        'alignment' => 'Neutral Good',
        'origin_feat' => 'Alert',
        'advancement_method' => 'Milestone',
        'languages' => 'Common, Dwarvish',
        'level' => 2,
        'strength' => 16,
        'dexterity' => 14,
        'constitution' => 14,
        'intelligence' => 10,
        'wisdom' => 12,
        'charisma' => 8,
        'notes' => 'Keeps the city gate keys.',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Rin of the Watch')
        ->assertJsonPath('data.background', 'Guard')
        ->assertJsonPath('data.languages.1', 'Dwarvish')
        ->assertJsonPath('data.notes', 'Keeps the city gate keys.');

    expect(Character::query()->sole()->background)->toBe('Guard');
});

test('character creation normalizes plain text before save', function () {
    $this->postJson('/api/characters', [
        'name' => '  <b>Rin</b>  ',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics', 'Perception'],
        'background' => 'Soldier',
        'origin_feat' => 'Alert',
        'advancement_method' => 'Milestone',
        'languages' => ['Common', ' Elvish '],
        'level' => 1,
        'strength' => 15,
        'dexterity' => 14,
        'constitution' => 13,
        'intelligence' => 12,
        'wisdom' => 10,
        'charisma' => 8,
        'personality_traits' => '  Curious   but steady  ',
        'goals' => "  Protect   the old archive \n<b>at all costs</b> ",
        'notes' => '<script>alert(1)</script> Keeps a ledger ',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Rin')
        ->assertJsonPath('data.personality_traits', 'Curious but steady')
        ->assertJsonPath('data.goals', "Protect the old archive\nat all costs")
        ->assertJsonPath('data.notes', 'Keeps a ledger')
        ->assertJsonPath('data.languages.0', 'Common')
        ->assertJsonPath('data.languages.1', 'Elvish');

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $character = Character::query()->sole();

    expect($character->name)->toBe('Rin');
    expect($character->goals)->toBe("Protect the old archive\nat all costs");
    expect($character->notes)->toBe('Keeps a ledger');
});

test('character creation rejects empty required text after cleaning', function () {
    $this->postJson('/api/characters', [
        'name' => '<strong> </strong>',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics'],
        'background' => 'Soldier',
        'origin_feat' => 'Alert',
        'advancement_method' => 'Milestone',
        'languages' => ['Common'],
        'level' => 1,
        'strength' => 15,
        'dexterity' => 14,
        'constitution' => 13,
        'intelligence' => 12,
        'wisdom' => 10,
        'charisma' => 8,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('character creation rejects notes that exceed the shared limit', function () {
    $this->postJson('/api/characters', [
        'name' => 'Rin',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics'],
        'background' => 'Soldier',
        'origin_feat' => 'Alert',
        'advancement_method' => 'Milestone',
        'languages' => ['Common'],
        'level' => 1,
        'strength' => 15,
        'dexterity' => 14,
        'constitution' => 13,
        'intelligence' => 12,
        'wisdom' => 10,
        'charisma' => 8,
        'notes' => str_repeat('a', 2001),
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['notes']);
});

test('the rules wizard validates nested state before saving', function () {
    $this->postJson('/api/rules-wizard/message', [
        'message' => 'save character',
        'state' => [
            'character' => [
                'name' => 'Rin',
                'species' => 'Human',
                'class' => 'Barbarian',
                'subclass' => 'Path of the Berserker',
                'skill_proficiencies' => ['Athletics'],
                'skill_expertise' => ['Stealth'],
                'background' => 'Soldier',
                'origin_feat' => 'Alert',
                'advancement_method' => 'Milestone',
                'languages' => ['Common'],
                'level' => 1,
                'strength' => 15,
                'dexterity' => 14,
                'constitution' => 13,
                'intelligence' => 12,
                'wisdom' => 10,
                'charisma' => 8,
            ],
        ],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['skill_expertise']);
});

test('the rules wizard snapshot surfaces non-blocking official rules warnings', function () {
    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'show summary',
        'state' => [
            'character' => [
                'name' => 'Rin',
                'species' => 'Human',
                'class' => 'Barbarian',
                'subclass' => 'Path of the Berserker',
                'skill_proficiencies' => ['Athletics'],
                'background' => 'Soldier',
                'alignment' => 'Lawful Evil',
                'origin_feat' => 'Alert',
                'advancement_method' => 'Story Goal',
                'languages' => ['Elvish', 'Abyssal'],
                'level' => 1,
                'strength' => 15,
                'dexterity' => 14,
                'constitution' => 13,
                'intelligence' => 12,
                'wisdom' => 10,
                'charisma' => 8,
            ],
        ],
    ])->assertOk();

    $warnings = $response->json('snapshot.official_rules_warnings');

    expect($warnings)->toBeArray();
    expect(implode(' ', $warnings))
        ->toContain('Story Goal is supported here as a table variant')
        ->toContain('evil alignments should be cleared with the DM first')
        ->toContain('Common is missing')
        ->toContain('fixed packages')
        ->toContain('Tool proficiencies and starting equipment still need to be tracked separately');
});

test('the rules wizard message request enforces its shared limits', function () {
    $this->postJson('/api/rules-wizard/message', [
        'message' => str_repeat('a', 501),
        'state' => [],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('the rules wizard blocks skipping core mechanics but allows skipping roleplay', function () {
    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $state = [];

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $messages = [
        'new character',
        'Barbarian',
        '1',
        'Milestone',
        'Path of the Berserker',
        'Athletics, Perception',
        'skip',
        'Soldier',
        'Human',
    ];

    // Developer context: This loop applies the same step to each entry in the current list.
    // Clear explanation: This line repeats the same work for every item in a group.
    foreach ($messages as $message) {
        $response = $this->postJson('/api/rules-wizard/message', [
            'message' => $message,
            'state' => $state,
        ])->assertOk();

        // Developer context: This assignment stores a working value that the next lines reuse.
        // Clear explanation: This line saves a piece of information so the next steps can keep using it.
        $state = $response->json('state');
    }

    $this->postJson('/api/rules-wizard/message', [
        'message' => 'skip',
        'state' => $state,
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'origin_feat')
        ->assertSeeText('Origin Feat is part of the core build, so it cannot be skipped.');

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'Alert',
        'state' => $state,
    ])->assertOk();

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $state = $response->json('state');

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'Common, Elvish',
        'state' => $state,
    ])->assertOk();

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $state = $response->json('state');

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'roll stats',
        'state' => $state,
    ])->assertOk();

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $state = $response->json('state');

    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'keep these scores',
        'state' => $state,
    ])->assertOk();

    // Developer context: This assignment stores a working value that the next lines reuse.
    // Clear explanation: This line saves a piece of information so the next steps can keep using it.
    $state = $response->json('state');

    $this->postJson('/api/rules-wizard/message', [
        'message' => 'skip',
        'state' => $state,
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'name')
        ->assertSeeText('Alignment skipped for now.');
});
