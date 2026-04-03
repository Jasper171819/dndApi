# Eerste ontwerp van de database

De database zal 1 hoofdtabel krijgen:

- `characters`

## Velden

De tabel `characters` zal deze velden krijgen:

| veld | soort gegevens | verplicht | uitleg |
| --- | --- | --- | --- |
| `id` | getal | ja | uniek nummer van het character |
| `name` | tekst | ja | naam van het character |
| `species` | tekst | ja | ras of soort van het character |
| `class` | tekst | ja | class van het character |
| `subclass` | tekst | nee | subclass van het character |
| `background` | tekst | ja | achtergrond van het character |
| `alignment` | tekst | nee | alignment van het character |
| `level` | getal | ja | level van het character |
| `notes` | lange tekst | nee | extra notities |
| `created_at` | datum en tijd | ja | moment van aanmaken |
| `updated_at` | datum en tijd | ja | moment van laatste wijziging |

## Uitleg

Met deze tabel zal de applicatie alle basisgegevens van een character kunnen opslaan en via de API kunnen ophalen, aanpassen en verwijderen.
