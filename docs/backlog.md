# Backlog

Living priority list for the `experiment/laravel-stack` branch. This is not a
changelog — when an item is done, **delete it from this file** rather than
checking it off. `docs/PROGRESS.md` and `docs/ROADMAP.md` are the historical
record of the old NestJS/Nuxt stack; this file is only what's left to do.

Roadmap, in order:

1. ~~Clean up the repo~~ — done (this branch): old stack moved to `stale/`
   (gitignored, kept on disk for reference), Laravel now lives at repo root
   so Herd can park it and serve `osrs-events.test`, docs consolidated here.
2. Migrate everything else to Laravel — see **Migration** below.
3. Expand admin/user management — currently Discord-login-only, see **Admin & users** below.
4. Secure the whole thing — all requests, all future auth routes — see **Security** below.

---

## Housekeeping (before step 2 starts)

- [ ] Rewrite `CLAUDE.md` for the new layout and stack. It still documents
  the NestJS + Nuxt conventions (pnpm workspace paths, GraphQL codegen,
  Prisma) and the old `backend/`/`frontend/` structure — none of it matches
  the repo as it now stands. Left untouched deliberately during the cleanup
  since it's a real rewrite, not a move.
- [ ] Revisit `docs/ROADMAP.md` and `docs/PROGRESS.md` once the repo
  cleanup + migration are far enough along to know what of the old roadmap
  still applies, what's obsolete, and what should fold into this file instead.
- [ ] Decide whether `docs/README.md` should be restored to repo root — moving
  it means GitHub no longer renders a landing readme for the repo.

## Migration (step 2)

Full findings and rationale: see the published prototype report from this
branch's SSR evaluation. Concrete carry-over work:

- [ ] Port remaining 11 of 16 Prisma models to Eloquent: `BoardAuthor`,
  `BoardAccess`, `BoardInvite`, `UserGuild`, `TeamMember`, `UserPermission`,
  `UserRole`, `Role`, `Task`, plus the `BoardAccessMode` (OPEN/GUILD/INVITE)
  matrix and `BoardInvite.useCount` transaction logic. This is the genuinely
  hard part — no framework shortcut either way, needs a real rewrite of the
  access-control logic, not a line-for-line port.
- [ ] Decide UUID-vs-bigint once, for every table. This prototype left
  `users` on Laravel's default bigint id while every other table uses UUID
  to match Prisma — a deliberate inconsistency to surface the decision, not
  a resolution of it.
- [ ] Rewrite the GraphQL code-first API surface as Inertia controllers —
  full rewrite, not a port; the resolver layer has no Laravel equivalent.
- [ ] Port ~20 Nuxt pages/components to Inertia + Vue pages.
- [ ] Pick an i18n solution for 598 keys currently in
  `stale/frontend/locales/en.json` — no drop-in replacement for
  `@nuxtjs/i18n`. Leading candidate: `laravel-vue-i18n` (documented SSR
  support). Whatever's chosen needs the same SSR-safety scrutiny as the
  `<Head>` bug below — an i18n composable that touches `localStorage`/cookies
  on mount will fail the same silent way.
- [ ] Port Discord OAuth + guild sync from the NestJS implementation to
  Laravel Socialite's Discord driver.
- [ ] Watch for these five SSR gotchas found during the prototype — all fixed
  once, but easy to reintroduce while porting 20 more pages:
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
- [ ] `stale/` can be deleted once the migration is verified complete and the
  team is confident nothing needs porting from it anymore.

## Admin & users (step 3)

- [ ] Design what "admin functionality" beyond board CRUD actually needs to
  cover — this wasn't scoped yet, needs a real requirements pass before
  building.
- [ ] User management beyond Discord OAuth: currently the *only* way into
  the app is Discord login. Decide whether that stays the sole auth path or
  whether an internal/admin account type is needed (e.g. for support access
  without a Discord account).
- [ ] Filament was evaluated as a CMS candidate for Phase 6 (marketing/landing
  copy editing). Verdict from the prototype: it solves the authoring UI
  (its `Builder` field + `spatie/laravel-permission`), not the rendering
  half — the public Vue/Inertia site still needs a hand-built block
  renderer either way. Worth prototyping as a **narrow standalone service**
  (its own small Filament app, same Postgres DB, consumed by the existing
  frontend) rather than pulled in as part of the full migration.

## Security (step 4)

- [ ] Audit every route for auth middleware — this prototype's `/boards/{board}`
  slice used `auth` with no further authorization check (any logged-in user
  can view any board regardless of the real `BoardAccessMode` matrix, since
  that matrix isn't ported yet — see Migration above). Not safe as-is.
- [ ] The `/login` route added in this prototype is a **dev-only stand-in**
  that logs in a fixed test user with no credential check whatsoever — it
  must not exist in anything resembling a real deployment. Delete it once
  real Discord OAuth is wired up.
- [ ] Rate limiting / throttling on all auth routes.
- [ ] CSRF, session, and cookie config review once real deployment domains
  are known (currently defaults from a fresh `laravel/laravel` scaffold).
- [ ] Revisit `BoardInvite` token/`useCount` handling for race conditions
  once ported — the original Prisma schema notes this needs a transaction;
  confirm the Eloquent port actually wraps it in one.
