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

    /** wiki page id => [title, what it tests], in card order */
    public const TASKS = [
        12426 => ['Giant Mole', 'boss kill'],
        18027 => ['Mole claw', 'boss drop, always'],
        18026 => ['Mole skin', 'boss drop, always'],
        13463 => ['Big bones', 'common drop'],
        33699 => ['Baby Mole', 'pet, case differs in game'],
        80432 => ['Obor', 'boss kill'],
        80435 => ['Giant key', 'key drop'],
        115742 => ['Bryophyta', 'boss kill'],
        115744 => ['Mossy key', 'key drop'],
        20667 => ['Hill Giant', 'monster kill'],
        12398 => ['Cow', 'monster kill'],
        11468 => ['Cowhide', 'common drop'],
        11607 => ['Bones', 'common drop'],
        10116 => ['Ashes', 'common drop'],
        11566 => ['Feather', 'stackable drop'],
        9854 => ['Coins', 'stackable drop'],
        12222 => ['Nature rune', 'stackable drop'],
        12692 => ['Law rune', 'stackable drop'],
        13440 => ['Death rune', 'stackable drop'],
        12720 => ['Yew logs', 'noted drop'],
        14789 => ['Dragon bones', 'common drop'],
        69787 => ['Lizardman shaman', 'slayer kill'],
        69387 => ['Dragon warhammer', 'rare drop'],
        12840 => ['Abyssal demon', 'slayer kill'],
        10123 => ['Abyssal whip', 'rare drop'],
        14912 => ['Gargoyle', 'slayer kill'],
        10683 => ['Granite maul', 'rare drop'],
        13336 => ['Dust devil', 'slayer kill'],
        29357 => ['Kraken', 'slayer boss kill'],
        16243 => ['Long bone', 'rare slayer drop'],
        10799 => ["Karil's coif", 'rare drop, apostrophe'],
        44177 => ['Tanzanite fang', 'collection log'],
        44179 => ['Pet Snakeling', 'collection log, case differs in game'],
        44148 => ["Zulrah's scales", 'stackable, apostrophe'],
        19649 => ['Clue scroll (easy)', 'clue item, non-numeric suffix kept'],
        22394 => ['Clue scroll (medium)', 'clue item'],
        16198 => ['Clue scroll (hard)', 'clue item'],
        37295 => ['Clue scroll (elite)', 'clue item'],
        10988 => ['Ranger boots', 'clue reward'],
        16375 => ['Loop half of key', 'rare drop table'],
        14915 => ['Tooth half of key', 'rare drop table'],
        13006 => ['Uncut diamond', 'rare drop table'],
        15201 => ['Prayer potion', 'doses: Prayer potion(4)'],
        18131 => ['Super restore', 'doses: Super restore(4)'],
        10636 => ['Games necklace', 'charges: Games necklace(8)'],
        11003 => ['Ring of dueling', 'charges: Ring of dueling(8)'],
        45332 => ['Grimy ranarr weed', 'herb drop'],
        17097 => ['Ranarr seed', 'seed drop'],
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

        $card = BingoCard::firstOrCreate(['event_id' => $event->id], ['size' => 7]);
        $card->update(['size' => 7]);
        app(BingoService::class)->ensureSquares($card->fresh());

        $squares = $card->squares()->orderBy('position')->get();
        foreach ($tasks as $index => $task) {
            $squares->get($index)?->update(['task_id' => $task->id]);
        }

        $this->command?->info('Seeded '.$tasks->count()." tasks — visit /events/{$event->id}");
    }
}
