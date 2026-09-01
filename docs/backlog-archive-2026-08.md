# Backlog — archive, opened 2026-03 through 2026-08-30

**This file is closed.** It ran to 5432 lines: 241 finished items against 38
open ones, spread over ~90 headings, and the open work had become impossible to
find inside it. The still-open items moved to a fresh `docs/backlog.md` on
2026-08-30, the speculative ones to `docs/ideas.md`, and the standing SSR
gotchas list to `docs/ssr-gotchas.md` (which is where `CLAUDE.md` now points).

**Nothing here was deleted.** Every `[x]` stands exactly as written, because a
`[x]` is "I built it" and not "it works" — the second judgement is the owner's,
and this file is where they make it. Code comments across `app/`, `database/`
and `resources/js` that cite `docs/backlog.md` mean this file.

---

Living priority list for the `experiment/laravel-stack` branch. This is not a
changelog — finished items eventually get **deleted** from this file rather
than living on as history. `docs/PROGRESS.md` and `docs/ROADMAP.md` are the
historical record of the old NestJS/Nuxt stack; this file is only what's
left to do.

**Two-step done, though** (set 2026-08-20): mark a finished item `[x]` with
a note on what was built, and leave it in place. Only delete it once the
project owner has actually verified it — "I built it" is not the same claim
as "it works", and a `[x]` line is the record of the first while the second
is still pending. Don't clear `[x]` items out on your own initiative.

Roadmap, in order:

1. ~~Clean up the repo~~ — done (this branch): old stack moved to `stale/`
   (gitignored, kept on disk for reference), Laravel now lives at repo root
   so Herd can park it and serve `osrs-events.test`, docs consolidated here.
2. Migrate everything else to Laravel — see **Migration** below.
3. Expand admin/user management — currently Discord-login-only, see **Admin & users** below.
4. Secure the whole thing — all requests, all future auth routes — see **Security** below.
5. Fix the first-run experience — landing pages and onboarding are currently
   bare-bones — see **Onboarding & landing polish** below.

---

## Housekeeping (before step 2 starts)

- [x] ~~Rewrite `CLAUDE.md` for the new layout and stack~~ — done (see
  Migration's i18n entry below for why this got forced sooner than planned).
- [x] ~~Revisit `docs/ROADMAP.md` and `docs/PROGRESS.md`~~ — done, now that
  migration is far enough along to actually answer this. `PROGRESS.md` is
  correctly left alone — it's a historical record of the NestJS+Nuxt
  implementation and doesn't claim to be anything else. `ROADMAP.md` got a
  clarifying note instead of a rewrite: Phases 1-3 describe the old stack,
  but Phase 3's access control/invites/TEAM mode now have equivalent
  verified implementations here too (this file's Migration/Security
  sections); Phases 4-7 are product roadmap, not stack-specific, and still
  apply as-is. Nothing in either file needed real changes — the underlying
  question was "does this doc still make sense," and it does, once
  labeled correctly.
- [ ] Decide whether `docs/README.md` should be restored to repo root — moving
  it means GitHub no longer renders a landing readme for the repo.

## Migration (step 2)

Full findings and rationale: see the published prototype report from this
branch's SSR evaluation. Concrete carry-over work:

- [x] ~~Port remaining 11 of 16 Prisma models to Eloquent~~ — done. All 16
  models now have Eloquent equivalents (`BoardAuthor`, `BoardAccess`,
  `BoardInvite`, `UserGuild`, `TeamMember`, `UserPermission`, `UserRole`,
  `Role`, `Task`, `BoardTeam` + the original 5 from the prototype), UUID PKs
  throughout including `users` (settled the UUID-vs-bigint question in
  UUID's favor, so no mixed-key schema). **Not done**: the actual
  `BoardAccessMode` (OPEN/GUILD/INVITE) authorization logic and
  `BoardInvite.use_count` increment-on-join transaction — the tables exist,
  the enforcement code doesn't yet. See Security below.
- [x] ~~Port Discord OAuth + guild sync~~ — done.
  `app/Http/Controllers/Auth/DiscordController.php` via
  `laravel/socialite` + `socialiteproviders/discord`, matching the old
  NestJS `AuthService`/`UsersService` behavior: new users get the PLAYER
  role, guild sync is delete-all-and-reinsert in a transaction and is
  non-fatal on failure. One bug found and fixed while wiring it up:
  Socialite's `->scopes()` *merges* with the driver's default scope list
  instead of replacing it — was silently requesting an unwanted `email`
  scope until switched to `->setScopes()`. Needs real
  `DISCORD_CLIENT_ID`/`DISCORD_CLIENT_SECRET` in `.env` before the callback
  can be tested end-to-end (redirect construction was verified via curl;
  the actual Discord round-trip wasn't, no credentials in this environment).
- [x] ~~Port the boards feature (list, create/edit, show, dice roll, tile
  toggle)~~ — done. `BoardController` (index/show/store/update/destroy),
  `PlayerBoardController` (roll/toggleTile, ported from the old
  `PlayersService.rollDice()`/`completeTile()` — SOLO mode only, see below),
  `Pages/Boards/Index.vue`, `Pages/BoardShow.vue`,
  `Components/BoardSettingsModal.vue`. Verified end-to-end in a real
  browser against a fresh SSR build — dice roll and tile toggle both
  confirmed via direct DB state checks (`current_position` and
  `CompletedTile` count changing), not just "no error shown".
  **Known simplification, not an oversight**: create still uses tabs instead
  of CLAUDE.md's stepper-with-per-step-validation convention, to avoid
  building that plumbing for one form in this pass.
  Co-author management and TEAM mode (both originally listed as known gaps
  here) are now done — see their own entries below.
- [x] ~~Rewrite the GraphQL code-first API surface as Inertia controllers~~ —
  done, full rewrite as intended (not a mechanical port; the resolver layer
  has no Laravel equivalent). Boards, teams, tasks, admin, invites, access,
  tiles, leaderboard, profile all have Inertia controllers now.
- [x] ~~Tile editing~~ — done. `TileController::upsert()` (ported from
  `TilesService.upsert()` — a tile is identified by `(board_id, position)`,
  not a pre-existing row; boards were never auto-populated with a full grid
  on creation in the old app either, `GameBoard.vue` rendered
  placeholder "empty-{position}" tiles for any unconfigured position, ported
  the same way here). `TileEditModal.vue` — task search/select (against the
  existing `Task` table, not a live OSRS Wiki call — despite some landing-page
  copy claiming "search the wiki," no such integration exists anywhere in the
  old codebase either, confirmed by grepping for it), tile type, target
  position. `BoardShow.vue` now renders the full grid in boustrophedon
  (snake) order matching real Snakes & Ladders numbering, not a plain
  top-left reading order, and has an edit-mode toggle. Verified via DB:
  assigned a real task to tile 1, confirmed the relation persisted.
- [x] ~~Leaderboard page~~ — done. `LeaderboardController` (ported from
  `PlayersService.getLeaderboard()` — rank, tiles remaining, whether a
  ladder/snake lies on the path ahead), `Boards/Leaderboard.vue`. Found a
  real bug while testing: `PlayerBoard` model had no `team()` relation
  defined at all — a 500 (`Call to undefined relationship [team]`) the
  moment the leaderboard tried to eager-load it. Fixed.
- [x] ~~Profile page~~ — done. `ProfileController` (nickname editing, ported
  from `UsersService.updateProfile()`), `Profile.vue` — role badges, joined
  boards with a progress bar. Verified nickname save via DB.
- [x] ~~Invite management UI~~ — done. An "Invites" tab in
  `BoardSettingsModal.vue` (create/list/revoke), using plain `fetch()`
  against `BoardInviteController` rather than Inertia's router, since the
  modal isn't a page component and an Inertia visit would re-render the
  whole underlying board page just to refresh one list. Uses the
  `XSRF-TOKEN` cookie directly for CSRF (no `<meta name="csrf-token">`
  exists in `app.blade.php` — Blade's `@csrf` is for `<form>` tags, not
  fetch headers). Direct join-by-link route
  (`GET /boards/{board}/join/{token}`, ported from the old
  `join/[token].vue`) redirects unauthenticated visitors through Discord
  login via `redirect()->guest()` + Laravel's own `intended()` mechanism,
  replacing the old client-side `localStorage` post-auth-redirect hack.
  Verified both the create-invite and the join-by-link flows via DB checks.
- [x] ~~Team members modal~~ — done. `TeamMembersModal.vue` — search/add
  (new `TeamController::searchUsers()` endpoint), remove. Verified add and
  remove via DB. Found a real Vue bug while testing: the parent page stored
  `managingTeam` as a direct object reference from the `teams` prop; after
  adding a member triggers an Inertia reload, `teams` gets replaced with
  entirely new objects, but the modal kept displaying the *stale* pre-reload
  team (confirmed live — it showed "No members yet" right after a member had
  actually been added and the underlying page's own list had updated
  correctly). Fixed by storing only the team ID and deriving the current
  team via a computed lookup into the live `teams` prop instead.
- [x] ~~Port teams + admin pages~~ — done. `TeamController`
  (index/store/update/destroy/addMember/removeMember, guild-based visibility
  filter preserved from `TeamsService.findAll()`), `Admin\BoardController`,
  `Admin\TaskController`, `Admin\UserController` (role assign/remove,
  permission grant/revoke — only `canCreateBoards`/`canCreateTiles` exist as
  keys, matching the old `PermissionKey` enum). Verified team creation via
  DB state check in a real browser session, same as the boards mutations.
  **Known simplification**: `TeamController::authorizeManage()` only checks
  `isAdmin() || hasRole('TEAM_MANAGER')` server-side — correct and
  authoritative — but `Teams/Index.vue`'s `canManage` UI flag only checks
  `isAdmin`, since per-team membership role isn't in the shared auth prop.
  A TEAM_MANAGER who isn't admin won't see the manage buttons even though
  the server would allow the action; not a security gap, just a UI gap.
- [x] ~~Port remaining static/marketing pages~~ — done: `Home`, `About`,
  `Donate`, `Privacy`, `Terms`, `OsrsClanEvents`, `OsrsEventIdeas`. Copy
  transcribed from `stale/frontend/locales/en.json` (the real English
  strings, not placeholders) — will need re-extracting into locale files
  once i18n lands, but it's the actual site copy in the meantime, not filler.
  `OsrsClanEvents`' login CTA hit a fifth Ziggy footgun (see #10 below):
  calling `route()` from a `computed()` in `<script setup>` instead of
  directly in the template.
- [x] ~~Pick an i18n solution, verify it's SSR-safe~~ — done.
  `laravel-vue-i18n` (npm package, not a composer one — despite the name it's
  pure Vue/Vite, nothing PHP-side to require). Its own README documents SSR
  needing the *eager* glob variant (`import.meta.glob(..., { eager: true })`)
  in `ssr.js` instead of the Promise-returning one `app.js` uses — this was
  actually verified end-to-end, not just trusted from the docs: a real
  `lang/en.json` test key was curled from raw SSR HTML and the *resolved
  translated string* was present, not the raw key, with zero warnings in the
  SSR log. Wiring is in `vite.config.js` (the `i18n()` plugin), `app.js`, and
  `ssr.js`. `lang/php_*.json` (auto-generated from PHP lang files by the Vite
  plugin, if any get added) is gitignored per the package's own guidance.
- [x] ~~Port the actual 598 keys from `stale/frontend/locales/en.json` into
  `lang/en.json`, and rewire every page from hardcoded English strings to
  `$t()`/`trans()` calls~~ — done. All 598 keys flattened into
  `lang/en.json`'s dotted-key format (628 after adding ~30 keys that turned
  up missing during the rewire — new UI text the old app's locale file never
  had, like invite-expiry copy and admin search placeholders), and all 26
  Vue files (every page + every modal component) rewired.
  **Real bug found and fixed while verifying this live, not just a port**:
  `app.js`'s client-side `i18nVue` plugin loads the language file via an
  async dynamic import and previously mounted the app before that promise
  resolved. Any `trans()` call at `<script setup>` top level (SEO
  title/description via `useSeoData`, script-built label arrays) ran before
  messages existed and permanently cached the raw untranslated key — the
  browser tab literally showed `"seo.home_title - OSRS Events"` after
  hydration despite the server-rendered HTML having the correct title,
  because `useSeoData`'s `resolved` computed captured `trans()`'s fallback
  value once and never re-evaluated it. `$t()` calls inside templates were
  never affected (they're reactive to the message store loading). Fixed by
  `await`ing `loadLanguageAsync('en')` (same shared `I18n` instance the
  plugin registers, per its `shared: true` default) before `app.mount()`.
  SSR was never affected — the package's `I18n` constructor uses a
  synchronous `loadLanguage` server-side already.
  Also found while dispatching this as parallel sub-agent work: a stale
  `CLAUDE.md` (still describing the pre-migration NestJS+Nuxt stack) caused
  one fresh agent to distrust the actual filesystem and refuse the task
  outright, and a second agent to falsely report success on two files
  (`Privacy.vue`/`Terms.vue`) it never actually wrote — both had to be
  redone with tighter verification (`git status`/grep counts) built into the
  task itself. `CLAUDE.md` is now rewritten for the current stack; see the
  Housekeeping item above, now resolved.
- [ ] Watch for these SSR gotchas found so far — all fixed once, but easy to
  reintroduce while porting the rest:
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
- [x] ~~Site navigation (header/footer/user menu)~~ — done, and it was a real
  gap, not polish: every page existed in isolation with no way to navigate
  between them except typing URLs directly, which is how this entire
  migration had been tested up to this point. `AppHeader.vue` (role-gated
  nav — Boards/Teams/Tasks/Users, matching the old `AppHeader.vue`'s
  `isAdmin`/`isEditor`/`isTeamManager` logic, now sourced from a `roles`
  array added to `HandleInertiaRequests`'s shared `auth.user`),
  `UserMenu.vue` (avatar dropdown, profile link, logout), `AppFooter.vue`
  (guide/legal links — the only site-wide crawl path to the marketing
  pages). Wired into `AppRoot.vue` once, not per-page. Verified logout
  end-to-end in a real browser: header correctly flips from the user menu
  back to "Login with Discord" and redirects home.
  **Found a process-crashing bug while building this** — not a per-request
  500, an uncaught exception that killed the entire long-running SSR Node
  process outright: any `@nuxt/ui` component going through its Inertia-mode
  `Link` override (`u-navigation-menu`, any `href`/`to`-bound `u-button` —
  including the login button) unconditionally reads `usePage().url` for
  active-route highlighting. `AppHeader` renders as a sibling to, and
  *before*, the actual Inertia page component in `AppRoot.vue`'s template,
  so during SSR that read happens before Inertia's own page-state singleton
  is populated — `page.url.startsWith(...)` throws on `undefined`. Also hit
  a nested-`<a>` bug in the same component: `u-header` already wraps its
  `#title` slot content in its own link to `to` (default `/`) — the old
  Nuxt app's own `AppHeader.vue` had a comment warning about exactly this,
  read while porting it, and still made the mistake once by putting a raw
  `<a href="/">` inside the slot anyway. Fixed by keeping the title as plain
  text (no nested link) and moving the nav menu + login button behind
  `<client-only>` — they cost nothing server-rendered (behind
  `v-if="isAuthenticated"` for an anonymous crawler anyway, and the footer
  already carries the crawl-relevant marketing links as plain `<a>` tags).
  **Follow-up found later**: the plain-text title fix above didn't actually
  eliminate `u-header`'s internal link — `Header.vue` unconditionally wraps
  `#title` in its own `ULink :to="props.to"` (default `/`) regardless of what
  the slot content is, so the same `page.url.startsWith(href)` crash was
  still firing client-side on every single page's hydration (confirmed via
  an unminified dev-mode Vue stack trace: `<Link to="/" ... data-slot="title">
  inside <Header>`). Not process-fatal client-side like the SSR case was, but
  a real per-page console error on every load. Fixed by passing `to=""` to
  `<u-header>` itself so `href.value` is falsy and `isLinkActive`'s own guard
  short-circuits before reaching `.startsWith` — title still renders
  server-side, just as a non-clickable element now (lost the click-logo-to-
  go-home affordance; nav menu/footer still cover navigation).
- [x] ~~Theming — wire real brand colors + fonts~~ — done. The app had been
  running on `@nuxt/ui`'s untouched defaults (blue primary/green neutral)
  because the `ui()` Vite plugin was never given a theme config at all.
  Ported `ui.config.ts` from `stale/frontend/app/ui.config.ts` (`primary:
  purple`, `neutral: stone`) and wired it into `vite.config.js`'s `ui: {}`
  option. Also ported the Cinzel/Cinzel Decorative heading fonts and
  board-tile snake/ladder/current/completed CSS from the old
  `assets/css/main.css`, and restored the full favicon/PWA icon set +
  `manifest.webmanifest` that existed in `stale/frontend/public/` but never
  made it into the new `public/` during the repo restructure — the new app
  had been serving with only Laravel's stock `favicon.ico` this whole time.
  Snake/ladder connector lines are ported too now — see below.
- [x] ~~TEAM mode gameplay~~ — done. New `PlayerBoardService` ports
  `getOrCreatePlayerBoard()`'s TEAM branch (one shared `PlayerBoard` per
  team, resolved via `BoardTeam` -> `TeamMember`), used by
  `PlayerBoardController::roll()`/`toggleTile()` and `BoardController::show()`.
  `BoardShow.vue` renders a dedicated "no team on this board" empty state
  (`board.no_team_title`/`no_team_desc`) instead of the grid when the user
  has no team on a TEAM board. Verified via `tinker` against real DB rows,
  not just reading the code: two team members share one `PlayerBoard` row,
  a user with no team gets `null` not an error. Also built the
  board-team assignment UI that never existed at all — nothing let an admin
  actually add a team to a board before this, so TEAM mode was unreachable
  regardless of the gameplay logic. New `BoardController::teamsIndex()`/
  `addTeam()`/`removeTeam()` (ported from `BoardsService::addTeamToBoard()`/
  `removeTeamFromBoard()`) plus a Teams tab in `BoardSettingsModal.vue`
  (only shown for TEAM-mode boards).
- [x] ~~Co-author management~~ — done. Ported from the old
  `EditorsSection.vue`. Needed a generic user-search endpoint that didn't
  exist (`TeamController::searchUsers()` is scoped to one team's
  non-members, not reusable) — new `GET /users/search`
  (`UserSearchController`, gated on `canCreateBoards`/admin, same as board
  create/edit itself). `BoardSettingsModal`'s Basics tab now has a
  search-and-add editors list seeded from the board's existing authors on
  edit; the true owner can't be removed via this UI, matching the backend
  (which already preserves owner rows regardless of submitted
  `author_ids`).
- [x] ~~Snake/ladder SVG connector lines + dice-roll animation~~ — done.
  Ports `Board/SnakeLadder.vue` and `Dice/Roller.vue`. Connectors use
  percentage coordinates (`viewBox 0 0 100 100`) instead of the old
  pixel-based version, since this grid is fluid-width (Tailwind
  `grid-cols-N` + `aspect-square`, nothing fixed to measure) — scales for
  free with no `ResizeObserver`. `DiceRoller.vue` needed the server's actual
  rolled value to pick a die face, which no flash prop carried yet (the
  existing `board-save` flash is a pre-formatted sentence, not a number) —
  added a `lastRoll` flash key alongside it. Verified live end-to-end via
  a seeded admin login (real gameplay, not just reading the
  code): correct die face, toast, and the connector lines redrawing around
  the new position after a snake hit.
- [x] ~~`stale/` can be deleted once the migration is verified complete~~ —
  **deleted 2026-08-20** on the owner's say-so: 79,054 files, 853 MB. It was
  gitignored, so this is not recoverable from the repo. Comments across the
  codebase still cite paths inside it ("Ported from stale/frontend/...");
  those are provenance notes about where a behaviour came from, not files
  anyone can open, and were left as-is.

## Branding

- [x] ~~**Header logo**~~ — done. **Logo 5a** (final): two clinking pixel-art
  beer mugs on a 16×16 grid, with handles, a lit edge column, darker bases
  and a three-pixel splash. Brand source lives in `resources/images/logo/`
  (colour + three mono variants + the designer's `README.md` with the
  palette and usage rules); `Components/AppLogo.vue` inlines the colour
  variant's rects verbatim rather than `<img>`-ing it, so it renders
  server-side with no extra request. Keep the two in sync by hand.
  Palette: bg `#1c1919`, ember `#e0762f`, gold `#d4a33e`, highlight
  `#ffcf5c`, parchment `#e6d9b8`.
  **No tile and no light/dark swap** — 5a's darker handles and bases
  (`#c15c1f`/`#9a721e`/`#b4501a`) give the silhouette its own edge, so it
  holds on white, `#1c1919` and mid-grey alike (verified by rendering all
  three at 32px). This is the thing the previous mark couldn't do: its cream
  `#f0eadb` foam and splash *were* the silhouette, so on a white header they
  vanished and left two floating orange rectangles. That earlier round burned
  through six treatments — dark tile (read as a sticker patched onto a light
  header, vetoed), flat ink silhouette (mugs merged into one blob at 32px),
  1px ink outline (splash droplets clotted into it), tan foam everywhere
  (dulled dark mode) — before settling on a light-mode-only cream swap.
  5a makes all of that unnecessary. Don't reintroduce a header tile.
  `size-8` on a 16-unit viewBox is exactly 2×; the mark's own spec forbids
  non-integer scaling and rotation, so don't give it an arbitrary width.
  The old `osrs-events-10a*.svg` files in `stale/frontend/public/` stay
  orphaned — they were never wired up and are not the current design.
- [x] ~~**Favicon / PWA icon set**~~ — done, regenerated from logo 5a.
  Two deliberately different treatments: `favicon.*` is transparent and
  monochrome (flattened to a single ink, `prefers-color-scheme`-adaptive in
  `favicon.svg`) because a multi-color box turns to mush at 16px in tab
  chrome; the app icons (`apple-touch-icon`, `android-chrome-*`,
  `maskable-icon-*`) keep full brand color on an opaque `#1C1919` tile
  because they sit on an arbitrary wallpaper and need their own edge.
  `manifest.webmanifest` intentionally lists only the four app icons —
  never the transparent `favicon-*.png`, which Android would otherwise pull
  in and render wrong.
  The dark tile is scoped to the app icons *only* — the header and favicon
  are background-free. Worth restating because "no background" is the right
  call for every surface except this one: iOS paints transparent pixels
  black regardless, and Android's squircle mask bites into transparent gaps,
  so a home-screen icon has to carry its own edge.
  Note the designer's `README.md` suggests its own `<link>` block that wires
  the *colour* SVG up as the favicon under different filenames. We don't
  follow it — it conflicts with the transparent-monochrome favicon rule and
  with the filenames already in `app.blade.php`. Treat that section as a
  suggestion, not a spec.
- [x] ~~**Primary button contrast**~~ — fixed. Nuxt UI's solid variant is
  `text-inverted bg-{color}`, and `--ui-text-inverted` is white in light
  mode, so a primary button was white on amber-500 `#fe9a00` = **2.15:1** —
  under even the 3:1 large-text floor. Dark mode was never affected
  (`text-inverted` resolves to dark ink there, 10.17:1), which is why this
  only showed up on white. Fixed with a `color: primary, variant: solid`
  compound variant in `ui.config.ts` pinning `text-[#1c1919]` in both modes:
  8.14:1 light, 10.17:1 dark. Darkening the fill to amber-700 was the
  alternative (5.05:1 with white text) but it turns the gold brown — the
  brand colour is worth keeping, the white text isn't.
- [x] ~~**`text-primary` on light backgrounds**~~ — done 2026-08-20, with the
  two-token split this entry called for. Measured in the browser rather than
  estimated: amber-500 as text is **2.13:1** on white and 1.96:1 on a light
  panel; dark mode already scored **10.15:1**, so only light mode was wrong.
  Across the amber ramp on white — 400: 1.72, 500: 2.13, 600: 3.20,
  **700: 5.03**, 800: 7.09 — amber-700 is the first shade clearing 4.5:1.
  amber-600 clears only the 3:1 large-text/UI floor, so it was not enough.
  Fill and text are now separate: `bg-primary` keeps amber-500, and
  `html:not(.dark) .text-primary` resolves to amber-700 (`resources/css/app.css`).
  The trade-off this entry warned about is resolved in the same direction
  `ui.config.ts` already took for solid buttons — move the ink, keep the gold.
  Soft/ghost/link/subtle buttons are deliberately included: their labels are
  text and had the same problem. Solid buttons set their own ink and are
  untouched.

- [x] ~~**Wordmark font**~~ — done 2026-08-20, self-hosted from RuneStar as
  specified (`public/fonts/`, `--font-osrs-game`, `.osrs-game-font` pinning
  24px with smoothing off since the face is drawn at 12px). **Not applied:**
  the chat-style `#ffff00` + black shadow from the spec — the wordmark uses
  `text-highlighted`, and yellow-on-dark next to the amber mark read as two
  competing yellows. Worth a look when the logo is finalised.
  Original note: logo 5a's README specifies the in-game RuneScape
  Bold 12 face for the wordmark, self-hosted from
  [RuneStar/fonts](https://github.com/RuneStar/fonts) (CC0) rather than a
  webfont CDN. Not done: the header still pairs the mark with plain Cinzel
  text, and no wordmark SVG shipped in the export. Chat-style text per the
  spec is `color: #ffff00; text-shadow: 2px 2px 0 #000`.
- [x] ~~**Primary brand color**~~ — was `purple` (a leftover placeholder),
  now `amber` in `ui.config.ts` to match the gold mug. Note the landing
  page's "See a board in action" demo is a *pre-rendered PNG* under
  `public/images/demo/`, so it still shows the old purple current-tile ring
  and dice — it won't follow the theme until those screenshots are retaken.

## Admin & users (step 3)

- [x] ~~User management beyond Discord OAuth~~ — resolved: email/password is
  now a first-class second auth path (see step 5's Auth entry), so no
  separate "internal account type" is needed. Any account can hold any role.
- [x] ~~Admin area moved out of settings~~ — done 2026-08-20. Administration
  was a group in the settings sidebar, which put site-wide management inside
  a page about your own account. It now lives at `/admin` on its own shell
  (`Components/AdminLayout.vue`) built from Nuxt UI dashboard components —
  collapsible resizable sidebar, navbar with a per-page `#actions` slot, and
  a dashboard landing page with live counts plus recent audit activity.
  Three things worth not undoing:
  * The shell is **client-only**. `UDashboardGroup`/`Sidebar`/`Panel` each
    import the `#imports` virtual specifier and the sidebar calls Nuxt's
    `useRoute()`, neither of which resolves outside Nuxt. A `#fallback`
    renders the same frame so SSR still serves a layout. Safe here only
    because admin is auth-gated with no SEO stake — do not copy the pattern
    to a public page.
  * `AppRoot` hides the site header, footer and announcement banner for any
    page whose Inertia component name starts with `Admin/`. Without it the
    site chrome renders on top of the dashboard rather than around it.
  * The group is guarded by one middleware (`EnsureCanAccessAdmin`) AND by
    the existing per-controller checks. Not redundant: route middleware is
    easy to forget on a newly added route.
  Old `/settings/admin/*` paths redirect. `/settings` keeps Profile and
  Account only.

- [x] ~~Admin settings shell~~ — done. `/admin/*` renders in the
  same `SettingsLayout` as the personal settings, with the sidebar split
  into "Your account" and "Administration" groups (the admin group is
  hidden for non-admins; every controller still re-checks `isAdmin()`, the
  sidebar is not the authorization). `/admin/users` replaces the
  old top-level `/admin/users` nav item (kept as a redirect) and swaps the
  two always-visible selects per row for one `u-dropdown-menu` that only
  offers what's actually applicable — roles not yet held, permissions not
  yet granted, delete only when allowed.
  **Permissions are grant-only in this UI on purpose** (per the ask):
  `revokePermission` still exists on the controller and route, it's just
  not offered as a menu entry. Wire it up here if that changes.
- [x] ~~**More admin settings pages**~~ — all four candidates below are
  built. The shell moved to `/admin` on its own dashboard while this was
  happening; see the admin-area item above.
  1. ~~**Boards & tasks**~~ — **done**. Both now live at
     `/admin/{boards,tasks}` in the same sidebar; old paths
     redirect. Their controllers moved to `Settings\Admin\` to match.
     The header nav lost its admin entries entirely (it's what everyone
     uses to *play*, so it stays short) — Administration is now the single
     place admins go.
     One thing worth keeping in mind for future items here: the sidebar
     filters **per item**, not by one `isAdmin` check on the group. Tasks is
     gated on `canCreateTiles`, so an EDITOR sees Tasks and nothing else in
     that group — verified live, and matched against the server, which
     returns 200 for tasks and 403 for boards/users/content on that account.
  2. ~~**Site settings**~~ — done 2026-08-20. `App\Models\Setting` is a
     key/value store read through one cached array, so the three settings
     shared on every Inertia response cost a cache hit, not a query.
     `/admin/site` covers: email registration open/closed (Discord
     login is unaffected — it's the only recovery path for accounts without
     an email), default board size + dice roll limit prefilled into the
     create-board form, and a site-wide announcement banner.
     The maintenance toggle listed here originally was **not** built —
     Laravel already has `artisan down`, and a half-toggle that only hides
     the UI while routes stay live would be worse than nothing.
     The announcement carries a type (info/success/warning/error) driving
     colour and icon from `Support/announcement.js`, shared by the live
     banner and the admin preview so the two can't drift. Two things worth
     knowing before touching it: the colour classes are written out
     literally per type because Tailwind never generates an interpolated
     `bg-${color}/10`, and the page's section nav is plain buttons rather
     than `u-tabs` — `u-tabs` reaches `#imports` and would drag the whole
     page behind `ClientOnly`.
     Announcement copy supports inline `[text](url)` and `**bold**`, parsed
     to tokens rendered with `v-for` — never `v-html`, and hrefs must be
     http(s) or site-relative, so `javascript:` and protocol-relative URLs
     degrade to plain text (verified). Deliberately markdown rather than an
     ad-hoc syntax: if this later swaps to `u-editor` in markdown mode (see
     the CMS item), the stored format doesn't have to change.
  3. ~~**Audit log**~~ — done 2026-08-20. `App\Models\AuditLog` +
     `/admin/audit`. Records role grant/revoke, permission
     grant/revoke, user deletion, task deletion and site settings changes
     (diffed, so a save that changed nothing logs nothing).
     The design point worth not undoing: actor and target are each stored
     **twice** — a nullable id, and a plain-text label captured at write
     time. Deletion is the action most worth logging and the one where a
     foreign key is worthless, since it points at a row that no longer
     exists. `target_id` carries no FK at all (targets are polymorphic), so
     it deliberately dangles; `target_label` is what keeps the entry
     readable. Verified by deleting a user, then finding that user's own
     deletion record by searching their name — a join-based search would
     have returned nothing.
     Read-only on purpose: no store/update/destroy action and no route for
     one. If retention is ever needed it belongs in a scheduled prune with
     an explicit window, not a "clear log" button.
     Filters (added the same day): action, user, and team/clan, plus free
     text. The user filter matches actor **and** target — "everything about
     this person" means both — and its options come from the log's own
     labels rather than the users table, so deleted accounts stay
     selectable. Team and clan share one control with a `team:`/`guild:`
     prefix; picking a clan spans every team in it, because `guild_id` is
     stored on team-scoped rows too. Verified against the DB across nine
     filter combinations including deleted users and a team with no clan.
     Team, member and board-team mutations are logged as of that work, and
     invite create/revoke as of the invites overview below.
     Still not logged, a deliberate gap: board create/delete.
     `AuditLog::record()` is a one-liner per call site.
  4. ~~**Invites overview**~~ — done 2026-08-20. `/admin/invites`:
     every `BoardInvite` across every board, with the board, who created it,
     usage, how many people actually joined through it, and expiry.
     Status (active/unused/exhausted/expired) is **derived, not stored** —
     expiry is a moment passing, not an event anything writes a row for. It
     exists twice by necessity (PHP for the badge, SQL for the filter), so
     `statusOf()` and `applyStatus()` sit next to each other in
     `InviteController` and must be changed together. The four states are
     mutually exclusive and ordered: expiry beats exhaustion, and "unused"
     only describes a link that is otherwise still usable.
     Summary counts are for the whole table, not the filtered page — they're
     a reference point, and recomputing them per filter would make them move
     as you narrow.
     Revoking deletes the invite but deliberately leaves the `BoardAccess`
     rows it granted; the confirm text says so, since the natural fear is
     that it ejects everyone who already joined. It's audit-logged from both
     surfaces (here and the per-board modal) so the trail doesn't depend on
     which button was used. The token is never logged — it IS the credential.
     Also added while building this: `FilterClear.vue`, shared by this page
     and the audit log, and `BoardInvite::creator()` plus a `created_at`
     cast, both of which the overview needed and neither of which existed.
- [ ] Design what "admin functionality" beyond the above needs to cover —
  still worth a real requirements pass rather than guessing further.
- [x] ~~**Decide: keep the homegrown roles/permissions, or move to
  `spatie/laravel-permission`?**~~ — decided 2026-08-20: **moved to spatie.**
  Behaviour is unchanged — the point was the plumbing, not who can do what.
  * `App\Models\Role` / `Permission` extend spatie's and add **`HasUuids`**.
    The package assumes auto-incrementing ids; this schema is uuid-keyed
    throughout (CLAUDE.md), and without the trait a created role saves with no
    id. `roles.description` was carried over because the admin UI shows it.
  * `model_morph_key` is **`model_uuid`**, not the stub's `model_id` — that
    column is an `unsignedBigInteger`, and against uuid users nothing would
    ever have matched.
  * Three migrations, in order: rename the old `roles` out of the way (spatie
    wants that exact name and the table had live rows, so its migration would
    have failed outright), create spatie's tables, then copy and drop. The
    copy is written against the **query builder, not the models** — a data
    migration that depends on Eloquent depends on what those classes look like
    today, and these were about to be rewritten.
  * **Permissions had no catalogue before.** `user_permissions` stored a bare
    string per user, so the migration derives the set from keys actually
    granted *plus* the two the code checks by name — without that second half
    a permission nobody currently holds would have vanished silently.
  * `User::hasPermission()` stays as the app's entry point: it keeps the ADMIN
    bypass (spatie would answer "no" for an admin never explicitly granted a
    key) and returns false for an unknown key, where spatie's
    `hasPermissionTo()` throws.
  Verified against the real database, not a fresh one: 3 roles with their
  descriptions, 5 assignments and 2 permissions carried across, old tables
  gone, then grant and revoke driven through the admin UI. 15 tests cover it.

- [ ] **CMS / page layout editor** (roadmap Phase 6). The goal, as scoped
  2026-08-20: edit every public page from the admin section through a
  layout editor that composes pages out of Nuxt UI page elements
  (`u-page-hero`, `u-page-section`, `u-page-feature`, …) rather than a
  freeform rich-text blob — the components are already the vocabulary the
  pages are written in, so the editor should speak the same language.
  `/admin/content` exists as the landing spot and currently does
  one honest thing: inventories the 8 public pages and states that they're
  still hardcoded Vue. It deliberately does not fake an editor.
  The three real pieces of work, none started:
  1. ~~**Storage**~~ — done 2026-08-20. `pages` table, one JSON `blocks`
     document per page plus title/subtitle/SEO/is_published. One column, not
     a `page_blocks` table: a block list is only ever read and written whole,
     so rows would buy ordering queries nothing needs while making a reorder
     an UPDATE across every row. `/about` renders from it; `PageSeeder` uses
     firstOrCreate so a re-run never overwrites edited content. Slugs resolve
     through a `/{page}` catch-all declared LAST in routes/web.php, so every
     fixed path wins and a slug can't shadow a real route.
  2. ~~**Renderer**~~ — done 2026-08-20.
     `resources/js/Components/Cms/PageRenderer.vue` walks a block list;
     `Cms/blocks.js` is the vocabulary AND the security boundary. Blocks
     land: `hero`, `section` (the one container), `features`, `prose`,
     `links`, `cta`, `callout`, `separator`.
     Two rules that must not be softened later, because block props will be
     untrusted database content rendered into a public page: the component
     is resolved from the map in `blocks.js` only, never by looking a stored
     type string up against globally registered components; and `sanitize()`
     builds a **new** props object from the schema rather than spreading raw
     input, so an unlisted key cannot reach a component. That is what keeps
     `ui`, `class` and event handlers out. Options like section alignment
     are bounded enums mapped to fixed class strings, never pass-through
     classes.
     Proved against `/about`, which now renders entirely from a block list.
     The list is hardcoded in `PageController` **on purpose** — in the
     controller rather than the Vue page, so the blocks already arrive as
     plain JSON over an Inertia prop exactly as they will from the database.
     Swapping the source is a change to that one method.
     Verified by feeding the real renderer hostile blocks: unknown type
     dropped, `javascript:`/`data:`/protocol-relative and query-carrying
     `mailto:` URLs all rejected, `ui`/`class`/`onClick`/`innerHTML` never
     reached the component, no `v-html` anywhere, and a five-deep section
     chain cut off by the depth guard. Confirmed in the browser and again
     through SSR.
     Note when measuring SSR output: Inertia's rendered body carries the
     whole props payload in `data-page`, so grepping it for a hostile string
     finds the input, not the output. Strip that attribute first — two
     checks lied before this was spotted.
     Still missing from the vocabulary: images, video/embeds, and a
     multi-column layout block. Add them when a page needs one.
  3. ~~**Editor UI**~~ — done 2026-08-20. `/admin/content` lists editable
     pages (and, honestly, the ones still hardcoded); `/admin/content/{slug}`
     edits one, with a live preview running the SAME PageRenderer the public
     page uses — the payoff for building the renderer first.
     The editor is **generated from the vocabulary**: each BLOCK_TYPES entry
     carries `label`, `icon` and `fields`, so adding a block type is one edit
     rather than three (renderer + add menu + form). Repeaters recurse
     through the same field component, and containers nest the same editor.
     **A bug worth not reintroducing:** `$request->validate()` returns only
     the keys it has rules for. With `blocks.*.type` and `blocks.*.props`
     named but not `blocks.*.blocks`, every nested child was stripped from
     the validated array and saving a page silently emptied its sections.
     The controller now validates the request but persists
     `$request->input('blocks')` — the rules decide whether to accept, not
     what to write. Caught by opening /about after a save and finding its
     buttons gone.
     For the prose inside a block, `@nuxt/ui` v4 already ships `u-editor`
     (TipTap 3 — `@tiptap/core`, `@tiptap/markdown`, drag handle, bubble
     menu, mention/emoji menus are all already declared dependencies of
     `@nuxt/ui`, currently tree-shaken out because nothing imports them).
     So the rich-text half costs no new dependency, only the SSR handling
     every interactive `@nuxt/ui` component needs. `@tiptap/markdown` means
     it can round-trip markdown rather than storing HTML, which is what
     keeps stored content renderable without `v-html`.
  4. **Two more pages moved into the CMS** — done 2026-08-21. `/privacy` and
     `/terms` were hardcoded Vue; they are now `pages` rows rendered by
     PageRenderer, editable at `/admin/content`, and their fixed routes were
     **removed** so the `/{page}` catch-all resolves them — a fixed route left
     in place would shadow the database row and quietly keep serving the old
     copy. `Privacy.vue` and `Terms.vue` are deleted. Four hardcoded pages
     remain: `/`, and the three `landing.*` SEO pages.
     * **A `list` block was needed first.** The prose block renders exactly
       one paragraph, and a policy page is mostly lists; writing them as
       dashed prose would look like a list without being one, which is worse
       for anything read aloud. Items go through the same inline parser as
       prose, so a list entry can carry a link without its own escape hatch.
     * **The privacy copy was rewritten, not transcribed.** It claimed the app
       collects no email address and no passwords — untrue since email
       registration — and predated the OSRS username and the audit log.
       Carrying a knowingly-false privacy statement into the database would
       have been worse than leaving it in Vue. Written from what the schema
       actually stores; **still wants the owner's read before launch**, since
       accurate is the floor rather than the whole bar for a legal document.
     * Two bugs found by looking at the rendered result: seeding `seo_title`
       as `"Privacy Policy — OSRS Events"` double-applied the site-name suffix
       (SSR gotcha #4, reintroduced), and `/admin/content` reported **"0
       blocks"** for every page because `blocks` was missing from the column
       list the count reads.

  5. **The home page is partly editable** — done 2026-08-21, at the owner's
     request: embed content in Home with a note saying which parts are logic.
     Its hero headline and standfirst come from a `pages` row, plus one block
     region rendered below the preview. Everything else stays in the
     component because it is **behaviour, not text** — the hero button
     depends on whether you are signed in, the admin shortcuts only exist for
     admins, and the feature and guide grids are structured lists the block
     vocabulary has no equivalent for.
     `/admin/content` now has three groups rather than two: fully editable,
     **partly editable** (with a "you can edit" / "handled in code" note per
     page), and still hardcoded. An admin who opens that editor expecting the
     whole page should learn why half of it is missing there, not by hunting.
     Two seams that needed guarding, both found by looking at the result:
     * The row was listed **twice** — once as a fully editable page, which is
       exactly the impression the inventory exists to prevent.
       `Page::PARTIAL_SLUGS` filters it out of that list.
     * `/{page}` served it at **`/home`**, a second URL with the same copy and
       none of the parts the component adds. It 404s now, and `Page::
       publicPath()` is the one place that knows a partial page's row is
       published somewhere other than `/{slug}` — the editor's "view live"
       link was pointing at the dead URL until it existed.
     Falls back to the translations when the row is missing, so a fresh
     install without the seeder is a plain home page rather than a 500.

  6. **Editor UI needs a visual pass** — flagged by the owner 2026-08-20:
     it works and is functionally fine, but reads as "a bad Divi", which is
     fair — it is a vertical stack of accordion boxes, and you edit in a list
     that sits beside the thing it changes rather than on it.
     Deliberately deferred, not forgotten. Three directions were put up; the
     call was to leave it for now:
     * **Preview as canvas** — preview full width, hover a block for an
       outline plus a small toolbar, click to open its fields in a
       slide-over, insert points between blocks. Biggest change, but
       everything underneath (renderer, blocks.js, BlockFields) is unaffected
       by it.
     * **Tidy the list** — keep two columns, make the rows real cards with a
       type colour, a drag handle instead of arrows, clearer nesting indent,
       wider preview. Smallest change.
     * **Full preview + slide-over** — middle ground: page at true width, a
       button per block opens its fields, no canvas toolbars.

  Sequencing note: the renderer is the risky part and the one that makes
  the other two useful — build a hardcoded block list through the renderer
  first, before any editor UI or table design.
  Filament was evaluated as a shortcut here. Verdict from the prototype: it
  solves the authoring UI (its `Builder` field + `spatie/laravel-permission`),
  not the rendering half — the public Vue/Inertia site still needs the
  hand-built block renderer either way. If revisited, prototype it as a
  **narrow standalone service** (its own small Filament app, same database,
  consumed by the existing frontend), not pulled into this app.

## Boards → events (step 6)

Flagged by the owner 2026-08-20, after noticing that `/boards` has
sub-pages which aren't discoverable from it.

- [x] ~~**Rebuild `/boards` as a hub, not a list.**~~ — done 2026-08-20 at
  `/events`: a slice of your events, a slice of what is open to join, and the
  calendar marked Soon. `/events/all` holds the full list. It should show a slice of
  each thing rather than being one flat listing:
  * a slice of **My boards** (what you're actually playing),
  * a slice of **Public boards** (what you could join),
  * a slice of the **Event calendar** (marked Soon — the nav already
    advertises it, see AppHeader's `soon()` entries).
  Each slice links through to its own full page. Today `/my-boards` exists
  but nothing on `/boards` points at it, which is the actual complaint.

- [x] ~~**Give My boards its own view.**~~ — done 2026-08-20. One row per
  event with a read-only `BoardPreview` beside it drawing the real board:
  actual snake and ladder positions, where you stand, which tiles are behind
  you. The component gained a second mode rather than a twin — real tiles
  when given, an illustrative board when not, which is what the create form
  needs. Not the same card grid as public
  boards: one board per row, full width, with a real **non-interactive board
  preview** on the right so you can see the shape of the board — tile
  layout, snakes and ladders, where you are — without opening it. The board
  grid already renders from data in `BoardShow.vue`; the preview wants that
  same layout in a read-only, scaled-down form rather than a second
  implementation of it.

- [x] ~~**Name it for where it's going: these become events.**~~ — decided
  and carried out 2026-08-20. The intent was already written in
  docs/ROADMAP.md phase 5 ("when creating a board/event, choose the event
  type") and phase 7; this made it real:
  * `boards.type` — a string with an app-level allowlist
    (`Board::EVENT_TYPES`), not a database enum, because the set is expected
    to grow and an enum means a column rewrite each time. Defaulted rather
    than nullable: every existing row genuinely IS a snakes and ladders
    event, and a nullable column invites "no type" as a state to handle
    forever.
  * Planned types are **listed and disabled** in the create form rather than
    hidden — a gap where Bingo will be tells nobody anything. The server
    rejects them independently (`availableEventTypes()`).
  * Public paths are `/events` and `/my-events`, with redirects for every
    old path — including `/boards/{id}/join/{token}`, which is live in
    already-sent invite links.
  * The **"BINGO!" modal was a misnomer** and is now "Board complete!". It
    fires on finishing a Snakes & Ladders board; real Bingo is a separate
    type with line and full-board rules. Left alone it would have collided.
  **Deliberately NOT done: the model is still `Board`.** The table, model,
  controllers and half the i18n namespace still say board. That rename is
  purely mechanical and can happen any time; the product decisions above
  could not, and doing both at once would have made a large diff impossible
  to verify page by page. Do it as its own change.

  **Follow-up, done the same day: the event was split out of the board.**
  `events` now holds what a competition IS (title, type, dates, mode, access,
  listing); `boards` holds only the Snakes & Ladders payload (size, dice
  limit) plus `event_id`. Ownership, entry and team assignment moved to the
  event too — a Bingo event with no board must still be joinable. Tiles and
  player progress stayed on the board, since Bingo brings its own.
  Two things in that migration worth not undoing:
  * **Each event reuses its board's uuid.** Every satellite's `board_id`
    value was therefore already a valid `event_id`, so the columns were
    copied straight across instead of remapped row by row, and every live
    `/events/{uuid}` link kept resolving. Remapping is where this migration
    would have lost data.
  * SQLite refuses to drop a column that an **index or a foreign key** still
    names, and cannot drop a constraint in place. Each satellite therefore
    goes add-copy-drop, dropping its unique index and FK first and recreating
    the unique on `event_id` — losing it would have allowed duplicate
    authors. Production is Postgres, where this is simpler, but dev is
    SQLite and the migration has to survive both.
  Verified against real rows rather than a fresh database: `migrate:fresh`
  would have run the data-move against zero rows and proved nothing.
  10 events, zero orphans, 25 routes returning 200, and a tile completion
  still writing through the chain afterwards.

  **The shared uuid has a sharp edge, found by the infra scan afterwards.**
  Because migrated events and their boards share an id, code that compares a
  board id against an event id *passes on every existing row* and only breaks
  on an event created after the split. One such check had already slipped in
  (tile ownership in `TileController::destroy`). When touching anything that
  compares ids across the two tables, assume the equality is a coincidence
  and check which one you actually mean.

  **Still saying "board" and worth a separate mechanical pass:** the models
  `BoardAuthor`/`BoardTeam`/`BoardInvite`/`BoardAccess` and their tables now
  carry `event_id` while still being named for boards, and the Vue pages
  (`Boards/Index`, `BoardCard`, `BoardShow`) likewise. Confusing, but
  renaming them touches nothing behavioural.

- [x] ~~**Skill of the Month as a real second event type.**~~ — built
  2026-08-20, and the first thing to prove the event/board split was worth
  doing: a `SKILL_RACE` event has no board at all.

  **Credited to [Wise Old Man](https://wiseoldman.net) deliberately and
  explicitly** (README, `WiseOldManService`, `Event::SKILL_METRICS`, and a
  card on the page itself). Their metric names, their `start`/`end`/`gained`
  delta shape and their ranking rules are used unchanged — translating
  between two vocabularies at the boundary is how they drift apart, and this
  app does not track hiscores itself.

  **SSE, not Reverb.** Chosen with the owner: the data only flows one way,
  the browser sends nothing, so a WebSocket buys a return path with no use
  and a second service to run. `EventSource` is a built-in, reconnects on its
  own and rides the session cookie, so the stream's access check is the same
  one the page render uses.

  Decisions worth not undoing:
  * **Entering is explicit, not derived from access.** An OPEN event grants
    access *without storing a row* (`BoardAccessService::hasAccess`), so a
    leaderboard built from access rows would be permanently empty for the
    commonest mode — and where it did work, it would enrol anyone who merely
    looked at a public leaderboard. `POST /events/{event}/enter`.
  * **Standings are stored, not fetched per request.** The page render and
    the stream both read `event_standings`; only `events:sync-standings`
    talks to Wise Old Man. Outbound request volume therefore tracks the
    schedule, not how many people are watching.
  * **Three display states, not two**: a real gain, a genuine zero, and "no
    measurement" — the last sorts last and gets **no rank at all**. Its
    `gained` is 0, so without that it ties with everyone who really gained
    nothing and takes a placing off people who are actually competing.
  * `--track` (the POST that asks Wise Old Man to re-import a player) is off
    by default and absent from the schedule: it is a write against someone
    else's service, so an operator turns it on knowingly.

  Four real bugs this shook out, all found by driving it rather than reading
  it:
  1. `BoardController::join` and `joinByLink` both called
     `$access->joinBoard(...)`, which **has not existed since the split** —
     the method is `joinEvent`. Every join was a fatal error. A leftover from
     that sweep, in the same family as the five already listed above.
  2. `tap($standing)->fill([...])->save()` returns `true`, not the model —
     `HigherOrderTapProxy` returns its target from `__call`, so the chain
     continued onto `$standing` and ended at `save()`'s boolean. The row was
     written and *then* the method blew up on its return type.
  3. **`max_execution_time` kills a stream before its own deadline.** It is
     30s by default under php-fpm *and* the CLI server, so a 45s stream died
     mid-flight — and the fatal couldn't even be reported, because the
     headers had gone out 30 seconds earlier. `set_time_limit()` at the top
     of the stream callback.
  4. Two accounts entered the same RSN and the leaderboard listed it twice
     with identical gains. `users.osrs_username` stays non-unique on purpose
     (a global unique index lets the first claimant lock a name they may not
     own), but **within one event it is now unique** — the same account
     cannot compete against itself.

  Also fixed by watching it rather than testing it: the live indicator
  flipped to "Reconnecting…" every 45 seconds, because that is exactly how
  often the server closes a stream by design. A disconnect only counts after
  a reconnect fails to land within ~6s.

  **A fifth bug, and the worst of them: a rename could freeze every
  leaderboard.** `enter()` refuses an RSN someone else already races under,
  but nothing stopped a user changing their name in settings *afterwards* to
  one that is taken. `syncUsernames()` then wrote it anyway, violated the
  unique index, and — because it runs inside the scheduled command — took the
  whole run down with it. Every participant after that row, in that event and
  every event after it, silently stopped updating. The only symptom is a
  leaderboard that quietly stops moving; nobody gets an error.
  Two fixes, both needed:
  * A clash now marks the standing `duplicate_username` and keeps the name
    its numbers came from, which the page shows as "Name clash". Two accounts
    claiming one RSN is a thing only a person can settle.
  * **The command wraps each row and each event.** Unattended work must never
    let one participant stop the run. Row-level errors are counted, printed
    and `report()`ed, and the loop continues.
  Sync errors are keyed by their stored value (`events.error_<value>` /
  `_hint`) rather than hardcoded in the template, so a new failure mode needs
  a key pair and nothing else.

  **The RSN is now checked against Wise Old Man — as a warning, not a gate.**
  Asked for by the owner 2026-08-20, and it resolves the objection that kept
  this out originally: their API only knows accounts somebody has looked up
  there at least once, so a real newcomer 404s and *refusing* the name would
  lock out exactly the people this is for. Warning instead of blocking gets
  the signal without the false negative.
  * `WiseOldManService::findPlayer()` returns **three** answers, and the third
    is the point: found, genuinely not found (a real 404 —
    `{"code":"PLAYER_NOT_FOUND"}`, confirmed against their API, not an empty
    body), and **null meaning we could not tell**. A timeout or a 500 must
    never reach a user as "that account doesn't exist"; being wrong in that
    direction tells someone their own RSN is a typo when it isn't.
  * `OsrsIdentityService` owns setting a name so all three entry points
    behave identically, and stores Wise Old Man's canonical casing over
    whatever was typed ("pondake" is saved as "Pondake").
  * `users.osrs_verified_at` is a timestamp, not a boolean — "verified" is a
    claim with an age, and accounts get renamed and archived. Cleared on any
    name change; also set by a successful standings sync, since reading gains
    proves the account exists.
  * The notice recurs and is deliberately **not dismissible**: ignoring it
    means scoring nothing in every race entered, and a one-click dismiss makes
    that permanent and silent. Hidden on the gate page itself, which is
    already asking.
  * Short (6s) timeout, because this runs inline on signup, and the check is
    outside the registration transaction — a third-party HTTP call has no
    business holding one open.

  **Worth knowing locally:** nothing runs Laravel's scheduler in dev, so
  standings sit at "Waiting for first sync" until `php artisan
  events:sync-standings` is run by hand. That is what it looked like when the
  bug above was reported — the row was fine, it had simply never been looked
  up. A "last updated" line on the page would make the difference between
  *stale* and *never synced* visible without reading the database.

  **Follow-up, same day: an OSRS username is now mandatory for every account.**
  Flagged by the owner — a tracked event is pointless if half the accounts
  can't be looked up. Three entry points, because there are three ways an
  account can come into existence:
  * The registration form asks for it, required.
  * **Discord OAuth has nowhere to ask** — the callback returns a Discord
    identity and nothing else — so `RequireOsrsUsername` middleware redirects
    any account without one to `/welcome/osrs-username`. Accounts that predate
    the field land there too, which is the point.
  * `App\Rules\OsrsUsername` is shared by all three (register, gate, profile
    settings) rather than copied — three regexes is three chances to disagree
    about what a valid name is.
  Shape only, no existence check: Wise Old Man 404s for any real account
  nobody has ever looked up there, so verifying would reject genuine new
  players. The standings page already reports that per participant.
  **`lang/en/validation.php` is new and is a deliberate exception** to this
  repo's flat-JSON rule — see CLAUDE.md's i18n section for why `:attribute`
  can't be resolved from `lang/en.json`.

- [x] ~~**`db:seed` had been broken since the Board→Event split.**~~ — fixed
  2026-08-20 while making the seeder aware of the owner's RSN. `composer
  setup` runs the seeders, so a fresh checkout of this branch could not be set
  up at all. Four separate stale spots, and the last two only surface on an
  empty database:
  1. `DatabaseSeeder` still wrote the pre-split shape — `Board::firstOrCreate`
     with title/description/mode/access_mode. Hard failure on the first row.
  2. `DemoDataSeeder::seedTiles()` inserted `event_id` on `tiles` (no such
     column) from a `$event` that wasn't in scope.
  3. `seedGuildMembership(Event $event)` read `$board->required_guild_id` and
     `$board->title` — `$board` wasn't in scope either.
  4. `seedPlayers()` branched on `$board->mode`, which the split moved to the
     event and is now always null — so **every TEAM board silently took the
     solo branch** and then died on a spec with `teams` and no `players`.
  Invisible on an existing database, where the idempotency guards
  short-circuit before reaching any of it. Verified by migrating and seeding a
  throwaway SQLite file from empty: 11 events, 10 boards (the skill race has
  none, correctly), 514 tiles, all four TEAM boards with their teams, and
  every user carrying an RSN.
  The owner is seeded as **Pondake** (`AdminUserSeeder::OWNER_OSRS_USERNAME`,
  also applied to the real Discord account by `GrantOwnerAdminSeeder`, but
  only when empty — a rename made in the app is theirs to keep).

  **Renaming a demo event needs a migration step, not just a new title.**
  `DemoDataSeeder` is idempotent *by title*, so changing one in `boardSpecs()`
  creates a second row and leaves the first behind under the name being
  retired. Renaming the old Snakes & Ladders "Skill of the Month" board did
  exactly that: the database ended up with both it and its replacement, the
  stale one still showing a 7x7 grid beside the real skill race of nearly the
  same name — which is how it was spotted. `renameLegacyTitles()` now carries
  old titles forward; add a pair to it whenever a demo title changes.

- [x] ~~**Edge-case sweep, 2026-08-20.**~~ — asked for after the run of bugs
  above. What it turned up, in rough order of severity:
  1. **`/onboarding/joinable-boards` selected `size` off `events`**, a column
     the split moved to `boards`. It does **not** fail on SQLite: an unknown
     identifier in a SELECT list is read as a *string literal* and returned as
     data (the endpoint handed back the word "size"). PostgreSQL raises
     `column does not exist`. So this was a dev-invisible, production-only
     500. Every other explicit column list in the app was then checked against
     the real schema programmatically rather than by eye — all clean.
  2. **A new Discord account got the onboarding modal on top of the RSN gate**,
     with the modal's own endpoints sitting behind that gate — so "Skip for
     now" and the join-a-board step would have bounced off it. Two blocking
     flows competing for the same screen. The gate wins now; the tour runs
     once the user is through.
  3. **`$board->access_mode` in the demo seeder** (null since the split) meant
     no fresh seed ever produced a single invite or guild membership — the
     admin invites overview and the "VIP Beta Test" spec that exists purely to
     cover its four invite states both had nothing to show. Confirmed by
     counting on a freshly-seeded database: zero of each.
  4. **A metric event with no metric threw a TypeError** on every sync. The
     column is nullable while validation requires one, so only a seeder or a
     console command can produce it — but unguarded it is a fatal, which is a
     far worse way to find out than a message on the row (`no_metric`).
  5. **An unstarted race read as "Waiting for first sync"** — the same state a
     broken one shows, making a perfectly healthy upcoming event look stuck.
  6. **An event's type was editable after creation**, and neither direction
     survives it: turning a board event into a race orphans its board, its
     tiles and everyone's progress, while turning a race into a board event
     leaves it with no board — an empty grid nobody can play. The selector was
     live in the edit modal, and the server accepted it. The type is now fixed
     at creation (refused server-side, disabled in the UI with the reason).
     Rebuilding the payload on the fly was considered and rejected: one of
     those directions destroys data, and refusing is honest where a silent
     rebuild is not.

- [x] ~~**A boardless event crashed the events hub.**~~ — fixed 2026-08-20,
  found by the seeder putting a skill race in the listing. `BoardCard`
  rendered the grid size unconditionally; for a SKILL_RACE that is null, and
  `$t()` calls `toString()` on whatever it substitutes — so one boardless
  event blanked the entire page, header and footer only. It now shows the
  metric in that slot and "View standings" rather than "Play". **Worth
  generalising: any card, list or preview that reads a board field off an
  event now needs a null branch, and the symptom is a blank page rather than
  an error in the UI.**

  **Not done / known gaps:**
  * ~~A skill race you entered does not appear in `/my-events`~~ — **fixed
    2026-08-21.** Entries now carry a `kind` discriminator rather than the page
    guessing from which fields are null: a board row keeps its progress bar and
    preview, a race row shows your placing (`#1 of 5`) and XP gained and has no
    preview, because there is no board to preview. Both sort into one list by
    start date, so the two types interleave by when they run rather than being
    segregated by an implementation detail.
  * A skill race has no team mode. `events.mode` still offers SOLO/TEAM and a
    TEAM skill race would currently rank individuals — either aggregate by
    team or hide the option for this type.
  * Nothing links to a skill race's `/leaderboard` route; the standings *are*
    the page. `LeaderboardController` still assumes a board.
  * One RSN per account, so no alts.
  * `artisan serve` cannot serve the stream and anything else at once (PHP's
    built-in server is single-threaded; `PHP_CLI_SERVER_WORKERS` forks, so it
    does nothing on Windows). Documented in the README — use Herd/fpm when
    working on that page. Worth remembering when sizing fpm in production
    too: this feature holds a worker per viewer.

- [x] ~~**Bingo and drop races implemented.**~~ — done 2026-08-21. All four
  event types are now `available: true`.

  **A drop race turned out to be a boss killcount race**, which made it small
  rather than large. Wise Old Man returns `bosses.{name}.kills` in the same
  envelope as `skills.{name}.experience`, so it reuses the entire standings
  pipeline — the sync command, the SSE stream, the leaderboard page, the
  enter/leave flow — and differs only in which branch of the response is read.
  `EVENT_TYPES` gained a `metricKind`, and `gainedXp()` became `gained($kind)`.
  71 boss metrics pulled from a live response rather than transcribed.
  * **Their `-1` means unranked, not a count.** Found by running a real race:
    Lynx Titan has no Zulrah killcount and came back as -1, which was heading
    into an unsigned column as a value. It is an absence, so it stores null.
  * The metric picker and every label switch on the kind — calling boss kills
    "XP gained" would just be wrong, and a boss slug looked up under `skills.`
    renders as the raw key.

  **Bingo needed its own payload**, and deliberately does not reuse `boards`
  and `tiles` despite both being grids. A Snakes & Ladders board has a dice
  limit, a per-player position, and tiles whose snake/ladder type and target
  mean nothing on a card; bingo needs something they lack — a completion that
  belongs to a **team**, not to one player's walk across a board. Three tables
  (`bingo_cards`, `bingo_squares`, `bingo_completions`) cost three migrations
  once; a shared table with a nullable position and a completion that means
  two things by parent type would cost every query afterwards.
  * **Line detection is server-side** (`BingoService`), because the server has
    to be able to agree with what a player was shown. Rows, columns and both
    diagonals, computed from the size rather than stored — a stored copy is a
    thing that can disagree with the grid it describes.
  * **Shrinking a card is refused** when squares outside the new grid carry
    completions. A size dropdown must not be able to erase other people's
    progress silently.
  * Squares are created up front, unlike S&L tiles which appear on first edit:
    a card has to be clickable the moment it exists, and a missing row renders
    as a hole.
  * An author can both play and edit, and one click cannot mean both — so
    edit mode is an explicit toggle rather than a guess.

  **Still open:** bingo has no SSE stream (its standings are page-load only,
  where a race's are live), no per-square proof or approval flow, and the
  `mimic`/`nightmare` style boss display names were hand-mapped from slugs —
  worth checking against the wiki before launch.

- [x] ~~**Bingo made real, and SSE generalised to one channel per event
  type.**~~ — done 2026-08-21.

  **The table question, reconsidered on request.** Re-evaluated whether bingo
  should reuse `boards`/`tiles`, with real requirements in hand this time
  (docs/bingo-research.md). The answer is **more strongly separate than
  before**: researching how clans actually run these showed the two grids
  diverging, not converging. To share one table, `tiles` would need `points`,
  `status`, `proof_url`, `submitted_by`, `reviewed_by` and `reviewed_at` —
  every one meaningless for Snakes & Ladders — while `boards` would carry
  `win_condition` and bingo would carry `dice_roll_limit`. The completion
  tables differ more fundamentally still: `player_boards` is one row per
  player holding a **position**; a bingo completion belongs to a **team** and
  holds a review state. What the two genuinely share is `Task`, and that is
  shared already.

  **What the research changed.** The first pass was a shared checklist, not a
  bingo tracker: a completion was a boolean fact. Every tool clans actually
  use treats it as a **claim under review**. So:
  * PENDING / APPROVED / REJECTED, with a proof URL, a note, and who reviewed
    it. **Only APPROVED scores** — a pending claim is visible to its author
    and invisible to the standings, or review means nothing.
  * A rejection **keeps the row** and its reason, so the claimant can see why.
  * **Points per square plus a line bonus**, because counting squares treats a
    Zulrah pet and a bucket of sand as equal.
  * Claims close with the event — "after the deadline" is not a judgement
    call, and the guides say the cutoff is what hosts most need enforced.
  * Sizes go to 10x10, which is what competitive events run.
  * `requires_approval` is a card setting: a clan that trusts everyone should
    not have to review every square.
  * The review queue sits **beside the card**, not on another screen — leaving
    the thing you are judging in order to judge it is the wrong shape.

  **Deliberately not built:** "a drop counts for only one tile" cannot be
  enforced without item-level tile definitions or the RuneLite plugin. It is a
  moderation rule the host applies while looking at the proof; a checkbox
  claiming to enforce it would be worse than a host who knows to look.

  **SSE now covers every type.** `/events/{event}/standings/stream` became
  `/events/{event}/stream`, and the controller resolves a channel by type
  instead of knowing about standings. Bingo pushes claims and reviews as they
  land; Snakes & Ladders pushes player positions, so a roll moves everyone's
  view. The fingerprint/payload split is the load-bearing part — see CLAUDE.md.

  Verified in the browser end to end, not just by tests: a rival's approved
  squares appeared on an open card without a reload, a pending claim arrived
  in the host queue, approving it emptied the queue and moved that rival into
  first place at 6 points, and moving a player on a Snakes & Ladders board
  updated an open board's leaderboard. 174 tests.

  **Still open:** bingo has no per-square discussion or dispute state (the
  research found a fourth "disputed" state some clans use), and the boss
  display names are still hand-mapped from slugs.

## Onboarding & landing polish (step 5)

Flagged 2026-08-19: landing pages currently read as placeholder-bare (plain
text + basic layout, no real visual identity) and there's no first-run
experience at all — a new user lands straight on the boards index with zero
guidance. This is a real gap, not a nice-to-have — it's the first thing
every new user sees.

- [x] ~~**Landing pages need actual `@nuxt/ui` component work**~~ — partially
  done. `Home.vue` and `SnakesAndLadders.vue` etc. already used real
  `u-page-hero`/`u-page-section` primitives (feature grids, FAQ, HowTo
  JSON-LD) — the actual gap was zero imagery anywhere, all text/icons. Added
  a "See a board in action" section to `Home.vue` (right under the hero)
  with a real demo screenshot (`public/images/demo/board-preview.png` — a
  populated 9×9 board, captured live off the seeded demo data, framed with
  a bordered/glowing card) — this is the placeholder imagery the item asked
  for, swap for a real product shot later. **Still open**: the other
  `landing.*` pages (`OsrsClanEvents.vue`, `OsrsEventIdeas.vue`) don't have
  an equivalent visual yet — same treatment (a relevant demo screenshot per
  page) would close this out fully.
- [x] ~~**First-run onboarding modal**~~ — done.
  `Components/OnboardingModal.vue`, mounted in `AppRoot.vue` (not on any one
  page — it has to be able to appear wherever a new user first lands) and
  auto-opened off a `needsOnboarding` flag shared by
  `HandleInertiaRequests`. Backed by a new nullable
  `users.onboarding_completed_at` (timestamp, not a boolean — it answers
  "have they?" as well as "when?", leaving room to re-run the flow after a
  big product change); existing users were backfilled to `now()` in the
  migration so nobody gets a first-run tour after weeks of use.
  Three steps in a `u-stepper`, two-column throughout: step content left,
  live preview right. **Lottie was not used** — the preview is
  `Components/BoardPreview.vue`, rendering the same boustrophedon layout and
  the same `.board-tile--*` classes the real board uses, driven straight off
  form state. That answers the item's own open question: a canned animation
  would have shown *a* board, this shows *the* board being configured
  (verified: switching 7×7 → 9×9 re-renders the grid live).
  Skippable at every step ("Skip for now"), and replayable afterwards from
  `/settings/profile` (`POST /onboarding/reset`).
  **Steps are assembled per account, not fixed** — a review pass against
  each user type showed the original fixed three only made sense for one of
  them:
  - *Discord player without `canCreateBoards`* (the common case) hit a dead
    end: a "create your first board" step that told them they weren't
    allowed. They now get a **Find a board** step instead, listing boards
    they can actually join (`GET /onboarding/joinable-boards` — OPEN plus
    GUILD boards for guilds they're in; INVITE excluded, since without a
    code those are just unclickable).
  - *Email/password account* had a real invisible gap: `UserGuild` rows come
    **only** from Discord's guild sync, so such an account can never join a
    GUILD board or see a guild team, and nothing said so. An **Account**
    step now surfaces that, with a connect button.
  - *Admin/creator* was the one the original flow already fitted — it still
    gets Welcome → create a board → plugin, and skips the Account step
    entirely since nothing's missing.
  The Account step only appears when something IS missing (no Discord, or
  no email), so a fully set-up account still sees three steps, not four.
  The board step posts to the existing `POST /boards` — a shortcut into the
  real create flow, not a parallel implementation, so the controller's rules
  apply unchanged.
  All three types verified end-to-end in a browser with real accounts.
  **Not done**: the "theme fields" the original item mentioned — boards have
  no theme/colour concept at all, so there was nothing to preview. Worth
  revisiting only if board theming ever becomes a feature.
- [x] ~~**Auth** — email/password path with no Discord account required~~ —
  done. `RegisteredUserController`/`AuthenticatedSessionController`
  (`/register`, `/login`), `Pages/Auth/{Register,Login}.vue`. Discord OAuth
  stays the primary flow; this is an alternative, surfaced via a dropdown on
  the header's login button (`UserMenu.vue`) instead of replacing it.
  Migration: `users.discord_id`/`discord_username` both went nullable
  (an email account has neither), `email` (unique) + `password` added.
  `User::casts()` auto-hashes `password` on write. Security: `Password::min(8)
  ->letters()->mixedCase()->numbers()`, route `throttle:5,1` (register) /
  `throttle:10,1` (login) matching the existing Discord-route pattern,
  `$request->session()->regenerate()` on login/register to prevent session
  fixation — and retrofitted onto `DiscordController::callback()` too, which
  never had it.
  **Password reset is now done too** — `PasswordResetLinkController` /
  `NewPasswordController` (`/forgot-password`, `/reset-password/{token}`),
  `Pages/Auth/{ForgotPassword,ResetPassword}.vue`, plus the
  `password_reset_tokens` table the original users migration skipped.
  `MAIL_MAILER=log` locally means the reset mail lands in
  `storage/logs/laravel.log` — verified end to end by pulling the real link
  out of that log and completing the reset. Note the `password.reset` route
  name is load-bearing: Laravel's own `ResetPassword` notification builds
  its link from it, so renaming it silently breaks the emailed URL.
  Security notes: `/forgot-password` always reports success regardless of
  whether the address exists, so it can't be used to probe for accounts;
  the reset regenerates `remember_token` so pre-reset sessions die with it;
  send is throttled hardest (`throttle:3,1`) since it's the one that puts
  mail in someone else's inbox.
  A Discord-only account has no email (OAuth scopes are identify+guilds by
  design), so it can now add one under `/settings/account`, and setting a
  password is **blocked** until it does — a password with no recovery route
  is a lockout waiting to happen. Disconnecting Discord requires both.
  **Still not done**: email verification (`email_verified_at`). An added
  address is trusted as-is for now; worth revisiting when Brevo replaces
  the log mailer on staging/prod.
  Two real bugs found live-testing this: the users-table migration missed
  that `discord_username` was ALSO `NOT NULL` (registration 500'd on its
  first real attempt, fixed with a follow-up migration), and a handful of
  admin/search views (`Admin/Users/Index.vue`, `BoardSettingsModal.vue`)
  assumed `discord_username` always exists — fixed to fall back to
  `nickname`/`email` instead of rendering a bare "@".
  2. ~~**Roles & permissions explainer**~~ — **done** as step 1 of the modal:
     shows the user's role badges and spells out what they can and can't do,
     reading live off the shared `auth.user` flags rather than a static list.
  3. ~~**First board creation**~~ — **done** as step 2, feeding the live
     preview.
  4. ~~**RuneLite plugin teaser**~~ — **done** as step 3. The mock is drawn
     in markup rather than shipped as an image file, specifically so it
     can't be mistaken for a screenshot of something that exists — the
     plugin doesn't (`docs/runelite-plugin.md` is feasibility research from
     a *different* integration). Carries a "Coming soon" badge and an
     explicit "Mock-up — the plugin hasn't been released yet" line.

## Security (step 4)

- [x] ~~BoardAccessMode (OPEN/GUILD/INVITE) enforcement~~ — done.
  `App\Services\BoardAccessService` (ported from the old
  `AccessService`/`InvitesService`): `hasAccess()`/`canJoin()`/`joinBoard()`/
  `useInvite()`, same rules as before — board authors always pass, OPEN
  always passes, GUILD checks `UserGuild`, INVITE requires a token/code and
  consumes it in a transaction that increments `BoardInvite.use_count`
  (confirmed via DB check, not just a 200 response). `BoardController::show()`
  now actually calls `hasAccess()` and renders `Boards/AccessGate.vue`
  instead of the board for anyone who fails it; `PlayerBoardController`'s
  roll/toggle actions check it too, not just `auth` middleware.
  `BoardInviteController` (create/revoke, owner-or-admin gated) exists but
  has no UI yet — invites can only be created via tinker/an API client, not
  from the board settings modal. New routes: `POST /boards/{board}/join`,
  `POST /boards/{board}/invites`, `DELETE /boards/{board}/invites/{invite}`.
  **Found and fixed while testing this**: `BoardShow.vue`'s Roll/tile
  buttons were gated on `playerBoard` already existing, but `playerBoard`
  is only ever *created* by rolling/toggling — a genuine cold-start deadlock
  where a brand-new player could never start playing at all. Every earlier
  test of this page used the seeder's pre-created `PlayerBoard` row and
  never exercised a first-time visit, so it went unnoticed until testing the
  INVITE join flow with a fresh user in a real browser. Invite-management UI
  is now built too — see Migration above.
- [x] ~~Rate limiting / throttling on the Discord OAuth routes~~ — done.
  `throttle:20,1` on `/auth/discord/redirect`, `throttle:10,1` (tighter —
  it's the one doing the actual token exchange + DB writes) on
  `/auth/discord/callback`. Verified live: the 21st request within a minute
  to `/auth/discord/redirect` returns 429, not another redirect.
- [x] ~~CSRF, session, and cookie config review~~ — done, as much as it can
  be without a real domain yet. `VerifyCsrfToken` has no exceptions
  configured, so no gap there. `SESSION_DOMAIN`/`SESSION_SAME_SITE` are fine
  as-is regardless of the eventual domain (same-origin OAuth redirect flow
  works under the `lax` default). The one real gap: `SESSION_SECURE_COOKIE`
  was never in `.env.example` and defaults to unset — harmless on the
  plain-HTTP local dev server, but would silently ship session/XSRF cookies
  over HTTP on a real deployment if nobody thought to set it explicitly.
  Now documented in `.env.example` with a comment explaining why it matters.

## Infra scan, 2026-08-20

Run after the events split. Recorded so the next one has a baseline rather
than starting from scratch.

Clean: 23 migrations all applied with none pending; zero orphans across ten
referential checks (boards/events, tiles, player_boards and all four
satellites); no event without a type or title; dev database, `public/hot`,
`public/build`, `bootstrap/ssr` and `stale/` all correctly gitignored;
`composer audit` clean; `pnpm audit` one low-severity advisory only;
`/dev-login` has since been removed outright (2026-08-21) — the auth flow is exercised for real, and a local-only login backdoor is one fewer thing to keep correctly gated.

Fixed during the scan:
- `APP_NAME` was still `Laravel` in both `.env` and `.env.example`. It leaks
  into mail sender names and anything reading `config('app.name')`.
- **Three bugs the Board→Event sweep left behind**, none of which a build or
  a page load would have caught: `tiles` has no `event_id` column so the tile
  upsert identified nothing; `board_teams` lost its delete filter the same
  way; and the teams endpoint still called a relation that had moved. All
  three were on POST/DELETE paths, which is why the earlier "25 routes return
  200" sweep missed them — **exercise mutations, not just GETs**.
- 23 hardcoded English flash strings wired to `lang/en.json`, against
  CLAUDE.md's rule. Most of the keys already existed and had never been used.

Worth keeping as a habit:
- A translation-key audit (referenced-but-missing vs defined-but-unused)
  catches the raw-key class of bug. Note that "unused" is a loose upper
  bound — keys built at runtime (`admin.invite_status_${x}`) look unused.
- Measuring rendered output means the **live DOM or an SSR render**, not a
  `fetch()` of the HTML: locally `public/hot` means the served document has
  an empty `#app`, so scanning it proves nothing. That mistake was made
  twice in one day before it was spotted.

## SEO reality check, 2026-08-21

Asked for by the owner: is the site set up to rank for "OSRS events", "OSRS
event", "OSRS clan event", "runescape events", "old school runescape event"?

**The honest answer is no, and the biggest reasons are not on-page ones.**

- [ ] **The live site is running old code.** `osrs-events.com` resolves and is
  indexed, but the copy Google has quotes "No passwords needed" and the
  pre-rewrite feature list — everything from this session is unshipped.
  `robots.txt` 404s in production despite being tracked in the repo, which
  says the deployment predates it. **Nothing else on this list matters until a
  deploy happens.**

- [x] ~~No sitemap at all~~ — `/sitemap.xml` 404'd; now generated from the
  routes and CMS rows, with everything behind auth excluded and `robots.txt`
  pointing at it.

- [ ] **The head terms are unwinnable, and not because of the site.** Checked
  the live SERPs rather than guessing:
  * **"OSRS events"** returns the OSRS Wiki's *Events* article (in-game random
    and holiday events), Jagex's own pages and YouTube seasonal guides. The
    query means "what events are running in the game", not "a tool for running
    clan events". **The product's own name collides with an informational term
    the official wiki owns.** Ranking would mean outranking the wiki and Jagex
    on their own subject, and the traffic would not convert if it arrived.
  * **"runescape events" / "old school runescape event"** are the same
    problem, with RS3 and Jagex marketing added.
  * Conclusion: treat these as **brand terms to be found by**, not terms to
    compete for. Winning "osrs-events" as a *navigational* query is realistic
    and worth the Organization/WebSite schema below; winning "OSRS events" as
    a *head* query is not.

- [ ] **The winnable terms are the specific ones.** "OSRS clan event ideas"
  returns forum threads, Fandom wikis and clan sites — a weak SERP with no
  strong commercial page in it, and `/osrs-event-ideas` targets it directly
  with ~630 words and ItemList schema. That page is the most likely first win.

- [ ] **The real competitive set is the bingo tools**, not the wiki:
  `osrsbingohub.com`, `aiobingo.com`, `rune-bingo.com`, `osrs-tracker.com`,
  `praynr.com`. All established, all ranking for "OSRS bingo" — a term with
  unambiguous *tool* intent and real volume. **Bingo is the one event type
  still marked `available: false`.** The clearest SEO opportunity and the
  biggest product gap are the same thing.

  **Half of that is no longer true, 2026-08-24:** bingo shipped and
  `Event::EVENT_TYPES` marks it `available: true`. The product gap closed; the
  SEO opportunity is still entirely open, because none of it is deployed.

- [ ] **Missing on-page work, in order of value:**
  1. `Organization` + `WebSite` JSON-LD on the home page — nothing identifies
     the site as an entity, which is what a navigational brand query needs.
  2. `/events` has **no schema and no SeoHead meta** despite being a public,
     crawlable index. It is the natural landing page for "OSRS clan events".
  3. Landing pages are ~600 words each. Adequate, not commanding, for
     anything contested.
  4. No `ItemList`/`Event` schema on the public events index, which is the
     one page whose content genuinely is a list of events.

## Staging feedback, 2026-08-21

Reported from staging in one pass. Tests live in
`tests/Feature/StagingFeedbackTest.php` and `tests/Feature/TeamOwnershipTest.php`.

- [x] ~~**Discord login 500'd when the Discord window was closed mid-flow**~~ —
  done. `DiscordController::callback()` called `Socialite::driver()->user()`
  as its first statement with nothing around it, so two ordinary situations
  came back as an unhandled stack trace on the user's screen: a cancelled
  authorisation (Discord returns `?error=access_denied` and no code, which
  the old code fed straight into the token exchange as a null code) and a
  dead round trip (spent or expired code, or a session whose state no longer
  matches — the 400 from `/oauth2/token` in the screenshot). Both now
  redirect with a flash message, to `/login` normally and to
  `/settings/account` if the session was mid-link. The link-user id is
  pulled *before* anything can fail, because leaving it behind would make
  the next plain login silently try to link instead.
- [x] ~~**Teams could not be assigned while creating an event**~~ — done. The
  Teams tab appeared as soon as you picked TEAM mode and then said "save the
  board first", which is a tab that refuses to do the one thing it exists
  for. Invites genuinely cannot work before the event row exists (an invite
  points at an event id); teams can, because the teams already exist. The
  tab now stages picks on the form in create mode and `BoardController::store`
  writes them in the same transaction, reading from a new `/teams/options`
  endpoint. The empty case — no teams at all — says so, links to `/teams`,
  and points out that assigning them later is fine.
- [x] ~~**Team management rights were one global role**~~ — done. `authorizeManage()`
  asked "are you an admin, or do you hold TEAM_MANAGER?", so creating a team
  left you with a card you could do nothing with, while one role granted
  everything over every team on the site. Rights now live on the membership
  row (`team_members.role`, backfilled: oldest member per team becomes
  OWNER):
  * OWNER — the creator. Renames, manages members, promotes, deletes. One
    per team, and cannot be demoted or removed (a team with no owner is a
    team nobody can delete).
  * MANAGER — promoted by the owner. Everything except deleting and
    promoting.
  * MEMBER — in the team, manages nothing.

  The teams page ships `viewerRole`/`canManage`/`canDelete` per team rather
  than gating on `isAdmin` client-side, and `TeamSettingsModal` now handles
  edit as well as create. Also closed a real hole in passing: the
  add-member user search had no permission check at all and would list every
  account on the site to anyone who could guess a team id.
- [x] ~~**Retire the global `TEAM_MANAGER` role.**~~ Done 2026-08-24 — see the
  entry at the end of this file. Dropped rather than renamed: nothing in the
  app explained such a role, nothing granted it deliberately, and ADMIN
  already covers "somebody has to be able to fix this team".
- [x] ~~**Created vs joined was invisible on the profile**~~ — done.
  `/settings/profile`'s "Your events" listed everything in one column of
  cards with an Owner badge as the only hint which was which. It is compact
  rows now, with an All / Created by you / Joined toggle. "Created" means
  being an author rather than strictly the owner — an event you were added
  to as an editor is one you run, and `/my-events` already draws the line
  there; the Owner/Editor badge still separates the two inside the list.
- [x] ~~**Event blueprints**~~ — done. Reusable event formats ("Skill of the
  Week", "Boss of the Month", "Clan Bingo Night") carrying an optional type,
  metric and description. They autocomplete on the create-event title field
  and prefill whatever they carry; a title-only blueprint is a legitimate
  entry, for a name a clan reuses whose format changes each run. Managed
  under `/admin/blueprints`, gated on `canCreateBoards` rather than
  `isAdmin` — the people running events are the people who know which
  formats get reused. 22 starters in `EventBlueprintSeeder`, safe to re-run
  on its own (`php artisan db:seed --class=EventBlueprintSeeder`).
- [x] ~~**Let event hosts manage blueprints from the event side.**~~ — built
  2026-08-22. See "Templates, from both ends" below. Both open questions are
  answered: **per-clan** visibility (the same rule teams use), and a **copy**
  rather than a link.

## Staging feedback, round two — 2026-08-21

- [x] ~~**Role badge in the header**~~ — done. The user menu button stacks the
  display name over the highest-ranking role (ADMIN / EDITOR / TEAM_MANAGER).
  PLAYER is deliberately absent: everybody has it, so it distinguishes nobody.
- [x] ~~**"Events" in the nav was not clickable**~~ — done. The grouped entry
  had children and no `to`, so a click could only open a list. Desktop now
  uses `u-popover mode="hover"` with a real link as its trigger — click
  navigates, hover reveals the group — and the mobile drawer renders the
  parent as a link with a separate chevron button beside it, which is two hit
  areas rather than one row that means two things. Touch deliberately does not
  get the hover panel (a tap fires hover *and* click).
- [x] ~~**Skill/boss picker was an unsearchable dropdown**~~ — done.
  `MetricPicker` wraps `u-select-menu`, which ships a search box, and skills
  carry their real OSRS icons. Icons are extracted from `@dava96/osrs-icons`
  by `scripts/extract-osrs-icons.mjs` into `public/images/osrs/skills/` and
  committed — the package is a devDependency only, since its ESM build does
  not load under Node at all and 20k inlined base64 sprites have no business
  in an SSR render.
- [ ] **Boss icons.** Skills have all 24; bosses have none. The icon set is
  built from wiki *item* and *category* images, and there is no "Zulrah icon"
  — only Zulrah's scales, and Zulrah's pet. Mapping ~70 bosses to a signature
  drop is a hand-curated table that would be quietly wrong in places, so it is
  a deliberate gap rather than a guess. Pets are probably the most consistent
  choice if someone wants to do it properly.
- [x] ~~**Dates**~~ — done. The create form pre-fills today and a fortnight
  out, and both are required on create: every status badge, standings window
  and bingo cutoff keys off a date, and each null one makes some other code
  invent a fallback. `update()` keeps them nullable — events created before
  this rule exist with null dates and a required field would make them
  uneditable — but enforces the pairing (`required_with:start_date`) and the
  ordering.
- [x] ~~**Invite links tab appeared on every event**~~ — done. It now only
  exists in edit mode *and* when access mode is INVITE. It was never a
  placeholder for the Teams tab; it is a section that applies to one access
  mode, and rendering it on an OPEN event to explain that invites appear once
  the event exists — on an event that plainly existed — is what made it read
  as a bug.
- [x] ~~**No way to choose individual vs teams**~~ — done, and this was the
  real one. The Solo/Team switch was rendered only inside the Snakes & Ladders
  block, so bingo and both race types could never be team events and the Teams
  step they gate could never appear. The server had scored per-team for all of
  them the whole time (`BingoService::competitorFor`); the form simply could
  not say so.
- [x] ~~**Board creation is a stepper now, not tabs**~~ — done, matching
  CLAUDE.md's own rule at last. Type → Basics → Format → Access, plus Teams
  when the mode calls for it, with per-step validation and a first step that
  presents the four event types as cards rather than hiding three of them
  inside a `<select>`. Edit stays tabs. The sections live in
  `Components/BoardSettings/*.vue` and are shared by both, so the two cannot
  drift.
- [x] ~~**The OSRS Wiki search did not exist**~~ — done. The home page has
  promised "search the wiki directly to fill in icons and titles
  automatically" since this stack was built, and the tile and bingo editors
  searched the local `tasks` table — fourteen rows. `OsrsWikiService` proxies
  MediaWiki's own api.php (cached a day, one request for both hits and
  thumbnails), and picking a result creates a Task keyed on the wiki page id,
  so the library grows as people use it. Both editors now share
  `Components/TaskPicker.vue`.
- [x] ~~**Bingo had no settings button and no review dialog**~~ — done. The
  page now carries three distinct controls instead of one toggle: Quick edit
  (what a *square* asks for), Edit board (what the *event* is — including card
  size, win condition, line bonus and whether claims need approval, which
  BoardController::update routes through `BingoService::applyCardSettings`),
  and Review, which carries the pending count and opens a dialog showing the
  screenshot inline with the claimant's Discord and OSRS names side by side.
  Approved squares show the holder's avatar.
- [x] ~~**A list view for filling in a board.**~~ — done, built to the
  description below. `Components/TileListEditor.vue`, one component for both
  grids, opened by "Fill in tiles" on either board page and automatically on
  `?setup=tiles` — the redirect a freshly created event now lands on, since
  "go and turn on edit mode and click 49 squares" is not a next step anybody
  guesses. What it does:
  * A numbered list rather than the grid. Each row is one position: number,
    what it currently asks for, and empty rows visibly empty.
  * **Say which way the numbering runs**, because the two grids disagree and
    that is exactly what makes a list confusing. Snakes & Ladders runs
    bottom-left to top-right (it is a track); a bingo card reads top-left to
    bottom-right (it is a grid). Ideally show it, not just say it.
  * Clicking a row opens a small nested popover with the wiki task picker
    (`Components/TaskPicker.vue` already does the searching).
  * A count of how many are filled, so "am I done" has an answer.
  * Bingo rows carry the free-square toggle; Snakes & Ladders rows carry the
    snake/ladder target, which should be picked rather than typed as a
    number.

  Where it lives matters too: the create stepper cannot hold it, because
  tiles need an event to belong to (S&L tiles are created on first edit, bingo
  squares with the card). Either a step that appears immediately *after*
  creating, or a prominent panel on the event page for a board that is still
  mostly empty.
- [x] ~~**Pre-launch password lock**~~ — done, and yes it was realistic. A
  shared password in front of the whole site (`EnsureSiteUnlocked`), set from
  Admin → Site, with exactly two things reachable while it is on: the lock
  screen and the auth routes. An admin session walks straight through, so
  nobody has to hand out the shared password to the people running the site.
  Deliberately *not* maintenance mode — `php artisan down` takes the app off
  the air for everyone and cannot be turned off from a browser.
- [x] ~~**Coming soon page published**~~ — done, on the orphan branch
  `coming-soon`, one `index.html` plus a README covering the Ploi setup (web
  root `/`, not `/public`, and no deploy script). Orphan so deploying it
  cannot drag application code onto a public host. The source of truth stays
  at `docs/coming-soon/index.html` on this branch.


## Staging feedback, round three — 2026-08-21

Done in this pass: the header submenu paints above the header again (it was
portalled with z-index:auto under a sticky z-50 header) and its hover now
waits 300ms so it stops fighting the click on the link it hangs off; default
event length is an admin setting; the Discord server field is conditionally
required for real on both ends; start-date and card-size finally have the
guidance their neighbours had; event type shows on the my-events rows; "Quick
edit" became "Edit tiles" and "Edit board" became "Event settings" on all
three board pages; bingo squares can be free squares (wildcards); teams no
longer leak.

That last one deserves its own note:

- [x] ~~**Every private team was visible to every account**~~ — done, and it
  was a real leak rather than a UI slip. `TeamController` scoped teams to
  "your Discord guilds, **or any team with no guild**", and a guild has
  always been optional — so every team anybody made without one was offered
  to everybody, including in the event form's picker. `Team::scopeVisibleTo()`
  now says: you are in it, or it belongs to a Discord server you are in, or
  you are an admin. No catch-all. A team with no server is a private group,
  not an unclaimed one — which is also why the team form now picks a server
  from your synced guilds (with "No server — private team" as the default)
  instead of asking you to type an 18-digit snowflake.
- [x] ~~**Teams was hidden from the nav**~~ — done. It was gated on
  `isAdmin || isTeamManager`, which was wrong in both directions: creating a
  team needs no permission at all, and the page is now scoped to what you can
  actually see. Every signed-in account gets it.
- [x] ~~**Announcements leaked past the pre-launch lock**~~ — done. The
  banner is written for people already using the site ("summer bingo starts
  Friday, sign up in #events") and the lock screen is the one page a stranger
  can reach. `HandleInertiaRequests` now withholds the prop entirely unless
  the request has passed the lock — withheld, not hidden client-side, since a
  prop that reaches the browser is already disclosed.
- [ ] **Per-announcement visibility switch.** The blanket rule above is the
  safe default, but it also means nothing at all can be said on the lock
  screen. A per-announcement "show this one publicly" toggle would allow a
  launch date or a link to a Reddit post while keeping clan chatter behind
  the door. Worth doing only if there turns out to be something to say —
  otherwise it is a switch to get wrong.

### Blueprints for boards, not just titles

- [x] ~~**Board layout blueprints.**~~ — built 2026-08-22, see "Templates
  that bring the board" below. Event blueprints currently carry a title,
  type, metric and description. What they cannot carry is the *board*: a
  Snakes & Ladders layout (where the snakes and ladders sit, which task is on
  which tile) or a bingo card (the squares, their points, where the free
  square goes). That is the part that actually takes an hour to set up, and
  the part a clan most wants to reuse between seasons.

  Presented as the first thing you see once the event type is picked — a row
  of "start from a template" options above the blank-event route, with a
  group description explaining what each layout is for. Most relevant for
  Snakes & Ladders and bingo; a race has almost no layout to speak of, so it
  can keep the title-only blueprints it has.

  **Decided 2026-08-22: a whole tile set, not a generator.** A blueprint
  stores the actual squares — positions, snakes, ladders, targets — so
  applying one gives exactly the board that was designed, every time. No
  randomisation: "start from a template" has to mean the same thing twice, or
  a host cannot recommend one to anybody.

  Consequence worth planning for: a snapshot is tied to a grid size, so a
  5x5 layout cannot be applied to a 7x7. The picker has to filter by size
  rather than offer every layout and fail late.

  **Decided 2026-08-22: a copy, not a link.** Saving an event as a template
  takes a snapshot. Editing the event afterwards leaves the template alone,
  and editing the template leaves the event alone. The alternative would let
  somebody's template change under another host's hands without either of
  them noticing.

### Icons and metrics

- [ ] **Boss icons via pets, plus a CRUD and a watcher.** Skills have all 24
  icons; bosses have none, because the icon set is built from wiki item and
  category images and there is no "Zulrah icon". **Pets are the answer** — a
  boss's pet is a real inventory sprite, unambiguous, and one per boss.
  Two caveats worth designing around rather than discovering: a brand-new
  boss's pet often is not on the wiki for a while (so a blank is a normal,
  temporary state, not an error), and the boss list itself grows with every
  game update.

  Wanted, in order of usefulness:
  * a CRUD under `/admin` for the boss list and each boss's icon, so a gap
    can always be filled by hand;
  * a scheduled job that reads the wiki's boss list and reports what this app
    does not know about yet, by mail or notification;
  * fully automatic import on top of that, once the mapping has been right
    by hand for a while.

  `scripts/extract-osrs-icons.mjs` already does the extract-and-commit half
  for skills and is the obvious place to grow this.

### Privacy

- [x] **Scope the task library the way teams are now scoped.** `/tasks/search`
  returns every Task on the site to anyone who can edit a board. Today that is
  fourteen seeded rows so nothing is exposed — but the wiki picker creates a
  Task per page anybody imports, so the library grows into a record of what
  every clan is running. It should be filtered the same way teams are: tasks
  you created, plus tasks created by people you share a Discord server with,
  plus the seeded ones. Needs a `created_by` on `tasks` (wiki imports would
  set it) and the same visibility scope. If that turns out to be more than it
  is worth, the fallback is simpler and also fine: drop the local library
  from the picker and let the wiki be the only source.

  **Retired by round four's wiki-cache change** — the picker is one search box
  over the OSRS Wiki and `tasks` became an ordinary cache with a seven-day TTL,
  so the table stopped being a record of what each clan is running.


## Staging feedback, round four — 2026-08-21

Fixed here: the save that silently did nothing, the wording that called a
Discord server a "guild id", pointer cursors everywhere, the avatars nobody
could see, the free square's doubled-up label, the badges on /my-events, the
board's edit mode being invisible, a review button where the sentence about
reviewing is, win-line hints on hover, and the login page keeping its chrome
behind the lock. Plus the tile list editor above.

Three of those were real bugs rather than polish:

- [x] ~~**Editing any non-bingo event failed validation**~~ — and this was
  the reported "I hit save, it jumped back to Basics and saved nothing". The
  settings form carries every type's fields at once, so an edit on a Snakes &
  Ladders event posted `bingo_size: null`. `sometimes` only skips a key that
  is ABSENT — a present null is validated — so the save died on "the card
  size field must be an integer", about a card the event does not have and a
  field the form does not show. Fixed at both ends: the client strips fields
  that do not belong to the type, and the server treats a null there as
  "not submitted" rather than as a value.
- [x] ~~**Validation errors were invisible on a tab you were not on**~~ —
  which is what made the above look like nothing happening at all. The modal
  now lists every problem in a banner above the tabs, each row clickable to
  jump to the section that owns it.
- [x] ~~**A GUILD event with no server locked everybody out, permanently**~~ —
  including the admin who has to fix it. `canEditEvent()` let an admin edit
  any event while `hasAccess()` would not let them open one, so the only
  route to the settings was behind a gate that turned them away.
  `BoardAccessService::canBypass()` now covers authors and admins alike.

### Still open from this round

- [x] ~~**A rejected claim only explained itself on hover.**~~ — done. A
  dismissible alert above the card names the square and gives the host's
  reason (or says there wasn't one), with a 1/2 navigator when several were
  rejected. Dismissals are held per position rather than removing rows, so a
  claim rejected AGAIN after a re-submission comes back — the point is that
  you find out, and "I closed it once" is not the same as "I know".
- [x] ~~**Clicking a claimed square withdraws it, with no warning.**~~ No
  hover state says so and nothing confirms it, so an accidental second click
  quietly undoes a claim. Should open the claim in a dialog with a delete
  button, matching how everything else destructive works here.
  **Done 2026-08-22** — this entry was a duplicate of the one under round
  five and was left unticked when that one was closed.
- [x] ~~**Review notes on an APPROVED claim go nowhere.**~~ The field is
  offered whatever the verdict, and only a rejection shows it. Either surface
  it on the approved square too ("host said: nice one") or stop asking for it
  when approving. Currently it is quietly discarded, which is the one option
  that is definitely wrong. **Done 2026-08-22** — same duplicate.
- [x] ~~**Say WHY an admin can see a team.**~~ — done 2026-08-22. Team
  visibility is now scoped, but
  an admin still sees everything with no indication which teams are theirs,
  which come from a shared Discord server, and which they can only see
  because they are an admin. Group the teams page under those three headings
  — useful for everyone, not just admins. (`Team::scopeVisibleTo` already
  computes the answer; it just is not carried through to the page.)
- [x] ~~**Teams and participants on the event page.**~~ — done, as its own
  page (`/events/{event}/participants`) reachable from all three event types.
  Teams expand to their members with a manage link for whoever runs that
  team; people are listed with their OSRS name and a Host badge.

  The privacy rule landed as discussed and is the part worth not breaking:
  **counts are public, names are not.** A listed OPEN event is indexed and
  reachable by anyone, and nobody joined a board game expecting to end up in
  a public directory of who plays what — so a stranger sees "12 taking part"
  and no names, while anyone in the event (including via an assigned team)
  and anyone running it sees everything. Seven tests in
  `tests/Feature/ParticipantsTest.php`.
- [x] ~~**Accept `7d` / `1w` / `1m` in the default event length.**~~ — done
  2026-08-22. It is a
  plain day count today. Parsing short forms would read better, and a month
  should mean a calendar month (28-31 days) rather than a fixed 30 — which
  means storing the unit, not just the number.
- [ ] **Use Nuxt UI's date range picker for the event window.** Two `<input
  type="date">` fields work but they are two decisions where there is really
  one. Its range picker also wants a look: every day in the range renders as
  a filled primary swatch, which is loud — the two ends should be solid and
  everything between them subtle.
- [x] ~~**The task library is a wiki cache now, not a second source.**~~ —
  done. The picker is one search box over the OSRS Wiki; the source toggle is
  gone. Every page somebody uses is written to `tasks` with a
  `wiki_synced_at` stamp and a seven-day TTL (`Task::wikiCacheIsStale`), and
  re-picking a page refreshes the row rather than creating a second one — so
  a renamed page or a new image corrects itself. Hand-written tasks are never
  stale; they have no upstream to be stale against. Admin > Tasks still
  manages them directly.

  This also retires the privacy question from round three: the table stopped
  being a record of what each clan is running and became an ordinary cache.

### Local development note

`php artisan serve` is **single-threaded**, and the SSE stream holds a
connection open for ~45 seconds per viewer. So with an event page open,
every other request — saving a tile, searching the wiki, submitting the
settings form — queues behind the stream and appears to hang for up to 45s.
It is not a bug in the app and it does not happen behind nginx + php-fpm,
which has a worker per request. Worth knowing before debugging a "hang" that
is really a queue: close the event tab, or hit the endpoint directly.


## Staging feedback, round five — 2026-08-21

Done: the wiki picker lost its source toggle and the task table became a
proper cache; rejected claims explain themselves in an alert with a
navigator; the win-line hint draws an actual line and goes quiet in edit
mode; avatars are readable; a bingo row on /my-events has a card preview;
"Live" became "Updating live" so it stops competing with the event's own
Running badge; the settings modal no longer snaps back to Basics for a frame
before closing; `/dev-login` is gone and the seeded admin has real
credentials.

### Needs your call before I touch it

- [x] ~~**Should an admin be able to edit every event?**~~ — **decided
  2026-08-22: yes, but only from the admin section.** See "The admin split"
  below. You wrote
  "canEditEvent mag idd niet alle events bewerken", which I read as agreeing
  that a site admin editing anybody's event is too much — but it is also the
  power that makes moderation possible, and `BoardAccessService::canBypass()`
  now leans on it so an admin can open an event to fix it. I have deliberately
  NOT changed it. The options as I see them:
  1. Leave it. Admin is a small, trusted set, and the audit log already
     records what they change.
  2. Read-only bypass: an admin can always OPEN any event (needed to
     moderate, and to unstick a misconfigured one), but editing needs
     authorship. Moderation actions that genuinely need a write — deleting an
     event, removing a claim — become explicit admin actions with their own
     audit entries, rather than a general edit right.
  3. Full separation: admins get neither, and moderation happens only through
     purpose-built admin screens.

  My preference was (2). Your answer went further and is better: admins may
  edit **every** event, but only in the admin section and its own event CRUD.
  On the public side an admin is an ordinary user. Built 2026-08-22.

### New

- [ ] **Per-user Wise Old Man API key.** The shared, unauthenticated WOM
  endpoint is rate-limited per IP, so once this app is busy enough the whole
  site's standings stall together. Let a user store their own key and use it
  for the events they run, so a rate limit degrades to "your boards still
  update" rather than "nothing updates".

  What matters more than the key field: **saying so.** When WOM answers 429,
  the standings page should say the sync is rate-limited and offer the
  setting, rather than showing stale numbers with no explanation. That
  message is most of the value; the key is the fix it points at.

  A key is a credential, so: stored encrypted (`encrypted` cast), never
  shared back to the browser, and the settings field shows only whether one
  is set — the same shape the site lock password uses.

### Still open from round four

- [x] **Clicking a claimed square withdraws it, with no warning.** No hover
  state says so and nothing confirms it, so an accidental second click
  quietly undoes a claim. Should open the claim in a dialog with a delete
  button, matching how everything else destructive works here.
  **Done 2026-08-22** — see the claim dialog below.
- [x] **Review notes on an APPROVED claim go nowhere.** The field is offered
  whatever the verdict, and only a rejection shows it. Either surface it on
  the approved square too, or stop asking for it when approving.
  **Done 2026-08-22** — shown whatever the verdict.
- [x] ~~**Accept `7d` / `1w` / `1m` in the default event length**~~ — done
  2026-08-22, with a month
  meaning a calendar month rather than a fixed 30 — which means storing the
  unit, not just the number.
- [ ] **Use Nuxt UI's date range picker for the event window**, and tone down
  its range styling: every day in the range renders as a filled primary
  swatch, where only the two ends should be solid.
- [x] ~~**Board layout blueprints** (round three) — still the biggest
  remaining feature.~~ **Done 2026-08-22.**

### Settled: the dev server really is the local slowness

Twice wrong before it was right, so here is the measurement rather than the
argument.

I first said `php artisan serve` serialises requests behind the SSE stream,
then retracted it after a test that showed ~2.3s per request. **The
retraction was the mistake** — that test never established its own premise.
Its "open streams" were PowerShell background jobs that take seconds to
start, so at the moment of measuring, nothing was connected.

Redone from the browser, waiting for each `EventSource` to actually fire
`open` before measuring:

- One stream connects. A **second** EventSource to the same origin never
  fires `open` at all while the first is held.
- With one stream open, a plain asset request from that tab took **23
  seconds** — the wait until the first stream hit its 45-second cap.
- `PHP_CLI_SERVER_WORKERS=8` on a second server changed neither number.
  The variable forks, and Windows has no fork.

So on Windows, any page with a live channel makes the rest of the site feel
broken in that tab, and none of it is the app's doing. It is also why a
dialog took seven seconds to open — its chunk is lazily loaded, and the
fetch queued behind the stream.

**What to do about it locally:** serve through Herd (nginx + php-fpm) rather
than `php artisan serve` when working on bingo, board or skill-race pages.
A localhost port with real concurrency would need Octane/FrankenPHP, which
is a dependency decision, not a fix to make unasked.

**What it means for production:** php-fpm gives a worker per connection, so
this does not serialise — but each viewer still holds one worker for 45
seconds at a time. Sustained concurrent viewers is roughly `pm.max_children`.
That number needs choosing on purpose before launch, not discovering.


## Test round, 2026-08-22

A dedicated sweep rather than tests-alongside-a-feature, and it earned its
keep: **five bugs, four of which nothing else was going to find.**

- **`safeHref` accepted `/\evil.example`.** A security hole, not a polish
  item. It rejected a leading `//` but not `/\`, and browsers following the
  WHATWG URL spec treat a backslash as a forward slash in a special scheme —
  so an admin-authored announcement or CMS page could link off-site from
  something that reads as a site-relative path. Now both spellings go.
- **A tile could be created off the end of its board.** `position` was
  bounded at zero and nothing else, so position 99 on a 5x5 was a row that
  renders nowhere and counts in every query asking how many tiles a board
  has. `target_position` had the same gap — a snake pointing at a square that
  does not exist.
- **A bingo card or a race accepted tile POSTs.** The upsert identifies its
  row by (board_id, position); with no board that is (null, position), which
  is not "no match" but "a tile belonging to no board" — a 500 on the NOT
  NULL constraint rather than a 404.
- **`destroy` compared `$tile->board_id === $event->board?->id`.** `null ===
  null` is true, so a board-less event could delete any tile that also had no
  board. Reachable only via the bug above, but the guard was wrong either way.
- **`i-lucide-crown` was in two icon groups**, so the picker rendered it
  twice.

Also folded in: `Board::TILE_COUNTS`, replacing the same three-entry array
inlined in two controllers and absent from the validation that needed it
most.

### What now exists

- **Frontend tests.** There were none. `pnpm test` runs Vitest over
  `tests/js/` — 87 tests across the pure helpers in `resources/js/Support/`.
  A separate `vitest.config.js`, deliberately: the app's Vite config loads
  the Laravel and Nuxt UI plugins, and Nuxt UI's virtual `#imports` is the
  exact thing that cannot resolve outside that pipeline.

  The `trans()` stub returns `t:<key>` rather than the key itself. That is
  not cosmetic — laravel-vue-i18n returns the key when a translation is
  missing, and `Support/metrics.js` keys off exactly that to fall back to a
  raw slug. A stub returning the bare key would have made every test
  silently exercise the missing-translation path.

- **`Support/bingoLines.js`**, extracted from the bingo page so the line
  rules can be tested without mounting a page full of @nuxt/ui components —
  and so there is one place mirroring what `BingoService::lines()` does in
  PHP. `tests/js/bingoLines.test.js` and
  `tests/Feature/BingoWinLinesTest.php` assert the same counts on purpose:
  two implementations in two languages with nothing else forcing them to
  agree, and a card highlighting a line the standings will not score is a
  page arguing with its own leaderboard.

- **Backend coverage** for what recent work left untested: event blueprints
  (including that every seeded one carries a type/metric pairing the create
  form would actually accept), the settings cache regression, tile editing
  bounds and permissions, and the win-line rules.

342 backend tests, 87 frontend.

### Worth knowing

The seeded-blueprint tests are the ones most likely to catch a future
mistake: they walk every row the seeder writes and check it against
`Event::availableTypes()` and the metric list for its own type. A blueprint
offering a boss on a skill race would hand the user a validation error they
did not cause, and that is exactly the kind of thing that gets added in a
hurry.


### Second pass, 2026-08-22 — the claim dialog and the live channel

**The claim dialog.** A claimed square now opens rather than acts. It shows
the verdict, what the host wrote back (whatever the verdict — that note was
being typed on approvals and then discarded), your own note, the proof link,
and either a withdraw button or the reason there isn't one. Withdrawing a
claim a host has already ruled on is refused server-side too; the dialog
doesn't offer a disabled red button for it, because a button that silently
does nothing reads as broken and its explanation was living in a `title`
attribute no touchscreen ever shows.

Caught while verifying in the browser: the submit button was a `v-else` on
the withdraw button. Once withdrawal became conditional on more than "is
there a claim", that else-branch started offering **Submit claim** on a
square that already had one. Only visible by opening the thing and looking.

**Backgrounded tabs let go of the stream.** `useEventStream` now drops its
connection on `visibilitychange` and reopens on return. Nothing is missed —
the channel sends a full snapshot, not a diff, and a reconnect with no
`Last-Event-ID` gets current state first thing.

This is worth more than it looks. A connection costs a PHP worker for its
whole life at one end and a connection slot at the other, and a tab nobody
is looking at was paying both. Measured on the dev server: with the stream
released, a request from that tab returned in 536ms; with it held, the same
request stalled past 6 seconds.

`tests/js/useEventStream.test.js` covers both directions, plus the thing
that is easy to get wrong: a deliberate disconnect must not light up the
staleness indicator, or every backgrounded tab greets its owner with a
warning about a problem that never happened.

349 backend tests, 100 frontend.


### Third pass, 2026-08-22 — the game loop, and who gets to read your email

Two areas that had no tests at all: the Snakes & Ladders game loop, and what
the app publishes about a player. Four bugs, one of them the worst thing
found in any of these sweeps.

**Every host's email address was on every event page.** `User` marks only
`password` and `remember_token` hidden, so anything handing over a whole
model hands over the email with it. `EVENT_WITH` eager-loaded a bare
`authors.user`, and `cardData()` passes `authors` straight to the browser —
so the host's email shipped with every event card, every event page, and the
admin event list. The leaderboard did the same with `->with(['user','team'])`
for players: any account can open the leaderboard of any open event, and
signing up costs nothing.

Both now name their columns, the way the board page and the live channel
already did. `tests/Feature/PlayerIdentityTest.php` asserts the absence of a
specific address across all three surfaces, so a payload that stops naming
its fields fails there rather than in somebody's inbox.

Worth saying plainly: the three places that publish player identities each
build their own payload, and two of the three were wrong. The pattern to keep
is naming columns at the eager load — a `$hidden` list would help, but it
fails open, and this is a class of bug that should fail closed.

**A tile from another board could be ticked off here.** `toggleTile` binds
the tile by id alone and never checked whose board it belonged to, so a tile
id from any other event counted towards your progress on this one — a way to
win without playing. Same shape as the `TileController::destroy` bug from
yesterday, and written with the same trap avoided: `$event->board?->id`
compares null to null as equal.

**Rolling on an empty grid walked the player to position −1.** `count() - 1`
with no tiles. The leaderboard had the same arithmetic and reported negative
tiles remaining.

`PlayerBoardTest` covers rolling, the daily limit (including that yesterday's
rolls do not count against today — the cast that caused a real 500 once),
snakes un-completing the ground they slide past, and that teammates share one
piece rather than getting one each. `LeaderboardTest` covers the ranking and
what it discloses.

377 backend tests, 100 frontend.


### Fourth pass, 2026-08-22 — account settings

**Changing your email took no password.** Once an account has one, the email
address is the recovery path: a reset link goes there and nowhere else. So a
session on its own was enough to take an account permanently — change the
address, then ask for a reset link. It now takes the password, the same as
changing the password does, with a field on the form to match. A Discord
login still sets its first email freely; it has no password to give, and
this endpoint is the only way it ever gets an address.

`AccountSettingsTest` covers both account shapes through both endpoints,
including that a password cannot be set on an account with no email to
recover it with.

### Done: a password change signs you out everywhere else

**Decided 2026-08-22: yes, sign out everywhere. Built the same day.** (I had
first read the answer the other way round and written the opposite; the copy
on the account page said so for about an hour.)

`AuthenticateSession` is now in the web group and `updatePassword` calls
`Auth::logoutOtherDevices()`. Two things worth knowing about how that works,
because neither is what the name suggests:

- The middleware is what actually ends the other sessions. It keeps a copy of
  the password hash in each session and turns away any session whose copy has
  gone stale. Without it, `logoutOtherDevices()` on its own ends nothing.
- `logoutOtherDevices()` does **not** cycle the remember token. It forces a
  re-hash of the password, and both a stale session and a "keep me signed in"
  cookie carry their own copy of the old hash — so changing it stops all of
  them matching at once. One mechanism, both routes back in. (I wrote a test
  asserting the remember token changed. It does not, and the test was wrong,
  not the framework.)

**It leaves Discord logins alone**, which is most of the user base: the
middleware returns early for a user with no password at all. There is a test
for that specifically, because a middleware that got it wrong would sign
everybody out on every request.

Kept for the record:

- [x] ~~Changing a password does not sign out anywhere else.~~ Laravel's
  `Auth::logoutOtherDevices()` is the tool, but it only bites with
  `AuthenticateSession` in the web middleware group, and that is not enabled
  here.

  Turning it on is a site-wide behaviour change, not a local fix: it signs
  people out whenever the stored password hash changes, and it puts another
  middleware in front of a stack that already has the site lock and the OSRS
  username gate in it. Worth doing before launch — changing a password is
  precisely what somebody does when they think a session is not theirs, and
  right now it does not end that session — but not worth doing quietly.


### Fifth pass, 2026-08-22 — the Discord server a team claims

Round three asked for teams to be visible only to your own server or
yourself, marked with the server name. The visibility half was built. The
marking half was taken on trust: `guild_id` and `guild_name` came straight
off the form with no rule on either.

That is a spoof in a feature whose whole point is provenance. Anyone could
label their team as somebody else's clan — and because `scopeVisibleTo`
publishes a team to every member of the guild it names, doing so also pushed
that team into that clan's list.

Now: `guild_id` has to be a server the person is actually in
(`Rule::exists` against their own `user_guilds`), and `guild_name` is read
from that row rather than accepted from the form — otherwise the first check
is walked around by naming a server you ARE in and labelling it as something
else. Clearing the server clears the label with it; a name with no id behind
it is the same unverified claim by another route. The client no longer sends
`guild_name` at all.

Also pinned: the picker binds to a string, so "no server" arrives as `''`
rather than a missing key, and it is `ConvertEmptyStringsToNull` that turns
that into a null the `exists` rule skips. Without that middleware every
serverless team would fail validation, so it is worth a test rather than an
assumption.


### Sixth pass, 2026-08-22 — public pages and the first-run flow

No bugs found here, which is worth writing down as plainly as the bugs: both
controllers were already careful, and the tests exist now to keep them that
way.

Two guards worth knowing about:

- **A page cannot shadow a real route.** The CMS sits on a catch-all
  `/{page}` at the very bottom of the route file, so a row slugged `events`
  would be a silent, total swap of the events list if that ordering ever
  changed. There is a test for it now rather than a convention.

- **`joinableBoards` asserts the VALUE of `size`, not just that the key is
  there.** That endpoint shipped a production-only 500 once: it selected a
  column that had moved to another table, and SQLite reads an unknown
  identifier in a SELECT list as a string literal instead of raising — so dev
  looked fine and PostgreSQL 500'd. Checking the value catches it under
  SQLite, because the literal comes back as the word "size".

399 backend tests, 100 frontend.


### The admin split, 2026-08-22

Decided: **an admin may edit any event, but only from the admin section. On
the public side of the app an admin is an ordinary user.**

`User::canEditEvent()` and `isEventOwnerOrAdmin()` both used to start with
`isAdmin()`. That made the power invisible: an admin browsing the site had
edit buttons on everybody's events and could write to any of them without
ever having decided to. Now `canEditEvent()` answers on authorship alone, and
`isEventOwnerOrAdmin()` is simply `isEventOwner()`.

**Reading did not change, deliberately.** `BoardAccessService::canBypass()`
still lets an admin open any event, and the participants list still shows
them names — you cannot moderate what you cannot see, and hiding a list on
the way in protects nothing when the same person can read the event itself.
The split is about writing.

**The admin section got its own routes** — update, destroy, teams and invites
under `/admin/events/{event}`. They are not a copy: each asserts `isAdmin()`
and then hands off to the same controller method the public route uses,
passing `asAdmin: true`. One implementation, one set of validation rules, one
audit trail; the only thing that differs is who is allowed to arrive. The
flag is not something a caller can grant itself — nothing reachable from the
public side passes it.

`BoardSettingsModal` takes a `basePath` prop so the admin page points the
same modal at the admin endpoints. Creating still posts to `/events` on both
sides: a new event has an author by definition, so there is nothing to split.

Eleven tests broke, which was the point — they had encoded the old rule.
Three of them were named `an_author_can_...` while acting as a site admin,
which only ever worked because of the bypass. They say what they meant now.

**Known gap, deliberately not filled:** tile and bingo-square editing are
public-side host actions, so an admin who is not an author cannot fix a
single offensive tile — only delete the whole event. If that turns out to
matter, the fix is an admin-side route for it, not a hole in the rule.


### Templates, from both ends — 2026-08-22

A blueprint used to be a name. It is now the shape of an event, and it can be
written from an event as well as read into one.

**What changed underneath.** `event_blueprints` gained a `settings` JSON
column, plus `created_by` and `guild_id`. JSON rather than thirty nullable
columns because the fields a blueprint carries differ per event type — a card
has a win condition and no dice, a board has a roll limit and no card — so
columns would put every type's fields on every row and leave most of them
null forever. The shape is enforced by `EventBlueprint::APPLICABLE`, an
allow-list, because these settings end up in a form that posts to the create
endpoint: a stored key nobody vetted would be a stored field nobody vetted.
There is a test for a tampered row.

**Creating an event starts with a gallery.** "Template" is the first step of
the stepper, ahead of Type, and each card says what you are about to get —
"Teams · 5×5 card · First line wins · Host checks claims" — rather than just
naming a format. Picking one fills in what it carries and jumps to whichever
question it did not answer; "Start from scratch" is a card of its own, so
somebody who knows what they want is one click past it. The old autocomplete
under the title is gone: a one-line suggestion cannot show a grid size, and
picking a format you cannot see is picking a name.

**Saving one is offered twice, deliberately.** In the edit modal, because
that is when a host is thinking about the settings, and again on the event
page once the event has ended, because that is when they know whether the
format was worth keeping. The finished-event prompt lives in
`EventTypeHeading` — the one component all three event pages share, and it
already works out whether an event has ended.

**Visibility mirrors teams**: the set that ships with the app (no owner, no
server), your own, and your clan's. A format somebody wrote for their clan
carries their event's settings and their clan's name in the title as often as
not, so it is not obviously public. The server a template is filed under is
checked against the person's own guilds, the same as a team's — it decides
who else sees it, so it is a claim rather than a label.

**The dates are the one thing a template must not carry.** A format that
starts every event in July is a format nobody can use in August.

**The seeded set now carries real settings.** Where a description used to
tell the host what to set — "give it a roll limit", "set the win condition" —
the settings do it, and the wording says what the format IS rather than what
is left to do.

438 backend tests, 112 frontend.


### Branding on the pages, plainness in the app — 2026-08-22

The coming-soon page's look, applied to the pages people READ and deliberately
not to the ones they work in. Landing pages, the guides and every CMS page get
it; events, teams, settings and admin stay flat. The list lives in
`Support/landing.js` and is keyed on the Inertia component name.

Two techniques, both lifted from that page rather than re-picked by eye: two
soft pools of gold light behind the near-black base, and a 4px pixel grid over
everything at the edge of visible. Cards on those pages get the OSRS interface
panel — a tan bevel lit from the top-left, a hard dark seat under it — built
from box-shadows, so it scales and costs no asset. Light mode gets the same
lighting at a fraction of the strength; at dark-mode opacity on a pale
background it reads as a stain.

The whole thing is **one fixed layer at `z-index: -1`** plus a
`display: contents` wrapper. No page component knows about it and nothing
about the layout changes.

### The lock lets the shop window stay lit

`EnsureSiteUnlocked` now passes the public pages through: the landing pages,
the guides, the CMS pages and the sitemap. The lock exists to keep the APP
unannounced, not to hide what a search engine indexes.

**Allowed by route name, not by path.** `pages.show` is the CMS catch-all
`/{page}`, which matches any single segment — as a path pattern it would have
let `/events` and `/teams` through with it. There is a test that says exactly
that.

The header trims itself while the door is shut, to the guides and About. It
keys off the `site.locked` prop, which already means "shut for THIS visitor" —
false for an admin and false for anyone who has typed the shared password. An
extra `isAdmin` check there would have got the second of those wrong.

### Found on the way: SSR rendered the page before last

`usePage()` reads a module-scoped store that Inertia's own App component fills
in during ITS setup. AppRoot wraps App, so AppRoot's setup runs first — and on
the server, where the app is rebuilt per request but the store is module state
that survives between them, **AppRoot was reading the previous request's
page.**

Measured, not guessed: requesting `/` and `/events` alternately, the
server-rendered chrome lagged exactly one request behind, every time.

It predates all of this and it affected the announcement banner and the
chromeless-page rules as well as the new background. The obvious worry was
worse — one visitor's page served to another — so that was checked
specifically: nothing user-specific is server-rendered here (the header's
menus are all `<client-only>`), so the damage was a wrong announcement and a
missing background, not a leak.

Fixed by reading `initialPage`, which setup() is handed per request, until the
component has mounted. That is also correct on the client's first render, so
hydration still matches; after that the store is authoritative, because
AppRoot persists across client-side visits while `initialPage` never changes
again.

444 backend tests, 122 frontend.


### Lock on means nothing interactive — 2026-08-22

Reported: "sinds het slot erop zit, waarom mag ik nou alsnog een account
aanmaken?" Fair. The lock let the public pages through but left every hook
into the app on them.

- **Registration is closed while the site is locked.** `/register` was in the
  always-allowed list next to `/login`; it is not the same thing. Signing in
  stays open because whoever is building the site has to get in. Password
  reset stays too — recovery for an account that already exists, not a way to
  acquire one.
- **The Discord back door is shut with it.** Discord is a sign-in route AND a
  way to get an account without ever seeing a registration form, and the OAuth
  routes have to stay open for the admin. So the callback refuses an unknown
  `discord_id` while locked and signs an existing one in as normal. Worth its
  own test file: from the route list the door looks shut while
  `auth/discord/callback` quietly makes users.
- **The public pages drop their calls to action.** Every landing hero had an
  `isAuthenticated ? /events : /login` pair, and while locked both are dead
  ends. They are replaced by one line saying the app is not open yet — a
  button that lands on a password box reads as broken; a sentence reads as
  not-yet.

The hero copy still says "log in with Discord to get started". That sentence
is CMS content (`page.subtitle`, falling back to `home.description`), so it is
editable in admin → content rather than something to branch on in code.

### The header was serving the previous visitor's menu

Found while checking the above, and the reason it looked like the trimming was
not working: a signed-out visitor's HTML arrived carrying a signed-in
visitor's nav — links to /events, /my-events and /teams on a page that was
meant to show none.

Same root cause as the SSR lag fixed earlier, one level down. `useAuth()`
reads `usePage()`, and AppHeader calls it from ABOVE the page component, where
the shared store still holds the last request. Fixing AppRoot alone had not
fixed the header.

Now `Support/pageState.js` provides the corrected page from AppRoot and
`useAuth()`/`useSiteLock()`/AppHeader read through it. Verified by alternating
a signed-in and a signed-out request against `/` — six interleaved requests,
every one correct.

**One test was deleted for being worthless.** A feature test asserting the
served HTML contains no `/events` link passes whatever the header does: the
suite runs with `INERTIA_SSR_ENABLED=false` (phpunit.xml), so that HTML never
contains a rendered nav at all. Replaced with an assertion about the props the
nav is built from, plus `tests/js/pageState.test.js` on the composable itself.

452 backend tests, 125 frontend.


### Walkthrough, 2026-08-22

Every route, every event type, and the main flows clicked through in a
browser. Four things found, all fixed.

**A copy of a prop only stays right if something copies it again.** The bingo
page seeded `rows` from `props.standings` and then only ever updated it from
the live channel. So your OWN actions were the slowest thing on the page:
approving a claim returned fresh props, and the standings kept the old numbers
until the stream got round to saying so. Seen as approving a square and being
told "nobody has marked a square yet" next to a counter reading 1 of 16. The
same pattern was in `BoardShow` (`livePlayers`) and `SkillRace` (`rows`); all
three now watch the prop.

**`registration_open` gated the front door and left the back one open.** It is
an admin switch labelled "registration", and it only ever checked the
email/password form — a Discord login sailed past it. It now covers both.

**Two settings descriptions had become false**, both by today's own changes:
"Discord login always stays available" (no longer true of the registration
switch) and "the lock screen and the login pages stay reachable" (the public
pages are reachable now too). Rewritten.

**Nothing else broke.** All 33 parameterless routes 200; all four event types
render, plus their leaderboard and participants pages. Created an event from a
template, claimed a bingo square, approved it, rolled a die (3 → 9 on a six),
marked a tile complete, entered and left a race, created and deleted a team,
saved profile and site settings, and read every admin page.

Two things that looked like bugs and were not, worth writing down so the next
sweep does not chase them:

- **No dice on a Snakes & Ladders board** until the tile you are standing on
  is marked complete. That is deliberate — rolling is the reward for finishing
  what you are on — and the guard is in BoardShow.
- **Deleting a team from the UI did nothing** under automation. It is behind
  `window.confirm`, which an automated click dismisses. The endpoint works.

453 backend tests, 125 frontend.


### Mail, actually sent — 2026-08-22

Mailpit locally (SMTP 1025, inbox on 8025), and the whole reset walked over
real HTTP with its own cookie jar: ask for a link, read the mail out of
Mailpit, open the link, save a new password, sign in with it, and confirm the
old one is refused. Every step passed — after one fix.

**`POST /reset-password` was blocked by the site lock.** The allow-list had
`reset-password/*`, which matches the link from the email because that
carries a token, and NOT `reset-password`, which is where that page posts to.
So a locked site let somebody open the link, type a new password, and
answered 423 when they saved it. The recovery path was dead in exactly the
state that needs it most — and every existing test stopped at the GET, so
nothing caught it. Both spellings are allowed now, with a test that walks the
whole flow rather than its first step.

**The mail wears the app's colours.** `resources/views/vendor/mail/html/
themes/osrs.css` re-skins Laravel's default rather than replacing it — the
structure is what makes a markdown mailable render and it is well tested
against the clients that matter, so only the palette and the type change.

Deliberately a LIGHT theme, unlike the app. A dark email is a coin toss:
Outlook and several webmail clients override backgrounds and leave pale text
on white, and this is the one message somebody has to be able to read.
Parchment and ink, with the gold on the button and the rules. Georgia rather
than Cinzel, because webfonts do not load in most mail clients and Georgia is
the closest widely-present face.

There is a test asserting the rendered HTML carries the brand colours and
none of Laravel's zinc. Not decoration: the theme is chosen by one line in
`config/mail.php`, and losing it fails nothing — the mail just quietly goes
back to looking like scaffolding.

**Worth knowing for next time:** `php artisan serve` reads `.env` once, at
startup, and an already-set environment variable wins over the file. A mail
change needs the dev server restarted; `config:clear` alone does nothing.
`.env.example` says so now.

462 backend tests, 125 frontend.


### Mobile sweep, 2026-08-22

Reported: too many pages with controls running off the side and not wrapping.
Measured on a 375px viewport by loading every page into an iframe at that
width and reporting any element whose right edge passed it. Six real problems,
all fixed; every page now reports `scrollWidth === 375`.

- **The event toolbars ran off the screen.** `shrink-0` kept them at their full
  natural width, so `flex-wrap` never got the chance to do anything. The bingo
  card was the worst at **772px of controls on a 375px screen** — seven buttons
  in a row that refused to shrink. Now `sm:shrink-0`: hold your ground once
  there is room for it.
- **Four page headers never stacked.** Title, description and an action button
  side by side left the description a ~150px column and four ragged lines.
  `flex-col` until `sm`.
- **The footer links were `justify-end` at every width**, so on a phone the
  shorter second row hugged the right edge.
- **A badge on the ideas page** carried a whole phrase and pushed the page six
  pixels wider than the screen.
- **A bingo square could stop being square.** `aspect-square` is a floor, not
  a ceiling — a tile holding an avatar grew taller than its neighbours and
  stood proud of the row. `overflow-hidden` plus an avatar sized to the tile:
  every square now measures 62×62, none excepted.
- **Titles and body copy were centred.** Fine on a wide column, poor at 375px
  where a centred paragraph breaks into ragged lines and the eye hunts for the
  start of each. Left-aligned below `sm` on the landing pages.

**A bingo square stops being a label on a phone and becomes a token.** A 5x5
grid leaves about 62px a side, and four things were competing for it: points,
icon, two lines of 11px text, and a face. The text is the one that cannot work
at that size — "Complete a..." carries nothing — so it goes when an icon is
there to carry the meaning, and the icon grows into the room. Nothing becomes
unreachable: tapping opens the claim dialog with the full title, and "Fill in
tiles" lists every square in words. A square with a task and no icon keeps its
label, or it would be blank.

### Checked from a player's seat, not just an admin's

Fair criticism, and it had been true of every browser check so far: an admin
sees a seven-button toolbar where a player sees one, plus a create button and
admin nav nobody else has — so "it fits" from that seat proves less than it
looks.

There are now two dev logins to switch between, both on seeded accounts:
`admin@osrs-events.test` and `player-seat@example.test`. The player's pages
were swept the same way and came back clean, and their view turned out to
carry something the admin's never does — the "we can't find your name on Wise
Old Man" banner, with two more buttons in it.

Worth keeping: the lock has to come off to walk the app as a player, because
a player who signs in while it is on lands on the lock screen. That is correct
behaviour, and it means player-seat checks need the lock lifted and put back.

463 backend tests, 125 frontend.


### Templates that bring the board — 2026-08-22

The biggest item on the list, and the half that was missing: a template
carried a grid size and a win condition, which is three clicks, and threw away
the evening a host actually spends deciding which task sits where.

`event_blueprints.layout` now holds a snapshot of the board — every tile with
its type, its jump target and its task, or every filled square with its
points and whether it is the wildcard. A separate column from `settings`
because they have different lives: settings is a flat map the create form
applies field by field under an allow-list, a layout is up to 81 rows written
by a different code path after the event exists.

**Each entry carries the task id AND the title it had.** The id keeps the tile
linked to the shared Task while that still exists; the title survives the Task
being renamed or deleted, so a year-old template still describes itself
instead of turning into a grid of blanks. There is a test that deletes the
task and checks the tile still says what it asks for.

**Positions that no longer fit are dropped, not clamped.** A layout belongs to
one grid size. The picker warns before you change the size, and the server
leaves out what falls off the end — stacking three tiles onto the last square
would be worse than an honest gap. A jump pointing past the new last tile
becomes a plain tile rather than a snake to nowhere.

**The blueprint id is a claim, not a permission.** It arrives from the browser
and is re-read through `visibleTo()`, or a guessed id would be a way to pull
another clan's board into your own event. Tested.

Found while verifying in the browser, and it would have made the feature look
broken: **the gallery was 20 rows sorted by title.** Right for the
autocomplete it used to be, wrong for a gallery — a format saved as "Weekend
format" simply never appeared once the seeded set filled the first twenty. Now
sixty rows, yours first, then your clan's, then the set that ships with the
app. The `orderBy` also had to come out of `scopeSuggestable`: applied inside
the scope it ran first and silently beat the caller's own ordering.

478 backend tests, 131 frontend.


### Two small ones — 2026-08-22

**The event length takes `10d`, `2w`, `1m`.** A bare number still reads as
days, so an existing setting keeps working — and the migration carries the old
`default_event_duration_days` row across rather than quietly resetting
somebody's deliberate 30 days to the default.

The unit is stored, not converted. That is the whole point: an event starting
31 January and running "1m" ends on 28 February, and only the unit can tell
you that. Flattening it to 30 days at save time throws away the one thing that
makes the answer right, and is wrong by up to three days depending on the
month.

`app/Support/EventDuration.php` and `resources/js/Support/duration.js` are the
same calendar in two languages — the form computes the end date before
anything is submitted and the server computes it from the same setting — so
they are tested against the same awkward dates on purpose: 31 January, a leap
year, and a year end. JavaScript needs the extra care: `setMonth` on 31
January rolls into 3 March, which is not what anybody means by a month.

Worth remembering: **a migration that writes a setting has to clear the
settings cache itself.** `Setting::set()` does it; the query builder does not,
so the row landed and the app kept serving the old value. Caught by reading it
back and getting the default.

**The teams page says why each team is on it.** Grouped under "Your teams",
"From your Discord servers" and "Other teams" — the last one explaining that
an admin sees them to moderate, not because they are part of them. The reason
is decided server-side, because the client has no business knowing which
Discord servers somebody is in, and the strongest claim wins: a team that is
both yours and on your server shows as yours.

492 backend tests, 145 frontend.


### The live channel, checked properly — 2026-08-22

Asked whether the SSE side has enough tests and whether it holds up with
several people in an event. Two real bugs came out of looking.

**A bingo card's own rules were not in its fingerprint.** The payload carries
`winLines` so that "a host changing which shapes count mid-event reaches every
open card" — except the fingerprint watched only the claims and the squares,
so that change woke nobody and the comment described something that did not
happen. The win condition was worse than cosmetic: it decides the standings,
so an open card went on scoring by a rule that had been switched off. All
three of `win_condition`, `line_bonus` and `win_lines` are in it now.

**A backgrounded tab still said "Updating live".** The tab drops its
connection on purpose when hidden — that is the optimisation from earlier —
but `streaming` stayed true, so the indicator claimed to be live in the one
state where it definitely is not, and the state a tab spends most of its life
in. It now goes quiet while suspended and comes back on return.

That second one was found the hard way: a two-tab live test kept showing
nothing arriving, and the reason was that the Browser pane reports its tab as
`hidden`, so the channel was suspending itself. I was measuring my own feature
rather than the app's liveness.

**What the live push does do**, verified against a change made entirely
outside the browser (tinker, no HTTP): the stream opens, sends a snapshot, and
sends a second message once the fingerprint moves, carrying standings that
match the database exactly.

**What could not be verified locally: several watchers at once.** `php artisan
serve` serves one stream at a time, so a second tab's `EventSource` never
connects — 53 aborted attempts in 85 seconds while another held the worker.
Even my own diagnostic requests stole it. Multi-viewer behaviour needs
nginx+fpm to test, which is the same conclusion the dev-server note reached
earlier.

Also added: a test that the stream announces itself with the right headers.
`X-Accel-Buffering: no` is the one that cannot be caught locally — nginx
buffers a proxied response by default, which holds every event until the
connection closes, so the page would sit silent and then receive everything at
once. Nothing else in the suite would notice it going missing.

495 backend tests, 146 frontend.


## Staging feedback, round six — 2026-08-22

Tested from a second account (MB Test) against a real second browser, which is
why several of these are things no single-seat check would have found.

### Broken

- [x] ~~**Invite links cannot be created.**~~ — cause found and fixed
  2026-08-24. Every invite action asserted OWNERSHIP while the tab they live
  in renders for anyone who may edit the event, so a co-host opened Invite
  links and each button answered 403 — which reaches them as "Something went
  wrong. Please try again." Handing out and revoking links is running an
  event, not owning one; deleting the event stays the owner's alone.
- [x] ~~**Snakes & Ladders has no dice.**~~ — fixed 2026-08-22. Reported as "totally broken". It is
  the deliberate gate — rolling is the reward for marking the tile you are on
  complete — but the gate is invisible, so the page reads as missing its main
  control. Show it disabled with the reason instead of not at all.
- [x] ~~**A tile's target does not match the board.**~~ — fixed 2026-08-24,
  and it was an off-by-one with two editors disagreeing. The tile list editor
  offers targets by the number PRINTED on the tile; the click-a-tile modal
  bound its input straight to `target_position`, which counts from zero. Type
  9 there and the arrow goes to tile 10. The modal speaks printed numbers
  now, bounded to tiles that exist.
- [x] ~~**Tile edits do not reach other viewers.**~~ — fixed 2026-08-22. The second browser kept the
  old tile after a save. The Snakes & Ladders channel streams player
  positions only; bingo streams its squares.
- [x] ~~**The OSRS username is asked twice.**~~ — the returning half is
  fixed 2026-08-22; why it was asked twice is still open, see below. Given during setup, then claiming
  a bingo square redirects to the same question again — and answering it does
  not return you to where you were.
- [x] ~~**The claim note does not reset between squares.**~~ — fixed
  2026-08-22. Open a second tile
  and the previous note is still in the field.
- [x] ~~**A reviewed card rendered half pending, half approved** after
  approving one claim.~~ — cause found and fixed 2026-08-23: only hosts ever
  re-fetched their own claim state, so a player watched the standings award
  them points while their own square still read "waiting for review".

### Wrong or missing

- [x] ~~**The "New accounts" toggle is meaningless while the site is
  locked**~~ — it says so, and since 2026-08-22 it is disabled while the lock
  is on rather than being a live switch under a note saying the note wins.
- [x] ~~**Copy**~~ — fixed 2026-08-22. "You log in with Discord, so there's no email on this account
  yet" should read "You logged in through Discord, ...".
- [x] ~~**Onboarding said no events were joinable**~~ while several public
  open events exist. The cause was a refused request read as an empty result
  — the site lock answers that endpoint with 423 for anyone who has not typed
  the shared password, and the modal fell through to "no events to join".
  Told apart since 2026-08-22. Tightened again 2026-08-24: the list also
  drops events that have ended or been paused, since both refuse a join and
  this list is somebody's first act on the site.
- [x] ~~**Bingo has no join.**~~ — fixed 2026-08-22, and so does every other type. A new player can click a square and start
  scoring. It should take a deliberate join — and it has to, before the
  RuneLite plugin exists.
- [x] ~~**A skill race never shows its skill icon.**~~ — fixed 2026-08-22. The metric is chosen and
  then never drawn. Find it a place.
- [x] ~~**"Invite link or code"**~~ — they are one invite in two shapes, and
  the app left people to work that out: the gate asks for "code or link"
  while the host's list showed a six-character code and no way to get the
  link at all. Both are copyable now, and one line above the list says they
  are the same thing. Fixed 2026-08-24.

### Confirmed working

- Enter/leave on a race updated the other browser live, both directions.
- The review counter went up on the host's screen when the other browser
  submitted a claim.
- A player does not see another player's pending claim — noted as correct.


### What was fixed straight away, and what is still open

**Fixed**

- **The dice is always on screen now**, disabled with the reason when the
  tile you are standing on is not ticked off yet. The gate was deliberate —
  rolling is the reward for finishing what you are on — but hiding the card
  entirely made the page read as missing its main control, which is the
  correct reading of a board with no way to play on it.
- **The name page brings you back.** Nothing was storing the destination, so
  `redirect()->intended()` had nothing to read and always fell back to
  /events. A safe request comes back to itself; a write comes back to the
  page it was made from, because its URL only answers that verb.
- **The claim note resets on every opening**, not only when the square
  changes. Carrying a note over is how somebody submits the wrong one.
- **The skill icon** sits beside the line that already names the skill
  ("Ranked by Mining XP gained") rather than needing a place of its own. A
  boss race keeps the trophy — there is no Zulrah icon.
- **The New accounts toggle says the lock overrules it.**
- **The Discord copy.**

**Invite links: the server is not the problem.** Creating one as the owner of
an OPEN event works, and there is now a test for exactly that case — every
existing invite test used an INVITE event, so the reported combination was
the untested one. What was wrong is that the failure said nothing: any
non-JSON response, any empty message, became "Something went wrong. Please
try again." A 419 from a stale session, a 403, a 500 and a dropped connection
were indistinguishable. Each says what it is now, with the status in the
toast and the response body in the console, so the next occurrence carries
information.

**Still open, and why they need more than a fix**

- **Why the name was asked twice.** The standalone page prefills from the
  display name, so "MB Test" being in the box is the suggestion, not a
  memory — meaning `osrs_username` really was empty after the wizard. Either
  the wizard's step did not save or it was skipped. Needs reproducing from a
  fresh account rather than guessing.
- **Bingo has no join**, and a new player can score by clicking. This is a
  product decision with a schema behind it (participation is currently
  implied by a claim), and it has to be settled before the RuneLite plugin.
- **Onboarding said nothing was joinable** while open events exist. Worth
  checking whether the filter is too narrow before deciding whether setup
  should ask which Discord servers somebody is in.
- **Tile edits do not reach other viewers**, and **a tile's target did not
  match the board**, and **a card rendered half pending, half approved.**
  All three need reproducing before they are worth touching.
- **"Invite link or code"** — whether those are one thing or two is a
  question about the model, not a bug.

498 backend tests, 146 frontend.


## Content review before launch (step 8)

Flagged 2026-08-20 by the owner: do this **after the build work is done**,
not alongside it — the copy depends on what the app actually ends up doing.

- [x] ~~**The site still describes itself as a Snakes & Ladders app.**~~ —
  **copy pass done 2026-08-21.** Flagged by the owner on the onboarding
  modal's opening line, which stopped being true when skill races shipped.

  ~50 product-level strings rewritten across `home.*`, `about.*`,
  `onboarding.*`, `nav.*` and `seo.*`. The rule applied throughout: **Snakes &
  Ladders stays named and prominent** — it is the flagship format and
  `/osrs-snakes-and-ladders` exists to rank for it — but it stops standing in
  for the whole product. So `seo.home_title` is now "Free Snakes & Ladders and
  Skill Races for Clans" rather than dropping the keyword.

  The home feature list was **reordered, not just reworded**. It used to open
  with boards and then spend two more entries on dice and snakes, which reads
  as a Snakes & Ladders site with extras; it now opens with "more than one
  kind of event" and gives S&L and skill races one entry each.

  Two further stale claims surfaced while reading the rendered page rather
  than the file: **"No passwords needed"** on the Discord feature (email
  signup shipped since), and `about.privacy_body` still saying we collect only
  a Discord username, ID and avatar — which stopped being true the same day,
  and now also omits the OSRS account name. Both corrected. The privacy
  *policy* itself is the item below.

  **Still open:** the only screenshot on the home page is a board. The copy
  now says so explicitly ("one of the formats you can run") rather than
  claiming a leaderboard shot that does not exist — but a second image
  showing a live skill race is worth adding, and would let the preview
  section carry the same "more than one format" message the features do.

- [x] ~~**Outbound mail has never been configured, and password reset is dead
  without it.**~~ — **walked end to end against Mailpit on 2026-08-22**, see
  "Mail, actually sent" below. What remains is a deployment step, not code:
  point MAIL_* at Brevo and verify the From domain.
  `/forgot-password` works end to end — the broker returns
  `passwords.sent`, the notification renders, the link points at the right
  route — and then `MAIL_MAILER=log` writes all 13KB of it to
  `storage/logs/laravel.log`. The user is told the mail is on its way. There
  is no error anywhere. Verified 2026-08-21 by sending a real reset link
  against a throwaway account.

  `.env.example` and the README now document Brevo over plain SMTP (300/day
  free, EU-hosted, no package needed because Laravel's `smtp` mailer covers
  it). **Nothing is configured yet** — that is a deployment step, and the
  From address has to be at a domain verified with whichever provider wins.

  Two things deliberately left undecided rather than assumed:
  - **Queueing — decided 2026-08-22: no queue.** Laravel's `ResetPassword`
    notification stays synchronous, so the SMTP round trip happens inside the
    web request. Fine at reset-only volume, and it avoids running a worker
    for one email. Revisit only if mail becomes a feature rather than a
    recovery path.
  - **Email verification — decided 2026-08-22: manual registrations must
    confirm their address.** `User` does not implement `MustVerifyEmail`
    today, so an email/password account is usable with an address nobody
    proved they own. Not yet built.

    **The Discord half does not apply as asked.** The question was whether a
    Discord login hands us an email we could take as already verified. It
    does not: the scopes are `identify` and `guilds`, and `email` was left
    out on purpose (see DiscordController::redirect). A Discord account here
    has no email address at all until the person sets one on the account
    page — and one they typed is exactly the kind that needs confirming.

    So there is a question behind the question: **add the `email` scope?**
    Discord does tell us whether the address is verified on their side, so
    adding it would let a Discord login arrive with a trusted address and
    skip the whole flow. Against it: it asks for more than the app needs,
    on a consent screen people read, for a field the app has lived without.

    **Parked 2026-08-22 at your request — both halves decided later.** Not
    started, and nothing else depends on it. When it comes back round, the
    order that matters is: decide the scope first, because it decides whether
    a Discord account ever has an address to verify, and that decides what
    the manual-registration flow has to handle.

  ~~Also worth noting: the reset mail is Laravel's stock template, zinc button
  and all, with none of the OSRS branding the rest of the app has.~~ **Done
  2026-08-22** — see the theme note below.

- [x] ~~**Privacy policy needs an update.**~~ Done 2026-08-24 — see the entry
  at the end of this file. `/privacy` was written for the
  Discord-only version of the app and no longer describes what is collected.
  Since then: email/password accounts (email address, hashed password,
  password-reset tokens), an audit log recording admin actions against named
  users and retaining their names after deletion, site settings, and invite
  records naming who created and who accepted them. The audit log is the one
  most worth being explicit about — it deliberately keeps a deleted user's
  display name, which is exactly the kind of retention a privacy policy has
  to state rather than imply.
  Check `/terms` at the same time; it has had no review at all on this stack.

  **Draft ready 2026-08-24: `docs/legal-review.md`.** Six things the pages do
  not say that the code does — session IP and user-agent rows, the audit log's
  90-day retention, invites naming who created and who used them, the new
  event emails, a team's Discord server link, and the webhook posts once that
  switch goes on — each with the table it lives in and wording you can paste.
  Nothing has been written to the live pages: they are CMS content and this
  is your read-through to make, not mine.

  **Re-flagged 2026-08-22 at your request: both pages need a fresh read-
  through before launch, not a patch.** Since the text above was written the
  app has also gained an admin section that edits other people's events, a
  team-to-Discord-server link, and sessions that end on a password change —
  all of which are things a privacy policy is expected to describe.
## Staging feedback, round seven — 2026-08-22

Ten items, most of them consequences of two things the model never had: an
explicit way to say you are playing, and a live channel that carried the
event itself rather than only what is played on it.

### Fixed

- [x] ~~**The dice disappears on an empty square.**~~ Not the gate that was
  reported last round — a condition problem underneath it. Standing on a
  square with no task left nothing to do: no dice, because the tile was not
  ticked off, and no way to tick it off, because there was nothing to
  complete. The board simply stopped. Rolling is now gated on the tile
  *having* a task, so an empty square rolls straight on.

- [x] ~~**A join button for every event type, not only races.**~~ Asked for
  twice. Joining is now one record (`event_participants`) and one action,
  whatever the type — the button says the same thing on a bingo card as on a
  board, and what that means for the type is decided on the server: a race
  still enters the standings and baselines them, a board hands out a player
  board, bingo needed nothing beyond the row.

  Two consequences worth knowing:

  - **Opening a board no longer enrols you in it.** It used to create a
    player board for whoever looked, so every passer-by turned up in the
    player list and on the leaderboard at square one. Joining creates it now.
  - **Playing is still joining.** Rolling, ticking a tile and claiming a
    square each write the row themselves. Nobody should have to press a
    button twice, and a player missing from the list because they never
    pressed it would be worse than an unasked-for row.

  Existing players were carried across by the migration, from all three play
  tables, so events running right now did not empty out.

- [x] ~~**The skill icon on every standings row.**~~ Plus the unit, because
  a bare number stops saying what it counts once you have scrolled past the
  heading.

- [x] ~~**The "New accounts" toggle is now actually blocked**~~, not merely
  annotated. A live switch under a note saying the note wins is a control
  that does nothing, and the only way to find that out was to flip it and
  try to register.

- [x] ~~**Snakes & Ladders tile edits reach other viewers**~~, arrows
  included. The channel fingerprinted player positions and nothing else, so
  a host putting a task on a tile or moving a ladder mid-event reached
  nobody — the second browser kept the old board until it was reloaded. The
  bingo card streamed its squares from the start; this is the same thing for
  the same reason.

- [x] ~~**Event changes reach other viewers.**~~ Moving a skill race's dates
  updated nothing, on any type: every channel streamed its payload and none
  of them streamed the event. **Shipped wrong the first time and fixed the
  same day — see "the fingerprint that could not change" below.** Each
  channel now sends the event itself, built by `App\Support\EventCard`, the
  same object the page was rendered with; the page swaps one for the other.
  Measured at 0.5–3.0 seconds from a write to the screen, on title, dates and
  the browser tab, on all three page types.

- [x] ~~**The OSRS name typed during setup is kept.**~~ It was discarded on
  Skip and on Finish, which is why the standalone page asked for it again.

- [x] ~~**Onboarding's "nothing joinable" now tells a refusal apart from an
  empty list.**~~ It reported both the same way.

- [x] ~~**"Click a square when you have completed it" moved to the
  Information card.**~~ It sat above the board, which is also what pushed
  the grid out of line with the cards beside it.

- [x] ~~**"Who is playing" is hidden on a small race.**~~ The standings
  already list everyone; the button only earns its place once the table is
  long enough to hide someone.

### Found while working

- [x] ~~**A bingo event with no card yet was a 500.**~~ The page creates one
  on the way in, passing only an id — so `size` was null on the instance in
  hand (a column default only reaches PHP on a re-read) and the line counter
  died on it a few statements later. Only reachable on an event created
  outside the normal flow, which is exactly the kind of row a migration or
  an import leaves behind. Found by a test written for something else.

### Verified in a browser

- A ladder moved from outside the app redrew on an open board without a
  reload.
- An end date changed from outside the app updated the open page — after the
  delay the dev server imposes: the reload request queues behind the SSE
  connection holding the single worker. Not the feature.
- Join and leave both flip the button and raise their toast; leaving an
  event you have played in is refused with the message that says to ask a
  host.

### Still open from earlier rounds

Invite links (the client now reports what actually failed, the model
question remains), the tile-target mismatch, the half-pending/half-approved
render, "invite link or code", the Nuxt UI date range picker, boss icons via
pets, per-user WOM key, scoping the task library, retiring `TEAM_MANAGER`,
the privacy and terms rewrite, and pointing mail at Brevo.

518 backend tests, 151 frontend.
## The fingerprint that could not change — 2026-08-22

Reported the same day it shipped: *"I still don't see a date update via SSE
in a viewer session."* Correct, and my own verification had said otherwise.
Two separate mistakes, both worth writing down.

**The model was 45 seconds old.** The stream loads the event when the
connection opens and then asks the channel the same question every three
seconds for the next forty-five. The fingerprint's queries — players, tiles,
claims — re-read every time, but the event's own attributes are just the
instance's, frozen at whenever that viewer connected. So an edit could only
surface when the connection turned over. Same for `$event->bingoCard` and
`$event->board`: a cached relation is as stale as an attribute, which means
**last round's "a host changing which shapes count reaches every open card"
was never true either**. Channels re-read now.

**Every test missed it, by construction.** One handed the channel
`$event->fresh()` on each call; another edited through the very instance the
channel was holding. Both make the model current in a way the stream never
does. The rule that came out of it: **a channel test uses one instance for
its whole life and writes past it**, because that is the only way an edit
ever reaches an open stream — the host saving is a different request with a
different copy of the row. With the fix reverted, five tests fail; before it,
none did.

**And my browser check measured the wrong thing.** I watched a date change
arrive, saw ~30 seconds, and blamed the dev server's single worker. The delay
was real and the explanation was half right — but it was covering for the
staleness, and I recorded "verified" for something a user could not use. The
number that mattered was the one I never took: time from write to screen,
stamped on both sides. It was 29.3 seconds.

**Which then turned out to be the second bug.** With the fingerprint fixed
the *notice* was immediate, but the page still asked Inertia for fresh props
— and that request queues behind the very stream that triggered it, so the
screen still changed 29 seconds later. A version was the wrong design for
something that has to arrive over a connection that is already open. The
event rides along in the payload now: 0.5–3.0 seconds, and no second request
at all.

`EventCard` exists for that — the shape the pages render, extracted from
`BoardController::cardData()` so the channel and the controller cannot drift.
`metricKind` moved onto it for the same reason: the race page reads it to
know whether it is counting XP or kills, and a card without it would have
gone blank on the first push.

521 backend tests, 151 frontend.
## Sweep: what else was not live — 2026-08-23

Asked after the fix landed: is that everything, and is it stable? Three more
gaps came out of walking each channel's fingerprint against what the pages
actually render.

- [x] ~~**A claim's verdict never reached the claimant.**~~ The refresh of
  `claims`, `completed`, `completedLines` and `hasWon` was gated on
  `canEdit`, so only hosts got it. A player saw the standings award them
  points and the avatar appear on the square while their own square still
  read "waiting for review" — **which is the half pending, half approved
  render reported in round six.** The payload now carries a claims version,
  and every viewer fetches their own copy when it changes. Scoped to claims
  on purpose: a square being edited already reaches everyone over the
  channel, so making each viewer fetch for that would be a request per viewer
  for nothing. Hosts pay less than before, not more.

- [x] ~~**The bingo card's size was not fingerprinted at all.**~~ The grid is
  drawn from it, so a host resizing a card mid-event left every other viewer
  with the wrong number of columns — and it was only noticed at all when the
  square count happened to change with it. `requires_approval` was missing
  for the same reason and matters as much and less visibly: it decides
  whether clicking a square opens a claim dialog or ticks it off, so a stale
  viewer plays by a rule that has been switched off.

- [x] ~~**The page read the card's settings from a prop the stream never
  touched.**~~ Even once the channel noticed, `props.card.size` drove the
  grid. It reads a live copy now, from the same `EventCard` the settings
  modal edits.

**Also covered: the payload carries the hosts now.** `User` hides only
password and remember_token, and a bare `authors.user` published every host's
email address once already. The card builder names its columns, and there is
a test asserting the host's name is in the encoded payload and their address
is not — both halves, because an assertion that no email is present passes
trivially on a payload with no host in it.

**What is deliberately not live**, because it is per viewer and a shared
channel cannot carry it: whether you may edit, which team you are on, whether
you have joined, your own OSRS name. All of them change only through
somebody's deliberate action, and an event edit pulls them anyway.

**Still not proven: two people at once.** `php artisan serve` serves one SSE
stream at a time, so every measurement here is one browser plus a write from
outside the app. The multi-viewer case needs Herd or nginx+fpm, and until
somebody runs it there, "works when several people are in it" is reasoning,
not evidence.

**And a trap worth writing down:** the Browser pane being collapsed puts the
tab in `visibilityState: hidden`, which the composable treats as a reason to
drop the connection — correctly. One measurement here read as a dead feature
and was the optimisation working. Check `document.visibilityState` before
believing a stream did nothing.

525 backend tests, 151 frontend.

---

## Stopping an event — 2026-08-23

Two things a host could not do. Pausing did not exist at all, so "hold
everything while we sort out a dispute" had no answer but editing the end
date and hoping. Deleting existed as a route with no button outside
`/admin/events`, so a host could create an event and never get rid of it —
and when an admin did press it, `$event->delete()` went through a schema
where every child table cascades: board, tiles, player boards, completions,
standings, participants, invites, gone.

- [x] ~~**`paused_at` on events.**~~ Readable, not playable. Rolling,
  ticking a tile, claiming a square and joining all refuse while it is set;
  leaving does not, because being stuck inside a stopped event with no way
  out until a host comes back is worse than the thing the pause protects.
  Everything a *host* does stays open — pausing is usually the prelude to
  fixing whatever went wrong. `SyncEventStandings` skips paused races, or
  the pause would be a lie the moment it lifted.
- [x] ~~**It is live.**~~ `paused_at` is in `SignalsEventEdits`'
  fingerprint and in `EventCard`, so an open board goes to "Paused", grows
  a banner and loses its dice without a reload. Seen happening in a second
  tab that was never touched.
- [x] ~~**A danger zone in the settings modal.**~~ Last tab. Pause is any
  host's; delete is the owner's alone and sits behind typing the event's
  title, because "are you sure?" has been clicked through by everyone who
  has ever used a computer.
- [x] ~~**Deleting is a soft delete.**~~ The rows stay, the event leaves
  every list and 404s on every route, and `/admin/events` lists it struck
  through with a Restore button. Both halves are audited
  (`event.paused` / `resumed` / `deleted` / `restored`).
- [x] ~~**Everybody who joined gets told, unless the host says not to.**~~
  A checkbox in the danger zone, defaulting to on, on both actions. Queued
  `EventStatusChanged` notification carrying plain strings rather than the
  model — the cancellation mail is sent about an event that is already
  soft-deleted, so a serialized model would not resolve and the one mail
  people most need would be the one that never arrives.

**The honest part: most people will not get the email.** Discord login asks
for `identify` and `guilds` and deliberately not `email`, and `users.email`
is nullable, so a Discord-only account has no address at all. Those are
skipped, and the flash says so out loud — "1 of 2 participants were
emailed", not a silent success. The onboarding wizard now names event
notifications as a reason to add an address. Adding `email` to the Discord
scopes is **not** happening for now (owner's call, 2026-08-23); a Discord
webhook per event is the other way to reach the rest, and is not built.

**Verified in a browser** (localhost:8010, plus Mailpit on 1025/8025):

- Pause and resume from the modal, which stays open and flips to the other
  state rather than closing.
- The banner and the missing dice on a Snakes & Ladders board; the banner
  and the frozen standings on a skill race; the banner on a bingo card, and
  a click on a square doing nothing while it is up.
- The paused state arriving in a second tab through the stream, on a tab
  that was never touched.
- **All three mails, for real, over SMTP**: paused, resumed and cancelled —
  the last two driven from the actual UI, not from a service call. Exactly
  one mail per action; a second click sends nothing. The cancelled mail
  correctly has no action button, because there is no page left to send
  anyone to.
- The email checkbox off: pause, resume and delete each stayed silent.
- Delete → the deleted event struck through in `/admin/events` → Restore →
  back on the site, everything intact.
- "Paused" showing on the cards in `/events` and in `/my-events`.
- The refusal itself, through a real session: `POST /roll` on a paused board
  comes back with the toast rather than a roll.
- **SSR renders the paused state.** The server-rendered HTML already
  contains the banner and the "not taking moves" notice, so there is no
  flash of a playable board before hydration.

**Fixed while testing:** an admin deleting from `/admin/events` was thrown
out to the public events hub — off the only page where the deleted event is
still visible, and away from the Restore button they would want next. Admins
stay put now; hosts still go to the hub, because the page they were on has
gone.

**One local caveat, again:** `php artisan serve` is single-threaded, so the
pause request sat behind the two open SSE streams until they turned over.
Not an app bug and not new (see the note above), but it is why this round
was measured with patience rather than with two browsers.

- [x] ~~**A restore tells nobody.**~~ Fixed later the same day — see the
  walkthrough round below. Restoring announces itself, and unlike every
  other announcement in this feature that one is not optional: everybody
  who joined was told the event was cancelled, so silence would leave them
  holding a false last word.
- [ ] **Owner runs the multi-viewer test on Herd** (claimed 2026-08-23). Two
  real browsers on `osrs-events.test`, one host and one player, pausing and
  resuming while both watch. That is the one claim this round cannot make
  from `artisan serve`, and it is the same gap the fingerprint round left
  open — so proving it once covers both.

552 backend tests, 154 frontend.

---

## The walkthrough round — 2026-08-23

Six users were written down first and then walked through the app: a clan
organiser, a player arriving from a Discord link, a co-host who does not
play, a stranger from a search result, the site admin, and somebody on a
phone in light mode. What follows is what they ran into. None of it was an
error page; all of it told somebody something untrue.

- [x] ~~**Light mode failed contrast on the words that carry state.**~~
  Measured against the page background at the size they actually render:
  "Running" 2.22, "Paused" 1.91, a rejected claim 3.81, the quiet
  `text-dimmed` line 2.59 — against a 4.5 floor. Fixed in `app.css` the same
  way the brand text already was: the fills keep their vivid 500s, the TEXT
  drops a few steps in light mode only. Now light 4.79–7.64 and dark
  5.77–11.11, both ladders intact.
  **Watch out for:** `--ui-text-muted` set on bare `:root` also applies in
  dark mode. Setting it there alone dragged dark muted to 2.29 — worse than
  the bug. Both modes are pinned explicitly now, and both were re-measured.
- [x] ~~**"Board" and "event" were used for the same thing.**~~ 69 strings.
  The rule applied: "board" stays where it means the Snakes & Ladders GRID
  (tiles, sizes, the S&L landing page), and becomes "event" everywhere it
  meant the competition — "Create Board" on a page called Events, "Board
  title", "Edit Board", "Teams share one board", "this board requires an
  invite".
- [x] ~~**Four player-facing strings were hardcoded English**~~ — the roll
  limit, the roll result, "no team on this board", and every access refusal
  in `BoardAccessService`. They are the most-read sentences in the app and
  the only ones breaking the i18n rule.
- [x] ~~**Tap targets under the floor on a phone.**~~ The action row was
  28px and the header 32px, against Apple's 44. One rule in `ui.config.ts`
  (`max-sm:min-h-11`), because the sizes are right on a desktop — this is
  about the input device. Re-measured at 375px: 35 buttons, none under 44,
  no horizontal overflow, admin list included.
- [x] ~~**Joining a team event you have no team in said "you joined".**~~
  True and useless: it reads as "you can play now", and then nothing
  happens. `join()` now reports it and the flash says a host has to put you
  on a team first.
- [x] ~~**`/leaderboard` on a bingo event said "No players yet"**~~ about a
  card five people were scoring on. That page IS the S&L ranking; an event
  with no board redirects to where its standings actually are.
- [x] ~~**An admin read every private event silently.**~~ The power is
  right and moderating is what it is for — but /teams has always said "you
  can see these because you are an admin, so you can moderate, not because
  you are part of them", and an invite-only clan event is at least as good
  a place to say it. Now shown on the event itself, and only when the admin
  pass is the only reason the page opened.
- [x] ~~**A pause could not say why.**~~ One optional line from the host,
  carried to the banner, the email and the Discord post. "Paused" answers
  "will my claim bounce"; the clan is asking "for how long".
- [x] ~~**Resuming was four clicks deep**~~ in a tab called "Danger zone" —
  where you go to END an event, not to un-pause one. The banner carries a
  Resume button for hosts, and the tab is called "Stop or delete".
- [x] ~~**A restore told nobody**~~ — everyone kept a "cancelled" email
  about an event that was running again. Restoring now announces itself,
  and that one is not optional.
- [x] ~~**The announcement banner could not be dismissed**~~ and rendered
  on every page including the admin area. Remembered per announcement text,
  so editing it makes it a new one worth seeing.
- [x] ~~**The landing page invited you through a locked door**~~ ("log in
  with Discord to get started" directly above "the app is not open yet"),
  and still advertised bingo and drop races as "on the way" three days
  after both shipped.

**Not a finding after all:** the header nav looked like seven flat items in
a text dump. It is not — Events, Community and Guides are groups with
popovers, and the flattening was the reading tool's, not the page's.

### Discord announcements — off, and staying off until somebody watches it

- [ ] **Try the webhook against a real Discord server before enabling it.**
  Built this round and shipped **disabled**: admin → Site settings →
  Discord announcements. It is the only feature here that makes the app
  send something outward on a host's say-so, into a room full of people who
  never asked this app for anything, so it wants a human watching the first
  post rather than a passing test.

  What to check when you do: the message reads well in a real channel; the
  link resolves; nothing pings (`allowed_mentions` is empty, and an event
  titled "@everyone bingo" is the test case); a revoked webhook fails
  quietly rather than breaking the pause; and the rate limit is fine at the
  volume a clan actually generates.

  Why it exists: Discord login never asks for an email address, so on a
  normal clan event roughly half the roster cannot be mailed at all — and
  every one of them is already in the channel this posts to. The URL is
  validated against Discord's own hosts only (it is a server-side request
  forgery primitive otherwise), and it never rides in the event payload —
  only an editor is handed it, because anyone holding it can post there.

573 backend tests, 154 frontend.

---

## Multi-user pass — 2026-08-24

Six seats, driven one after another through the same app: guest, player,
player-who-may-create, co-host, owner, admin — plus a newcomer with no OSRS
name and a half-finished wizard. The account roles were swapped underneath a
single browser session rather than logging in six times, which is also why
`tests/Feature/PermissionMatrixTest.php` now exists: the same table, run by
phpunit, so it stays true without anybody driving it.

**Held, in both places:** the events list and the guides are open to a guest
and everything private redirects to login; `/admin` is 403 for every logged-in
persona except an admin, while `/admin/tasks` and `/admin/blueprints` answer
to their own permissions instead; an admin editing on the PUBLIC routes gets
403 exactly like anyone else; a co-host may pause, edit and hand out invite
links but not delete; a private event shows a stranger the gate and an admin
the event with the notice on it; a newcomer who skips the name step is sent
to the page that asks the moment they try to join.

**Caught while writing it**, and both worth keeping in mind:

- `actingAs()` sets the guard for the rest of the test. A matrix whose guest
  row runs after an authenticated one is not testing a guest at all — it is
  testing whoever ran last. `forgetGuards()` between personas.
- The OSRS-name gate stands down while the first-run wizard is open, because
  the wizard asks for the same field. Correct, and not obvious: a test that
  asserts the redirect without completing onboarding is asserting the wrong
  thing.

**Header, reworked in the same pass.** Six equally-weighted buttons became two
play actions and one Manage menu carrying the review count; "Updating live"
folded into the status dot, so the pulse IS the stream and an ended event
stops claiming to be live. Checked against a long title, an ended event, an
upcoming one, a drop race and a Snakes & Ladders board — the five events
seeded for it are still in the local database. On a 375px screen a 74-
character title went from six lines to four, and the control row from three
rows to two.

**One CSS trap found doing it:** Nuxt UI ships an unlayered `h1 { font-size:
var(--text-3xl) }`, and unlayered CSS beats anything in `@layer utilities`
whatever its specificity — so a plain `text-2xl` on that heading is silently
ignored. The `!` in `max-sm:text-2xl!` is load-bearing.

588 backend tests, 154 frontend.

---

## Widths, properly this time — 2026-08-24

The previous round was measured at 375px and at a browser pane that
reported 483px while being called "desktop". That is one width and a bit,
not three, and it hid a real bug.

**Redone at 1280 (desktop), 1024 (laptop), 768 (tablet) and 375 (phone)**,
page by page: the three event types, a 74-character title, an ended event,
an upcoming one, a 9x9 board, the hub, my-events, the admin list, teams and
the participants page.

- [x] ~~**A long title dragged the hub 390px sideways at 768px.**~~ Only at
  tablet width, where the grid gives a card ~341px: `truncate` sets
  `white-space: nowrap`, and every flex box inside a PageCard defaults to
  `min-width: auto`, so the title cannot shrink and each parent grows to fit
  it in turn. `min-w-0` on the innermost row does nothing while its parents
  can still grow — the whole stack needs it. No overflow at any of the four
  widths now.
- [x] ~~**The Manage badge made its button 32px**~~ beside 28px siblings. A
  badge one size down, and the row is level.
- [x] ~~**A race's controls were the default size**~~ while both other event
  pages use `sm`. Same bar, three pages, now one height.

**Measured after, at every width:** no horizontal overflow anywhere; one
control row per event page; 28px controls on pointer widths and nothing
under 44px at 375; hub at 3 / 3 / 2 / 1 columns; board tiles 152 / 118 / 135
/ 62px for a 5x5 and 95 / 66 / 72 / 44px for a 9x9; contrast unchanged and
passing in both themes (light 4.79-7.64, dark 5.77-10.15).

**Worth knowing for the next report of "the buttons are gone":** an admin is
an ordinary user on the public side, so on an event they do not host there
is no Manage menu and no way in from that page at all — the way in is
/admin/events. That is the rule working, but the page says nothing about it.
A small "open in admin" affordance when `isAdmin && ! canEdit` would close
the gap; not built, because it is a decision about how visible admin power
should be rather than a bug.

---

## Three things a walkthrough asked for — 2026-08-24

- [x] ~~**An admin on an event they do not host had no way in.**~~ The public
  side gives them no controls, which is the rule working, and said nothing
  about where the power went — so it read as buttons going missing, reported
  from a screenshot. The event now carries a notice and a link to
  `/admin/events?event=<id>`, which opens that event's settings on arrival.
  Shown only to an admin who cannot already edit it.
- [x] ~~**A race's numbers could go quietly wrong.**~~ A standing is a
  measurement over a window — this metric, between these dates — so moving
  either leaves every row displayed, ranked, and untrue. `synced_at` could
  not say this: it answers "when was this read", not "was it read about the
  same question". Events carry `standings_stale_since` now, set when the
  window changes and cleared when a sync catches up, with an "Update from
  Wise Old Man" button in the warning and a "numbers last read" line above
  the table. Hosts only, throttled, and the entrants Wise Old Man cannot
  measure come back **by name** — "Not A Player is not tracked" is
  actionable, "updated 4 of 5" is not.
  **Deliberately not an automatic re-sync on save:** forty entrants is forty
  outbound requests to somebody else's public API, which a form submit does
  not get to decide.
- [x] ~~**The walkthrough itself is now repeatable.**~~ Two local-only
  commands: `php artisan dev:fixtures` seeds the edge-case events demo data
  never produces (a title long enough to wrap, ended, unstarted, on hold,
  invite-only, teams), and `php artisan dev:persona <seat>` reshapes the
  signed-in account into guest/player/creator/cohost/owner/admin/newcomer,
  with `restore` putting back exactly what was there before — from a snapshot
  taken at the first switch rather than from an assumption.

  The method itself — six seats, four widths, one session re-roled instead of
  six logins — is a **personal skill** (`multi-user-walkthrough`) rather than
  a file in this repo, because none of it is about OSRS events. These two
  commands are this project's implementation of the "reshape one account"
  step it asks for.

**Why one seat and not six accounts:** six logins means six passwords typed
into a form on every pass, and the app's own rule is that an admin hands out
roles — so a second account cannot elevate itself into the seat you need.
Re-roling one seat is the only version of this that is repeatable.

600 backend tests, 154 frontend.

---

## Push notifications — 2026-08-24

Nine categories, a settings page, and a silent opt-in. The whole feature is
one answer to a problem the app already had: **it has two announcement
channels and neither reaches a person.** Email misses every Discord-only
account, because the OAuth scopes are `identify`+`guilds` and deliberately not
`email` — roughly half of any clan has no address this app can send to. The
Discord webhook reaches the room, not the person, and one line in a busy
channel is scrolled past in ten minutes. Push is the only channel addressed to
an individual who is not currently looking at anything.

- [x] ~~**The nine categories.**~~ Player: claim reviewed, event starting or
  ending, final standings, event paused/cancelled, rolls back, someone passed
  you, team scored. Host: claims waiting for review, standings stopped
  updating. The catalogue is `App\Support\NotificationCategory::ALL` and it is
  the contract — the settings page renders it, the validator whitelists
  against it, senders read their default from it, the throttle reads its
  window from it.
- [x] ~~**Off by default is a design position, not a default.**~~ Rolls,
  rank changes and team activity ship off. Permission to notify is not
  permission to notify about everything: one chatty category is how somebody
  ends up revoking permission outright, and revoking takes the rare important
  ones — a rejected claim, a cancelled event — with it.
- [x] ~~**"Someone passed you" had to be designed against itself.**~~ Races
  resync every ten minutes and mid-table positions swap constantly, so the
  obvious version (notify on any rank change) would be the loudest thing in
  the app. It fires on **boundaries** only: you were first and are not, or you
  were on the podium and have fallen off it. Off by default, hourly per event
  on top of that.
- [x] ~~**The throttle is per entity, keyed on the notification's tag.**~~ Same
  concept used twice on purpose: the tag is also what collapses notifications
  on a lock screen, so a sender that has decided which notifications replace
  each other has already decided which ones rate-limit together. Ten claims
  landing in a host's queue inside a minute are one line saying "3 claims
  waiting", not ten buzzes.
  **Only the push half is throttled.** The SSE channels stay unthrottled — a
  page already open costs nothing to update.
- [x] ~~**Silent opt-in.**~~ Runs on every page load for a signed-in user.
  Denied → never anything. Granted with a subscription → re-POST it anyway,
  because that upsert is what heals a browser holding a subscription the
  server has lost (a wiped database, a pruned row) — a state that is otherwise
  completely undetectable, since the toggle reads "on" and nothing arrives.
  Granted without one → subscribe. Undecided → ask, once ever per browser,
  which is where Android shows its own accept/deny prompt.
  **iOS is skipped entirely in that last branch.** There,
  `requestPermission()` outside a user gesture does not merely fail — it
  records a denial the page can never undo. iOS goes through the toggle only.
- [x] ~~**The off switch needs its own flag.**~~ Unsubscribing drops the
  browser's subscription but leaves the OS permission granted, which is
  exactly the state auto-subscribe reads as "granted, so subscribe silently".
  Without `users.push_opted_out_at`, turning notifications off turned them
  back on at the next page load and the toggle did nothing. It rides along in
  the shared Inertia auth props so the very first autoSubscribe of a page load
  already knows.
- [x] ~~**The settings page names the failure instead of showing a dead
  toggle.**~~ Eight distinguishable states, each with a different fix:
  subscribed, ready, opted out, blocked at browser level, iOS needs the app
  installed first, insecure context, no Push API, and *the server has no VAPID
  keys*. That last one is on us, not the user, and a page that cannot say so
  sends people hunting through their own browser settings for an hour.
  Each category also has a **Send a test** button, because every real trigger
  is an event you cannot summon — a host approving a claim, a race ending, a
  sync breaking — so without it the first time anybody sees a given
  notification is the moment it matters.

**What verification actually proved, and what it could not.** The full send
path was exercised against a local HTTP server standing in for a push service:
`Authorization: vapid t=<ES256 JWT>, k=<key>`, `content-encoding: aes128gcm`,
TTL from config, `topic` carrying the tag, 2922 bytes of encrypted body. The
410 endpoint got its row marked `expired_at`; the 201 endpoint got
`last_used_at` stamped. The deep-link handler was driven directly:
`/settings/account` routed, `//evil.example.com/steal` and
`https://evil.example.com` both refused. **Not proved locally:** the push
service → device hop, and the OS permission grant — the embedded browser
denies notifications outright and blocks service-worker registration, exactly
as expected. That last hop needs a real phone and the Send a test button.

**A real bug the verification caught.** `push:doctor` reported four green
ticks on VAPID while *every single send failed*. Encrypting a payload needs a
fresh ephemeral P-256 key per message (RFC 8291), generating one goes through
OpenSSL, and on Windows OpenSSL cannot find its config file unless
`OPENSSL_CONF` is set — failing with a bare "Unable to create the local key"
that names neither OpenSSL nor the file. The doctor now tests that operation
by performing it, and says so. **A queue worker needs `OPENSSL_CONF` in its
own environment, not just your shell.**

**Deliberately not built: a worker-side rotate endpoint.** Browsers rotate
subscriptions on their own schedule; `pushsubscriptionchange` re-subscribes in
the worker, but does not tell the server, because the worker holds no session
and that endpoint could therefore not be authenticated — anyone with a stolen
endpoint URL could redirect a person's notifications to a subscription of
their own. The per-load sync registers the new subscription on the next visit
instead. **The cost is real:** between the rotation and that next visit, that
device receives nothing. The stale row cleans itself up on the first 410.

617 backend tests, 154 frontend.

---

## Diagnostics in the admin section — 2026-08-24

`push:doctor` was the wrong shape for its own job. The moments it is needed
are a deploy that went quiet and a phone that stopped buzzing, and both of
those were answered with "open an SSH session, find the site directory" — which
on this host is owned by an isolated system user the deploy login cannot even
`cd` into. A diagnostic you cannot reach is not a diagnostic.

- [x] ~~**`/admin/diagnostics`, five groups.**~~ The unifying theme is not push
  — it is **silence**. Every check covers something that reports success,
  renders normally and delivers nothing: a mailer that writes to a log file, a
  cron entry that was never created, a VAPID pair whose halves belong to
  different keys, an SSR process serving an empty div. None of them raise an
  error anywhere.
- [x] ~~**One service, two surfaces.**~~ `DiagnosticsService` owns every rule;
  the page renders all five groups and `push:doctor` prints the push half and
  exits non-zero (so it still works as a deploy gate). Two implementations of
  "is this key pair valid" would disagree within a month, and a diagnostic
  whose answer depends on where you read it is worse than none.
- [x] ~~**Four levels, and `info` is not a pass.**~~ A group made only of facts
  is not green. Colouring "3 devices registered" as a success would claim a
  check that never ran.
- [x] ~~**The scheduler can now be asked whether it is alive.**~~ Laravel
  records nothing about this, which made a missing cron entry the quietest
  failure in the app. `ScheduleHeartbeat` is stamped from `->onSuccess()`, so
  it measures what *completed*. **Absence is the signal**: a stamp that was
  never written and one two days old mean the same thing, and a one-hour TTL
  would have expired the evidence of an outage during the outage — turning
  "last ran two days ago" into "never ran", which is the same display as a
  fresh install.
- [x] ~~**Four buttons, each aimed at whoever pressed it.**~~ A real encrypted
  push to your own devices; a plain mail to your own address; one live Wise Old
  Man lookup; the notification sweep as a dry run. Nothing on the page can
  reach another user, which is what makes it safe behind an ordinary admin
  login rather than a confirmation dialog.
  **The sweep is dry-run only on purpose.** The real run sends to other people,
  and a button that buzzes thirty phones is not a diagnostic — if the scheduler
  is dead the fix is the cron entry, which the checks above already name.
- [x] ~~**Nothing it prints is a secret.**~~ The page is built to be
  screenshotted into a chat: keys are described (length, shape, whether the
  halves match), never shown, and device endpoints are fingerprinted. The full
  cross-user device list stays on the command, where you are already the
  operator answering "whose phone stopped working".

**The Wise Old Man check has three outcomes, not two.** A player their API has
never heard of is a *working* API; reporting that as a failure would send
somebody debugging their own server for an hour over a typo in a username.

**What it found on this dev box the moment it existed**, none of which was
visible anywhere else: encryption failing outright (`OPENSSL_CONF` unset —
green VAPID, zero deliveries), the scheduler never having run, a Wise Old Man
user agent with no contact address in it, and one entrant failing to sync.

**A test that passed while testing nothing.** `Http::fake()` **merges** stub
callbacks rather than replacing them, so the three-outcome Wise Old Man test —
written as one method that re-faked the same URL pattern three times — had the
first stub answering all three calls. It passed, and only ever exercised the
200 branch. Split into three methods. Worth remembering for any test that
wants two different responses from one endpoint.

639 backend tests, 154 frontend.

---

## The prompt that never came — 2026-08-24

Reported from staging: *"it did not instant prompt me. Since there's no
prompt, I cant test notifications yet."*

- [x] ~~**The automatic ask is not a reliable way to get a prompt.**~~ It only
  works on Chromium. Firefox has required a user gesture for
  `Notification.requestPermission()` since 72 and ignores the call otherwise,
  Safari the same, and Chrome may answer with its quiet UI — a bell in the
  address bar that is **indistinguishable from nothing having happened**. The
  silent opt-in was built exactly as asked and is still right where it works;
  it just cannot be the only route.
- [x] ~~**An in-app offer bar.**~~ Shown to a signed-in user whose permission
  is still undecided, once the automatic attempt has settled. Clicking it is a
  real gesture, so it produces a real prompt on every platform — and on iOS it
  is the only route that has ever existed. "Not now" snoozes for a week rather
  than forever: forever belongs to the browser's own block and to the off
  switch, both of which are explicit.
- [x] ~~**The once-ever flag could be spent on a prompt nobody saw.**~~ It is
  set *before* the call, so a browser that silently refused to show the prompt
  burned it anyway and could never be asked again from the app. The bar clears
  that memory before asking, and `clearPromptMemory()` exists for it.
- [x] ~~**The bar stays hidden wherever it could not help**~~ — no Push API,
  no VAPID keys on the server, permission already decided, opted out, iOS
  outside an installed app, or a page already asking for something (the OSRS
  gate, the onboarding tour). A bar advertising a button that does nothing
  turns a quiet failure into a visible broken one.

The decision is a pure function in `Support/pushPrompt.js` with thirteen tests
over it, because it is an eight-way answer that is otherwise only observable
by finding a browser in each state.

**What could not be verified here:** the bar itself. The embedded browser
forces `Notification.permission` to `denied`, so the correct behaviour to
observe locally was the bar staying *hidden* — which it does, and without
burning the once-ever flag, since the denied branch returns before setting it.

639 backend tests, 167 frontend.

---

## Three from the first real test on a phone — 2026-08-24

- [x] ~~**The offer bar drew over the admin heading.**~~ It was emitted from
  AppRoot without the `showSiteChrome` gate the announcement banner and the
  OSRS notice both carry. The admin area brings its own full-height dashboard
  shell and renders no site chrome, so a bar above it does not push it down —
  it draws straight across the page title.
- [x] ~~**A repeated flash message toasted once and then never again.**~~ The
  bridge watched the flash *value*, and a watcher only fires on a change —
  so working through a review queue, where every approval flashes the same
  "Claim approved", toasted the first and swallowed the rest. Now hooked to
  Inertia's `success` event, which fires per visit regardless of whether the
  text changed. The same miss made the Wise Old Man lookup look broken while
  it was answering fine; that was the tell.
- [x] ~~**The generator's example VAPID subject passes every format check.**~~
  `mailto:you@example.com` *is* a valid mailto: URL, and push services use
  that address to reach an operator whose app is misbehaving — so an address
  nobody reads turns a warning into a block that arrives without one. The
  diagnostics page warns on it now. Spotted in a screenshot of staging.

**Not a bug: approving your own claim sends you nothing.** Reported as "I sent
in a claim and approved it myself and got no notification". `BingoNotifier`
skips the notification when the claimant and the reviewer are the same person
— being told the outcome of a decision you made one second ago reads as a bug,
not a service. It is deliberate and there is a test for it. Testing the real
path needs a second account, or the Send a test button on /admin/diagnostics.

**The toast itself was fine.** Measured at 76×343 with 16px padding, on
`z-[100]` — above the modal overlay, not under it. What the screenshot showed
was a toast sitting over the review modal's dimmed backdrop, with that modal's
own Close footer above it, in a zoomed crop.

640 backend tests, 168 frontend.

---

## Why Edge never asked — 2026-08-24

The end-to-end path is confirmed working: a second account on Edge claimed a
square, the host approved it on a phone, the notification arrived and its deep
link landed on the right page. What did not work was getting Edge to ask in
the first place — the only route there was the settings page, which is exactly
what the offer bar was supposed to remove.

Two causes, both mine.

- [x] ~~**The bar was hidden from anyone who had not finished the tour.**~~ It
  was gated on `needsOnboarding`, which stays true until onboarding is
  *completed* — and closing the tour only snoozes it for a day. So a fresh
  account (which is what a second test account is) never saw the bar at all.
  The gate now keys on the tour being **open**, not pending. A page that is
  actually asking for something should suppress it; a pending state should not.
- [x] ~~**The automatic ask was once-ever, and the flag is written before the
  call.**~~ So a browser that silently declined to show the prompt — Firefox
  without a gesture, Chrome's quiet UI — spent its only attempt on a dialog
  nobody ever saw, and the app could never ask again. Now at most three
  attempts, a month apart.
  **The migration is the point:** the old value was the literal string `'1'`,
  and it is read as "asked once, long ago" — which makes every browser still
  carrying it due for one more attempt on the next load. Those are precisely
  the browsers that may have spent their only ask on nothing.
- [x] ~~**Notifications was missing from the profile menu.**~~ It was in the
  settings sidebar only, which you have to already be in settings to see.

The whole ask policy — the bar and the automatic prompt — now lives in
`Support/pushPrompt.js` with tests over it, including the migration. Getting
that read backwards would mean nobody is ever asked again, and nothing
anywhere would report it.

**The trade being made, stated once:** browsers penalise unprompted permission
requests, and repeated dismissals push Chromium into quiet mode and eventually
into an automatic block. Three asks a month apart is well inside what that
tolerates, and the bar means a suppressed prompt is no longer a dead end — but
the automatic ask is not free, and more of it would not be better.

640 backend tests, 174 frontend.

---

## The last global role is gone — 2026-08-24

`TEAM_MANAGER` granted rename, staff and restaff over **every team on the
site** to anyone holding it. The per-team OWNER/MANAGER/MEMBER roles replaced
that on 2026-08-21, and it was kept alive for exactly one reason: nobody
holding it should lose access mid-deploy. That was a temporary measure that
outlived its reason by three days.

- [x] ~~**Deleted, not renamed.**~~ The alternative on the list was to keep it
  under an honest name ("site-wide team staff"). The reason not to: nothing in
  the app explains such a role, nothing grants it deliberately, and ADMIN
  already answers "somebody has to be able to fix this team". A standing
  over-permission kept for a use case nobody has is how a permission system
  rots — it survives every review because removing it feels riskier than
  leaving it, and the risk only grows.
- [x] ~~**The check, the role row, and the way back in.**~~ Gone from
  `Team::isManagedBy()`; the migration detaches every holder and deletes the
  role; `ROLE_OPTIONS` on the admin users page no longer offers it, because
  offering it would recreate it by name. The dead `isTeamManager` flag in
  `useAuth` went too — nothing had used it since the Teams nav stopped being
  gated on it.
- [x] ~~**No automatic conversion, deliberately.**~~ Turning a global role into
  per-team memberships would mean adding that account to every team as a
  MANAGER — a far larger and much less reversible change than the one being
  undone. Whoever held it keeps every per-team role they had; if they genuinely
  need the reach back, the answers are ADMIN or a MANAGER seat on the specific
  teams, both of which say what they are.
  **The migration logs who held it before detaching**, because afterwards
  nothing anywhere records that those accounts ever did — the role row goes
  too, so even the audit log has nothing left to join against.

**Pinned as a test, not left to the migration.** The migration only deletes a
row; nothing stops the name being created again from the admin users page. So
`TeamOwnershipTest` asserts that a fresh `TEAM_MANAGER` holder is *forbidden*
from renaming somebody else's team, and that an admin still can. If the check
ever came back, that is what would catch it.

Rehearsed both directions locally, including the branch that never runs on a
clean install: with the role present and held, the row is deleted, the holder
is detached, and the warning lands in the log with the user id in it.

642 backend tests, 174 frontend.

### The walkthrough that should have run first

Asked for after the fact, and fairly: a change to who-can-do-what is the
skill's own trigger. Run properly, one account moved through the ladder.

**Four teams, one account, a different seat in each** — owner, manager, member,
outsider — so a single page load shows every rung side by side instead of
needing four passes. What `/teams` rendered:

| Seat | Card | Footer |
|---|---|---|
| owner | Owner badge | Members · Edit · Delete |
| manager | Manager badge | Members · Edit |
| member | no badge | **nothing** |
| outsider | **not listed at all** | — |

No seat is shown a control it cannot use, which is the finding this pass most
often turns up at the collaborator rung. Inside the members modal the same
line holds: a manager can add and remove, and is offered no promote control; an
owner gets promote and remove on everybody except themselves. The owner's own
row has no remove — a team without its owner is a team nobody can delete.

**The gate caught me out, exactly as the skill warns.** Demoting the account to
a plain player put the site lock back in front of it — admin had been walking
straight through it. Lifted for the pass, restored after, and the restore is
verified rather than assumed.

**Widths** — 1265 / 1009 / 753 / 375, page-level overflow **0** at all four.
The `under44` count at 375 is 8, and all eight are the wordmark and the footer
text links; every team control clears 44px. Not chased: a footer of text links
is not what that rule is aimed at.

**Turned into tests rather than a paragraph.** `PermissionMatrixTest` grew a
teams ladder with its own seat map, and the retired role is carried there as a
seat — `ex-global-manager` holds `TEAM_MANAGER` and is in no team, so every row
asserts it grants nothing and a row that ever came back green would say so.
That covers rename, add member, promote, remove and delete in one table, plus
the two guards that keep a team from losing its owner.

647 backend tests, 174 frontend.

---

## The policy caught up with the code — 2026-08-24

`/privacy` described the Discord-only version of this app. Since then it had
gained email accounts, an audit log that outlives deleted users, invite records
naming two people, sessions storing IP addresses — and, this week, push
notifications, which store a device identifier per person and hand a message to
a third party we do not choose. I widened that gap myself and then recommended
closing it, which is the right order but worth admitting.

- [x] ~~**The reason it was never going to fix itself.**~~ These are CMS rows,
  and `PageSeeder::seedPage` uses `firstOrCreate` — correct for every other
  page, because re-seeding must not flatten something an admin edited. For
  these two it meant the repository could hold a perfectly accurate policy
  while every environment that had already run kept serving the old one, and
  nothing anywhere would say so.
- [x] ~~**One source, two routes.**~~ The copy moved to
  `App\Support\LegalPages`. The seeder plants it on a fresh install;
  `php artisan pages:sync-legal` applies it to a database whose rows exist.
  `--diff` rehearses. It **overwrites** those two page bodies, which is why it
  is run on purpose and not wired into the deploy.
- [x] ~~**Seven gaps closed**~~ — sessions (IP + user-agent), the audit log's
  retention as a number, invites naming who created and who used them, teams
  tied to a Discord server, Discord announcements phrased so it is true whether
  the switch is on or off, the whole notifications section, and `/terms`
  finally saying what a **host** may do to the people in their event.
- [x] ~~**The retention number is pinned to the config.**~~ The page states
  ninety days because `AuditLog::retentionDays()` returns ninety. A test
  asserts the two agree, so changing the config without changing the copy fails
  the suite rather than making the page quietly untrue.

**What `/terms` gained is the part I would reread first.** Joining somebody
else's event hands its host real power over your participation — they see your
OSRS name, rule on your claims, can remove you, and can delete the event with
everyone's progress in it. That was true the whole time and written down
nowhere. The counterweight is in there too, because it is equally load-bearing:
a host is not an administrator, and cannot see your email address or your other
events.

**Still yours to decide**, and listed in `docs/legal-review.md` rather than
guessed at here: whether `dev@absolit.nl` is the right address for deletion
requests, whether sessions and push subscriptions want a stated maximum age the
way audit entries now have, and whether a free hobby project with no payments
needs a lawyer's read anyway.

655 backend tests, 174 frontend.

---

## Legal, round two — owner's own pass (open)

The pages are accurate now; that was the engineering half. The owner is coming
back to the rest, and this is what they were thinking when they said so, so the
next pass does not start from scratch.

- [ ] **Look at free/licensed policy sources instead of hand-writing more.**
  The current text was written from the schema, which is its strength and its
  ceiling: it describes this app precisely and carries none of the structure or
  jurisdiction boilerplate a template would bring. **The decision worth making
  first is which half comes from where** — keep the accurate description of
  what is stored, and let a template supply the scaffolding around it. Swapping
  wholesale would trade something true for something generic.
  Leads worth checking rather than trusting: permissively-licensed policies
  published by other open projects (Automattic's are CC-licensed and widely
  adapted), and the various policy generators. **Verify the licence terms
  yourself** — "free to use" and "free to adapt and publish under your own
  name" are not the same permission, and I have not checked any of them.

- [ ] **Written for the world, not for the Netherlands.** The playerbase is
  wherever OSRS is, so anything that reads as EU-only ceremony is the wrong
  shape. The good news is that the two facts that simplify this most are
  already true and already stated: **nothing is sold, and there is no
  analytics, advertising or third-party tracking of any kind.** Most
  jurisdiction-specific machinery — "do not sell my data" rights, ad-tech
  disclosures, consent banners — collapses to a sentence when there is nothing
  to disclose. Worth keeping that framing when a template tries to add it back.

- [ ] **Whether to publish a personal email address at all.** Currently
  `mailto:dev@absolit.nl` on `/privacy`, and the owner is unsure — the GitHub
  account is public, so anyone determined can reach the same contact details,
  LinkedIn included.
  **Also now out of date:** the policy says "ask and your account will be
  deleted", which was true when it was the only route. Settings → Account has
  a delete button as of 2026-08-24, so that paragraph in
  `LegalPages::privacy()` should point at it and the address should become the
  fallback. Noted here rather than changed, because the whole legal pass is
  the owner's.
  Two things worth separating there. *Findable* and *published* are not the
  same act, and which one the page performs is still a choice. And the address
  is only load-bearing because of the point below.

- [x] ~~**Self-serve account deletion would mostly dissolve that question.**~~
  Done 2026-08-24 — see the entry at the end of this file.
  Right now `Admin\UserController::destroy` is the only way an account gets
  deleted — there is no button in Settings. That is why the policy has to say
  "ask", and why asking needs an address. A delete-your-account flow turns the
  contact address into a fallback for edge cases rather than the mechanism, and
  it is a better answer for the user too: nobody should have to email a
  stranger to leave.
  This is a feature, not a policy question, which is why it is the one item
  here that does not need the owner's judgement to start.
  Worth designing around: it is destructive and irreversible, it takes their
  event progress with it, and events they *host* have other people's progress
  in them — so "delete my account" and "delete the events I run" are not the
  same request and should not be the same button.

**Not doing on my own:** none of the above is legal advice, and the first three
are the owner's calls rather than mine. `docs/legal-review.md` holds the
applied changes and the smaller open questions (the deletion address, whether
sessions and push subscriptions want a stated maximum age).

---

## Leaving without taking everyone with you — 2026-08-24

Self-serve account deletion, at Settings → Account. It was on the list as the
thing that would make the "do I have to publish my email address" question
mostly go away, and it does — but it turned out to be worth building on its own
merits, because it uncovered a crash.

**Account deletion was already broken.** `board_invites.created_by` was NOT
NULL with a plain `constrained('users')`, which defaults to RESTRICT — so
deleting any account that had ever handed out an invite link failed on a
foreign key violation. An admin was the only deletion route that existed, which
means deletion has not worked for a single host since invites shipped, and
nothing ever said so. Found by trying it before writing anything.

### The shape

The hard part is never the delete. It is that one account can be the only
person able to run something other people are still in, and **neither default
is acceptable**: silently ending somebody's event, or refusing to let a person
leave until they find a replacement. So the page states the whole cost on one
screen and makes the unavoidable choices explicit.

Three outcomes:

- [x] ~~**Handed over.**~~ Anything still running that somebody else can take.
  Co-hosts are offered first — they already run it, so a handover changes
  nothing anybody would notice — and participants only when there is no
  co-host. Never a list of everybody on the site: that invites handing your
  clan's event to a stranger.
- [x] ~~**Ended.**~~ The same things, when nobody else is in them or the owner
  would rather. Events are soft-deleted, so an admin can still put one back.
- [x] ~~**Kept, anonymised.**~~ Anything already finished. A race that ended in
  July had a winner and still does: the standings row stays, keeps the OSRS
  name it was scored on, and loses only its link to the account. Same for bingo
  squares and board positions. On screen that reads as a deleted player, which
  is what actually happened.

**The line is whether a thing has ended, not how old it is.** A finished event
needs no owner — nothing about it can change any more — so asking somebody to
rehome their archive is asking a question with no useful answer.

### Decisions worth restating

- [x] ~~**No FK surgery.**~~ The obvious version was four `nullOnDelete`
  constraints. Rejected twice over: SQLite cannot change a foreign key's action
  without a table rebuild, and it would scatter "what happens when an account
  closes" across four migrations where nobody deciding that would ever read it.
  `AccountDeletionService::keepHistory()` nulls them in one readable pass, and
  the tests can watch it do so. Only the columns became nullable.
- [x] ~~**An unanswered choice is a refused request.**~~ The service throws
  rather than defaulting, because every default here is either "silently delete
  somebody else's event" or "silently hand it over". The page catches that and
  says the page went stale — which is exactly what it means when a second tab
  created an event after this one rendered.
- [x] ~~**Typed confirmation, not a checkbox.**~~ The OSRS name, trimmed and
  case-insensitive. Plus the password where the account has one, on the same
  reasoning as changing the email: a borrowed session must not be able to do
  the irreversible things.
- [x] ~~**An admin deleting somebody else is a different operation.**~~ It makes
  no decisions on their behalf: history is preserved identically, and anything
  the account owned simply loses its owner — still reachable from
  /admin/events, which is where that admin already is.

### What this does not do

The privacy page's promise is still "ask and your account will be deleted" —
`LegalPages` has not been updated to point at the button, because the whole
legal pass is the owner's to make. That is a one-line change to make when they
come back to it, and it is the last thing keeping the contact address
load-bearing.

676 backend tests, 174 frontend.

### Finishing the deletion feature

Asked whether it was complete. It was not, and not for the reason I had been
raising: the data survived a deletion but nothing on screen said whose row it
was, so a departed player rendered as a blank name.

- [x] ~~**"Deleted player", said out loud.**~~ One key, `common.deleted_user`,
  applied where a row can outlive its account: the board's player list and its
  live overlay, the leaderboard, and the three name builders in BingoService.
  An unlabelled row reads as a rendering bug rather than as somebody who left,
  which is the opposite of what the anonymisation was for.
- [x] ~~**Checked for crashes rather than assumed.**~~ Every unguarded
  `->user->` in the app was reread against a null. Both candidates —
  `LeaderboardController` building its player payload and
  `EventStandingsService` clearing the verified flag — were already written
  with explicit null checks, so nothing throws. Worth having looked: making a
  column nullable is exactly the change that turns a defensive habit into a
  load-bearing one.
- [x] ~~**Pinned.**~~ Bingo standings must return the label for a departed
  player, and the board page and leaderboard must both still answer 200 with a
  gap in them.

678 backend tests, 174 frontend.

### One seat could not leave

The player pass the deletion feature had not had. Found by reasoning about the
seats rather than the code: **an account that never gave an OSRS name could not
close itself.** Every settings route sits behind `require-osrs-username`, which
redirects any write from an account without one — so the site demanded a
RuneScape name before it would let somebody go, and the person most likely to
want out was the only one who could not get out.

- [x] ~~**The account routes came out from behind the gate.**~~ Signing in,
  changing how you sign in, and leaving are not playing, and that gate is about
  scoring. `settings.account` and its four writes plus the Discord link routes
  are exempt now — all of them, rather than only the delete, because a page
  that renders while the forms inside it redirect is the worse bug.
- [x] ~~**The confirmation needed a fallback.**~~ It was the OSRS name, which
  those accounts do not have. `User::deletionPhrase()` uses the OSRS name where
  there is one and whatever else identifies the account where there is not.

Confirmed as a failing test first — the redirect went to `/welcome/osrs-username`
— then fixed, then pinned.

679 backend tests, 174 frontend.

## Livegangdatum vastleggen

Zodra de site live gaat: de datum ergens vastleggen. Hij is nodig voor het
portfolio-artikel over dit project (veld `end_date` / livegang) en anders is hij over een
maand niet meer te achterhalen.

## The wizard that kept coming back — 2026-08-25

Asked because it kept reappearing on staging. **Not a bug in the state, a bug
in the labels** — and this time it was actually driven in a browser rather than
read off the code, because reading it off the code is how the last three
"probably fine"s happened.

### The criteria, confirmed

The modal opens when all three hold:

1. `users.onboarding_completed_at IS NULL`, shared as `auth.user.needsOnboarding`;
2. the page component does not start with `Auth/` — the OSRS-name gate wins;
3. `localStorage['onboarding-snoozed-until']` is in the past.

### What the two exits actually do

| Exit | Effect |
| --- | --- |
| **Skip for now** / **Get started** | POST `/onboarding/complete`, timestamp written, gone for good |
| **X**, Escape, click-outside | 24h `localStorage` snooze, nothing server-side |

Driven end to end on `localhost:8010` with a fresh account: finished the flow,
confirmed `onboarding_completed_at` was written, then set the snooze **25 hours
into the past** so the tour was explicitly *allowed* to reappear, reloaded, and
navigated. It stayed shut. Then reset the row, closed with the X, and confirmed
the mirror image: `onboarding_completed_at` still NULL, snooze exactly 24.00h,
and the tour back on the next load once that expired.

**So the mechanism is right and the wording is backwards.** "Skip for *now*" is
the permanent one; the X — which every user reads as "close this for now" — is
the temporary one. Whoever keeps seeing it is clicking the X, which is the only
control on the modal that behaves the way its shape promises.

- [x] ~~**Rename `onboarding.skip`.**~~ Done 2026-08-29 — "Skip for now" →
  "Skip intro". `AppRoot.vue`'s 24h snooze (closing via Escape/X/outside
  click) is untouched, still exactly what its own comment says it should
  be — only this button, which always calls the permanent
  `completeOnboarding()` (`POST /onboarding/complete`) regardless of which
  step it's clicked from, was the one claiming to be temporary. The 24h
  snooze is worth keeping exactly as it is — it exists because closing the
  tour used to persist nothing and it reopened on every single page load —
  but the button next to it must stop calling a permanent action "for now".

**Two things that are not the bug, worth writing down so they are not
re-investigated:** the snooze is per-browser while the completion is
per-account, so a second device shows it once more; and `localStorage` throwing
in private mode is deliberately read as "not snoozed", which fails toward
showing it.

Also noticed while setting this up: the site lock renders `SiteLock`, which
does **not** match the `Auth/` prefix that suppresses the tour. An admin
bypasses the lock so it never collides in practice, but the guard is narrower
than its comment implies.

### The account-deletion paragraph, fixed

`/privacy` still said "ask and your account will be deleted" with a mailto as
the only route, which stopped being true when Settings → Account shipped.
`LegalPages::privacy()` now leads with the self-serve route and keeps the
address as the fallback for someone locked out of their own account.

**It will not reach staging by re-running the seeder.** `PageSeeder` is
`firstOrCreate` on the slug, on purpose: once a page is a row it is editable
content and a re-run must not overwrite an admin's edits. Landing a copy change
today means deleting the row first. If seeder-as-source-of-truth is the
workflow until launch, that wants an explicit `--force` refresh rather than
softening `firstOrCreate`.

83 tests over the legal, onboarding and page suites.

### The tab strip with no pointer — not a finding on develop

Reported from staging: the Site settings section nav (Access / Boards /
Support / Announcement) shows no `cursor: pointer`. Measured on `develop` at
`localhost:8010`, all four compute `pointer`:

```
Access → pointer   Events → pointer   Support → pointer   Announcement → pointer
```

They are plain `<button type="button">` in `Pages/Admin/Site.vue`, and the
unlayered rule in `resources/css/app.css` covers every non-disabled button.
It is in the shipped bundle.

**The screenshot dates itself.** It reads "Boards"; `admin.site_section_boards`
has said "Events" since `1354dcf` (2026-08-24). And the global cursor rule
landed in `fe9b170` (2026-08-21), one day *after* the section nav itself
(`4313501`, 2026-08-20) — so a build from that window has this nav and not the
rule. Staging is running something older than both.

Nothing to fix; it goes away with a deploy. Worth remembering that a screenshot
from staging is evidence about staging's build, not about the branch.

## Snakes & Ladders task tiles have no claim/approve flow — asked 2026-08-25

Bingo's claim/review model (PENDING/APPROVED/REJECTED, proof URL, reviewer —
see the "table question" writeup above) exists because a bingo square is
**"a claim under review"**, not a boolean fact: a clan cannot trust a
self-reported drop without proof.

But `Tile` carries a `task_id` too ([Tile.php:14](../app/Models/Tile.php)) — an
S&L tile can be exactly the same kind of challenge as a bingo square ("kill
Zulrah"). It just gets marked done by `PlayerBoardController::toggleTile()`
([PlayerBoardController.php:117](../app/Http/Controllers/PlayerBoardController.php)),
a plain self-toggle with no proof and no host review. Same trust problem
bingo was built to solve, unsolved on the other board type.

- [ ] **Decide whether task tiles on Snakes & Ladders should require proof and
  host approval**, same as bingo squares.
  **Answered and built 2026-08-30** (commit `d122b7d` plus the two bugfixes
  that followed it) — see the entries at the end of this file. Left unticked
  here because that is the owner's tick to make; not carried into the new
  `docs/backlog.md`. Not free — `requires_approval`,
  `proof_url`, review state and a review queue all live on `bingo_cards`, not
  on `boards`/`player_boards`/`completed_tiles`, and the "table question"
  research deliberately kept the two schemas separate. Options are: extend
  `completed_tiles` with the same review columns, or accept the asymmetry
  because S&L is a race-to-the-end and a lying self-toggle mostly just costs
  the liar the game, while a bingo squad's own trust in each other never gets
  tested unless the host looks. Whichever way this goes, write down the
  reasoning — it's the kind of thing that gets re-litigated every time
  someone new reads the two board types side by side.

## Feedback batch, staging screenshots — 2026-08-25

Two of these were real bugs, fixed directly rather than filed:

- [x] ~~**An ended event still let you play it.**~~ Reported live: an event
  showing the "Ended" badge still rendered a clickable "mark as complete" card
  and a working dice roller, and the roll actually landed. The "Ended" badge
  itself was already correct (`eventStatus()` in `Support/board.js`, driven off
  `end_date`) — nothing gating the dice/tile-complete controls had ever checked
  it, client or server, only `isPaused()`. Added `Event::isEnded()` (mirrors
  `boardEventStatus()`'s day-level comparison, `endOfDay()->isPast()` rather
  than a bare `isPast()` so an event stays playable through the rest of its own
  end date) and checked it in `PlayerBoardController::roll()`/`toggleTile()`
  (server) and `BoardShow.vue`'s `isEnded` computed (client, same "ended
  outranks paused" precedence as the JS status badge already uses).
- [x] ~~**Page header didn't reach the same right edge as the alert below
  it.**~~ `EventTypeHeading`'s root `<div class="min-w-0">` had no grow class
  in the `flex justify-between` row it sits in, so it (and the "you're an
  admin, not a host" alert nested inside it) only sized to content instead of
  filling the row. Added `flex-1`; same fix applies to all three event types
  since Board/Bingo/SkillRace pages all wrap it in the identical flex row.

Everything else here is scoping only — none of it is built yet:

- [x] ~~**Account deletion: split "end" from "delete progress".**~~ — done
  2026-08-26. The old single "End it" choice for a still-running, co-host-less
  event was a real mismatch between label and behaviour: its copy claimed
  "delete the event and everyone's progress," but the code only ever
  soft-deleted the `Event` row — hidden from every list, admin-restorable, and
  every player's board position/completed tiles/standings sitting untouched
  and simply unreachable underneath it. Now split into what each of those two
  things actually deserves to be:
  * **End it** — `AccountDeletionService::endEventInPlace()`. The event, its
    board and everyone's progress are completely untouched; only its
    `end_date` is pulled to now (if it hasn't already passed), which is
    enough on its own — `Event::isEnded()` (this session's earlier fix) and
    every rule built on it already refuse a roll or a tile-complete on an
    ended event. The row is never soft-deleted, so it keeps its place in
    every listing. The owner attribution disappears on its own too, via
    `board_authors.user_id`'s existing `cascadeOnDelete` firing when
    `$user->delete()` runs at the end of the transaction — no code needed
    there at all.
  * **Delete it** — `AccountDeletionService::deleteEventAndProgress()`. What
    the old label always claimed: every `PlayerBoard`/`CompletedTile` for its
    board, every `EventStanding`, every `BingoCompletion` on its card, and its
    `EventParticipant` rows are deleted outright. The event ROW is still only
    soft-deleted afterwards, same as before — an admin restoring it from
    `/admin/events` gets back an empty shell (its settings), not a
    resurrection of what this just destroyed.
  * **"Delete account and all events"** — a fast path beside the ordinary
    delete button. Skips every per-item select entirely: `AccountController::
    destroy()` builds the choice maps itself (`'delete'` for every owned
    event and team) when `delete_everything` is sent, and ignores any
    `events`/`teams` sent alongside it — the fast path means what it says,
    not "everything except what I'd already picked."
  * **Confirmation moved into a modal.** Typing the RuneScape name and giving
    the password used to sit inline on the page; a stray click anywhere in
    that card could get partway through a form for an irreversible action.
    Both delete buttons now just open one shared `<client-only>` `u-modal`,
    keyed by which one was clicked (different confirmation copy for the
    ordinary vs. fast path) — the per-item selects still live on the page
    itself, since there can be many of them and a small modal is the wrong
    place for that part.
  Teams were **not** given the same three-way split — the ask was specifically
  about events, and a team's only "ending" was already destructive before
  this (see "Verify what actually happens when a team is deleted" above); left
  as its own question if a team ever needs the same treatment.
  10 new backend tests in `AccountDeletionTest.php`, all passing alongside
  the pre-existing 21. Verified live in a browser: the fast-path modal opens
  with the account's real name interpolated into the confirm copy, and the
  ordinary delete button is correctly disabled until every per-item choice is
  made.
  **Follow-up, same day**: the team candidate list offered every member in
  storage order with no manager-first priority, unlike events' co-hosts-first
  rule — fixed to match (`TeamMember::MANAGING_ROLES` first, falling back to
  everyone only when there's no manager). The team's "End it" choice had also
  borrowed the event's soft-ending copy verbatim, which is backwards for a
  choice that is still the destructive cascade discovered above — given its
  own accurate label and hint. The event "End it" label/hint were rewritten a
  second time too — "keep it, but it's over" was still vague on its own
  terms, and the hint's claim that a departed owner shows "the same as a
  deleted player anywhere else" was simply untrue: `board_authors.user_id` is
  `NOT NULL`, so there is no anonymised placeholder to leave behind the way a
  leaderboard row gets one — the row is removed outright and the owner stops
  appearing as an editor, full stop. The hint now says exactly that instead of
  implying a parity that was never built. Also added a standalone "settle one
  event/team right now" action (its own endpoint, `AccountDeletionService::
  settleOneEvent()`/`settleOneTeam()`, account never touched) plus client-side
  "Load more" paging for the events/teams lists — asked for after noticing the
  list had no ceiling and every decision was otherwise stuck until the final,
  irreversible confirm. 9 more backend tests, verified live against the actual
  running server (not just PHPUnit) for both the "end" and "delete" code
  paths.
- [x] ~~**Profile → "Your events" as its own page.**~~ — turned out to already
  exist, 2026-08-26: `/my-events` (`BoardController::mine()`) answers the same
  question with real board previews and hosted/playing filters across every
  event type, reachable from the header's **Events → My events** nav item
  (`nav.boards`/`nav.my_boards` were already literally "Events"/"My events" —
  the stale code comments calling that group "Boards" were the only thing
  saying otherwise). Asked the owner rather than building a near-duplicate
  page: confirmed it was redundant, so the simpler created/joined list
  embedded in `Settings/Profile.vue` was removed rather than kept in sync
  with a second implementation of the same list. `ProfileController::show()`
  dropped the whole `events` query it was building for that section. The
  settings page now links out to `/my-events` instead. Two tests that pinned
  bugs specific to the removed list (title/link correctness, the
  created/joined `isHost` flag) were deleted rather than ported, since
  `/my-events` already has its own equivalent coverage in
  `StagingFeedbackTest.php`.
- [ ] **Per-host Wise Old Man API key.** Today `WOM_API_KEY` is one site-wide
  key (`config/services.php`, read by `WiseOldManService`) shared by the
  scheduled `events:sync-standings` sync across every event — there's no
  per-user or per-event key anywhere. Idea: let an event host paste their own
  WOM API key (a Settings section, "Wise Old Man") so their own events sync at
  the higher authenticated rate limit without depending on the site's shared
  key/quota. Needs a place to store it (probably on `users` or `events`), and a
  decision on how per-event throttling interacts with the sync command's
  current single global rate pacing (`WiseOldManService::requestsPerMinute()`/
  `shouldThrottle()`) before it's more than a config field.
- [x] ~~**Community hub page**~~ — done 2026-08-26. New `/community`
  (`CommunityController`), same shape as the events hub: a slice of the
  user's own teams (with a "View all" to `/teams` once there are more than
  the slice shows), plus an explained placeholder section per still-"Soon"
  nav child (Global leaderboards, Clan directory) — same convention
  `Boards/Index.vue` already uses for its own Calendar row, rather than
  silently having nothing where the nav promises something. The Community nav
  group gained a `to: '/community'` the same way the Boards group has its own
  destination — clicking the label goes somewhere, hovering/the chevron opens
  the children. **What the two Soon placeholders actually describe is real
  scoping work, not filler copy** — see the two new backlog entries directly
  below, written while building this, for what they'd need to become real.
  No per-team destination page exists yet (`/teams` has no `/teams/{id}`
  route — everything happens through modals on the one list page), so hub
  cards link to `/teams` as a whole rather than to an individual team; worth
  revisiting if a team ever gets its own page for another reason.
- [ ] **Global leaderboards, for real** (the Community hub's first Soon
  placeholder). Site-wide rankings across every event a player has taken
  part in, not scoped to one event the way `LeaderboardController` and
  `EventStandingsService` both are today. Concrete shape worth starting
  from: total tiles completed across every Snakes & Ladders board ever
  played, count of events hosted vs. joined, and a "hall of fame" ranking
  the single biggest XP gain and the single biggest kill-count gain ever
  recorded by `EventStandingsService` — the numbers already exist per event,
  this is an aggregation question, not a new data source. Needs deciding
  whether it's all-time or has its own rolling window (a season?), and
  whether an account that's been through the leaving-without-taking-
  everyone-with-you deletion flow should still occupy a hall-of-fame slot as
  "Deleted player" (consistent with how a finished event's own standings
  already survive account deletion) or drop out of a *global*, cross-event
  ranking specifically — a question this app hasn't had to answer yet
  because every ranking has been scoped to one event until now.
- [ ] **Clan directory** (the Community hub's second Soon placeholder). A
  browsable, public-facing directory of clans/teams open to new members —
  distinct from `/teams`, which only ever shows teams a given account is
  already in or shares a Discord server with (`Team::scopeVisibleTo`) and is
  therefore useless for finding a clan you're not already part of. Needs:
  an opt-in "publicly listed" flag on `Team` (defaulting off — a private
  team should stay invisible to strangers exactly as it is today), a public
  index page showing member count, recent activity and upcoming events the
  clan is running, and a real join mechanism for a stranger to request or
  claim a spot (today the only way onto a team is being added by someone who
  already manages it — `TeamController::addMember`/`searchUsers`, both
  scoped to people already visible to the manager). That join mechanism is
  the load-bearing piece: a directory that only ever shows clans without a
  way to actually get into one is a list, not a directory.
- [x] ~~**`/teams` as a masonry grid.**~~ — done 2026-08-26. CSS multi-column
  (`columns-1 sm:columns-2 lg:columns-3` + `break-inside-avoid` per card)
  replaces the plain `grid`, which forced every card in a row to match the
  tallest one — a two-member team and a sixteen-member team sat in equally
  tall boxes, one of them mostly empty. Columns let each card be exactly as
  tall as its own content.
- [ ] **`/teams` team membership history.** A team that had members A/B/C/D
  at one event and A/B/D at a later one is still "the same team," and
  there's currently no way to see that a roster changed between events.
  Worth thinking through as a lightweight revision log on `TeamMember`
  (add/remove timestamps already imply this partially, via `AuditLog`)
  rather than a new versioning system — the audit log already records
  team/member mutations (see Admin & users → Audit log above), so this may
  mostly be a UI question (a "roster over time" view per team) rather than
  new storage.
- [x] ~~**OSRS wiki icon search for team icons**~~ — done 2026-08-26, and
  extended to the task icon field below in the same pass since both wanted
  the identical thing: pick an icon straight off the OSRS Wiki instead of
  pasting a URL by hand. New `WikiController::searchGlobal()` +
  `GET /wiki/search`, gated on being signed in rather than on
  `assertCanEditEvent` — there's no event to check edit rights against for a
  team or a standalone task, and the actual writes (saving the team, saving
  the task) stay permissioned at their own endpoints regardless of what this
  read-only search returns. `TaskPicker.vue` (the tile/bingo-square editor's
  wiki picker) creates or refreshes a **Task** row per choice on purpose — a
  tile really does want a shared, reusable Task underneath it. A team icon
  and a task's own icon field have nothing to attach a second Task to, so
  the new `WikiIconPicker.vue` just emits the chosen `icon_url` directly,
  same search endpoint and result shape, no Task side-effect. Both new
  components are only ever rendered behind `<client-only>` (`TeamSettingsModal`
  via `Teams/Index.vue`, `TaskSettingsModal` via `Admin/Tasks.vue`), which is
  what makes them safe without the SSR gymnastics tile editing's version needs.
  **Not done, left as its own idea**: sourcing team/task icons from an
  in-house icon set instead of (or alongside) a live wiki lookup, now that
  skill icons exist and boss icons are in progress — a genuinely separate
  question from "can you search the wiki at all," which this answers.
- [ ] **Discord server picker: recently-used ordering.** When choosing a
  Discord server for a team or event, most-recently-used/most-often-picked
  servers should sort to the top instead of a flat list.
- [ ] **Discord server picker: server icon per entry**, if the Discord API
  actually exposes a per-guild icon for servers the bot/OAuth session already
  knows about — check what's available before committing to this.
- [ ] **Team edit modal: show the linked Discord server**, and consider
  loading "add member" suggestions from that server's membership if Discord's
  API/our stored guild-sync data supports it (`UserGuild` sync exists for
  login, but check whether per-guild member *lists* are actually fetchable
  with the scopes this app requests before promising this).
- [x] ~~**Team members modal: tooltip, not `title` attribute**~~ — done
  2026-08-26. `u-tooltip` on the promote/demote and remove-member buttons in
  `TeamMembersModal.vue`, safe here specifically because that whole modal is
  already rendered behind `<client-only>` in `Teams/Index.vue` — unlike the
  SkillRace leaderboard's error badge, which stays a native `title` on
  purpose because that page renders server-side and a real `u-tooltip` there
  would be the SSR hazard CLAUDE.md warns about.
- [x] ~~**Teams: replace `window.confirm` on delete with a popover confirm**~~
  — done 2026-08-26. New `Components/ConfirmPopover.vue` (a `u-popover`
  wrapping a message + cancel/confirm pair, with an optional note textarea
  built in for the task-delete item below) replaces `window.confirm()` in
  `Teams/Index.vue`. `Admin/Blueprints.vue` still uses `window.confirm()` for
  its own delete — not touched, since it wasn't asked for, but the same
  component is right there if that's wanted next.
- [x] ~~**Verify what actually happens when a team is deleted**~~ — traced
  2026-08-26, and it deletes more than the confirm text used to say. `Team`
  is hard-deleted, not soft, and three foreign keys cascade off it: its
  `team_members` rows and `board_teams` assignment (both expected), but also
  **`player_boards.team_id`** and, through that, every one of that team's
  `completed_tiles` — meaning a deleted team's actual Snakes & Ladders
  progress (dice position, every tile ticked off) is destroyed on every board
  it was ever assigned to, and the same is true of its bingo claims
  (`bingo_teams`/`bingo_squares.team_id`, also `cascadeOnDelete`). The old
  confirm text ("its members and every event it is assigned to lose the
  team") undersold this badly — it read as an unassignment, not as the
  team's game history being gone for good. `teams.delete_confirm` now says
  so plainly: dice position, completed tiles, claimed squares, all of it.
  No behaviour changed, only the warning — deciding whether team deletion
  should become soft/reversible (matching how `Event` and `Task` both work
  now) is a bigger question than a copy fix and is its own decision to make,
  not assumed here.
- [ ] **Guide pages read as landing pages, not guides — Snakes & Ladders
  done, the rest still open.** `/osrs-snakes-and-ladders`'s "How it works"
  was rewritten 2026-08-27 from one flat five-step list (which mixed host
  actions and player actions with nothing signalling the audience changed
  partway through) into two explicitly-labelled tracks — **Running an
  event** (5 steps: create, give every tile a task, place snakes/ladders,
  decide who can join, share it) and **Playing** (4 steps: join by rolling,
  see what you landed on, complete it or don't, come back tomorrow) — each
  with its own numbering, so "step 3" means something different depending
  which column you're reading.
  **Found and fixed while touching this**: both this list and the "Board
  sizes" section below it were hardcoded English sitting in
  `LandingController::snakesAndLadders()` (`'Create a board'`, `'5x5
  board'`, …) while a full, already-translated, already-correct set of
  `landing.snakes.step*`/`size_*` keys sat unused in `lang/en.json` — nothing
  had ever pointed at them. Both arrays now build through `trans()`, and the
  orphaned duplicate keys the old hardcoded copy would have collided with
  were removed rather than left alongside the ones now actually wired up.
  Also dropped a second, dead JSON-LD builder in the Vue file itself
  (`useSeoData({ jsonLd: [...] })` computed a `jsonLdBlocks` value the
  template never rendered) — the real FAQPage/HowTo structured data for this
  page has only ever come from `LandingController`'s own `View::share
  ('jsonLd', ...)` straight into `app.blade.php`, for the SSR reasons that
  controller method's own comment already documents; the Vue-side attempt
  was pure dead weight computing something nobody read.
  **Screenshots are still not in the page.** A real, populated demo board
  ("Summer Grind Board", owned by the `claude-demo@absolit.nl` account — see
  that seeder's own docblock) was built specifically to screenshot from, and
  renders exactly as intended (task icons, snake/ladder connectors, the "You
  are here" label, the dice roller) — confirmed live via `claude-in-chrome`.
  What blocked finishing this: no tool in this session could get a captured
  screenshot from either browser (Claude_Browser pane or real Chrome via
  claude-in-chrome, including its own `save_to_disk` option) onto a locally
  readable path — searched Downloads, all three Chrome profiles, and every
  Claude CLI cache directory without finding one. Whoever picks this up next
  should drop real PNGs into `public/images/guides/` at the two marked
  `<!-- Screenshot slot -->` comments in `SnakesAndLadders.vue` (the tile
  editor mid-wiki-search, and the live board) rather than reopen that search —
  the demo board is already sitting there ready to shoot.
  **Not started at all**: Bingo, Skill of the Month and Drop Race have no
  guide page of any kind yet, only `/osrs-clan-events` (a general "what is
  this platform" overview, not a per-format walkthrough) and
  `/osrs-event-ideas`. Static content stays the right call for all of these
  rather than waiting on the CMS block vocabulary to grow to support it.
- [x] ~~**"You are here" label on the current tile**~~ — done 2026-08-26,
  **Snakes & Ladders only**. A thin top-of-tile banner (`.board-tile--here-
  label` in `app.css`, same brand-ink-on-amber pairing the primary-button
  contrast fix already established) on whichever tile equals
  `playerBoard.current_position` in `BoardShow.vue`. **Not applied to
  Bingo**: bingo has no single "current position" at all — every square is
  independently claimable, so there's no one tile that "you are here" could
  point at. If the actual want is highlighting the viewer's own
  claimed/pending squares on a bingo card, that's a different feature
  (styling by claim state, not by position) and worth asking for by that
  name rather than assumed here.
- [ ] **A real Snakes & Ladders connector SVG** — dynamic height or an actual
  curved path between snake/ladder endpoints, rather than the current
  percentage-coordinate straight connector (see Branding → "Snake/ladder SVG
  connector lines" above for what exists today). Explicitly open-ended: "see
  how far this can be made to look right," not a fixed spec.
- [x] ~~**Event detail meta fields need tooltips/popovers.**~~ — done
  2026-08-26, as native `title` attributes rather than `u-tooltip`.
  `EventTypeHeading.vue`'s date-range line and every field the three event
  pages add through its `#meta` slot (board size + solo/team on
  `BoardShow.vue`, card size + win condition on `Bingo.vue`, ranked-by on
  `SkillRace.vue`) now explain themselves on hover, dynamically where the
  wording depends on what's configured (solo vs team, line vs full-house,
  skill vs boss race). Also added to the second, lower "meta" card on
  `BoardShow.vue` (dates/size/roll-limit/mode badges), which repeats the same
  facts and was equally unexplained.
  **Native `title`, not `u-tooltip`, everywhere here** — `EventTypeHeading`
  is shared by all three event pages and all three render server-side;
  `u-tooltip` reaches `@nuxt/ui`'s `#imports` specifier, the SSR crash
  CLAUDE.md's SSR-gotchas list warns about (see `SkillRace.vue`'s own error
  badge, which avoids it for the identical reason). The Teams tooltip fix
  earlier in this batch is the exception that proves the rule: that one is
  safe specifically because it's already rendered behind `<client-only>`.
- [ ] **Ideas/feedback page.** A form (guides page, footer, or its own route)
  where anyone can submit an idea or piece of feedback. Needs: honeypot field,
  rate limiting, basic spam heuristics on title+description (flag likely spam
  rather than reject outright; only delete what's unambiguously spam), an
  admin CRUD to review submissions, and on a non-spam submission both an email
  to the site owner and a push notification (existing `NotificationCategory`
  catalogue + `PushNotifier` — see CLAUDE.md's push section for the pattern
  every new category has to follow).
- [x] ~~**Admin events list needs a search box and filters**~~ — done
  2026-08-26. `/admin/events` had no search or filter at all before this —
  every event ever created, in one flat list. `AdminBoardController::index()`
  now takes `search` (title match) and `status` (active/paused/deleted, an
  unrecognised value falling back to 'all' rather than to an empty list) and
  echoes both back as `filters` so the UI reflects what actually ran, not
  what was typed. Debounced reload mirrors Audit.vue's own pattern.
  **The one thing this needed beyond Audit's pattern**: real `sessionStorage`
  persistence, not just the URL — asked for explicitly, since a plain link
  back to `/admin/events` from anywhere else in `/admin` carries no query
  string at all, unlike Audit.vue's filters which only survive within that
  page's own back/forward history. A bare visit with nothing saved uses the
  URL as normal; a bare visit with something saved re-syncs the URL to match
  on load, so the address bar and the visible list never disagree. 7 new
  tests in `AdminEventsFilterTest.php`.
- [x] ~~**Task delete is instant and irreversible in the UI.**~~ Popover
  confirm + optional note + undo, done 2026-08-26. `Task` gained
  `SoftDeletes` (migration `add_soft_deletes_to_tasks_table`) specifically so
  undo could be a real restore rather than a same-title recreate: every tile/
  bingo square's `task_id` uses `nullOnDelete`, which only fires on an actual
  SQL DELETE — a soft delete is an UPDATE, so a tile using the task keeps
  pointing at it the entire time it's "deleted," and restoring the task row
  is the complete undo with nothing to re-link. `TaskController::destroy()`
  takes an optional `note` (recorded on the `task.deleted` audit log entry,
  not stored on the task itself — there's no row left to attach it to once
  deleted) and no longer sets a `board-save` flash, since the frontend shows
  its own actioned toast (`common.undo` → `TaskController::restore()`) and a
  second plain-text toast saying the same thing would just be noise on top
  of it. `ConfirmPopover.vue`'s note textarea is generic (any consumer can
  opt in via `note-placeholder`), not task-specific. 6 new tests in
  `TaskControllerTest.php`.
  **Not done: notifying hosts.** Whether a host should be told when a task
  their board is using gets deleted out from under them is still an open
  question, deliberately not answered here — it needs its own
  `NotificationCategory` entry (throttle, default on/off, copy) per
  CLAUDE.md's push conventions, which is a real design decision and not
  something to fold into a delete-confirm popover.
- [x] ~~**Task edit: icon field is a bare URL.**~~ — done 2026-08-26, in the
  same pass as the team-icon wiki lookup above — see that entry for how
  `WikiIconPicker.vue` works and why it doesn't create a Task the way the
  tile editor's own wiki picker does.
- [x] ~~**Diagnostics: "N standings are failing to sync" needs to actually be
  actionable.**~~ — done 2026-08-27. New `DiagnosticCheck::$key` (only set on
  `wom_standings`) lets the page attach a "Details" button to this one check
  without matching on its label; opens `StandingsFailuresModal.vue`, fetched
  from a new `GET /admin/diagnostics/standings`.
  **Grouped by account, not by row** — the same wrong RSN commonly fails
  several events at once, and the fix (a nudge, a reset) is one action
  against the account, never one per event. A row whose account no longer
  exists is still counted (so the summary line and the modal never disagree)
  but offers neither action — there's nobody left to nudge.
  **Nudge** sends a real push through a new catalogue category,
  `OSRS_USERNAME_REMINDER` — its own category rather than riding along under
  an existing one, so a player who muted something else for an unrelated
  reason doesn't lose this too. Logged via `AuditLog` (`diagnostics.
  osrs_nudge_sent`), and the modal surfaces the count and "last nudged
  :when" per account so a repeat failure reads as "reset it" rather than
  "nudge it again," exactly as asked — though the choice between the two
  stays the admin's, nothing here forces a reset after N nudges.
  **Reset** clears `osrs_username`/`osrs_verified_at` outright rather than
  editing them — `RequireOsrsUsername` middleware already sends any account
  with neither set through `/welcome/osrs-username` on its next page load,
  the same path a brand new signup takes, so "re-onboard cleanly" needed no
  new UI at all. Logged via `AuditLog` (`diagnostics.osrs_username_reset`,
  carrying the old value in metadata).
  **The page's own "nothing here reaches another user" guarantee is
  deliberately given up for these three routes only** — the class docblock
  now says so explicitly, since it used to justify skipping confirmation
  dialogs entirely. Both actions sit behind their own `ConfirmPopover`.
  7 new backend tests, plus live verification against the actual running
  server (not just PHPUnit) for both the nudge and the reset.

Two questions answered while triaging this batch rather than filed as work:

- **`WOM_USER_AGENT`** — `config/services.php`'s `services.wom.user_agent`,
  defaulting to `env('WOM_USER_AGENT', 'osrs-events')`. Wise Old Man's API
  etiquette asks every caller to identify itself with a contact address in its
  User-Agent so they have someone to reach if a client is hammering them (see
  the comments in `WiseOldManService`/`config/services.php`) — it should be
  something like `"osrs-events (contact: you@example.com)"`, per
  `.env.example`'s own documented format, not the bare default. It is
  unrelated to the API key: the key raises the shared rate limit, the
  user-agent just says who's asking. Whether it should be the owner's personal
  email or a dedicated project address is a judgement call, not a technical
  one — either satisfies WOM's ask.
- **Discord webhooks/announcements** — already built, not missing from the
  backlog: `Event::$fillable` carries `discord_webhook_url`, and
  `app/Services/DiscordAnnouncer.php` + `EventNotificationService` send
  through it. If specific announcement triggers are still missing (which
  events, which moments), that's a narrower follow-up than "is this built at
  all" — worth naming precisely if something's still not firing.

## The pre-launch door blocked the wrong people — 2026-08-25

Reported live, from staging: an ordinary signed-in account ("MB Test") saw
the header showing them fully logged in, the home page's "not open yet"
banner, AND the onboarding welcome modal, all at once — described as "iets
geks/fout" (something weird/wrong), a session mix-up.

It wasn't a mix-up. It was the pre-launch door doing exactly what it had
always been built to do, and that turned out to be the wrong thing:
`EnsureSiteUnlocked` let an **admin** session through and nobody else,
including an ordinary account that had signed in perfectly normally. The
owner's own read, unprompted: *"het slotje is puur bedoeld ter afscherming
van nieuwe mensen"* — the lock exists to keep out newcomers, not to lock out
people who already have an account. Someone already signed in has, by
definition, already gotten past whichever door they came through; asking them
for the shared password on top of that answered "are you allowed to use the
site you're already using" with a password box. The home page's "not open
yet" banner and the header's trimmed nav were reading the same wrong signal,
which is why both fired for a fully-authenticated player at once.

- [x] ~~**Fixed: any signed-in account now walks straight through the
  pre-launch door.**~~ `EnsureSiteUnlocked` (now exposing a shared static
  `isShutFor()` that both the middleware and `HandleInertiaRequests`' `site`
  props call, so the route gate and the header/home-page signal can't
  disagree the way they just did) passes anyone with an existing session —
  any role, not only admin. The door still refuses a stranger with no
  account and no shared password, and still refuses new registration
  (including via Discord — `DiscordController::registrationClosed()`).
- [x] ~~**Added the second, stricter switch the owner asked for, with
  different naming and look.**~~ `admin_lockdown_enabled` — "Full lockdown"
  in the admin Site settings, deliberately styled in `error` red rather than
  the pre-launch lock's neutral tone, so the two don't read as "the same
  lock, one notch further." While it's on, **only** an admin session gets
  through — not an existing player, not the shared password, and (unlike the
  pre-launch door) not the public marketing pages either, since "blocks
  everything except for admins" was the explicit spec. Sign-in routes stay
  reachable regardless, or an admin who isn't currently signed in would have
  no way to become one. 31 tests in `SiteLockTest.php` cover both doors,
  including that full lockdown refuses the shared password outright rather
  than silently accepting it and bouncing the visitor right back.

Not touched: the onboarding modal itself. It was never gated by the lock at
all (`needsOnboarding` + a non-`Auth/` page), and showing a real welcome tour
to a real logged-in account is correct regardless of whether the site is
pre-launch — it was only ever confusing *alongside* the incorrect "not open
yet" banner a moment earlier, which the fix above removes for anyone actually
signed in.

## Feedback batch, round two — 2026-08-25

- [x] ~~**Active bingo/S&L tasks need a wiki link and an explanation popup.**~~
  Done 2026-08-27. The `board.your_task`/`board.tile_info` cards in
  `BoardShow.vue` already showed the full description inline, so no separate
  popup was needed there — just the missing wiki link, added next to the
  title the same way `TaskPicker.vue` already links out. The bingo side had
  the bigger gap: `BingoClaimModal.vue` — the dialog a player actually opens
  to claim a square — showed neither the description nor a wiki link at all,
  and `BoardController::bingo()`'s `squares.task` eager-load only selected
  `id,title,icon_url`, so `description`/`wiki_url` weren't even reaching the
  frontend. Both now show a task header (icon, title, wiki link, description)
  above the claim form — that header **is** the explanation popup the item
  asked for, since the claim dialog already is one. Also added the same wiki
  link to the Admin > Tasks list row.
  **Re-seeding, since this exposed how bare the demo data was:**
  `DemoDataSeeder::seedTasks()` grew from 14 tasks to 66 (bossing, Slayer,
  clues, minigames, more skills, quests/diaries), all with `wiki_url` now —
  including backfilling it onto the original 14, which predate that column
  being read here. `seedTiles()` used to leave ~70% of NORMAL tiles without a
  task at all (a 3-in-10 roll, the rest a generic label or a one-off
  "Community choice tile" override); every NORMAL tile gets a real task now,
  shuffled and cycled per board instead of drawn with replacement, so the
  same task doesn't cluster. A new `backfillTileTasks()` fixes up
  already-seeded boards too — `seedTiles()` itself only ever fires once per
  board (`$board->tiles()->count() === 0`), so the old sparse assignment
  would otherwise have stuck around forever on every board seeded before this
  change. Verified against the 9 demo boards after a re-seed: zero empty
  NORMAL tiles, 20–54 distinct tasks per board depending on size.

## Guide pages for the other three event types — 2026-08-27

Picks up two things the S&L guide rewrite (above, "Guides content hierna")
left open: real screenshots were still blocked, and Bingo/Skill Race/Drop
Race had no guide page at all even though all three have been `available =>
true` in `Event::EVENT_TYPES` this whole time — `/osrs-event-ideas` was
telling visitors "Bingo boards are the next event type on the roadmap," which
was simply wrong.

- [x] ~~**Visible screenshot placeholders, not HTML comments.**~~ New
  `GuideScreenshot.vue` (`resources/js/Components/`): given only an `alt`
  description and no `src`, it renders a dashed-border box with an image icon
  and the description as visible body text — the placeholder's actual content
  is the description, not a blank rectangle. Once a real file exists under
  `public/images/guides/` and gets passed as `src`, the same component swaps
  to a real `<img :alt>` **plus that same text repeated as a visible
  `<figcaption>` underneath** — the explicit ask was that the description
  must show up for sighted users too, not live only in the invisible `alt`
  attribute. Replaced the two bare `<!-- Screenshot slot -->` comments in
  `SnakesAndLadders.vue` with it; the screenshot-tooling dead end itself
  (documented in the previous entry) is unchanged and still unresolved — this
  only fixes what the page shows in the meantime.
- [x] ~~**Bingo, Skill Race and Drop Race guide pages, same host/player
  two-track shape as Snakes & Ladders.**~~ New routes `/osrs-bingo`,
  `/osrs-skill-race`, `/osrs-drop-race` (`LandingController::bingo()` and a
  shared `metricRacePage()` helper for the two WOM-backed types, since
  `SKILL_RACE`/`DROP_RACE` are one pipeline server-side already —
  `Event::needsMetric()`, `EventParticipationService`, the same
  `SkillRaceController` routes — differing only in `SKILL_METRICS` vs
  `BOSS_METRICS`). Each page: hero, a host-steps/player-steps two-column
  section (5 host steps, 4 player steps, mirroring the S&L page's structure
  and using the same `GuideScreenshot` placeholder pattern), a "why this
  format" paragraph, a 3-item features grid, and an FAQ — CMS-editable via
  `faqsFor()` the same way the existing three landing pages are, falling back
  to the shipped copy.
  **Drop Race's copy deliberately corrects the format's own name**: the
  underlying mechanic is a boss *killcount* race (`bosses.{name}.kills` from
  Wise Old Man, per the existing comment in `Event.php`), not a log of actual
  item drops — the guide says so explicitly ("Kill count, not drop luck")
  rather than let the page's own name overpromise RNG-drop tracking that
  doesn't exist.
  Wired into every place the three existing guides already appear:
  `AppHeader.vue`'s Guides submenu (desktop hover + mobile drawer),
  `AppFooter.vue`, `EnsureSiteUnlocked::PUBLIC_ROUTES` and
  `Support/landing.js`'s `PUBLIC_PATHS`/`LANDING_PAGES` (so all three stay
  reachable and correctly-styled while the site is pre-launch-locked),
  `SitemapController::STATIC_PATHS`, and `Page::PARTIAL_SLUGS`.
  **Also fixed**: `landing.event_ideas.supported_body` — the sentence
  claiming Bingo was still "the next event type on the roadmap" — now lists
  all four formats the site actually runs.
  Covered by `tests/Feature/NewGuidePagesTest.php` (steps/modes/FAQ counts
  and FAQPage JSON-LD, all three routes) plus extended assertions in
  `SiteLockTest.php`, `SitemapTest.php` and `tests/js/landing.test.js`. Full
  suite green (734 backend, 174 frontend) after the change; both
  `pnpm build` and `pnpm exec vite build --ssr` reran and the SSR node
  process restarted on the new bundle. Verified live in-browser: all three
  new pages, the S&L page's two placeholder boxes, and the nav drawer's
  Guides submenu links.

## Jagex attribution wording, corrected — 2026-08-27

Double-checked against the actual policy text
(`legal.jagex.com/docs/policies/fan-content-policy` §8.1), which requires this
exact sentence "in a prominent and visible place":

> "Created using intellectual property belonging to Jagex Limited under the
> terms of Jagex's Fan Content Policy. This content is not endorsed by or
> affiliated with Jagex."

- [x] ~~**The live disclaimer didn't match — close, but not the required
  wording.**~~ The footer (`common.not_affiliated`, rendered on every page —
  the actual "prominent and visible" placement, more so than one page) said
  only "Not affiliated with Jagex Ltd." The `/terms` page's own Jagex callout
  (`LegalPages::terms()`) was closer but still a paraphrase, not the
  sentence. Both now carry the exact required wording verbatim. Ran
  `php artisan pages:sync-legal` to push the `/terms` change into the
  existing database row — `LegalPages.php`'s own docblock warns that editing
  the file alone does nothing on an environment that already seeded the page,
  since the seeder only `firstOrCreate`s.
- [x] ~~**Found and removed a second, dead copy while checking.**~~ An entire
  earlier draft of the terms page — 26 `terms.*` keys in `lang/en.json`
  (`terms.jagex_body`, `terms.service_body`, `terms.acceptance_body`, etc.) —
  turned out to be orphaned: nothing in `resources/js` or `app/` referenced
  any of them, only the bundled translation file itself. This is what the
  session initially (and wrongly) quoted back as "the current wording" before
  actually checking what renders — `LegalPages.php` (`Written from what the
  schema stores, checked against it on 2026-08-24`) superseded this whole
  namespace at some point and nobody deleted the leftover keys. Removed
  rather than left sitting there as a second, disagreeing source of truth for
  the same legal page.

Full suite still green after (734 backend / 174 frontend); both bundles
rebuilt, SSR restarted, and the exact sentence verified live on both the
site-wide footer and `/terms`.

## The guide pages read like a landing page, not a guide — 2026-08-27

Reported directly: *"het feit dat alles met zulke ontiegelijke grote margins
wordt verspreid alsof het nog een landingspagina is ofzo vind ik exceptioneel
kut om te lezen"* — the six guide pages (Snakes & Ladders, Bingo, Skill Race,
Drop Race, Clan Events, Event Ideas) were built from `u-page`/`u-page-hero`/
`u-page-section`, which are @nuxt/ui's own marketing-landing-page components:
centered narrow content, huge vertical gaps between sections, no theme
override in `ui.config.ts` to soften it because there isn't one to write —
the components are the wrong shape for the job, not a CSS tweak away from
right. Explicit reference point given: OSRS Wiki — dense, connected,
information-first, with a sidebar.

- [x] ~~**New `GuideLayout.vue`**~~ — compact left-aligned header (title,
  lead, CTA row) instead of a full-bleed hero; a two-column
  `grid-cols-[1fr_260px]` body below it; the article column capped at
  `max-w-3xl` so a line of text isn't stretched across a 1440px screen (the
  actual cause of "erg lelijk om te lezen" — nothing was wrapping, it was
  just unreadably wide); a `sticky` sidebar, desktop-only, carrying three
  wiki-style blocks decided in this pass since the ask left them open to
  "door jou te bepalen": an anchor-linked **"On this page"** table of
  contents, a **"Quick facts"** infobox (grid size / win condition / metric /
  players — whatever's fixed per page), and an **"Other guides"** list. Mobile
  drops the sidebar entirely rather than building a collapsible ToC — these
  pages are short enough on a phone that a jump-nav would cost a tap to reach
  content already one scroll away.
  **`@apply` in a scoped `<style>` block was tried first and reverted** —
  Tailwind v4 rejects `@apply` inside a Vue SFC's scoped style unless it also
  imports the theme via `@reference`, which isn't a pattern used anywhere
  else in this codebase. Went with a shared `GUIDE_PROSE` class-string object
  in the new `resources/js/Support/guides.js` instead — plain Tailwind
  utility classes applied directly to each page's own tags, one definition,
  no scoped-style plumbing.
  Same file adds `GUIDE_LINKS` (path/label-key/icon for all six guides), now
  the one source both `AppHeader.vue`'s Guides dropdown and `AppFooter.vue`'s
  footer links map over — previously typed out twice (three times, counting
  the sidebar this pass just added), which is how a dropdown and a footer
  eventually stop agreeing on what a "guide" is.
- [x] ~~**All six guide pages rebuilt onto it.**~~ Same wiki-style rhythm
  everywhere: underlined `<h2>` section headers, connected paragraphs, tight
  lists instead of feature-grid cards for things like Bingo's win conditions
  or Snakes & Ladders' board sizes. `GuideScreenshot` placeholders (see the
  entry above) carried over unchanged into the new layout.
- [x] ~~**Dropped CMS-editability for these six pages' FAQ, per explicit
  instruction — "Maak ze statisch en zelf zonder CMS. Dat fixen we later
  wel."**~~ Three of the six (`osrs-snakes-and-ladders`, `osrs-clan-events`)
  used to read their FAQ from a `pages` database row if one existed
  (`LandingController::faqsFor()` → `Page::faqItems()`), falling back to a
  hardcoded array otherwise; the other three never had this wired up at all.
  All six now always render the static array — `faqsFor()` and
  `Page::faqItems()`/`collectFaqs()` removed as dead code (zero remaining
  callers), `PageSeeder.php` no longer seeds FAQ blocks for these slugs.
  `Page::PARTIAL_SLUGS` keeps all six regardless — an environment that
  already ran the old seeder still has these rows, and that list's job (keep
  them out of the CMS inventory and the `/{page}` catch-all) doesn't depend
  on whether anything still reads their content.
  `tests/Feature/LandingPageFaqTest.php` rewritten from "a Page row overrides
  the shipped FAQ" to the opposite: a leftover row for any of the six slugs
  must be ignored. The generic `'faq'` CMS block type itself is untouched —
  still valid on any other editable page — only the special-case landing-page
  wiring is gone.

Full suite green after (740 backend / 174 frontend — the FAQ test rewrite
added a few); both bundles rebuilt, SSR restarted, verified live on all six
guide pages at 1100px and mobile width, including that the sidebar ToC's
anchor links, the quick-facts box and the other-guides list all render
correctly and exclude the current page from its own "other guides" list.

## Guide-page follow-ups from the first real read-through — 2026-08-27

- [x] ~~**FAQ made foldable, open by default.**~~ New `GuideFaq.vue` — native
  `<details>`/`<summary>` per question, no JS and nothing to `<client-only>`
  for SSR. `open` by default: these are guide pages meant to be read, not FAQ
  widgets meant to stay collapsed until clicked, so foldable is the
  affordance (a question you've already read can be closed out of the way)
  rather than a way to hide the answers. Replaces the inline `<dl>` on all
  five FAQ-carrying guides.
- [x] ~~**Fixed a real spacing bug the redesign shipped with.**~~ Every
  section's `<h2>` had `first:mt-0` in its shared class, meant to zero the
  top margin on the page's opening heading only — but each `<h2>` is the
  first child of its own `<section>`, so `first:` matched every one of them,
  not just the first on the page. Every heading below the first was hugging
  the paragraph above it with no breathing room. Removed; `mt-12` now applies
  everywhere it's supposed to.
- [x] ~~**Light-mode quick-facts box looked off.**~~ `bg-elevated/50` over
  the page background picked up a muddy, faintly-warm tint in light mode
  instead of a clean neutral card. Solid `bg-elevated`, no opacity.
- [x] ~~**Snakes & Ladders' quick facts relabelled and extended.**~~ "Rolls:
  1 per day (default)" buried the fact that it's a default inside the value
  string instead of saying so in the label — now "Default roll limit: 1 per
  day" (it genuinely is a per-board default, `Board::dice_roll_limit`, not a
  fixed rule — a host can raise or remove it). Added a fourth fact, Access
  (open / Discord / invite), matching the fact count the other five guides
  already carry.
- [x] ~~**Clan Events' "Free, no ads" quick fact cut down to "Free".**~~
  Reported directly as reading like an unprompted denial nobody asked for.
  The longer "free, ad-free, no paid tier" framing still exists as an actual
  feature-grid item elsewhere on that same page, where the context to explain
  it exists — the one-line infobox fact didn't need to defend against an
  objection nobody raised.
- [x] ~~**Event Ideas: badged the four formats that aren't built, and fixed
  one that was misdescribed.**~~ The page listed eight formats with no
  signal that only four exist as a real event type
  (`Event::EVENT_TYPES`) — Speedrun ladder, Achievement diary/quest race,
  Battleship and Collection log push now carry the same "Soon" badge the nav
  uses for planned features. Also found a real content bug while at it:
  "Drop log race" was described as teams logging actual item drops for
  points — that is NOT what the shipped Drop Race does (kill count from the
  moment you join, synced via Wise Old Man, same as Skill Race but on a boss)
  — so the idea was quietly promising a different, unbuilt mechanic under
  the name of a feature that already exists. Renamed to "Drop race" and
  rewritten to describe the real mechanic; `LandingController::eventIdeas()`'s
  `$formats` ItemList array updated to match.
- [x] ~~**The four unbuilt formats are now real backlog items, not just
  page copy.**~~ Filed below as their own entry rather than only living as
  marketing copy on `/osrs-event-ideas`.

**New backlog items — future event types** (from the Event Ideas page, not
yet built, no entry in `Event::EVENT_TYPES`):
- [ ] **Speedrun ladder** — a fixed set of encounters, members submit times,
  runs indefinitely with no end date. Lowest-maintenance format on the list;
  good as a permanent background event between bigger ones.
- [ ] **Achievement diary or quest race** — points for diary tiers or quests
  completed inside the event window. One of the few formats that favours
  newer accounts over veterans, since veterans have already finished
  everything — useful for onboarding a wave of new members.
- [ ] **Battleship** — each team hides ships on a grid and fires by
  completing tasks. Very social, very chat-heavy, and needs an organiser
  watching it daily — the format lives or dies on somebody refereeing it.
- [ ] **Collection log push** — track collection log slots filled across the
  whole clan against one shared target. Cooperative rather than competitive,
  which suits clans where a leaderboard would put people off rather than
  draw them in.

Full suite still green (740 backend / 174 frontend); both bundles rebuilt,
SSR restarted.

## The RuneLite plugin is not a replacement for the approval system — 2026-08-28

Raised directly, not yet acted on: the owner wants to build the RuneLite
plugin `docs/runelite-plugin.md` already scopes, but is explicit that it must
not become a gate. Players keep free choice of client — mobile, OSRS
Deadman-style clients, or just not running RuneLite at all — and none of that
can turn into "your claims don't count." The manual claim/screenshot path
(`BingoClaimModal.vue`, `board.complete_tile` on `BoardShow.vue`) has to stay
first-class for as long as this app exists, not a fallback that quietly rots
once the plugin ships.

This actually matches what `runelite-plugin.md`'s own "Trust boundary" section
already says — plugin completions are *claims*, not verified fact, because
anything a local HTTP client sends with a token is trivially forgeable.
`CompletedTile.completed_via` already models `MANUAL | RUNELITE` for exactly
this reason. What that section left as a "consider" is now a real ask:

- [ ] **Surface `completed_via` in the UI**, not just the schema. A board
  owner reviewing a bingo card's pending queue (`BingoReviewModal.vue`) or
  looking at a Snakes & Ladders board's completed tiles currently can't tell
  which were plugin-reported vs. manually claimed — needed before the plugin
  ships, not after, or every auto-completion looks identical to a screenshot
  claim with no way to audit it later. Confirmed as the right call.
- [x] ~~**The plugin must never be the only path to a tile.**~~ Confirmed,
  explicitly — "dit zal elke event raken" (this will touch every event).
  Every task a board can ask for has to stay completable by hand, full stop.
  Less a code change than a standing design constraint on the tile/task
  model, worth keeping in mind on every future task type: if a task can only
  be verified by something the plugin detects and a human reviewing a
  screenshot cannot judge, that task type isn't allowed to exist.

**Resolves the open "does a plugin completion still need approval" question
above** — the answer isn't one global rule, it's a choice per board:

- [ ] **Per-board "trust RuneLite" toggle**, owner/host-only, off by default.
  Off (the safe default): a RuneLite-reported completion lands exactly like
  a manual claim — same `requires_approval` gate, same review queue, nothing
  skipped. On: the host is explicitly extending clan-level trust to their own
  players, and a plugin completion on that board auto-approves — no queue,
  no review click. This is deliberately an opt-in the *host* makes about
  *their own* clanmates, not a platform-wide policy — a casual weekend board
  and a competitive clan championship are not the same trust decision, and
  the toggle is what lets them differ instead of picking one rule for both.
  Needs its own `Board` column (`trust_runelite_completions` or similar) and
  a branch in whatever the plugin's completion endpoint becomes — doesn't
  exist yet, since the plugin itself doesn't exist yet, but the schema
  decision is cheap to make now and expensive to retrofit once boards exist
  that already have completions on them.
- [ ] **Guides content: how to take a claim screenshot that actually holds
  up.** Raised alongside the above — the six guide pages
  (`docs/backlog.md`, "The guide pages read like a landing page, not a
  guide") are the natural home for this, not a tooltip buried in
  `BingoClaimModal.vue`. What actually needs to be visible in a screenshot
  (username, timestamp, the specific drop/message) is currently only
  implied by `bingo.claim_intro`'s one line of copy
  ("Post a screenshot showing the drop message or collection log slot, with
  your username and the timestamp visible.") — worth a real section with an
  example image, since a rejected claim over a bad screenshot is exactly the
  kind of friction a guide page exists to prevent, and it's the same
  screenshot standard whether or not a board ever gets a RuneLite plugin.

## The type-icon corner mark, and `/my-events` reading as three different pages — 2026-08-28

Two rounds of iteration on the events-hub card's type indicator, then a
consistency pass on `/my-events` off the back of it.

- [x] ~~**`BoardCard.vue`'s type badge replaced with a corner mark.**~~ The
  pill (icon + "Snakes & Ladders"/"Bingo" label) repeated across every card
  in a grid read as the loudest thing on the page for the fact needed least
  — the description rows already imply it. Three real bugs surfaced getting
  the replacement right, not just taste:
  1. **z-index**: `relative` alone does not open a new stacking context —
     only a positioned element with an explicit z-index does. Without `z-0`
     alongside it, `-z-10` on the mark was scoped to whatever ancestor
     *did* open one, however far up the tree, and the icon could vanish
     behind something unrelated. Root cause of "I don't see it at all."
  2. **Self-overlapping stroke icons**: `text-{color}/N` sets alpha on
     `currentColor`, which is *per path*. `worm`'s stroke crosses itself, so
     two 20%-alpha strokes overlapping compounded to ~36% where they
     crossed — the doubled, darker line the icon showed. `opacity-N` on the
     `<svg>` itself composites the icon as one flattened layer instead,
     which also stopped mattering once —
  3. **No snake icon in Lucide.** `worm` was the stand-in and just looked
     wrong. `@iconify-json/mdi` added (matches the existing
     `@iconify-json/lucide`/`simple-icons` pattern — dev dependency, only
     referenced icons bundle) for `mdi:snake`, a real filled snake glyph.
     `mdi:ladder` is sitting in the same package if the mark ever needs to
     say "ladder" too. Final treatment: `text-muted opacity-20`, not
     `text-primary` — primary is the one hue this page already uses for
     "click here" (the Play button), and decorating with it made the mark
     compete with the actual call to action on its own card.
- [x] ~~**`/my-events` redesigned around one consistent preview slot.**~~
  Reported directly: every event type rendered differently and the page
  felt messy. Root cause: the preview block (`BoardPreview`/`BingoPreview`)
  was conditional on which data happened to exist, not on what kind of
  event the row was — a race got nothing (no grid to draw), and a *hosted*
  board or bingo card got nothing either, because `entry.preview` and
  `entry.card` are only populated once you've actually played/opened one.
  - New `RacePreview.vue`, the race analogue of the other two — same
    `aspect-square` footprint, shows rank/participants/XP gained when
    entered, the shared error/pending copy from the standings table when
    sync hasn't landed, and a distinct "not entered" state (trophy icon)
    for a race you only host. That third state used to just be blank.
  - A **hosted-but-not-played board** now gets `BoardPreview` in its
    existing illustrative mode (no `specialTiles`/`currentPosition` given)
    — the same placeholder the create-event form already shows, instead of
    an empty box.
  - One container size for all three previews (`w-64` — was `w-64` for a
    board and `w-56` for bingo, two numbers with no reason to differ).
  - The inline rank/participants/XP block in the content column was a
    second, differently-laid-out copy of what `RacePreview` now shows in
    the consistent slot — removed rather than kept as a duplicate.
  - Bingo had no entry at all in the meta-facts row (a board said its grid
    size, a race said what it ranked on, bingo said nothing) — added
    `:size × :size card` there too, reusing `boards.bingo_card`.
  - The primary button's label/icon was gated on `=== 'board'`, so bingo
    fell into the *race* branch by elimination and said "View standings"
    with a trophy icon for a card that is played, not ranked. Now
    board → Continue, bingo → Play, race → View standings.
  - `formatXp()` was independently defined in three places now
    (`SkillRace.vue`, `Boards/Mine.vue`, and the new `RacePreview.vue`) —
    extracted to `formatMetricValue()` in `Support/metrics.js`, the file
    that already owns everything else about how a metric's value is
    labelled.
  - Verified logged in as the `ClaudeDemoUserSeeder` account
    (`claude-demo@absolit.nl`), enrolled into one of each kind (a played
    board, a hosted-only board, an entered race, a joined bingo card) —
    all four now show a populated preview in the same slot, same size.

## `/my-events` button consistency, a rename, and a page-transition regression — 2026-08-28/29

Follow-up feedback on the pass above, plus a scoped motion request.

- [x] ~~**One button label, "Open", instead of Continue/Play/View standings.**~~
  All three went to the exact same href — the words differed, the
  destination never did. Fixed in both `Boards/Mine.vue`'s row button and
  `BoardCard.vue`'s grid-card footer button (same underlying bug: gated on
  `board.size`/`=== 'board'`, so bingo fell into "View standings" in both
  places). New `common.open` key; `boards.continue`, `boards.play` and
  `events.view_standings` were left with zero references anywhere in
  `resources/js` afterward and were removed rather than kept as dead keys.
- [x] ~~**Participant counts, where relevant.**~~ Added `withCount('participants')`
  to `BoardController::mine()`'s query and a `participants` field per entry,
  shown in the meta-facts row for every kind (reuses the existing
  `participants.count` key). Surfaced a real, if minor, data gap while
  testing it: `DemoDataSeeder::seedPlayerBoard()` and `seedSkillRace()`
  create `PlayerBoard`/`EventStanding` rows directly and never wrote the
  matching `EventParticipant` row, so `participants()->count()` — the
  column this feature reads — came back 0 on boards with real seeded
  players. **Not a live bug**: `EventParticipationService::join()`, the
  path an actual "Join event" click goes through, already writes
  `EventParticipant` correctly; only the seeder's direct-Eloquent shortcuts
  skipped it. Fixed in the seeder to match, re-ran it, confirmed non-zero
  counts on re-check.
- [x] ~~**"Who is playing" renamed to "Participants."**~~ `participants.heading`,
  `participants.open` (the button on BoardShow/Bingo/SkillRace that opens
  the page), and `participants.title` (the browser tab title) all updated;
  a stale comment in `ui.config.ts` referencing the old label by name
  updated too.
- [x] ~~**Page transitions, modelled on a "vet" one built in a sibling
  project (`i:/portfolio`).**~~ Shipped, but not on the first attempt —
  recorded in full because the failure mode is worth remembering:
  - **First attempt (reverted the same session): a Vue `<Transition
    name="page" mode="out-in">` wrapped around `<component :is="page">` in
    `AppRoot.vue`, keyed on the URL** — the direct equivalent of what
    Nuxt's `pageTransition` config does automatically, since Inertia has no
    such built-in. **Broke real navigation immediately**: clicking into an
    event, and the browser's own Back button, both landed on a blank page.
    `mode="out-in"` will not mount the next page until it receives a
    `transitionend` for the one leaving — and for whatever reason, in this
    particular unmount/remount chain, that event never fired, so the app
    just sat there empty, permanently, waiting on a contract that was
    never going to complete. Reported directly by the owner within
    minutes of shipping; reverted immediately, confirmed fixed with real
    click-throughs (forward and Back, on both an S&L board and a bingo
    event) before touching it again.
  - **Second attempt (shipped): no `<Transition>`, no remounting, no
    `:key`.** `router.on('start', …)` / `router.on('finish', …)` toggle a
    plain `navigating` ref, bound as a `page-navigating` class directly on
    `<component :is="page">` (not on its `display: contents` wrapper —
    that property has a history of inconsistent browser support for
    `opacity`). `finish` fires whether the visit succeeded, failed, or was
    cancelled, so the dim always clears. This class of approach cannot
    reproduce the first attempt's failure: the page component is never
    unmounted, so there is no lifecycle contract to hang on — worst case
    if something's off, the class just doesn't toggle and there's no
    visual effect, never a blank page. Deliberately brisk per the ask
    ("niet te langzame transities, lekker vlot") — 100ms, opacity only, no
    transform. `prefers-reduced-motion: reduce` turns it off outright
    (`transition: none`), which is safe here specifically because nothing
    is waiting on the transition to end, unlike the first attempt where
    the reduced-motion path would have made the hang worse, not better.
    Verified with real clicks (not just tool-driven navigation) through
    multiple hops each direction, console clean throughout.

Full suites still green after (740 backend / 174 frontend).

## Breadcrumbs, a genuinely clickable board preview, and a mobile title bug found while checking — 2026-08-29

- [x] ~~**Breadcrumbs on every event-related page.**~~ Home → Events, plus
  the page's own place in the hierarchy — `Boards/Index.vue` (`/events`),
  `Boards/Mine.vue` (`/my-events`), `Boards/Leaderboard.vue`,
  `Events/Participants.vue`, and — one addition covering three pages at
  once — `EventTypeHeading.vue`'s `event.title` crumb, computed from the
  live event so a host renaming mid-visit doesn't leave it stale, added
  individually to `BoardShow.vue`, `Events/Bingo.vue` and
  `Events/SkillRace.vue` (the heading component itself sits inside a flex
  row with a sibling action bar, so the breadcrumb had to live on the page
  above that row rather than inside the shared component, or the action
  bar would misalign against it). Plain `u-breadcrumb :items`, no new
  wrapper component — it's a single prop pass-through everywhere, nothing
  to abstract.
- [x] ~~**The board preview on `/my-events` looked clickable and wasn't.**~~
  Reported directly. `BoardPreview`/`BingoPreview`/`RacePreview` sit in
  their own block beside the row's content, outside both the title link
  and the "Open" button — and `BoardPreview` specifically reuses the real
  board's `.board-tile` styling (app.css), hover scale-up included, which
  is built for an actually-clickable tile. The preview inherited the cue
  with none of the behaviour. Fixed by making the whole slot a real
  `<Link :href="/events/:id">` rather than stripping the hover effect —
  the affordance was correct, the page just needed to make it true.
  Verified the click lands on the event page, not just that it looks
  right.
- [x] ~~**Found and fixed while checking the above at a real phone
  width, not the tool pane's own ~530px shape: the event-page title
  rendered one letter per line at 375px.**~~ Directly prompted — "denk je
  om de layout?" — and worth checking with a real breakpoint, not
  assuming the browser pane's own width proved anything. Measured before
  guessing: the heading's flex-item width was 4.8px against a 343px row,
  the action bar beside it 322px. `BoardShow.vue`, `Events/Bingo.vue` and
  `Events/SkillRace.vue` share one row —
  `flex items-start justify-between gap-4 flex-wrap` — with the heading as
  a `flex-1` child. That combination has a real heuristic problem: a
  browser decides whether a flex row wraps using each child's
  *hypothetical* (pre-grow) size, and `flex-1`'s basis is 0 — so the
  heading "wants" nothing going into that decision, the action bar's own
  wrapped width already fits beside it, and the row never wraps at all.
  What renders instead is the leftover after grow: ~5px, and `break-words`
  on the `<h1>` did exactly what it's told with that little room —
  breaking every single character. `BoardShow.vue` had *already* fixed the
  adjacent symptom once (`sm:shrink-0` on the action bar, with a comment
  explaining a 772px-wide control bar running off a 375px screen) — that
  fix was real but incomplete, since it addresses the action bar's own
  width, not the heading's starvation next to it, and evidently was never
  re-tested at a width narrow enough to expose this second half. Fixed by
  switching the row to `flex-col sm:flex-row` on all three pages instead
  of fighting the wrap heuristic — the two stack, full width each, below
  `sm`, so there's no wrap decision left to get wrong. Verified at a real
  375×812 viewport (`resize_window`) on all three event-type pages plus
  `/my-events`, not just the pane's own shape.

Full suite still green (174 frontend; no backend touched this pass).

## An upcoming event could be played — reported live from the "Starts next month" demo board — 2026-08-29

Screenshotted directly: an event badged "Upcoming" with a working dice
button and "Click to roll" beside it. Real bug, both sides of the request.

- [x] ~~**Nothing server-side checked the start date at all.**~~ `Event` had
  `isPaused()` and `isEnded()`, both mirroring `boardEventStatus()` in
  `Support/board.js`, but no `isUpcoming()` — so
  `PlayerBoardController::roll()`, `PlayerBoardController::toggleTile()` and
  `BingoController::claim()` each checked paused/ended and stopped there.
  Confirmed the gap was real, not theoretical: a direct POST to `/roll` on
  the upcoming demo board (bypassing the UI entirely) created zero
  `PlayerBoard` rows before the fix. New `Event::isUpcoming()`
  (`start_date`'s own day counts as started, mirroring `isEnded()`'s
  `endOfDay()` the other direction — a board due today is live from
  midnight), checked in all three, each with its own already-established
  message key (`events.not_started`, or `bingo.event_not_started` — new —
  for the bingo-specific "claims aren't open" phrasing `bingo.event_ended`
  already used).
- [x] ~~**Frontend hid the controls to match**~~ — `BoardShow.vue` gained an
  `isUpcoming` computed (same pattern as its own `isEnded`, whose comment
  already documented this exact class of bug happening once before, for
  "ended" instead of "upcoming" — the dice/tick-tile controls only ever
  checked `isPaused` then too). `Bingo.vue`'s `onSquareClick` gained the
  matching early return. **Joining stays available while upcoming** — that's
  signing up ahead of the start, which `EventParticipationService::join()`
  already allows (only checks `isPaused`) and the header's own join button
  never restricted; only the dice and the tick-tile button, which are
  actually playing, now wait for the start date. Verified at
  `BoardShow.vue`, `Events/Bingo.vue`, and traced `Events/SkillRace.vue`
  (no play action there — entering isn't gated either, same reasoning).

Full suite still green (740 backend / 174 frontend).

## Bingo never got a seeder, so no bingo card was ever fully task-filled — 2026-08-29

Picks up the earlier "task variety" pass (14 tasks → 66, every NORMAL S&L
tile filled) — asked again explicitly for bingo, which that pass never
touched.

- [x] ~~**No `BINGO` event existed in any seeder at all.**~~ `DatabaseSeeder`'s
  own board and every `boardSpecs()` entry are `SNAKES_LADDERS`; the six
  bingo events already sitting in this dev database (Clan Bingo — Summer,
  Weekend Bingo, Christmas Event, the Midsummer Championship, Ended last
  week, Invite only night) were each created by hand through the app, which
  is exactly why none had more than 14 of 25 squares filled — nothing ever
  filled one in but a host clicking through the UI. New `bingoSpecs()` /
  `seedBingoCard()` / `seedBingoSquares()`, mirroring `boardSpecs()`'s
  shape: three cards (solo/open, team/full-house, solo/invite) rather than
  boards' nine — bingo's own state variety was already covered by what
  existed by hand, backfilled below regardless.
- [x] ~~**New `backfillBingoSquareTasks()`, site-wide, not scoped to the
  three specs above.**~~ Deliberate: "every seeded S&L and bingo board"
  meant fixing what was already in the database from manual testing too,
  not only what this seeder creates going forward. One wildcard per card,
  shuffled-and-cycled tasks on the rest — same reasoning as
  `backfillTileTasks()`. Verified after running: all 9 bingo cards site-wide
  (6 pre-existing + 3 new), 0 squares without a task or a wildcard reason —
  up from 14/25, 1/25, and four cards at 0/25 before.

Full suite still green (740 backend / 174 frontend).

## The S&L side of that same pass was scoped too narrowly — reported live from a still-empty board — 2026-08-29

`backfillTileTasks(Board $board)` only ever got called from inside
`seedBoard()`, which only runs for `boardSpecs()`'s own nine titles — so a
board created by hand through the app, same as the bingo cards the previous
entry backfilled, was never reachable. Caught immediately: the owner opened
"Starts next month" right after the reseed and it was still bare.

- [x] ~~**`backfillTileTasks()` rewritten as `backfillAllTileTasks()`, no
  `Board` param, site-wide.**~~ Exactly the same fix `backfillBingoSquareTasks()`
  already got a few commits earlier, applied to the surface it was
  supposed to cover the first time. Dropped the per-board call from
  `seedBoard()` entirely — the site-wide sweep runs once in `run()` and
  catches boardSpecs()' own boards too, so nothing is lost by removing it
  from there. Re-ran: 178 empty NORMAL tiles backfilled in one pass — the
  exact count flagged as "not this seeder's boards" three commits ago, now
  covered. "Starts next month" verified directly: 25/25 NORMAL tiles filled.

Full suite still green (740 backend / 174 frontend).

## "Your current task" was below "Roll the dice" whose own copy said "above" — and the wiki link/description vanished seconds after page load — 2026-08-29

Two bugs off one screenshot: a card order that contradicted its own text,
and a live-channel data gap that undid the wiki-link work from days earlier
almost as soon as the page rendered it correctly.

- [x] ~~**Card order swapped in `BoardShow.vue`'s sidebar: "Your current
  task" now precedes "Roll the dice."**~~ `board.roll_needs_current_tile`
  reads "Finish the tile you are on first, then roll. Mark it complete
  **above**" — true only if the task card is actually above the roll
  card, and it wasn't; it was the next one down. Reordered rather than
  reworded: the task is what a player acts on next, the dice are the
  reward for finishing it, so "things to do are always on top" was the
  right call and "above" was the copy that should have been true all
  along. Verified at 375px: task card (with the "Mark as complete"
  button) now renders first, "Roll the dice" and its now-accurate "above"
  second.
- [x] ~~**The wiki link (and the description) on the current/selected task
  disappeared moments after page load — a real data gap, not a display
  bug.**~~ Asked directly: "did you not seed that along, or is the code
  missing." Neither — the earlier wiki-link pass fixed
  `BoardController::show()`'s initial props (`'board.tiles.task'`, no
  column restriction) but missed the **live SSE channel** that overwrites
  `liveTiles` on (near-)every connect: `SnakesLaddersChannel::payload()`
  still had `->with('task:id,title,icon_url')` — no `description`, no
  `wiki_url` — so the correct initial render got clobbered by a narrower
  one within seconds of the page opening, same as `BingoChannel::payload()`'s
  `squares.task:id,title,icon_url`, which had the identical gap and was
  fixed alongside it even though nobody had reported it yet for bingo.
  Both channels select `description,wiki_url` now. Verified live: clicked
  a tile after the page had settled (channel definitely connected), wiki
  link and description both present in the DOM, not just in a
  screenshot-free initial fetch.

Full suite still green (740 backend / 174 frontend).

## The wiki link was an icon tucked beside a title; S&L tiles had no claim/approve flow — 2026-08-29

Two separate asks in one message, both acted on directly.

- [x] ~~**Wiki link: a proper button, not a small icon beside the title.**~~
  "Im not that satisfied with just a link button icon that goes to the wiki.
  Needs a better button maybe. Maybe in the header, aligning to the right?"
  Moved from a bare `<u-icon>` wrapped in an `<a>` next to the task title
  into a real `<u-button>` in the card's `#header` slot, right-aligned via
  `justify-between` — `BoardShow.vue`'s "Your current task" and "Selected
  tile" cards, and `BingoClaimModal.vue`'s task header, all three carried
  the identical anti-pattern.

- [x] ~~**Snakes & Ladders task tiles had no claim/approve flow — the open
  question from 2026-08-25 above, now decided and built.**~~ "I can
  currently simply complete a tile without providing proof. This is an
  issue touching an existing backlog that stuff needs approval guard."
  Answered the "extend `completed_tiles` or accept the asymmetry" question
  from the writeup above: extended it, mirroring `bingo_completions`
  column-for-column (`status`, `proof_url`, `note`, `marked_by`,
  `reviewed_by`, `reviewed_at`, `review_note` — migration
  `2026_08_29_120000_add_review_to_boards_and_completed_tiles`) and adding
  the matching `boards.requires_approval` next to `bingo_cards.requires_approval`.
  Both default `true` — a board nobody configures should not be the
  self-trusting one.
  * `PlayerBoardController::toggleTile()` is now a claim endpoint, not a
    plain self-toggle: on a board that requires approval, a first claim
    lands PENDING (optional `proof_url`/`note`, same as bingo — not
    required server-side, same reasoning bingo's own claim() already
    settled), and only APPROVED counts toward `completedTileIds`/`canRoll`.
    Withdrawing your own still-pending claim is allowed; touching one a
    host already ruled on is refused (`board.already_reviewed`), same
    restriction bingo enforces.
  * New `PlayerBoardController::review()` (`PATCH
    /events/{event}/tiles/completions/{completedTile}`), host-only,
    approve/reject with a note — same shape as `BingoController::review()`.
  * New `BoardReviewService` mirrors `BingoService`'s two review-facing
    methods: `pendingQueue()` for the host's dialog, `claimsVersion()` — a
    cheap hash folded into `SnakesLaddersChannel`'s fingerprint/payload so a
    host approving a claim while the claimant still has the page open
    reaches them live, the same `claims_version` → `router.reload({only:
    [...]})` pattern `Bingo.vue` already uses. Getting this wrong was the
    exact bug fixed above the same day (a live channel silently overwriting
    correct props with a narrower shape) — worth naming directly since it
    is the same class of mistake this new code could have repeated.
  * Two new Vue components, `TileClaimModal.vue`/`TileReviewModal.vue`,
    close mirrors of `BingoClaimModal.vue`/`BingoReviewModal.vue` — the
    "table question" research deliberately kept the two schemas separate,
    and that held here too rather than trying to genericize one claim
    dialog over both shapes.
  * `BoardShow.vue`'s "Your current task" card now branches on
    `requiresApproval`: claim/pending/approved/rejected states behind the
    new modal when on, the original direct complete/uncomplete button when
    off — verified both paths live, including flipping the setting on an
    existing board and confirming the old plain-toggle button reappears
    unchanged.
  * `BoardSettings/FormatFields.vue` gained the same `u-switch` bingo's
    Format tab already has, now shown for both `isBingo` and `hasBoard`
    (was bingo-only) — the create/edit payload-scoping logic in
    `BoardSettingsModal.vue` had `requires_approval` deleted for every
    non-bingo type, so it had to move out of that guard alongside the
    board-only fields.
  Verified live end to end: claimed a tile with a proof link → "Waiting for
  review" state, Manage menu badge, dice card explaining why rolling is
  blocked → reviewed and approved from the host dialog → claim state, board
  tint and dice availability all updated without a manual reload → confirmed
  requires_approval:false reverts to the exact original single-click
  complete/uncomplete behaviour. Full suite still green (740 backend / 174
  frontend); both bundles rebuilt, SSR restarted.

## Two real bugs found testing the claim/approve flow above — 2026-08-30

Both reported directly ("I can submit an empty claim now") or found while
verifying the fix, not filed and left.

- [x] ~~**A claim could be submitted with no proof at all.**~~ `proof_url`
  was `nullable` in both `PlayerBoardController::toggleTile()` and
  `BingoController::claim()` — copied straight from bingo's own precedent,
  which itself allowed it ("Optional, but a claim without proof is likely
  to be rejected"). Reported as an empty submission succeeding, and once
  named it was obviously wrong on both: a review queue exists so a host has
  something to check a claim against, and a blank one gives them nothing.
  Fixed on both controllers: `proof_url` is now `required` whenever the
  board/card requires approval, `nullable` when it doesn't (nothing to
  check it against there). Had to reorder both methods — the validation ran
  before the existing-claim check, so requiring proof up front would have
  rejected every *withdrawal* too, since a withdrawal is a bare POST with
  no body. Moved validation to after the withdraw branch in both. Frontend:
  both claim modals mark the field required and disable Submit until it's
  filled in, rather than only reporting the error after a round trip.
  Several existing Bingo tests posted bare claims relying on the old
  nullable behaviour and needed a `proof_url` added to stay meaningful
  (`BingoTest`, `BingoReviewTest`, `BingoClaimWithdrawalTest`, `EventPauseTest`).

- [x] ~~**A rejected S&L claim permanently bricked the board — found while
  verifying the fix above, not yet reported.**~~ Mirroring bingo's
  `already_reviewed` lock (any non-PENDING claim is the host's to change,
  not the claimant's) is wrong for this board type specifically: a bingo
  square is optional, so a stuck one still lets the card finish, but an S&L
  tile is the one the player is standing on — `canRoll` only unlocks once
  it's APPROVED, so a REJECTED claim with no way back meant that player
  could never roll again, on that board, ever. Changed
  `PlayerBoardController::toggleTile()`'s lock to only APPROVED (a host's
  positive ruling stands); a REJECTED claim can be cleared by the player and
  resubmitted with better proof, same endpoint. `TileClaimModal.vue` now
  shows a "Try again" action instead of "Withdraw" for a rejected claim,
  left open on success (rather than closed) so the same dialog turns
  straight into the fresh submission form — trying again is one continuous
  flow, not a close-then-reopen. Deliberately NOT changed on bingo's side:
  the asymmetry is the point, not an oversight (see the note in the code).
  Verified live: rejected a fresh claim, confirmed "Mark as complete" was
  gone and the tile permanently blocked before the fix; after it, "Try
  again" clears the rejection, the form reopens in place, a fresh claim
  with new proof lands PENDING, and approving it unblocks rolling again.
  Also re-verified bingo's own claim flow end to end as a real SOLO
  participant (join → claim → pending) since the validation reorder touched
  that controller too.

Two new PHPUnit tests for the rejection fix, four existing Bingo tests
updated for the proof requirement. Full suite green (745 backend / 174
frontend); both bundles rebuilt, SSR restarted.

- [x] ~~**A RuneLite "soon" teaser on both claim forms.**~~ Asked directly,
  offered as one of two options ("or improve labels") — picked the teaser.
  A disabled, greyed-out block below the note field in both
  `TileClaimModal.vue` and `BingoClaimModal.vue`: a puzzle icon, "RuneLite
  plugin" plus a "Soon" badge, and a disabled input reading "Detected
  automatically — no screenshot needed". Previews Phase 4's plan
  (`docs/runelite-plugin.md`, `ROADMAP.md`) — `completed_via` has carried a
  `RUNELITE` case in the schema since the very first migration with nowhere
  in the UI that ever said so. Deliberately inert (`disabled`,
  `pointer-events-none`, no click handler at all): nothing here should look
  clickable before the plugin exists to answer it.
