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
