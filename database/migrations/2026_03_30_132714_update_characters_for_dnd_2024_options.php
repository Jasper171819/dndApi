<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This file is one of the custom parts that make this app work.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Developer context: This return hands the finished value or response back to the caller.
// Clear explanation: This line sends the result back so the rest of the app can use it.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Developer context: Up handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function up(): void
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (Schema::hasColumn('characters', 'race') && ! Schema::hasColumn('characters', 'species')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->renameColumn('race', 'species');
            });
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (! Schema::hasColumn('characters', 'background')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->string('background')->nullable()->after('class');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    // Developer context: Down handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function down(): void
    {
        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (Schema::hasColumn('characters', 'background')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->dropColumn('background');
            });
        }

        // Developer context: This branch checks a rule before the workflow continues down one path.
        // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
        if (Schema::hasColumn('characters', 'species') && ! Schema::hasColumn('characters', 'race')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->renameColumn('species', 'race');
            });
        }
    }
};
