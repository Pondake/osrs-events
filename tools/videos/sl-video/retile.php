<?php
use App\Models\{Event, Task};
$event = Event::where('title', 'Saturday Snakes & Ladders')->firstOrFail();
$broken = ['Zulrah.png', 'Alchemical_Hydra.png', 'Wintertodt.png'];
$tasks = Task::whereNotNull('icon_url')->orderBy('title')->get()
    ->reject(fn ($t) => collect($broken)->contains(fn ($b) => str_contains($t->icon_url, '/'.$b)))
    ->shuffle(11)->values();
$i = 0;
foreach ($event->board->tiles()->orderBy('position')->get() as $tile) {
    if ($tile->type === 'NORMAL') {
        $tile->update(['task_id' => $tasks[$i++ % $tasks->count()]->id]);
    }
}
echo "tasks={$tasks->count()} tiles={$i}\n";
