# Overzicht van de API-routes

## Karakters

| methode | url | json parameters | json resultaat |
| --- | --- | --- | --- |
| `GET` | `/api/characters` | geen | lijst met alle karakters |
| `GET` | `/api/characters/{id}` | geen | 1 karakter met opgegeven id |
| `POST` | `/api/characters` | `name`, `species`, `class`, `subclass`, `background`, `alignment`, `level`, `notes` | opgeslagen karakter |
| `PUT` | `/api/characters/{id}` | `name`, `species`, `class`, `subclass`, `background`, `alignment`, `level`, `notes` | bijgewerkt karakter |
| `DELETE` | `/api/characters/{id}` | geen | melding dat karakter is verwijderd |

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
