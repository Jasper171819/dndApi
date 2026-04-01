# macOS Setup

## 1. Rename the Extracted Project Folder

If the ZIP extracts to something like `API_Basis-main/dnd-api`, move the inner Laravel app folder and rename the final folder to:

```text
~/Projects/adventurers-ledger
```

## 2. Open Terminal

1. Press `Command + Space`
2. Type `Terminal`
3. Press `Enter`

Go to the project folder:

```bash
cd ~/Projects/adventurers-ledger
```

## 3. Install Homebrew If You Need It

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

The installer may ask for your macOS password. That is normal.

## 4. Install PHP, Composer, and MySQL

```bash
brew install php composer mysql
```

Check the versions:

```bash
php -v
composer --version
mysql --version
```

## 5. Start MySQL

```bash
brew services start mysql
```

## 6. Create the Database

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS dnd_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If your local MySQL setup uses a password or a different user, update `.env` before continuing.

## 7. Run the Project

First-time setup:

```bash
composer run setup-local
```

Start the app:

```bash
composer run start-local
```

Open:

```text
http://127.0.0.1:8001
```

## 8. Notes

- `sudo` is mainly for system installers, not for normal Composer or Laravel project commands.
- Laravel migrations create the tables for you after the empty `dnd_api` database exists.
