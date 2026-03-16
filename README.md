# dndApi
Projectbeschrijving – D&D Dice Character Creator

Voor de eindopdracht van het vak API Development en het Basis Agile Portfolio ontwikkel ik een kleine webapplicatie genaamd D&D Dice Character Creator. Het doel van dit project is om zowel mijn technische API-vaardigheden als mijn kennis van de Agile werkwijze aan te tonen.

De applicatie stelt gebruikers in staat om Dungeons & Dragons personages te genereren met behulp van dobbelstenen. In plaats van handmatig statistieken te kiezen, gebruikt de applicatie een dobbelsteenmechanisme om de ability scores te bepalen. Hierdoor ontstaat een eenvoudige en speelse manier om een karakter te maken.

Gebruikers kunnen in de applicatie:

Ability scores genereren met een dobbelsteenmechanisme

Een nieuw personage aanmaken

Opgeslagen personages bekijken

Personages verwijderen

De ability scores worden gegenereerd met de bekende 4d6 drop lowest methode. Hierbij worden vier zeszijdige dobbelstenen gegooid, waarna de laagste worp wordt verwijderd en de overige drie waarden worden opgeteld.

Technisch bestaat de applicatie uit een Laravel REST API die communiceert met een React frontend. De data van de personages wordt opgeslagen in een MySQL database. Daarnaast wordt gebruik gemaakt van tools zoals Git voor versiebeheer en Postman voor het testen van API-endpoints.

Het project heeft een kleine scope, zodat het binnen ongeveer één week ontwikkeld kan worden, maar tegelijkertijd voldoende functionaliteit bevat om API-ontwikkeling, databasegebruik, frontend-integratie en Agile documentatie te demonstreren.
