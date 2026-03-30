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
            if (! Schema::hasColumn('characters', 'alignment')) {
                $table->string('alignment')->nullable()->after('background');
            }

            if (! Schema::hasColumn('characters', 'origin_feat')) {
                $table->string('origin_feat')->nullable()->after('alignment');
            }

            if (! Schema::hasColumn('characters', 'languages')) {
                $table->json('languages')->nullable()->after('origin_feat');
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

            if (Schema::hasColumn('characters', 'languages')) {
                $columns[] = 'languages';
            }

            if (Schema::hasColumn('characters', 'origin_feat')) {
                $columns[] = 'origin_feat';
            }

            if (Schema::hasColumn('characters', 'alignment')) {
                $columns[] = 'alignment';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
