Run `pnpm generate` inside the `frontend/` directory to regenerate `app/types/graphql.ts` from the backend GraphQL schema.

After it completes:
1. Check if any `as any` casts in the frontend can now be removed because the types exist.
2. Report what changed in `app/types/graphql.ts` (new types, modified fields, removals).
