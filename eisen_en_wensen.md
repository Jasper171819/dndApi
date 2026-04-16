# Eisen en wensen

## Inleiding

Dit document beschrijft de eisen en wensen voor de applicatie **D&D Karakterbeheer**.

De applicatie zal klein en overzichtelijk blijven. Het doel is een simpele frontend te maken die werkt met een eigen Laravel API.

## Eisen

Dit zijn de minimale eisen.

1. De gebruiker moet een lijst met alle karakters kunnen zien.
2. De gebruiker moet de gegevens van 1 karakter kunnen bekijken.
3. De gebruiker moet een nieuw karakter kunnen toevoegen.
4. De gebruiker moet een bestaand karakter kunnen aanpassen.
5. De gebruiker moet een bestaand karakter kunnen verwijderen.
6. De frontend moet de eigen API gebruiken.
7. De API moet JSON teruggeven.
8. De API moet gebruikmaken van `GET`, `POST`, `PUT` en `DELETE`.
9. De database moet een tabel hebben waarin de karaktergegevens worden opgeslagen.
10. Er moet een data seeder zijn met genoeg voorbeelddata om de app te testen.
11. De API moet logging hebben op `info`, `warning` en `error`.
12. Er moeten tests zijn die laten zien dat de belangrijkste onderdelen werken.

## Wensen

Dit zijn extra wensen. Deze zijn handig, maar niet verplicht voor het minimum.

1. De pagina zou een nette en duidelijke opmaak moeten hebben.
2. De gebruiker zou op 1 pagina zowel de lijst als het formulier moeten zien.
3. Bewerken zou via een apart popoutformulier moeten kunnen.
4. De gebruiker zou duidelijke meldingen moeten krijgen bij opslaan, aanpassen en verwijderen.
5. De API zou invoer moeten opschonen, zoals lege spaties of HTML-tags.
6. Het project zou makkelijk lokaal te starten moeten zijn.
7. Er mag een extra hulppagina zijn met een overzicht van de API-routes.
