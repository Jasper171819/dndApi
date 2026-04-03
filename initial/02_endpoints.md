# Eerste ontwerp van de endpoints

De applicatie zal de volgende endpoints krijgen:

| methode | url | json parameters | json resultaat |
| --- | --- | --- | --- |
| `GET` | `/api/characters` | geen | lijst met alle characters |
| `GET` | `/api/characters/{id}` | geen | 1 character met alle gegevens |
| `POST` | `/api/characters` | `name`, `species`, `class`, `background`, `level`, optioneel `subclass`, `alignment`, `notes` | nieuw opgeslagen character |
| `PUT` | `/api/characters/{id}` | `name`, `species`, `class`, `background`, `level`, optioneel `subclass`, `alignment`, `notes` | bijgewerkt character |
| `DELETE` | `/api/characters/{id}` | geen | melding dat het character is verwijderd |

## Opmerking

De API zal alleen over characters gaan. Er zullen in deze versie geen extra onderdelen bijkomen.
