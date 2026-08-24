<?php

namespace App\Console\Commands;

use App\Models\BingoCard;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Tile;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * The events a walkthrough needs and a normal seed does not produce.
 *
 * Every layout bug found so far lived at an edge: a title long enough to wrap
 * four times, an event that has ended, one that has not started, one on hold.
 * Demo data is all comfortably in the middle, so a sweep over it says
 * everything is fine — which it was, at 375px, on a short title.
 *
 * Idempotent by title, so it can be run before every pass without piling up
 * copies. Local only.
 */
class DevFixtures extends Command
{
    protected $signature = 'dev:fixtures {--host=admin : discord_username to own the seeded events}';

    protected $description = 'Seed the edge-case events a multi-user walkthrough needs (local only)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('dev:fixtures only runs locally.');

            return self::FAILURE;
        }

        $host = User::where('discord_username', $this->option('host'))->first();

        if ($host === null) {
            $this->error("No account with discord_username \"{$this->option('host')}\".");

            return self::FAILURE;
        }

        foreach ($this->specs() as $spec) {
            $existing = Event::withTrashed()->where('title', $spec['title'])->first();

            if ($existing !== null) {
                $this->line("  exists: {$spec['title']}");

                continue;
            }

            $event = Event::create([
                'title' => $spec['title'],
                'type' => $spec['type'],
                'metric' => $spec['metric'] ?? null,
                'description' => 'Seeded by dev:fixtures to test an edge of the layout.',
                'mode' => $spec['mode'] ?? 'SOLO',
                'access_mode' => $spec['access'] ?? 'OPEN',
                'is_listed' => true,
                'start_date' => Carbon::now()->addDays($spec['starts']),
                'end_date' => Carbon::now()->addDays($spec['ends']),
            ]);

            if ($spec['paused'] ?? false) {
                $event->forceFill([
                    'paused_at' => Carbon::now(),
                    'pause_reason' => 'Seeded paused, to test the banner.',
                ])->save();
            }

            BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

            $this->payloadFor($event);

            $this->info("  seeded: {$spec['title']}");
            $this->line("    /events/{$event->id}");
        }

        return self::SUCCESS;
    }

    /**
     * One event per edge, and nothing that merely repeats an edge already
     * covered — a sweep people actually run is a short one.
     *
     * @return array<int, array<string, mixed>>
     */
    private function specs(): array
    {
        return [
            // Four lines of title on a phone, two on a desktop. This is the
            // one that found the 390px overflow at tablet width.
            ['title' => 'The Grand Midsummer Clan Championship of Old School RuneScape — Season Four', 'type' => 'BINGO', 'starts' => -2, 'ends' => 10],
            ['title' => 'Ended last week', 'type' => 'BINGO', 'starts' => -20, 'ends' => -3],
            ['title' => 'Starts next month', 'type' => 'SNAKES_LADDERS', 'starts' => 20, 'ends' => 40],
            ['title' => 'On hold', 'type' => 'SNAKES_LADDERS', 'starts' => -1, 'ends' => 6, 'paused' => true],
            ['title' => 'Zulrah sprint', 'type' => 'DROP_RACE', 'metric' => 'zulrah', 'starts' => -1, 'ends' => 6],
            ['title' => 'Invite only night', 'type' => 'BINGO', 'starts' => -1, 'ends' => 6, 'access' => 'INVITE'],
            ['title' => 'Teams of four', 'type' => 'SNAKES_LADDERS', 'starts' => -1, 'ends' => 6, 'mode' => 'TEAM'],
        ];
    }

    /** A board needs a grid and a card needs squares, or the page is empty. */
    private function payloadFor(Event $event): void
    {
        if ($event->type === 'SNAKES_LADDERS') {
            $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);

            foreach (range(0, 24) as $position) {
                Tile::create(['board_id' => $board->id, 'position' => $position, 'type' => 'NORMAL']);
            }
        }

        if ($event->type === 'BINGO') {
            app(BingoService::class)->ensureSquares(BingoCard::create(['event_id' => $event->id, 'size' => 5]));
        }
    }
}
