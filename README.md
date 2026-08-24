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

That covers the open tab. For everything else there are **web push
notifications** — nine categories, opt-in per category, installable as a PWA —
because a clan event happens over days and nobody sits on the page waiting.
See [Notifications](#notifications).

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
| Notifications | Web Push (VAPID) via `minishlink/web-push` + a service worker |
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
| `APP_ICON_FLAVOR` | Leave unset. The home-screen icon follows `APP_ENV`: only `production` gets the clean brand mark, staging and local get the amber "under construction" one so two installs of the PWA are told apart. Set to `production` only to override that on a production-like host. |
| `VAPID_SUBJECT` / `_PUBLIC_KEY` / `_PRIVATE_KEY` | Web push. Generate **one pair per environment** with `php artisan webpush:vapid`. Unset is a valid state — notifications simply report themselves unconfigured instead of failing. See [Notifications](#notifications). |
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
├── Console/Commands/        scheduled + operational commands
├── Support/                 catalogues and value objects (notification categories, checks)
└── Services/                real business logic (board access, bingo, WOM, push)
resources/
├── js/
│   ├── Pages/               one component per route, PascalCase
│   ├── Components/
│   │   └── Cms/             page-block renderer and editor
│   ├── Composables/         useAuth, useSeo, useEventStream, usePush
│   └── Support/             plain JS helpers
├── css/app.css              Tailwind, fonts, OSRS board styling
└── views/app.blade.php      root shell
public/sw.js                 service worker — receives pushes, routes the tap
public/manifest.webmanifest  PWA manifest (a .dev variant for non-production)
routes/web.php
routes/console.php           scheduled work (standings sync, push sweep)
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

It also runs the notification sweep every fifteen minutes:

```bash
php artisan push:sweep
```

Five of the nine notification categories are answers to "has enough time
passed" — an event starting, an event ending, final standings, a review queue
left sitting, standings that stopped updating — and only a clock can ask that.
Without the scheduler those five never fire; the four that hang off a
controller action still do.

---

## Notifications

The app has four ways to tell someone something happened, and each covers a
gap the others cannot:

| Channel | Reaches | Blind spot |
|---|---|---|
| Live channel (SSE) | Whoever has the page open | Everyone else |
| Discord webhook | The channel the event lives in | A room, not a person — scrolled past in ten minutes |
| Email | Accounts with an address | Discord login asks for `identify`+`guilds` and deliberately **not** `email`, so roughly half of any clan has no inbox this app can reach |
| **Web push** | An individual, on their phone, app closed | Needs permission, and on iOS needs the app installed |

### The nine categories

Off-by-default is a design position, not an oversight: one chatty category is
how somebody ends up revoking permission entirely, which takes the rare
important ones with it.

| Category | Fires when | Default |
|---|---|---|
| Claim reviewed | A host approves or rejects your bingo square | on |
| Event starting / ending | One hour before either | on |
| Final standings | An hour after an event ends, with your placing | on |
| Event paused / cancelled | A host changes an event's status | on |
| Claims waiting *(host)* | Claims are sitting in your review queue | on |
| Standings stopped updating *(host)* | A sync is failing, or the measurement window moved | on |
| Your rolls are back | Your daily d6 resets and you have a board in progress | **off** |
| Someone passed you | You lose first place, or fall off the podium | **off** |
| Your team scored | A teammate gets a square approved | **off** |

Everything lives in `App\Support\NotificationCategory::ALL` — audience,
default, throttle window, icon. The settings page renders it, the validator
whitelists against it, the senders read their defaults from it. Adding a
category is one row there plus its `lang/en.json` keys.

Throttles are a **per-entity floor**, not a global rate: ten claims landing in
a host's queue inside a minute are one line saying "3 claims waiting", but two
different events never silence each other. Only the push half is throttled —
the SSE channels stay unthrottled, because a page already open costs nothing
to update.

### Turning it on

```bash
php artisan webpush:vapid
```

Prints a keypair. It writes nothing — the danger is in the pasting, not the
running — so generate for staging and production from wherever is convenient
and paste each pair into that environment only.

> **Generate one pair per environment, and never rotate one that devices have
> already subscribed against.** Every subscription is bound to the public key
> it saw at subscribe time. Replace the pair and every registered device stops
> receiving — *invisibly*, because pushes to the stale subscriptions are still
> accepted by the push service. The command refuses to overwrite without
> `--force` for exactly this reason.

The private key belongs in the backend environment only. The public key is
served by the API (`GET /push/public-key`) rather than compiled into the
frontend bundle, so the two cannot drift apart across separate deploys.

**On a host where the site has its own system user** (Ploi's isolated sites,
and anything else that puts the app under `/home/<site-user>/` rather than
your login's home), an SSH session as the deploy user cannot even `cd` into
the site directory — the folder is there, it is just not readable by you.
Three ways round it, and the first is usually the right one:

```bash
# 1. Generate anywhere at all — it only prints — and paste into the panel's
#    environment editor, which writes .env as the correct user for you.
php artisan webpush:vapid --force

# 2. Or run it as the site's user, from its own directory:
sudo -u <site-user> -H bash -lc 'cd /home/<site-user>/<domain> && php artisan webpush:vapid'

# 3. Or become that user first, and work normally from there:
sudo -i -u <site-user>
```

`--force` in (1) is not overriding a safety check on the target environment —
it is telling the guard that the keys it can see are your *local* ones and not
the ones being replaced.

Users are subscribed **silently** once they allow notifications: every page
load re-registers the browser (an upsert, which is what heals a subscription
the server has lost). An undecided browser is asked once, automatically —
that is where Chrome and Android raise the OS accept/deny prompt.

**Do not rely on that automatic ask alone.** It only works on Chromium.
Firefox has required a user gesture for `Notification.requestPermission()`
since 72 and ignores the call otherwise; Safari the same; and Chrome may
answer it with its quiet UI — a small bell in the address bar that is
indistinguishable from nothing having happened. So a signed-in user whose
permission is still undecided also gets an in-app bar offering to turn
notifications on. Clicking it is a real gesture, so it produces a real prompt
everywhere, and on iOS it is the only route that has ever existed. "Not now"
snoozes it for a week; the toggle at `/settings/notifications` is always there.

**iOS is never prompted automatically**: there, `requestPermission()` outside
a user gesture records a denial the page can never undo.

### When nothing arrives

**Go to `/admin/diagnostics`.** It is the same set of checks as the command
below, with buttons, and it does not need an SSH session — which matters
because the moments you need it are a deploy that went quiet and a phone that
stopped buzzing. See [Diagnostics](#diagnostics).

```bash
php artisan push:doctor
```

The command runs the *same* checks (DiagnosticsService is the single source,
so the two cannot disagree) and adds the full device list across all users. It
exits non-zero on a failure, so it also works as a deploy gate. Between them
they answer: whether keys exist, whether the subject is a `mailto:`/`https:`
URL (Apple rejects anything else), whether the public key is a 65-byte
uncompressed P-256 point, whether **the private key actually derives the
configured public key** — a mismatched pair passes every length check and
breaks every send silently — and whether this PHP can generate the
per-message encryption key at all.

Things that look like bugs and are not:

- **`skipped` in every result.** No VAPID keys in *that process's* environment.
  Queue workers hold the environment they booted with, so keys added after a
  deploy need `php artisan queue:restart` (or `horizon:terminate`) before
  anything sends.
- **Install offers a bookmark instead of an app.** The manifest is fetched with
  credentials omitted, so behind any auth gate the browser gets the login page
  instead. `crossorigin="use-credentials"` is already on the `<link>`; check the
  content type from outside your own session with
  `curl -sI https://<host>/manifest.webmanifest`.
- **"Unable to create the local key" on Windows.** Encryption needs a fresh
  ephemeral P-256 key per message and OpenSSL cannot find its config. Set
  `OPENSSL_CONF` (Herd: `$env:OPENSSL_CONF = "$HOME\.config\herd\openssl.cnf"`).
  A queue worker needs it in *its* environment, not just your shell.
- **Nothing on iPhone.** iOS only delivers notifications to a PWA added to the
  home screen. The settings page says so in words rather than showing a dead
  toggle.
- **"It never prompted me."** Usually the browser suppressing an unprompted
  ask (see above), not the app. The in-app bar is the reliable route; if that
  is not showing either, `/admin/diagnostics` will say whether the server has
  keys at all, since the bar stays hidden when it cannot work.

Every category has a **Send a test** button on the settings page, because the
real triggers are events you cannot summon — a host approving a claim, a race
ending, a sync breaking. Without it the first time anyone sees a given
notification is the moment it matters.

```bash
php artisan push:sweep --dry-run
```

Shows what the time-based sweep would send, before per-user preferences and
throttles are applied — so most daily-roll lines it prints will not actually
go out. The same rehearsal has a button on the diagnostics page.

---

## Diagnostics

`/admin/diagnostics`, admin only. Five groups, each covering something that
**fails without saying so** — the unifying theme is silence, not any one
subsystem:

| Group | The silence it catches |
|---|---|
| Push notifications | Keys that pass every length check and deliver nothing; devices bound to a VAPID key that no longer exists |
| Scheduled work | A missing cron entry. Standings stop moving, five notification categories stop firing, every page still renders |
| Mail | `MAIL_MAILER=log`, which reports success and writes to a file; queued mail with no worker consuming it |
| Wise Old Man | Their API unreachable, or entrants whose names cannot be measured — those score zero in a live race |
| Rendering | `public/hot` left behind by a killed dev server, which silently switches SSR off |

Four buttons, and each is aimed at whoever pressed it:

- **Send a test** — one real encrypted push to your own devices, per
  notification type. The only way to prove the last hop.
- **Send a test email** — a plain message to your own address. Transport only.
- **Look up** — one live Wise Old Man lookup, so "is it them or is it us" has
  an answer in five seconds. It distinguishes *unknown player* from
  *unreachable API*, because the first is good news.
- **Rehearse the sweep** — the notification sweep as a dry run, output shown
  verbatim. Nothing is sent: the real run belongs to the scheduler, and a
  button that buzzes thirty phones is not a diagnostic.

Nothing on the page can reach another user, and nothing prints a secret — keys
are described (length, shape, whether the halves match), never shown, and
device endpoints are fingerprinted. It is meant to be screenshotted into a
chat.

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
