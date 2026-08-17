<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_boards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignUuid('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->unsignedInteger('current_position')->default(0);
            $table->unsignedInteger('dice_rolls_today')->default(0);
            $table->timestamp('last_roll_date')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_boards');
    }
};
