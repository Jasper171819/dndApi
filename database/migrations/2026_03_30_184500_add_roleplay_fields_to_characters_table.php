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
        Schema::table('characters', function (Blueprint $table) {
            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'personality_traits')) {
                $table->text('personality_traits')->nullable()->after('languages');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'ideals')) {
                $table->text('ideals')->nullable()->after('personality_traits');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'bonds')) {
                $table->text('bonds')->nullable()->after('ideals');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'flaws')) {
                $table->text('flaws')->nullable()->after('bonds');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    // Developer context: Down handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Developer context: This assignment stores a working value that the next lines reuse.
            // Clear explanation: This line saves a piece of information so the next steps can keep using it.
            $columns = [];

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (Schema::hasColumn('characters', 'flaws')) {
                $columns[] = 'flaws';
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (Schema::hasColumn('characters', 'bonds')) {
                $columns[] = 'bonds';
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (Schema::hasColumn('characters', 'ideals')) {
                $columns[] = 'ideals';
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (Schema::hasColumn('characters', 'personality_traits')) {
                $columns[] = 'personality_traits';
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
