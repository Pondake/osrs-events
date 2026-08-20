# OSRS Events

Event boards for Old School RuneScape clans. Someone builds a board out of
OSRS tasks, players roll a daily d6 and work their way across it, and snakes
and ladders make the standings move. Discord login, or an email account if you
prefer.

Every account carries an **OSRS username**. It is asked for at signup, and any
account without one (a Discord login, or anything created before the field
existed) is sent to a one-field page before it can do anything else — XP is
read from the hiscores, and the hiscores are keyed by account name.

Snakes & Ladders is the first **event type**. Skill races ("Skill of the
Month" — one skill, one month, most XP gained wins) are the second, with a
leaderboard that updates itself. Bingo and drop races are on the roadmap,
which is why the model separates an *event* from the board it is played on.

---

## Credit: Wise Old Man

Skill races are built on [**Wise Old Man**](https://wiseoldman.net) — the
open-source OSRS progress tracker ([GitHub](https://github.com/wise-old-man/wise-old-man)).
This app does not track hiscores itself. It reads XP gains from their API, and
the whole event type is modelled on their competition view: their metric
names, their `start`/`end`/`gained` delta shape, and their ranking rules, kept
deliberately unchanged so the two pages never disagree about a number.

If you run this, be a good API citizen: set `WOM_USER_AGENT` to something with
a contact address in it, keep to their rate limit (20 requests a minute, 100
with a key), and consider supporting them.

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
| `WOM_USER_AGENT` | Identifies this app to Wise Old Man. Put a contact address in it. |

---

## Running it

```bash
php artisan serve --port=8000
```

> **Working on the skill-race leaderboard?** Don't use `artisan serve`. That
> page holds an SSE connection open for ~45 seconds at a time, and PHP's
> built-in server handles one request at a time — so a single open tab blocks
> the entire dev server. (`PHP_CLI_SERVER_WORKERS` helps on Linux/macOS with
> `--no-reload`; it forks, so on Windows it does nothing.) Serve through
> Herd, Valet, or nginx + php-fpm instead.

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
routes/console.php           scheduled work (standings sync)
ui.config.ts                 Nuxt UI theme, wired into vite.config.js
lang/en.json                 flat dotted-key translations
docs/backlog.md              what is done, what is not, and why
```

### Scheduled work

Skill-race standings are refreshed by a command, not on page view — the page
and the live stream both just read what it wrote:

```bash
php artisan events:sync-standings
```

`routes/console.php` runs it every ten minutes, so a deployment needs
Laravel's scheduler running (`php artisan schedule:work`, or the usual
one-line cron entry). Without it, standings never move.

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
