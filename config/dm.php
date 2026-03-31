<?php
// Developer context: This config file is the shared source of truth for DM-side record kinds, statuses, and guided NPC choices used by the DM wizard, DM records API, and DM page UI.
// Clear explanation: This file lists the main options the Dungeon Master tools use, like record types, save statuses, and NPC behavior choices.

return [
    'kinds' => [
        'npc' => [
            'label' => 'NPC',
            'hint' => 'Build a roleplay-ready NPC with optional combat support.',
        ],
        'scene' => [
            'label' => 'Scene',
            'hint' => 'Capture the current situation, stakes, pressure, and clues.',
        ],
        'quest' => [
            'label' => 'Quest',
            'hint' => 'Track a hook, objective, reward, and the problems around it.',
        ],
        'location' => [
            'label' => 'Location',
            'hint' => 'Store a place with sensory anchors, hazards, and secrets.',
        ],
        'encounter' => [
            'label' => 'Encounter',
            'hint' => 'Plan terrain, enemy pressure, objectives, and initiative-ready participants.',
        ],
        'loot' => [
            'label' => 'Loot',
            'hint' => 'Track rewards, treasure sources, and clue-tied payoffs.',
        ],
    ],
    'statuses' => [
        'draft' => [
            'label' => 'Draft',
            'hint' => 'Still being built or refined.',
        ],
        'ready' => [
            'label' => 'Ready',
            'hint' => 'Prepared and ready for the table.',
        ],
        'active' => [
            'label' => 'Active',
            'hint' => 'In play right now or part of the current session.',
        ],
        'archived' => [
            'label' => 'Archived',
            'hint' => 'Kept for reference after the scene or arc has passed.',
        ],
    ],
    'npc_attitudes' => [
        'friendly' => [
            'label' => 'Friendly',
            'hint' => 'Open to trust, comfort, or cooperation if handled well.',
        ],
        'indifferent' => [
            'label' => 'Indifferent',
            'hint' => 'Neutral until the party gives a reason to care.',
        ],
        'hostile' => [
            'label' => 'Hostile',
            'hint' => 'Starts resistant, suspicious, or openly opposed.',
        ],
    ],
    'npc_combat_modes' => [
        'narrative_only' => [
            'label' => 'Narrative only',
            'hint' => 'No combat block, just roleplay and situation notes.',
        ],
        'quick_stats' => [
            'label' => 'Quick stats',
            'hint' => 'Light combat numbers for initiative, AC, HP, and attack notes.',
        ],
        'monster_backed' => [
            'label' => 'Monster-backed',
            'hint' => 'Use a local monster entry as the combat base and layer NPC notes on top.',
        ],
    ],
];
