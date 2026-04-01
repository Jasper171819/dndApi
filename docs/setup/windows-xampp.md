# Windows Setup With XAMPP

This is the main beginner guide for people who download the GitHub ZIP instead of cloning the repository.

## What You Need

- XAMPP
- Composer
- the extracted project ZIP

XAMPP is used here for:
- `MySQL`
- `phpMyAdmin`
- the bundled PHP executable at `C:\xampp\php\php.exe`

The Laravel app itself still runs with:

```powershell
php artisan serve
```

## 1. Pick a Clean Folder Name

If GitHub gives you a nested path like this:

```text
API_Basis-main\dnd-api
```

do this first:

1. move the inner `dnd-api` folder somewhere easy to find
2. rename that final folder to `adventurers-ledger`

Recommended final path:

```text
C:\Projects\adventurers-ledger
```

Optional future-project path:

```text
C:\xampp\htdocs\adventurers-ledger
```

`htdocs` is optional. It is only an organization preference here.

## 2. Install XAMPP

1. Download XAMPP from Apache Friends.
2. Install it with the default Windows installer.
3. Open the XAMPP Control Panel.

You do not need to serve Laravel from Apache for this guide.

## 3. Check the PHP That Comes With XAMPP

Open PowerShell and run:

```powershell
C:\xampp\php\php.exe -v
```

If you want the shorter `php` command to work everywhere, add `C:\xampp\php` to your Windows `PATH`.

After that, this should work in a new terminal window:

```powershell
php -v
```

## 4. Install Composer

1. Download the official Windows Composer installer from `getcomposer.org`.
2. Run the installer.
3. When it asks for PHP, point it to:

```text
C:\xampp\php\php.exe
```

4. Close your terminal and open a new one.
5. Check Composer:

```powershell
composer --version
```

Administrator note:
- run installers as Administrator only if Windows asks for it
- normal Laravel project commands should be run in a normal terminal window

## 5. Open a Terminal in the Project Folder

### PowerShell

1. Open Start.
2. Search for `PowerShell`.
3. Open it.
4. Run:

```powershell
cd C:\Projects\adventurers-ledger
```

### Command Prompt

1. Open Start.
2. Search for `cmd`.
3. Open Command Prompt.
4. Run:

```cmd
cd /d C:\Projects\adventurers-ledger
```

### Windows Terminal

1. Open Start.
2. Search for `Windows Terminal`.
3. Open it.
4. Use either the PowerShell or Command Prompt command from above.

## 6. Start XAMPP Services

In the XAMPP Control Panel:

1. start `MySQL`
2. start `Apache` only if you want to use `phpMyAdmin`

Apache is not used to serve the Laravel app in this beginner flow. It is only useful here so `phpMyAdmin` loads in the browser.

## 7. Create the Database

### Option A: phpMyAdmin

1. Open `http://localhost/phpmyadmin`
2. Click `New`
3. Create a database named:

```text
dnd_api
```

4. Use collation:

```text
utf8mb4_unicode_ci
```

Do not create tables manually.

Laravel creates the table structure when you run migrations.

### Option B: MySQL Command Line

If you prefer the terminal:

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS dnd_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 8. Run the First-Time Setup

Quickest first-time path:

```powershell
composer run setup-local
```

What it does:
- copies `.env` if needed
- installs PHP dependencies
- creates an `APP_KEY` if needed
- runs the startup doctor
- runs Laravel migrations

If you want a check-only step after dependencies are installed:

```powershell
composer install
composer run doctor
```

## 9. Start the App

```powershell
composer run start-local
```

Open:

```text
http://127.0.0.1:8001
```

## 10. Database Structure

Create only the empty database yourself. Laravel migrations build the tables.

Framework tables:
- `users`
- `cache`
- `jobs`

Main app tables:
- `characters`
- `homebrew_entries`
- `dm_records`

If the schema changes later, update it with:

```powershell
php artisan migrate
```

## 11. Troubleshooting

### `php` is not recognized

- use `C:\xampp\php\php.exe -v`
- add `C:\xampp\php` to your Windows `PATH`
- close the terminal and open a new one

### `composer` is not recognized

- reinstall Composer
- make sure the installer finished successfully
- open a new terminal window and try `composer --version` again

### `artisan` cannot be found

- make sure you are inside the final project folder
- the command should run from a path like `C:\Projects\adventurers-ledger`
- do not run commands from the outer GitHub ZIP folder if it still contains `API_Basis-main`

### MySQL connection failed

- make sure `MySQL` is started in XAMPP
- check `.env` and confirm it still points to `127.0.0.1`, port `3306`, and database `dnd_api`

### phpMyAdmin does not open

- start `Apache` in the XAMPP Control Panel
- then refresh `http://localhost/phpmyadmin`
