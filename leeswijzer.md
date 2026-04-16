# Leeswijzer

Deze leeswijzer hoort bij de kleine versie van **dnd-api**.

Dit project is een eenvoudige Laravel-app voor het bekijken, toevoegen, aanpassen en verwijderen van karakters via een eigen API.

## Belangrijkste bestanden

- `README.md`
  Technische handleiding voor installatie, starten, routes en tests.

- `eisen_en_wensen.md`
  De lijst met eisen en wensen van de applicatie.

- `endpoints.md`
  Overzicht van de API-routes en de gebruikte velden.

- `database_ontwerp.md`
  Uitleg van de tabel `characters` en de gebruikte velden.

- `database/schema/creatiebestand.sql`
  Het SQL-creatiebestand van de database.

- `beoordeling_api.md`
  Controlelijst van de inleverpunten en waar die in het project terug te vinden zijn.

## Belangrijkste codebestanden

- `resources/views/welcome.blade.php`
  De frontendpagina van de applicatie.

- `routes/api.php`
  De API-routes voor de CRUD-bewerkingen.

- `app/Http/Controllers/Api/CharacterController.php`
  De controller die de API-aanvragen verwerkt.

- `database/migrations/2026_03_30_101216_create_characters_table.php`
  De migratie die de tabel `characters` aanmaakt.

- `database/seeders/DemoContentSeeder.php`
  Seeder met voorbeelddata om de applicatie te testen.

## Extra map

- `initial/`
  Bevat de eerste documenten voor akkoord:
  - lijst van eisen en wensen
  - ontwerp van de API-routes
  - ontwerp van de database

## Gebruik

De hoofdpagina van de app staat op `/`.

Er is ook een extra hulppagina op `/api-overzicht`. Daar staat een overzicht van de web- en API-routes van de applicatie.

## Inlevering

De inlever-zip is een kleine, schone versie van het project.

Daarin zitten geen `vendor`, `node_modules` en geen `.env`-bestand.
