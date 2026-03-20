# ⚔️ OSRS Events — Progress Tracker

## Phase 1 — Core Features

### ✅ Completed

## Phase 2 — Teams & Permissions

### 🔄 In Progress

#### Foundation
- [ ] **GraphQL codegen** (frontend only — backend is code-first and is already the source of truth)
  - [ ] Generate TypeScript interfaces from the GraphQL schema for use in frontend composables and components
- [ ] **Composables for GraphQL endpoints & state management** — clean up components using generated TS interfaces; replaces ad-hoc type definitions scattered across components

#### Leaderboard
- [ ] **Leaderboard per board/event**
  - [ ] Tile-position-based ranking (no points yet — deferred to a later phase)
  - [ ] Shows player & team rankings side by side (or toggled)
  - [ ] Default view: top 5 players/teams; "Show more" button to expand full leaderboard (dedicated route `/boards/[id]/leaderboard`)
  - [ ] Columns: rank, name (avatar), current tile, tiles remaining to finish
  - [ ] "Tiles remaining" number is coloured: 🟢 green if a ladder is on the path ahead, 🔴 red if a snake is on the path ahead
  - [ ] Leaderboard lives in a sidebar panel on the board page

#### Teams
- [ ] **Teams system**
  - [ ] Team model: name + OSRS Wiki icon picker (same flow as task icon picker) — icon is decorative
  - [ ] Team icon/avatar shown on the board and in leaderboards
  - [ ] Teams and players can independently join multiple boards
  - [ ] Create, join, and manage teams
- [ ] **Team-based board view**: show team members' positions and progress
- [ ] **Team logic when creating & playing boards**: assign a board to a team (optional); both individual players and teams can participate across multiple boards
- [ ] Teams navigation link in header (visible for TEAM_MANAGER role)

#### Roles & Permissions
- [ ] **EDITOR** is a per-board assignment (not a global role): board owners and admins can assign editors to a board both at creation time and after the board is created
- [ ] Editors have full board capabilities (tile management, task assignment, task creation) scoped to their assigned boards only — role already seeded
- [ ] **TEAM_MANAGER** role: can manage team membership — role already seeded
- [ ] Granular permissions stored in a `UserPermission` table (`id, userId, permissionKey` — `@@unique([userId, permissionKey])`), independent of roles:
  - [ ] `canCreateBoards` — locked by default; admin grants this to allow a user to create their own boards
  - [ ] `canCreateTiles` — locked by default; required to create/edit tiles (stacks with board assignment for editors)
- [ ] `BoardAuthor` gains an `isOwner` boolean — distinguishes the original creator (permanent access, can never be removed) from assigned editors
- [ ] Introduce a `usePermissions()` composable — centralises all role + permission checks, exposes context-aware helpers like `canEditBoard(boardId)`, `canManageTeam(teamId)`, `canCreateBoards`, `canCreateTiles`, etc.
- [ ] **Admin user management view** — new admin page for managing users, roles, and permissions; table with search; per-user view lets admin assign roles and individual permissions

> 💡 **Deferred to Phase 3**: Team shareable invite links (single-use link a TEAM_MANAGER generates; one player can accept it to join the team)



> See [ROADMAP.md](./ROADMAP.md) for Phase 3 onwards.

---

## Database Schema (Prisma v7)

### Enums
- `BoardSize`: SIZE_5X5 | SIZE_7X7 | SIZE_9X9
- `TileType`: NORMAL | SNAKE | LADDER
- `CompletionSource`: MANUAL | RUNELITE

### Models
- **Role**: id, name (unique), description, createdAt, updatedAt
- **User**: id, discordId (unique), discordUsername, nickname?, avatarUrl?, createdAt, updatedAt
- **UserRole** (pivot): id, userId, roleId ← `@@unique([userId, roleId])`
- **Task**: id, title, iconUrl?, description?, createdAt, updatedAt
- **Board**: id, title, startDate?, endDate?, size (BoardSize), diceRollLimit?, createdAt, updatedAt
- **BoardAuthor** (pivot): id, boardId, userId, isOwner (bool) ← `@@unique([boardId, userId])`
- **Tile**: id, boardId, position, taskId?, titleOverride?, type (TileType), targetPosition?, ← `@@unique([boardId, position])`
- **PlayerBoard**: id, userId, boardId, currentPosition, diceRollsToday, lastRollDate?, ← `@@unique([userId, boardId])`
- **CompletedTile**: id, playerBoardId, tileId, completedAt, completedVia ← `@@unique([playerBoardId, tileId])`

### Phase 2 additions (planned)
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
