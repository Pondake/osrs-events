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
        {{-- Favicons — transparent, monochrome trophy. favicon.svg adapts to
             light/dark tab chrome; the .ico/PNGs are the legacy fallback. --}}
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.webmanifest">

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
