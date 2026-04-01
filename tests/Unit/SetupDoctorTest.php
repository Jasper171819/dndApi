<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file checks the folder-name warnings that help ZIP users avoid confusing nested paths.

use App\Support\SetupDoctor;

test('folder warnings call out nested github zip names', function () {
    $warnings = app(SetupDoctor::class)->folderWarningsFor('C:\Projects\API_Basis-main\dnd-api');

    expect($warnings)->toContain("Rename the final project folder to 'adventurers-ledger' so terminal examples stay simple.")
        ->and(implode(' ', $warnings))->toContain('GitHub ZIP structure');
});

test('folder warnings stay quiet for the recommended folder name', function () {
    $warnings = app(SetupDoctor::class)->folderWarningsFor('C:\Projects\adventurers-ledger');

    expect($warnings)->toBe([]);
});
