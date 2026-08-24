<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Support\PushMessage;
use Illuminate\Support\Collection;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * The one place an encrypted push actually leaves this server.
 *
 * **A notification that fails to send must never cost more than the
 * notification.** Almost everything here is in service of that: every send
 * site is a side effect of something a user already did — a claim reviewed, a
 * standings sync, a scheduled sweep — and all of those have already succeeded
 * by the time we get here. A throw would undo none of it while making the
 * original action look like it failed.
 *
 * Every caller gets a structured count back rather than a boolean, because
 * "nothing happened" has four different meanings and each has a different
 * fix:
 *
 *  - `skipped`  — no VAPID keys, or nothing to send to. Normal locally.
 *  - `sent`     — the push service accepted it. Not "it arrived": the last
 *                 hop to the device is unobservable from here, by design.
 *  - `expired`  — 404/410, the only two answers meaning "stop trying".
 *  - `failed`   — everything else. Transient; the row stays alive.
 */
class WebPushService
{
    /** @var array{sent: int, expired: int, failed: int, skipped: int} */
    private const EMPTY_RESULT = ['sent' => 0, 'expired' => 0, 'failed' => 0, 'skipped' => 0];

    public function isConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    /**
     * Send one message to every live subscription given.
     *
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public function send(Collection $subscriptions, PushMessage $message): array
    {
        $result = self::EMPTY_RESULT;

        if ($subscriptions->isEmpty()) {
            return $result;
        }

        // Missing keys is a normal state, not an error: it is what a fresh
        // clone and a fresh deploy both look like. Returning a skipped count
        // rather than throwing is what lets the settings page say "the server
        // has no keys configured" instead of showing a 500 — see
        // NotificationController::show.
        if (! $this->isConfigured()) {
            return [...$result, 'skipped' => $subscriptions->count()];
        }

        try {
            $webPush = new WebPush(
                ['VAPID' => [
                    'subject' => config('webpush.vapid.subject'),
                    'publicKey' => config('webpush.vapid.public_key'),
                    'privateKey' => config('webpush.vapid.private_key'),
                ]],
                [
                    'TTL' => config('webpush.ttl'),
                    'urgency' => config('webpush.urgency'),
                    'topic' => $message->tag ?? $message->category,
                ],
                // Explicit, not null: the library only applies its own
                // default when one is actually passed, and an unbounded
                // request can hold a queue worker open indefinitely against
                // a push service that has stopped answering.
                config('webpush.timeout'),
            );
        } catch (Throwable $error) {
            // A malformed keypair throws here rather than at send time — the
            // constructor validates VAPID. Nothing has been sent, so the
            // whole batch counts as failed.
            report($error);

            return [...$result, 'failed' => $subscriptions->count()];
        }

        $payload = json_encode($message->toArray());
        $byEndpoint = $subscriptions->keyBy('endpoint');

        foreach ($subscriptions as $subscription) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                    ]),
                    $payload,
                );
            } catch (Throwable $error) {
                // Queueing encrypts, and encryption throws outright on a
                // corrupt stored key or an OpenSSL install that cannot
                // generate the ephemeral EC key. One bad row must not take
                // the other twenty-nine with it.
                report($error);
                $result['failed']++;
            }
        }

        try {
            // The loop, not just the call. flush() returns a Generator, so
            // wrapping only the invocation catches nothing at all — the
            // request is not made until the first iteration, and that is
            // where a transport exception actually surfaces.
            foreach ($webPush->flush() as $report) {
                $row = $byEndpoint->get($report->getEndpoint());

                if ($report->isSuccess()) {
                    $result['sent']++;
                    $row?->forceFill(['last_used_at' => now()])->saveQuietly();

                    continue;
                }

                if ($report->isSubscriptionExpired()) {
                    // Marked, never deleted. A device that disappears from
                    // the settings list reads as "push was never enabled
                    // here", which sends people looking for the wrong bug.
                    $result['expired']++;
                    $row?->forceFill(['expired_at' => now()])->saveQuietly();

                    continue;
                }

                // 429 and 5xx land here: transient, so the row stays live and
                // the next send tries again.
                $result['failed']++;
                report(new \RuntimeException(
                    'Web push rejected: '.$report->getReason().' ('.$report->getEndpoint().')'
                ));
            }
        } catch (Throwable $error) {
            report($error);
            $result['failed'] += max(0, $subscriptions->count() - $result['sent'] - $result['expired'] - $result['failed']);
        }

        return $result;
    }

    /** @return array{sent: int, expired: int, failed: int, skipped: int} */
    public static function emptyResult(): array
    {
        return self::EMPTY_RESULT;
    }

    /**
     * Add two results together — for callers that fan out over many users
     * and want one line in a command's output rather than N.
     *
     * @param  array{sent: int, expired: int, failed: int, skipped: int}  $a
     * @param  array{sent: int, expired: int, failed: int, skipped: int}  $b
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public static function merge(array $a, array $b): array
    {
        return [
            'sent' => $a['sent'] + $b['sent'],
            'expired' => $a['expired'] + $b['expired'],
            'failed' => $a['failed'] + $b['failed'],
            'skipped' => $a['skipped'] + $b['skipped'],
        ];
    }
}
