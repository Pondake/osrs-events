<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
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

        // The password reset in the same template as every other mail, and in
        // our own words: Laravel's stock text opens with "Hello!" and "You are
        // receiving this email because we received a password reset request",
        // which reads like the phishing it is meant to be the opposite of.
        // Same notification class, same link, only the message changes — so
        // the expiry and the URL are built exactly as ResetPassword does.
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject(trans('mail.reset_subject'))
                ->greeting(trans('notifications.greeting', ['name' => $notifiable->displayName()]))
                ->line(trans('mail.reset_line'))
                ->action(trans('mail.reset_action'), $url)
                ->line(trans('mail.reset_expiry', ['minutes' => $minutes]))
                ->line(trans('mail.reset_ignore'))
                ->markdown('mail.notice', [
                    'hero' => [
                        'tone' => 'account',
                        'label' => trans('mail.reset_label'),
                        'title' => trans('mail.reset_title'),
                    ],
                    'preheader' => trans('mail.reset_expiry', ['minutes' => $minutes]),
                    'reason' => trans('mail.reset_footer'),
                ]);
        });

        RateLimiter::for('runelite-plugin', fn (Request $request) => Limit::perMinute(60)
            ->by($request->attributes->get('plugin_token_id') ?? $request->ip()));
    }
}
