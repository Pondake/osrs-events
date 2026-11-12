<?php

use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventFinish;
use App\Models\PlayerBoard;
use App\Models\User;

$event = Event::where('title', 'Saturday Snakes & Ladders')->firstOrFail();
$start = ['hans' => 33, 'bob' => 28, 'aggie' => 20, 'duke' => 17, 'gnome' => 12, 'wise' => 9, 'aereck' => 7,
    'gertrude' => 5, 'romeo' => 2, 'juliet' => 1, 'thessalia' => 14, 'ned' => 24];
foreach ($start as $key => $pos) {
    $user = User::where('discord_username', 'demo_'.$key)->firstOrFail();
    PlayerBoard::where('user_id', $user->id)->where('board_id', $event->board->id)
        ->update(['current_position' => $pos, 'dice_rolls_today' => 0, 'last_move_from' => null, 'last_move_landed' => null, 'last_move_jump' => null]);
}
CompletedTile::whereIn('player_board_id', PlayerBoard::where('board_id', $event->board->id)->pluck('id'))->delete();
EventFinish::where('event_id', $event->id)->delete();
echo 'RESET '.$event->id.' '.$event->board->tiles()->where('position', 48)->value('id')."\n";
