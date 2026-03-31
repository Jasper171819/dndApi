# Beoordeling API-vaardigheden

## Kort antwoord

Ja, deze criteria zijn in dit project duidelijk aanwezig.

De applicatie gebruikt een eigen Laravel API met echte endpoints, een frontend die die API aanroept, request-validatie voordat data wordt opgeslagen, en feature tests die de belangrijkste API-stromen automatisch controleren.

## 1. De student is in staat API's te bouwen op basis van endpoints

Ja.

In [routes/api.php](./routes/api.php) staan echte API-endpoints voor meerdere onderdelen van de applicatie, bijvoorbeeld:

- characters
- dice
- compendium
- player wizard
- DM wizard
- homebrew
- DM records

Deze endpoints zijn gekoppeld aan controllerlogica zoals:

- `CharacterController`
- `DiceController`
- `CompendiumController`
- `RulesWizardController`
- `DmWizardController`
- `HomebrewController`
- `DmRecordController`

Daardoor is dit niet alleen een databaseproject, maar een project met een echte API-laag tussen frontend en database.

## 2. De student is in staat om endpoints voor een API op te stellen

Ja.

De API bevat meerdere soorten endpoints:

- lees-endpoints zoals `GET /api/characters`
- aanmaak-endpoints zoals `POST /api/characters`
- update-endpoints zoals `PUT /api/characters/{character}`
- verwijder-endpoints zoals `DELETE /api/characters/{id}`
- utility-endpoints zoals `POST /api/roll-dice`
- domeinspecifieke endpoints zoals `POST /api/rules-wizard/message`
- aparte DM-endpoints zoals `POST /api/dm-wizard/message` en `GET /api/dm-records`

Dat laat zien dat de student niet alleen losse routes maakt, maar ook verschillende soorten API-verkeer kan opzetten.

## 3. De student is in staat API's te gebruiken

Ja.

De browserpagina's gebruiken de eigen API via `fetch(...)`. Dat gebeurt onder andere in:

- [welcome.blade.php](./resources/views/welcome.blade.php)
- [roster.blade.php](./resources/views/roster.blade.php)
- [homebrew.blade.php](./resources/views/homebrew.blade.php)
- [dm.blade.php](./resources/views/dm.blade.php)

De frontend doet dus geen directe SQL-query's. In plaats daarvan:

1. de gebruiker klikt of vult iets in op een pagina
2. de pagina stuurt een HTTP-request
3. Laravel verwerkt dat request via een API-endpoint
4. de controller leest of schrijft data
5. de API geeft JSON terug aan de browser

## 3a. Hoe, waar en waarom leest en schrijft deze API?

### Waar zit de API?

De API zit in [routes/api.php](./routes/api.php).

Daar wordt bepaald welke URL en welke HTTP-methode naar welke controlleractie gaat.

Voorbeelden:

- `GET /api/characters` -> `CharacterController@index`
- `POST /api/characters` -> `CharacterController@store`
- `PUT /api/characters/{character}` -> `CharacterController@update`
- `DELETE /api/characters/{id}` -> `CharacterController@destroy`
- `POST /api/rules-wizard/message` -> `RulesWizardController@message`
- `POST /api/dm-wizard/message` -> `DmWizardController@message`
- `GET /api/dm-records` -> `DmRecordController@index`

### Hoe werkt lezen en schrijven?

De werking is:

1. de frontend verstuurt een request met `GET`, `POST`, `PUT` of `DELETE`
2. Laravel vangt dit op in `routes/api.php`
3. de route stuurt het request naar een controller
4. die controller gebruikt request classes, modellen en support classes om data te valideren, lezen of opslaan
5. de controller geeft daarna een JSON-response terug

### Wat betekenen GET, POST, PUT en DELETE hier?

- `GET` = data ophalen  
  Voorbeeld: `GET /api/characters`
- `POST` = nieuwe data aanmaken  
  Voorbeeld: `POST /api/characters`
- `PUT` = bestaande data aanpassen  
  Voorbeeld: `PUT /api/homebrew/{homebrewEntry}`
- `DELETE` = bestaande data verwijderen  
  Voorbeeld: `DELETE /api/dm-records/{dmRecord}`

### Waarom is dit een API en niet alleen databasegebruik?

Omdat de pagina's niet direct met MySQL praten.

De database is alleen de opslaglaag. De API is de tussenlaag die bepaalt:

- welke requests zijn toegestaan
- welke validatie wordt uitgevoerd
- welke controller reageert
- welke JSON terugkomt

De scheiding in dit project is dus:

- frontend = vraagt data op of stuurt data in
- API = verwerkt de requestlogica
- database = bewaart de gegevens

## 3b. Hoe gaan ingevulde gegevens precies door de code heen naar de database?

Dit moet in dit project duidelijk te volgen zijn, en dat is ook zo.

Hieronder staat het concrete pad van ingevulde charactergegevens.

### Stap 1: de gebruiker vult het formulier in

Op de hoofdpagina in [welcome.blade.php](./resources/views/welcome.blade.php) vult de gebruiker gegevens in zoals:

- naam
- species
- class
- subclass
- background
- talen
- ability scores

Daarna verstuurt de frontend deze gegevens met `fetch(...)` naar:

- `POST /api/characters` voor een nieuw character
- `PUT /api/characters/{character}` voor een bestaand character

### Stap 2: Laravel koppelt de URL aan de juiste code

In [routes/api.php](./routes/api.php) staat:

- `POST /api/characters` -> `CharacterController@store`
- `PUT /api/characters/{character}` -> `CharacterController@update`

Dat betekent dat Laravel weet welke controller de ingevulde data moet afhandelen.

### Stap 3: de request-validatie wordt uitgevoerd

Voordat de controller de data opslaat, gaat het request eerst door [StoreCharacterRequest.php](./app/Http/Requests/StoreCharacterRequest.php).

Die request class doet twee belangrijke dingen:

1. `prepareForValidation()`  
   Hier wordt de invoer eerst opgeschoond en genormaliseerd.

2. `rules()`  
   Hier worden de validatieregels opgehaald.

Die logica komt uit [CharacterDataValidator.php](./app/Support/CharacterDataValidator.php).

Daar gebeurt onder andere:

- opschonen van vrije tekst
- omzetten van lijsten zoals talen en skills
- controleren of verplichte velden aanwezig zijn
- controleren of class, subclass, background en andere keuzes geldig zijn
- controleren of expertise alleen op gekozen skill proficiencies zit

Dus: de ruwe formulierdata gaat niet direct de database in, maar eerst door een laag die de data schoonmaakt en controleert.

### Stap 4: de controller ontvangt de gevalideerde data

Na die validatie komt de data terecht in [CharacterController.php](./app/Http/Controllers/Api/CharacterController.php).

Bij het opslaan van een nieuw character gebeurt daar dit:

1. `$request->characterData()` haalt alleen de gevalideerde data op
2. `CharacterHitPointRoller` berekent extra afgeleide gegevens, zoals HP-metadata
3. `Character::create([...$characterData, ...$metadata])` maakt het database-record aan

Bij een update gebeurt bijna hetzelfde:

1. de bestaande `Character` wordt via route-model-binding opgehaald
2. de nieuwe invoer wordt opnieuw gevalideerd
3. `$character->update([...$characterData, ...$metadata])` werkt het record in de database bij

### Stap 5: Eloquent schrijft naar MySQL

Het model [Character.php](./app/Models/Character.php) is een Eloquent-model.

Dat model is gekoppeld aan de tabel `characters` in MySQL.

Daardoor betekent:

- `Character::create(...)` = een nieuwe rij schrijven in de tabel `characters`
- `$character->update(...)` = een bestaande rij aanpassen
- `$character->delete()` = een bestaande rij verwijderen

Met andere woorden: op het moment dat de controller `Character::create(...)` of `$character->update(...)` uitvoert, wordt de data via Laravel Eloquent echt in de database gezet.

### Stap 6: de API stuurt een JSON-response terug

Na opslag stuurt de controller een JSON-response terug, bijvoorbeeld:

- een succesmelding
- de opgeslagen data
- validatiefouten wanneer invoer niet klopt

De frontend krijgt die response terug en kan daarna:

- het formulier leegmaken
- de roster verversen
- de nieuwe data tonen op de pagina
- waarschuwingen laten zien als een build buiten de officiële 2024-baseline valt

## 3c. Samengevat als datastroom

De volledige stroom van ingevulde gegevens is dus:

1. gebruiker vult formulier in op de pagina
2. frontend stuurt data met `fetch(...)`
3. `routes/api.php` kiest de juiste endpoint
4. `StoreCharacterRequest` ontvangt de invoer
5. `CharacterDataValidator` normaliseert en valideert de data
6. `CharacterController` ontvangt alleen geldige data
7. `Character` model schrijft via Eloquent naar MySQL
8. de controller geeft JSON terug aan de frontend

## 3d. Waarom dit belangrijk is

Dit laat zien dat de student niet alleen endpoints heeft gemaakt, maar ook begrijpt dat data in een API-project door meerdere lagen gaat:

- frontend
- route
- request-validatie
- support en normalisatie
- controller
- model
- database

Dat is precies het verschil tussen:

- alleen "een formulier maken"
- en echt begrijpen hoe gegevens gecontroleerd en opgeslagen worden in een API-gestuurd systeem

## 3e. Hetzelfde patroon bestaat ook bij homebrew en DM-records

Dit is niet alleen zo bij characters. Dezelfde opbouw zie je ook terug in andere delen van het project.

### Homebrew

Bij homebrew gaat de data bijvoorbeeld zo:

1. de gebruiker vult het homebrewformulier in op de pagina
2. de frontend stuurt die data naar `/api/homebrew`
3. [routes/api.php](./routes/api.php) stuurt dat request naar `HomebrewController@store`
4. [StoreHomebrewEntryRequest.php](./app/Http/Requests/StoreHomebrewEntryRequest.php) normaliseert en valideert de invoer
5. [HomebrewController.php](./app/Http/Controllers/Api/HomebrewController.php) haalt met `$request->entryData()` alleen de geldige data op
6. `HomebrewEntry::create(...)` schrijft de gegevens via Eloquent naar MySQL
7. de API geeft daarna JSON terug aan de frontend

### DM-records

Bij DM-records gebeurt hetzelfde principe:

1. de DM vult gegevens in via de DM-pagina of de DM wizard
2. de frontend stuurt die data naar `/api/dm-records`
3. [routes/api.php](./routes/api.php) koppelt dat endpoint aan `DmRecordController@store`
4. [StoreDmRecordRequest.php](./app/Http/Requests/StoreDmRecordRequest.php) normaliseert en valideert de invoer
5. de validatieregels komen uit `DmRecordDataValidator`
6. [DmRecordController.php](./app/Http/Controllers/Api/DmRecordController.php) gebruikt daarna `$request->recordData()`
7. `DmRecord::create(...)` slaat de gegevens op in MySQL
8. de API geeft een JSON-response terug

## 3f. De wizard gebruikt hetzelfde API-principe

Ook de player wizard en DM wizard gebruiken dezelfde API-opbouw.

Bij de player wizard:

1. de gebruiker typt een bericht op de pagina
2. de frontend stuurt dat naar `/api/rules-wizard/message`
3. [routes/api.php](./routes/api.php) koppelt dit aan `RulesWizardController@message`
4. [RulesWizardMessageRequest.php](./app/Http/Requests/RulesWizardMessageRequest.php) controleert de invoer
5. `RulesWizardStateSanitizer` en `RulesWizardService` verwerken bericht en staat
6. de API geeft de volgende wizardreactie en een bijgewerkte snapshot terug als JSON

Bij de DM wizard gebeurt hetzelfde patroon via `/api/dm-wizard/message`.

## 3g. Extra punt: officiële regels worden niet blind afgedwongen, maar wel bewaakt

De applicatie is niet op elke plek hard geblokkeerd op de officiële 2024-regels, maar geeft nu wel duidelijke waarschuwingen wanneer een build van de officiële baseline afwijkt.

Dat gebeurt via:

- [OfficialRulesWarningService.php](./app/Support/OfficialRulesWarningService.php)
- configuratie in [config/dnd.php](./config/dnd.php)
- weergave in de builder en wizard-snapshot

Dit laat zien dat de API niet alleen data kan opslaan, maar ook extra logica kan toepassen om gebruikers te informeren over de status van hun invoer.

## 4. De student is in staat API's automatisch te testen met feature tests

Ja.

In [tests/Feature/AppSmokeTest.php](./tests/Feature/AppSmokeTest.php) staan feature tests met onder andere:

- `get`
- `getJson`
- `postJson`
- `putJson`
- `deleteJson`

Deze tests controleren onder andere:

- of de hoofdpagina's laden
- of de API-root overzichtsinformatie teruggeeft
- of de configurator en compendium API werken
- of characters aangemaakt en bijgewerkt kunnen worden
- of homebrew werkt
- of de dice API werkt
- of de player wizard API werkt
- of de DM wizard API werkt
- of DM records correct kunnen worden opgeslagen, aangepast, verwijderd en geëxporteerd
- of niet-blokkerende officiële-waarschuwingen in de wizard-snapshot verschijnen

Op dit moment draaien daar **36 feature tests** voor.

## Eindconclusie

- API bouwen met endpoints: ja
- endpoints opstellen: ja
- API gebruiken vanuit de frontend: ja
- API automatisch testen met feature tests: ja

Een belangrijke nuance is dat dit project vooral de eigen API gebruikt, niet een externe publieke API. Maar voor de gevraagde vaardigheden rond het opzetten, gebruiken en testen van API's is dit project wel een duidelijk en passend voorbeeld.
