<?php

return [

    /*
    |---------------------------------------------------------------------
    | VAPID
    |---------------------------------------------------------------------
    |
    | The keypair that identifies this server to every push service. Generate
    | one pair PER ENVIRONMENT with `php artisan webpush:vapid`.
    |
    | Never rotate these on a live environment. Every subscription a browser
    | hands out is bound to the public key it saw at subscribe time, so
    | replacing the pair silently invalidates every registered device — and
    | the failure is invisible, because a push to a stale subscription is
    | still accepted by the push service. The generator refuses to overwrite
    | without --force for exactly this reason.
    |
    | The private key is a credential and belongs in .env only. The public key
    | is served to the browser by the API (GET /push/public-key) rather than
    | compiled into the frontend bundle, so the two cannot drift apart across
    | separate deploys.
    |
    | `subject` must be a mailto: or https: URL. Some push services — Apple's
    | in particular — reject anything else outright, and the rejection does
    | not say which field was wrong.
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', config('app.url')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |---------------------------------------------------------------------
    | Delivery
    |---------------------------------------------------------------------
    |
    | `ttl` is how long a push service should hold a message for a device
    | that is offline. Twelve hours: a "your rolls are back" that surfaces
    | two days later is worse than one that never arrives.
    |
    | `timeout` is explicit on purpose. Some versions of the send library only
    | apply their own default when one is actually passed, and leaving it null
    | lets a single unresponsive push service hold a queue worker open with no
    | bound at all.
    |
    | `urgency` normal lets a phone batch delivery with its own wake cycles
    | rather than waking the radio per message.
    |
    */

    'ttl' => (int) env('WEBPUSH_TTL', 43200),
    'timeout' => (int) env('WEBPUSH_TIMEOUT', 10),
    'urgency' => env('WEBPUSH_URGENCY', 'normal'),

];
