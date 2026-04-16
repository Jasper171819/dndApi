# dnd-api

## Technische samenvatting

- Framework: Laravel 13
- PHP: 8.3 of hoger
- Frontend: Blade
- Database: relationele database met tabel `characters`
- Hoofdroutes:
  - web: `/`
  - web: `/api-overzicht`
  - api: `/api/characters`
  - api: `/api/characters/{id}`

## Vereisten

- PHP 8.3 of hoger
- Composer
- een werkende databaseverbinding via `.env`

## Installatie

1. Kopieer `.env.example` naar `.env`.
2. Vul de database-instellingen in `.env` in.
3. Installeer de PHP-dependencies:

```bash
composer install
```

4. Genereer een applicatiesleutel:

```bash
php artisan key:generate
```

5. Maak de database-tabellen aan en vul de demo-data:

```bash
php artisan migrate --seed
```

## Applicatie starten

Start de Laravel development server:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Open daarna:

```text
http://127.0.0.1:8001
```

## Beschikbare routes

### Web

- `GET /`
- `GET /api-overzicht`

### API

- `GET /api/characters`
- `GET /api/characters/{id}`
- `POST /api/characters`
- `PUT /api/characters/{id}`
- `DELETE /api/characters/{id}`

## Belangrijke bestanden

- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/Api/CharacterController.php`
- `app/Http/Requests/StoreCharacterRequest.php`
- `app/Models/Character.php`
- `database/migrations/2026_03_30_101216_create_characters_table.php`
- `database/seeders/DemoContentSeeder.php`
- `resources/views/welcome.blade.php`
- `resources/views/api-overzicht.blade.php`

## Database

- hoofdtafel: `characters`
- SQL-creatiebestand: `database/schema/creatiebestand.sql`
- extra database-uitleg: `database_ontwerp.md`

## Testen

Voer de test-suite uit met:

```bash
php artisan test
```

## Documentatie

- functionele leeswijzer: `leeswijzer.md`
- eisen en wensen: `eisen_en_wensen.md`
- API-routes: `endpoints.md`
- database-ontwerp: `database_ontwerp.md`
- beoordeling: `beoordeling_api.md`
- eerste akkoorddocumenten: `initial/`
