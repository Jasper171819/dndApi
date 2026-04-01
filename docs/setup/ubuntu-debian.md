# Ubuntu Or Debian Setup

## 1. Rename the Extracted Project Folder

If the ZIP extracts to something like `API_Basis-main/dnd-api`, move the inner Laravel app folder and rename the final folder to:

```text
~/Projects/adventurers-ledger
```

## 2. Open Terminal

Use one of these:
- press `Ctrl + Alt + T`
- open the applications menu and search for `Terminal`

Move into the project folder:

```bash
cd ~/Projects/adventurers-ledger
```

## 3. Install PHP And MySQL

```bash
sudo apt update
sudo apt install php-cli php-mysql php-mbstring php-xml php-curl php-zip unzip curl mysql-server
```

Check the tools:

```bash
php -v
mysql --version
```

## 4. Install Composer From The Official Installer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
php -r "unlink('composer-setup.php');"
composer --version
```

## 5. Start MySQL

```bash
sudo systemctl enable --now mysql
```

## 6. Create The Database

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS dnd_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

The default `.env.example` is XAMPP-friendly, so if your Linux MySQL user is not `root` with a blank password, update `.env` before you run the setup command.

## 7. Run The Project

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

- `sudo` is expected for package installation and for moving the Composer binary into `/usr/local/bin`.
- Do not run normal project commands like `composer install` or `php artisan serve` with `sudo`.
- Laravel migrations create the tables after the empty `dnd_api` database exists.
