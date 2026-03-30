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
            if (! Schema::hasColumn('characters', 'age')) {
                $table->string('age')->nullable()->after('flaws');
            }

            if (! Schema::hasColumn('characters', 'height')) {
                $table->string('height')->nullable()->after('age');
            }

            if (! Schema::hasColumn('characters', 'weight')) {
                $table->string('weight')->nullable()->after('height');
            }

            if (! Schema::hasColumn('characters', 'eyes')) {
                $table->string('eyes')->nullable()->after('weight');
            }

            if (! Schema::hasColumn('characters', 'hair')) {
                $table->string('hair')->nullable()->after('eyes');
            }

            if (! Schema::hasColumn('characters', 'skin')) {
                $table->string('skin')->nullable()->after('hair');
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

            foreach (['skin', 'hair', 'eyes', 'weight', 'height', 'age'] as $column) {
                if (Schema::hasColumn('characters', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
