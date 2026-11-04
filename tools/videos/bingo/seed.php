<?php
use App\Models\{BingoCard, BingoCompletion, BoardAuthor, Event, EventParticipant, Task, User};
use App\Services\BingoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

$wiki = 'https://oldschool.runescape.wiki/images/';
$players = [
    'bob' => ['Bob', 'Bob_chathead.png?048f5', [0, 5, 6, 8, 9, 12, 18, 21]],
    'hans' => ['Hans', 'Hans_chathead.png?c8854', [1, 2, 6, 12, 13, 24]],
    'aggie' => ['Aggie', 'Aggie_chathead.png?5a27b', [3, 9, 14, 20]],
    'duke' => ['Duke', 'Duke_Horacio_chathead.png?6efaf', [4, 10, 16]],
    'ned' => ['Ned', 'Ned_chathead.png?8585a', [11, 17]],
];
$owner = User::where('discord_username', 'e2e_owner')->firstOrFail();
$event = Event::create([
    'title' => 'Clan Bingo Night', 'type' => 'BINGO', 'description' => 'Demo card.', 'mode' => 'SOLO',
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
foreach ($players as $key => [$nick, $avatar, $done]) {
    $user = User::forceCreate([
        'discord_id' => 'demo-'.str_pad($key, 14, '0', STR_PAD_LEFT), 'discord_username' => 'demo_'.$key,
        'nickname' => $nick, 'osrs_username' => $nick, 'email' => $key.'@demo.test',
        'password' => Hash::make(Str::random(32)), 'avatar_url' => $wiki.$avatar,
        'osrs_verified_at' => now(), 'onboarding_completed_at' => now(),
    ]);
    EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id]);
    foreach ($done as $p) {
        BingoCompletion::create(['bingo_square_id' => $squares[$p]->id, 'user_id' => $user->id, 'marked_by' => $user->id,
            'completed_via' => 'MANUAL', 'status' => 'APPROVED', 'rsn' => $nick, 'reviewed_by' => $owner->id, 'reviewed_at' => now()]);
    }
}
echo 'EVENT '.$event->id.' '.$squares[7]->id."\n";
