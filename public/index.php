<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
// Developer context: This branch checks a rule before the workflow continues down one path.
// Clear explanation: This line asks whether a condition is true so the code can choose the right path.
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
// Developer context: This assignment stores a working value that the next lines reuse.
// Clear explanation: This line saves a piece of information so the next steps can keep using it.
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
