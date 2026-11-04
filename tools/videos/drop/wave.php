<?php
use App\Models\{Event, EventStanding};
$waves = [
    ['Hans' => 61, 'Aggie' => 57, 'Duke' => 52, 'Gnome Kid' => 44, 'Bob' => 41, 'Old Wise' => 35, 'Ned' => 29, 'Juliet' => 22],
    ['Hans' => 63, 'Aggie' => 58, 'Duke' => 53, 'Gnome Kid' => 45, 'Bob' => 49, 'Old Wise' => 36, 'Ned' => 38, 'Juliet' => 23],
    ['Hans' => 65, 'Aggie' => 59, 'Duke' => 54, 'Gnome Kid' => 50, 'Bob' => 56, 'Old Wise' => 37, 'Ned' => 46, 'Juliet' => 38],
    ['Hans' => 67, 'Aggie' => 60, 'Duke' => 56, 'Gnome Kid' => 52, 'Bob' => 63, 'Old Wise' => 39, 'Ned' => 53, 'Juliet' => 39],
    ['Hans' => 68, 'Aggie' => 64, 'Duke' => 57, 'Gnome Kid' => 53, 'Bob' => 71, 'Old Wise' => 40, 'Ned' => 57, 'Juliet' => 44],
];
$n = (int) trim(file_get_contents(__DIR__.'/wave.txt'));
$event = Event::where('title', 'Boss of the Month: Zulrah')->firstOrFail();
foreach ($waves[$n] as $name => $gained) {
    $row = EventStanding::where('event_id', $event->id)->where('username', $name)->firstOrFail();
    $row->update(['gained' => $gained, 'end_value' => $row->start_value + $gained, 'synced_at' => now()]);
}
echo 'WAVE '.$n.' '.$event->id."\n";
