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
        if (Schema::hasColumn('characters', 'race') && ! Schema::hasColumn('characters', 'species')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->renameColumn('race', 'species');
            });
        }

        if (! Schema::hasColumn('characters', 'background')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->string('background')->nullable()->after('class');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('characters', 'background')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->dropColumn('background');
            });
        }

        if (Schema::hasColumn('characters', 'species') && ! Schema::hasColumn('characters', 'race')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->renameColumn('species', 'race');
            });
        }
    }
};
