<?php

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
Schedule::command('events:sync-standings')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
