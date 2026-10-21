<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Tells IndexNow (Bing, Yandex and the engines that share it) about every URL
 * in the sitemap. Run it after a deploy that changed public pages — not on a
 * schedule: resubmitting unchanged URLs is exactly what the protocol asks
 * sites not to do.
 */
class SubmitIndexNow extends Command
{
    protected $signature = 'seo:indexnow
        {--dry-run : List the URLs that would be submitted, and send nothing}';

    protected $description = 'Submit the sitemap URLs to IndexNow';

    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public function handle(SitemapController $sitemap): int
    {
        $urls = array_column($sitemap->entries(), 'loc');

        if ($this->option('dry-run')) {
            foreach ($urls as $url) {
                $this->line($url);
            }

            return self::SUCCESS;
        }

        $key = config('services.indexnow.key');

        if (blank($key)) {
            $this->error('INDEXNOW_KEY is not set.');

            return self::FAILURE;
        }

        $response = Http::timeout(15)->post(self::ENDPOINT, [
            'host' => parse_url(config('app.url'), PHP_URL_HOST),
            'key' => $key,
            'keyLocation' => route('indexnow.key'),
            'urlList' => $urls,
        ]);

        // 200 and 202 both mean accepted; 202 is "key not verified yet".
        if (! in_array($response->status(), [200, 202], true)) {
            $this->error("IndexNow answered {$response->status()}: {$response->body()}");

            return self::FAILURE;
        }

        $this->info('Submitted '.count($urls)." URLs ({$response->status()}).");

        return self::SUCCESS;
    }
}
