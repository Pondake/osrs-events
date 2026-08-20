<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // osrs-events' only login path — see app/Http/Controllers/Auth/DiscordController.php
    // and the socialiteproviders/discord event listener in AppServiceProvider::boot().
    'discord' => [
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect' => env('DISCORD_REDIRECT_URI'),
    ],

    // Wise Old Man — the OSRS progress tracker skill-race standings are read
    // from. See app/Services/WiseOldManService.php. No key is required; one
    // raises the rate limit from 20 to 100 requests a minute.
    //
    // They ask API consumers to identify themselves with a contact address in
    // the User-Agent. The default below is a fallback, not a good one — set
    // WOM_USER_AGENT for any real deployment.
    'wom' => [
        'api_key' => env('WOM_API_KEY'),
        'user_agent' => env('WOM_USER_AGENT', 'osrs-events'),
    ],

];
