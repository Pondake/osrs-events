import { join } from 'path'
import { Module } from '@nestjs/common'
import { GraphQLModule } from '@nestjs/graphql'
import { ApolloDriver, ApolloDriverConfig } from '@nestjs/apollo'
import { ConfigModule } from '@nestjs/config'
import { PrismaModule } from './prisma/prisma.module'
import { AuthModule } from './auth/auth.module'
import { UsersModule } from './users/users.module'
import { BoardsModule } from './boards/boards.module'
import { TilesModule } from './tiles/tiles.module'
import { TasksModule } from './tasks/tasks.module'
import { PlayersModule } from './players/players.module'
import { TeamsModule } from './teams/teams.module'
import { PermissionsModule } from './permissions/permissions.module'
import { InvitesModule } from './invites/invites.module'
import { AccessModule } from './access/access.module'
import { SeedModule } from './seed/seed.module'

@Module({
  imports: [
    // Config (loads .env)
    ConfigModule.forRoot({
      isGlobal: true,
      envFilePath: '.env'
    }),

    // GraphQL with Apollo — code-first
    // In development the schema is written to schema.gql so graphql-codegen
    // can generate frontend TypeScript types without a running server.
    // In production (Vercel Lambda) the filesystem is read-only, so we keep
    // it in memory with autoSchemaFile: true.
    GraphQLModule.forRoot<ApolloDriverConfig>({
      driver: ApolloDriver,
      autoSchemaFile:
        process.env.NODE_ENV !== 'production'
          ? join(process.cwd(), 'schema.gql')
          : true,
      sortSchema: true,
      context: ({ req }: { req: Request }) => ({ req }),
      playground: process.env.NODE_ENV !== 'production',
      introspection: process.env.NODE_ENV !== 'production'
    }),

    // Database
    PrismaModule,

    // Feature modules
    AuthModule,
    UsersModule,
    BoardsModule,
    TilesModule,
    TasksModule,
    PlayersModule,
    TeamsModule,
    PermissionsModule,
    InvitesModule,
    AccessModule,
    // SeedModule, enable to seed when running the app
  ]
})
export class AppModule {}
