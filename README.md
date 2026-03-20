# OSRS Snakes & Ladders

A web-based Snakes & Ladders game themed around Old School RuneScape. Admins build boards with OSRS tasks as tile objectives, and players track their progression after logging in with Discord.

Built with Nuxt 4, NestJS, and GraphQL — with a parchment-styled board, SVG snake/ladder connections, and a rolling d6.

---

## Stack

| Layer | Tech |
|---|---|
| Frontend | Nuxt 4 + Nuxt UI v4 + Pinia + urql |
| Backend | NestJS + Apollo GraphQL (code-first) |
| Database | PostgreSQL via Prisma v7 |
| Auth | Discord OAuth2 + JWT |

---

## Prerequisites

- Node.js 20+
- pnpm 9+
- PostgreSQL running locally on port 5432
- A Discord application (for OAuth)

---

## Setup

### 1. Discord application

1. Go to [discord.com/developers/applications](https://discord.com/developers/applications) and create a new application
2. In the **OAuth2** tab (not "General Information"):
   - Copy **Client ID** → `DISCORD_CLIENT_ID` in `backend/.env`
   - Reset and copy **Client Secret** → `DISCORD_CLIENT_SECRET`
   - Add a redirect URI: `http://localhost:3001/auth/discord/callback`
   - Save changes

### 2. Environment files

Copy the examples and fill them in:

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

You'll need a `JWT_SECRET` — generate one:

```bash
node -e "console.log(require('crypto').randomBytes(64).toString('hex'))"
```

The frontend `.env` works out of the box for local development.

### 3. Database

```bash
cd backend

# Create the database and apply migrations
pnpm prisma migrate dev --name init

# Seed roles, OSRS tasks, and a demo board
pnpm prisma db seed
```

### 4. Start the servers

```bash
# Terminal 1 — backend on port 3001
cd backend && pnpm start:dev

# Terminal 2 — frontend on port 3000
cd frontend && pnpm dev
```

Open [http://localhost:3000](http://localhost:3000).

### 5. Grant yourself admin

After your first Discord login you'll have the `PLAYER` role. To promote yourself:

```bash
cd backend && pnpm prisma studio
```

Open the `UserRole` table and add the `ADMIN` role to your user. The role already exists from the seed.

---

## Project layout

```
osrs-snakes/
├── backend/          # NestJS API (port 3001)
│   ├── prisma/       # Schema, migrations, seed
│   └── src/
│       ├── auth/     # Discord OAuth + JWT
│       ├── boards/   # Board CRUD
│       ├── tiles/    # Tile upsert, snake/ladder config
│       ├── tasks/    # OSRS task library
│       └── players/  # Player state, dice rolls, completions
│
└── frontend/         # Nuxt 4 app (port 3000)
    └── app/
        ├── components/
        │   ├── Board/    # GameBoard, BoardTile, SnakeLadderSVG
        │   ├── Dice/     # DiceRoller
        │   └── Tile/     # TileEditModal
        ├── pages/
        │   ├── boards/   # Board list + board detail (gameplay)
        │   ├── admin/    # Board & task management
        │   └── profile/  # Player profile + board history
        └── stores/       # Pinia: auth store
```

---

## Development notes

- **Backend hot-reload**: `pnpm start:dev` uses NestJS watch mode. The GraphQL schema (`src/schema.gql`) is auto-regenerated on each restart — if you change an entity, restart the backend.
- **ESLint**: both packages have their own config. Run `pnpm lint:fix` inside `frontend/` or `backend/`.
- **Board position order**: tile 1 is bottom-left, even rows go left→right, odd rows go right→left (standard snakes & ladders snaking).
- **OSRS Wiki icons**: the task editor searches the OSRS Wiki MediaWiki API and only shows results that have a thumbnail image.

---

## Roadmap

- **Phase 2** — Teams, `EDITOR` and `TEAM_MANAGER` roles
- **Phase 3** — RuneLite plugin webhook (auto-complete tiles from in-game events)
- **Phase 4** — Rebrand to OSRS Events, bingo board mode with leaderboard

---

*Not affiliated with Jagex or Old School RuneScape.*
