import { Module } from '@nestjs/common'
import { TeamsService } from './teams.service'
import { TeamsResolver } from './teams.resolver'
import { PrismaModule } from '../prisma/prisma.module'

@Module({
  imports: [PrismaModule],
  providers: [TeamsService, TeamsResolver],
  exports: [TeamsService],
})
export class TeamsModule {}
