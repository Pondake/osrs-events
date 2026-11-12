<?php

use App\Models\Event;
use App\Models\EventStanding;

$waves = [
    ['Hans' => 412385, 'Aggie' => 388120, 'Duke' => 351904, 'Gnome Kid' => 297233, 'Bob' => 276510, 'Old Wise' => 240871, 'Ned' => 198402, 'Juliet' => 154760],
    ['Hans' => 431022, 'Aggie' => 392456, 'Duke' => 356318, 'Gnome Kid' => 301877, 'Bob' => 318044, 'Old Wise' => 247290, 'Ned' => 262915, 'Juliet' => 160233],
    ['Hans' => 440318, 'Aggie' => 395870, 'Duke' => 360741, 'Gnome Kid' => 330102, 'Bob' => 372688, 'Old Wise' => 251406, 'Ned' => 309577, 'Juliet' => 256930],
    ['Hans' => 452760, 'Aggie' => 398214, 'Duke' => 371055, 'Gnome Kid' => 344619, 'Bob' => 421390, 'Old Wise' => 262118, 'Ned' => 352804, 'Juliet' => 258441],
    ['Hans' => 463105, 'Aggie' => 430667, 'Duke' => 374512, 'Gnome Kid' => 349283, 'Bob' => 478926, 'Old Wise' => 266035, 'Ned' => 381147, 'Juliet' => 290572],
];
$n = (int) trim(file_get_contents(__DIR__.'/wave.txt'));
$event = Event::where('title', 'Skill of the Month: Mining')->firstOrFail();
foreach ($waves[$n] as $name => $gained) {
    $row = EventStanding::where('event_id', $event->id)->where('username', $name)->firstOrFail();
    $row->update(['gained' => $gained, 'end_value' => $row->start_value + $gained, 'synced_at' => now()]);
}
echo 'WAVE '.$n.' '.$event->id."\n";
