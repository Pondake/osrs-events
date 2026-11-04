<?php
use App\Models\{BingoCard, BingoCompletion, BoardAuthor, Event, EventFinish, EventParticipant, Task, User};
use App\Services\BingoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

$state = (int) trim(file_get_contents(__DIR__.'/state.txt'));
$w = 'https://oldschool.runescape.wiki/images/thumb/';
$squares = [
    ['Zulrah', 'Zulrah_%28serpentine%29.png/100px-Zulrah_%28serpentine%29.png?29a54'],
    ['Vorkath', 'Vorkath.png/100px-Vorkath.png?1ce3f'],
    ['Barrows', 'Barrows_minigame.png/100px-Barrows_minigame.png?f7aaf'],
    ['Goblin', 'Goblin.png/100px-Goblin.png?3c3d8'],
    ['Chicken', 'Chicken_%281%29.png/100px-Chicken_%281%29.png?a7258'],
    ['Cow', 'Cow_%281%29.png/100px-Cow_%281%29.png?52ebb'],
    ['Fire cape', 'Fire_cape_detail.png/100px-Fire_cape_detail.png?f1d08'],
    ['Tempoross', 'Tempoross.png/100px-Tempoross.png?12042'],
    ['Wintertodt', 'Howling_Snow_Storm.gif/100px-Howling_Snow_Storm.gif?ec549'],
];
$owner = User::where('discord_username', 'e2e_owner')->firstOrFail();
$player = User::where('discord_username', 'demo_iron')->first() ?? User::forceCreate([
    'discord_id' => 'demo-0000000000iron', 'discord_username' => 'demo_iron', 'nickname' => 'Iron Player',
    'osrs_username' => 'Iron Player', 'email' => 'iron@demo.test', 'password' => Hash::make(Str::random(32)),
    'osrs_verified_at' => now(), 'onboarding_completed_at' => now(),
]);
$event = Event::where('title', 'Clan Bingo Night')->first();
if (! $event) {
    $event = Event::create(['title' => 'Clan Bingo Night', 'type' => 'BINGO', 'mode' => 'SOLO', 'access_mode' => 'OPEN', 'is_listed' => true,
        'description' => 'A single evening on one card. Keep the squares achievable — a card nobody finishes is a card nobody remembers.',
        'start_date' => Carbon::now()->subHours(2), 'end_date' => Carbon::now()->addDays(14)]);
    BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);
    $card = BingoCard::create(['event_id' => $event->id, 'size' => 3, 'win_condition' => 'LINE', 'requires_approval' => false]);
    app(BingoService::class)->ensureSquares($card);
    foreach ($card->squares()->orderBy('position')->get() as $sq) {
        [$t, $icon] = $squares[$sq->position];
        $task = Task::firstOrCreate(['title' => $t], ['icon_url' => $w.$icon]);
        $sq->update(['task_id' => $task->id, 'title_override' => null, 'is_wildcard' => false]);
    }
    EventParticipant::create(['event_id' => $event->id, 'user_id' => $player->id]);
}
$sq = $event->bingoCard->squares()->orderBy('position')->get();
BingoCompletion::whereIn('bingo_square_id', $sq->pluck('id'))->delete();
EventFinish::where('event_id', $event->id)->delete();
foreach (array_slice([3, 5, 4], 0, $state) as $p) {
    BingoCompletion::create(['bingo_square_id' => $sq[$p]->id, 'user_id' => $player->id, 'marked_by' => $player->id,
        'completed_via' => 'PLUGIN', 'status' => 'APPROVED', 'rsn' => 'Iron Player', 'reviewed_at' => now()]);
}
if ($state === 3) {
    EventFinish::create(['event_id' => $event->id, 'user_id' => $player->id, 'display_name' => 'Iron Player', 'finished_at' => now()]);
}
echo 'EVENT '.$event->id."\n";
