<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <meta name="theme-color" content="#1c1919">
        <title>{{ __('errors.maintenance_heading') }} — {{ config('app.name') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Cinzel+Decorative:wght@700&display=swap">
        {{-- Inline on purpose: this page is pre-rendered by `artisan down` and
             served while the app and its bundles are half-deployed, so it can
             depend on nothing that a deploy replaces. --}}
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            html { color-scheme: dark; }
            body {
                margin: 0;
                min-height: 100vh;
                min-height: 100dvh;
                display: grid;
                place-items: center;
                padding: 24px 16px;
                background-color: #1c1919;
                background-image:
                    radial-gradient(60rem 40rem at 15% -10%, rgba(224, 118, 47, .13), transparent 60%),
                    radial-gradient(50rem 38rem at 88% 8%, rgba(212, 163, 62, .11), transparent 62%),
                    linear-gradient(rgba(255, 255, 255, .011) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, .011) 1px, transparent 1px);
                background-size: auto, auto, 4px 4px, 4px 4px;
                color: #a8a29e;
                font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
                line-height: 1.5;
            }
            .panel {
                width: 100%;
                max-width: 36rem;
                padding: 40px 24px;
                text-align: center;
                background-color: #1c1919;
                background-image: linear-gradient(180deg, rgba(255, 207, 92, .05), transparent 45%);
                border: 1px solid #44403c;
                border-radius: 12px;
                box-shadow:
                    inset 0 1px 0 rgba(255, 207, 92, .16),
                    inset 0 0 0 1px rgba(0, 0, 0, .35),
                    0 24px 60px -20px rgba(0, 0, 0, .8);
            }
            @media (min-width: 640px) { .panel { padding: 56px 40px; } }
            .logo { width: 56px; height: 56px; display: block; margin: 0 auto; }
            .code {
                margin: 24px 0 0;
                font-family: 'Cinzel Decorative', Georgia, serif;
                font-size: 4.5rem;
                font-weight: 700;
                line-height: 1;
                letter-spacing: -.025em;
                color: #d4a33e;
            }
            @media (min-width: 640px) { .code { font-size: 6rem; } }
            h1 {
                margin: 24px 0 0;
                font-family: 'Cinzel', Georgia, serif;
                font-size: 1.75rem;
                line-height: 1.2;
                color: #fafaf9;
            }
            p.body { margin: 12px 0 0; }
            p.note { margin: 24px 0 0; font-size: .875rem; color: #9a938c; }
        </style>
    </head>
    <body>
        <main class="panel">
            <svg class="logo" viewBox="0 0 16 16" shape-rendering="crispEdges" role="img" aria-label="{{ config('app.name') }}">
                <rect x="1" y="9" width="1" height="4" fill="#c15c1f" />
                <rect x="2" y="9" width="1" height="1" fill="#c15c1f" />
                <rect x="2" y="12" width="1" height="1" fill="#c15c1f" />
                <rect x="3" y="7" width="5" height="8" fill="#e0762f" />
                <rect x="3" y="7" width="1" height="8" fill="#f09a4e" />
                <rect x="3" y="14" width="5" height="1" fill="#b4501a" />
                <rect x="3" y="4" width="5" height="2" fill="#ffcf5c" />
                <rect x="15" y="9" width="1" height="4" fill="#9a721e" />
                <rect x="14" y="9" width="1" height="1" fill="#9a721e" />
                <rect x="14" y="12" width="1" height="1" fill="#9a721e" />
                <rect x="9" y="7" width="5" height="8" fill="#d4a33e" />
                <rect x="9" y="7" width="1" height="8" fill="#eec257" />
                <rect x="9" y="14" width="5" height="1" fill="#9a721e" />
                <rect x="9" y="4" width="5" height="2" fill="#f2d894" />
                <rect x="7" y="1" width="2" height="2" fill="#ffcf5c" />
                <rect x="4" y="2" width="1" height="1" fill="#e0762f" />
                <rect x="11" y="2" width="1" height="1" fill="#d4a33e" />
            </svg>
            <p class="code" aria-hidden="true">503</p>
            <h1>{{ __('errors.maintenance_heading') }}</h1>
            <p class="body">{{ __('errors.updating_body') }}</p>
            <p class="note">{{ __('errors.updating_note') }}</p>
        </main>
    </body>
</html>
