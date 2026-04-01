<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file checks that the startup helper catches the common mistake of opening the terminal in the wrong folder.

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

test('the project root guard explains the wrong folder error clearly', function () {
    $temporaryDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ledger-root-guard-'.bin2hex(random_bytes(5));

    mkdir($temporaryDirectory);

    try {
        $phpBinary = (new PhpExecutableFinder())->find(false);

        expect($phpBinary)->not->toBeFalse();

        $process = new Process([$phpBinary, base_path('scripts/ensure_project_root.php')], $temporaryDirectory);
        $process->run();

        expect($process->getExitCode())->toBe(1);
        expect($process->getErrorOutput().$process->getOutput())
            ->toContain('This command must be run from the Laravel app folder.')
            ->toContain('C:\Projects\adventurers-ledger');
    } finally {
        rmdir($temporaryDirectory);
    }
});
