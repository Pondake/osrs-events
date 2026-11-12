<?php

use App\Models\Event;
use App\Models\EventBlueprint;
use App\Models\User;
use Database\Seeders\EventBlueprintSeeder;
use Illuminate\Support\Facades\Hash;

if (! EventBlueprint::where('title', 'Skill of the Week')->exists()) {
    (new EventBlueprintSeeder)->run();
}
foreach (Event::where('title', 'like', 'Skill of the Week%')->get() as $e) {
    $e->delete();
}
$names = [
    'The Grand Midsummer Clan Championship of Old School RuneScape — Season Four' => 'Midsummer Clan Championship',
    'On hold' => 'Gathering Week', 'Photo finish' => 'Autumn Snakes & Ladders', 'E2E Ladder' => 'Ladder League',
    'Zulrah sprint' => 'Zulrah Sprint', 'Teams of four' => 'Teams of Four',
];
foreach ($names as $from => $to) {
    Event::where('title', $from)->update(['title' => $to]);
}
foreach (Event::where('title', 'like', 'E2E%')->get() as $e) {
    $e->update(['is_listed' => false]);
}

$host = User::where('discord_username', 'demo_host')->first() ?? User::forceCreate([
    'discord_id' => 'demo-00000000000host', 'discord_username' => 'demo_host',
    'nickname' => 'Hans', 'osrs_username' => 'Hans', 'email' => 'host@demo.test',
    'password' => Hash::make(Str::random(32)), 'avatar_url' => 'https://oldschool.runescape.wiki/images/Hans_chathead.png?c8854',
    'osrs_verified_at' => now(), 'onboarding_completed_at' => now(),
]);
$host->grantStarterAccess();
echo "RESET {$host->id}\n";
