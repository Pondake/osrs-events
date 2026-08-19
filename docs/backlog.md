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
- [ ] **`text-primary` on light backgrounds** — same root cause, different
  surface, still open. Amber-500 as *text* on a light background is also
  2.15:1, and `text-primary` is used in ~7 spots (`AppFooter` link hover,
  `DiceRoller`'s roll number, `About`'s icon, leaderboard rank numbers in
  `Leaderboard.vue` and `BoardShow.vue`), plus the 57 non-solid button
  usages (`soft`/`ghost`/`link`/`subtle`) which all render `text-{color}`.
  No clean fix without a trade-off, because `--ui-primary` drives both fill
  and text: bumping light mode to amber-700 makes text pass at 5.05:1 but
  browns every soft/outline button too. The real answer is splitting brand
  *fill* from brand *text* into two tokens. Needs a design call — don't
  silently change it.
- [ ] **Wordmark font** — logo 5a's README specifies the in-game RuneScape
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
- [ ] **First-run onboarding modal** — a guided step-by-step flow for
  setting up your first board, shown to new users (or reachable from a
  persistent "help me get started" entry point). Two-column layout: a form
  driving the current step on one side, a **live graphical preview** on the
  other that updates as fields are filled in — e.g. a board mock-up that
  visually assembles itself as size/mode/theme fields are set. Evaluate
  Lottie for the animated piece vs. something driven directly off form
  state (SVG/Canvas reacting to input) — the latter is probably more
  honest for "shows what you're actually building" rather than a canned
  animation, but worth comparing effort/fidelity before committing.
- [ ] Onboarding steps should cover, in order:
  1. **Auth** — Discord login (already exists), plus a path with **no
     Discord account required** (email/password or similar). This is a
     bigger decision than just an onboarding-flow step: today Discord OAuth
     is the *only* login path (see the existing open question in **Admin &
     users** above, "whether that stays the sole auth path"). Resolve that
     decision first; the onboarding flow just needs to expose whichever
     paths end up existing.
  2. **Roles & permissions explainer** — surface what role/permission the
     new user has and what it unlocks. Note: role/permission enforcement
     itself is **already implemented and working** (`Role`, `UserRole`,
     `UserPermission` models; `User::hasRole()`/`hasPermission()`/
     `isAdmin()`; gates like `canCreateBoards`/`canCreateTiles` throughout
     the admin controllers — see `app/Models/User.php`). It's a hand-rolled
     system, though, **not** `spatie/laravel-permission` — the "moet via
     spatie/roles" ask needs a decision: keep the current homegrown tables
     (they work, they're simple, no new dependency) or migrate to
     `spatie/laravel-permission` for its policy/gate integration and
     Filament compatibility (relevant if the Filament CMS prototype above
     ever ships — it already assumes spatie). Don't silently swap the
     underlying system as a side effect of building the onboarding UI;
     decide this explicitly first, it's a real migration either way.
  3. **First board creation** — the actual point of the flow, feeding the
     live preview described above.
  4. **RuneLite plugin teaser** — a small screen with mock screenshots
     showing how a user would install/connect the plugin. `docs/runelite-plugin.md`
     has the feasibility research; **the plugin itself does not exist yet**
     (that doc is proof-of-concept notes from a different integration, not
     a shipped osrs-events plugin). Build the teaser screen anyway with
     placeholder/mocked screenshots — it's marketing for a real, scoped,
     "days not months" roadmap item per that doc's effort estimate, not
     vaporware, but be explicit in the UI copy that it's "coming soon"
     rather than implying it's installable today.

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
