<?php

use App\Models\Character;

test('de homepage laadt', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('D&amp;D Karakterbeheer', false)
        ->assertSee('Nieuw karakter', false)
        ->assertSee('Karakters', false)
        ->assertSee('Bekijk API-overzicht', false);
});

test('de api overzichtspagina laadt vanuit de bestaande api routes', function () {
    $character = Character::query()->create(basicCharacterPayload());

    $this->get('/api-overzicht')
        ->assertOk()
        ->assertSee('API-overzicht', false)
        ->assertSee('/', false)
        ->assertSee('/api-overzicht', false)
        ->assertSee('/api/characters', false)
        ->assertSee("/api/characters/{$character->id}", false)
        ->assertSee('Web', false)
        ->assertSee('API', false)
        ->assertSee('POST', false)
        ->assertSee('DELETE', false);
});

test('de karakterlijst komt terug uit de api', function () {
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

test('de gegevens van 1 karakter komen terug uit de api', function () {
    $character = Character::query()->create(basicCharacterPayload());

    $this->getJson("/api/characters/{$character->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $character->id)
        ->assertJsonPath('data.name', 'Rin')
        ->assertJsonPath('data.class', 'Fighter');
});

test('onbekend karakter geeft een nette 404 melding', function () {
    $this->getJson('/api/characters/999999')
        ->assertNotFound()
        ->assertJsonPath('message', 'Karakter niet gevonden.');
});

test('een karakter kan worden opgeslagen', function () {
    $this->postJson('/api/characters', basicCharacterPayload([
        'name' => '  <b>Kael</b>  ',
        'notes' => "<script>nope</script> Beschermt de groep",
    ]))
        ->assertCreated()
        ->assertJsonPath('message', 'Karakter opgeslagen.')
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

test('een karakter kan worden bijgewerkt', function () {
    $character = Character::query()->create(basicCharacterPayload());

    $this->putJson("/api/characters/{$character->id}", basicCharacterPayload([
        'name' => 'Rin van de Poort',
        'subclass' => 'Battle Master',
        'level' => 3,
        'notes' => 'Is bevorderd tot sergeant.',
    ]))
        ->assertOk()
        ->assertJsonPath('message', 'Karakter bijgewerkt.')
        ->assertJsonPath('data.name', 'Rin van de Poort')
        ->assertJsonPath('data.level', 3);

    expect(Character::query()->sole()->name)->toBe('Rin van de Poort');
});

test('een karakter kan worden verwijderd', function () {
    $character = Character::query()->create(basicCharacterPayload());

    $this->deleteJson("/api/characters/{$character->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Karakter verwijderd.');

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
