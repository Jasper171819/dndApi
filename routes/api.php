<?php

use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\CompendiumController;
use App\Http\Controllers\Api\ConfiguratorController;
use App\Http\Controllers\Api\DiceController;
use App\Http\Controllers\Api\RulesWizardController;
use Illuminate\Support\Facades\Route;

Route::get('/configurator', [ConfiguratorController::class, 'index']);
Route::get('/compendium', [CompendiumController::class, 'index']);
Route::get('/compendium/{section}', [CompendiumController::class, 'show']);

Route::get('/characters', [CharacterController::class, 'index']);
Route::get('/characters/{id}', [CharacterController::class, 'show']);
Route::post('/characters', [CharacterController::class, 'store']);
Route::delete('/characters/{id}', [CharacterController::class, 'destroy']);

Route::post('/roll-dice', [DiceController::class, 'roll']);
Route::post('/roll-stats', [DiceController::class, 'rollStats']);
Route::post('/rules-wizard/message', [RulesWizardController::class, 'message']);
