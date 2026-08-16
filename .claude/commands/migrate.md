Run a Prisma migration for the backend.

Migration name (required): $ARGUMENTS

Steps:
1. Run `pnpm prisma migrate dev --name $ARGUMENTS` from inside `backend/`.
2. Run `pnpm prisma generate` from inside `backend/`.
3. Remind the user to run `/codegen` in the frontend if the schema change adds or removes GraphQL-exposed fields.
