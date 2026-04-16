<?php

namespace Database\Seeders;

use App\Models\Character;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->characters() as $character) {
            Character::query()->updateOrCreate(
                ['name' => $character['name']],
                $character,
            );
        }
    }

    private function characters(): array
    {
        return [
            [
                'name' => 'Rin',
                'species' => 'Human',
                'class' => 'Fighter',
                'subclass' => 'Champion',
                'background' => 'Guard',
                'alignment' => 'Neutral Good',
                'level' => 2,
                'notes' => 'Een stadswacht die graag alles netjes houdt.',
            ],
            [
                'name' => 'Liora',
                'species' => 'Elf',
                'class' => 'Wizard',
                'subclass' => 'Evoker',
                'background' => 'Sage',
                'alignment' => 'Lawful Good',
                'level' => 4,
                'notes' => 'Houdt van boeken en magie.',
            ],
            [
                'name' => 'Mira',
                'species' => 'Dwarf',
                'class' => 'Cleric',
                'subclass' => 'Life',
                'background' => 'Acolyte',
                'alignment' => 'Lawful Neutral',
                'level' => 3,
                'notes' => 'Helpt de groep met genezing.',
            ],
            [
                'name' => 'Thorn',
                'species' => 'Halfling',
                'class' => 'Rogue',
                'subclass' => 'Thief',
                'background' => 'Merchant',
                'alignment' => 'Chaotic Good',
                'level' => 5,
                'notes' => 'Snel en handig met sloten.',
            ],
            [
                'name' => 'Kael',
                'species' => 'Dragonborn',
                'class' => 'Paladin',
                'subclass' => 'Devotion',
                'background' => 'Soldier',
                'alignment' => 'Lawful Good',
                'level' => 6,
                'notes' => 'Probeert eerst te praten en dan pas te vechten.',
            ],
        ];
    }
}
