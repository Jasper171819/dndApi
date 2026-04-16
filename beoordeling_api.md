# Beoordeling API als leerblad

## Kernzin

De gebruiker klikt op opslaan, JavaScript maakt van de invoer JSON, Laravel stuurt die invoer via route, request, normalizer en validatie naar de controller, het model slaat alles op in de database, en daarna komt JSON terug zodat het scherm wordt bijgewerkt.

## Wat deze app in het kort is

Deze kleine versie van `dnd-api` is een eenvoudige Laravel-app voor characterbeheer.

De app heeft:

- 1 hoofdpagina op `/`
- 1 hulppagina op `/api-overzicht`
- 1 resource: `characters`
- 5 API-routes voor CRUD

De browser praat dus niet direct met de database.

De hoofdflow is:

frontend -> route -> request -> normalizer -> validatie -> controller -> model -> database -> JSON-response -> scherm

## De 14 stappen die ik uit mijn hoofd moet kennen

Dit is de vaste volgorde van deze app. Bijna alle beoordelingscriteria kun je uitleggen vanuit deze 14 stappen.

1. klik op opslaan
2. JavaScript vangt submit op
3. `formPayload()`
4. `fetch(...)`
5. route
6. request
7. normalizer
8. validatie
9. controller
10. model
11. database
12. JSON terug
13. lijst opnieuw laden
14. scherm bijgewerkt

## De hoofdflow stap voor stap

Hieronder staat dezelfde flow nog een keer, maar nu met uitleg erbij.

### Stap 1: klik op opslaan

De gebruiker vult het formulier in op [welcome.blade.php](./resources/views/welcome.blade.php) en klikt op `Opslaan`.

Waarom belangrijk:

- dit is het startpunt van de hele flow
- zonder deze stap gebeurt er nog niets in de API

### Stap 2: JavaScript vangt submit op

In [welcome.blade.php](./resources/views/welcome.blade.php) staat:

- `form.addEventListener('submit', saveCharacter);`

Wat gebeurt er:

- JavaScript vangt de submit op
- de browser doet niet een gewone oude HTML-submit
- JavaScript neemt de controle over

Waarom belangrijk:

- zo kan de frontend via de API werken

### Stap 3: `formPayload()`

In dezelfde pagina zet `formPayload(createFields)` de invoer om naar een JavaScript-object.

Wat gebeurt er:

- velden zoals `name`, `species`, `class` en `notes` worden verzameld
- `level` wordt omgezet naar een getal
- alle waarden worden klaar gezet als JSON-body

Waarom belangrijk:

- de API verwacht nette, gestructureerde data

### Stap 4: `fetch(...)`

Daarna verstuurt `saveCharacter()` een request met:

- `fetch('/api/characters', { method: 'POST' })`

Wat gebeurt er:

- de frontend stuurt JSON naar Laravel
- de browser gaat dus eerst naar de API en niet direct naar de database

Waarom belangrijk:

- hier begint het echte API-gedeelte

### Stap 5: route

In [routes/api.php](./routes/api.php) staat de koppeling tussen URL en controller.

Voorbeeld:

- `Route::post('/characters', [CharacterController::class, 'store']);`

Wat gebeurt er:

- Laravel ziet de URL en de methode
- daarna kiest Laravel de juiste controlleractie

Waarom belangrijk:

- zonder route weet Laravel niet welke code moet draaien

### Stap 6: request

Voordat de controller werkt met de data, komt de invoer eerst binnen in [StoreCharacterRequest.php](./app/Http/Requests/StoreCharacterRequest.php).

Wat gebeurt er:

- Laravel gebruikt een aparte request-klasse
- die request-klasse bereidt de validatie voor

Waarom belangrijk:

- de controller hoeft dan niet zelf alle invoercontrole te doen

### Stap 7: normalizer

In `prepareForValidation()` gebruikt de request [PlainTextNormalizer.php](./app/Support/PlainTextNormalizer.php).

Wat gebeurt er:

- HTML en scripts worden verwijderd
- overbodige spaties worden weggehaald
- tekst wordt opgeschoond voordat die wordt gevalideerd

Waarom belangrijk:

- eerst schoonmaken, daarna pas controleren

### Stap 8: validatie

Daarna gebruikt `rules()` de validatieregels in `StoreCharacterRequest`.

Wat gebeurt er:

- verplichte velden worden gecontroleerd
- maximale lengtes worden gecontroleerd
- `level` moet een integer zijn tussen 1 en 20

Waarom belangrijk:

- alleen geldige data mag door naar de controller

### Stap 9: controller

In [CharacterController.php](./app/Http/Controllers/Api/CharacterController.php) komt het request binnen in `store()`.

Wat gebeurt er:

- de controller ontvangt alleen gevalideerde data
- de controller bepaalt wat er met die data moet gebeuren

Waarom belangrijk:

- dit is de centrale logica van het endpoint

### Stap 10: model

In dezelfde controller staat:

- `Character::query()->create($request->characterData())`

Wat gebeurt er:

- Laravel gebruikt het model [Character.php](./app/Models/Character.php)
- het model is de laag tussen controller en database

Waarom belangrijk:

- de controller praat niet direct met SQL, maar via het model

### Stap 11: database

Het model schrijft naar de tabel `characters`.

Die tabel staat uitgelegd in [database_ontwerp.md](./database_ontwerp.md) en wordt gemaakt in de migratie [2026_03_30_101216_create_characters_table.php](./database/migrations/2026_03_30_101216_create_characters_table.php).

Wat gebeurt er:

- de gegevens worden echt opgeslagen in de database
- daardoor blijven ze bestaan na een refresh

Waarom belangrijk:

- zonder database wordt niets blijvend bewaard

### Stap 12: JSON terug

Na het opslaan stuurt de controller JSON terug.

Voorbeeld:

- `message`
- `data`

Wat gebeurt er:

- de API laat weten dat het opslaan gelukt is
- ook het opgeslagen karakter wordt teruggestuurd

Waarom belangrijk:

- de frontend moet weten wat het resultaat van het request was

### Stap 13: lijst opnieuw laden

Na een succesvolle response roept de frontend `loadCharacters()` aan.

Wat gebeurt er:

- de browser doet daarna een nieuwe `GET /api/characters`
- de lijst met karakters wordt opnieuw opgehaald

Waarom belangrijk:

- zo zie je meteen de nieuwste data

### Stap 14: scherm bijgewerkt

Daarna zet `renderCharacters()` de JSON om naar HTML-kaarten op het scherm.

Wat gebeurt er:

- de gebruiker ziet het nieuwe of gewijzigde karakter meteen terug

Waarom belangrijk:

- hiermee eindigt de hele flow zichtbaar in de browser

## O/V/G-Beoordeling API-1 als leeruitleg

`O` betekent onvoldoende.  
`V` betekent voldoende.  
`G` betekent goed.

Bij deze beoordeling kijk je niet alleen of iets bestaat, maar ook of het compleet, correct en goed bruikbaar is.

## Snelle overzichtstabel

| criterium | stap(pen) in de flow | waar laat ik dit zien | score |
| --- | --- | --- | --- |
| Eisen en wensen | voorbereiding voor stap 1 | [eisen_en_wensen.md](./eisen_en_wensen.md) | `G` |
| Endpoints | 4 en 5 | [endpoints.md](./endpoints.md), [routes/api.php](./routes/api.php) | `V` |
| Database | 10 en 11 | [database_ontwerp.md](./database_ontwerp.md), [migratie](./database/migrations/2026_03_30_101216_create_characters_table.php) | `G` |
| API | 5 t/m 12 | [CharacterController.php](./app/Http/Controllers/Api/CharacterController.php), [StoreCharacterRequest.php](./app/Http/Requests/StoreCharacterRequest.php) | `G` |
| Data seeder | ondersteunt 11 en testen | [DemoContentSeeder.php](./database/seeders/DemoContentSeeder.php) | `V` |
| Logging | vooral 4 t/m 12 | [ApiRequestAuditMiddleware.php](./app/Http/Middleware/ApiRequestAuditMiddleware.php), [ApiAuditLogger.php](./app/Support/ApiAuditLogger.php) | `G` |
| Testen | controle van meerdere stappen | [tests/Feature](./tests/Feature) | `G` |
| Frontend | 1 t/m 4 en 13 t/m 14 | [welcome.blade.php](./resources/views/welcome.blade.php) | `G` |

## Uitleg per criterium

### 1. Eisen en wensen

`Score van dit project: G`

`Bij welke stap(pen) van de flow hoort dit`

Dit hoort bij de voorbereiding voor stap 1. Eerst moet duidelijk zijn wat de app moet kunnen, pas daarna kun je de flow bouwen.

`Waar laat ik dit zien`

In [eisen_en_wensen.md](./eisen_en_wensen.md).

`Wat betekent O, V en G hier`

- `O`: er ontbreken kernfunctionaliteiten
- `V`: de kernfunctionaliteiten staan genoemd
- `G`: de lijst is volledig en er is duidelijk onderscheid tussen eisen en wensen

`Wat ik hierover kan vertellen`

In `eisen_en_wensen.md` staat wat deze app moet kunnen. Daarin staan alle belangrijke onderdelen, zoals bekijken, toevoegen, aanpassen en verwijderen. Ook is er netjes onderscheid gemaakt tussen eisen en wensen, en daarom is dit een `G`.

`Onthoudzin`

Eerst leg ik uit wat de app moet kunnen, en dat staat volledig en netjes gescheiden in `eisen_en_wensen.md`.

### 2. Endpoints

`Score van dit project: V`

`Bij welke stap(pen) van de flow hoort dit`

Dit hoort vooral bij stap 4 en 5:

- stap 4: `fetch(...)`
- stap 5: route

`Waar laat ik dit zien`

In [endpoints.md](./endpoints.md) en [routes/api.php](./routes/api.php).

`Wat betekent O, V en G hier`

- `O`: er zijn te weinig correcte endpoints voor de kernfunctionaliteiten
- `V`: de CRUD-endpoints zijn correct beschreven
- `G`: er zijn naast CRUD ook extra bruikbare endpoints toegevoegd

`Wat ik hierover kan vertellen`

In `endpoints.md` staat welke API-routes bestaan, en in `routes/api.php` zie je dat die routes ook echt in Laravel zijn gebouwd. De app heeft alle CRUD-endpoints die nodig zijn: `GET`, `POST`, `PUT` en `DELETE`. Dat is voldoende voor een `V`, maar geen `G`, omdat er geen extra endpoints naast CRUD zijn gemaakt.

`Onthoudzin`

De CRUD-endpoints zijn compleet en correct, dus dit is `V`; voor `G` zouden er extra endpoints moeten zijn.

### 3. Database

`Score van dit project: G`

`Bij welke stap(pen) van de flow hoort dit`

Dit hoort bij stap 10 en 11:

- stap 10: model
- stap 11: database

`Waar laat ik dit zien`

In [database_ontwerp.md](./database_ontwerp.md) en in de migratie [2026_03_30_101216_create_characters_table.php](./database/migrations/2026_03_30_101216_create_characters_table.php).

`Wat betekent O, V en G hier`

- `O`: de database past niet goed bij de endpoints
- `V`: de database bevat de structuur om de endpoints te laten werken
- `G`: de database bevat de juiste structuur en de juiste gegevensvelden om de endpoints goed te realiseren

`Wat ik hierover kan vertellen`

De API werkt met de tabel `characters`. In de migratie zie je dat de velden zoals `name`, `species`, `class`, `background`, `level` en `notes` echt bestaan. Daardoor sluit de database goed aan op wat de endpoints opslaan en teruggeven, en daarom is dit een `G`.

`Onthoudzin`

Het model schrijft naar een tabel die precies de juiste velden heeft voor deze API, daarom scoort de database een `G`.

### 4. API

`Score van dit project: G`

`Bij welke stap(pen) van de flow hoort dit`

Dit hoort bij stap 5 tot en met 12:

- route
- request
- normalizer
- validatie
- controller
- model
- database
- JSON terug

`Waar laat ik dit zien`

In [CharacterController.php](./app/Http/Controllers/Api/CharacterController.php), [StoreCharacterRequest.php](./app/Http/Requests/StoreCharacterRequest.php) en [PlainTextNormalizer.php](./app/Support/PlainTextNormalizer.php).

`Wat betekent O, V en G hier`

- `O`: te weinig endpoints werken goed
- `V`: enkele responses zijn onvolledig of bevatten fouten
- `G`: de endpoints functioneren correct

`Wat ik hierover kan vertellen`

De route stuurt het request naar de controller, de request-klasse controleert de data, de normalizer maakt de tekst schoon, en daarna verwerkt de controller alles netjes. In `CharacterController.php` zie je dat de API lijst, detail, opslaan, bijwerken en verwijderen ondersteunt en correcte JSON-responses terugstuurt. Daarom krijgt de API een `G`.

`Onthoudzin`

De hele keten van route tot JSON-response werkt correct, dus de API scoort `G`.

### 5. Data seeder

`Score van dit project: V`

`Bij welke stap(pen) van de flow hoort dit`

De seeder hoort niet direct bij 1 runtime-stap, maar ondersteunt vooral stap 11 en het testen van de app.

`Waar laat ik dit zien`

In [DemoContentSeeder.php](./database/seeders/DemoContentSeeder.php).

`Wat betekent O, V en G hier`

- `O`: er is geen goede data seeder
- `V`: de data seeder bevat correcte gegevens
- `G`: de data seeder bevat een ruime hoeveelheid correcte gegevens om de API goed te testen

`Wat ik hierover kan vertellen`

De seeder vult de database met voorbeeldkarakters zoals `Rin`, `Liora`, `Mira`, `Thorn` en `Kael`. Daarmee kun je de app en de API goed uitproberen. Dat is genoeg voor een `V`, maar nog geen `G`, omdat 5 records wel bruikbaar zijn maar nog niet echt een ruime hoeveelheid vormen.

`Onthoudzin`

De seeder is correct en bruikbaar, maar nog niet uitgebreid genoeg voor een `G`, dus dit is `V`.

### 6. Logging

`Score van dit project: G`

`Bij welke stap(pen) van de flow hoort dit`

Dit hoort vooral bij stap 4 tot en met 12, dus tijdens het hele API-request en de response terug.

`Waar laat ik dit zien`

In [ApiRequestAuditMiddleware.php](./app/Http/Middleware/ApiRequestAuditMiddleware.php), [ApiAuditLogger.php](./app/Support/ApiAuditLogger.php) en [ApiAuditLoggingTest.php](./tests/Feature/ApiAuditLoggingTest.php).

`Wat betekent O, V en G hier`

- `O`: logging ontbreekt of is fout
- `V`: de API logt belangrijke gebeurtenissen
- `G`: de API logt op meerdere niveaus en met voldoende inhoud

`Wat ik hierover kan vertellen`

De middleware hangt om de API-requests heen en de logger schrijft informatie weg over successen, waarschuwingen en fouten. In de logging zie je niet alleen dat er iets gebeurde, maar ook extra context zoals `request_id`, statuscode, route, fouttype en duur. Daarom is logging hier een `G`.

`Onthoudzin`

De API logt niet alleen iets, maar logt ook op meerdere niveaus en met duidelijke context, dus dit is `G`.

### 7. Testen

`Score van dit project: G`

`Bij welke stap(pen) van de flow hoort dit`

Dit hoort bij de controle van meerdere stappen uit de hele flow.

`Waar laat ik dit zien`

In [tests/Feature/AppSmokeTest.php](./tests/Feature/AppSmokeTest.php), [tests/Feature/ApiAuditLoggingTest.php](./tests/Feature/ApiAuditLoggingTest.php) en [tests/Feature/DemoSeederTest.php](./tests/Feature/DemoSeederTest.php).

`Wat betekent O, V en G hier`

- `O`: er zijn te weinig automatische testen of ze zijn fout
- `V`: de API bevat voldoende automatische testen
- `G`: de API bevat voldoende correcte en zinvolle automatische testen

`Wat ik hierover kan vertellen`

De tests controleren niet alleen of iets opent, maar ook of CRUD werkt, validatie werkt, 404-meldingen kloppen, logging wordt geschreven en de seeder data aanmaakt. Daarmee laten de testen zinvol zien dat de belangrijkste onderdelen van dit project werken. Daarom is dit een `G`.

`Onthoudzin`

De testen bewijzen de belangrijkste flows en foutgevallen, dus dit onderdeel scoort `G`.

### 8. Frontend

`Score van dit project: G`

`Bij welke stap(pen) van de flow hoort dit`

Dit hoort bij stap 1 tot en met 4 en stap 13 tot en met 14:

- klik op opslaan
- JavaScript vangt submit op
- `formPayload()`
- `fetch(...)`
- lijst opnieuw laden
- scherm bijgewerkt

`Waar laat ik dit zien`

In [welcome.blade.php](./resources/views/welcome.blade.php).

`Wat betekent O, V en G hier`

- `O`: de frontend gebruikt de API weinig of foutief
- `V`: de frontend maakt gebruik van de API
- `G`: de frontend maakt volop gebruik van de API

`Wat ik hierover kan vertellen`

De frontend doet niet alleen 1 request, maar gebruikt de API voor ophalen, opslaan, bijwerken en verwijderen. Ook wordt na een actie de lijst opnieuw geladen en het scherm direct aangepast. Daardoor maakt de frontend volop gebruik van de API, en daarom is dit een `G`.

`Onthoudzin`

De frontend start de flow, praat steeds met de API en werkt daarna het scherm opnieuw bij, dus dit is `G`.

## Zo vertel ik het in de juiste volgorde

Als ik dit mondeling moet uitleggen, doe ik het in deze volgorde:

1. Eerst zeg ik de 14 stappen op uit mijn hoofd.
2. Daarna leg ik uit dat bijna alle beoordelingscriteria ergens in die flow zitten.
3. Daarna pak ik per criterium de juiste stapnummers erbij.
4. Daarna noem ik het bestand waarin de docent het kan zien.
5. Als laatste leg ik kort uit waarom het daar `O`, `V` of `G` is.

## Korte spreekversie om te oefenen

Eerst klik je op opslaan. Daarna vangt JavaScript de submit op, maakt `formPayload()` van de invoer een object en stuurt `fetch(...)` de data naar de API. In Laravel pakt de route het request op, de request-klasse en normalizer maken de data schoon en valideren die, de controller verwerkt alles, het model schrijft naar de database en daarna komt JSON terug. Ten slotte laadt de frontend de lijst opnieuw en wordt het scherm bijgewerkt.

Vanuit die vaste flow kan ik ook de beoordeling uitleggen. Eisen en wensen staan in `eisen_en_wensen.md`, endpoints in `endpoints.md` en `routes/api.php`, de database in `database_ontwerp.md` en de migratie, de API-logica in `CharacterController.php`, de seeder in `DemoContentSeeder.php`, logging in `ApiAuditLogger.php`, testen in `tests/Feature` en de frontend in `welcome.blade.php`.

## Ezelsbrug

klik -> JavaScript -> formPayload -> fetch -> route -> request -> normalizer -> validatie -> controller -> model -> database -> JSON -> opnieuw laden -> scherm
