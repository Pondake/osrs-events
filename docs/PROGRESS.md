# OSRS Events — what is built

Where the app actually stands, as of **2026-08-22**.

This file used to track the NestJS + Nuxt + GraphQL build. That stack was
deleted on 2026-08-20 once the Laravel + Inertia migration was verified, so
everything it described is gone — kept in git history, not here. Rewritten
from scratch rather than edited, because a progress file that half-describes
a stack nobody runs is worse than no progress file.

For decisions and the traps behind them, read **`docs/backlog.md`** — that is
the long-form record, and this is the summary.

---

## Where it is

**Feature-complete enough to run a real event, not yet launched.** The site
sits behind a shared-password lock; the public pages are readable, the app is
not. What is left before launch is deployment and content, not features:
outbound mail pointed at a real provider, and the privacy policy and terms
brought up to date.

Roughly 500 backend tests and 145 frontend tests, run with `php artisan test`
and `pnpm test`.

---

## The four event types

The model separates an **event** from the thing it is played on, because only
some types have one.

| Type | Payload | Scored on |
|---|---|---|
| Snakes & Ladders | a `Board` of tiles | position on the board |
| Bingo | a `BingoCard` of squares | approved claims, lines, bonuses |
| Skill race | none | XP gained, from Wise Old Man |
| Drop race | none | boss kill counts, from Wise Old Man |

Each has a **live channel** (`app/Events/Channels/`) answering two questions:
`fingerprint()`, which runs every few seconds per viewer and must be cheap,
and `payload()`, which only runs when something actually changed. Adding a
type means writing a channel, not touching the stream controller.

---

## What works

### Running an event

- Create from a **template** or from scratch, through a stepper. A template
  carries the settings *and* the board — every tile with its snakes, ladders
  and tasks, or every filled bingo square. A copy, not a link.
- Save any event you host **as a template**, while editing or once it has
  finished.
- Templates come in three sets: the ones that ship with the app, your own,
  and your Discord server's.
- Edit through one modal — a stepper while creating, tabs while editing.
- Tiles: a grid editor and a list editor, with tasks pulled from the OSRS
  Wiki and cached.
- Invite links and short codes for events that are not open.
- Teams, scoped to the Discord servers you are actually in.

### Playing

- Roll a d6, move, hit snakes and ladders, tick tiles off.
- Claim bingo squares with a screenshot; a host approves or rejects with a
  note that the claimant sees either way.
- Enter and leave a race; standings sync from Wise Old Man.
- Live standings on every type, pushed rather than polled.

### Around it

- Discord OAuth and email/password side by side, with password reset.
- An admin section: users and roles, events, tasks, templates, site
  settings, CMS pages, invites, and an audit log.
- A first-run wizard.
- Landing pages and CMS-editable content, with SEO metadata and a sitemap.

---

## Decisions worth knowing

These are the ones that shape the code. Each has its reasoning in the
backlog.

- **Admins are ordinary users on the public side.** Editing somebody else's
  event happens in the admin section, on its own routes. Reading is not
  restricted — you cannot moderate what you cannot see.
- **The lock keeps the app unannounced, not the shop window.** Public pages,
  guides and CMS pages stay readable while it is on. New accounts do not.
- **The branding is on the pages people read, not the ones they work in.**
- **A copy, never a link.** Templates snapshot; editing the event afterwards
  leaves the template alone.
- **Claims are the unit of truth in bingo**, and only approved ones score.
- **SSE, not WebSockets.** The data only flows one way. It costs a PHP worker
  per viewer, which is why every stream is capped at 45 seconds and a
  backgrounded tab drops its connection.

---

## Known gaps

The backlog holds the full list; these are the ones worth knowing before
reading the code.

- **`php artisan serve` serves one SSE stream at a time.** On Windows there
  is no way around it — `PHP_CLI_SERVER_WORKERS` forks. Use Herd or
  nginx+fpm when working on anything live, or the rest of the site will feel
  broken while a stream is open.
- **Bingo has no join step.** Participation is implied by claiming a square.
  This has to be settled before the RuneLite plugin.
- **Boss races have no icons.** The icon set is built from wiki item images
  and there is no "Zulrah icon"; pets are the intended answer.
- **Mail is configured but not pointed anywhere.** Works against Mailpit
  locally; production needs Brevo and a verified From domain.
- **No email verification.** An email account is usable with an address
  nobody proved they own. A product decision, not an oversight.
