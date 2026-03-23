import { Module } from '@nestjs/common'
import { PermissionsService } from './permissions.service'
import { PermissionsResolver } from './permissions.resolver'
import { PrismaModule } from '../prisma/prisma.module'

@Module({
  imports: [PrismaModule],
  providers: [PermissionsService, PermissionsResolver],
  exports: [PermissionsService],
})
export class PermissionsModule {}
