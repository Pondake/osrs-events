# OSRS Events

Event boards for Old School RuneScape clans. Someone builds a board out of
OSRS tasks, players roll a daily d6 and work their way across it, and snakes
and ladders make the standings move. Discord login, or an email account if you
prefer.

Snakes & Ladders is the first **event type** — Bingo and race formats are on
the roadmap, which is why the model separates an *event* from the board it is
played on.

---

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 13 · PHP 8.3+ |
| Frontend | Inertia.js v2 · Vue 3 · Nuxt UI v4 · Tailwind v4 |
| Database | PostgreSQL (SQLite locally) via Eloquent |
| Auth | Discord OAuth (Socialite) · email + password |
| Rendering | SSR through a long-running Node process |

There is no separate API: controllers return `Inertia::render(...)` and Vue
takes it from there.

---

## Prerequisites

- PHP 8.3+ and Composer
- Node 20+ and **pnpm** (never npm or yarn — see `CLAUDE.md`)
- A database: SQLite works out of the box, PostgreSQL for anything real
- A Discord application, if you want Discord login to work

---

## Setup

```bash
composer setup
```

That copies `.env`, generates a key, migrates, seeds demo data, installs the
frontend and builds both bundles. Then fill in `.env` — every variable is
documented there, but the ones that matter first:

| Variable | Why |
|---|---|
| `DISCORD_CLIENT_ID` / `_SECRET` / `_REDIRECT_URI` | Discord login. Without them the button 400s. |
| `ADMIN_USER` / `ADMIN_PASS` | Local-only admin account, reachable at `/dev-login?as=admin&pass=…`. Only exists when `APP_ENV=local`. |
| `SESSION_SECURE_COOKIE` | Leave unset locally; **set it to `true` on any HTTPS deployment**, or session cookies go out over plain HTTP. |

---

## Running it

```bash
php artisan serve --port=8000
```

```bash
pnpm dev
```

Two processes: Laravel serves the app, Vite serves frontend modules with HMR.
While `pnpm dev` runs it writes `public/hot`, and that file is what tells
Laravel to load assets from Vite instead of `public/build`.

**SSR is a third process** and is off unless you start it:

```bash
node bootstrap/ssr/ssr.js
```

It loads `bootstrap/ssr/ssr.js` once at startup, so it must be **restarted
after every SSR build** — it will not pick up changes on its own. While
`public/hot` exists, Inertia routes rendering through Vite and SSR is bypassed
entirely, which is why SSR problems tend to surface only in production.

---

## Building

```bash
pnpm build:ssr
```

Builds the client bundle into `public/build` and the SSR bundle into
`bootstrap/ssr`. `pnpm build` does the client half alone. After any frontend
change **both** need rebuilding, and the SSR process restarting.

---

## Layout

```
app/
├── Http/Controllers/        public + app controllers
│   └── Admin/               everything behind /admin
├── Http/Middleware/
├── Models/
└── Services/                real business logic (board access, player boards)
resources/
├── js/
│   ├── Pages/               one component per route, PascalCase
│   ├── Components/
│   │   └── Cms/             page-block renderer and editor
│   ├── Composables/
│   └── Support/             plain JS helpers
├── css/app.css              Tailwind, fonts, OSRS board styling
└── views/app.blade.php      root shell
routes/web.php
ui.config.ts                 Nuxt UI theme, wired into vite.config.js
lang/en.json                 flat dotted-key translations
docs/backlog.md              what is done, what is not, and why
```

---

## Before you change anything

Read **`CLAUDE.md`** for the conventions this codebase actually follows, and
**`docs/backlog.md`** for the current state of play. The backlog is not a
changelog — it records decisions and the traps behind them, including a list
of SSR gotchas that have each cost real debugging time at least once.

Two that catch people immediately:

- `route()` and `$t()` are **template-only** globals. From `<script setup>`
  use `trans()` for i18n, and keep `route()` calls in the template.
- Tailwind scans source text, so an interpolated class name like
  `` `bg-${color}` `` is never generated. Write the variants out.
