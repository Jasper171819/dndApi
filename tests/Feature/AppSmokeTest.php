<?php

use App\Models\Character;

test('de homepage laadt', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('D&amp;D Character Beheer', false)
        ->assertSee('Nieuw character', false)
        ->assertSee('Characters', false);
});

test('de character lijst komt terug uit de api', function () {
    Character::query()->create(basicCharacterPayload([
        'name' => 'Rin',
    ]));

    Character::query()->create(basicCharacterPayload([
        'name' => 'Liora',
        'class' => 'Wizard',
    ]));

    $response = $this->getJson('/api/characters')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $names = collect($response->json('data'))->pluck('name')->all();

    expect($names)->toContain('Liora', 'Rin');
});

test('een los character detail komt terug uit de api', function () {
    $character = Character::query()->create(basicCharacterPayload());

    $this->getJson("/api/characters/{$character->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $character->id)
        ->assertJsonPath('data.name', 'Rin')
        ->assertJsonPath('data.class', 'Fighter');
});

test('onbekend character geeft een nette 404 melding', function () {
    $this->getJson('/api/characters/999999')
        ->assertNotFound()
        ->assertJsonPath('message', 'Character niet gevonden.');
});

test('een character kan worden opgeslagen', function () {
    $this->postJson('/api/characters', basicCharacterPayload([
        'name' => '  <b>Kael</b>  ',
        'notes' => "<script>nope</script> Beschermt de groep",
    ]))
        ->assertCreated()
        ->assertJsonPath('message', 'Character opgeslagen.')
        ->assertJsonPath('data.name', 'Kael')
        ->assertJsonPath('data.notes', 'Beschermt de groep');

    expect(Character::query()->count())->toBe(1);
});

test('de api valideert verplichte velden en level bereik', function () {
    $this->postJson('/api/characters', [
        'name' => '',
        'species' => '',
        'class' => '',
        'background' => '',
        'level' => 25,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'species', 'class', 'background', 'level']);
});

test('een character kan worden bijgewerkt', function () {
    $character = Character::query()->create(basicCharacterPayload());

    $this->putJson("/api/characters/{$character->id}", basicCharacterPayload([
        'name' => 'Rin van de Poort',
        'subclass' => 'Battle Master',
        'level' => 3,
        'notes' => 'Is bevorderd tot sergeant.',
    ]))
        ->assertOk()
        ->assertJsonPath('message', 'Character bijgewerkt.')
        ->assertJsonPath('data.name', 'Rin van de Poort')
        ->assertJsonPath('data.level', 3);

    expect(Character::query()->sole()->name)->toBe('Rin van de Poort');
});

test('een character kan worden verwijderd', function () {
    $character = Character::query()->create(basicCharacterPayload());

    $this->deleteJson("/api/characters/{$character->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Character verwijderd.');

    expect(Character::query()->count())->toBe(0);
});

function basicCharacterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rin',
        'species' => 'Human',
        'class' => 'Fighter',
        'subclass' => 'Champion',
        'background' => 'Guard',
        'alignment' => 'Neutral Good',
        'level' => 2,
        'notes' => 'Beschermt de stadspoort.',
    ], $overrides);
}
