# Adventurer's Ledger

Adventurer's Ledger is a Laravel-based D&D companion app with its own local API, MySQL storage, and browser-side autosave.

The project combines:
- a character builder
- a player-facing rules wizard
- a roster page for saved characters
- a homebrew workshop
- a separate DM desk with its own DM wizard and reusable DM records

The browser pages call the Laravel API with `fetch(...)`, so the frontend never talks directly to MySQL.

## Main Pages

- `/`
  Builder page with the character form, ability tracker, player wizard, dice tray, and local rules library.
- `/roster`
  Saved characters with search and popup editing.
- `/homebrew`
  Custom entries that stay separate from the official builder data.
- `/dm`
  Session tools, encounter tracking, monster lookup, DM wizard, and DM record management.

## Rules Handling

The app is built around a local 2024 rules catalog.

Important nuance:
- the builder and wizard use verified official options as the main reference
- homebrew stays separate by default
- when a build drifts away from the strict official 2024 baseline, the app now shows non-blocking warnings instead of silently pretending everything is official

Examples of these warnings:
- table-specific advancement methods outside the default official baseline
- language choices that no longer match the usual official starting package
- evil alignments that should be cleared with the DM first
- flexible builder areas where the app is intentionally looser than the printed package

## API Overview

The API routes are defined in [routes/api.php](./routes/api.php).

Main endpoint groups:
- `GET /api`
- `GET /api/configurator`
- `GET /api/compendium`
- `GET /api/compendium/{section}`
- `GET /api/characters`
- `GET /api/characters/{id}`
- `POST /api/characters`
- `PUT /api/characters/{character}`
- `DELETE /api/characters/{id}`
- `POST /api/roll-dice`
- `POST /api/roll-stats`
- `POST /api/rules-wizard/message`
- `POST /api/dm-wizard/message`
- `GET /api/homebrew`
- `POST /api/homebrew`
- `PUT /api/homebrew/{homebrewEntry}`
- `DELETE /api/homebrew/{homebrewEntry}`
- `GET /api/dm-records`
- `POST /api/dm-records`
- `PUT /api/dm-records/{dmRecord}`
- `DELETE /api/dm-records/{dmRecord}`
- `POST /api/dm-records/{dmRecord}/export-homebrew`

The `/api` root is also a self-describing overview. It returns:
- grouped endpoint lists
- processing trees
- route purposes
- request and response notes

## How Requests Flow

The request flow is:

1. A page sends an HTTP request such as `GET`, `POST`, `PUT`, or `DELETE`.
2. Laravel matches that request in [routes/api.php](./routes/api.php).
3. A controller handles the request.
4. Request classes and support classes normalize and validate the data.
5. Models and support classes read from or write to MySQL.
6. The controller returns JSON to the browser.

Examples:
- `GET /api/characters` reads saved characters from the database.
- `POST /api/characters` validates input and stores a new character.
- `PUT /api/homebrew/{homebrewEntry}` updates an existing homebrew entry.
- `POST /api/rules-wizard/message` sends a player-wizard command and gets the next reply.
- `POST /api/dm-wizard/message` sends a DM-only wizard command and gets the next reply.

## Main Technical Structure

- Laravel for routing, controllers, requests, models, and services
- Blade templates for page rendering
- MySQL for persistent data
- Plain JavaScript with `fetch(...)` for browser interactivity
- `localStorage` for local draft recovery on the main working pages

Important backend pieces include:
- request validation classes in [app/Http/Requests](./app/Http/Requests)
- shared input cleanup and validation logic in [app/Support](./app/Support)
- wizard logic in [app/Services](./app/Services)

## Local Setup

1. Install dependencies:

```bash
composer install
```

2. Create your environment file and configure the database:

```bash
copy .env.example .env
```

3. Make sure MySQL has a database named `dnd_api`, then run:

```bash
php artisan migrate
```

4. Start the app:

```bash
php artisan serve --port=8001
```

If `php artisan serve` says it cannot find `artisan`, make sure you are inside the `dnd-api` folder before running the command.

## Quality and Testing

The project includes feature tests for the page shells and the main API flows.

Useful commands:

```bash
php artisan test
php artisan view:cache
php artisan optimize:clear
```

Current smoke coverage includes:
- page loading for Builder, DM, Roster, and Homebrew
- shared primary navigation
- `/api` root documentation output
- configurator and compendium API responses
- character creation and update flows
- dice API
- player wizard API
- DM wizard API
- DM record CRUD and export flow
- non-blocking official-rules warnings in the wizard snapshot

Current feature test count: `36`.

## Notes

- Official builder/configurator data stays separate from homebrew data.
- DM records stay separate from Homebrew until they are explicitly exported.
- The player wizard and DM wizard are separate systems on purpose.
- The app warns about official-rules drift, but it does not hard-lock every flexible table variant.
