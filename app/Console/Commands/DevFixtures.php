<?php

namespace App\Console\Commands;

use App\Models\BingoCard;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PlayerBoard;
use App\Models\Role;
use App\Models\Tile;
use App\Models\User;
use App\Services\BingoService;
use Database\Seeders\ClaudeDemoUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

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

    /** Named twice — once to seed it, once to notice it is already there. */
    private const PHOTO_FINISH = 'Photo finish';

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

        $this->photoFinish($host);

        return self::SUCCESS;
    }

    /**
     * The one state the end-of-event rules can only be walked in: two
     * competitors whose finishing claims are both sitting in the queue, and
     * the winning one submitted first — so it is not the one a host reading
     * the queue top-down clicks first.
     *
     * Everything those rules decide hangs on that gap: the podium ordering by
     * submission rather than approval, a place staying provisional while
     * somebody earlier is still unreviewed, the STOP close waiting for the
     * queue to clear before it names a winner. None of it can be produced by
     * playing the board forwards in a browser, because a claim submitted
     * there is always submitted now.
     *
     * Two accounts rather than one re-roled: a photo finish needs two people
     * in it, and this is not a permission question. Their password is
     * committed for the same reason ClaudeDemoUserSeeder's is — local
     * install, no real data.
     */
    private function photoFinish(User $host): void
    {
        $existing = Event::withTrashed()->where('title', self::PHOTO_FINISH)->first();

        if ($existing !== null) {
            $this->line('  exists: '.self::PHOTO_FINISH);
            $this->line("    /events/{$existing->id}");

            return;
        }

        $event = Event::create([
            'title' => self::PHOTO_FINISH,
            'type' => 'SNAKES_LADDERS',
            'description' => 'Seeded by dev:fixtures: two finishing claims in the queue, the winner submitted first.',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => Carbon::now()->subDays(3),
            'end_date' => Carbon::now()->addDays(6),
            // The half that makes the ordering matter: under STOP the first
            // finish ends it for everybody, so approving out of order gives
            // somebody else's win away and cannot be taken back.
            'finish_rule' => 'STOP',
        ]);

        // The owner is a seeded account with a password rather than --host,
        // because this scenario has to be walked from the host's seat and the
        // usual local admin logs in through Discord. --host stays on as a
        // co-host, which is also the seat worth checking: reviewing claims is
        // day-to-day running, not destroying somebody's work.
        $owner = $this->account('dev_host', 'Event Host');

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => false]);

        $board = Board::create([
            'event_id' => $event->id,
            'size' => 'SIZE_5X5',
            // Without review there is no queue, and without a queue there is
            // no order to get wrong.
            'requires_approval' => true,
        ]);

        $tiles = collect(range(0, 24))->mapWithKeys(fn (int $position) => [
            $position => Tile::create([
                'board_id' => $board->id,
                'position' => $position,
                'type' => 'NORMAL',
                'title_override' => 'Tile '.($position + 1),
            ]),
        ]);

        // Minutes apart, which is the realistic gap: two people come back
        // from the same trip and both send a screenshot.
        $this->racer($event, $board, $tiles, 'dev_racer_one', 'Racer One', Carbon::now()->subMinutes(12));
        $this->racer($event, $board, $tiles, 'dev_racer_two', 'Racer Two', Carbon::now()->subMinutes(10));

        $this->info('  seeded: '.self::PHOTO_FINISH);
        $this->line("    /events/{$event->id}");
        $this->line('    seats: dev_host@example.test, dev_racer_one@example.test, dev_racer_two@example.test');
    }

    /**
     * A local account that can actually log in, made once and reused.
     *
     * Password committed for the same reason ClaudeDemoUserSeeder's is: local
     * install, no real data, and a walkthrough that needs three seats cannot
     * be run at all without one.
     */
    private function account(string $username, string $name): User
    {
        $user = User::firstOrNew(['email' => $username.'@example.test']);

        if (! $user->exists) {
            $user->password = Hash::make(ClaudeDemoUserSeeder::PASSWORD);
        }

        $user->forceFill([
            'discord_username' => $username,
            'nickname' => $name,
            'osrs_username' => $name,
            'onboarding_completed_at' => now(),
        ])->save();

        $user->assignRole(Role::findOrCreate('PLAYER', 'web'));

        return $user;
    }

    /**
     * One competitor, parked one approval away from home.
     *
     * Everything before the last tile is already signed off, so the only
     * thing in the host's queue is the claim that decides the event.
     *
     * @param  Collection<int, Tile>  $tiles
     */
    private function racer(Event $event, Board $board, Collection $tiles, string $username, string $name, Carbon $submittedAt): void
    {
        $user = $this->account($username, $name);

        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id]);

        $playerBoard = PlayerBoard::create([
            'user_id' => $user->id,
            'board_id' => $board->id,
            'current_position' => 24,
        ]);

        foreach (range(0, 23) as $position) {
            CompletedTile::create([
                'player_board_id' => $playerBoard->id,
                'tile_id' => $tiles[$position]->id,
                'status' => 'APPROVED',
                'completed_at' => $submittedAt->copy()->subHours(24 - $position),
                'completed_via' => 'MANUAL',
                'marked_by' => $user->id,
                'reviewed_by' => $event->authors()->first()?->user_id,
                'reviewed_at' => $submittedAt->copy()->subHours(24 - $position),
            ]);
        }

        CompletedTile::create([
            'player_board_id' => $playerBoard->id,
            'tile_id' => $tiles[24]->id,
            'status' => 'PENDING',
            // The whole point: the timestamp the podium sorts on is when it
            // was sent, not when a host gets round to it.
            'completed_at' => $submittedAt,
            'completed_via' => 'MANUAL',
            'marked_by' => $user->id,
        ]);
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
