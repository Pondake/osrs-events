<?php

namespace Database\Seeders;

use App\Models\BingoCard;
use App\Models\Event;
use App\Models\Task;
use App\Services\BingoService;
use App\Support\PluginTestSet;
use Illuminate\Database\Seeder;

/**
 * The card every plugin tester plays, see PluginTestSet. Safe to run again:
 * it reuses the event and only rewrites which task sits on which square.
 *
 *   php artisan db:seed --class=PluginTestSetSeeder
 */
class PluginTestSetSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::firstOrCreate(
            ['title' => PluginTestSet::EVENT_TITLE],
            [
                'type' => 'BINGO',
                'description' => 'The fixed test set for the RuneLite plugin. Free-to-play, safe on a hardcore account. Your checklist is under Settings, RuneLite plugin.',
                'mode' => 'SOLO',
                'access_mode' => 'OPEN',
                'is_listed' => false,
                'start_date' => now()->startOfDay(),
                'end_date' => now()->addDays(120)->endOfDay(),
            ],
        );

        $card = BingoCard::firstOrCreate(['event_id' => $event->id], ['size' => 6]);
        app(BingoService::class)->ensureSquares($card->fresh());

        $squares = $card->squares()->orderBy('position')->get();
        $index = 0;

        foreach (PluginTestSet::TARGETS as $pageId => [$title, $minQuantity, $requiredCount]) {
            $task = Task::updateOrCreate(
                ['wiki_page_id' => $pageId],
                [
                    'title' => $title,
                    'wiki_url' => 'https://oldschool.runescape.wiki/w/'.str_replace(' ', '_', $title),
                    'wiki_synced_at' => now(),
                ],
            );

            $squares->get($index++)?->update([
                'task_id' => $task->id,
                'min_quantity' => $minQuantity,
                'required_count' => $requiredCount,
            ]);
        }

        $this->command?->info('Seeded '.count(PluginTestSet::TARGETS)." squares — visit /events/{$event->id}");
    }
}
