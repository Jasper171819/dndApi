# D&D Character Beheer

Dit project is een kleine Laravel schoolapp.

De app heeft:
- 1 simpele frontendpagina
- 1 eigen API
- 1 tabel voor characters

Met deze app kan een gebruiker:
- alle characters zien
- 1 character bekijken
- een character toevoegen
- een character aanpassen
- een character verwijderen

## Starten

Gebruik voor de eerste keer:

```bash
composer run setup
```

Start daarna de app met:

```bash
composer run serve
```

Open daarna:

```text
http://127.0.0.1:8001
```

## API endpoints

De app gebruikt alleen deze endpoints:

- `GET /api/characters`
- `GET /api/characters/{id}`
- `POST /api/characters`
- `PUT /api/characters/{id}`
- `DELETE /api/characters/{id}`

Meer uitleg staat in [endpoints.md](./endpoints.md).

## Database

De belangrijkste tabel is:

- `characters`

Meer uitleg staat in [database_ontwerp.md](./database_ontwerp.md).

Het SQL-creatiebestand staat in:

- [database/schema/creatiebestand.sql](./database/schema/creatiebestand.sql)

## Tests

Gebruik voor de tests:

```bash
composer run test
```

De tests controleren:
- de homepage
- de character API
- de demo seeder
- de API logging
