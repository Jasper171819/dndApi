<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file gathers the startup checks that help someone see whether the app is ready to run locally.

namespace App\Support;

use PDO;
use PDOException;

class SetupDoctor
{
    /**
     * Build a plain report that the Artisan command can print for first-time setup.
     *
     * @return array{context:list<string>,passes:list<string>,warnings:list<string>,failures:list<string>}
     */
    public function inspect(): array
    {
        $path = base_path();
        $defaultConnection = (string) config('database.default', 'mysql');

        $report = [
            'context' => [
                "Project folder: {$path}",
                "Database connection: {$defaultConnection}",
                'Recommended folder name for ZIP users: adventurers-ledger',
            ],
            'passes' => [],
            'warnings' => [],
            'failures' => [],
        ];

        foreach ($this->folderWarningsFor($path) as $warning) {
            $report['warnings'][] = $warning;
        }

        if (PHP_VERSION_ID >= 80300) {
            $report['passes'][] = sprintf('PHP version looks good (%s).', PHP_VERSION);
        } else {
            $report['failures'][] = sprintf(
                'PHP 8.3 or newer is required. Current version: %s. Update PHP before continuing.',
                PHP_VERSION
            );
        }

        $envPath = base_path('.env');

        if (is_file($envPath)) {
            $report['passes'][] = '.env file found.';
        } else {
            $report['failures'][] = 'No .env file was found. Copy .env.example to .env before running setup.';
        }

        if (filled((string) config('app.key'))) {
            $report['passes'][] = 'APP_KEY is set.';
        } else {
            $report['failures'][] = 'APP_KEY is missing. Run composer run setup-local or generate a key before serving the app.';
        }

        $missingExtensions = $this->missingExtensions();

        if ($missingExtensions === []) {
            $report['passes'][] = 'Required PHP extensions are loaded.';
        } else {
            $report['failures'][] = 'Missing PHP extensions: '.implode(', ', $missingExtensions).'.';
        }

        $nonWritablePaths = $this->nonWritablePaths();

        if ($nonWritablePaths === []) {
            $report['passes'][] = 'Laravel storage paths are writable.';
        } else {
            $report['failures'][] = 'These paths are not writable yet: '.implode(', ', $nonWritablePaths).'.';
        }

        foreach ($this->databaseChecks($defaultConnection, $missingExtensions) as $check) {
            $report[$check['status']][] = $check['message'];
        }

        return $report;
    }

    /**
     * Provide rename hints when the extracted ZIP still uses nested repo names.
     *
     * @return list<string>
     */
    public function folderWarningsFor(string $path): array
    {
        $warnings = [];
        $normalizedPath = str_replace('/', '\\', $path);
        $folderName = basename($path);
        $parentName = basename(dirname($path));

        if ($folderName !== 'adventurers-ledger') {
            $warnings[] = "Rename the final project folder to 'adventurers-ledger' so terminal examples stay simple.";
        }

        if (
            preg_match('/-main$/i', $folderName) === 1
            || preg_match('/-main$/i', $parentName) === 1
            || $folderName === 'dnd-api'
            || str_contains($normalizedPath, 'API_Basis-main\\dnd-api')
        ) {
            $warnings[] = 'This path still looks like the GitHub ZIP structure. Move the inner Laravel app folder to a clean path such as C:\\Projects\\adventurers-ledger.';
        }

        return $warnings;
    }

    /**
     * @return list<string>
     */
    protected function missingExtensions(): array
    {
        $requiredExtensions = [
            'ctype',
            'fileinfo',
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'pdo_mysql',
            'tokenizer',
            'xml',
        ];

        return array_values(array_filter(
            $requiredExtensions,
            static fn (string $extension): bool => ! extension_loaded($extension)
        ));
    }

    /**
     * @return list<string>
     */
    protected function nonWritablePaths(): array
    {
        $paths = [
            base_path('storage'),
            base_path('bootstrap/cache'),
        ];

        return array_values(array_filter(
            $paths,
            static fn (string $path): bool => ! is_writable($path)
        ));
    }

    /**
     * @param  list<string>  $missingExtensions
     * @return list<array{status:string,message:string}>
     */
    protected function databaseChecks(string $defaultConnection, array $missingExtensions): array
    {
        if ($defaultConnection !== 'mysql') {
            return [[
                'status' => 'warnings',
                'message' => "DB_CONNECTION is set to {$defaultConnection}. The beginner quickstart assumes MySQL/XAMPP, so double-check your custom database setup.",
            ]];
        }

        if (in_array('pdo_mysql', $missingExtensions, true)) {
            return [];
        }

        $connection = config('database.connections.mysql', []);
        $host = (string) ($connection['host'] ?? '');
        $port = (string) ($connection['port'] ?? '3306');
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');
        $charset = (string) ($connection['charset'] ?? 'utf8mb4');

        $checks = [];

        if ($host === '' || $database === '') {
            $checks[] = [
                'status' => 'failures',
                'message' => 'DB_HOST or DB_DATABASE is empty. Update your .env so the app can reach MySQL.',
            ];

            return $checks;
        }

        try {
            $pdo = $this->connectToMysqlServer($host, $port, $username, $password, $charset);
            $checks[] = [
                'status' => 'passes',
                'message' => "MySQL is reachable at {$host}:{$port}.",
            ];
        } catch (PDOException) {
            $checks[] = [
                'status' => 'failures',
                'message' => "Could not connect to MySQL at {$host}:{$port}. Start MySQL in XAMPP and re-check DB_HOST, DB_PORT, DB_USERNAME, and DB_PASSWORD in .env.",
            ];

            return $checks;
        }

        if ($this->mysqlDatabaseExists($pdo, $database)) {
            $checks[] = [
                'status' => 'passes',
                'message' => "Database {$database} exists.",
            ];
        } else {
            $checks[] = [
                'status' => 'failures',
                'message' => "Database {$database} does not exist yet. Create it in phpMyAdmin, then run php artisan migrate.",
            ];
        }

        return $checks;
    }

    protected function connectToMysqlServer(
        string $host,
        string $port,
        string $username,
        string $password,
        string $charset
    ): PDO {
        return new PDO(
            "mysql:host={$host};port={$port};charset={$charset}",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ],
        );
    }

    protected function mysqlDatabaseExists(PDO $pdo, string $database): bool
    {
        $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :database');
        $statement->execute(['database' => $database]);

        return (bool) $statement->fetchColumn();
    }
}
