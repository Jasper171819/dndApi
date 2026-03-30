<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/roster', 'roster')->name('roster');
Route::view('/homebrew', 'homebrew')->name('homebrew');
