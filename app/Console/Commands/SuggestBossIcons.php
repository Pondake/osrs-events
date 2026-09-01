<?php

namespace App\Console\Commands;

use App\Models\BossIcon;
use App\Services\BossIconService;
use App\Services\OsrsWikiService;
use Illuminate\Console\Command;

/**
 * Looks for an icon for every boss that has none, and proposes it.
 *
 * **Proposes, never applies.** The backlog set that rule before any of this
 * was built: an automatic import earns its place only once the mapping has
 * been right by hand for a while, and a wrong icon on a live event page is
 * worse than a blank one. So this writes `suggested_url` and stops; a human
 * approves it on /admin/boss-icons.
 *
 * What it searches for is the boss's own name, not its pet's — the pet's name
 * is exactly the thing nobody knows for a boss that shipped last week, which
 * is why these are blank in the first place. The wiki's top image for
 * "Mad Angel" may be the boss rather than Aggy, and that is fine: the person
 * approving can see the picture, and dismissing costs one click.
 *
 * A dismissed URL is remembered so the same rejected image does not come back
 * every week. A different one for the same boss still can.
 *
 *     php artisan boss-icons:suggest          # propose, write nothing else
 *     php artisan boss-icons:suggest --dry-run
 */
class SuggestBossIcons extends Command
{
    protected $signature = 'boss-icons:suggest {--dry-run : Report what it would propose, write nothing}';

    protected $description = 'Propose wiki images for bosses that have no icon';

    public function handle(BossIconService $icons, OsrsWikiService $wiki): int
    {
        $all = collect($icons->all());

        $proposed = 0;
        $skipped = 0;

        // The package catching up, noticed at runtime without reading the
        // package. The extraction script writes a PNG when a pet finally
        // ships; this sees the file appear and offers to hand the boss back
        // to it. A hand-set wiki image stays in force until somebody agrees,
        // because an override was a decision and the package is not entitled
        // to overrule it silently.
        foreach ($all->where('source', 'custom') as $entry) {
            $metric = $entry['metric'];
            $sprite = $icons->spriteUrl($metric);

            if ($sprite === null || $entry['suggested'] !== null) {
                continue;
            }

            $row = BossIcon::where('metric', $metric)->first();

            if ($row?->dismissed_url === $sprite) {
                continue;
            }

            $this->line("  {$metric}: the package now ships a pet sprite");

            if (! $this->option('dry-run')) {
                $row->update(['suggested_url' => $sprite]);
            }

            $proposed++;
        }

        $missing = $all->where('source', 'none');

        if ($missing->isEmpty()) {
            $this->info($proposed === 0
                ? 'Every boss has an icon. Nothing to propose.'
                : "Proposed {$proposed}. Approve them at /admin/boss-icons.");

            return self::SUCCESS;
        }

        $this->info("{$missing->count()} boss(es) without an icon.");

        foreach ($missing as $entry) {
            $metric = $entry['metric'];
            $existing = BossIcon::where('metric', $metric)->first();

            // Already waiting on somebody. Proposing again would only reset
            // the queue somebody is working through.
            if ($existing?->suggested_url) {
                $skipped++;

                continue;
            }

            $label = trans("bosses.{$metric}");
            $term = $label === "bosses.{$metric}" ? str_replace('_', ' ', $metric) : $label;

            // The service caches per term and answers [] on failure, so an
            // outage costs suggestions rather than the run.
            $hit = collect($wiki->search($term, 5))->first(fn ($page) => ! empty($page['icon_url']));

            if ($hit === null) {
                $this->line("  {$term}: nothing on the wiki with an image");
                $skipped++;

                continue;
            }

            if ($existing?->dismissed_url === $hit['icon_url']) {
                $this->line("  {$term}: same image was already turned down");
                $skipped++;

                continue;
            }

            $this->line("  {$term}: {$hit['title']}");

            if (! $this->option('dry-run')) {
                BossIcon::updateOrCreate(
                    ['metric' => $metric],
                    ['suggested_url' => $hit['icon_url']],
                );
            }

            $proposed++;
        }

        $this->info($this->option('dry-run')
            ? "Would propose {$proposed}, skip {$skipped}."
            : "Proposed {$proposed}, skipped {$skipped}. Approve them at /admin/boss-icons.");

        return self::SUCCESS;
    }
}
