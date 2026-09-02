# OSRS Events — Claude Rules

This repo migrated from NestJS+Nuxt to Laravel+Inertia on the
`experiment/laravel-stack` branch. The old stack used to sit on disk at
`stale/` for reference; it was **deleted 2026-08-20** once the migration was
verified complete. Comments across the codebase still cite paths inside it
("Ported from stale/frontend/...") — those are provenance notes about where a
behaviour came from, not files you can open.

## Model selection
- **Haiku** — mechanical tasks: adding i18n keys, renaming, small field additions, fixing typos, one-liner refactors
- **Sonnet** — complex tasks: new features, multi-file refactors, architectural decisions, debugging subtle issues, schema changes

## Package manager
Always **pnpm** for JS tooling, **composer** for PHP. Never `npm`, `npx`, or `yarn`.
```
pnpm dev            # vite dev server (client HMR)
pnpm test           # vitest over tests/js (frontend)
php artisan test    # phpunit over tests/Feature (backend)
pnpm build          # client bundle -> public/build
pnpm exec vite build --ssr   # SSR bundle -> bootstrap/ssr/ssr.js
php artisan serve --port=<port>
node bootstrap/ssr/ssr.js    # Inertia SSR server, port 13714
php artisan migrate
composer install
```
After any frontend change, both `pnpm build` and `pnpm exec vite build --ssr`
must be rerun and the SSR node process restarted — it's a long-running
process that loads the bundle once at startup, not per-request.

## Repository layout
```
osrs-events/            Laravel app lives at repo root (Herd serves osrs-events.test)
├── app/                 Http/Controllers, Http/Middleware, Models, Services
├── resources/
│   ├── js/
│   │   ├── Pages/       Inertia page components (PascalCase, one per route)
│   │   ├── Components/  shared Vue components
│   │   ├── Composables/ useAuth.js, useSeo.js
│   │   ├── Support/     plain JS helpers (board.js)
│   │   ├── app.js       client entry
│   │   ├── ssr.js       SSR entry
│   │   └── AppRoot.vue  root component — header/page/footer, flash-to-toast bridge
│   ├── css/app.css       Tailwind + Nuxt UI imports, OSRS theme CSS
│   └── views/app.blade.php  root Blade shell — favicons, fonts, @inertiaHead
├── routes/web.php
├── ui.config.ts          Nuxt UI theme (colors, component overrides) — wired into vite.config.js
├── lang/en.json           flat dotted-key translations (see i18n below)
├── docs/backlog.md         living priority list — only what is still open
├── docs/ideas.md           thought through, not scheduled
├── docs/ssr-gotchas.md     sixteen render traps, every one hit for real
├── docs/discord.md         local notes — always gitignored
└── docs/backlog-archive-2026-08.md   the old backlog, kept whole
```

Stack: Laravel 13 · Inertia.js v2 · Vue 3 · `@nuxt/ui` v4 · Tailwind v4 ·
Eloquent/PostgreSQL · Laravel Socialite (Discord OAuth).

### Which doc gets what — decide this, don't invent a new file

Reorganised 2026-08-30, when the old backlog hit 5432 lines and its open work
became unfindable. The split only holds if new findings land in the right one,
so: **write it into one of these, never into a new document.**

| It is… | It goes in |
|---|---|
| still to do, and will actually be picked up | `docs/backlog.md` |
| a real idea, thought through, not scheduled | `docs/ideas.md` |
| a render/SSR trap that bit us once | `docs/ssr-gotchas.md` |
| a Discord server detail | `docs/discord.md` — always gitignored |
| finished — `[x]` plus what was built | stays put in `docs/backlog.md` |

Three rules that are easy to get wrong:

- **Finished items stay.** Mark `[x]` with a note on what was built and leave
  it in place. A `[x]` is "I built it", which is not the same claim as "it
  works" — only the owner deletes a line, after verifying it. Never tidy them
  away on your own initiative.
- **`docs/backlog-archive-2026-08.md` is closed.** Read it for history and for
  the reasoning behind a decision; don't add to it, don't delete from it. Code
  comments across `app/`, `database/` and `resources/js` that cite
  `docs/backlog.md` as provenance mean that archive — same convention as the
  deleted `stale/` paths.
- **Moving an item between backlog and ideas is normal.** Something that keeps
  getting skipped belongs in `ideas.md`; something whose turn has come moves
  back. That is the mechanism, not a failure of planning.

`docs/PROGRESS.md` and `docs/ROADMAP.md` are the historical record of the old
NestJS/Nuxt stack plus the product roadmap; `docs/legal-review.md`,
`docs/runelite-plugin.md` and `docs/bingo-research.md` are single-subject
references. None of those four are places to file new work.

---

## Vue / TypeScript

### Refs
- Always `ref()`. Only use `reactive()` when there is a specific, justified reason.

### Composables — when to extract
Extract logic to a composable only when:
1. The component script exceeds ~200 lines **and** the logic forms a coherent group, or
2. The logic needs to be shared across multiple components

Do not extract prematurely. Inline logic is fine for small-to-medium components.

### Styling
- Tailwind utility classes throughout. Only write custom CSS when Tailwind genuinely cannot express it (see `resources/css/app.css` for the OSRS-specific exceptions: Cinzel fonts, board-tile snake/ladder styling).
- **Mobile-first**: default styles target mobile, use `sm:`, `lg:` etc. to scale up.
- Theme (brand colors, component slot overrides) lives in `ui.config.ts` at the repo root, wired into `vite.config.js`'s `ui()` plugin call as `ui: uiConfig`. Don't hardcode brand colors in components — extend `ui.config.ts` instead.

### Error handling
- Every `catch` block must include `console.error(error)` at minimum.
- Show a toast only when the failure is **relevant to the user**.
- Auth errors: 401 → redirect to login; 403 → show a forbidden toast/alert.

### Toast IDs
- Every `toast.add()` must include a stable `id` so retries overwrite instead of stacking (e.g. `'board-save'`, `'board-save-error'`).

---

## i18n — ALL user-visible strings must be translated
- Translations live in `lang/en.json` — a **flat file with literal dotted-string keys**
  (e.g. `"boards.title": "Boards"`), not nested objects. This is Laravel's JSON
  translation format via the `laravel-vue-i18n` npm package (not a composer
  package despite the name — pure Vue/Vite).
- **In `<template>`**: use the global property directly — `$t('boards.title')`. No import needed.
- **In `<script setup>`**: `$t` is a Vue globalProperty and is NOT reachable from script —
  `import { trans } from 'laravel-vue-i18n'` and call `trans('key')` instead. This is the
  same class of gotcha as Ziggy's `route()` (see `docs/ssr-gotchas.md`): a
  template-only global helper that silently doesn't exist in script scope.
- **Placeholders use Laravel's `:name` syntax**, not vue-i18n's `{name}` —
  `trans('validation.title_too_long', { max: 100 })` for a string stored as
  `"Title cannot exceed :max characters"`.
- Never hardcode English strings in templates or script.
- Key namespaces: `common.*`, `nav.*`, `auth.*`, `home.*`, `boards.*`, `board.*`,
  `tile_editor.*`, `admin.*`, `profile.*`, `dice.*`, `validation.*`, `leaderboard.*`,
  `teams.*`, `errors.*`, `about.*`, `privacy.*`, `terms.*`, `donate.*`, `seo.*`, `landing.*`.
- Add the key **at the same time** as writing the component — never leave translation as a follow-up task.
- **One exception to "flat JSON only": `lang/en/validation.php`.** Laravel resolves
  the `:attribute` placeholder in validation messages through the PHP loader as
  `validation.attributes.<field>`, and never consults `lang/en.json` for it — so a
  JSON key of that name is silently ignored and the message falls back to the
  humanized column name ("The osrs username field is required"). Field-name
  overrides therefore have to live in that PHP file. Everything else stays in
  `lang/en.json`.
- Longer-term direction (not yet built): most of this copy will eventually move to
  backend-editable content instead of static JSON, with a small set of stationary
  exceptions (account/auth strings, button labels, validation messages) staying in
  `lang/en.json`. Keep using `lang/en.json` for everything until that lands.

## Naming
- Vue component files: **PascalCase** (`BoardSettingsModal.vue`, `AccessGate.vue`)
- Component usage in templates: **kebab-case** (`<board-settings-modal />`)
- Inertia page components: **PascalCase**, under `resources/js/Pages/`, matching the
  controller's `Inertia::render('Boards/Index', ...)` string exactly (including subfolders)
- Composables: **camelCase**, `use` prefix (`useAuth.js`, `useSeo.js`)
- i18n keys: **snake_case** within each dotted namespace

## Create vs edit — always a modal
- Both create and edit use a single `<Entity>SettingsModal` component.
- Create (`entityId = null`): renders `u-stepper` with linear step navigation and per-step validation.
- Edit (`entityId` set): renders `u-tabs` for free navigation between sections.
- Never create a dedicated `/*/create` page for entities that have a modal edit flow.

## SSR — read `docs/ssr-gotchas.md` before touching anything render-related
That list documents real, previously-hit bugs (Nuxt UI's `#imports` barrel crashing SSR
startup, `<ClientOnly>` requirements for interactive `@nuxt/ui` components, Ziggy's
`route()` script-vs-template split, JSON-LD via Blade not Inertia's `<Head>`, etc.).
Re-reading it before adding new SSR-rendered UI will save real debugging time.

---

## Backend conventions (Laravel)

### Module structure
Each domain gets a Controller under `app/Http/Controllers/`, a Model under `app/Models/`,
and Services under `app/Services/` for anything with real business logic (e.g.
`BoardAccessService`). No GraphQL layer — controllers return `Inertia::render(...)`.

### Eloquent
- UUID primary keys throughout (`HasUuids` trait), including `users` — no mixed-key schema.
- Cast every `datetime`-shaped column explicitly in `$casts`, even obviously date-like ones —
  a missed cast on `PlayerBoard.last_roll_date` caused a real 500 (`isToday()` on a raw string).
- Define every relation a controller will eager-load, even obvious ones — a missing
  `PlayerBoard::team()` caused a real `RelationNotFoundException`.
- Migrations: `php artisan make:migration <name>`, then `php artisan migrate`.
- **SQLite does not reject an unknown column in a SELECT list** — it reads the
  bare identifier as a *string literal* and returns it as data, so
  `->get(['id', 'size'])` against a table with no `size` silently yields the
  word "size". PostgreSQL (production) raises `column does not exist`. Any
  explicit column list is therefore untested by dev usage alone; check names
  against `Schema::getColumnListing()` when a column moves between tables.
  This shipped a production-only 500 in `OnboardingController` once already.

### Roles & permissions — `spatie/laravel-permission`
- Roles and permissions are spatie's, not hand-rolled (migrated 2026-08-20).
  `App\Models\Role` / `App\Models\Permission` extend spatie's and add
  **`HasUuids`** — the package assumes auto-incrementing ids and this schema is
  uuid-keyed throughout, so without the trait a created role gets no id at all.
  `Role` also keeps a `description` column the admin UI shows.
- `config/permission.php` sets `model_morph_key` to **`model_uuid`**. The
  stub's `model_id` is an `unsignedBigInteger`; against uuid users nothing
  would ever match.
- **Use `User::hasPermission($key)`, not spatie's `hasPermissionTo()`.** Two
  app rules live in that wrapper: ADMIN bypasses every granular check, and an
  unknown key returns `false` instead of throwing `PermissionDoesNotExist`
  (spatie throws; every caller here wants a plain no).
- `$user->can($key)` works too — `HasRoles` registers permissions with
  Laravel's Gate — but it has neither of the two behaviours above.
- Creating a role or permission by name: `Role::findOrCreate($name, 'web')`.
  A bare `firstOrCreate(['name' => …])` skips `guard_name`, and a row without
  one is invisible to every check.

### Live updates — one SSE channel per event type
- Every event type has an `App\Events\Channels\EventChannel`, resolved from the
  type by `EventChannelResolver`. `EventStreamController` knows nothing about
  standings or bingo cards — **adding an event type means writing a channel,
  not touching the controller.**
- A channel answers two questions, and the split matters: `fingerprint()` runs
  every few seconds per connected viewer so it must be cheap, `payload()` only
  runs when something actually changed.
- **A fingerprint must be built from what the client displays**, not from every
  column. A sync that rewrites `synced_at` without changing a score, or a host
  re-approving to the same verdict, must not wake every open browser. There
  are tests for both directions in `EventStreamTest`.
- The channel is **public** — one stream is shared by every viewer, so it can
  never carry per-viewer state. Host-only data (the bingo review queue) is
  refreshed with a partial `router.reload({ only: [...] })` when the stream
  says something changed.
- Client side, use `useEventStream()` — it owns reconnect and staleness. The
  server closes every stream after ~45s by design, so a disconnect is normal;
  the indicator only reports trouble after a reconnect fails to land in ~6s.
- SSE, not WebSockets: the data only flows one way. See EventStreamController
  for the full reasoning and what it costs (a PHP worker per viewer).

### Push notifications — the catalogue is the contract
- Every kind of push is a row in `App\Support\NotificationCategory::ALL`:
  audience, default, throttle window, icon. The settings page renders it, the
  preference validator whitelists against it, the senders read their default
  from it, and `PushNotifier` reads its throttle from it. **Adding a category
  means adding a row there**, plus `notifications.category_<key>[_desc]` and
  `notifications.preview_<key>_{title,body}` in `lang/en.json`.
- **Anything high-frequency ships `default => false`.** Permission to notify is
  not permission to notify about everything, and one chatty category is how a
  person ends up revoking permission — which takes the rare important ones with
  it.
- `throttle` is a **per-entity floor**, keyed on the message's `tag`. Same
  concept twice on purpose: the tag is also what collapses notifications on a
  lock screen, so a sender that has decided which notifications replace each
  other has already decided which ones rate-limit together.
- **Only the push half is throttled.** The SSE channels stay unthrottled — a
  page already open costs nothing to update; it is the phone in a pocket that
  needs restraint.
- `PushMessage` carries a **path, not a URL**. The service worker resolves it
  against its own scope, and the `app:navigate` fallback in `AppRoot.vue`
  rejects anything not starting with a single `/` (a protocol-relative `//host`
  is another origin, and would be an open redirect).
- Sends never throw. `WebPushService` returns `{sent, expired, failed, skipped}`;
  missing VAPID keys is a normal state (a fresh clone, a fresh deploy) and
  reports `skipped`, which is what lets the settings page name the problem
  instead of 500ing.
- **Never rotate VAPID keys on a live environment.** Every subscription is bound
  to the key it saw at subscribe time; replacing the pair invalidates every
  device invisibly, because pushes to the stale ones are still accepted.
  `webpush:vapid` refuses to overwrite without `--force`.
- Auto-subscribe runs on every page load and is deliberately silent, but it
  **must never prompt on iOS** — `requestPermission()` outside a user gesture
  records a denial there that the page can never undo. iOS goes through the
  toggle only.
- On Windows, **`OPENSSL_CONF` must be set or every send fails** — encryption
  needs a fresh ephemeral P-256 key per message and OpenSSL cannot find its
  config. `push:doctor` checks this explicitly; without that check it reported
  four green ticks while nothing was delivered. A queue worker needs it in
  *its* environment, not just your shell.
```
php artisan webpush:vapid          # generate a pair, once, per environment
php artisan push:doctor            # keys, pair match, encryption, every device
php artisan push:sweep --dry-run   # what the time-based sweep would send
```

### Diagnostics — one service, two surfaces
- `App\Services\DiagnosticsService` owns every check. `/admin/diagnostics`
  renders all five groups; `push:doctor` prints the push half and exits
  non-zero on a failure. **Never add a rule to one and not the other** — a
  diagnostic whose answer depends on where you read it is worse than none.
- A check is a `DiagnosticCheck` with four levels. `info` is not a pass: it is
  a fact that cannot be right or wrong, and a group made only of `info` is
  never green.
- **Nothing a check returns may be a secret.** The page is designed to be
  screenshotted — keys are described, never printed; endpoints are
  fingerprinted.
- Scheduled work is proved by `ScheduleHeartbeat`, stamped from
  `->onSuccess()` in `routes/console.php`. Absence is the signal: a stamp that
  was never written and one two days old both mean the cron entry is gone.
- Actions on that page must only ever reach **the admin pressing them** (their
  own devices, their own inbox) or be an explicit rehearsal. That rule is why
  the sweep button is dry-run only.

### Auth
- Discord OAuth via `laravel/socialite` + `socialiteproviders/discord` — not a first-party
  Socialite driver. Use `->setScopes([...])`, never `->scopes([...])` (the latter merges
  with the driver's default scope list instead of replacing it).
- JWT/session via Laravel's own cookie-based auth — no separate JWT service.
- `HandleInertiaRequests` middleware shares `auth.user` (id, discordUsername, nickname,
  avatarUrl, isAdmin, canCreateBoards, canCreateTiles, roles) globally to every page.

### Services — read vs write
- Controller `index`/`show` actions must be pure reads — no side effects, no record creation.
- Mutating actions (`store`/`update`/`destroy`/custom actions like `roll`/`toggleTile`)
  handle all state changes.

### Transactions
- Use `DB::transaction(fn () => ...)` when multiple writes must succeed or fail together
  (e.g. creating a `BoardAccess` row and incrementing `BoardInvite.use_count`).

### Ziggy (`route()` helper)
- Package is `tightenco/ziggy`, PHP namespace is `Tighten\Ziggy\Ziggy` — they don't match.
- `route()` is bound on Vue's `globalProperties` by the `ZiggyVue` plugin — usable bare in
  `<template>`, but NOT from `<script setup>` JS. `import { route } from 'ziggy-js'` looks
  like a fix but resolves its own unconfigured Ziggy instance and crashes SSR. Keep every
  `route()` call directly in the template.

---

## What NOT to do
- Do not use `npm`, `npx`, or `yarn`
- Do not hardcode user-visible strings — always use `$t()`/`trans()` against `lang/en.json`
- Do not use `reactive()` without a good reason — default to `ref()`
- Do not write custom CSS unless Tailwind cannot express it
- Do not create a separate `/create` page for entities that have a modal flow
- Do not skip `console.error()` in catch blocks
- Do not call `route()` or `$t()` from `<script setup>` JS — both are template-only globals;
  use `trans()` (i18n) directly, and restructure around `route()`'s template-only requirement
- Do not add `ssr.noExternal` for `@nuxt/ui` in `vite.config.js` — it silently breaks Vue's
  component resolution globally instead of crashing loudly (see `docs/ssr-gotchas.md`)
