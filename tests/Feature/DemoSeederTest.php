<?php

use App\Models\Character;

test('de demo seeder maakt vijf characters aan', function () {
    $this->seed();

    expect(Character::query()->count())->toBe(5);
    expect(Character::query()->where('name', 'Rin')->exists())->toBeTrue();
    expect(Character::query()->where('name', 'Kael')->exists())->toBeTrue();
});
