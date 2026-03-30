<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homebrew_entries', function (Blueprint $table) {
            $table->id();
            $table->string('category', 40)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('name', 120);
            $table->text('summary');
            $table->text('details')->nullable();
            $table->text('source_notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homebrew_entries');
    }
};
