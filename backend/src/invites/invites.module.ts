import { Module } from '@nestjs/common'
import { InvitesService } from './invites.service'
import { InvitesResolver } from './invites.resolver'
import { PrismaModule } from '../prisma/prisma.module'
import { BoardsModule } from '../boards/boards.module'

@Module({
  imports: [PrismaModule, BoardsModule],
  providers: [InvitesService, InvitesResolver],
  exports: [InvitesService],
})
export class InvitesModule {}
