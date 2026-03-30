<?php

test('the homepage loads', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee("Adventurer's Ledger", false)
        ->assertSee('Character Builder', false);
});

test('the roster page loads', function () {
    $this->get('/roster')
        ->assertOk()
        ->assertSee('Party Roster', false)
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

test('the rules wizard blocks skipping core mechanics but allows skipping roleplay', function () {
    $state = [];

    $messages = [
        'new character',
        'Barbarian',
        '1',
        'Path of the Berserker',
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
