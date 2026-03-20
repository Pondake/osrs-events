import { Module } from '@nestjs/common'
import { TilesService } from './tiles.service'
import { TilesResolver } from './tiles.resolver'

@Module({
  providers: [TilesService, TilesResolver],
  exports: [TilesService]
})
export class TilesModule {}
