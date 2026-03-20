import { Module } from '@nestjs/common'
import { GraphQLModule } from '@nestjs/graphql'
import { ApolloDriver, ApolloDriverConfig } from '@nestjs/apollo'
import { ConfigModule } from '@nestjs/config'
import { join } from 'path'
import { PrismaModule } from './prisma/prisma.module'
import { AuthModule } from './auth/auth.module'
import { UsersModule } from './users/users.module'
import { BoardsModule } from './boards/boards.module'
import { TilesModule } from './tiles/tiles.module'
import { TasksModule } from './tasks/tasks.module'
import { PlayersModule } from './players/players.module'

@Module({
  imports: [
    // Config (loads .env)
    ConfigModule.forRoot({
      isGlobal: true,
      envFilePath: '.env'
    }),

    // GraphQL with Apollo — code-first
    GraphQLModule.forRoot<ApolloDriverConfig>({
      driver: ApolloDriver,
      autoSchemaFile: join(process.cwd(), 'src/schema.gql'),
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
    PlayersModule
  ]
})
export class AppModule {}
