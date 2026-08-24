<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Everything below this line is what the SSR-parity test cares
             about: title/meta/canonical/JSON-LD emitted by each page's own
             <Head> block (see Pages/SnakesAndLadders.vue), collected during
             the server render and injected here by @inertiaHead — not
             appended client-side after hydration. --}}
        {{-- Home-screen icons come in two flavours: the clean brand mark on
             production, and an amber blueprint/hazard version everywhere else,
             so a phone with both installed shows which one it is opening. See
             config/app.php's 'icon_flavor' for why APP_ENV decides this and why
             the unknown case falls to dev. --}}
        @php
            $isProdIcons = config('app.icon_flavor') === 'production';
            $iconAppName = config('app.name').($isProdIcons ? '' : ' (dev)');
        @endphp

        {{-- Favicons — transparent, monochrome toasting mugs. favicon.svg adapts to
             light/dark tab chrome; the .ico/PNGs are the legacy fallback.
             Deliberately NOT varied per flavour: there is no background to put a
             texture on at 16px, and the tab already shows the URL. --}}
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ $isProdIcons ? '/apple-touch-icon.png' : '/apple-touch-icon-dev.png' }}">
        {{-- crossorigin, even though the manifest is same-origin and served
             as a static file. The manifest is fetched with credentials
             OMITTED by default, so behind any auth gate (a password-protected
             preview, an SSO proxy) the browser gets the gate's login HTML
             instead, fails to parse it, and silently downgrades "Install app"
             to a plain bookmark — while the page itself loads perfectly,
             because THAT request does carry the cookie. It is a no-op here
             and cheap insurance against the day it is not; on iOS an app that
             cannot be installed is an app that cannot receive notifications
             at all. --}}
        <link rel="manifest" href="{{ $isProdIcons ? '/manifest.webmanifest' : '/manifest.dev.webmanifest' }}" crossorigin="use-credentials">

        <meta name="theme-color" content="#1c1919">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        {{-- iOS reads its home-screen label from here, not from the manifest,
             so the "(dev)" suffix has to be repeated for it to differ there. --}}
        <meta name="apple-mobile-web-app-title" content="{{ $iconAppName }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="application-name" content="{{ $iconAppName }}">
        <meta name="msapplication-TileColor" content="#1c1919">

        {{-- Google Fonts — Cinzel and Cinzel Decorative for OSRS-style headings --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cinzel+Decorative:wght@400;700&display=swap">

        @routes
        @inertiaHead

        {{-- See LandingController::snakesAndLadders() for why JSON-LD is
             shared as plain Blade view data instead of going through
             Inertia's <Head> component. --}}
        @isset($jsonLd)
            @foreach ($jsonLd as $block)
                <script type="application/ld+json">{!! $block !!}</script>
            @endforeach
        @endisset

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
