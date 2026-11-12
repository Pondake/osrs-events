<?php

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventStanding;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

$wiki = 'https://oldschool.runescape.wiki/images/';
$players = [
    ['hans', 'Hans', 'Hans_chathead.png?c8854'],
    ['aggie', 'Aggie', 'Aggie_chathead.png?5a27b'],
    ['duke', 'Duke', 'Duke_Horacio_chathead.png?6efaf'],
    ['gnome', 'Gnome Kid', 'Gnome_child_chathead.png?16a86'],
    ['bob', 'Bob', 'Bob_chathead.png?048f5'],
    ['wise', 'Old Wise', 'Wise_Old_Man_chathead.png?68f26'],
    ['ned', 'Ned', 'Ned_chathead.png?8585a'],
    ['juliet', 'Juliet', 'Juliet_chathead.png?59aa4'],
];
$owner = User::where('discord_username', 'e2e_owner')->firstOrFail();
$event = Event::create([
    'title' => 'Skill of the Month: Mining',
    'type' => 'SKILL_RACE',
    'metric' => 'mining',
    'description' => 'Demo race.',
    'mode' => 'SOLO',
    'access_mode' => 'OPEN',
    'is_listed' => true,
    'start_date' => Carbon::now()->subDays(9),
    'end_date' => Carbon::now()->addDays(21),
]);
BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);
foreach ($players as [$key, $nick, $avatar]) {
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
    EventStanding::create(['event_id' => $event->id, 'user_id' => $user->id, 'username' => $nick,
        'start_value' => 6_000_000 + crc32($key) % 4_000_000, 'gained' => 0, 'live_gained' => 0, 'synced_at' => now()]);
}
echo "EVENT {$event->id}\n";
