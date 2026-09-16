<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

/**
 * A short-lived signed link that signs the browser in as a local account,
 * so a walkthrough never has to type a password into the login form.
 *
 * The route only exists in the local environment, see routes/web.php.
 */
class DevLoginLink extends Command
{
    protected $signature = 'dev:login-link
        {account : discord_username or email}
        {--base=http://localhost:8010 : origin the browser uses}';

    protected $description = 'Print a 10-minute signed sign-in link for a local account (local only)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('dev:login-link only runs locally.');

            return self::FAILURE;
        }

        $account = $this->argument('account');
        $user = User::where('discord_username', $account)->orWhere('email', $account)->first();

        if ($user === null) {
            $this->error("No account with discord_username or email \"{$account}\".");

            return self::FAILURE;
        }

        URL::forceRootUrl($this->option('base'));
        $this->line(URL::temporarySignedRoute('dev.login', now()->addMinutes(10), ['user' => $user->id]));

        return self::SUCCESS;
    }
}
