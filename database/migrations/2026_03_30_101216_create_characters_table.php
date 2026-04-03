<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('species', 50);
            $table->string('class', 50);
            $table->string('subclass', 50)->nullable();
            $table->string('background', 50);
            $table->string('alignment', 30)->nullable();
            $table->unsignedTinyInteger('level');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
