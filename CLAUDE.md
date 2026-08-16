# OSRS Events — Claude Rules

## Model selection
- **Haiku** — mechanical tasks: adding i18n keys, renaming, small field additions, fixing typos, one-liner refactors
- **Sonnet** — complex tasks: new features, multi-file refactors, architectural decisions, debugging subtle issues, schema changes

## Package manager
Always **pnpm**. Never `npm`, `npx`, or `yarn`.
```
pnpm dev / pnpm build / pnpm generate
pnpm prisma migrate dev --name <name>
pnpm prisma generate
```

## Repository layout
```
osrs-events/
├── backend/    NestJS · GraphQL code-first · Prisma v7 · PostgreSQL (Neon)
└── frontend/   Nuxt 4 · Vue 3 · Nuxt UI v4 · Tailwind v4 · Pinia · TypeScript
```

---

## Vue / TypeScript

### Refs
- Always `ref()`. Only use `reactive()` when there is a specific, justified reason.

### Composables — when to extract
Extract logic to a composable only when:
1. The component script exceeds ~200 lines **and** the logic forms a coherent group (e.g. all form-related state), or
2. The logic needs to be shared across multiple components

Do not extract prematurely. Inline logic is fine for small-to-medium components.

### TypeScript
- Standard `strict` mode. No exotic flags.
- Let TypeScript **infer** return types wherever possible — only annotate explicitly when inference fails or the type is non-obvious.
- Remove `as any` casts after codegen updates types. Only keep them when genuinely unavoidable.

---

## Frontend conventions

### i18n — ALL user-visible strings must be translated
- Every label, description, button, toast, placeholder, step title, badge → `frontend/locales/en.json`
- Never hardcode English strings in templates or script. Use `$t('key')` in templates, `t('key')` in script.
- Key namespaces: `admin.*`, `board.*`, `boards.*`, `teams.*`, `common.*`, `validation.*`, `errors.*`
- Add the key **at the same time** as writing the component — never leave translation as a follow-up task.

### Naming
- Vue component files: **PascalCase** (`SettingsModal.vue`, `AccessGate.vue`)
- Component usage in templates: **kebab-case** (`<board-settings-modal />`)
- Pages and folders: **kebab-case** (`admin/boards/index.vue`)
- Composables: **camelCase**, `use` prefix (`useBoards.ts`, `useBoardPage.ts`)
- i18n keys: **snake_case** within each namespace

### Styling
- Tailwind utility classes throughout. Only write custom CSS when Tailwind genuinely cannot express it.
- **Mobile-first**: default styles target mobile, use `sm:`, `lg:` etc. to scale up.

### Error handling
- Every `catch` block must include `console.error(error)` at minimum.
- Show a toast only when the failure is **relevant to the user** — ask: "does the user need to know this failed, or is it silent background work?"
- Auth errors: 401 → redirect to login; 403 → show a forbidden toast/alert.

### Toast IDs
- Every `toast.add()` must include an `id`.
- Toasts in the same logical category share the same `id` so they overwrite instead of stack.
- Convention: `'step-validation'`, `'board-save'`, `'board-save-error'`, `'team-update'`, `'team-update-error'`

### GraphQL types
- Generated from `backend/schema.gql` — run `pnpm generate` in `/frontend` after any backend schema change.
- File: `frontend/app/types/graphql.ts` — **do not edit manually**.
- After codegen, clean up `as any` casts that were working around missing types.
- Codegen runs with `enumsAsTypes: true` — GraphQL enums become string-literal
  unions (`type BoardAccessMode = 'OPEN' | 'GUILD' | 'INVITE'`), not TS enums.
  Assign raw literals (`accessMode: 'OPEN'`); never `BoardAccessMode.Open`.
  Import them with `import type`, since they have no runtime value.

### Composables pattern
- All GraphQL queries/mutations live in `frontend/app/composables/` — never inline in pages or components.
- Field selection strings at the top of the file: `const X_FIELDS = \`...\``
- Admin/public pairs: e.g. `useBoards()` (public, `isListed: true` filter) vs `useAllBoards()` (admin, no filter). Apply same split to any entity with a public and admin view.
- Reactive composables return `{ data, loading, load }`.
- Imperative mutations are standalone exported `async function`s.

### Create vs edit — always a modal
- Both create and edit use a single `<Entity>SettingsModal` component.
- Create (`entityId = null`): renders `u-stepper` with linear step navigation and per-step validation.
- Edit (`entityId` set): renders `u-tabs` for free navigation between sections.
- Never create a dedicated `/*/create` page for entities that have a modal edit flow.
- A `/create` route that does exist should immediately redirect to the list page (`navigateTo('/...', { replace: true })`).

### Validation
- Step validation fires before advancing — `tryNext()` validates the current step and shows a toast on failure.
- Final submit validates again (defence in depth).
- Use a stable toast `id` to prevent stacking on rapid clicks.

---

## Backend conventions

### Module structure
Each domain under `backend/src/<domain>/`:
```
<domain>/
├── <domain>.module.ts
├── <domain>.service.ts
├── <domain>.resolver.ts     GraphQL
├── dto/                     input types
└── entities/                GraphQL object types
```
Register new modules in `AppModule` imports (`backend/src/app.module.ts`).

### Prisma
- Schema: `backend/prisma/schema.prisma`
- After changes: `pnpm prisma migrate dev --name <descriptive_name>` then `pnpm prisma generate`
- Generated client: `backend/src/generated/prisma/` — **never edit**
- Runtime: `PrismaPg` driver adapter (`@prisma/adapter-pg`)

### GraphQL resolvers
- `@UseGuards(JwtAuthGuard)` on every authenticated operation
- Role checks via `@Roles(...)` decorator
- Nullable returns: add `{ nullable: true }` to `@Query()` / `@Field()` explicitly
- `string | undefined` args: `@Args('x', { type: () => String, nullable: true })`

### Auth
- JWT in cookie (`auth_token`) + localStorage (legacy fallback)
- Discord OAuth scope: `identify guilds` — guilds synced into `UserGuild` on every login
- `CurrentUser()` decorator available in all guarded resolvers

### Services — read vs write
- Query resolvers must be pure reads — no side effects, no record creation.
- Mutations handle all state changes. Example: `myBoardState` is a pure lookup; `joinBoard` creates the `PlayerBoard`.

### Transactions
- Use `prisma.$transaction([...])` when multiple writes must succeed or fail together (e.g. creating a `BoardAccess` and incrementing `useCount` on a `BoardInvite`).

---

## What NOT to do
- Do not use `npm`, `npx`, or `yarn`
- Do not hardcode user-visible strings — always use i18n
- Do not edit generated files (`app/types/graphql.ts`, `src/generated/prisma/`)
- Do not auto-create `PlayerBoard` on read — `joinBoard` mutation handles it; `myBoardState` is a pure read
- Do not add unnecessary `as any` — remove after codegen
- Do not use `reactive()` without a good reason — default to `ref()`
- Do not write custom CSS unless Tailwind cannot express it
- Do not create a separate `/create` page for entities that have a modal flow
- Do not skip `console.error()` in catch blocks
