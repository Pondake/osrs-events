import { Module } from '@nestjs/common'
import { BoardsService } from './boards.service'
import { BoardsResolver } from './boards.resolver'

@Module({
  providers: [BoardsService, BoardsResolver],
  exports: [BoardsService]
})
export class BoardsModule {}
