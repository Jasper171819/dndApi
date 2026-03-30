<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            if (! Schema::hasColumn('characters', 'personality_traits')) {
                $table->text('personality_traits')->nullable()->after('languages');
            }

            if (! Schema::hasColumn('characters', 'ideals')) {
                $table->text('ideals')->nullable()->after('personality_traits');
            }

            if (! Schema::hasColumn('characters', 'bonds')) {
                $table->text('bonds')->nullable()->after('ideals');
            }

            if (! Schema::hasColumn('characters', 'flaws')) {
                $table->text('flaws')->nullable()->after('bonds');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('characters', 'flaws')) {
                $columns[] = 'flaws';
            }

            if (Schema::hasColumn('characters', 'bonds')) {
                $columns[] = 'bonds';
            }

            if (Schema::hasColumn('characters', 'ideals')) {
                $columns[] = 'ideals';
            }

            if (Schema::hasColumn('characters', 'personality_traits')) {
                $columns[] = 'personality_traits';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
