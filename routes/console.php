<?php

use App\Models\AuditLog;
use App\Support\ScheduleHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Skill-race standings.
 *
 * Every ten minutes, not every minute: the command paces itself at one Wise
 * Old Man request every three seconds, so a race with 40 entrants already
 * takes two minutes to get through. Overlapping runs would double the request
 * rate against a shared public API, which is what withoutOverlapping prevents.
 *
 * `--track` is deliberately absent. It POSTs to Wise Old Man to re-import a
 * player, which is a write against someone else's service; leaving it off the
 * schedule means an operator turns that on knowingly rather than inheriting
 * it. See SyncEventStandings and WiseOldManService.
 */
// Named explicitly rather than bare `model:prune`, which would sweep every
// prunable model in the app — including any added later that nobody meant to
// hand to this schedule.
Schedule::command('model:prune', ['--model' => [AuditLog::class]])
    ->daily()
    ->onOneServer();

Schedule::command('events:sync-standings')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    // Stamped on success so /admin/diagnostics can answer "is the scheduler
    // even running". Nothing else records this, and a missing cron entry is
    // the quietest failure in the app: standings stop moving, every page
    // still renders, nothing is logged. See ScheduleHeartbeat.
    ->onSuccess(fn () => ScheduleHeartbeat::record('events:sync-standings'));

/**
 * The time-based notifications.
 *
 * Every fifteen minutes rather than hourly: the windows this checks are an
 * hour wide ("starts in an hour", "an hour left"), and a sweep that only ran
 * on the hour would deliver a warning anywhere between sixty and one minutes
 * before the thing it warns about. Four passes narrows that to fifteen.
 *
 * Running four times inside one window is safe by construction, not by luck —
 * SweepPushNotifications claims each once-per-event fact with an atomic
 * Cache::add, and the recurring ones go through PushNotifier's per-user
 * throttle. See that command's docblock for why those are two mechanisms and
 * not one.
 */
Schedule::command('push:sweep')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->onSuccess(fn () => ScheduleHeartbeat::record('push:sweep'));
