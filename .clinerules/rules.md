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
- PostgreSQL: `postgresql://postgres@localhost:5432/osrs_snakes`
- Prisma ORM with `prisma.config.ts` (Prisma v7+)
- All models use `@id @default(uuid()) @db.Uuid`

## Project Structure Rules
- Frontend pages: `frontend/app/pages/`
- Frontend components: `frontend/app/components/Category/ComponentName.vue`
- Frontend stores (Pinia): `frontend/app/stores/`
- Frontend composables: `frontend/app/composables/`
- Backend modules: `backend/src/module-name/`

## Phase Tracking
- **Phase 1** (current): Core game, auth, boards, tiles, dice roll, tile completion
- **Phase 2** (planned): Teams, EDITOR role, TEAM_MANAGER role
- **Phase 3** (planned): Dark mode refinements, pixel art, RuneLite webhook
- Dark mode is included from Phase 1 via `UColorModeButton`

## Naming
- All GraphQL entities use `Entity` suffix (e.g., `BoardEntity`, `TileEntity`)
- All GraphQL input types use `Input` suffix (e.g., `CreateBoardInput`)
- NestJS services use `Service` suffix, resolvers use `Resolver` suffix
