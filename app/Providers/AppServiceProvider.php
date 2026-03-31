<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    // Developer context: Register handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    // Developer context: Boot handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function boot(): void
    {
        //
    }
}
