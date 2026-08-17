<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completed_tiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('player_board_id')->constrained('player_boards')->cascadeOnDelete();
            $table->foreignUuid('tile_id')->constrained('tiles')->cascadeOnDelete();
            $table->timestamp('completed_at')->useCurrent();
            $table->enum('completed_via', ['MANUAL', 'RUNELITE'])->default('MANUAL');

            $table->unique(['player_board_id', 'tile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completed_tiles');
    }
};
