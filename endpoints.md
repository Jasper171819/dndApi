# Overzicht van de endpoints

## Characters

| methode | url | json parameters | json resultaat |
| --- | --- | --- | --- |
| `GET` | `/api/characters` | geen | lijst met alle characters |
| `GET` | `/api/characters/{id}` | geen | 1 character met opgegeven id |
| `POST` | `/api/characters` | `name`, `species`, `class`, `subclass`, `background`, `alignment`, `level`, `notes` | opgeslagen character |
| `PUT` | `/api/characters/{id}` | `name`, `species`, `class`, `subclass`, `background`, `alignment`, `level`, `notes` | bijgewerkt character |
| `DELETE` | `/api/characters/{id}` | geen | melding dat character is verwijderd |

## Toelichting

Verplichte velden bij opslaan en aanpassen:

- `name`
- `species`
- `class`
- `background`
- `level`

Optionele velden:

- `subclass`
- `alignment`
- `notes`
