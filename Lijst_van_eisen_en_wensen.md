# Lijst van eisen en wensen

## Inleiding

Dit document is achteraf afgeleid uit de huidige code, pagina's, API-routes en tests van het project **Adventurer's Ledger**.

Het is dus geen letterlijke originele opdrachtomschrijving, maar een logische lijst van:

- wat het project nu aantoonbaar moet kunnen
- welke technische keuzes daaruit blijken
- welke onderdelen als wens gezien kunnen worden

## Hoofddoel van het project

Het project moet een D&D-hulpmiddel zijn waarmee spelers en een Dungeon Master op een overzichtelijke manier characters, regels, dobbelstenen, homebrew en sessiegegevens kunnen beheren via een website met een eigen Laravel API.

## Eisen

### Functionele eisen

1. Het systeem moet een **character builder** aanbieden op de hoofdpagina.
2. De gebruiker moet een character kunnen **aanmaken, bekijken, aanpassen en verwijderen**.
3. Het systeem moet een **roster-pagina** hebben waarop opgeslagen characters zichtbaar zijn.
4. De roster-pagina moet characters kunnen **zoeken en bewerken via een popupvenster**.
5. Het systeem moet een **lokale regelsbibliotheek / compendium** aanbieden.
6. Het systeem moet een **player wizard** bevatten die helpt met characteropbouw en spelregels.
7. Het systeem moet een **aparte DM-pagina** hebben.
8. De DM-pagina moet een **eigen DM wizard** hebben die losstaat van de player wizard.
9. De DM wizard moet DM-materiaal kunnen opbouwen zoals:
   - NPC's
   - scènes
   - encounters
   - quests
   - locaties
   - loot
10. Het systeem moet **DM-records** kunnen opslaan, aanpassen, verwijderen en exporteren naar homebrew wanneer dat bewust wordt gekozen.
11. Het systeem moet een **homebrew-pagina** hebben voor aangepaste inhoud.
12. Homebrew moet **gescheiden blijven van de officiële builder-data** totdat er bewust wordt geëxporteerd of gekoppeld.
13. Het systeem moet een **dice API** hebben voor dobbelsteenrollen.
14. Dobbelsteenrollen moeten zich gedragen als **echte dobbelsteenrollen** en niet als een simpele willekeurige waarde tussen een minimum en maximum.
15. Het systeem moet zowel **player-side** als **DM-side** hulpmiddelen aanbieden zonder dat deze twee stromen onduidelijk door elkaar lopen.
16. Het systeem moet de gebruiker **waarschuwen** wanneer keuzes afwijken van de officiële 2024-baseline, zonder die keuzes direct te blokkeren.

### API-eisen

17. De applicatie moet API-endpoints aanbieden via `routes/api.php`.
18. De API moet minimaal gebruikmaken van de HTTP-methodes:
   - `GET`
   - `POST`
   - `PUT`
   - `DELETE`
19. De API moet JSON-responses teruggeven aan de frontend.
20. De frontend moet de API gebruiken via `fetch(...)`.
21. De API moet endpoints bevatten voor:
   - configurator
   - compendium
   - characters
   - dice
   - player wizard
   - DM wizard
   - homebrew
   - DM-records
22. De API-root moet een **overzicht van de API** kunnen teruggeven met endpointgroepen en verwerkingsinformatie.

### Data- en validatie-eisen

23. Characterdata moet gevalideerd worden **vóór** opslag.
24. Vrije tekstvelden moeten opgeschoond worden zodat vervuilde of ongeldige invoer niet zomaar wordt opgeslagen.
25. Ongeldige API-invoer moet netjes worden afgehandeld, bijvoorbeeld met een `422`-response.
26. De database moet worden gebruikt als opslaglaag voor characters, homebrew en DM-records.
27. De applicatie moet lokale browseropslag gebruiken voor concepten op pagina's waar gebruikers anders werk kwijt kunnen raken.
28. De wizard-state moet opgeschoond en gevalideerd worden voordat die wordt opgeslagen of gebruikt.

### Gebruikerservaring en kwaliteit

29. De hoofdpagina's moeten een **consistente navigatie** hebben.
30. De pagina's moeten een **linker zijrail met interne paginalinks** gebruiken voor snelle navigatie binnen een pagina.
31. De interface moet responsief zijn en bruikbaar blijven op kleinere schermen.
32. Het systeem moet herstel van concepten zichtbaar melden met een duidelijke melding zoals `Draft restored`.
33. Tooltips en hulpuitleg moeten consistent worden weergegeven.
34. De player wizard en DM wizard moeten gebruikers begeleiden zonder dat alle spelkennis vooraf nodig is.
35. Popupvensters voor bewerken moeten qua gedrag en bediening vergelijkbaar werken op verschillende pagina's.

### Technische eisen

36. Het project moet gebouwd zijn met **Laravel**.
37. De frontend moet gebruikmaken van **Blade-views** en browser-side JavaScript.
38. De backend moet gebruikmaken van **controllers, requests, modellen en support classes**.
39. Er moet een duidelijke scheiding zijn tussen:
   - pagina-routes in `routes/web.php`
   - API-routes in `routes/api.php`
40. Het project moet onderhoudbaar blijven door consistente commentaarstijl en codeconventies.

### Test- en kwaliteitsborgingseisen

41. Het project moet **feature tests** bevatten voor de belangrijkste pagina's en API-stromen.
42. Tests moeten minimaal aantonen dat:
   - hoofdpagina's laden
   - de API basisresponses geeft
   - characters opgeslagen en bewerkt kunnen worden
   - homebrew werkt
   - dice werkt
   - de player wizard werkt
   - de DM wizard werkt
   - DM-records CRUD ondersteunen
   - officiële waarschuwingen zichtbaar worden in de wizard-snapshot
43. Het project moet lokaal controleerbaar zijn met commando's zoals:
   - `php artisan test`
   - `php artisan view:cache`

## Wensen die al gerealiseerd zijn

Dit zijn onderdelen die je ook als wens of extra kwaliteit zou kunnen zien, maar die in de huidige versie van het project al aanwezig zijn.

1. Een aparte DM-omgeving naast de speleromgeving.
2. Een aparte DM wizard in plaats van één gemengde wizard.
3. Lokale conceptopslag in de browser zodat werk niet direct verloren gaat bij een refresh of slechte verbinding.
4. Duidelijke meldingen wanneer een concept uit lokale opslag is hersteld.
5. Een consistente bovenste navigatie op de hoofdpagina's.
6. Een linker zijrail met paginalinks voor sneller navigeren binnen langere pagina's.
7. Popupvensters voor het aanpassen van roster- en homebrew-gegevens.
8. Een lokale regelsbibliotheek in plaats van afhankelijkheid van een externe API tijdens gebruik.
9. Scheiding tussen officiële data en homebrew-data.
10. Een aparte opslaglaag voor DM-records.
11. Automatische feature tests voor de belangrijkste onderdelen van de applicatie.
12. Responsieve pagina-opbouw zodat de site ook op kleinere schermen bruikbaar blijft.
13. Een zelfbeschrijvende API-root met endpointgroepen en verwerkingsboom.
14. Niet-blokkerende waarschuwingen wanneer een build afwijkt van de officiële 2024-baseline.
15. Een aparte spelerwizard en DM-wizard die allebei gebruikmaken van dezelfde API-opbouw.

## Toekomstige wensen

Dit zijn logische uitbreidingen die goed bij het huidige project passen, maar die niet als harde eis uit de huidige code blijken.

1. Een login- of accountsysteem zodat characters en DM-gegevens per gebruiker gescheiden kunnen worden.
2. Import- en exportmogelijkheden voor characters, bijvoorbeeld als JSON of PDF.
3. Een uitgebreidere inventory- en equipmentmodule.
4. Meer gedetailleerde character-sheet-onderdelen zoals geld, armor, tool proficiencies en spell-preparation workflows.
5. Live synchronisatie tussen spelers en DM tijdens een sessie.
6. Een echte offline-first modus in plaats van alleen lokale browserconcepten.
7. Printvriendelijke character sheets en DM-overzichten.
8. Meer geavanceerde encounter-tools zoals vaste initiative-sjablonen, terreinnotities en battle-presets.
9. Een uitgebreider compendium met nog meer lokale regelsdata en zoekmogelijkheden.
10. Duidelijkere handhaving van volledige officiële background-pakketten, tool proficiencies en starting equipment wanneer het project nog strikter op de 2024-regels moet aansluiten.

## Samenvatting

Als je de huidige codebase als uitgangspunt neemt, dan zijn de belangrijkste afleidbare eisen:

- een Laravel-webapp met een eigen API
- characterbeheer via builder, roster en wizard
- een aparte DM-omgeving met eigen wizard en DM-records
- een homebrew-omgeving die gescheiden blijft van officiële data
- lokale regelsdata en dobbelsteenfunctionaliteit
- validatie, lokale conceptopslag, niet-blokkerende regelswaarschuwingen en automatische feature tests

Daarnaast zijn er al meerdere wensen gerealiseerd, zoals popupbewerking, lokale conceptopslag, een aparte DM-omgeving, een consistente navigatiestructuur en waarschuwingen wanneer een build buiten de officiële 2024-baseline valt. De toekomstige wensen liggen vooral in uitbreiding, synchronisatie, export en nog diepere character- en sessiefuncties.
