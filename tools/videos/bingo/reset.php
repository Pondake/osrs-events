<?php

use App\Models\BingoCompletion;
use App\Models\Event;
use App\Models\EventFinish;

$event = Event::where('title', 'Clan Bingo Night')->firstOrFail();
$sq = $event->bingoCard->squares()->where('position', 7)->first();
BingoCompletion::where('bingo_square_id', $sq->id)->delete();
EventFinish::where('event_id', $event->id)->delete();
echo 'RESET '.$event->id.' '.$sq->id."\n";
