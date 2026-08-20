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
  the existing `/dev-login` route (real gameplay, not just reading the
  code): correct die face, toast, and the connector lines redrawing around
  the new position after a snake hit.
- [ ] `stale/` can be deleted once the migration is verified complete and the
  team is confident nothing needs porting from it anymore.

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
- [ ] **Decide: keep the homegrown roles/permissions, or move to
  `spatie/laravel-permission`?** Still open — it was raised alongside the
  onboarding work ("moet via spatie/roles") but is a standalone call, so
  it's tracked here rather than buried under a finished onboarding step.
  Current state: hand-rolled and working — `Role`/`UserRole`/
  `UserPermission` models, `User::hasRole()`/`hasPermission()`/`isAdmin()`,
  and `canCreateBoards`/`canCreateTiles` gates across the controllers.
  For spatie: real Gate/Policy integration (`@can`, `authorize()`) instead
  of hand-written `abort_unless` everywhere, caching, and Filament assumes
  it if that CMS prototype ever happens.
  Against: it's a working system with no current pain, the migration
  touches every authorization call site, and the tables would need
  converting (spatie keys on a `model_has_roles` morph, not our
  `user_roles`). Not a side-effect refactor — decide it deliberately.
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
  4. **Editor UI needs a visual pass** — flagged by the owner 2026-08-20:
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

  **Not done / known gaps:**
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
`/dev-login` correctly gated on `app()->environment('local')`.

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

## Content review before launch (step 8)

Flagged 2026-08-20 by the owner: do this **after the build work is done**,
not alongside it — the copy depends on what the app actually ends up doing.

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
