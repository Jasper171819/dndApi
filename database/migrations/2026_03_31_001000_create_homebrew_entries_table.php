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
    // Developer context: Up handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
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

    // Developer context: Down handles one focused step in this file's workflow; keep its inputs and return shape aligned with nearby callers.
    // Clear explanation: This part does one specific job for the feature this file powers.
    public function down(): void
    {
        Schema::dropIfExists('homebrew_entries');
    }
};
