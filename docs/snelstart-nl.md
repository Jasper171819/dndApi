# Korte Windows Snelstart

Deze korte handleiding gaat uit van:
- Windows
- XAMPP voor `MySQL` en `phpMyAdmin`
- Composer apart geinstalleerd
- starten met `php artisan serve`

## 1. Zet de map op een simpele plek

Als GitHub een map maakt zoals `API_Basis-main\dnd-api`, laat die naam dan niet zo staan.

Gebruik liever:

```text
C:\Projects\adventurers-ledger
```

`C:\xampp\htdocs\adventurers-ledger` mag ook, maar `htdocs` is niet verplicht.

## 2. Installeer XAMPP en Composer

- installeer XAMPP
- controleer PHP met:

```powershell
C:\xampp\php\php.exe -v
```

- installeer daarna Composer met de officiele Windows installer
- kies tijdens de Composer installatie:

```text
C:\xampp\php\php.exe
```

- controleer Composer met:

```powershell
composer --version
```

## 3. Open een terminal

PowerShell:

```powershell
cd C:\Projects\adventurers-ledger
```

CMD:

```cmd
cd /d C:\Projects\adventurers-ledger
```

## 4. Start XAMPP

Open de XAMPP Control Panel en start:
- `MySQL`
- `Apache` alleen als je `phpMyAdmin` wilt openen

## 5. Maak de database

Open:

```text
http://localhost/phpmyadmin
```

Maak daarna een lege database aan met de naam:

```text
dnd_api
```

Gebruik collation:

```text
utf8mb4_unicode_ci
```

Maak geen tabellen met de hand. Laravel doet dat via migrations.

## 6. Eerste setup

```powershell
composer run setup-local
```

## 7. Start de app

```powershell
composer run start-local
```

Open daarna:

```text
http://127.0.0.1:8001
```

## 8. Belangrijk

- gebruik voor eerste testers altijd dezelfde mapnaam: `adventurers-ledger`
- run commando's vanuit de uiteindelijke projectmap
- als `artisan` niet gevonden wordt, zit je waarschijnlijk nog in de verkeerde map
