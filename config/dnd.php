<?php

$classes = [
    'Barbarian' => [
        'summary' => 'A fierce frontline warrior fueled by primal power and physical toughness.',
        'primary_focus' => ['Strength', 'Constitution'],
        'subclasses' => [
            'Path of the Berserker',
            'Path of the Wild Heart',
            'Path of the World Tree',
            'Path of the Zealot',
        ],
    ],
    'Bard' => [
        'summary' => 'A versatile performer whose magic is shaped through art, words, and inspiration.',
        'primary_focus' => ['Charisma', 'Dexterity'],
        'subclasses' => [
            'College of Dance',
            'College of Glamour',
            'College of Lore',
            'College of Valor',
        ],
    ],
    'Cleric' => [
        'summary' => 'A divine spellcaster who channels sacred power through faith and devotion.',
        'primary_focus' => ['Wisdom', 'Strength'],
        'subclasses' => [
            'Life Domain',
            'Light Domain',
            'Trickery Domain',
            'War Domain',
        ],
    ],
    'Druid' => [
        'summary' => 'A guardian of nature who calls on primal magic and wild transformation.',
        'primary_focus' => ['Wisdom', 'Constitution'],
        'subclasses' => [
            'Circle of the Land',
            'Circle of the Moon',
            'Circle of the Sea',
            'Circle of the Stars',
        ],
    ],
    'Fighter' => [
        'summary' => 'A disciplined combatant defined by martial skill, tactics, and resilience.',
        'primary_focus' => ['Strength', 'Dexterity'],
        'subclasses' => [
            'Battle Master',
            'Champion',
            'Eldritch Knight',
            'Psi Warrior',
        ],
    ],
    'Monk' => [
        'summary' => 'A focused martial artist who blends mobility, discipline, and inner power.',
        'primary_focus' => ['Dexterity', 'Wisdom'],
        'subclasses' => [
            'Warrior of Mercy',
            'Warrior of Shadow',
            'Warrior of the Elements',
            'Warrior of the Open Hand',
        ],
    ],
    'Paladin' => [
        'summary' => 'A holy champion who combines martial prowess, healing, and oath-bound purpose.',
        'primary_focus' => ['Strength', 'Charisma'],
        'subclasses' => [
            'Oath of Devotion',
            'Oath of Glory',
            'Oath of the Ancients',
            'Oath of Vengeance',
        ],
    ],
    'Ranger' => [
        'summary' => 'A wilderness expert who blends combat skill, scouting, and nature magic.',
        'primary_focus' => ['Dexterity', 'Wisdom'],
        'subclasses' => [
            'Beast Master',
            'Fey Wanderer',
            'Gloom Stalker',
            'Hunter',
        ],
    ],
    'Rogue' => [
        'summary' => 'A precise specialist who relies on mobility, stealth, and opportunistic strikes.',
        'primary_focus' => ['Dexterity', 'Intelligence'],
        'subclasses' => [
            'Arcane Trickster',
            'Assassin',
            'Soulknife',
            'Thief',
        ],
    ],
    'Sorcerer' => [
        'summary' => 'An innate spellcaster whose magic is tied to bloodline, fate, or strange power.',
        'primary_focus' => ['Charisma', 'Constitution'],
        'subclasses' => [
            'Aberrant Sorcery',
            'Clockwork Sorcery',
            'Draconic Sorcery',
            'Wild Magic Sorcery',
        ],
    ],
    'Warlock' => [
        'summary' => 'A spellcaster bound to a supernatural patron in exchange for eldritch power.',
        'primary_focus' => ['Charisma', 'Constitution'],
        'subclasses' => [
            'Archfey Patron',
            'Celestial Patron',
            'Fiend Patron',
            'Great Old One Patron',
        ],
    ],
    'Wizard' => [
        'summary' => 'A scholarly arcane caster who masters spells through study and preparation.',
        'primary_focus' => ['Intelligence', 'Constitution'],
        'subclasses' => [
            'Abjurer',
            'Diviner',
            'Evoker',
            'Illusionist',
        ],
    ],
];

$species = [
    'Aasimar' => [
        'summary' => 'A celestial-touched hero able to unleash radiant or ominous divine power.',
        'size' => 'Medium',
        'speed' => '30 feet',
        'traits' => ['Celestial Revelation', 'Darkvision', 'Healing Hands', 'Necrotic and Radiant Resistance'],
    ],
    'Dragonborn' => [
        'summary' => 'A draconic adventurer defined by breath weapons and dragon ancestry.',
        'size' => 'Medium',
        'speed' => '30 feet',
        'traits' => ['Breath Weapon', 'Darkvision', 'Damage Resistance', 'Draconic Flight'],
    ],
    'Dwarf' => [
        'summary' => 'A durable subterranean people known for resilience, stonecraft, and grit.',
        'size' => 'Medium',
        'speed' => '30 feet',
        'traits' => ['Darkvision', 'Dwarven Resilience', 'Stonecunning'],
    ],
    'Elf' => [
        'summary' => 'A graceful, long-lived people with magical ties and keen senses.',
        'size' => 'Medium',
        'speed' => '30 feet',
        'traits' => ['Darkvision', 'Fey Ancestry', 'Keen Senses', 'Trance'],
    ],
    'Gnome' => [
        'summary' => 'A curious and clever people tied to invention, wit, and subtle magic.',
        'size' => 'Small',
        'speed' => '30 feet',
        'traits' => ['Darkvision', 'Gnomish Cunning', 'Gnomish Lineage'],
    ],
    'Goliath' => [
        'summary' => 'A giant-kin adventurer with great strength and a powerful ancestry.',
        'size' => 'Medium',
        'speed' => '35 feet',
        'traits' => ['Large Form', 'Powerful Build', 'Giant Ancestry'],
    ],
    'Halfling' => [
        'summary' => 'A nimble, lucky, and surprisingly courageous small folk.',
        'size' => 'Small',
        'speed' => '30 feet',
        'traits' => ['Brave', 'Halfling Nimbleness', 'Luck'],
    ],
    'Human' => [
        'summary' => 'A flexible and ambitious people shaped by versatility and determination.',
        'size' => 'Medium',
        'speed' => '30 feet',
        'traits' => ['Heroic Inspiration', 'Resourceful', 'Skillful', 'Versatile'],
    ],
    'Orc' => [
        'summary' => 'A relentless hero known for endurance, aggression, and explosive momentum.',
        'size' => 'Medium',
        'speed' => '30 feet',
        'traits' => ['Adrenaline Rush', 'Darkvision', 'Powerful Build', 'Relentless Endurance'],
    ],
    'Tiefling' => [
        'summary' => 'A fiend-touched adventurer shaped by an infernal or lower-planar legacy.',
        'size' => 'Medium',
        'speed' => '30 feet',
        'traits' => ['Darkvision', 'Fiendish Legacy', 'Otherworldly Presence'],
    ],
];

$backgrounds = [
    'Acolyte' => ['summary' => 'A life shaped by worship, sacred study, and religious service.', 'theme' => 'Faith and devotion'],
    'Artisan' => ['summary' => 'A craftsperson trained through practical work, detail, and trade.', 'theme' => 'Craft and trade'],
    'Charlatan' => ['summary' => 'A swindler, trickster, or social manipulator skilled at false appearances.', 'theme' => 'Deception and disguise'],
    'Criminal' => ['summary' => 'A survivor of the underworld who knows stealth, locks, and illicit work.', 'theme' => 'Underworld survival'],
    'Entertainer' => ['summary' => 'A performer used to holding attention through music, art, or spectacle.', 'theme' => 'Performance and showmanship'],
    'Farmer' => ['summary' => 'A worker of field or livestock hardened by labor and the natural world.', 'theme' => 'Labor and resilience'],
    'Guard' => ['summary' => 'A watchful defender trained to spot danger and hold the line.', 'theme' => 'Protection and duty'],
    'Guide' => ['summary' => 'A traveler of wild places who knows routes, terrain, and natural magic.', 'theme' => 'Travel and wilderness'],
    'Hermit' => ['summary' => 'A secluded seeker shaped by contemplation, solitude, and inward focus.', 'theme' => 'Solitude and insight'],
    'Merchant' => ['summary' => 'A trader used to bargaining, logistics, and life on the road.', 'theme' => 'Commerce and negotiation'],
    'Noble' => ['summary' => 'A person raised among status, influence, etiquette, and expectation.', 'theme' => 'Status and leadership'],
    'Sage' => ['summary' => 'A researcher or scholar devoted to learning, archives, and study.', 'theme' => 'Scholarship and memory'],
    'Sailor' => ['summary' => 'A mariner shaped by ships, storms, ports, and hard travel.', 'theme' => 'Sea travel and grit'],
    'Scribe' => ['summary' => 'A meticulous writer or copyist trained in texts, records, and precision.', 'theme' => 'Records and precision'],
    'Soldier' => ['summary' => 'A veteran of drills, campaigns, and disciplined martial training.', 'theme' => 'Discipline and battle'],
    'Wayfarer' => ['summary' => 'A castoff or drifter who learned to survive by instinct, grit, and streetcraft.', 'theme' => 'Survival and movement'],
];

$originFeats = [
    'Alert' => 'Boosts initiative and lets you swap initiative with a willing ally.',
    'Crafter' => 'Improves crafting utility and discounts for nonmagical gear.',
    'Healer' => 'Enhances healing through healer kits and improves healing rolls.',
    'Lucky' => 'Grants luck points to gain advantage or impose disadvantage.',
    'Magic Initiate' => 'Teaches cantrips and a 1st-level spell from a chosen spell list.',
    'Musician' => 'Grants instrument proficiencies and lets you hand out Heroic Inspiration after rests.',
    'Savage Attacker' => 'Lets you reroll weapon damage dice once per turn and choose the better result.',
    'Skilled' => 'Grants proficiency in any combination of three skills or tools.',
    'Tavern Brawler' => 'Improves unarmed fighting, improvised weapon use, and shoving pressure.',
    'Tough' => 'Raises maximum hit points immediately and as you level.',
];

$abilities = [
    'Strength' => 'Measures physical power, athletic force, and lifting or striking capability.',
    'Dexterity' => 'Measures agility, reflexes, balance, and precision movement.',
    'Constitution' => 'Measures stamina, resilience, and bodily endurance.',
    'Intelligence' => 'Measures reasoning, memory, study, and analytical ability.',
    'Wisdom' => 'Measures awareness, intuition, perception, and common sense.',
    'Charisma' => 'Measures force of personality, confidence, and social presence.',
];

$alignments = [
    'Lawful Good' => 'Acts with compassion while honoring order, duty, or structure.',
    'Neutral Good' => 'Acts to help others without strong attachment to law or chaos.',
    'Chaotic Good' => 'Acts with compassion while valuing freedom and personal choice.',
    'Lawful Neutral' => 'Values order, codes, and consistency above moral extremes.',
    'Neutral' => 'Avoids strong alignment extremes and seeks balance or practicality.',
    'Chaotic Neutral' => 'Follows impulse, freedom, or individuality over structure.',
    'Lawful Evil' => 'Uses order, hierarchy, and rules for selfish or cruel ends.',
    'Neutral Evil' => 'Pursues selfish gain without strong loyalty to order or chaos.',
    'Chaotic Evil' => 'Acts with destructive selfishness and rejects restraint or order.',
];

$skills = [
    'Acrobatics' => ['ability' => 'Dexterity', 'summary' => 'Balance, tumbling, escapes, and agile movement.'],
    'Animal Handling' => ['ability' => 'Wisdom', 'summary' => 'Calming, directing, or reading beasts.'],
    'Arcana' => ['ability' => 'Intelligence', 'summary' => 'Knowledge of magic, planes, and eldritch lore.'],
    'Athletics' => ['ability' => 'Strength', 'summary' => 'Climbing, swimming, jumping, grappling, and physical exertion.'],
    'Deception' => ['ability' => 'Charisma', 'summary' => 'Lies, disguises, misdirection, and false impressions.'],
    'History' => ['ability' => 'Intelligence', 'summary' => 'Knowledge of people, wars, places, and past events.'],
    'Insight' => ['ability' => 'Wisdom', 'summary' => 'Reading motives, feelings, and hidden intent.'],
    'Intimidation' => ['ability' => 'Charisma', 'summary' => 'Pressuring, threatening, or cowing others.'],
    'Investigation' => ['ability' => 'Intelligence', 'summary' => 'Examining clues, searching carefully, and solving details.'],
    'Medicine' => ['ability' => 'Wisdom', 'summary' => 'Diagnosing injuries, stabilizing creatures, and practical care.'],
    'Nature' => ['ability' => 'Intelligence', 'summary' => 'Knowledge of terrain, plants, animals, and natural cycles.'],
    'Perception' => ['ability' => 'Wisdom', 'summary' => 'Spotting danger, noticing details, and sensing the environment.'],
    'Performance' => ['ability' => 'Charisma', 'summary' => 'Entertaining an audience through art, music, or drama.'],
    'Persuasion' => ['ability' => 'Charisma', 'summary' => 'Negotiation, diplomacy, and winning cooperation.'],
    'Religion' => ['ability' => 'Intelligence', 'summary' => 'Knowledge of deities, rites, and sacred traditions.'],
    'Sleight of Hand' => ['ability' => 'Dexterity', 'summary' => 'Pickpocketing, concealment, and quick manual tricks.'],
    'Stealth' => ['ability' => 'Dexterity', 'summary' => 'Hiding, sneaking, and moving without notice.'],
    'Survival' => ['ability' => 'Wisdom', 'summary' => 'Tracking, foraging, navigation, and enduring the wild.'],
];

$conditions = [
    'Blinded' => 'You cannot see and automatically fail sight-based checks.',
    'Charmed' => 'You cannot attack the charmer and they gain social leverage over you.',
    'Deafened' => 'You cannot hear and automatically fail hearing-based checks.',
    'Exhaustion' => 'A state of mounting strain that imposes escalating penalties.',
    'Frightened' => 'You suffer disadvantage while the source of fear is in sight.',
    'Grappled' => 'Your speed is reduced to 0 by a grappling creature or effect.',
    'Incapacitated' => 'You cannot take actions, bonus actions, or reactions.',
    'Invisible' => 'You are unseen without special senses or revealing effects.',
    'Paralyzed' => 'You are incapacitated, immobile, and vulnerable in close combat.',
    'Petrified' => 'You are transformed into a rigid, inert substance and incapacitated.',
    'Poisoned' => 'You have disadvantage on attack rolls and ability checks.',
    'Prone' => 'You are lying on the ground and must crawl or stand up.',
    'Restrained' => 'Your speed is 0 and your attacks and Dexterity saves worsen.',
    'Stunned' => 'You are incapacitated, can barely move, and fail Strength and Dexterity saves.',
    'Unconscious' => 'You are unaware, incapacitated, and drop what you are holding.',
];

$damageTypes = [
    'Acid' => 'Corrosive damage that eats away at surfaces and creatures.',
    'Bludgeoning' => 'Impact damage from clubs, falls, hammers, and crushing force.',
    'Cold' => 'Freezing damage that saps heat and slows the body.',
    'Fire' => 'Burning heat from flame, magma, and intense magical ignition.',
    'Force' => 'Pure magical energy that strikes with raw arcane power.',
    'Lightning' => 'Electrical shock that courses through targets instantly.',
    'Necrotic' => 'Withering damage that drains vitality and life energy.',
    'Piercing' => 'Stabbing damage from arrows, spears, and pointed weapons.',
    'Poison' => 'Toxic damage delivered through venom, fumes, or corruption.',
    'Psychic' => 'Mental damage that assaults thoughts, will, or consciousness.',
    'Radiant' => 'Luminous sacred damage associated with divine or celestial energy.',
    'Slashing' => 'Cutting damage from blades, claws, and similar edges.',
    'Thunder' => 'Concussive sound damage that erupts with overwhelming force.',
];

$languages = [
    'Abyssal' => 'A harsh extraplanar tongue associated with chaotic fiends.',
    'Celestial' => 'A radiant language tied to divine and upper-planar beings.',
    'Common' => 'The shared trade and travel tongue used throughout many realms.',
    'Deep Speech' => 'An alien, unsettling language tied to aberrant influence.',
    'Draconic' => 'The ancient tongue of dragons, sorcery, and arcane records.',
    'Dwarvish' => 'A sturdy language of clans, stonecraft, and tradition.',
    'Elvish' => 'A flowing language associated with fey grace and long memory.',
    'Giant' => 'A broad, forceful language spoken by giant kin and their kindred.',
    'Gnomish' => 'A clever, busy language used among inventive communities.',
    'Goblin' => 'A sharp and practical tongue shared among goblinoids.',
    'Halfling' => 'A warm, familiar language common in close-knit halfling culture.',
    'Infernal' => 'A precise, authoritarian fiendish language of devils and contracts.',
    'Orc' => 'A forceful language tied to strength, endurance, and clan identity.',
    'Primordial' => 'A broad elemental language linked to the raw forces of creation.',
    'Sylvan' => 'A musical tongue associated with fey crossings and wild magic.',
    'Undercommon' => 'A subterranean trade language used beneath the surface world.',
];

$magicSchools = [
    'Abjuration' => 'Protective magic that wards, banishes, or nullifies threats.',
    'Conjuration' => 'Magic that summons creatures, objects, or energies.',
    'Divination' => 'Magic that reveals information, truths, or hidden paths.',
    'Enchantment' => 'Magic that influences minds, emotions, and choices.',
    'Evocation' => 'Magic that channels elemental and raw arcane power outward.',
    'Illusion' => 'Magic that deceives the senses or alters appearances.',
    'Necromancy' => 'Magic of life force, death, and the manipulation of vitality.',
    'Transmutation' => 'Magic that changes matter, form, or physical properties.',
];

$weaponProperties = [
    'Ammunition' => 'Requires ammunition to make attacks at range.',
    'Finesse' => 'Lets you use Strength or Dexterity for attack and damage rolls.',
    'Heavy' => 'Harder for smaller creatures to use effectively.',
    'Light' => 'Easy to handle and often paired for dual wielding.',
    'Loading' => 'Restricts the number of shots you can make with it each turn.',
    'Range' => 'Can attack effectively at a listed normal and long distance.',
    'Reach' => 'Extends your melee reach beyond the usual 5 feet.',
    'Thrown' => 'Can be hurled as a ranged attack using listed range values.',
    'Two-Handed' => 'Requires two hands when you attack with it.',
    'Versatile' => 'Can be wielded one-handed or two-handed for different damage.',
];

$weaponMasteries = [
    'Cleave' => 'Lets wide-swinging weapons threaten a nearby second target.',
    'Graze' => 'Allows a miss to still chip away at a target.',
    'Nick' => 'Pairs well with dual-weapon play by smoothing follow-up strikes.',
    'Push' => 'Drives a target backward after a solid hit.',
    'Sap' => 'Disrupts a foe and blunts their offensive pressure.',
    'Slow' => 'Reduces the target’s mobility after being struck.',
    'Topple' => 'Knocks or threatens to knock the target prone.',
    'Vex' => 'Sets up more accurate follow-up attacks against the same foe.',
];

$equipmentCategories = [
    'Armor' => 'Protective gear such as light armor, medium armor, heavy armor, and shields.',
    'Weapons' => 'Simple and martial melee or ranged weapons used in combat.',
    'Tools' => 'Practical kits, instruments, and specialty equipment for noncombat tasks.',
    'Adventuring Gear' => 'General supplies used for travel, survival, and exploration.',
    'Arcane Focus' => 'Mystical items used to channel arcane spellcasting.',
    'Druidic Focus' => 'Natural implements used to channel primal druidic magic.',
    'Holy Symbol' => 'Sacred objects used as divine spellcasting foci.',
    'Pack' => 'Bundled gear designed for common adventuring roles.',
];

$roleplay = require __DIR__.'/dnd_roleplay.php';
$spells = require __DIR__.'/dnd_spells.php';
$monsters = require __DIR__.'/dnd_monsters.php';

$sectionBuilder = static function (string $key, string $title, array $items): array {
    return [
        'key' => $key,
        'title' => $title,
        'count' => count($items),
        'items' => array_values($items),
    ];
};

$namedSectionItems = static function (array $items, array $extra = []): array {
    $mapped = [];

    foreach ($items as $name => $detail) {
        $detail = is_array($detail) ? $detail : ['summary' => $detail];
        $mapped[] = array_merge([
            'name' => $name,
            'summary' => $detail['summary'] ?? '',
        ], array_diff_key($detail, ['summary' => true]), $extra);
    }

    return $mapped;
};

$flatSectionItems = static function (array $items): array {
    return array_map(static fn (string $name): array => ['name' => $name, 'summary' => ''], $items);
};

$compendium = [
    'classes' => $sectionBuilder('classes', 'Classes', $namedSectionItems($classes)),
    'species' => $sectionBuilder('species', 'Species', $namedSectionItems($species)),
    'backgrounds' => $sectionBuilder('backgrounds', 'Backgrounds', $namedSectionItems($backgrounds)),
    'origin_feats' => $sectionBuilder('origin_feats', 'Origin Feats', $namedSectionItems($originFeats)),
    'abilities' => $sectionBuilder('abilities', 'Ability Scores', $namedSectionItems($abilities)),
    'skills' => $sectionBuilder('skills', 'Skills', $namedSectionItems($skills)),
    'conditions' => $sectionBuilder('conditions', 'Conditions', $namedSectionItems($conditions)),
    'damage_types' => $sectionBuilder('damage_types', 'Damage Types', $namedSectionItems($damageTypes)),
    'alignments' => $sectionBuilder('alignments', 'Alignments', $namedSectionItems($alignments)),
    'languages' => $sectionBuilder('languages', 'Languages', $namedSectionItems($languages)),
    'magic_schools' => $sectionBuilder('magic_schools', 'Magic Schools', $namedSectionItems($magicSchools)),
    'spells' => $sectionBuilder('spells', 'Spells', $spells),
    'monsters' => $sectionBuilder('monsters', 'Monsters', $monsters),
    'weapon_properties' => $sectionBuilder('weapon_properties', 'Weapon Properties', $namedSectionItems($weaponProperties)),
    'weapon_masteries' => $sectionBuilder('weapon_masteries', 'Weapon Masteries', $namedSectionItems($weaponMasteries)),
    'equipment_categories' => $sectionBuilder('equipment_categories', 'Equipment Categories', $namedSectionItems($equipmentCategories)),
];

return [
    'verified_at' => '2026-03-30',
    'source_note' => 'Verified against current official D&D Beyond 2024 / 5.5e rules pages and Player\'s Handbook update posts, with spell metadata generated from the official 5.5e Core Rules spell index and monster metadata generated from the official 2024 Basic Rules creature stat blocks.',
    'classes' => array_keys($classes),
    'species' => array_keys($species),
    'backgrounds' => array_keys($backgrounds),
    'origin_feats' => array_keys($originFeats),
    'abilities' => array_keys($abilities),
    'skills' => array_keys($skills),
    'conditions' => array_keys($conditions),
    'damage_types' => array_keys($damageTypes),
    'alignments' => array_keys($alignments),
    'languages' => array_keys($languages),
    'magic_schools' => array_keys($magicSchools),
    'weapon_properties' => array_keys($weaponProperties),
    'weapon_masteries' => array_keys($weaponMasteries),
    'equipment_categories' => array_keys($equipmentCategories),
    'class_details' => $classes,
    'species_details' => $species,
    'background_details' => $backgrounds,
    'origin_feat_details' => $originFeats,
    'ability_details' => $abilities,
    'alignment_details' => $alignments,
    'skill_details' => $skills,
    'condition_details' => $conditions,
    'damage_type_details' => $damageTypes,
    'language_details' => $languages,
    'alignment_roleplay' => $roleplay['alignment_profiles'],
    'alignment_axis_traits' => $roleplay['alignment_axis_traits'],
    'roleplay_field_help' => $roleplay['roleplay_field_help'],
    'appearance_field_help' => $roleplay['appearance_field_help'],
    'form_placeholder_profiles' => $roleplay['form_placeholder_profiles'],
    'ability_appearance_cues' => $roleplay['ability_appearance_cues'],
    'magic_school_details' => $magicSchools,
    'weapon_property_details' => $weaponProperties,
    'weapon_mastery_details' => $weaponMasteries,
    'equipment_category_details' => $equipmentCategories,
    'monster_count' => count($monsters),
    'compendium_sections' => array_map(
        static fn (array $section): array => [
            'key' => $section['key'],
            'title' => $section['title'],
            'count' => $section['count'],
        ],
        $compendium
    ),
    'compendium' => $compendium,
];
