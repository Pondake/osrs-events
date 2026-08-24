<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Throwable;

/**
 * Generate the keypair that identifies this server to every push service.
 *
 * **One pair per environment, generated once, never rotated on a live one.**
 * Every subscription a browser hands out is bound to the public key it saw at
 * subscribe time; replacing the pair invalidates every registered device at a
 * stroke, and does so invisibly — pushes to the stale subscriptions are still
 * accepted by the push service and simply never arrive. That is why this
 * refuses to overwrite without --force, and says so rather than just doing it.
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid {--force : Overwrite existing keys. Invalidates every registered device.}';

    protected $description = 'Generate a VAPID keypair for Web Push';

    public function handle(): int
    {
        $existing = config('webpush.vapid.public_key');

        if (filled($existing) && ! $this->option('force')) {
            $this->error('VAPID keys are already configured for THIS environment.');
            $this->line('');
            // The command itself writes nothing — it prints. Saying so is the
            // difference between a useful guard and a wall: generating a pair
            // for another environment from your laptop is a completely normal
            // thing to do, and the laptop's own keys are no reason to refuse.
            $this->line('  This command only prints a keypair; it never writes to .env. The');
            $this->line('  danger is in the pasting, not the running.');
            $this->line('');
            $this->line('  Generating a pair for a DIFFERENT environment (staging, production)?');
            $this->line('  Pass --force and paste the output into that environment only.');
            $this->line('');
            $this->line('  Replacing the keys THIS environment already uses invalidates every');
            $this->line('  device that has subscribed to it — and does so invisibly, since');
            $this->line('  pushes to the old subscriptions keep being accepted and simply never');
            $this->line('  arrive. Run `php artisan push:doctor` first to see how many that is.');

            return self::FAILURE;
        }

        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $error) {
            $this->error('Could not generate a keypair: '.$error->getMessage());

            // The Windows special: generating a P-256 key goes through
            // OpenSSL, which fails when it cannot find its config file — and
            // on Windows that is the default state. The underlying error is a
            // bare "Unable to create the key", which names neither OpenSSL
            // nor the config file, so without this hint it costs half an hour
            // every single time.
            if (PHP_OS_FAMILY === 'Windows' && ! getenv('OPENSSL_CONF')) {
                $this->line('');
                $this->warn('OPENSSL_CONF is not set, which is the usual cause on Windows.');
                $this->line('  Try:  $env:OPENSSL_CONF = "$HOME\.config\herd\openssl.cnf"; php artisan webpush:vapid');
            }

            return self::FAILURE;
        }

        $this->info('VAPID keypair generated. Add these to your .env:');
        $this->line('');
        $this->line('VAPID_SUBJECT="mailto:you@example.com"');
        $this->line('VAPID_PUBLIC_KEY="'.$keys['publicKey'].'"');
        $this->line('VAPID_PRIVATE_KEY="'.$keys['privateKey'].'"');
        $this->line('');
        $this->comment('The private key is a credential — backend .env only, never the frontend bundle.');
        $this->comment('VAPID_SUBJECT must be a mailto: or https: URL. Apple rejects anything else.');
        $this->line('');
        $this->comment('Queue workers hold the environment they booted with: restart them after');
        $this->comment('adding these, or every send will keep reporting "skipped".');

        return self::SUCCESS;
    }
}
