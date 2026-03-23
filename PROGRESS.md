# ⚔️ OSRS Events — Progress Tracker

## Phase 1 — Core Features

### ✅ Completed

## Phase 2 — Teams & Permissions

### ✅ Completed

#### Foundation
- [x] **GraphQL codegen** (frontend only — backend is code-first and is already the source of truth)
  - [x] `codegen.ts` config, `"generate"` script in `package.json`, `app/types/graphql.ts` generated from `backend/schema.gql`
- [x] **Composables for GraphQL endpoints & state management**
  - [x] `useBoards.ts`, `useTasks.ts`, `useTiles.ts`, `usePlayers.ts`, `useUsers.ts`, `useTeams.ts`
- [x] **Centralised board utilities** (`app/utils/board.ts`)
  - [x] `BOARD_SIZE_LABEL`, `BOARD_TILE_COUNT`, `BOARD_MIN_WIDTH` — replaces inline `SIZE_DISPLAY` Records scattered across files
  - [x] `formatBoardSize()`, `formatDate()` helpers — shared across all pages/components
- [x] **Composable-first page refactor** — all pages now import composables rather than inlining GraphQL
  - [x] `pages/boards/index.vue` — uses `useBoards()`
  - [x] `pages/boards/[id]/index.vue` — uses `useBoard()`, `usePlayerBoard()`, `useBoardPlayerStates()`, `usePermissions()`
  - [x] `pages/boards/[id]/leaderboard.vue` — uses `useLeaderboard()` (SSR, replaces `onMounted` pattern)
  - [x] `pages/admin/boards/index.vue` — uses `useBoards()`
  - [x] `pages/admin/tasks/index.vue` — uses `useTasks()`
  - [x] `pages/admin/users/index.vue` — uses `useUsers()`
  - [x] All local `interface` declarations replaced with generated types from `~/types/graphql`
- [x] **Nav menu hierarchy** — grouped nav with dropdown support
  - [x] Boards: dropdown (All Boards + Manage Boards) for admin/editor; direct link otherwise
  - [x] Teams: direct link (TEAM_MANAGER / ADMIN only)
  - [x] Tasks: direct link (ADMIN / EDITOR only)
  - [x] Admin: dropdown → Users (ADMIN only)
- [x] **Seed rewrite** — richer dev data for all features
  - [x] 8 fake users: 5 regular players + `BoardBuilder` (EDITOR), `TeamCaptain` (TEAM_MANAGER), `ClanLeader` (EDITOR + TEAM_MANAGER + `canCreateBoards` permission)
  - [x] 3 teams: Dragon Slayers, Iron Maidens, Barrows Brothers
  - [x] 4 boards distributed across teams with realistic tile counts and player states

#### Leaderboard
- [x] **Leaderboard per board/event**
  - [x] Tile-position-based ranking — `boardLeaderboard` query on backend
  - [x] Default view: top 5 in sidebar panel (`Board/Leaderboard.vue`); full leaderboard at `/boards/[id]/leaderboard`
  - [x] Columns: rank, avatar, name, current tile, tiles remaining
  - [x] "Tiles remaining" coloured 🟢 green (ladder ahead) / 🔴 red (snake ahead)
  - [x] Leaderboard refreshes after each dice roll
  - [x] Fixed Nuxt routing conflict: `pages/boards/[id].vue` → `pages/boards/[id]/index.vue`

#### Teams
- [x] **Teams system — backend**
  - [x] `Team` + `TeamMember` Prisma models, `TeamsModule`, `TeamsService`, `TeamsResolver`
  - [x] Mutations: `createTeam`, `updateTeam`, `deleteTeam`, `addTeamMember`, `removeTeamMember`
  - [x] Queries: `teams` (admin), `team`, `myTeams`
- [x] **Teams system — frontend**
  - [x] `useTeams.ts` composable (`useMyTeams`, `useAllTeams`)
  - [x] `Team/Form.vue` — name input + OSRS Wiki icon picker (icon-only, does not auto-fill name)
  - [x] `pages/teams/index.vue` — list, create, edit, delete teams; add/remove members; permission-gated actions
  - [x] Teams navigation link in header (visible for TEAM_MANAGER and ADMIN)

#### Roles & Permissions
- [x] **Backend permissions infrastructure**
  - [x] `UserPermission` Prisma model (`id, userId, permissionKey` — `@@unique([userId, permissionKey])`)
  - [x] `PermissionsModule`, `PermissionsService`, `PermissionsResolver`
  - [x] Queries: `myPermissions`, `userPermissions`; Mutations: `grantPermission`, `revokePermission`
- [x] `BoardAuthor.isOwner` boolean — creator can never be removed
- [x] `usePermissions()` composable — centralises all role + permission checks; `canEditBoard()`, `canManageTeam()`, `canCreateBoards`, `canCreateTiles`
- [x] **Admin user management view** (`pages/admin/users/index.vue`) — searchable table (cols: User, Joined, Roles, Actions); separate modals for editing roles (per-role toggles, ADMIN protected) and permissions (lazy-loaded per user, per-key toggles)
- [x] **EDITOR per-board assignment** — `isOwner` field on `BoardAuthorEntity`; `create()` sets creator as owner; `update()` preserves owners; `addBoardAuthor` / `removeBoardAuthor` mutations; `updateBoard` open to EDITOR role with ownership check; edit board page at `/admin/boards/[id]/edit` with inline author add/remove

#### Board TEAM mode
- [x] `BoardMode` enum (`SOLO` | `TEAM`) on `Board` model
- [x] `BoardTeam` pivot table (`id, boardId, teamId`) — links a team to a TEAM-mode board
- [x] `addTeamToBoard` / `removeTeamFromBoard` mutations (ADMIN / board-owner EDITOR)
- [x] `BoardTeamEntity` + `BoardTeamTeamSummary` GraphQL entities; `PlayerBoard` TEAM mode: shared board state per team
- [x] Prisma migration `add-teams-permissions-isowner` — creates `UserPermission`, `Team`, `TeamMember` tables and `BoardAuthor.isOwner` column
- [x] Prisma `output` path fix in `schema.prisma` → `generator.output = "../src/generated/prisma"` (was writing to pnpm store)

#### Code quality & UX (polish pass)
- [x] **Props cleanup** — all `interface Props { ... }` declarations removed across components; always inline `defineProps<{...}>()`
- [x] **`Task` → `TaskEntity`** type fix in `Tile/EditModal.vue` (was `Task` which was undefined; now correctly imports `TaskEntity` from `~/types/graphql`)
- [x] **Admin users page** — replaced `u-toggle` → `u-switch`; replaced raw `<table>` HTML with `<u-table :data :columns>` and named cell slots (Nuxt UI v3 table API)
- [x] **Sidebar dice section** — hidden until current tile is completed (`currentTileCompleted`); removed "complete first" blocking alert (section simply absent until rollable)
- [x] **Sidebar section animations** — `<transition name="sidebar-section">` with CSS fade + slide-up on dice, current tile, and clicked tile cards
- [x] **Clicked tile dismiss** — `×` button on the clicked-tile info card emits `clear-tile`; board page clears `clickedTile`
- [x] **Board page skeleton** — replaced generic `h-96` skeleton with a full `7×7` `board-grid board-grid-7` skeleton (49 `<u-skeleton>` tiles) + sidebar skeleton blocks; condition covers SSR idle state (`pending || (!board && !error)`)
- [x] **Board content fade-in** — `<Transition name="board-fade" appear>` on the game board + sidebar flex container; 0.3s ease opacity + translateY
- [x] **`useGql` SSR fix** — switched from plain `$fetch` + `ref()` to `useAsyncData` with `lazy: true`; SSR data is embedded in the Nuxt HTML payload and restored on the client (no re-fetch, `pending = false` immediately on hard reload); `lazy: true` prevents Suspense blocking on client-side navigation so skeleton shows instantly instead of an empty page

### 🔄 Pending

> Nothing remaining in Phase 2.

> 💡 **Deferred to Phase 3**: Team shareable invite links — single-use link a TEAM_MANAGER generates; one player accepts it to join the team.

> See [ROADMAP.md](./ROADMAP.md) for Phase 3 onwards.

---

## Database Schema (Prisma v7)

### Enums
- `BoardSize`: SIZE_5X5 | SIZE_7X7 | SIZE_9X9
- `TileType`: NORMAL | SNAKE | LADDER
- `CompletionSource`: MANUAL | RUNELITE
- `BoardMode`: SOLO | TEAM

### Models
- **Role**: id, name (unique), description, createdAt, updatedAt
- **User**: id, discordId (unique), discordUsername, nickname?, avatarUrl?, createdAt, updatedAt
- **UserRole** (pivot): id, userId, roleId ← `@@unique([userId, roleId])`
- **Task**: id, title, iconUrl?, description?, createdAt, updatedAt
- **Board**: id, title, startDate?, endDate?, size (BoardSize), mode (BoardMode), diceRollLimit?, createdAt, updatedAt
- **BoardAuthor** (pivot): id, boardId, userId, isOwner (bool) ← `@@unique([boardId, userId])`
- **BoardTeam** (pivot): id, boardId, teamId ← `@@unique([boardId, teamId])` — links a team to a TEAM-mode board
- **Tile**: id, boardId, position, taskId?, titleOverride?, type (TileType), targetPosition? ← `@@unique([boardId, position])`
- **PlayerBoard**: id, userId, boardId, currentPosition, diceRollsToday, lastRollDate? ← `@@unique([userId, boardId])`
- **CompletedTile**: id, playerBoardId, tileId, completedAt, completedVia ← `@@unique([playerBoardId, tileId])`

### Phase 2 additions
- **UserPermission**: id, userId, permissionKey ← `@@unique([userId, permissionKey])` — granular per-user permissions independent of roles
- **Team**: id, name, iconUrl?, createdAt, updatedAt
- **TeamMember** (pivot): id, teamId, userId ← `@@unique([teamId, userId])`

---

## Infrastructure Notes
- Project renamed from `osrs-snakes` → `osrs-events` (working directory is now `i:/osrs-events`)
- `dotenv` added as a devDependency — required by `prisma.config.ts` for `prisma generate` to work
- After any rename or fresh clone, run `pnpm prisma generate` in `/backend` before starting

## Notes
- Board position order: row 0 at bottom-left, even rows left→right, odd rows right→left (snaking)
- SVG overlay uses quadratic bezier curves with arrowheads for snake/ladder connections
- OSRS Wiki search uses MediaWiki API (opensearch endpoint) for task icon lookup
- Board size enum values: `SIZE_5X5`, `SIZE_7X7`, `SIZE_9X9` (displayed as `5×5`, `7×7`, `9×9`)
- Prisma v7: datasource URL via `prisma.config.ts`, seed command also in `prisma.config.ts`
- Prisma v7: runtime client uses `PrismaPg` driver adapter (`@prisma/adapter-pg`)
- `tsconfig.seed.json` exists to override module to CommonJS for ts-node seed execution
- Auth flash fix: `hydrated` state in auth store; `USkeleton` shown until `auth.client.ts` completes
