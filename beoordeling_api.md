# Beoordeling API

## Inleveren

Voor deze opdracht worden de volgende onderdelen ingeleverd:

- demonstratie aan de docent
- lijst van eisen en wensen
- overzicht van de endpoints
- de API zonder `vendor`
- het datamodel en het creatiebestand van de database
- de frontendapplicatie zonder `node_modules`

## Controle

### 1. Lijst van eisen en wensen

Ja.

Dit staat in [eisen_en_wensen.md](./eisen_en_wensen.md).

### 2. Endpoints

Ja.

Dit staat in [endpoints.md](./endpoints.md) en in [routes/api.php](./routes/api.php).

### 3. Database

Ja.

De database-opzet staat in [database_ontwerp.md](./database_ontwerp.md).

De tabel wordt gemaakt met de migratie in:

- [2026_03_30_101216_create_characters_table.php](./database/migrations/2026_03_30_101216_create_characters_table.php)

Het SQL-creatiebestand staat in:

- [creatiebestand.sql](./database/schema/creatiebestand.sql)

### 4. Werkende endpoints

Ja.

De API heeft werkende CRUD-endpoints voor characters.

### 5. Seeder

Ja.

De demo seeder maakt 5 voorbeeldcharacters aan.

Deze staat in:

- [DemoContentSeeder.php](./database/seeders/DemoContentSeeder.php)

### 6. Logging

Ja.

De API logt:

- `info` bij succesvolle create, update en delete
- `warning` bij `404` en `422`
- `error` bij onverwachte fouten

### 7. Frontend

Ja.

De homepage gebruikt de eigen API via `fetch(...)`.

## Eindconclusie

Deze versie van `dnd-api` past bij een kleine schoolopdracht:

- 1 pagina
- 1 tabel
- 1 resource
- 5 endpoints
