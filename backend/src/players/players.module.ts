import { Module } from '@nestjs/common'
import { PlayersService } from './players.service'
import { PlayersResolver } from './players.resolver'
import { AccessModule } from '../access/access.module'

@Module({
  imports: [AccessModule],
  providers: [PlayersService, PlayersResolver],
  exports: [PlayersService]
})
export class PlayersModule {}
