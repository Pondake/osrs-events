import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { AccessService } from './access.service'
import { BoardAccessEntity } from './entities/board-access.entity'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { CurrentUser } from '../auth/decorators/current-user.decorator'
import { UserEntity } from '../users/entities/user.entity'

@Resolver(() => BoardAccessEntity)
export class AccessResolver {
  constructor(private accessService: AccessService) {}

  @UseGuards(JwtAuthGuard)
  @Mutation(() => BoardAccessEntity)
  joinBoard(
    @Args('boardId', { type: () => ID }) boardId: string,
    @Args('tokenOrCode', { type: () => String, nullable: true }) tokenOrCode: string | undefined,
    @CurrentUser() user: UserEntity
  ) {
    return this.accessService.joinBoard(user.id, boardId, tokenOrCode)
  }

  @UseGuards(JwtAuthGuard)
  @Query(() => BoardAccessEntity, { name: 'myBoardAccess', nullable: true })
  myBoardAccess(
    @Args('boardId', { type: () => ID }) boardId: string,
    @CurrentUser() user: UserEntity
  ) {
    return this.accessService.getMyBoardAccess(user.id, boardId)
  }
}
