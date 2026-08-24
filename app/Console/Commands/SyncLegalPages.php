<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Support\LegalPages;
use Illuminate\Console\Command;

/**
 * Writes `/privacy` and `/terms` from the repository onto an existing site.
 *
 * PageSeeder plants these on a fresh install and then never touches them again
 * — it uses firstOrCreate, deliberately, so re-seeding cannot flatten a page an
 * admin has edited. That is right for every other page and wrong for these
 * two: they have to describe what the code actually stores, the code changes
 * far more often than a policy gets reread, and "the seeder has the correct
 * text" is worth nothing on an environment where the row already exists.
 *
 * So this exists as the deliberate second route, and it is deliberately not
 * automatic. It **overwrites** the page body, which would discard edits made
 * through admin → Content, so it is a thing somebody runs on purpose rather
 * than something a deploy does behind their back. `--diff` shows what would
 * change without touching anything.
 */
class SyncLegalPages extends Command
{
    protected $signature = 'pages:sync-legal
        {--diff : Show which pages would change, and write nothing}
        {--slug=* : Limit to these slugs (default: privacy and terms)}';

    protected $description = 'Rewrite the privacy and terms pages from the copy kept in the repository';

    public function handle(): int
    {
        $slugs = $this->option('slug') ?: LegalPages::SLUGS;

        $unknown = array_diff($slugs, LegalPages::SLUGS);

        if ($unknown !== []) {
            $this->error('Not a legal page: '.implode(', ', $unknown));
            $this->line('  Known: '.implode(', ', LegalPages::SLUGS));

            return self::FAILURE;
        }

        $changed = 0;
        $missing = 0;

        foreach ($slugs as $slug) {
            $page = Page::where('slug', $slug)->first();

            if ($page === null) {
                // Not an error worth failing on: a database that has never run
                // PageSeeder legitimately has no row here, and the fix is the
                // seeder rather than this.
                $this->warn("  {$slug}: no page row — run `php artisan db:seed --class=PageSeeder` first.");
                $missing++;

                continue;
            }

            $wanted = LegalPages::blocksFor($slug);

            // Compared as encoded JSON rather than with ==, because the stored
            // value has been through a json cast and back: key order survives,
            // but PHP's loose array comparison would call a reordered nested
            // array equal and report "no change" on a real one.
            if (json_encode($page->blocks) === json_encode($wanted)) {
                $this->line("  <info>=</info> {$slug}: already matches");

                continue;
            }

            $changed++;

            if ($this->option('diff')) {
                $this->line("  <comment>~</comment> {$slug}: would be rewritten "
                    ."({$this->countBlocks($page->blocks)} blocks → {$this->countBlocks($wanted)})");

                continue;
            }

            $page->update(['blocks' => $wanted]);
            $this->line("  <comment>~</comment> {$slug}: rewritten");
        }

        $this->line('');

        if ($this->option('diff')) {
            $this->info($changed === 0
                ? 'Nothing to do — both pages already match the repository.'
                : "{$changed} page(s) would change. Run without --diff to apply.");

            return self::SUCCESS;
        }

        $this->info($changed === 0 ? 'Nothing to do.' : "Rewrote {$changed} page(s).");

        if ($changed > 0) {
            $this->comment('Anything edited through admin → Content on those pages has been replaced.');
        }

        // Missing rows are reported but not fatal — see above.
        return $missing > 0 && $changed === 0 ? self::FAILURE : self::SUCCESS;
    }

    /** Counts nested blocks too, since a section carries its own. */
    private function countBlocks(?array $blocks): int
    {
        return collect($blocks ?? [])
            ->sum(fn (array $block) => 1 + $this->countBlocks($block['blocks'] ?? []));
    }
}
