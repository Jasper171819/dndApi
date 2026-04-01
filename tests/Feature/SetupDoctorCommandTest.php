<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file checks that the setup doctor gives clear startup feedback in the terminal.

use App\Support\SetupDoctor;

function bindSetupDoctorReport(array $report): void
{
    app()->instance(SetupDoctor::class, new class($report) extends SetupDoctor
    {
        public function __construct(protected array $report)
        {
        }

        public function inspect(): array
        {
            return $this->report;
        }
    });
}

test('the setup doctor command fails with a friendly env error', function () {
    bindSetupDoctorReport([
        'context' => [],
        'passes' => [],
        'warnings' => [],
        'failures' => ['No .env file was found. Copy .env.example to .env before running setup.'],
    ]);

    $this->artisan('app:doctor')
        ->expectsOutputToContain('[FAIL] No .env file was found. Copy .env.example to .env before running setup.')
        ->assertFailed();
});

test('the setup doctor command fails with a friendly app key error', function () {
    bindSetupDoctorReport([
        'context' => [],
        'passes' => [],
        'warnings' => [],
        'failures' => ['APP_KEY is missing. Run composer run setup-local or generate a key before serving the app.'],
    ]);

    $this->artisan('app:doctor')
        ->expectsOutputToContain('[FAIL] APP_KEY is missing. Run composer run setup-local or generate a key before serving the app.')
        ->assertFailed();
});

test('the setup doctor command fails with a friendly mysql unavailable error', function () {
    bindSetupDoctorReport([
        'context' => [],
        'passes' => [],
        'warnings' => [],
        'failures' => ['Could not connect to MySQL at 127.0.0.1:3306. Start MySQL in XAMPP and re-check DB_HOST, DB_PORT, DB_USERNAME, and DB_PASSWORD in .env.'],
    ]);

    $this->artisan('app:doctor')
        ->expectsOutputToContain('[FAIL] Could not connect to MySQL at 127.0.0.1:3306. Start MySQL in XAMPP and re-check DB_HOST, DB_PORT, DB_USERNAME, and DB_PASSWORD in .env.')
        ->assertFailed();
});

test('the setup doctor command fails with a friendly missing database error', function () {
    bindSetupDoctorReport([
        'context' => [],
        'passes' => ['MySQL is reachable at 127.0.0.1:3306.'],
        'warnings' => [],
        'failures' => ['Database dnd_api does not exist yet. Create it in phpMyAdmin, then run php artisan migrate.'],
    ]);

    $this->artisan('app:doctor')
        ->expectsOutputToContain('[PASS] MySQL is reachable at 127.0.0.1:3306.')
        ->expectsOutputToContain('[FAIL] Database dnd_api does not exist yet. Create it in phpMyAdmin, then run php artisan migrate.')
        ->assertFailed();
});

test('the setup doctor command can succeed with warnings', function () {
    bindSetupDoctorReport([
        'context' => ['Project folder: C:\Projects\dnd-api'],
        'passes' => ['PHP version looks good (8.3.0).'],
        'warnings' => ["Rename the final project folder to 'adventurers-ledger' so terminal examples stay simple."],
        'failures' => [],
    ]);

    $this->artisan('app:doctor')
        ->expectsOutputToContain("[WARN] Rename the final project folder to 'adventurers-ledger' so terminal examples stay simple.")
        ->expectsOutputToContain('Startup checks passed.')
        ->assertSuccessful();
});
