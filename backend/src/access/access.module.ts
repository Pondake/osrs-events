import { Module } from '@nestjs/common'
import { AccessService } from './access.service'
import { AccessResolver } from './access.resolver'
import { PrismaModule } from '../prisma/prisma.module'
import { InvitesModule } from '../invites/invites.module'

@Module({
  imports: [PrismaModule, InvitesModule],
  providers: [AccessService, AccessResolver],
  exports: [AccessService],
})
export class AccessModule {}
