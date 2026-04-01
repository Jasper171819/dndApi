<?php

$cwd = getcwd() ?: __DIR__;
$envPath = $cwd.DIRECTORY_SEPARATOR.'.env';

if (! is_file($envPath)) {
    fwrite(STDERR, "No .env file was found. Copy .env.example to .env first.\n");
    exit(1);
}

$contents = file_get_contents($envPath);

if ($contents === false) {
    fwrite(STDERR, "The .env file could not be read.\n");
    exit(1);
}

if (preg_match('/^APP_KEY=(.+)$/m', $contents, $matches) === 1 && trim($matches[1]) !== '') {
    fwrite(STDOUT, "APP_KEY already set.\n");
    exit(0);
}

$appKey = 'base64:'.base64_encode(random_bytes(32));

if (preg_match('/^APP_KEY=.*$/m', $contents) === 1) {
    $updatedContents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$appKey, $contents, 1);
} else {
    $updatedContents = rtrim($contents).PHP_EOL.'APP_KEY='.$appKey.PHP_EOL;
}

if ($updatedContents === null || file_put_contents($envPath, $updatedContents) === false) {
    fwrite(STDERR, "The .env file could not be updated with a new APP_KEY.\n");
    exit(1);
}

fwrite(STDOUT, "APP_KEY created in .env.\n");
