<?php

namespace Database\Seeders;

use App\Models\BingoCard;
use App\Models\Event;
use App\Models\Task;
use App\Services\BingoService;
use Illuminate\Database\Seeder;

/**
 * The fixed set a test group plays through with the plugin. The checklist of
 * this set lives with the "Plugin-API" backlog item — keep the two in step.
 *
 *   php artisan db:seed --class=RunelitePluginTestSeeder
 */
class RunelitePluginTestSeeder extends Seeder
{
    public const EVENT_TITLE = 'RuneLite plugin test bingo';

    /** wiki page id => [title, what it tests] */
    public const TASKS = [
        13463 => ['Big bones', 'common drop'],
        14789 => ['Dragon bones', 'common drop'],
        69387 => ['Dragon warhammer', 'rare drop'],
        10799 => ["Karil's coif", 'rare drop, apostrophe'],
        44177 => ['Tanzanite fang', 'collection log'],
        44179 => ['Pet Snakeling', 'collection log, case differs in game'],
        12426 => ['Giant Mole', 'boss kill'],
        80432 => ['Obor', 'boss kill'],
        22394 => ['Clue scroll (medium)', 'clue item, non-numeric suffix kept'],
        10988 => ['Ranger boots', 'clue reward'],
        15201 => ['Prayer potion', 'doses: Prayer potion(4)'],
        10636 => ['Games necklace', 'charges: Games necklace(8)'],
        44148 => ["Zulrah's scales", 'stackable, apostrophe'],
    ];

    public const MANUAL_ONLY_TITLE = 'Manual only: screenshot a sunset';

    public function run(): void
    {
        $tasks = collect(self::TASKS)->map(fn (array $spec, int $pageId) => Task::updateOrCreate(
            ['wiki_page_id' => $pageId],
            [
                'title' => $spec[0],
                'wiki_url' => 'https://oldschool.runescape.wiki/w/'.str_replace(' ', '_', $spec[0]),
                'wiki_synced_at' => now(),
            ],
        ))->values();

        $tasks->push(Task::firstOrCreate(['title' => self::MANUAL_ONLY_TITLE, 'wiki_page_id' => null]));

        $event = Event::firstOrCreate(
            ['title' => self::EVENT_TITLE],
            [
                'type' => 'BINGO',
                'description' => 'Squares for testing the RuneLite plugin. One square has no wiki page and can only be claimed by hand.',
                'mode' => 'SOLO',
                'access_mode' => 'OPEN',
                'is_listed' => false,
                'start_date' => now()->startOfDay(),
                'end_date' => now()->addDays(30)->endOfDay(),
            ],
        );

        $card = BingoCard::firstOrCreate(['event_id' => $event->id], ['size' => 4]);
        app(BingoService::class)->ensureSquares($card);

        $squares = $card->squares()->orderBy('position')->get();
        foreach ($tasks as $index => $task) {
            $squares->get($index)?->update(['task_id' => $task->id]);
        }

        $this->command?->info('Seeded '.$tasks->count()." tasks — visit /events/{$event->id}");
    }
}
