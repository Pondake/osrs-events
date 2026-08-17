<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('board_id')->constrained('boards')->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->string('short_code', 6);
            $table->string('label')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['board_id', 'short_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_invites');
    }
};
