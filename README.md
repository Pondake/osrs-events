# OSRS Events

Event boards for Old School RuneScape clans. Someone builds a board out of
OSRS tasks, players roll a daily d6 and work their way across it, and snakes
and ladders make the standings move. Discord login, or an email account if you
prefer.

Every account carries an **OSRS username**. It is asked for at signup, and any
account without one (a Discord login, or anything created before the field
existed) is sent to a one-field page before it can do anything else — XP is
read from the hiscores, and the hiscores are keyed by account name.

There are four **event types**, which is why the model separates an *event*
from the board it is played on — only one of them has a board at all:

| Type | What it is |
|---|---|
| Snakes & Ladders | A board of OSRS tasks, a daily d6, snakes and ladders |
| Skill race | One skill, one month, most XP gained wins |
| Drop race | Same, ranked on boss kill counts |
| Bingo | A 3×3–10×10 card of tasks; players claim squares, a host approves them, lines score |

Every type has a **live channel**: the page opens one `EventSource` and the
server pushes when something it displays actually changed. Adding a type
means writing a channel, not touching the stream controller.

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
| Database | MySQL 8+ or PostgreSQL (SQLite locally) via Eloquent, UUID keys throughout |
| Auth | Discord OAuth (Socialite) · email + password |
| Roles | `spatie/laravel-permission`, with UUID overrides |
| Live updates | Server-Sent Events, one channel per event type |
| Rendering | SSR through a long-running Node process |

There is no separate API: controllers return `Inertia::render(...)` and Vue
takes it from there.

---

## Prerequisites

- PHP 8.3+ and Composer
- Node 20+ and **pnpm** (never npm or yarn — see `CLAUDE.md`)
- A database: SQLite works out of the box; MySQL 8+ or PostgreSQL for
  anything real (the suite runs green against both)
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
| `ADMIN_USER` / `ADMIN_PASS` | Display name and password for the seeded admin account (`AdminUserSeeder`). Log in at `/login` with `ADMIN_EMAIL` and `ADMIN_PASS`. |
| `ADMIN_EMAIL` | Email the seeded admin logs in with. Defaults to `admin@osrs-events.test`. |
| `SESSION_SECURE_COOKIE` | Leave unset locally; **set it to `true` on any HTTPS deployment**, or session cookies go out over plain HTTP. |
| `WOM_USER_AGENT` | Identifies this app to Wise Old Man. Put a contact address in it. |
| `MAIL_*` | Password-reset mail. Ships as `log`, which *reports success and delivers nothing* — see [Mail](#mail). |

---

## Running it

```bash
php artisan serve --port=8000
```

> **Working on any event page?** Don't use `artisan serve`. Every event page
> holds an SSE connection open for ~45 seconds at a time, and PHP's built-in
> server handles one request at a time — so a single open tab blocks the
> entire dev server, and a second stream never connects at all.
> (`PHP_CLI_SERVER_WORKERS` helps on Linux/macOS with `--no-reload`; it
> forks, so on Windows it does nothing.) Serve through Herd, Valet, or
> nginx + php-fpm instead.

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
after every SSR build** — it will not pick up changes on its own.

> **`public/hot` silently switches SSR off.** Inertia checks for it first and,
> when present, posts to `INERTIA_SSR_HOT_URL` — which nothing here sets — so
> the render fails and falls back to client rendering *without a word*. The
> file is written while `pnpm dev` runs and deleted on a clean exit, so a
> killed dev server leaves it behind and every later page ships an empty
> `<div id="app">`. The browser looks perfect; only view-source shows it.
> When verifying anything SEO-related, check view-source and `ls public/hot`
> — and remember that while the dev server is up you are testing HMR source,
> never the built bundle.

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
├── Events/Channels/         one live channel per event type + the resolver
└── Services/                real business logic (board access, bingo, WOM)
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
lang/en/validation.php       the one exception — :attribute names must live here
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

The same schedule prunes admin audit rows nightly, keeping
`AUDIT_RETENTION_DAYS` (90 by default) — so without the scheduler that table
also grows without limit. Those rows keep a user's display name after the
account is deleted, which makes the window a privacy answer, not just
housekeeping.

---

## Mail

One thing sends mail: the **password-reset link** for email/password
accounts. Discord logins never need it, which is exactly why this is easy to
leave broken — most testing never touches it.

It ships as `MAIL_MAILER=log`. That writes the message to
`storage/logs/laravel.log` and tells the user their reset link is on its way,
so the failure looks like success from every side. Deploy without changing it
and password reset is dead with nothing in the logs saying so.

**Locally, use [Mailpit](https://mailpit.axllent.org)** — SMTP on 1025, an
inbox at <http://localhost:8025>, so the whole flow can be walked without
sending anything to a real address:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

`php artisan serve` reads `.env` once, at startup, and an already-set
environment variable wins over the file — so **a mail change needs the dev
server restarted**, not just `config:clear`.

The mail itself is themed
(`resources/views/vendor/mail/html/themes/osrs.css`) and carries the app
icon. It is a light theme on purpose: several clients override dark
backgrounds and leave pale text on white, and this is the one message
somebody has to be able to read.

**Brevo** is the intended provider — 300 mails/day free with no expiry,
EU-hosted, and it speaks plain SMTP, so Laravel's built-in `smtp` mailer
covers it with no package and no code:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=<your Brevo login email>
MAIL_PASSWORD=<SMTP key from Transactional → Settings → SMTP relay>
MAIL_FROM_ADDRESS=<an address at a domain you verified with Brevo>
```

`MAIL_FROM_ADDRESS` is the one with no working default: an unverified From
gets rejected or filed as spam, and `hello@example.com` is neither verified
nor yours.

Volume decides whether that stays the right answer. At password-reset-only
traffic it is a rounding error against 300/day, and the free tier never
expires. Reconsider when either changes:

| If | Then |
|---|---|
| Still transactional-only | Brevo free tier, indefinitely |
| Mail becomes a feature (event digests, invite mails) | Compare on volume — `resend` (3k/mo free) or `ses` (~$0.10 per 1,000) |
| Deliverability starts mattering commercially | `postmark`, paid, best-in-class for transactional |

All four are env-level swaps: `ses` and `postmark` are built into Laravel and
read their keys from `config/services.php`; `resend` also needs
`composer require resend/resend-laravel`. No application code knows which one
is in use.

To check what the app would send without sending it, leave `MAIL_MAILER=log`
and read the tail of `storage/logs/laravel.log` after requesting a reset.

---

## Before you change anything

Read **`CLAUDE.md`** for the conventions this codebase actually follows,
**`docs/PROGRESS.md`** for what is built, and **`docs/backlog.md`** for the
current state of play. The backlog is not a
changelog — it records decisions and the traps behind them, including a list
of SSR gotchas that have each cost real debugging time at least once.

Three that catch people immediately:

- **`php artisan serve` serves one SSE stream at a time.** Every event page
  opens one, so while a board is open in a tab the rest of the site queues
  behind it — measured at 23 seconds for a plain asset request. It is not the
  app: use Herd, Valet or nginx+fpm for anything live. `PHP_CLI_SERVER_WORKERS`
  forks and does nothing on Windows.

- `route()` and `$t()` are **template-only** globals. From `<script setup>`
  use `trans()` for i18n, and keep `route()` calls in the template.
- Tailwind scans source text, so an interpolated class name like
  `` `bg-${color}` `` is never generated. Write the variants out.
