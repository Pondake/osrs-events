# OSRS Snakes & Ladders — AI Project Rules

## Vue File Conventions
- **Order**: `<template>` FIRST, then `<script setup lang="ts">`, no `<style>` unless needed
- All text visible to users must use i18n: `$t('key')` or `const { t } = useI18n()`
- Use `defineProps` with TypeScript generics — no runtime prop validation
- Keep components clean and tidy — one concern per component

## Nuxt UI v4 Rules
- Package: `@nuxt/ui` version **4.5.1** (NOT v2, NOT v3)
- Layout components: `UApp`, `UHeader`, `UMain`, `UFooter`, `UContainer`
- Page components: `UPage`, `UPageBody`, `UPageHero`, `UPageSection`, `UPageCard`, `UPageCTA`
- Navigation: `UNavigationMenu`, `UDropdownMenu`
- Use `UButton`, `UInput`, `USelect`, `UModal`, `UBadge`, `USeparator`, `UAlert`, `USkeleton`, `UAvatar`, `UIcon`, `UTabs`, `UForm`, `UFormField`, `UColorModeButton` etc.
- **Always prefer Nuxt UI components** over plain HTML elements
- Color tokens: `text-primary`, `text-muted`, `bg-muted`, `ring-background`
- Do NOT use Nuxt UI v2/v3 prop names (e.g., `type` for buttons is NOT valid — use `color` and `variant`)
- **UTable columns (v4):** use `{ id: 'col', header: 'Label' }` — NOT `{ key, label }` (that is v3 API). TanStack Table (used internally) requires `id` not `key`. Cell slots are `#[id]-cell="{ row }"`.
- **UTable accessorKey:** only use `accessorKey` when the column reads directly from a data field without a slot. For all slot-rendered columns use `id`.
- **UTable cell slots:** the slot receives a TanStack `row` object — ALWAYS use `row.original` to access the actual data (e.g. `row.original.name`). Passing `row` directly to modal/click handlers will pass the TanStack wrapper object, not the data row — always pass `row.original`.

## i18n Rules
- Module: `@nuxtjs/i18n`
- Primary language: **English (en)** — all labels, descriptions, button texts in English
- Locale file: `frontend/locales/en.json`
- No other languages needed in Phase 1
- All user-visible strings must go through `$t()` or `t()` — no hardcoded UI text

## TypeScript / ESLint
- ESLint is configured — keep code lint-clean
- Backend: NestJS code-first GraphQL, decorators enabled in tsconfig
- Frontend: Nuxt 4 auto-imports — no manual imports of `ref`, `computed`, etc.
- `process.env` in `nuxt.config.ts` requires `@types/node`

## Tech Stack
- **Frontend**: Nuxt 4 + Nuxt UI v4.5.1 + @pinia/nuxt + @nuxtjs/i18n + @urql/vue
- **Backend**: NestJS + @nestjs/graphql (code-first) + Prisma + PostgreSQL
- **Auth**: Discord OAuth2 (manual, no passport-discord) + JWT (7 day expiry)
- **IDs**: UUID everywhere (no integer IDs)
- **Colors**: Primary = purple, Neutral = stone, Secondary accents = amber/gold

## Database
- PostgreSQL: `postgresql://postgres@localhost:5432/osrs_events`
- Prisma ORM with `prisma.config.ts` (Prisma v7+)
- All models use `@id @default(uuid()) @db.Uuid`

## Project Structure Rules
- Frontend pages: `frontend/app/pages/`
- Frontend components: `frontend/app/components/Category/ComponentName.vue`
- Frontend stores (Pinia): `frontend/app/stores/`
- Frontend composables: `frontend/app/composables/`
- Backend modules: `backend/src/module-name/`

## Naming
- All GraphQL entities use `Entity` suffix (e.g., `BoardEntity`, `TileEntity`)
- All GraphQL input types use `Input` suffix (e.g., `CreateBoardInput`)
- NestJS services use `Service` suffix, resolvers use `Resolver` suffix

## TypeScript Types — Always Use Generated Types First
- **Before writing any `interface` or `type` in frontend code, check `~/types/graphql` first.**
- GraphQL codegen (`pnpm generate:watch`) generates all entity and input types from the schema into `frontend/app/types/graphql.ts`. Run or check this file before adding new types.
- If the type already exists there, import it — never redefine it manually.
- If the generated type has more fields than your query selects, use `Pick<EntityType, 'field1' | 'field2'>` rather than a manual interface.
- If a query returns a generated type plus extra fields, extend it: `type MyEntry = LeaderboardEntryEntity & { user: Pick<UserEntity, 'id' | 'discordUsername'> }`
- **Keep manual types only when:** (a) the GQL query selects a strict subset of an entity's fields AND using the full entity would make TypeScript expect fields not present at runtime — add a comment explaining this; or (b) the type contains UI-only fields not in the schema (e.g. `BoardFormData.unlimitedRolls`, `PlayerAvatar.isTeam`).
- `defineProps<{...}>()` — always inline, never a separate `interface Props {}`.
