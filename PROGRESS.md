# ⚔️ OSRS Events — AI Progress Tracker

> Future phases and roadmap items are tracked in [ROADMAP.md](./ROADMAP.md).

## Project Overview
An OSRS-themed events platform. Players log in with Discord, join boards (currently Snakes & Ladders style) and track their progression on boards created by admins.

## Stack
- **Frontend**: Nuxt 4 + Nuxt UI v4.5.1 + @pinia/nuxt + @nuxtjs/i18n + custom `useGql`/`useGqlMutation` composables
- **Backend**: NestJS + @nestjs/graphql (Apollo, code-first) + Prisma v7 + PostgreSQL
- **Auth**: Discord OAuth2 (manual, no passport-discord) + @nestjs/jwt + passport-jwt
- **IDs**: UUID everywhere (`@id @default(uuid()) @db.Uuid`)
- **Color**: Purple (primary) + Stone (neutral) + Amber/Gold (secondary accents)

---

## Phase 1 — Core Features

### ✅ Completed

#### Project Setup
- [x] Project scaffolding (frontend Nuxt 4 + backend NestJS)
- [x] Added Nuxt UI v4.5.1
- [x] OSRS font (Cinzel via Google Fonts)
- [x] Dark mode switching
- [x] i18n setup (`@nuxtjs/i18n`, strategy: no_prefix, English only, `frontend/locales/en.json`)
- [x] ESLint + TypeScript configured (template > script order in Vue files)

#### Backend — NestJS + GraphQL + Prisma
- [x] Prisma v7 schema: Role, User, UserRole, Task, Board, BoardAuthor, Tile, PlayerBoard, CompletedTile
- [x] Prisma v7 config in `prisma.config.ts` (datasource URL + seed command outside schema.prisma)
- [x] Prisma v7 driver adapter: `PrismaPg` from `@prisma/adapter-pg` in `PrismaService` constructor
- [x] `@as-integrations/express5` installed (required by Apollo Server 5 + Express)
- [x] Database created and initial migration applied (`prisma migrate dev --name init`)
- [x] Board dates (`startDate`, `endDate`) are nullable
- [x] AppModule: ConfigModule (global), GraphQLModule (code-first, playground), PrismaModule, AuthModule, UsersModule, BoardsModule, TilesModule, TasksModule, PlayersModule
- [x] NestJS CORS configured for `NUXT_APP_URL` (port 3000)

#### Auth
- [x] Manual Discord OAuth2 flow: `GET /auth/discord` → Discord → `GET /auth/discord/callback` → JWT → Frontend
  - Backend on port 3001; Discord Developer Portal redirect URL must be `http://localhost:3001/auth/discord/callback`
  - On success: redirects to `http://localhost:3000/auth/callback?token=...`
  - On failure: redirects to `http://localhost:3000/auth/callback?error=auth_failed` (shows toast)
- [x] JWT strategy + JwtAuthGuard + OptionalJwtAuthGuard + RolesGuard
- [x] `GET /auth/me` returns current user with roles
- [x] Frontend auth store (Pinia): `user`, `token`, `isAuthenticated`, `isAdmin`, `isEditor`, `hydrated`
- [x] `auth.client.ts` plugin: loads token from localStorage, fetches user, sets `hydrated = true`
- [x] `USkeleton` in `AuthUserMenu` while `!hydrated` — eliminates the login-button flash
- [x] `auth/callback.vue`: captures `?token=` or `?error=` from OAuth redirect, shows toast, redirects
- [x] Auth + admin middleware for route guards

#### Roles & Permissions
- [x] Roles table with pivot table UserRole
- [x] Roles seeded: PLAYER, ADMIN, EDITOR (Phase 2), TEAM_MANAGER (Phase 2)
- [x] New users automatically get PLAYER role on first login
- [x] `@Roles()` decorator + RolesGuard for admin-only endpoints

#### Tasks
- [x] Tasks CRUD (admin) — GraphQL: `tasks(search)`, `createTask`, `updateTask`, `deleteTask`
- [x] Case-insensitive search for task autocomplete
- [x] Admin tasks page at `/admin/tasks` with OSRS Wiki search integration
- [x] 15 OSRS tasks seeded (Tutorial Island, Waterfall Quest, Fire Cape, Zulrah, etc.)

#### Boards
- [x] Boards CRUD — GraphQL: `boards`, `board(id)`, `createBoard`, `updateBoard`, `deleteBoard`
- [x] Board size enum: `SIZE_5X5` (25 tiles), `SIZE_7X7` (49 tiles), `SIZE_9X9` (81 tiles)
- [x] Board authors pivot table (creator always included)
- [x] Users search endpoint: `users(search)` (admin only, case-insensitive)
- [x] Admin boards list at `/admin/boards` (size display, delete confirmation modal)
- [x] Admin board create at `/admin/boards/create` (author search with Discord avatars)
- [x] Public boards list at `/boards` with `UPageCard` grid
- [x] **Demo board seeded**: "Dragon Slayer Journey" (7×7, 49 tiles, 5 snakes, 5 ladders, 15 tasks assigned)

#### Tiles
- [x] Tile upsert by `boardId_position` unique constraint
- [x] Tile types: NORMAL, SNAKE (red), LADDER (amber)
- [x] Tile target position for snake/ladder connections
- [x] `BoardGameBoard.vue`: snaking grid order (row 0 at bottom, even rows L→R, odd rows R→L)
- [x] `BoardTile.vue`: tile icon, type indicator, player avatars, edit pencil overlay
- [x] `SnakeLadderSVG.vue`: SVG overlay with quadratic bezier curves + arrowheads
- [x] `TileEditModal.vue`: task search autocomplete, title override, tile type, target position

#### Gameplay
- [x] `DiceRoller.vue`: SVG d6 with correct dot positions, dice-rolling animation, roll limit badge
- [x] `rollDice` mutation: daily roll limit check, d6 roll, snake/ladder jumps, position update
- [x] `completeTile` / `uncompleteTile` mutations (manual completion)
- [x] `myPlayerBoards` query: all boards the current player has joined (with board summary)
- [x] Board view at `/boards/[id]`: full gameplay (dice, tile completion, admin edit mode)

#### UI/UX
- [x] Toasts positioned at top-center (`UApp :toaster="{ position: 'top-center' }"`)
- [x] Dark mode toggle (`UColorModeButton` in header)
- [x] OSRS-style font (Cinzel via Google Fonts)
- [x] Purple + Gold/Amber color theme
- [x] Parchment board background + OSRS-style border
- [x] `dice-shake` CSS animation for dice rolling
- [x] Pixel-rendering class for OSRS wiki icons (`image-rendering: pixelated`)
- [x] Responsive layout
- [x] Role-based navigation (only shows when `hydrated` + authenticated; admin items for ADMIN role)

#### Profile
- [x] Profile page at `/profile` with Discord avatar, username, roles (with color-coded badges)
- [x] Player boards list with progress bar (completed tiles / total tiles)
- [x] Nickname (display name) editing inline in profile header — saves via `updateProfile` mutation
- [x] `displayName` getter in auth store (`nickname || discordUsername`)
- [x] Progress bar `progressPct` fixed: `Math.min(99, Math.floor(Math.max(0, pos) / (total - 1) * 100))` — no more `-2%` or premature `100%`

#### Gameplay Improvements
- [x] Snake/ladder roll result panel below dice roller — persistent display of FROM→TO after jump
- [x] Toast messages for snake/ladder now include the "from" tile number
- [x] Bingo modal shown when the last tile (position = totalTiles - 1) is completed
- [x] `TileEditModal.vue`: 3-step flow — choose action | edit task (global) | edit tile (board) | replace task
- [x] `Task/EditForm.vue`: reusable task edit form component with OSRS wiki search

#### Admin
- [x] Admin tasks page: inline toolbar (search + Create button), dead wiki code removed from script
- [x] Admin boards page: inline toolbar (search + Create button), client-side search filtering
- [x] Admin board create: Zod error messages fixed (plain string, not `() => string`)


---

> See [ROADMAP.md](./ROADMAP.md) for Phase 2 onwards.


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
- **BoardAuthor** (pivot): id, boardId, userId ← `@@unique([boardId, userId])`
- **Tile**: id, boardId, position, taskId?, titleOverride?, type (TileType), targetPosition?, ← `@@unique([boardId, position])`
- **PlayerBoard**: id, userId, boardId, currentPosition, diceRollsToday, lastRollDate?, ← `@@unique([userId, boardId])`
- **CompletedTile**: id, playerBoardId, tileId, completedAt, completedVia ← `@@unique([playerBoardId, tileId])`

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
