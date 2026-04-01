# Adventurer's Ledger

Adventurer's Ledger is a Laravel-based D&D companion app with its own local API, MySQL storage, and browser-side autosave.

The project combines:
- a character builder
- a player-facing rules wizard
- a roster page for saved characters
- a homebrew workshop
- a separate DM desk with its own DM wizard and reusable DM records

The browser pages call the Laravel API with `fetch(...)`, so the frontend never talks directly to MySQL.

## GitHub ZIP Quickstart

The easiest beginner setup is:
- Windows
- XAMPP for MySQL and phpMyAdmin
- Composer installed separately
- Laravel started with `php artisan serve`

### 1. Rename the extracted folder

If GitHub extracts a path like `API_Basis-main\dnd-api`, do not keep working from that nested name.

Instead:
1. move the inner Laravel app folder to a clean location such as `C:\Projects`
2. rename the final folder to `adventurers-ledger`
3. open your terminal in that final folder

Recommended final path:

```text
C:\Projects\adventurers-ledger
```

Optional XAMPP organization path:

```text
C:\xampp\htdocs\adventurers-ledger
```

`htdocs` is optional. It can be convenient for future projects, but this app does not require Apache to serve the Laravel pages because the recommended startup command is `php artisan serve`.

### 2. Follow the setup guide for your OS

- Windows with XAMPP: [docs/setup/windows-xampp.md](./docs/setup/windows-xampp.md)
- macOS: [docs/setup/macos.md](./docs/setup/macos.md)
- Ubuntu or Debian: [docs/setup/ubuntu-debian.md](./docs/setup/ubuntu-debian.md)
- Dutch quickstart: [docs/snelstart-nl.md](./docs/snelstart-nl.md)

### 3. First-time commands

Fastest first-time setup:

```bash
composer run setup-local
composer run start-local
```

If you want a check-only step first:

```bash
composer install
composer run doctor
```

Open the app at:

```text
http://127.0.0.1:8001
```

## Local Setup Commands

- `composer run doctor`
  Checks the project folder, PHP version, `.env`, `APP_KEY`, writable Laravel paths, and the configured database connection.
- `composer run setup-local`
  Copies `.env` when needed, installs PHP dependencies, creates an `APP_KEY` when needed, runs the startup checks, and then runs migrations.
- `composer run start-local`
  Runs the startup checks and starts the Laravel server on port `8001`.

Important local setup note:
- the current beginner path does not require `npm install`
- the pages are Blade-driven, so frontend build tooling is not required for first startup

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

## Database Structure

Users should create only the empty database manually. Laravel migrations build the table structure.

Framework tables:
- `users`
- `cache`
- `jobs`

Main app tables:
- `characters`
- `homebrew_entries`
- `dm_records`

If the schema changes later, use:

```bash
php artisan migrate
```

## Quality and Testing

The project includes feature and command-oriented tests for the page shells, API flows, and the setup doctor.

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
- startup root-guard and doctor command feedback

## Notes

- Official builder/configurator data stays separate from homebrew data.
- DM records stay separate from Homebrew until they are explicitly exported.
- The player wizard and DM wizard are separate systems on purpose.
- The app warns about official-rules drift, but it does not hard-lock every flexible table variant.
