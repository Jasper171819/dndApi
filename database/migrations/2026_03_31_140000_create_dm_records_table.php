<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_records', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 40);
            $table->string('status', 40)->default('draft');
            $table->string('name', 160);
            $table->text('summary');
            $table->string('campaign', 120)->nullable();
            $table->string('session_label', 120)->nullable();
            $table->json('tags')->nullable();
            $table->json('payload');
            $table->foreignId('linked_homebrew_entry_id')->nullable()->constrained('homebrew_entries')->nullOnDelete();
            $table->timestamps();

            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_records');
    }
};
