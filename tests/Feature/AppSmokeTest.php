<?php

use App\Models\Character;

test('the homepage loads', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee("Adventurer's Ledger", false)
        ->assertSee('Character Builder', false);
});

test('the roster page loads', function () {
    $this->get('/roster')
        ->assertOk()
        ->assertSee('Keep the whole party in view.', false)
        ->assertSee('Saved Characters', false);
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

test('character creation requires core origin mechanics', function () {
    $this->postJson('/api/characters', [
        'name' => 'Rin',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics'],
        'background' => 'Soldier',
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

test('character creation normalizes plain text before save', function () {
    $this->postJson('/api/characters', [
        'name' => '  <b>Rin</b>  ',
        'species' => 'Human',
        'class' => 'Barbarian',
        'subclass' => 'Path of the Berserker',
        'skill_proficiencies' => ['Athletics', 'Perception'],
        'background' => 'Soldier',
        'origin_feat' => 'Alert',
        'languages' => ['Common', ' Elvish '],
        'level' => 1,
        'strength' => 15,
        'dexterity' => 14,
        'constitution' => 13,
        'intelligence' => 12,
        'wisdom' => 10,
        'charisma' => 8,
        'personality_traits' => '  Curious   but steady  ',
        'notes' => '<script>alert(1)</script> Keeps a ledger ',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Rin')
        ->assertJsonPath('data.personality_traits', 'Curious but steady')
        ->assertJsonPath('data.notes', 'Keeps a ledger')
        ->assertJsonPath('data.languages.0', 'Common')
        ->assertJsonPath('data.languages.1', 'Elvish');

    $character = Character::query()->sole();

    expect($character->name)->toBe('Rin');
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

test('the rules wizard message request enforces its shared limits', function () {
    $this->postJson('/api/rules-wizard/message', [
        'message' => str_repeat('a', 501),
        'state' => [],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('the rules wizard blocks skipping core mechanics but allows skipping roleplay', function () {
    $state = [];

    $messages = [
        'new character',
        'Barbarian',
        '1',
        'Path of the Berserker',
        'Athletics, Perception',
        'skip',
        'Soldier',
        'Human',
    ];

    foreach ($messages as $message) {
        $response = $this->postJson('/api/rules-wizard/message', [
            'message' => $message,
            'state' => $state,
        ])->assertOk();

        $state = $response->json('state');
    }

    $this->postJson('/api/rules-wizard/message', [
        'message' => 'skip',
        'state' => $state,
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'origin_feat')
        ->assertSeeText('Origin Feat is part of the core build, so it cannot be skipped.');

    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'Alert',
        'state' => $state,
    ])->assertOk();

    $state = $response->json('state');

    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'Common, Elvish',
        'state' => $state,
    ])->assertOk();

    $state = $response->json('state');

    $response = $this->postJson('/api/rules-wizard/message', [
        'message' => 'roll stats',
        'state' => $state,
    ])->assertOk();

    $state = $response->json('state');

    $this->postJson('/api/rules-wizard/message', [
        'message' => 'skip',
        'state' => $state,
    ])
        ->assertOk()
        ->assertJsonPath('state.pending_field', 'name')
        ->assertSeeText('Alignment skipped for now.');
});
