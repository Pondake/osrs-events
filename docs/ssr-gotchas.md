# SSR gotchas

Sixteen render-related traps, every one of them hit for real on this branch and
fixed once. They are easy to reintroduce, which is why they live in their own
file rather than scrolling away inside a backlog — `CLAUDE.md` points here, and
reading it before touching anything render-related is the point.

Extracted 2026-08-30 from `docs/backlog.md` when that file was archived; the
list itself is unchanged.

---

1. Nuxt UI icons render empty in server HTML (fill in after client
   hydration) — fine for Googlebot, invisible to non-JS crawlers/scrapers.
2. JSON-LD through Inertia's documented `<Head><script v-html>` pattern
   serializes as an `innerHTML="..."` HTML *attribute* during SSR, not the
   tag's text content — invisible to any JSON-LD validator. Route JSON-LD
   through Blade view data instead.
3. Laravel 11+'s `@context` Blade directive collides with the literal
   `@context` key every JSON-LD block requires, even inside `{!! !!}` raw
   PHP — Blade's directive compiler is a text-level regex with no
   PHP-string awareness. Build JSON-LD strings in the controller, never
   inline in a `.blade.php` file.
4. Site-name title suffix must live in exactly one place. Setting it in
   both the `createInertiaApp` title callback and a page's own `<title>`
   double-applies on client hydration (SSR output looks fine; the client
   re-templates on top of it).
5. **The dangerous one**: `<Head>` used only in a `<template>`, never
   referenced in `<script>`, silently fails to auto-import via `@nuxt/ui`'s
   Vite plugin — ships zero meta tags, with a warning that only prints to
   the SSR Node process's own stderr. Nothing in the browser console,
   nothing in a production build. Always `import { Head } from
   '@inertiajs/vue3'` explicitly; don't rely on auto-import for it.
6. Any `@nuxt/ui` composable reached through `@nuxt/ui/composables`
   (including its own barrel `index.js`, which does `export * from
   './useComponentIcons.js'`) statically imports a virtual `#imports`
   specifier that only resolves through the `ui()` Vite plugin's
   bundler-time pipeline. Vite's SSR build externalizes node_modules deps
   by default, bypassing that pipeline — so importing `useToast` (or
   anything else from that directory) crashed the **entire SSR process at
   startup**, for every page, not just ones that use it. Forcing
   `@nuxt/ui` to bundle instead (`ssr.noExternal`) trades that crash for a
   worse, silent one: every page's SSR output becomes an empty `<div
   id="app">` with no error anywhere, because `@nuxt/ui`'s own BUILD-TIME
   code gets bundled into the runtime and breaks Vue's component
   resolution globally. The real fix: keep anything reaching
   `useComponentIcons.js` (interactive form components —
   u-select/u-switch/u-modal/u-tabs — and any `@nuxt/ui/composables`
   import) out of the SSR module graph entirely, via a `<ClientOnly>`
   wrapper (`resources/js/Components/ClientOnly.vue`) + `defineAsyncComponent`
   for the modal, and a dynamic `import()` inside `onMounted()` for
   `useToast`. Components with zero SEO value when closed/unopened (a
   modal, a toast) cost nothing by skipping SSR — don't fight the bundler
   over them.
7. Don't name a local variable `page` in a component that also declares a
   `page` **prop**, in the same `<script setup>` scope. `AppRoot.vue`'s
   props include `page` (the Vue component Inertia wants rendered, used in
   the template as `:is="page"`) — adding `const page = usePage()`
   (Inertia's reactive page-STATE object, an unrelated thing with an
   obvious matching name) shadowed the prop. The template's `:is="page"`
   then resolved to the state object instead of the component, and
   silently rendered nothing — the empty-`<div id="app">` symptom above
   had two independent causes, and this one had nothing to do with
   `@nuxt/ui` at all.
8. Ziggy's `route()` needs its route-definition config explicitly shared
   as an Inertia prop (`'ziggy' => fn () => (new Ziggy)->toArray()` in
   `HandleInertiaRequests`) for SSR to work. The `@routes` Blade directive
   only writes a `<script>` tag for the *browser's* `window.Ziggy` — the
   Node SSR process never sees it, so calling `route()` from any
   server-rendered page throws `Cannot read properties of undefined
   (reading 'login')` deep inside `ziggy-js`, crashing that page's entire
   render. (Note the correct import is `Tighten\Ziggy\Ziggy` —
   `tightenco/ziggy`'s composer package name doesn't match its own PHP
   namespace.)
9. `route()` (Ziggy) is only bound on Vue's `globalProperties` by the
   `ZiggyVue` plugin — usable as a bare identifier directly in a
   `<template>`, but not from plain `<script setup>` JS (a `computed()`
   callback, a function body). `import { route } from 'ziggy-js'` looks
   like the fix but isn't: that named export resolves its OWN Ziggy config
   independently of the plugin instance `ssr.js` explicitly configured
   with `page.props.ziggy`, falling back to a global `Ziggy` variable that
   doesn't exist in Node — reintroducing the exact SSR crash bug #8 fixed,
   just scoped to whichever page's script calls it. Keep every `route()`
   call directly in the template; restructure the component (extra
   `v-if`/`v-else` branches, whatever it takes) rather than hoisting the
   call into script.
10. Cast every `datetime`-shaped column on every model, even ones that look
   obviously date-like. `PlayerBoard::$casts` didn't include
   `last_roll_date`, so `Auth`-flow-verified, curl-verified SSR output
   looked completely correct right up until the dice-roll button was
   actually clicked in a real browser — `$playerBoard->last_roll_date
   ?->isToday()` threw `Call to a member function isToday() on string`,
   a 500 with zero SSR/Vue involvement at all. A reminder that curling SSR
   HTML only proves the *read* path works — mutations need their own
   end-to-end check, ideally by verifying the actual DB row changed, not
   just that the request returned 200.
11. Define every relation a controller will eager-load, even obvious ones.
   `PlayerBoard` had `user()`/`board()`/`completedTiles()` but no `team()`
   — invisible until `LeaderboardController` tried `->with(['user',
   'team'])` and got `RelationNotFoundException`. Same lesson as #10: this
   had nothing to do with SSR or Vue, a plain Eloquent gap that curling a
   *different* page's SSR output would never have caught.
12. Not a bug — a testing-methodology trap worth recording anyway, since it
   burned real time: Reka UI's `Tabs` (underneath `@nuxt/ui`'s `u-tabs`)
   requires the tab trigger to actually hold DOM focus
   (`document.activeElement`) before it registers a click. A synthetic
   `el.click()` — even a full `pointerdown`/`mousedown`/`pointerup`/
   `mouseup`/`click` event sequence — silently no-ops without `el.focus()`
   first. Looked exactly like an app bug (tabs appearing to not switch at
   all) until `el.focus(); el.click()` fixed it instantly. When a browser
   tool test shows an interactive element doing nothing, try focusing it
   explicitly before concluding the app itself is broken.
13. Don't make a list passed to `u-tabs`' `:items` a `computed()` if it can
   be static. Chased gotcha #12 above down the wrong path first: reactive
   `tabs` depending on other component state seemed like the natural
   explanation for tabs "not switching," since a new array reference on
   every render is a real Reka UI reset trigger in general — it just
   wasn't the actual cause here. Left as a static array with content-level
   `v-if` gating instead, which sidesteps the question rather than
   resolving it either way.
14. **A leftover `public/hot` silently turns SSR off entirely.** Laravel
   writes that file while `pnpm dev` runs and deletes it on a clean exit;
   if the dev server is killed, or simply left running, the file stays.
   Inertia's `HttpGateway` checks `Vite::isRunningHot()` FIRST and, when
   hot, posts to `INERTIA_SSR_HOT_URL` — which nothing here sets — so the
   request fails and SSR falls back to client rendering. The fallback is
   deliberately silent (that's the documented behaviour on any SSR error),
   so the only symptom is `<div id="app"></div>` in view-source while the
   page looks perfect in a browser. Found this way: the SSR process was
   healthy on :13714 and answering /health with 200, and pages still
   shipped 55KB of empty shell instead of 134KB of rendered markup. Check
   view-source, not the browser, when verifying anything SEO-related — and
   `ls public/hot` before concluding SSR is broken. Corollary: while the
   dev server is up, every "verification" runs against HMR source and
   never exercises the built bundle at all.
15. `php artisan serve` cannot serve an SSE stream and anything else at the
   same time. PHP's built-in server is single-threaded, and
   `PHP_CLI_SERVER_WORKERS` forks, so it does nothing on Windows. One open
   `EventSource` holds the only worker for the full 45s stream, and every
   other request — including a second stream — queues behind it. Symptom:
   a page whose own stream works, plus console `error` events, plus any
   other tab hanging. Not an app bug; use Herd/nginx+fpm when working on
   live-channel pages, and mutate via `artisan tinker` (its own process)
   rather than a second HTTP request when testing a push end to end.
16. **A deploy that rebuilds the SSR bundle but does not restart the SSR
   process keeps serving the previous build, forever.** The process loads
   `bootstrap/ssr/ssr.js` once at startup; nothing re-reads it. Ploi's
   "Restart NodeJS server after deployment" checkbox was ticked and did
   not do it, so the deploy script now kills the process by full script
   path (`pkill -f .../bootstrap/ssr/ssr.js`) and lets supervisor respawn
   it — by path rather than supervisor program name, since the path is
   unique to the site and needs no knowledge of Ploi's naming.
   Caught after renaming a route: pages whose stale compiled SSR still
   called `route('login')` threw inside ziggy (unknown name), Inertia fell
   back to client rendering, and exactly those pages shipped an empty
   `<div id="app">` — 44KB and no `<title>` — while every page that did
   not reference the renamed route rendered perfectly. A partial SSR
   outage looks like nothing at all in a browser, and the give-away is a
   page's byte count and title count, not its appearance.
