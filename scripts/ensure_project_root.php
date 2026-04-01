<?php

$cwd = getcwd() ?: __DIR__;
$needsVendor = in_array('--needs-vendor', $argv, true);
$separator = DIRECTORY_SEPARATOR;

$artisanPath = $cwd.$separator.'artisan';
$composerPath = $cwd.$separator.'composer.json';

if (! is_file($artisanPath) || ! is_file($composerPath)) {
    fwrite(STDERR, "This command must be run from the Laravel app folder.\n");
    fwrite(STDERR, "Open a terminal in the final project folder, for example C:\\Projects\\adventurers-ledger, and try again.\n");
    exit(1);
}

$folderName = basename($cwd);
$parentName = basename(dirname($cwd));
$normalizedPath = str_replace('/', '\\', $cwd);

if ($folderName !== 'adventurers-ledger') {
    fwrite(STDOUT, "Note: renaming this folder to 'adventurers-ledger' keeps the terminal commands easier to follow.\n");
}

if (
    preg_match('/-main$/i', $folderName) === 1
    || preg_match('/-main$/i', $parentName) === 1
    || $folderName === 'dnd-api'
    || str_contains($normalizedPath, 'API_Basis-main\\dnd-api')
) {
    fwrite(STDOUT, "Note: this path still looks like the GitHub ZIP structure. Move the inner Laravel app folder to a clean path such as C:\\Projects\\adventurers-ledger.\n");
}

if ($needsVendor && ! is_file($cwd.$separator.'vendor'.$separator.'autoload.php')) {
    fwrite(STDERR, "Dependencies are not installed yet. Run composer install or composer run setup-local from this folder first.\n");
    exit(1);
}
