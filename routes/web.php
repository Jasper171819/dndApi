<?php
// Developer context: Laravel reads this route file to map browser page URLs to Blade views instead of JSON controllers.
// Clear explanation: This file lists the normal pages people open in the browser, like the builder, roster, and homebrew pages.

use Illuminate\Support\Facades\Route;

// Developer context: This page route returns the main builder view when the browser opens the site root.
// Clear explanation: This line makes the homepage open the builder page.
Route::view('/', 'welcome')->name('home');
// Developer context: This page route returns the DM dashboard view for encounter running, notes, and quick reference tools.
// Clear explanation: This line makes the DM page open in the browser.
Route::view('/dm', 'dm')->name('dm');
Route::view('/roster', 'roster')->name('roster');
// Developer context: This page route returns the homebrew workshop view without going through an API controller.
// Clear explanation: This line makes the homebrew page open in the browser.
Route::view('/homebrew', 'homebrew')->name('homebrew');
