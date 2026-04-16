# Eerste ontwerp van de database

De database zal 1 hoofdtabel krijgen:

- `characters`

## Velden

De tabel `characters` zal deze velden krijgen:

| veld | soort gegevens | verplicht | uitleg |
| --- | --- | --- | --- |
| `id` | getal | ja | uniek nummer van het karakter |
| `name` | tekst | ja | naam van het karakter |
| `species` | tekst | ja | ras of soort van het karakter |
| `class` | tekst | ja | klasse van het karakter |
| `subclass` | tekst | nee | subklasse van het karakter |
| `background` | tekst | ja | achtergrond van het karakter |
| `alignment` | tekst | nee | alignment van het karakter |
| `level` | getal | ja | level van het karakter |
| `notes` | lange tekst | nee | extra notities |
| `created_at` | datum en tijd | ja | moment van aanmaken |
| `updated_at` | datum en tijd | ja | moment van laatste wijziging |

## Uitleg

Met deze tabel zal de applicatie alle basisgegevens van een karakter kunnen opslaan en via de API kunnen ophalen, aanpassen en verwijderen.
