<?php

use App\Models\BingoCompletion;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;

$event = Event::where('title', 'Clan Team Bingo')->firstOrFail();
$sq = $event->bingoCard->squares()->where('position', 7)->first();
$hans = User::where('discord_username', 'demo_hans')->first();
$team = Team::where('name', 'Iron Wolves')->first();
$owner = User::where('discord_username', 'e2e_owner')->first();
BingoCompletion::create(['bingo_square_id' => $sq->id, 'team_id' => $team->id, 'marked_by' => $hans->id,
    'completed_via' => 'MANUAL', 'status' => 'APPROVED', 'rsn' => 'Hans', 'reviewed_by' => $owner->id, 'reviewed_at' => now()]);
echo "SCORED\n";
