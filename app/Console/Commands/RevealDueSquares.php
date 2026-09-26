<?php

namespace App\Console\Commands;

use App\Models\BingoCard;
use App\Services\BingoService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Draws the next square on every bingo card whose reveal timer is due.
 *
 * The draw itself is the same one a host's button makes, so the limit, the
 * lock and the live update all come with it. See BingoService::revealDue().
 */
class RevealDueSquares extends Command
{
    protected $signature = 'bingo:reveal-due';

    protected $description = 'Reveal the next square on every bingo card whose timer is due';

    public function handle(BingoService $bingo): int
    {
        $cards = BingoCard::query()
            ->where('reveal', true)
            ->whereNotNull('reveal_every_minutes')
            ->whereHas('event', fn ($q) => $q->whereNull('closed_at'))
            ->with('event')
            ->get();

        $drawn = 0;

        foreach ($cards as $card) {
            try {
                if ($bingo->revealDue($card->event, $card) && $bingo->revealNext($card->event, $card) !== null) {
                    $drawn++;
                }
            } catch (Throwable $e) {
                $this->error("  {$card->event->title}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Revealed {$drawn} square(s) across {$cards->count()} timed card(s).");

        return self::SUCCESS;
    }
}
