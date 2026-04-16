<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->apiLogPath = storage_path('logs/test-api.log');

    File::ensureDirectoryExists(dirname($this->apiLogPath));
    File::delete($this->apiLogPath);

    config()->set('logging.channels.api.path', $this->apiLogPath);
    app('log')->forgetChannel('api');
});

test('succesvolle api writes loggen op info niveau', function () {
    $response = $this->postJson('/api/characters', auditCharacterPayload())
        ->assertCreated();

    $contents = readAuditLog($this->apiLogPath);

    expect($response->headers->get('X-Request-Id'))->not->toBeNull();
    expect($contents)
        ->toContain('api.mutation')
        ->toContain('"action":"create"')
        ->toContain('"entity_type":"character"')
        ->toContain('"status":201');
});

test('validatiefouten loggen op warning niveau', function () {
    $this->postJson('/api/characters', [
        'name' => '',
        'species' => '',
        'class' => '',
        'background' => '',
        'level' => 25,
    ])->assertStatus(422);

    $contents = readAuditLog($this->apiLogPath);

    expect($contents)
        ->toContain('api.warning')
        ->toContain('validation_failed')
        ->toContain('name')
        ->toContain('level');
});

test('niet gevonden meldingen loggen op warning niveau', function () {
    $this->getJson('/api/characters/999999')
        ->assertNotFound();

    $contents = readAuditLog($this->apiLogPath);

    expect($contents)
        ->toContain('api.warning')
        ->toContain('not_found')
        ->toContain('/api/characters/999999');
});

test('onverwachte fouten loggen op error niveau', function () {
    Route::middleware('api')->get('/api/test-audit-error', static function () {
        throw new RuntimeException('Geforceerde testfout.');
    });

    $this->getJson('/api/test-audit-error')
        ->assertStatus(500);

    $contents = readAuditLog($this->apiLogPath);

    expect($contents)
        ->toContain('api.error')
        ->toContain('unhandled_exception')
        ->toContain('RuntimeException')
        ->toContain('Geforceerde testfout.');
});

function auditCharacterPayload(): array
{
    return [
        'name' => 'Audit Character',
        'species' => 'Human',
        'class' => 'Fighter',
        'subclass' => 'Champion',
        'background' => 'Guard',
        'alignment' => 'Neutral Good',
        'level' => 2,
        'notes' => 'Wordt gebruikt voor logging tests.',
    ];
}

function readAuditLog(string $path): string
{
    clearstatcache(true, $path);

    return File::exists($path) ? File::get($path) : '';
}
