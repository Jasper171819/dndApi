<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->json('skill_proficiencies')->nullable()->after('languages');
            $table->json('skill_expertise')->nullable()->after('skill_proficiencies');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->dropColumn(['skill_proficiencies', 'skill_expertise']);
        });
    }
};
