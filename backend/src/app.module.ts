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
import { SeedModule } from './seed/seed.module'

@Module({
  imports: [
    // Config (loads .env)
    ConfigModule.forRoot({
      isGlobal: true,
      envFilePath: '.env'
    }),

    // GraphQL with Apollo — code-first
    // autoSchemaFile: true keeps the schema in memory — never writes to disk.
    // This is required for Vercel Lambda (read-only filesystem) and is safe
    // for local development too (schema.gql in the repo is the generated copy).
    GraphQLModule.forRoot<ApolloDriverConfig>({
      driver: ApolloDriver,
      autoSchemaFile: true,
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
    // SeedModule, enable to seed when running the app
  ]
})
export class AppModule {}
