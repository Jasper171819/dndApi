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
            if (! Schema::hasColumn('characters', 'age')) {
                $table->string('age')->nullable()->after('flaws');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'height')) {
                $table->string('height')->nullable()->after('age');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'weight')) {
                $table->string('weight')->nullable()->after('height');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'eyes')) {
                $table->string('eyes')->nullable()->after('weight');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'hair')) {
                $table->string('hair')->nullable()->after('eyes');
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if (! Schema::hasColumn('characters', 'skin')) {
                $table->string('skin')->nullable()->after('hair');
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

            // Developer context: This loop applies the same step to each entry in the current list.
            // Clear explanation: This line repeats the same work for every item in a group.
            foreach (['skin', 'hair', 'eyes', 'weight', 'height', 'age'] as $column) {
                if (Schema::hasColumn('characters', $column)) {
                    $columns[] = $column;
                }
            }

            // Developer context: This branch checks a rule before the workflow continues down one path.
            // Clear explanation: This line asks whether a condition is true so the code can choose the right path.
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
