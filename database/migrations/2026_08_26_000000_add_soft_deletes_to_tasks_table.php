<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A deleted task used to be gone outright, so there was nothing to undo — the
 * admin delete button fired instantly with no confirm at all, reported live
 * as "Gaat instant!" (goes instantly). Soft-deleting instead of hard-deleting
 * is also what makes undo cheap to restore correctly: tiles/bingo squares
 * point at a task by `task_id` with `nullOnDelete`, which only fires on an
 * actual SQL DELETE. A soft delete is an UPDATE, so a tile that was using a
 * task keeps pointing at it the whole time it's "deleted" — restoring the
 * task is the entire undo, nothing needs re-linking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
