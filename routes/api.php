<?php
// Developer context: Laravel reads this route file to map API URLs to controller methods in App\Http\Controllers\Api; each entry below is an HTTP endpoint.
// Clear explanation: This file lists the API addresses for the app, such as characters, dice, wizard, compendium, and homebrew.

use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\CompendiumController;
use App\Http\Controllers\Api\ConfiguratorController;
use App\Http\Controllers\Api\DiceController;
use App\Http\Controllers\Api\DmRecordController;
use App\Http\Controllers\Api\DmWizardController;
use App\Http\Controllers\Api\HomebrewController;
use App\Http\Controllers\Api\RulesWizardController;
use Illuminate\Support\Facades\Route;

// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::get('/', static function () {
    return response()->json([
        'name' => "Adventurer's Ledger API",
        'message' => 'This is the API entry point for characters, dice, wizards, homebrew, and DM records.',
        'summary' => 'The site pages call these endpoints with fetch requests, Laravel validates the input, and the controllers return JSON back to the browser.',
        'data_flow' => [
            'page -> fetch request -> routes/api.php -> FormRequest validation -> controller/service -> model -> database -> JSON response',
        ],
        'groups' => [
            [
                'title' => 'Builder and sheet data',
                'routes' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/configurator',
                        'purpose' => 'Loads the builder options and verified rules lists for the main page.',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/compendium',
                        'purpose' => 'Returns the local compendium index that powers the library.',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/compendium/{section}',
                        'purpose' => 'Loads one compendium section such as classes, monsters, or spells.',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/characters',
                        'purpose' => 'Reads the saved character roster from the database.',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/characters/{id}',
                        'purpose' => 'Loads one saved character by id.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/characters',
                        'purpose' => 'Validates and saves a new character record.',
                    ],
                    [
                        'method' => 'PUT',
                        'path' => '/api/characters/{character}',
                        'purpose' => 'Validates and updates an existing character record.',
                    ],
                    [
                        'method' => 'DELETE',
                        'path' => '/api/characters/{id}',
                        'purpose' => 'Removes a saved character from the roster.',
                    ],
                ],
            ],
            [
                'title' => 'Dice and wizard tools',
                'routes' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/roll-dice',
                        'purpose' => 'Rolls a dice expression such as 2d6+3 and returns the result details.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/roll-stats',
                        'purpose' => 'Rolls full ability scores for a character sheet.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/rules-wizard/message',
                        'purpose' => 'Processes the player-facing wizard commands and returns the updated wizard state.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/dm-wizard/message',
                        'purpose' => 'Processes the DM-only wizard commands and returns the updated DM wizard state.',
                    ],
                ],
            ],
            [
                'title' => 'Homebrew and DM records',
                'routes' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/homebrew',
                        'purpose' => 'Loads the saved homebrew entries.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/homebrew',
                        'purpose' => 'Validates and saves a new homebrew entry.',
                    ],
                    [
                        'method' => 'PUT',
                        'path' => '/api/homebrew/{homebrewEntry}',
                        'purpose' => 'Updates one saved homebrew entry.',
                    ],
                    [
                        'method' => 'DELETE',
                        'path' => '/api/homebrew/{homebrewEntry}',
                        'purpose' => 'Deletes one homebrew entry.',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/dm-records',
                        'purpose' => 'Loads the reusable DM records shelf.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/dm-records',
                        'purpose' => 'Saves a new DM record such as an NPC, scene, quest, or encounter.',
                    ],
                    [
                        'method' => 'PUT',
                        'path' => '/api/dm-records/{dmRecord}',
                        'purpose' => 'Updates an existing DM record.',
                    ],
                    [
                        'method' => 'DELETE',
                        'path' => '/api/dm-records/{dmRecord}',
                        'purpose' => 'Deletes a saved DM record.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/dm-records/{dmRecord}/export-homebrew',
                        'purpose' => 'Copies a DM record into the separate homebrew system on purpose.',
                    ],
                ],
            ],
        ],
        'processing_trees' => [
            [
                'name' => 'Configurator read',
                'endpoint' => ['method' => 'GET', 'path' => '/api/configurator'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::get("/configurator") sends the request to ConfiguratorController@index.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/ConfiguratorController.php',
                        'content' => 'ConfiguratorController@index returns config("dnd") as JSON.',
                    ],
                    [
                        'layer' => 'Config data',
                        'file' => 'config/dnd.php',
                        'content' => 'This config file contains the verified local builder options, such as classes, species, backgrounds, origin feats, and compendium section metadata.',
                    ],
                    [
                        'layer' => 'Response',
                        'file' => 'resources/views/welcome.blade.php',
                        'content' => 'The builder page reads that JSON through fetch() and uses it to fill the form and helper panels.',
                    ],
                ],
            ],
            [
                'name' => 'Compendium read',
                'endpoint' => ['method' => 'GET', 'path' => '/api/compendium/{section}'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::get("/compendium/{section}") sends the selected section slug to CompendiumController@show.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/CompendiumController.php',
                        'content' => 'CompendiumController@show reads one section from config("dnd.compendium") and returns it as JSON, or returns a 404 JSON error when the section does not exist.',
                    ],
                    [
                        'layer' => 'Config data',
                        'file' => 'config/dnd.php',
                        'content' => 'The compendium data contains the local rules library, such as classes, spells, monsters, conditions, and other verified reference sections.',
                    ],
                    [
                        'layer' => 'Response',
                        'file' => 'resources/views/welcome.blade.php',
                        'content' => 'The library on the site requests one section at a time and renders the returned JSON cards in the browser.',
                    ],
                ],
            ],
            [
                'name' => 'Character save',
                'endpoint' => ['method' => 'POST', 'path' => '/api/characters'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::post("/characters") sends the request to CharacterController@store.',
                    ],
                    [
                        'layer' => 'Request validation',
                        'file' => 'app/Http/Requests/StoreCharacterRequest.php',
                        'content' => 'StoreCharacterRequest calls CharacterDataValidator to normalize the raw form input and apply the shared save rules before the controller runs.',
                    ],
                    [
                        'layer' => 'Shared validator',
                        'file' => 'app/Support/CharacterDataValidator.php',
                        'content' => 'CharacterDataValidator cleans text, normalizes lists like languages and skills, checks allowed rules values from config, and blocks invalid character payloads.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/CharacterController.php',
                        'content' => 'CharacterController@store pulls the validated data from the request and asks CharacterHitPointRoller for rolled HP metadata.',
                    ],
                    [
                        'layer' => 'Support helper',
                        'file' => 'app/Support/CharacterHitPointRoller.php',
                        'content' => 'CharacterHitPointRoller adds or updates rolled hit point metadata so the sheet keeps its real dice-based HP values.',
                    ],
                    [
                        'layer' => 'Model',
                        'file' => 'app/Models/Character.php',
                        'content' => 'The Character Eloquent model writes the final payload into the characters table in MySQL.',
                    ],
                    [
                        'layer' => 'Database and response',
                        'file' => 'database/migrations/*characters*.php',
                        'content' => 'The characters table stores the saved sheet, and the controller returns the new record as JSON so the page can refresh the roster and builder state.',
                    ],
                ],
            ],
            [
                'name' => 'Character read',
                'endpoint' => ['method' => 'GET', 'path' => '/api/characters'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::get("/characters") sends the request to CharacterController@index.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/CharacterController.php',
                        'content' => 'CharacterController@index loads the newest Character records with Character::latest()->get().',
                    ],
                    [
                        'layer' => 'Model',
                        'file' => 'app/Models/Character.php',
                        'content' => 'The Character model reads the stored roster from the characters table through Laravel Eloquent.',
                    ],
                    [
                        'layer' => 'Response',
                        'file' => 'resources/views/welcome.blade.php and resources/views/roster.blade.php',
                        'content' => 'The builder page, roster page, and DM page consume that JSON to show the saved party data.',
                    ],
                ],
            ],
            [
                'name' => 'Dice roll',
                'endpoint' => ['method' => 'POST', 'path' => '/api/roll-dice'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::post("/roll-dice") sends the request to DiceController@roll.',
                    ],
                    [
                        'layer' => 'Request validation',
                        'file' => 'app/Http/Requests/RollDiceRequest.php',
                        'content' => 'RollDiceRequest cleans the dice expression and validates fields like expression length and mode.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/DiceController.php',
                        'content' => 'DiceController@roll reads the validated input and passes it to DiceRoller.',
                    ],
                    [
                        'layer' => 'Support helper',
                        'file' => 'app/Support/DiceRoller.php',
                        'content' => 'DiceRoller parses the dice expression and rolls real dice values instead of using fake min-max placeholders.',
                    ],
                    [
                        'layer' => 'Response',
                        'file' => 'resources/views/welcome.blade.php and resources/views/dm.blade.php',
                        'content' => 'The JSON response returns the expression, mode, total, and detail so the builder and DM desk can display the roll result.',
                    ],
                ],
            ],
            [
                'name' => 'Player wizard message',
                'endpoint' => ['method' => 'POST', 'path' => '/api/rules-wizard/message'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::post("/rules-wizard/message") sends the request to RulesWizardController@message.',
                    ],
                    [
                        'layer' => 'Request validation',
                        'file' => 'app/Http/Requests/RulesWizardMessageRequest.php',
                        'content' => 'RulesWizardMessageRequest normalizes the free-text message, sanitizes the nested wizard state, and validates the request envelope.',
                    ],
                    [
                        'layer' => 'State sanitizer',
                        'file' => 'app/Support/RulesWizardStateSanitizer.php',
                        'content' => 'RulesWizardStateSanitizer whitelists the top-level state keys and cleans the character and dungeon subtrees before the wizard uses them.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/RulesWizardController.php',
                        'content' => 'RulesWizardController@message hands the cleaned message and state to RulesWizardService.',
                    ],
                    [
                        'layer' => 'Service',
                        'file' => 'app/Services/RulesWizardService.php',
                        'content' => 'RulesWizardService interprets the command, updates the wizard state, may call shared validation/save helpers, and builds the wizard reply payload.',
                    ],
                    [
                        'layer' => 'Optional save path',
                        'file' => 'app/Support/CharacterDataValidator.php and app/Models/Character.php',
                        'content' => 'When the player wizard saves a sheet, it reuses the same character validation path as the normal character API and then writes to the characters table.',
                    ],
                    [
                        'layer' => 'Response',
                        'file' => 'resources/views/welcome.blade.php',
                        'content' => 'The builder page receives the next wizard log entries plus the updated wizard state as JSON.',
                    ],
                ],
            ],
            [
                'name' => 'DM wizard message',
                'endpoint' => ['method' => 'POST', 'path' => '/api/dm-wizard/message'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::post("/dm-wizard/message") sends the request to DmWizardController@message.',
                    ],
                    [
                        'layer' => 'Request validation',
                        'file' => 'app/Http/Requests/DmWizardMessageRequest.php',
                        'content' => 'DmWizardMessageRequest cleans the DM message, sanitizes the nested DM wizard state, and validates the request envelope.',
                    ],
                    [
                        'layer' => 'State sanitizer',
                        'file' => 'app/Support/DmWizardStateSanitizer.php',
                        'content' => 'DmWizardStateSanitizer keeps only the allowed DM wizard state keys and cleans the draft record plus page-linkage metadata.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/DmWizardController.php',
                        'content' => 'DmWizardController@message forwards the cleaned message and state to DmWizardService.',
                    ],
                    [
                        'layer' => 'Service',
                        'file' => 'app/Services/DmWizardService.php',
                        'content' => 'DmWizardService handles DM-specific commands like NPC, scene, quest, location, encounter, and loot creation, and prepares DM record drafts or save actions.',
                    ],
                    [
                        'layer' => 'Optional save path',
                        'file' => 'app/Support/DmRecordDataValidator.php and app/Models/DmRecord.php',
                        'content' => 'When the DM wizard saves a draft as a reusable record, it uses the shared DM record validator and then writes to the dm_records table.',
                    ],
                    [
                        'layer' => 'Response',
                        'file' => 'resources/views/dm.blade.php',
                        'content' => 'The DM page receives the next DM wizard reply, updated draft state, and any session or encounter linkage flags as JSON.',
                    ],
                ],
            ],
            [
                'name' => 'Homebrew save',
                'endpoint' => ['method' => 'POST', 'path' => '/api/homebrew'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::post("/homebrew") sends the request to HomebrewController@store.',
                    ],
                    [
                        'layer' => 'Request validation',
                        'file' => 'app/Http/Requests/StoreHomebrewEntryRequest.php',
                        'content' => 'StoreHomebrewEntryRequest uses PlainTextNormalizer plus category/status rules from config to clean and validate the homebrew payload.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/HomebrewController.php',
                        'content' => 'HomebrewController@store asks the request for the cleaned entryData() array and passes it to the model.',
                    ],
                    [
                        'layer' => 'Model',
                        'file' => 'app/Models/HomebrewEntry.php',
                        'content' => 'The HomebrewEntry model writes the cleaned custom content into the homebrew_entries table.',
                    ],
                    [
                        'layer' => 'Database and response',
                        'file' => 'database/migrations/*homebrew*.php',
                        'content' => 'The homebrew_entries table stores the separate custom content, and the controller returns the saved entry as JSON to the workshop page.',
                    ],
                ],
            ],
            [
                'name' => 'DM record save',
                'endpoint' => ['method' => 'POST', 'path' => '/api/dm-records'],
                'tree' => [
                    [
                        'layer' => 'Route',
                        'file' => 'routes/api.php',
                        'content' => 'Route::post("/dm-records") sends the request to DmRecordController@store.',
                    ],
                    [
                        'layer' => 'Request validation',
                        'file' => 'app/Http/Requests/StoreDmRecordRequest.php',
                        'content' => 'StoreDmRecordRequest normalizes the record first and then applies the shared DM record validation rules.',
                    ],
                    [
                        'layer' => 'Shared validator',
                        'file' => 'app/Support/DmRecordDataValidator.php',
                        'content' => 'DmRecordDataValidator cleans top-level fields, validates the selected record kind, and checks the nested payload for NPCs, scenes, quests, locations, encounters, or loot.',
                    ],
                    [
                        'layer' => 'Controller',
                        'file' => 'app/Http/Controllers/Api/DmRecordController.php',
                        'content' => 'DmRecordController@store takes the cleaned recordData() array and saves it with the model.',
                    ],
                    [
                        'layer' => 'Model',
                        'file' => 'app/Models/DmRecord.php',
                        'content' => 'The DmRecord model writes the reusable DM content into the dm_records table and can load its linked homebrew entry when needed.',
                    ],
                    [
                        'layer' => 'Database and response',
                        'file' => 'database/migrations/*dm_records*.php',
                        'content' => 'The dm_records table stores reusable DM assets, and the controller returns the saved record as JSON so the DM page can refresh its record shelf.',
                    ],
                ],
            ],
        ],
        'full_reference' => [
            [
                'title' => 'Character API',
                'used_by_pages' => ['Builder', 'Roster', 'DM Desk'],
                'routes' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/characters',
                        'request_content' => 'No request body. The page only asks for the saved roster.',
                        'validation' => 'No FormRequest is needed here because this is a read endpoint.',
                        'uses' => [
                            'app/Http/Controllers/Api/CharacterController.php@index',
                            'app/Models/Character.php',
                        ],
                        'reads_or_writes' => 'Reads the characters table through the Character model.',
                        'returns' => 'A JSON array of saved characters, ordered from newest to oldest.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/characters',
                        'request_content' => [
                            'required_fields' => ['name', 'species', 'class', 'subclass', 'skill_proficiencies', 'background', 'origin_feat', 'advancement_method', 'languages', 'level', 'strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'],
                            'optional_fields' => ['alignment', 'skill_expertise', 'personality_traits', 'ideals', 'goals', 'bonds', 'flaws', 'age', 'height', 'weight', 'eyes', 'hair', 'skin', 'notes'],
                        ],
                        'validation' => [
                            'request' => 'app/Http/Requests/StoreCharacterRequest.php',
                            'shared_validator' => 'app/Support/CharacterDataValidator.php',
                        ],
                        'uses' => [
                            'app/Http/Controllers/Api/CharacterController.php@store',
                            'app/Support/CharacterHitPointRoller.php',
                            'app/Models/Character.php',
                        ],
                        'reads_or_writes' => 'Writes a cleaned character record plus rolled HP metadata into the characters table.',
                        'returns' => 'A 201 JSON response with a success message and the saved character record.',
                    ],
                    [
                        'method' => 'PUT',
                        'path' => '/api/characters/{character}',
                        'request_content' => 'Uses the same character payload shape as POST /api/characters.',
                        'validation' => 'Uses the same StoreCharacterRequest and CharacterDataValidator flow as create.',
                        'uses' => [
                            'app/Http/Controllers/Api/CharacterController.php@update',
                            'app/Support/CharacterHitPointRoller.php',
                            'app/Models/Character.php',
                        ],
                        'reads_or_writes' => 'Updates an existing row in the characters table.',
                        'returns' => 'A JSON response with a success message and the refreshed saved character.',
                    ],
                ],
            ],
            [
                'title' => 'Configurator and Compendium API',
                'used_by_pages' => ['Builder', 'DM Desk'],
                'routes' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/configurator',
                        'request_content' => 'No request body. The page only asks for the local rules catalog.',
                        'validation' => 'No FormRequest is needed here because this is a read endpoint.',
                        'uses' => [
                            'app/Http/Controllers/Api/ConfiguratorController.php@index',
                            'config/dnd.php',
                        ],
                        'reads_or_writes' => 'Reads the local rules config, not the database.',
                        'returns' => 'JSON with classes, species, backgrounds, feats, rules metadata, and compendium section references.',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/compendium and /api/compendium/{section}',
                        'request_content' => 'Either no section or one section slug such as classes, spells, or monsters.',
                        'validation' => 'The controller checks whether the requested section exists and returns 404 JSON if it does not.',
                        'uses' => [
                            'app/Http/Controllers/Api/CompendiumController.php',
                            'config/dnd.php',
                        ],
                        'reads_or_writes' => 'Reads the local compendium config, not the database.',
                        'returns' => 'JSON with the compendium index or one requested section of local rules data.',
                    ],
                ],
            ],
            [
                'title' => 'Dice API',
                'used_by_pages' => ['Builder', 'DM Desk'],
                'routes' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/roll-dice',
                        'request_content' => [
                            'fields' => ['expression', 'mode'],
                            'example' => ['expression' => '2d6+3', 'mode' => 'advantage or disadvantage when relevant'],
                        ],
                        'validation' => [
                            'request' => 'app/Http/Requests/RollDiceRequest.php',
                            'helper' => 'app/Support/PlainTextNormalizer.php',
                        ],
                        'uses' => [
                            'app/Http/Controllers/Api/DiceController.php@roll',
                            'app/Support/DiceRoller.php',
                        ],
                        'reads_or_writes' => 'Does not write to the database. It calculates and returns a dice result.',
                        'returns' => 'JSON with expression, mode, total, and detailed roll breakdown.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/roll-stats',
                        'request_content' => 'No request body is required.',
                        'validation' => 'No extra request class is needed because the endpoint generates the rolls itself.',
                        'uses' => [
                            'app/Http/Controllers/Api/DiceController.php@rollStats',
                            'app/Support/DiceRoller.php',
                        ],
                        'reads_or_writes' => 'Does not write to the database. It rolls six ability scores.',
                        'returns' => 'JSON with each ability score total plus detailed 4d6-drop-lowest roll data.',
                    ],
                ],
            ],
            [
                'title' => 'Player Wizard API',
                'used_by_pages' => ['Builder'],
                'routes' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/rules-wizard/message',
                        'request_content' => [
                            'fields' => ['message', 'state'],
                            'message_examples' => ['new character', 'level up', 'show summary', 'help me roleplay'],
                            'state_content' => 'The state can contain pending_field, skipped_optional_fields, character, and dungeon.',
                        ],
                        'validation' => [
                            'request' => 'app/Http/Requests/RulesWizardMessageRequest.php',
                            'state_sanitizer' => 'app/Support/RulesWizardStateSanitizer.php',
                            'shared_character_rules' => 'app/Support/CharacterDataValidator.php',
                        ],
                        'uses' => [
                            'app/Http/Controllers/Api/RulesWizardController.php@message',
                            'app/Services/RulesWizardService.php',
                        ],
                        'reads_or_writes' => 'Usually updates only the wizard state in JSON. When the wizard saves a sheet, it writes to the characters table through the shared character save path.',
                        'returns' => 'JSON with the next wizard reply, updated wizard state, and any save results or dungeon state updates.',
                    ],
                ],
            ],
            [
                'title' => 'DM Wizard and DM Records API',
                'used_by_pages' => ['DM Desk'],
                'routes' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/dm-wizard/message',
                        'request_content' => [
                            'fields' => ['message', 'state'],
                            'message_examples' => ['new npc', 'new scene', 'save record', 'show monster goblin', 'export to homebrew'],
                            'state_content' => 'The state can contain flow_kind, pending_field, skipped_optional_fields, draft_record, and page_linkage.',
                        ],
                        'validation' => [
                            'request' => 'app/Http/Requests/DmWizardMessageRequest.php',
                            'state_sanitizer' => 'app/Support/DmWizardStateSanitizer.php',
                            'shared_record_rules' => 'app/Support/DmRecordDataValidator.php',
                        ],
                        'uses' => [
                            'app/Http/Controllers/Api/DmWizardController.php@message',
                            'app/Services/DmWizardService.php',
                        ],
                        'reads_or_writes' => 'Usually returns the next DM wizard draft state. When the wizard saves a reusable record, it writes to the dm_records table.',
                        'returns' => 'JSON with the next DM wizard reply, updated draft record, and linkage hints for the DM page tools.',
                    ],
                    [
                        'method' => 'GET / POST / PUT / DELETE',
                        'path' => '/api/dm-records',
                        'request_content' => 'DM records carry top-level fields like kind, status, name, summary, campaign, session_label, tags, and a nested payload for NPC, scene, quest, location, encounter, or loot.',
                        'validation' => [
                            'request' => 'app/Http/Requests/StoreDmRecordRequest.php',
                            'shared_validator' => 'app/Support/DmRecordDataValidator.php',
                        ],
                        'uses' => [
                            'app/Http/Controllers/Api/DmRecordController.php',
                            'app/Models/DmRecord.php',
                        ],
                        'reads_or_writes' => 'Reads and writes the dm_records table.',
                        'returns' => 'JSON with saved DM records, config-backed kind/status lists, and individual save/update/delete messages.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/dm-records/{dmRecord}/export-homebrew',
                        'request_content' => 'No large request body is needed. The route uses the selected saved DM record.',
                        'validation' => 'The record already exists, so the controller only needs the bound DmRecord model.',
                        'uses' => [
                            'app/Http/Controllers/Api/DmRecordController.php@exportToHomebrew',
                            'app/Support/DmRecordHomebrewExporter.php',
                            'app/Models\HomebrewEntry.php',
                        ],
                        'reads_or_writes' => 'Reads a DM record, creates a separate homebrew entry, and stores the link back on the DM record.',
                        'returns' => 'JSON with the updated DM record and the new linked homebrew entry.',
                    ],
                ],
            ],
            [
                'title' => 'Homebrew API',
                'used_by_pages' => ['Homebrew Workshop', 'DM Desk read-only view'],
                'routes' => [
                    [
                        'method' => 'GET / POST / PUT / DELETE',
                        'path' => '/api/homebrew',
                        'request_content' => 'Homebrew entries use fields like category, status, name, summary, details, source_notes, and tags.',
                        'validation' => [
                            'request' => 'app/Http/Requests/StoreHomebrewEntryRequest.php',
                            'normalizer' => 'app/Support/PlainTextNormalizer.php',
                        ],
                        'uses' => [
                            'app/Http/Controllers/Api/HomebrewController.php',
                            'app/Models/HomebrewEntry.php',
                            'config/homebrew.php',
                        ],
                        'reads_or_writes' => 'Reads and writes the homebrew_entries table while keeping that data separate from the official builder data.',
                        'returns' => 'JSON with saved homebrew entries, config-backed category/status lists, and individual save/update/delete messages.',
                    ],
                ],
            ],
        ],
        'endpoints' => [
            '/api/configurator',
            '/api/compendium',
            '/api/characters',
            '/api/roll-dice',
            '/api/roll-stats',
            '/api/rules-wizard/message',
            '/api/dm-wizard/message',
            '/api/homebrew',
            '/api/dm-records',
        ],
    ]);
});
Route::get('/configurator', [ConfiguratorController::class, 'index']);
Route::get('/compendium', [CompendiumController::class, 'index']);
// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::get('/compendium/{section}', [CompendiumController::class, 'show']);

// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::get('/characters', [CharacterController::class, 'index']);
Route::get('/characters/{id}', [CharacterController::class, 'show']);
// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::post('/characters', [CharacterController::class, 'store']);
Route::put('/characters/{character}', [CharacterController::class, 'update']);
// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::delete('/characters/{id}', [CharacterController::class, 'destroy']);

// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::post('/roll-dice', [DiceController::class, 'roll']);
Route::post('/roll-stats', [DiceController::class, 'rollStats']);
// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::post('/rules-wizard/message', [RulesWizardController::class, 'message']);
Route::post('/dm-wizard/message', [DmWizardController::class, 'message']);

// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::get('/homebrew', [HomebrewController::class, 'index']);
Route::post('/homebrew', [HomebrewController::class, 'store']);
// Developer context: This route line connects one HTTP endpoint to the controller action that owns it.
// Clear explanation: This line tells the app which URL and request type should open this feature.
Route::put('/homebrew/{homebrewEntry}', [HomebrewController::class, 'update']);
Route::delete('/homebrew/{homebrewEntry}', [HomebrewController::class, 'destroy']);

// Developer context: These route lines connect the reusable DM records API and its export action to the controller methods that own them.
// Clear explanation: These lines define the saved-record API for the DM tools.
Route::get('/dm-records', [DmRecordController::class, 'index']);
Route::post('/dm-records', [DmRecordController::class, 'store']);
Route::put('/dm-records/{dmRecord}', [DmRecordController::class, 'update']);
Route::delete('/dm-records/{dmRecord}', [DmRecordController::class, 'destroy']);
Route::post('/dm-records/{dmRecord}/export-homebrew', [DmRecordController::class, 'exportToHomebrew']);
