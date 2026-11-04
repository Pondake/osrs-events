<?php
use App\Models\{BingoCard, BingoCompletion, BoardAuthor, Event, EventParticipant, Task, Team, TeamMember, User};
use App\Services\BingoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

$wiki = 'https://oldschool.runescape.wiki/images/';
$owner = User::where('discord_username', 'e2e_owner')->firstOrFail();
$event = Event::where('title', 'Clan Team Bingo')->first();
if (! $event) {
    $event = Event::create([
        'title' => 'Clan Team Bingo', 'type' => 'BINGO', 'description' => 'Demo card.', 'mode' => 'TEAM',
        'access_mode' => 'OPEN', 'is_listed' => true,
        'start_date' => Carbon::now()->subDays(3), 'end_date' => Carbon::now()->addDays(11),
    ]);
    BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);
    $card = BingoCard::create(['event_id' => $event->id, 'size' => 5, 'win_condition' => 'LINE', 'requires_approval' => true]);
    app(BingoService::class)->ensureSquares($card);
    $titles = ['Kill 50 cows', 'Catch 50 lobsters', 'Complete a Barrows run', 'Kill the Giant Mole', 'Chop 300 logs',
        'Complete a hard clue scroll', 'Kill King Black Dragon', 'Kill Vorkath once', 'Complete a Tempoross kill', 'Kill the Kraken',
        'Mine 200 iron ore', 'Complete a Slayer task', 'Kill General Graardor', 'Craft 50 nature runes', 'Kill Cerberus once',
        'Catch 30 implings', 'Make 50 prayer potions', 'Complete a game of Pest Control', 'Defeat the Corporeal Beast', 'Fletch 200 arrows',
        'Kill TzTok-Jad', 'Complete a medium clue scroll', 'Cook 100 trout', 'Win a game of Castle Wars', 'Kill 5 Zulrah'];
    $squares = $card->squares()->orderBy('position')->get();
    foreach ($squares as $sq) {
        $task = Task::where('title', $titles[$sq->position])->first();
        $sq->update(['task_id' => $task?->id, 'title_override' => $task ? null : $titles[$sq->position], 'is_wildcard' => false]);
    }
    $teams = [
        'Iron Wolves' => [['bob', 'Bob', 'Bob_chathead.png?048f5'], ['hans', 'Hans', 'Hans_chathead.png?c8854'], ['aggie', 'Aggie', 'Aggie_chathead.png?5a27b']],
        'Rune Raiders' => [['duke', 'Duke', 'Duke_Horacio_chathead.png?6efaf'], ['ned', 'Ned', 'Ned_chathead.png?8585a']],
    ];
    $done = ['Iron Wolves' => [0, 2, 6, 12, 17, 23], 'Rune Raiders' => [1, 5, 9, 13, 20]];
    foreach ($teams as $name => $members) {
        $team = Team::create(['name' => $name]);
        $event->eventTeams()->create(['team_id' => $team->id]);
        $users = [];
        foreach ($members as [$key, $nick, $avatar]) {
            $user = User::where('discord_username', 'demo_'.$key)->first() ?? User::forceCreate([
                'discord_id' => 'demo-'.str_pad($key, 14, '0', STR_PAD_LEFT), 'discord_username' => 'demo_'.$key,
                'nickname' => $nick, 'osrs_username' => $nick, 'email' => $key.'@demo.test',
                'password' => Hash::make(Str::random(32)), 'avatar_url' => $wiki.$avatar,
                'osrs_verified_at' => now(), 'onboarding_completed_at' => now(),
            ]);
            TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id]);
            EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id]);
            $users[] = $user;
        }
        foreach ($done[$name] as $i => $p) {
            $u = $users[$i % count($users)];
            BingoCompletion::create(['bingo_square_id' => $squares[$p]->id, 'team_id' => $team->id, 'marked_by' => $u->id,
                'completed_via' => 'MANUAL', 'status' => 'APPROVED', 'rsn' => $u->nickname, 'reviewed_by' => $owner->id, 'reviewed_at' => now()]);
        }
    }
}
\App\Models\EventFinish::where('event_id', $event->id)->delete();
$sq = $event->bingoCard->squares()->where('position', 7)->first();
BingoCompletion::where('bingo_square_id', $sq->id)->delete();
echo 'EVENT '.$event->id.' '.$sq->id."\n";
