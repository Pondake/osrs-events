# 🗺️ OSRS Events — Roadmap

> Phase planning for future development. Active phase is tracked in [PROGRESS.md](./PROGRESS.md).

---


## Phase 3 — Board Access Control & Invites

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

- [ ] Add `BoardAccessMode` enum: `OPEN` | `GUILD` | `INVITE`
- [ ] Add `accessMode` (BoardAccessMode, default `OPEN`) and `requiredGuildId?` to `Board`
- [ ] Add `UserGuild` model: `id, userId, guildId, guildName, guildIcon?, syncedAt`
- [ ] Add `BoardInvite` model: `id, boardId, token (uuid), shortCode (6-char unique), createdBy, expiresAt?, maxUses?, useCount, createdAt`
- [ ] Add `BoardAccess` model: `id, boardId, userId, inviteId?, joinedAt`
- [ ] Run Prisma migration

### Backend

- [ ] Add `guilds` scope to Discord OAuth; sync returned guilds into `UserGuild` on every login
- [ ] Expose `accessMode` + `requiredGuildId` on `BoardEntity` / GraphQL schema
- [ ] Update `CreateBoardInput` / `UpdateBoardInput` with `accessMode` + `requiredGuildId`
- [ ] `BoardsService`: enforce access mode check in `joinBoard` / `getOrCreatePlayerBoard`
- [ ] "Can join" helper: `OPEN` — pass; `GUILD` — DB join against `UserGuild`; `INVITE` — check `BoardAccess`
- [ ] `BoardInviteService` + `BoardInviteResolver`:
  - [ ] `createInvite(boardId, options)` — generates UUID token + 6-char shortCode
  - [ ] `useInvite(boardId, tokenOrCode)` — validates + creates `BoardAccess`, respects expiry + maxUses
  - [ ] `revokeInvite(inviteId)`
  - [ ] `getInvitesByBoard(boardId)` — admin/owner only
- [ ] Run codegen + update `~/types/graphql`

### Frontend

- [ ] Board create/edit form: access mode selector (radio/segmented control)
  - [ ] `GUILD` selected → searchable dropdown of creator's Discord servers (from `UserGuild`)
  - [ ] `INVITE` selected → "Generate invite" button appears
- [ ] Admin invite management panel (on board edit page):
  - [ ] List active invites with token/shortCode, use count, expiry
  - [ ] Copy magic link button (`/boards/[id]/join/[token]`)
  - [ ] Revoke button per invite
- [ ] Board list: per-board access badge
  - [ ] No badge — `OPEN`
  - [ ] 🔒 + server name — `GUILD` (green if user is in that server, grey/locked if not)
  - [ ] 🔑 Invite only — `INVITE`
- [ ] Board detail join flow:
  - [ ] `OPEN`: "Join board" button (current behaviour)
  - [ ] `GUILD`: button if `UserGuild` contains `requiredGuildId`; else "You must be in [Server] to join"
  - [ ] `INVITE`: "Enter invite code" input + submit; magic link auto-fills + submits
- [ ] Magic link page: `/boards/[id]/join/[token]` — validate invite → join board → redirect to board

### Discord group-based teams

Teams are currently global — any TEAM_MANAGER can see and manage all teams. In Phase 3, teams become scoped to Discord guilds (servers) using the `UserGuild` data already synced at login.

**Model changes**
- [ ] Add `guildId?` to `Team` — ties a team to a specific Discord server
- [ ] Add `guildName?` to `Team` — cached for display without live Discord calls
- [ ] Run Prisma migration

**Backend**
- [ ] `TeamsService.findAll()` — when called by a TEAM_MANAGER (non-admin), filter to teams whose `guildId` is in the user's `UserGuild` list
- [ ] `TeamsService.create()` — auto-set `guildId`/`guildName` from the creator's primary guild (or let them pick)
- [ ] Member search for TEAM_MANAGERs — filter `users` query to only show users who share a guild with the manager (`UserGuild` join)
- [ ] Admins always see and manage all teams regardless of guild

**Frontend — `/teams`**
- [ ] TEAM_MANAGER view: show only teams in their guild(s) (backend-filtered)
- [ ] Show guild name as a group header when a manager belongs to multiple guilds
- [ ] Admin view: still shows all teams

**Frontend — `/admin/teams`**
- [ ] Group teams by `guildName` with collapsible sections
- [ ] Teams without a guild grouped under "No guild" fallback section
- [ ] Show guild icon (from `UserGuild`) next to each group header

### Deferred from Phase 2

- [ ] **Team shareable invite link** — single-use link a TEAM_MANAGER generates; one player accepts it to join the team (analogous to board invites but scoped to teams)

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

- [ ] Full rebrand: "OSRS Snakes & Ladders" → **"OSRS Events"** (logo, SEO, copy)
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

## Phase 6 — More Event Types

> To be decided. Brainstorm options with the community and team.

Potential ideas:
- [ ] **Speed-run board**: first to reach the finish wins, no daily limit
- [ ] **OSRS Achievements race**: custom achievement unlock races
- [ ] **Skill-based challenges**: track XP drops or level milestones
- [ ] **Drop log events**: log rare drops, track via RuneLite webhook
