<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A record of admin actions that are otherwise untraceable — role and
     * permission changes, user deletion, site settings edits.
     *
     * Actor and target are stored TWICE: a nullable id for linking while the
     * row still exists, and a plain-text label captured at write time. That
     * denormalization is the entire point rather than an optimization — the
     * action most worth logging is deletion, and a log that only holds a
     * foreign key loses the name of whoever was deleted at exactly the
     * moment it becomes the only remaining evidence. The actor's own id is
     * nullable for the same reason: admins can be deleted too, and their
     * trail must outlive them.
     *
     * No updated_at: an audit row that can be edited isn't an audit row.
     * Nothing in the app writes to these after insert, and there's no UI to.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('actor_id')->nullable();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->string('actor_label');

            // Dotted, e.g. 'user.role_granted' — the prefix groups actions by
            // subject for the filter dropdown without a second column.
            $table->string('action')->index();

            $table->string('target_type')->nullable();
            $table->uuid('target_id')->nullable();
            $table->string('target_label')->nullable();

            // Whatever the action needs to be readable later: which role,
            // which settings keys changed and to what.
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
