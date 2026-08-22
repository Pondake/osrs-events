# Backlog

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
- [ ] **Retire the global `TEAM_MANAGER` role.** It still grants management
  over every team, purely so nobody holding it loses access on deploy — but
  the per-team roles above now cover what it was created for. Either drop
  the check in `Team::isManagedBy()` and unassign the role, or keep it and
  rename it to something that admits what it is (site-wide team staff).
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

- [ ] **Scope the task library the way teams are now scoped.** `/tasks/search`
  returns every Task on the site to anyone who can edit a board. Today that is
  fourteen seeded rows so nothing is exposed — but the wiki picker creates a
  Task per page anybody imports, so the library grows into a record of what
  every clan is running. It should be filtered the same way teams are: tasks
  you created, plus tasks created by people you share a Discord server with,
  plus the seeded ones. Needs a `created_by` on `tasks` (wiki imports would
  set it) and the same visibility scope. If that turns out to be more than it
  is worth, the fallback is simpler and also fine: drop the local library
  from the picker and let the wiki be the only source.


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
- [ ] **Say WHY an admin can see a team.** Team visibility is now scoped, but
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
- [ ] **Accept `7d` / `1w` / `1m` in the default event length.** It is a
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

- [ ] **Clicking a claimed square withdraws it, with no warning.** No hover
  state says so and nothing confirms it, so an accidental second click
  quietly undoes a claim. Should open the claim in a dialog with a delete
  button, matching how everything else destructive works here.
  **Done 2026-08-22** — see the claim dialog below.
- [x] **Review notes on an APPROVED claim go nowhere.** The field is offered
  whatever the verdict, and only a rejection shows it. Either surface it on
  the approved square too, or stop asking for it when approving.
  **Done 2026-08-22** — shown whatever the verdict.
- [ ] **Accept `7d` / `1w` / `1m` in the default event length**, with a month
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

- [ ] **Privacy policy needs an update.** `/privacy` was written for the
  Discord-only version of the app and no longer describes what is collected.
  Since then: email/password accounts (email address, hashed password,
  password-reset tokens), an audit log recording admin actions against named
  users and retaining their names after deletion, site settings, and invite
  records naming who created and who accepted them. The audit log is the one
  most worth being explicit about — it deliberately keeps a deleted user's
  display name, which is exactly the kind of retention a privacy policy has
  to state rather than imply.
  Check `/terms` at the same time; it has had no review at all on this stack.

  **Re-flagged 2026-08-22 at your request: both pages need a fresh read-
  through before launch, not a patch.** Since the text above was written the
  app has also gained an admin section that edits other people's events, a
  team-to-Discord-server link, and sessions that end on a password change —
  all of which are things a privacy policy is expected to describe.
