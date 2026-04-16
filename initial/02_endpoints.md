# Eerste ontwerp van de endpoints

De applicatie zal de volgende endpoints krijgen:

| methode | url | json parameters | json resultaat |
| --- | --- | --- | --- |
| `GET` | `/api/characters` | geen | lijst met alle karakters |
| `GET` | `/api/characters/{id}` | geen | 1 karakter met alle gegevens |
| `POST` | `/api/characters` | `name`, `species`, `class`, `background`, `level`, optioneel `subclass`, `alignment`, `notes` | nieuw opgeslagen karakter |
| `PUT` | `/api/characters/{id}` | `name`, `species`, `class`, `background`, `level`, optioneel `subclass`, `alignment`, `notes` | bijgewerkt karakter |
| `DELETE` | `/api/characters/{id}` | geen | melding dat het karakter is verwijderd |

## Opmerking

De API zal alleen over karakters gaan. Er zullen in deze versie geen extra onderdelen bijkomen.
