import type { CodegenConfig } from '@graphql-codegen/cli'

/**
 * GraphQL Codegen config.
 *
 * Reads the schema written by NestJS on server startup (backend/schema.gql)
 * and generates TypeScript types into app/types/graphql.ts.
 *
 * Usage: pnpm generate
 * Re-run after any backend schema changes.
 */
const config: CodegenConfig = {
  schema: '../backend/schema.gql',
  generates: {
    './app/types/graphql.ts': {
      plugins: ['typescript'],
      config: {
        scalars: { DateTime: 'string' },
        enumsAsTypes: false,
        maybeValue: 'T | null | undefined',
        skipTypename: true,
      },
    },
  },
}

export default config
