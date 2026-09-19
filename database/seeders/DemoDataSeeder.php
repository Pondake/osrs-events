<?php

namespace Database\Seeders;

use App\Models\BingoCard;
use App\Models\BingoSquare;
use App\Models\Board;
use App\Models\Event;
use App\Models\BoardAccess;
use App\Models\BoardAuthor;
use App\Models\BoardInvite;
use App\Models\BoardTeam;
use App\Models\CompletedTile;
use App\Models\EventParticipant;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Tile;
use App\Models\User;
use App\Models\UserGuild;
use App\Services\EventStandingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Bulk demo data on top of DatabaseSeeder's single "Winter Clan Grind"
 * board — enough boards/teams/players to actually see size, mode,
 * access_mode, and progress-state variety in the UI instead of one empty
 * example. Idempotent per board title so re-running `db:seed` doesn't
 * duplicate boards, but does top up players/teams if the pool grew.
 */
class DemoDataSeeder extends Seeder
{
    private const TILE_COUNTS = [
        'SIZE_5X5' => 25,
        'SIZE_7X7' => 49,
        'SIZE_9X9' => 81,
    ];

    /** @var Collection<int, User> */
    private Collection $users;

    /** @var Collection<int, Team> */
    private Collection $teams;

    /** @var Collection<int, Task> */
    private Collection $tasks;

    public function run(): void
    {
        $this->renameLegacyTitles();

        $this->users = $this->seedUsers();
        $this->tasks = $this->seedTasks();
        $this->teams = $this->seedTeams();

        foreach ($this->boardSpecs() as $spec) {
            $this->seedBoard($spec);
        }

        // Site-wide, same reasoning as the bingo backfill below: a board
        // outside boardSpecs()' own nine titles — "Starts next month" was
        // created by hand through the app, the same way the pre-existing
        // bingo cards were — never got touched by the per-board version
        // this used to be, so it sat with every tile empty through two
        // separate task-filling passes. Reported directly from exactly
        // that board.
        $this->backfillAllTileTasks();

        $this->seedSkillRace();

        // No BINGO event existed anywhere in this seeder — DatabaseSeeder's
        // own board is Snakes & Ladders, and boardSpecs() only ever produces
        // that type too. Every bingo event in a fresh clone's dev database
        // up to now was created by hand through the app itself, which is
        // also why none of them had a single task on a square: nothing
        // filled one in until a host did.
        foreach ($this->bingoSpecs() as $spec) {
            $this->seedBingoCard($spec);
        }

        // Site-wide, not scoped to the specs above on purpose — it also
        // catches every ad-hoc bingo card already sitting in this database
        // from manual testing, which is most of what prompted this pass:
        // "every seeded S&L and bingo board" meant filling what's already
        // here too, not just what this seeder creates going forward.
        $this->backfillBingoSquareTasks();

        $this->command->info('Demo data seeded: '.$this->users->count().' users, '.$this->teams->count().' teams, '.count($this->boardSpecs()).' boards, '.count($this->bingoSpecs()).' bingo cards.');
    }

    /** @return Collection<int, User> */
    private function seedUsers(): Collection
    {
        $existing = User::where('discord_id', 'like', 'demo-%')->get();
        if ($existing->count() >= 90) {
            return $existing;
        }

        // Numbered off the highest EXISTING suffix, not off the row count —
        // deleting any demo user (the admin UI can now do that) makes those
        // two diverge, and counting would then re-generate a discord_id that
        // still exists and blow up on the unique index.
        $highest = $existing
            ->map(fn (User $u) => (int) substr($u->discord_id, strlen('demo-')))
            ->max() ?? 0;

        $needed = 90 - $existing->count();
        $fresh = User::factory()
            ->count($needed)
            ->sequence(fn ($sequence) => ['discord_id' => 'demo-'.str_pad((string) ($highest + $sequence->index + 1), 12, '0', STR_PAD_LEFT)])
            ->create();

        // ~1 in 3 demo users has a nickname override, to show
        // User::displayName()'s fallback in the UI (avatar list, leaderboard).
        $fresh->each(function (User $user, int $i) {
            if ($i % 3 === 0) {
                $user->update(['nickname' => fake()->firstName()]);
            }
        });

        return $existing->concat($fresh);
    }

    /** @return Collection<int, Task> */
    private function seedTasks(): Collection
    {
        return (new TaskSeeder)->sync();
    }

    /** @return Collection<int, Team> */
    private function seedTeams(): Collection
    {
        // Member counts deliberately span the full 2-20 range so team size
        // itself is visible as a variable, not just team count.
        $specs = [
            ['name' => 'Iron Foundry', 'members' => 2, 'guild' => true],
            ['name' => 'Woox Walkers', 'members' => 3, 'guild' => false],
            ['name' => 'Zerker Pkers', 'members' => 5, 'guild' => true],
            ['name' => 'Quest Cape Club', 'members' => 6, 'guild' => false],
            ['name' => 'Barrows Brothers', 'members' => 8, 'guild' => true],
            ['name' => 'Slayer Task Force', 'members' => 12, 'guild' => false],
            ['name' => 'GIM Grinders', 'members' => 16, 'guild' => true],
            ['name' => 'The Whole Clan', 'members' => 20, 'guild' => true],
        ];

        return collect($specs)->map(function ($spec) {
            $team = Team::firstOrCreate(
                ['name' => $spec['name']],
                [
                    'id' => (string) str()->uuid(),
                    'guild_id' => $spec['guild'] ? fake()->numerify('##################') : null,
                    'guild_name' => $spec['guild'] ? $spec['name'].' Discord' : null,
                ],
            );

            if ($team->members()->count() === 0) {
                $this->users->random($spec['members'])->each(fn (User $user) => TeamMember::create([
                    'id' => (string) str()->uuid(),
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                ]));
            }

            return $team;
        });
    }

    private function boardSpecs(): array
    {
        // 3 boards per size, mode/access_mode/dates/dice_roll_limit varied
        // across the set so every combination shows up somewhere.
        return [
            [
                'title' => 'Weekend Warmup', 'size' => 'SIZE_5X5', 'mode' => 'SOLO', 'access_mode' => 'OPEN',
                'description' => 'A quick 5x5 sprint for the weekend — anyone can hop in.',
                'players' => 5, 'start_date' => now()->subDays(2), 'end_date' => now()->addDays(3), 'dice_roll_limit' => 3,
            ],
            [
                'title' => 'Guild Skirmish', 'size' => 'SIZE_5X5', 'mode' => 'TEAM', 'access_mode' => 'GUILD',
                'description' => 'Small-scale team event, restricted to our Discord server members.',
                'teams' => 2, 'start_date' => now()->subDay(), 'end_date' => now()->addDays(6), 'dice_roll_limit' => 1,
            ],
            [
                'title' => 'VIP Beta Test', 'size' => 'SIZE_5X5', 'mode' => 'SOLO', 'access_mode' => 'INVITE',
                'description' => 'Invite-only board for testing new tile types before a public launch.',
                'players' => 2, 'start_date' => null, 'end_date' => null, 'dice_roll_limit' => null,
                // Deliberately spans every state the admin invites overview
                // distinguishes — active, unused, exhausted and expired — so
                // the page has something to show for each without hand-made
                // rows. `uses` overrides the random count where the state
                // depends on it.
                'coauthor' => true, 'invites' => [
                    ['max_uses' => 5, 'expires' => now()->addWeek()],
                    ['max_uses' => null, 'expires' => null],
                    ['max_uses' => 3, 'expires' => null, 'uses' => 3, 'label' => 'Exhausted — first wave'],
                    ['max_uses' => null, 'expires' => now()->subDays(3), 'uses' => 2, 'label' => 'Expired — beta signup'],
                    ['max_uses' => 20, 'expires' => now()->addMonth(), 'uses' => 0, 'label' => 'Unused — spare link'],
                ],
            ],
            [
                'title' => 'Clan Wars: Spring', 'size' => 'SIZE_7X7', 'mode' => 'TEAM', 'access_mode' => 'OPEN',
                'description' => 'The big spring team competition — open to every clan team.',
                'teams' => 3, 'start_date' => now()->subWeeks(2), 'end_date' => now()->addWeek(), 'dice_roll_limit' => 3,
            ],
            [
                'title' => 'Elite Squad Only', 'size' => 'SIZE_7X7', 'mode' => 'TEAM', 'access_mode' => 'INVITE',
                'description' => 'Small invite-only team board for the top-tier squads.',
                'teams' => 2, 'start_date' => now()->subDays(4), 'end_date' => now()->addDays(10), 'dice_roll_limit' => 2,
                'coauthor' => true, 'invites' => [['max_uses' => 10, 'expires' => null]],
            ],
            [
                // Renamed: "Skill of the Month" is now the name of an actual
                // event type (SKILL_RACE, seeded separately below), and a
                // Snakes & Ladders board wearing that title reads as one.
                'title' => 'Autumn Sprint', 'size' => 'SIZE_7X7', 'mode' => 'SOLO', 'access_mode' => 'OPEN',
                'description' => 'Finished last month — kept around to show a completed board state.',
                'players' => 14, 'start_date' => now()->subMonth(), 'end_date' => now()->subDay(), 'dice_roll_limit' => 1,
            ],
            [
                'title' => 'Marathon Grind', 'size' => 'SIZE_9X9', 'mode' => 'SOLO', 'access_mode' => 'GUILD',
                'description' => 'Long-haul 9x9 board for guild members only — no rush.',
                'players' => 9, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(2), 'dice_roll_limit' => null,
            ],
            [
                'title' => 'Mega Clan Championship', 'size' => 'SIZE_9X9', 'mode' => 'TEAM', 'access_mode' => 'OPEN',
                'description' => 'Full-scale clan championship — every team, every size, all at once.',
                'teams' => 4, 'start_date' => now()->subDays(10), 'end_date' => now()->addDays(20), 'dice_roll_limit' => 5,
            ],
            [
                'title' => 'Endless Skilling', 'size' => 'SIZE_9X9', 'mode' => 'SOLO', 'access_mode' => 'OPEN',
                'description' => 'No start date, no end date, no roll limit — a permanent sandbox board.',
                'players' => 20, 'start_date' => null, 'end_date' => null, 'dice_roll_limit' => null,
            ],
        ];
    }

    /**
     * Carry old demo rows over to their current titles.
     *
     * Everything below is idempotent **by title**, which makes renaming one a
     * trap: changing the title in boardSpecs() creates a second row and
     * silently leaves the first behind, under the name that was being retired.
     * That is exactly what happened — a database ended up with both "Autumn
     * Sprint" and the "Skill of the Month" board it replaced, the latter still
     * showing a 7x7 grid next to the real skill race of nearly the same name.
     *
     * So a rename has to be migrated, not just declared. Add a pair here
     * whenever a demo title changes.
     */
    private function renameLegacyTitles(): void
    {
        // Old title => current title. "Skill of the Month" became the name of
        // an actual event type (SKILL_RACE), so the Snakes & Ladders board
        // wearing it had to give the name up.
        $renames = ['Skill of the Month' => 'Autumn Sprint'];

        foreach ($renames as $old => $current) {
            $legacy = Event::where('title', $old)->where('type', 'SNAKES_LADDERS')->first();

            if ($legacy === null) {
                continue;
            }

            // If the new title is already taken, an earlier run created it
            // fresh and this row is the duplicate — the rename can't happen
            // twice. Dropping the older one keeps the demo set the size it
            // claims to be; its board, tiles and player rows go with it via
            // the cascading foreign keys.
            if (Event::where('title', $current)->exists()) {
                $this->command->warn("Removing duplicate demo event \"{$old}\" (superseded by \"{$current}\").");
                $legacy->delete();

                continue;
            }

            $legacy->update(['title' => $current]);
            $this->command->info("Renamed demo event \"{$old}\" to \"{$current}\".");
        }
    }

    /**
     * A live skill race, so the second event type has demo data too.
     *
     * No board: a SKILL_RACE has nothing to play on, which is the whole point
     * of the event/board split.
     *
     * The entrants are real OSRS accounts on purpose. Their hiscores are
     * public and Wise Old Man already publishes exactly these numbers, and it
     * means `php artisan events:sync-standings` turns this into a leaderboard
     * with real XP in it rather than a table of zeroes. The deliberately
     * impossible name is there to demo the third state — someone the hiscores
     * have no record of, shown as "not tracked" and left unranked.
     */
    private function seedSkillRace(): void
    {
        $entrants = ['B0aty', 'Lynx Titan', 'Zezima', 'Not A Player'];

        $event = Event::firstOrCreate(
            ['title' => 'Skill of the Month — Mining'],
            [
                'id' => (string) str()->uuid(),
                'type' => 'SKILL_RACE',
                'metric' => 'mining',
                'description' => 'One month, one skill. Most Mining XP gained wins.',
                'mode' => 'SOLO',
                'access_mode' => 'OPEN',
                'is_listed' => true,
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
            ],
        );

        $owner = User::where('discord_id', 'local-admin-seed')->first() ?? $this->users->random();

        if (! BoardAuthor::where('event_id', $event->id)->exists()) {
            BoardAuthor::create([
                'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true,
            ]);
        }

        $standings = app(EventStandingsService::class);

        // The owner enters under their own name; the rest borrow a demo
        // account each, since a standing belongs to a user.
        $standings->enter($event, $owner);
        // See seedPlayerBoard()'s note on the same call — EventStandingsService::
        // enter() alone doesn't write one either; only the real join flow does.
        EventParticipant::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $owner->id],
            ['id' => (string) str()->uuid()],
        );

        // One entry per RSN per race is enforced now, so re-running this has
        // to check what is already in the race rather than reassigning names
        // and hitting the constraint. Candidates are demo users who aren't in
        // it yet — reusing one who is would just move their name.
        $candidates = $this->users
            ->reject(fn (User $user) => $user->id === $owner->id)
            ->reject(fn (User $user) => EventStanding::where([
                'event_id' => $event->id, 'user_id' => $user->id,
            ])->exists())
            ->values();

        foreach ($entrants as $name) {
            $taken = EventStanding::where(['event_id' => $event->id, 'username' => $name])->exists();

            if ($taken || $candidates->isEmpty()) {
                continue;
            }

            $user = $candidates->shift();
            $user->update(['osrs_username' => $name]);
            $standings->enter($event, $user);
            EventParticipant::firstOrCreate(
                ['event_id' => $event->id, 'user_id' => $user->id],
                ['id' => (string) str()->uuid()],
            );
        }

        $this->command->info("Seeded skill race {$event->id} — run `php artisan events:sync-standings` to fill it in.");
    }

    private function bingoSpecs(): array
    {
        // Three, not nine like boardSpecs() — bingo's own state variety
        // (solo/team, open/invite, running/ended) is already covered by
        // whatever's sitting in this database from manual testing, which
        // backfillBingoSquareTasks() fixes up regardless. These exist so a
        // completely fresh clone has at least one of each shape to look at,
        // not to re-enumerate every combination a second time.
        return [
            [
                'title' => 'Community Bingo Board', 'size' => 5, 'winCondition' => 'LINE', 'mode' => 'SOLO', 'access_mode' => 'OPEN',
                'description' => 'A quick 5x5 card open to the whole clan — first line wins.',
                'players' => 6, 'start_date' => now()->subDays(3), 'end_date' => now()->addDays(4), 'requires_approval' => true,
            ],
            [
                'title' => 'Clan Championship Bingo', 'size' => 7, 'winCondition' => 'FULL_HOUSE', 'mode' => 'TEAM', 'access_mode' => 'OPEN',
                'description' => 'Full-house team bingo — every square counts before anyone wins.',
                'teams' => 3, 'start_date' => now()->subWeek(), 'end_date' => now()->addWeek(), 'requires_approval' => true,
            ],
            [
                'title' => 'VIP Bingo Night', 'size' => 5, 'winCondition' => 'LINE', 'mode' => 'SOLO', 'access_mode' => 'INVITE',
                'description' => 'Invite-only bingo card for testing new squares before a public launch.',
                'players' => 3, 'start_date' => null, 'end_date' => null, 'requires_approval' => false,
                'coauthor' => true, 'invites' => [['max_uses' => 5, 'expires' => null]],
            ],
        ];
    }

    private function seedBingoCard(array $spec): void
    {
        $isNewEvent = Event::where('title', $spec['title'])->doesntExist();
        $owner = $this->users->random();

        $event = Event::firstOrCreate(
            ['title' => $spec['title']],
            [
                'id' => (string) str()->uuid(),
                'type' => 'BINGO',
                'description' => $spec['description'],
                'mode' => $spec['mode'],
                'access_mode' => $spec['access_mode'],
                'is_listed' => true,
                'start_date' => $spec['start_date'],
                'end_date' => $spec['end_date'],
            ],
        );

        $card = BingoCard::firstOrCreate(
            ['event_id' => $event->id],
            [
                'id' => (string) str()->uuid(),
                'size' => $spec['size'],
                'win_condition' => $spec['winCondition'],
                'line_bonus' => 0,
                'requires_approval' => $spec['requires_approval'],
            ],
        );

        if ($isNewEvent) {
            BoardAuthor::create([
                'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true,
            ]);

            if ($spec['coauthor'] ?? false) {
                $coauthor = $this->users->where('id', '!=', $owner->id)->random();
                BoardAuthor::create([
                    'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $coauthor->id, 'is_owner' => false,
                ]);
            }
        }

        if ($event->access_mode === 'INVITE') {
            $this->seedInvites($event, $owner, $spec['invites'] ?? []);
        }

        // Repair-on-run, same reasoning as seedBoard()'s tile check: a card
        // row can exist with no squares yet (BingoCard::firstOrCreate isn't
        // wrapped in the same transaction as the insert below).
        if ($card->squares()->count() === 0) {
            $this->seedBingoSquares($card);
        }

        $this->seedBingoPlayers($event, $spec);
    }

    private function seedBingoSquares(BingoCard $card): void
    {
        $total = $card->squareCount();

        // One wildcard is enough at these sizes — more than that and
        // "every square asks for something" stops being true of the card.
        $wildcardPosition = random_int(0, $total - 1);

        // Shuffled and cycled rather than drawn at random per square, same
        // reasoning as seedTiles(): a random draw with replacement clusters
        // the same task on the same card far more than it should.
        $shuffledTasks = $this->tasks->shuffle()->values();

        $squares = collect(range(0, $total - 1))->map(fn (int $position) => [
            'id' => (string) str()->uuid(),
            'bingo_card_id' => $card->id,
            'position' => $position,
            'task_id' => $position === $wildcardPosition ? null : $shuffledTasks[$position % $shuffledTasks->count()]->id,
            'title_override' => null,
            'points' => 1,
            'is_wildcard' => $position === $wildcardPosition,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        BingoSquare::insert($squares->all());
    }

    /**
     * Fills in the task on any bingo square that doesn't have one and isn't
     * a wildcard — every card that already existed in this database before
     * this pass, seeded by this class or clicked together by hand, plus
     * anything the specs above left behind on an earlier partial run.
     * Site-wide by design; see the call site for why.
     */
    private function backfillBingoSquareTasks(): void
    {
        $empty = BingoSquare::whereNull('task_id')->where('is_wildcard', false)->get();

        if ($empty->isEmpty()) {
            return;
        }

        $shuffledTasks = $this->tasks->shuffle()->values();

        $empty->each(function (BingoSquare $square, int $i) use ($shuffledTasks) {
            $square->update(['task_id' => $shuffledTasks[$i % $shuffledTasks->count()]->id]);
        });

        $this->command->info("Backfilled tasks onto {$empty->count()} empty bingo squares.");
    }

    /**
     * Takes the event alone, not the card — a bingo card has no per-player
     * row the way a board's PlayerBoard is one; taking part is the
     * EventParticipant row and nothing else.
     */
    private function seedBingoPlayers(Event $event, array $spec): void
    {
        if ($event->mode === 'TEAM') {
            $existingTeams = $event->eventTeams()->with('team.members')->get()->pluck('team');
            $teams = $existingTeams->isNotEmpty()
                ? $existingTeams
                : $this->teams->random(min($spec['teams'], $this->teams->count()));

            foreach ($teams as $team) {
                if (! BoardTeam::where(['event_id' => $event->id, 'team_id' => $team->id])->exists()) {
                    BoardTeam::create(['id' => (string) str()->uuid(), 'event_id' => $event->id, 'team_id' => $team->id]);
                }

                foreach ($team->members as $member) {
                    $this->grantEventAccess($event, $member->user);
                    EventParticipant::firstOrCreate(
                        ['event_id' => $event->id, 'user_id' => $member->user_id],
                        ['id' => (string) str()->uuid()],
                    );
                }
            }

            return;
        }

        $existingParticipantIds = EventParticipant::where('event_id', $event->id)->pluck('user_id');
        $players = $existingParticipantIds->isNotEmpty()
            ? $this->users->whereIn('id', $existingParticipantIds)
            : $this->users->random(min($spec['players'], $this->users->count()));

        foreach ($players as $user) {
            $this->grantEventAccess($event, $user);
            EventParticipant::firstOrCreate(
                ['event_id' => $event->id, 'user_id' => $user->id],
                ['id' => (string) str()->uuid()],
            );
        }
    }

    private function seedBoard(array $spec): void
    {
        $isNewBoard = Event::where('title', $spec['title'])->doesntExist();

        $owner = $this->users->random();

        // The competition and its Snakes & Ladders payload are two rows now —
        // see the split_events_from_boards migration.
        $event = Event::firstOrCreate(
            ['title' => $spec['title']],
            [
                'id' => (string) str()->uuid(),
                'type' => 'SNAKES_LADDERS',
                'description' => $spec['description'],
                'mode' => $spec['mode'],
                'access_mode' => $spec['access_mode'],
                'required_guild_id' => $spec['access_mode'] === 'GUILD' ? fake()->numerify('##################') : null,
                'is_listed' => true,
                'start_date' => $spec['start_date'],
                'end_date' => $spec['end_date'],
            ],
        );

        $board = Board::firstOrCreate(
            ['event_id' => $event->id],
            [
                'id' => (string) str()->uuid(),
                'size' => $spec['size'],
                'dice_roll_limit' => $spec['dice_roll_limit'],
            ],
        );

        if ($isNewBoard) {
            BoardAuthor::create([
                'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true,
            ]);

            if ($spec['coauthor'] ?? false) {
                $coauthor = $this->users->where('id', '!=', $owner->id)->random();
                BoardAuthor::create([
                    'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $coauthor->id, 'is_owner' => false,
                ]);
            }

            // access_mode moved to the event in the split. Read off the board
            // it is silently null, so this never fired and no fresh seed ever
            // produced a single guild membership — leaving GUILD events
            // joinable by nobody.
            if ($event->access_mode === 'GUILD') {
                $this->seedGuildMembership($event);
            }
        }

        // Outside the $isNewBoard block, unlike the rows above: invites are
        // the one part of a board's demo data that gets extended over time
        // (a new state for the admin overview to show, say), and gating them
        // on board creation meant the only way to add one was to delete the
        // board it belonged to. seedInvites() keys on the label to stay
        // idempotent.
        // Same null-off-the-board slip as the GUILD check above: no fresh seed
        // ever created an invite, so the admin invites overview — and the
        // "VIP Beta Test" spec that exists purely to cover its four states —
        // had nothing at all to show.
        if ($event->access_mode === 'INVITE') {
            $this->seedInvites($event, $owner, $spec['invites'] ?? []);
        }

        // Repair-on-run: an earlier crashed seed run can leave a Board row
        // committed with no tiles (Board::firstOrCreate isn't wrapped in the
        // same transaction as the tiles insert below). Backfill regardless
        // of $isNewBoard rather than trusting the board's mere existence to
        // mean it's complete. Any tile that's still missing a task after
        // this — an older board, or the ~30%-of-tiles rule this used to run
        // under — is caught by backfillAllTileTasks() below, site-wide.
        if ($board->tiles()->count() === 0) {
            $this->seedTiles($board);
        }

        $this->seedPlayers($event, $board, $spec);
    }

    private function seedTiles(Board $board): void
    {
        $total = self::TILE_COUNTS[$board->size];
        // ~10% of tiles total are snakes/ladders combined, split evenly.
        $specialCount = max(1, (int) round($total * 0.05));

        $specialPositions = collect(range(1, $total - 2))->shuffle()->take($specialCount * 2);
        [$snakePositions, $ladderPositions] = [$specialPositions->take($specialCount), $specialPositions->skip($specialCount)->take($specialCount)];

        // Shuffled and cycled rather than drawn at random per tile: a random
        // draw with replacement clusters the same task on the same board far
        // more than it should, and a board is exactly the surface where
        // players would notice "Kill 50 cows" three tiles in a row.
        $shuffledTasks = $this->tasks->shuffle()->values();
        $taskCursor = 0;

        $tiles = collect(range(0, $total - 1))->map(function (int $position) use ($total, $board, $snakePositions, $ladderPositions, $shuffledTasks, &$taskCursor) {
            $type = match (true) {
                $snakePositions->contains($position) => 'SNAKE',
                $ladderPositions->contains($position) => 'LADDER',
                default => 'NORMAL',
            };

            $target = match ($type) {
                'SNAKE' => random_int(0, max(0, $position - 1)),
                'LADDER' => random_int(min($position + 1, $total - 1), $total - 1),
                default => null,
            };

            // Every NORMAL tile carries a real task now — a seeded board is
            // meant to demo a finished event, and a grid mostly showing
            // generic position labels never looked like one.
            $taskId = null;
            if ($type === 'NORMAL') {
                $taskId = $shuffledTasks[$taskCursor % $shuffledTasks->count()]->id;
                $taskCursor++;
            }

            return [
                'id' => (string) str()->uuid(),
                // Tiles belong to the BOARD — `tiles` has no event_id column,
                // and $event was never in scope here either. Same slip as
                // TileController::upsert had.
                'board_id' => $board->id,
                'position' => $position,
                'task_id' => $taskId,
                'title_override' => null,
                'type' => $type,
                'target_position' => $target,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Tile::insert($tiles->all());
    }

    /**
     * Fills in the task on any NORMAL tile that doesn't have one, on any
     * board — the old ~30%-of-tiles rule this used to run under, seeded
     * boards from before the task pool grew past its original fourteen
     * rows, and (the point of making this site-wide rather than scoped to
     * boardSpecs()) every board created by hand through the app itself.
     * "Starts next month" — one of DatabaseSeeder's own age-old boards, not
     * one of boardSpecs()' nine — sat with every tile empty through the
     * first two task-filling passes for exactly this reason: this method
     * used to take a `Board $board` and only ever got called from inside
     * seedBoard(), so nothing outside that fixed nine-board list was ever
     * reachable. Same fix `backfillBingoSquareTasks()` already got, applied
     * a second time to the surface it was supposed to cover the first time.
     */
    private function backfillAllTileTasks(): void
    {
        $empty = Tile::where('type', 'NORMAL')->whereNull('task_id')->get();

        if ($empty->isEmpty()) {
            return;
        }

        $shuffledTasks = $this->tasks->shuffle()->values();

        $empty->each(function (Tile $tile, int $i) use ($shuffledTasks) {
            $tile->update(['task_id' => $shuffledTasks[$i % $shuffledTasks->count()]->id]);
        });

        $this->command->info("Backfilled tasks onto {$empty->count()} empty NORMAL tiles.");
    }

    private function seedGuildMembership(Event $event): void
    {
        // Half the pool "belongs" to the event's required guild, so GUILD
        // access is actually joinable by roughly half of seeded players.
        // (Guild restriction moved to the event in the split; $board was left
        // behind here and was never in scope.)
        $this->users->random(intdiv($this->users->count(), 2))->each(fn (User $user) => UserGuild::firstOrCreate(
            ['user_id' => $user->id, 'guild_id' => $event->required_guild_id],
            ['id' => (string) str()->uuid(), 'guild_name' => $event->title.' Discord'],
        ));
    }

    private function seedInvites(Event $event, User $owner, array $invites): void
    {
        foreach ($invites as $invite) {
            $label = $invite['label']
                ?? ($invite['max_uses'] ? "Limited ({$invite['max_uses']} uses)" : 'Unlimited invite');

            // Keyed on (board, label) so a re-run tops up newly added demo
            // invites without duplicating the existing ones — and without
            // regenerating short_code, which carries a unique constraint.
            BoardInvite::firstOrCreate(
                ['event_id' => $event->id, 'label' => $label],
                [
                    'id' => (string) str()->uuid(),
                    'token' => (string) str()->uuid(),
                    'short_code' => strtoupper(str()->random(6)),
                    'created_by' => $owner->id,
                    'expires_at' => $invite['expires'],
                    'max_uses' => $invite['max_uses'],
                    // A spec can pin the count when the state depends on it
                    // (exhausted needs use_count >= max_uses, unused needs 0);
                    // otherwise it stays random so the demo data isn't uniform.
                    'use_count' => $invite['uses']
                        ?? ($invite['max_uses'] ? random_int(0, (int) ($invite['max_uses'] / 2)) : random_int(0, 8)),
                ],
            );
        }
    }

    /**
     * Takes the event as well as the board because the split moved `mode`
     * (and teams, and access) onto the event. This read `$board->mode`, which
     * is now always null — so every TEAM board quietly took the solo branch
     * and then died on a spec that has 'teams' and no 'players'. Invisible on
     * an existing database, where the idempotency check short-circuits first;
     * only a fresh seed reaches it.
     */
    private function seedPlayers(Event $event, Board $board, array $spec): void
    {
        $total = self::TILE_COUNTS[$board->size];
        $boardTiles = Tile::where('board_id', $board->id)->orderBy('position')->get();

        if ($event->mode === 'TEAM') {
            // Idempotent team selection: once a board has teams assigned,
            // re-running the seeder reuses them instead of drawing a fresh
            // random subset each time (which would silently accumulate more
            // teams than $spec['teams'] intended on every re-run).
            $existingTeams = $event->eventTeams()->with('team.members.user')->get()->pluck('team');
            $teams = $existingTeams->isNotEmpty()
                ? $existingTeams
                : $this->teams->random(min($spec['teams'], $this->teams->count()));

            // PlayerBoardService keys TEAM progress on (board_id, team_id) —
            // one shared row per team, not per member (see its find()/
            // getOrCreate()). Its representative also can't be a user already
            // representing a different team on this same board: PlayerBoard
            // is only unique on (user_id, board_id), so a user in two teams
            // assigned to the same board would silently reuse their first
            // team's row for the second, leaving that team's BoardTeam
            // pointing at zero PlayerBoard rows. Track used users per board
            // to avoid that collision. Access is still per-user, so every
            // member still gets their own BoardAccess grant regardless.
            $usedUserIds = [];
            foreach ($teams as $team) {
                if (! BoardTeam::where(['event_id' => $event->id, 'team_id' => $team->id])->exists()) {
                    BoardTeam::create(['id' => (string) str()->uuid(), 'event_id' => $event->id, 'team_id' => $team->id]);
                }

                foreach ($team->members as $member) {
                    $this->grantEventAccess($event, $member->user);
                }

                $representative = $team->members->first(fn ($m) => ! in_array($m->user_id, $usedUserIds, true))?->user
                    ?? $team->members->first()?->user;

                if ($representative) {
                    $usedUserIds[] = $representative->id;
                    $this->seedPlayerBoard($board, $representative, $total, $boardTiles, $team->id);
                }
            }

            return;
        }

        // Idempotent same as the TEAM branch above: reuse whichever users
        // already have a PlayerBoard on this board rather than drawing a
        // fresh random sample every run — otherwise re-running `db:seed`
        // silently keeps adding ~$spec['players'] more distinct users on
        // top of the last run's, forever (caught at 68 accumulated players
        // on a board seeded for 20, after three re-runs in one session).
        $existingPlayerUserIds = $board->playerBoards()->pluck('user_id');
        $players = $existingPlayerUserIds->isNotEmpty()
            ? $this->users->whereIn('id', $existingPlayerUserIds)
            : $this->users->random(min($spec['players'], $this->users->count()));

        $players->each(fn (User $user) => $this->seedPlayerBoard($board, $user, $total, $boardTiles));
    }

    private function grantEventAccess(Event $event, User $user): void
    {
        if ($event->access_mode === 'OPEN') {
            return;
        }

        BoardAccess::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['id' => (string) str()->uuid(), 'access_mode' => $event->access_mode],
        );
    }

    private function seedPlayerBoard(Board $board, User $user, int $totalTiles, Collection $boardTiles, ?string $teamId = null): void
    {
        $playerBoard = PlayerBoard::firstOrCreate(
            ['user_id' => $user->id, 'board_id' => $board->id],
            [
                'id' => (string) str()->uuid(),
                'team_id' => $teamId,
                'current_position' => random_int(0, $totalTiles - 1),
                'dice_rolls_today' => random_int(0, 3),
                'last_roll_date' => fake()->randomElement([now(), now()->subDay(), null]),
            ],
        );

        $this->grantEventAccess($board->event, $user);

        // A real join (EventParticipationService::join()) always writes one
        // of these; this seeder creates the PlayerBoard row directly and
        // skipped it, which undercounted `/my-events`' new participant
        // total on every demo board — discovered while building that
        // feature, not a live bug (real joins go through the service).
        EventParticipant::firstOrCreate(
            ['event_id' => $board->event_id, 'user_id' => $user->id],
            ['id' => (string) str()->uuid()],
        );

        if ($playerBoard->completedTiles()->count() > 0) {
            return;
        }

        $completed = $boardTiles->where('position', '<=', $playerBoard->current_position);
        foreach ($completed as $tile) {
            CompletedTile::create([
                'id' => (string) str()->uuid(),
                'player_board_id' => $playerBoard->id,
                'tile_id' => $tile->id,
                'completed_via' => fake()->randomElement(['MANUAL', 'MANUAL', 'RUNELITE']),
            ]);
        }
    }
}
