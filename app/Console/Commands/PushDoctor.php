<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Services\DiagnosticsService;
use App\Support\DiagnosticCheck;
use Illuminate\Console\Command;

/**
 * The push half of /admin/diagnostics, on a terminal.
 *
 * **It runs the same checks, not its own.** DiagnosticsService owns every
 * rule; this only renders. Two implementations of "is this key pair valid"
 * would disagree within a month, and a diagnostic whose answer depends on
 * where you read it is worse than no diagnostic at all.
 *
 * The admin page is the better surface for almost every case — the moments
 * you need this are a deploy that went quiet and a phone that stopped
 * buzzing, and neither is a good time to be hunting for an SSH session. This
 * stays for the case the page cannot cover: a deploy that is broken enough
 * that the page itself will not render, and CI.
 */
class PushDoctor extends Command
{
    protected $signature = 'push:doctor';

    protected $description = 'Check the Web Push configuration and list registered devices';

    public function handle(DiagnosticsService $diagnostics): int
    {
        $checks = $diagnostics->pushChecks();

        $this->line('');
        $this->info('Push notifications');

        $failed = false;

        foreach ($checks as $check) {
            $this->render($check);
            $failed = $failed || $check->status === DiagnosticCheck::FAIL;
        }

        $this->line('');
        $this->comment('Same checks, with buttons to test them: /admin/diagnostics');

        $this->devices();

        // A non-zero exit so this is usable as a deploy gate. The page cannot
        // do that, which is the other reason the command stays.
        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Every device, across every user — the part the admin page deliberately
     * does not show.
     *
     * The page lists only the admin's own, because a screen that dumps every
     * user's devices is a privacy decision nobody asked for. On a terminal
     * you are already the operator answering "whose phone stopped working",
     * and that question needs the names.
     */
    private function devices(): void
    {
        $this->line('');
        $this->info('Devices');

        $subscriptions = PushSubscription::with('user')->orderBy('created_at')->get();

        if ($subscriptions->isEmpty()) {
            $this->line('  none registered yet');
            $this->line('');

            return;
        }

        $current = config('webpush.vapid.public_key');

        $this->table(
            ['user', 'device', 'push service', 'key', 'state', 'last used'],
            $subscriptions->map(fn (PushSubscription $row) => [
                $row->user?->displayName() ?? '(deleted)',
                $row->deviceLabel(),
                parse_url($row->endpoint, PHP_URL_HOST) ?: '?',
                match (true) {
                    $row->vapid_key === null => 'unknown',
                    $row->vapid_key === $current => 'current',
                    default => 'STALE',
                },
                $row->expired_at !== null ? 'expired' : 'active',
                $row->last_used_at?->diffForHumans() ?? 'never',
            ])->all(),
        );

        $this->line('');
    }

    private function render(DiagnosticCheck $check): void
    {
        $mark = match ($check->status) {
            DiagnosticCheck::OK => '<info>ok</info>  ',
            DiagnosticCheck::FAIL => '<error>FAIL</error>',
            DiagnosticCheck::WARN => '<comment>warn</comment>',
            default => '    ',
        };

        $this->line(sprintf('  %s  %-20s %s', $mark, $check->label, $check->detail));

        if ($check->fix !== null) {
            $this->line(sprintf('  %s  %-20s <comment>%s</comment>', '    ', '', $check->fix));
        }
    }
}
