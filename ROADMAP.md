# 🗺️ OSRS Events — Roadmap

> Phase planning for future development. Active phase is tracked in [PROGRESS.md](./PROGRESS.md).

---


## ✅ Phase 3 — Board Access Control & Invites (Access control complete; guild-grouping polish deferred)

> Goal: prevent players from freely joining any board. Access is controlled per board via Discord server membership and/or invite links/codes.

### Access model overview

Each board gets an `accessMode` field:
- `OPEN` — anyone logged in can join (current behaviour, kept as default)
- `GUILD` — player must be a member of a specific Discord server
- `INVITE` — player must use a magic link or invite code

`BoardAccess` acts as the authoritative "this player is allowed" record for GUILD and INVITE modes once they've passed the check at join time. The same user can't claim the same invite link twice.

Discord guild data is DB-cached (`UserGuild`) — refreshed on every login. "Can join" checks are a simple DB join, no live Discord API calls at render time. Privacy note: guild IDs + names only.

---

### Schema

- [x] Add `BoardAccessMode` enum: `OPEN` | `GUILD` | `INVITE`
- [x] Add `accessMode` (BoardAccessMode, default `OPEN`) and `requiredGuildId?` to `Board`
- [x] Add `UserGuild` model: `id, userId, guildId, guildName, guildIcon?, syncedAt`
- [x] Add `BoardInvite` model: `id, boardId, token (uuid), shortCode (6-char unique), createdBy, expiresAt?, maxUses?, useCount, createdAt`
- [x] Add `BoardAccess` model: `id, boardId, userId, inviteId?, joinedAt`
- [x] Run Prisma migration (`20260430115427_phase3_access_control`)

### Backend

- [x] Add `guilds` scope to Discord OAuth; sync returned guilds into `UserGuild` on every login
- [x] Expose `accessMode` + `requiredGuildId` on `BoardEntity` / GraphQL schema
- [x] Update `CreateBoardInput` / `UpdateBoardInput` with `accessMode` + `requiredGuildId`
- [x] `BoardsService`: enforce access mode check in `joinBoard` / `getOrCreatePlayerBoard`
- [x] "Can join" helper: `OPEN` — pass; `GUILD` — DB join against `UserGuild`; `INVITE` — check `BoardAccess`
- [x] `BoardInviteService` + `BoardInviteResolver`:
  - [x] `createInvite(boardId, options)` — generates UUID token + 6-char shortCode
  - [x] `useInvite(boardId, tokenOrCode)` — validates + creates `BoardAccess`, respects expiry + maxUses
  - [x] `revokeInvite(inviteId)`
  - [x] `getInvitesByBoard(boardId)` — admin/owner only
- [x] Run codegen + update `~/types/graphql`

### Frontend

- [x] Board create/edit form: access mode selector (radio/segmented control)
  - [x] `GUILD` selected → searchable dropdown of creator's Discord servers (from `UserGuild`)
  - [x] `INVITE` selected → "Generate invite" button appears
- [x] Admin invite management panel (on board edit page):
  - [x] List active invites with token/shortCode, use count, expiry
  - [x] Copy magic link button (`/boards/[id]/join/[token]`)
  - [x] Revoke button per invite
- [x] Board list: per-board access badge
  - [x] No badge — `OPEN`
  - [x] 🔒 + server name — `GUILD` (green if user is in that server, grey/locked if not)
  - [x] 🔑 Invite only — `INVITE`
- [x] Board detail join flow:
  - [x] `OPEN`: "Join board" button (current behaviour)
  - [x] `GUILD`: button if `UserGuild` contains `requiredGuildId`; else "You must be in [Server] to join"
  - [x] `INVITE`: "Enter invite code" input + submit; magic link auto-fills + submits
- [x] Magic link page: `/boards/[id]/join/[token]` — validate invite → join board → redirect to board

### Discord group-based teams

Teams are currently global — any TEAM_MANAGER can see and manage all teams. In Phase 3, teams become scoped to Discord guilds (servers) using the `UserGuild` data already synced at login.

**Model changes**
- [x] Add `guildId?` to `Team` — ties a team to a specific Discord server
- [x] Add `guildName?` to `Team` — cached for display without live Discord calls
- [x] Run Prisma migration

**Backend**
- [x] `TeamsService.findAll()` — when called by a TEAM_MANAGER (non-admin), filter to teams whose `guildId` is in the user's `UserGuild` list
- [x] `TeamsService.create()` — `guildId`/`guildName` pass through create/update
- [ ] Member search for TEAM_MANAGERs — filter `users` query to only show users who share a guild with the manager (`UserGuild` join)
- [x] Admins always see and manage all teams regardless of guild

**Frontend — `/teams`**
- [x] TEAM_MANAGER view: show only teams in their guild(s) (backend-filtered)
- [ ] Show guild name as a group header when a manager belongs to multiple guilds
- [x] Admin view: still shows all teams

**Frontend — `/admin/teams`**
- [ ] Group teams by `guildName` with collapsible sections
- [ ] Teams without a guild grouped under "No guild" fallback section
- [ ] Show guild icon (from `UserGuild`) next to each group header

### Deferred from Phase 2

- [ ] **Team shareable invite link** — single-use link a TEAM_MANAGER generates; one player accepts it to join the team (analogous to board invites but scoped to teams)

> ⚠️ The six unchecked boxes above are the only Phase 3 items still open. They are
> all guild-grouping polish rather than access control, and are carried forward —
> see the "Deferred" note in [PROGRESS.md](./PROGRESS.md).

---

## Phase 4 — Polish & RuneLite Integration

- [ ] Pixel-art snake/ladder sprites overlaid on the board
- [ ] **RuneLite plugin webhook integration**
  - [ ] Webhook endpoint (`POST /webhook/runelite`)
  - [ ] Per-user auth token (shown in profile, regeneratable)
  - [ ] Auto-complete a tile when a matching RuneLite event is received
- [ ] Dark mode refinements (already available in Phase 1 via `UColorModeButton`)

---

## Phase 5 — Rebrand & Bingo Mode

> **Search context (Aug 2026).** Nearly all organic demand in this niche is for
> *bingo*, and it is a crowded field: aiobingo.com, slepeosrs.com,
> osrsbingohub.com, rune-bingo.com, thebirdhouse.games and osrs-tracker.com all
> compete for it, several with automated drop tracking. "OSRS Snakes & Ladders"
> has far lower volume but effectively no competition — which is why the SEO
> work targets it first. Bingo is worth building for product reasons, but do not
> expect it to rank without automated tracking and genuinely better guide content
> than aiobingo.com's `/osrs-bingo-guide`.

- [ ] Full rebrand: "OSRS Snakes & Ladders" → **"OSRS Events"** (logo, SEO, copy)
      — footer wordmark and SEO metadata already updated; logo assets outstanding
- [ ] Proper logo and branding assets
- [ ] When creating a board/event, choose the **event type**:
  - Snakes & Ladders (existing)
  - Bingo (new)
- [ ] **Bingo board** — full implementation:
  - [ ] N×N grid (configurable) with free tile
  - [ ] Mark tiles as complete
  - [ ] Detect completed lines (horizontal, vertical, diagonal)
  - [ ] Full-board bingo detection
  - [ ] Points system: line bingo = X pts, full bingo = Y pts

---

## Phase 6 — Content CMS & Layout Editor

> Goal: stop shipping a deploy every time a word on a marketing page changes.

The SEO landing pages (`/osrs-snakes-and-ladders`, `/osrs-clan-events`,
`/osrs-event-ideas`), the home page and the about/donate pages are currently
hardcoded Vue templates with their copy in `frontend/locales/en.json`. Every
copy tweak is a code change, a PR and a redeploy — which in practice means the
copy never gets tweaked, and SEO content that should be iterated monthly goes
stale instead.

### Content model

- [ ] `Page` model: `slug`, `title`, `metaTitle`, `metaDescription`, `ogImage?`,
      `status` (DRAFT | PUBLISHED), `publishedAt?`, `updatedAt`
- [ ] `PageBlock` model: `pageId`, `position`, `type`, `data` (JSON)
- [ ] Block types to cover what the current pages already do: `hero`, `prose`,
      `feature_grid`, `steps`, `faq`, `cta`, `card_links`
- [ ] Keep i18n intact — blocks store copy per locale, not raw strings

### Layout editor

- [ ] Admin route `/admin/pages` — list, create, duplicate, publish
- [ ] Block-based editor: add/remove/reorder blocks (drag and drop)
- [ ] Live preview pane rendering the real front-end components, not an approximation
- [ ] Draft vs published split, so edits are previewable before they go live
- [ ] Per-page SEO fields wired straight into `useSeo` (title, description, OG image, noindex)

### Front-end

- [ ] `[...slug].vue` catch-all that renders published `Page` records through a
      block renderer component
- [ ] Migrate the three landing pages + home + about + donate into the CMS
- [ ] Keep the sitemap in sync — published pages become a sitemap source
      alongside `/api/__sitemap__/boards`

### Why this matters

- [ ] SEO content needs iteration; ranking pages get rewritten, expanded and
      re-tested against search results, and that cannot need a developer
- [ ] Non-developers (clan admins, whoever runs the project later) should be
      able to fix a typo or add an FAQ entry
- [ ] Blocked on nothing — can start any time after Phase 4

---

## Phase 7 — More Event Types

> To be decided. Brainstorm options with the community and team.

Potential ideas:
- [ ] **Speed-run board**: first to reach the finish wins, no daily limit
- [ ] **OSRS Achievements race**: custom achievement unlock races
- [ ] **Skill-based challenges**: track XP drops or level milestones
- [ ] **Drop log events**: log rare drops, track via RuneLite webhook
