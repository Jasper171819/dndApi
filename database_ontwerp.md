# Database ontwerp

## Doel

De database zal gebruikt worden om karakters op te slaan.

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

- `id` is het unieke nummer van het karakter
- `name` is de naam van het karakter
- `species` is het ras of soort
- `class` is de klasse van het karakter
- `subclass` is de subklasse, als die is ingevuld
- `background` is de achtergrond van het karakter
- `alignment` is de alignment, als die is ingevuld
- `level` is het level van het karakter
- `notes` is voor extra informatie

## Relaties

Deze kleine versie heeft geen relaties met andere applicatietabellen.
