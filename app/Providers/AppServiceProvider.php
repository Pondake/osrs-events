<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registers the "discord" driver with Socialite — it's not a
        // first-party Socialite provider, socialiteproviders/discord adds it
        // via this event instead of Socialite's own service provider.
        Event::listen(SocialiteWasCalled::class, [DiscordExtendSocialite::class, 'handle']);

        RateLimiter::for('runelite-plugin', fn (Request $request) => Limit::perMinute(60)
            ->by($request->attributes->get('plugin_token_id') ?? $request->ip()));
    }
}
