# Database ontwerp

## Doel

De database zal gebruikt worden om characters op te slaan.

## Tabel

De applicatie gebruikt 1 hoofdtafel:

- `characters`

## Velden van de tabel `characters`

- `id`
- `name`
- `species`
- `class`
- `subclass`
- `background`
- `alignment`
- `level`
- `notes`
- `created_at`
- `updated_at`

## Uitleg

- `id` is het unieke nummer van het character
- `name` is de naam van het character
- `species` is het ras of soort
- `class` is de class van het character
- `subclass` is de subclass, als die is ingevuld
- `background` is de achtergrond van het character
- `alignment` is de alignment, als die is ingevuld
- `level` is het level van het character
- `notes` is voor extra informatie

## Relaties

Deze kleine versie heeft geen relaties met andere applicatietabellen.
