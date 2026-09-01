# Changelog

Reconstructed on 2026-08-30 from 223 commits. **No releases were ever cut** —
there are no tags and no version numbers, so this is grouped by the arcs the
history actually has, dated by commit date. Everything below is "built", which
is not the same claim as "shipped": the site is not live yet.

From here on, new work gets an entry as it lands.

---

## 2026-08-31 — Public events, and a docs reset

- A listed event is readable without an account. `access_mode` decides who may
  join, not who may look — three questions now, where there was one.
- Progress on a listed event is public; who made it is not, unless the event is
  OPEN or you are in it. The live channel follows the names, not the page.
- An invite code can be handed in on the event page itself, now that a listed
  invite-only event no longer renders the access gate.
- The shared door password also opens Discord signup, so a beta tester is not
  refused at the button a clan actually uses.
- A rate-limited Wise Old Man lookup no longer reports the player as untracked.
- One date-range picker for the event window, replacing two date fields.
- Settings split: Connections (Discord) apart from Account (email, password).
- An announcement can be shown on the lock screen, off by default.
- The backlog was reopened: 5432 lines archived, open work rewritten, ideas and
  SSR gotchas split into their own documents.
- Bosses have icons: 63 of 71 now show their pet. Most come from the package
  the skill icons come from; Aggy and Bran are set from the wiki through the
  new admin page. The remaining eight drop no pet at all.
- Admin: a boss icon can be set by hand, sourced from the OSRS Wiki, without
  waiting on a package release.
- A weekly check proposes wiki images for bosses without an icon. It never
  applies one — proposals queue for approval, and a dismissal is remembered.

## 2026-08-30 — Host approval for tiles

- Claim/approve flow for Snakes & Ladders tiles: proof URL required once a board
  asks for approval, host verdicts, and a rejected claim can be resubmitted.
- A rejected S&L tile does not stay locked, unlike a rejected bingo square — the
  player is standing on that tile and would otherwise never roll again.
- The Vite dev server falls back to a free port instead of failing to start.

## 2026-08-27 — Community, guides and lockdown

- Community hub page, and six wiki-style guide pages written as static routes
  rather than CMS content.
- Full site lockdown alongside the existing pre-launch door.
- Ended events are gated, a "you are here" marker on the board, meta tooltips.
- Admin: task soft deletes with undo, event search and status filters, a
  standings-failure review queue.
- Account: settle one event or team at a time, with pagination.
- A reusable confirm popover for destructive actions, real tooltips on teams,
  masonry layout, generic wiki icon search, and a demo seeder for screenshots.

## 2026-08-24 — The walkthrough round

Driven by walking the app as every kind of user, at every screen size.

- **Push notifications**: per-category opt-in, an ask the browser cannot
  silently swallow, and three fixes from the first real test on a phone.
- **Diagnostics**: an admin page that answers "why is nothing happening".
- Leaving an account without taking everyone with you; a user without an OSRS
  name can leave too.
- A 404 that looks like the site it belongs to; colours that survive light mode
  and targets that survive a thumb; a long title no longer drags the page
  sideways; the event header holds two play buttons and one Manage menu.
- Stop an event without ending it, and tell the people in it.
- Teams: the global `TEAM_MANAGER` role retired, the permission ladder pinned
  by a table-driven test.
- Legal copy caught up with what the code actually does.
- Staging installs now look like staging.

## 2026-08-21 → 2026-08-23 — Live updates, blueprints, and a security pass

- **Live**: a channel per event type, event edits arriving in seconds instead of
  on reconnect, open cards woken when the rules change, and standings refreshed
  after your own action rather than waiting for the stream.
- **Blueprints**: a template carries a whole event, and hosts can save one.
- Configurable bingo win lines, plus the first frontend test suite.
- Security and privacy: signing out everywhere else when a password changes,
  requiring the password to change an email address, no longer publishing player
  and host email addresses, a shut door that stops handing out keys, and rate
  limits on participation.
- `/events` became a dashboard of four rows; "mine" means every event type.
- Audit rows pruned after 90 days; invite links capped at three and no longer
  failing silently.
- Mail: a locked site can still finish a password reset, and the email is branded.

## 2026-08-20 → 2026-08-21 — Events become the vocabulary

- **The event was split out of the board.** "Event" is now the public word;
  a board is one way to run one.
- Event types land: bingo, drop races, skill races, and Skill of the Month as a
  live-updating type. An event's type is fixed at creation.
- An OSRS username is required on every account and checked against Wise Old
  Man — warned, never blocked.
- Roles and permissions moved to `spatie/laravel-permission`, following the UUID
  guide exactly.
- SEO: a generated sitemap, a real robots.txt, uniform meta on every public page.
- CMS: the home page, privacy, terms and the landing-page FAQs became editable.

## 2026-08-19 → 2026-08-20 — Accounts, the admin section, and the CMS

- Email/password registration and login alongside Discord, password reset by
  email, and account settings that link or unlink Discord.
- Administration moved into a guarded `/admin` dashboard: site settings, users
  and roles, an audit log with filters, an invites overview across every board,
  and user deletion — the last unported mutation from the old backend.
- CMS: a pages table, a block editor with live preview, and a renderer proved by
  rendering `/about` from data.
- First-run onboarding modal with a live board preview, assembled per user type.
- Branding: logo, favicon and PWA icon set, amber primary, self-hosted
  RuneScape wordmark font.

## 2026-08-17 → 2026-08-18 — The rewrite lands

The stack moved from NestJS + Nuxt to **Laravel + Inertia + Vue 3**.

- Repo restructured with Laravel at the root; the old stack archived, later
  deleted once the migration was verified.
- Full Eloquent schema, Discord OAuth through Socialite, and hydration
  mismatches eliminated across every page.
- Ported in sequence: boards (list, create/edit, show, dice roll, tiles), teams,
  admin, and the marketing pages.
- All 598 i18n keys ported from the old Nuxt locale file.
- TEAM mode gameplay, co-author management, snake and ladder connector lines,
  an animated dice roller, and rate limiting on the OAuth routes.

## 2026-08-16 — Access control on the old stack

- Board access control, invites, and Discord guild scoping.
- Access mode, event status and team badges on board cards; inline validation on
  the board form.
- A typecheck and lint pass that cleared every `vue-tsc` error and the dead code
  it surfaced.

## 2026-03-20 → 2026-03-23 — First prototype

- The original Snakes & Ladders event demo on NestJS + Prisma + Nuxt, with a
  GraphQL layer.
- Teams, leaderboard and permissions; homepage and authentication flow.
