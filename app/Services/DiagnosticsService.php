<?php

namespace App\Services;

use App\Models\EventStanding;
use App\Models\PushSubscription;
use App\Support\DiagnosticCheck;
use App\Support\ScheduleHeartbeat;
use Base64Url\Base64Url;
use Illuminate\Support\Carbon;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\ES256;
use Throwable;

/**
 * Everything in this app that can fail without saying so, asked out loud.
 *
 * The unifying theme is not "push" — it is **silence**. Every check here
 * covers something that reports success, renders normally, and delivers
 * nothing: a mailer that writes to a log file, a scheduler whose cron entry
 * was never created, a VAPID pair whose halves belong to different keys, an
 * SSR process serving an empty div. None of them raise an error anywhere.
 *
 * One service, two surfaces: the admin page renders it and `push:doctor`
 * prints its push half. That split is deliberate — a diagnostic that
 * disagrees with itself depending on where you read it is worse than no
 * diagnostic, and two implementations of these rules would drift within a
 * month.
 *
 * **Nothing here may return a secret.** The output is designed to be
 * screenshotted into a chat: keys are described (length, shape, whether the
 * halves match), never printed; endpoints are fingerprinted, never shown.
 */
class DiagnosticsService
{
    private const PUBLIC_KEY_LENGTH = 65;

    private const PRIVATE_KEY_LENGTH = 32;

    /**
     * Every group, in the order the page shows them.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            $this->group('push', 'i-lucide-bell', $this->pushChecks()),
            $this->group('schedule', 'i-lucide-clock', $this->scheduleChecks()),
            $this->group('mail', 'i-lucide-mail', $this->mailChecks()),
            $this->group('wom', 'i-lucide-trophy', $this->womChecks()),
            $this->group('rendering', 'i-lucide-file-code', $this->renderingChecks()),
        ];
    }

    /**
     * The push half on its own, for `push:doctor`.
     *
     * @return array<int, DiagnosticCheck>
     */
    public function pushChecks(): array
    {
        $subject = config('webpush.vapid.subject');
        $public = config('webpush.vapid.public_key');
        $private = config('webpush.vapid.private_key');

        if (blank($public) || blank($private)) {
            return [
                DiagnosticCheck::fail(
                    trans('diagnostics.push_keys'),
                    trans('diagnostics.push_keys_missing'),
                    trans('diagnostics.push_keys_fix'),
                ),
            ];
        }

        $checks = [];

        // A mailto: or https: URL and nothing else. Push services — Apple's
        // in particular — reject anything else outright, and the rejection
        // names no field, so a bare domain here costs an afternoon.
        $subjectOk = is_string($subject)
            && (str_starts_with($subject, 'mailto:') || str_starts_with($subject, 'https://'));

        // The placeholder from `webpush:vapid`'s own output, pasted through
        // unchanged. It passes every format check — it *is* a mailto: URL —
        // and push services use this address to reach an operator whose app
        // is misbehaving, so an address nobody reads is a warning that only
        // arrives as a block. Caught on staging, from a screenshot.
        $isPlaceholder = is_string($subject) && str_contains($subject, 'example.com');

        $checks[] = match (true) {
            ! $subjectOk => DiagnosticCheck::fail(
                trans('diagnostics.push_subject'),
                trans('diagnostics.push_subject_bad'),
                trans('diagnostics.push_subject_fix'),
            ),
            $isPlaceholder => DiagnosticCheck::warn(
                trans('diagnostics.push_subject'),
                trans('diagnostics.push_subject_placeholder', ['subject' => (string) $subject]),
                trans('diagnostics.push_subject_placeholder_fix'),
            ),
            default => DiagnosticCheck::ok(trans('diagnostics.push_subject'), (string) $subject),
        };

        $publicRaw = Base64Url::decode($public);
        $privateRaw = Base64Url::decode($private);

        // An uncompressed P-256 point: 0x04, then a 32-byte X and a 32-byte
        // Y. Anything else is a key pasted wrong, and the send library's own
        // validation only checks the length.
        $publicOk = strlen($publicRaw) === self::PUBLIC_KEY_LENGTH && ($publicRaw[0] ?? '') === "\x04";
        $privateOk = strlen($privateRaw) === self::PRIVATE_KEY_LENGTH;

        $checks[] = DiagnosticCheck::when(
            $publicOk,
            trans('diagnostics.push_public_key'),
            trans('diagnostics.push_public_key_ok'),
            trans('diagnostics.push_public_key_bad', ['bytes' => strlen($publicRaw)]),
            trans('diagnostics.push_key_fix'),
        );

        $checks[] = DiagnosticCheck::when(
            $privateOk,
            trans('diagnostics.push_private_key'),
            trans('diagnostics.push_private_key_ok'),
            trans('diagnostics.push_private_key_bad', ['bytes' => strlen($privateRaw)]),
            trans('diagnostics.push_key_fix'),
        );

        // The check nothing else makes. A mismatched pair passes every length
        // test, is accepted by the send library, produces a signed request the
        // push service accepts — and delivers nothing, forever, to every
        // device. It is completely invisible without asking directly.
        if ($publicOk && $privateOk) {
            $checks[] = DiagnosticCheck::when(
                $this->keysMatch($publicRaw, $privateRaw),
                trans('diagnostics.push_pair'),
                trans('diagnostics.push_pair_ok'),
                trans('diagnostics.push_pair_bad'),
                trans('diagnostics.push_pair_fix'),
            );
        }

        // Encrypting a payload needs a fresh ephemeral P-256 key per message
        // (RFC 8291), and generating one goes through OpenSSL — which on
        // Windows cannot find its config unless OPENSSL_CONF is set, failing
        // with a bare "Unable to create the local key". Caught against a real
        // send: the four checks above were green while every message failed.
        $checks[] = DiagnosticCheck::when(
            $this->canCreateEphemeralKey(),
            trans('diagnostics.push_encryption'),
            trans('diagnostics.push_encryption_ok'),
            trans('diagnostics.push_encryption_bad'),
            PHP_OS_FAMILY === 'Windows' && ! getenv('OPENSSL_CONF')
                ? trans('diagnostics.push_encryption_fix_windows')
                : trans('diagnostics.push_encryption_fix'),
        );

        $active = PushSubscription::query()->active()->count();
        $expired = PushSubscription::query()->whereNotNull('expired_at')->count();

        $checks[] = DiagnosticCheck::info(
            trans('diagnostics.push_devices'),
            trans('diagnostics.push_devices_detail', ['active' => $active, 'expired' => $expired]),
        );

        // Named rather than guessed at: after a key change these behave
        // exactly like healthy rows — the push service keeps accepting sends
        // to them — so nothing but this distinguishes a device that will
        // never receive anything again.
        $stale = PushSubscription::query()
            ->whereNotNull('vapid_key')
            ->where('vapid_key', '!=', $public)
            ->count();

        if ($stale > 0) {
            $checks[] = DiagnosticCheck::warn(
                trans('diagnostics.push_stale'),
                trans('diagnostics.push_stale_detail', ['count' => $stale]),
                trans('diagnostics.push_stale_fix'),
            );
        }

        return $checks;
    }

    /**
     * Is the cron entry that runs everything actually there?
     *
     * The quietest failure in the app. Without it standings never move, five
     * of the nine notification categories never fire, and audit rows grow
     * without limit — while every page renders perfectly and nothing is
     * logged. Measured from what completed, not from what was scheduled.
     *
     * @return array<int, DiagnosticCheck>
     */
    public function scheduleChecks(): array
    {
        return [
            $this->heartbeat('events:sync-standings', trans('diagnostics.schedule_standings'), 30),
            $this->heartbeat('push:sweep', trans('diagnostics.schedule_sweep'), 45),
        ];
    }

    /**
     * @param  int  $staleAfterMinutes  generous against the interval, so a
     *                                  single skipped run (a long sync, a
     *                                  reboot) does not read as an outage
     */
    private function heartbeat(string $command, string $label, int $staleAfterMinutes): DiagnosticCheck
    {
        $last = ScheduleHeartbeat::lastRun($command);

        if ($last === null) {
            return DiagnosticCheck::fail(
                $label,
                trans('diagnostics.schedule_never'),
                trans('diagnostics.schedule_fix'),
            );
        }

        if ($last->lt(Carbon::now()->subMinutes($staleAfterMinutes))) {
            return DiagnosticCheck::fail(
                $label,
                trans('diagnostics.schedule_stale', ['when' => $last->diffForHumans()]),
                trans('diagnostics.schedule_fix'),
            );
        }

        return DiagnosticCheck::ok($label, trans('diagnostics.schedule_ok', ['when' => $last->diffForHumans()]));
    }

    /** @return array<int, DiagnosticCheck> */
    public function mailChecks(): array
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');

        $checks = [];

        // `log` is the dangerous one precisely because it is not an error: it
        // writes the message to a file and tells the user their reset link is
        // on its way. The failure looks like success from every side.
        $checks[] = $mailer === 'log'
            ? DiagnosticCheck::warn(
                trans('diagnostics.mail_driver'),
                trans('diagnostics.mail_driver_log'),
                trans('diagnostics.mail_driver_fix'),
            )
            : DiagnosticCheck::ok(trans('diagnostics.mail_driver'), (string) $mailer);

        $checks[] = ($from === null || str_contains((string) $from, 'example.com'))
            ? DiagnosticCheck::warn(
                trans('diagnostics.mail_from'),
                trans('diagnostics.mail_from_default', ['address' => (string) $from]),
                trans('diagnostics.mail_from_fix'),
            )
            : DiagnosticCheck::ok(trans('diagnostics.mail_from'), (string) $from);

        // Queued mail on a host with no worker is the same silence in a
        // different place: EventStatusChanged is ShouldQueue, so on a
        // database queue with nothing consuming it the row simply sits there.
        if (config('queue.default') !== 'sync') {
            $checks[] = DiagnosticCheck::info(
                trans('diagnostics.mail_queue'),
                trans('diagnostics.mail_queue_detail', ['driver' => (string) config('queue.default')]),
            );
        }

        return $checks;
    }

    /** @return array<int, DiagnosticCheck> */
    public function womChecks(): array
    {
        $agent = config('services.wom.user_agent');

        $checks = [];

        // Their rate limit is theirs, and a user agent with no contact in it
        // is how an app gets blocked without being asked to stop first.
        $checks[] = str_contains((string) $agent, '@') || filter_var($agent, FILTER_VALIDATE_URL)
            ? DiagnosticCheck::ok(trans('diagnostics.wom_agent'), (string) $agent)
            : DiagnosticCheck::warn(
                trans('diagnostics.wom_agent'),
                trans('diagnostics.wom_agent_bad', ['agent' => (string) $agent]),
                trans('diagnostics.wom_agent_fix'),
            );

        $checks[] = DiagnosticCheck::info(
            trans('diagnostics.wom_rate'),
            filled(config('services.wom.api_key'))
                ? trans('diagnostics.wom_rate_keyed')
                : trans('diagnostics.wom_rate_anonymous'),
        );

        // Not a config question but the one that matters: rows this app
        // cannot measure are rows scoring zero in a live race.
        $broken = EventStanding::query()->whereNotNull('sync_error')->count();

        $checks[] = $broken === 0
            ? DiagnosticCheck::ok(trans('diagnostics.wom_standings'), trans('diagnostics.wom_standings_ok'))
            : DiagnosticCheck::warn(
                trans('diagnostics.wom_standings'),
                trans('diagnostics.wom_standings_bad', ['count' => $broken]),
                trans('diagnostics.wom_standings_fix'),
            );

        return $checks;
    }

    /** @return array<int, DiagnosticCheck> */
    public function renderingChecks(): array
    {
        $checks = [];

        $hot = public_path('hot');

        // The nastiest one in this group. Inertia checks for this file first
        // and, when it exists, posts to INERTIA_SSR_HOT_URL — which nothing
        // here sets — so the render fails and falls back to client rendering
        // *without a word*. `pnpm dev` writes it and deletes it on a clean
        // exit, so a killed dev server leaves it behind and every later page
        // ships an empty <div id="app">. The browser looks perfect.
        $checks[] = file_exists($hot)
            ? DiagnosticCheck::fail(
                trans('diagnostics.render_hot'),
                trans('diagnostics.render_hot_present'),
                trans('diagnostics.render_hot_fix'),
            )
            : DiagnosticCheck::ok(trans('diagnostics.render_hot'), trans('diagnostics.render_hot_absent'));

        $bundle = base_path('bootstrap/ssr/ssr.js');

        if (! file_exists($bundle)) {
            $checks[] = DiagnosticCheck::fail(
                trans('diagnostics.render_bundle'),
                trans('diagnostics.render_bundle_missing'),
                trans('diagnostics.render_bundle_fix'),
            );

            return $checks;
        }

        $built = Carbon::createFromTimestamp(filemtime($bundle));

        $checks[] = DiagnosticCheck::info(
            trans('diagnostics.render_bundle'),
            trans('diagnostics.render_bundle_built', ['when' => $built->diffForHumans()]),
        );

        // The process loads that file once at startup and never re-reads it,
        // so a deploy that rebuilds the bundle without restarting the process
        // keeps serving the previous build indefinitely — and the symptom is
        // a page that renders client-side with an empty server payload.
        // Comparing what the process is serving is not possible from here;
        // saying so is better than implying it was checked.
        $checks[] = DiagnosticCheck::info(
            trans('diagnostics.render_process'),
            trans('diagnostics.render_process_detail'),
        );

        return $checks;
    }

    /**
     * Does this private key actually belong to this public key?
     *
     * Asked by signing with the pair and verifying against the public half. A
     * mismatch either throws while assembling the key (OpenSSL checks
     * consistency on load) or produces a signature that does not verify —
     * both are answers, and both mean the same thing.
     */
    private function keysMatch(string $publicRaw, string $privateRaw): bool
    {
        try {
            $jwk = new JWK([
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => Base64Url::encode(substr($publicRaw, 1, 32)),
                'y' => Base64Url::encode(substr($publicRaw, 33, 32)),
                'd' => Base64Url::encode($privateRaw),
            ]);

            $algorithm = new ES256;
            $signature = $algorithm->sign($jwk, 'diagnostics');

            return $algorithm->verify($jwk->toPublic(), 'diagnostics', $signature);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Can this PHP make the ephemeral key every encrypted push needs?
     *
     * Asked by doing it. There is no flag to read — the failure is inside
     * OpenSSL's own key generation, and the only reliable test is the
     * operation itself.
     */
    private function canCreateEphemeralKey(): bool
    {
        try {
            $key = openssl_pkey_new([
                'curve_name' => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
            ]);

            // Drain OpenSSL's error queue either way. Left behind, these
            // surface later attached to some unrelated call and send the next
            // person debugging in entirely the wrong direction.
            while (openssl_error_string() !== false) {
                // discarded on purpose
            }

            return $key !== false;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * A group's own status is its worst check.
     *
     * `info` never counts: a group of three facts and no verdicts is not
     * "passing", and colouring it green would claim a check that never ran.
     *
     * @param  array<int, DiagnosticCheck>  $checks
     * @return array<string, mixed>
     */
    private function group(string $key, string $icon, array $checks): array
    {
        $statuses = array_map(fn (DiagnosticCheck $check) => $check->status, $checks);

        $status = match (true) {
            in_array(DiagnosticCheck::FAIL, $statuses, true) => DiagnosticCheck::FAIL,
            in_array(DiagnosticCheck::WARN, $statuses, true) => DiagnosticCheck::WARN,
            in_array(DiagnosticCheck::OK, $statuses, true) => DiagnosticCheck::OK,
            default => DiagnosticCheck::INFO,
        };

        return [
            'key' => $key,
            'icon' => $icon,
            'label' => trans("diagnostics.group_{$key}"),
            'description' => trans("diagnostics.group_{$key}_desc"),
            'status' => $status,
            'checks' => array_map(fn (DiagnosticCheck $check) => $check->toArray(), $checks),
        ];
    }
}
