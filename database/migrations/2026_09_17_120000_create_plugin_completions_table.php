<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_completions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_event_id', 100);
            $table->string('kind', 16);
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->string('rsn', 32);
            $table->timestamp('occurred_at');
            $table->json('claims')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_completions');
    }
};
