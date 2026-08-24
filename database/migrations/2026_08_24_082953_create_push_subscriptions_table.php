<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (user, browser) that has agreed to be notified.
 *
 * The **endpoint is the identity**, not the user: a browser hands back the
 * same URL every time until permission is revoked, and a person with a phone
 * and a desktop is two rows. Every write is an upsert on it — an insert would
 * mean a new row per app launch and a notification arriving N times.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // 500, not the default 255: FCM endpoints run past 200 characters
            // already and Apple's are longer still. A unique index cannot be
            // built over a column too short to hold the value it indexes, so
            // this length is what makes the upsert possible at all.
            $table->string('endpoint', 500)->unique();

            // Encryption material for this one device. Never serialized back
            // out over the API — see PushSubscription::$hidden.
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->default('aesgcm');

            // Purely diagnostic, and the difference between a fix and a
            // shrug: "which of my devices stopped working" is unanswerable
            // without it, since every endpoint looks like line noise.
            $table->string('user_agent', 500)->nullable();

            // The VAPID public key this subscription was created against.
            // A subscription is bound to the key it saw at subscribe time, so
            // after a key change the stale ones still *accept* pushes and
            // deliver nothing. Storing it turns that from archaeology into a
            // query — see the push:doctor command.
            $table->string('vapid_key')->nullable();

            // Set on a 404/410 from the push service, which are the only two
            // answers that mean "stop trying". Marked rather than deleted: a
            // device that vanishes from the settings list reads as "push was
            // never switched on here", which sends people hunting for the
            // wrong bug.
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
