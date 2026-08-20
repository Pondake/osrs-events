# OSRS Events — Claude Rules

This repo migrated from NestJS+Nuxt to Laravel+Inertia on the
`experiment/laravel-stack` branch. The old stack lives on disk at `stale/`
(gitignored, kept for reference only — never edit it, never treat it as
the current app). Everything below describes the **current** app.

## Model selection
- **Haiku** — mechanical tasks: adding i18n keys, renaming, small field additions, fixing typos, one-liner refactors
- **Sonnet** — complex tasks: new features, multi-file refactors, architectural decisions, debugging subtle issues, schema changes

## Package manager
Always **pnpm** for JS tooling, **composer** for PHP. Never `npm`, `npx`, or `yarn`.
```
pnpm dev            # vite dev server (client HMR)
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
├── docs/backlog.md         living priority list — SSR gotchas, what's done, what's not
└── stale/                 old NestJS/Nuxt app — gitignored, reference only
```

Stack: Laravel 13 · Inertia.js v2 · Vue 3 · `@nuxt/ui` v4 · Tailwind v4 ·
Eloquent/PostgreSQL · Laravel Socialite (Discord OAuth).

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
  same class of gotcha as Ziggy's `route()` (see SSR gotchas in `docs/backlog.md`): a
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

## SSR — read `docs/backlog.md`'s "SSR gotchas" list before touching anything render-related
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
- Do not edit anything under `stale/` — it's read-only reference material for the migration
- Do not call `route()` or `$t()` from `<script setup>` JS — both are template-only globals;
  use `trans()` (i18n) directly, and restructure around `route()`'s template-only requirement
- Do not add `ssr.noExternal` for `@nuxt/ui` in `vite.config.js` — it silently breaks Vue's
  component resolution globally instead of crashing loudly (see `docs/backlog.md`)
