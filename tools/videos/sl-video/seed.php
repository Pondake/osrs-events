<?php

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PlayerBoard;
use App\Models\Task;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

$wiki = 'https://oldschool.runescape.wiki/images/';
$players = [
    ['hans', 'Hans', 'Hans_chathead.png?c8854', 33],
    ['bob', 'Bob', 'Bob_chathead.png?048f5', 28],
    ['aggie', 'Aggie', 'Aggie_chathead.png?5a27b', 20],
    ['duke', 'Duke', 'Duke_Horacio_chathead.png?6efaf', 17],
    ['gnome', 'Gnome Kid', 'Gnome_child_chathead.png?16a86', 12],
    ['wise', 'Old Wise', 'Wise_Old_Man_chathead.png?68f26', 9],
    ['aereck', 'Aereck', 'Father_Aereck_chathead.png?7dcdb', 7],
    ['gertrude', 'Gertrude', 'Gertrude_chathead.png?bddec', 5],
    ['romeo', 'Romeo', 'Romeo_chathead.png?c8cd3', 2],
    ['juliet', 'Juliet', 'Juliet_chathead.png?59aa4', 1],
    ['thessalia', 'Thessalia', 'Thessalia_chathead.png?8624a', 14],
    ['ned', 'Ned', 'Ned_chathead.png?8585a', 24],
];

$owner = User::where('discord_username', 'e2e_owner')->firstOrFail();
$event = Event::create([
    'title' => 'Saturday Snakes & Ladders',
    'type' => 'SNAKES_LADDERS',
    'description' => 'Demo board.',
    'mode' => 'SOLO',
    'access_mode' => 'OPEN',
    'is_listed' => true,
    'start_date' => Carbon::now()->subDays(2),
    'end_date' => Carbon::now()->addDays(12),
]);
BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);
$board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_7X7']);

$ladders = [4 => 18, 13 => 27, 22 => 35, 30 => 42];
$snakes = [47 => 29, 39 => 23, 26 => 10, 16 => 6];
$tasks = Task::whereNotNull('icon_url')->inRandomOrder(7)->get()->values();
$cursor = 0;
foreach (range(0, 48) as $p) {
    $type = isset($ladders[$p]) ? 'LADDER' : (isset($snakes[$p]) ? 'SNAKE' : 'NORMAL');
    Tile::create([
        'board_id' => $board->id,
        'position' => $p,
        'type' => $type,
        'target_position' => $ladders[$p] ?? $snakes[$p] ?? null,
        'task_id' => $type === 'NORMAL' && $p > 0 ? $tasks[$cursor++ % $tasks->count()]->id : null,
    ]);
}

foreach ($players as [$key, $nick, $avatar, $pos]) {
    $user = User::forceCreate([
        'discord_id' => 'demo-'.str_pad($key, 14, '0', STR_PAD_LEFT),
        'discord_username' => 'demo_'.$key,
        'nickname' => $nick,
        'osrs_username' => $nick,
        'email' => $key.'@demo.test',
        'password' => Hash::make(Str::random(32)),
        'avatar_url' => $wiki.$avatar,
        'osrs_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id]);
    PlayerBoard::create(['user_id' => $user->id, 'board_id' => $board->id, 'current_position' => $pos]);
}
echo "EVENT {$event->id}\n";
