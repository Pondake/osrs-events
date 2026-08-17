<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('board_id')->constrained('boards')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('title_override')->nullable();
            $table->enum('type', ['NORMAL', 'SNAKE', 'LADDER'])->default('NORMAL');
            $table->unsignedInteger('target_position')->nullable();
            $table->timestamps();

            $table->unique(['board_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiles');
    }
};
