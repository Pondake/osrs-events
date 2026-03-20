import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { BoardsService } from './boards.service'
import { BoardEntity } from './entities/board.entity'
import { CreateBoardInput, UpdateBoardInput } from './dto/create-board.input'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { OptionalJwtAuthGuard } from '../auth/guards/optional-jwt.guard'
import { RolesGuard } from '../auth/guards/roles.guard'
import { Roles } from '../auth/decorators/roles.decorator'
import { CurrentUser } from '../auth/decorators/current-user.decorator'
import { UserEntity } from '../users/entities/user.entity'

@Resolver(() => BoardEntity)
export class BoardsResolver {
  constructor(private boardsService: BoardsService) {}

  /** List all boards — public (optional auth for player-specific data) */
  @UseGuards(OptionalJwtAuthGuard)
  @Query(() => [BoardEntity], { name: 'boards' })
  findAll() {
    return this.boardsService.findAll()
  }

  /** Get a single board by UUID */
  @UseGuards(OptionalJwtAuthGuard)
  @Query(() => BoardEntity, { name: 'board', nullable: true })
  findOne(@Args('id', { type: () => ID }) id: string) {
    return this.boardsService.findById(id)
  }

  /** Create a new board (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => BoardEntity)
  createBoard(
    @Args('input') input: CreateBoardInput,
    @CurrentUser() user: UserEntity
  ) {
    return this.boardsService.create(input, user.id)
  }

  /** Update a board (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => BoardEntity)
  updateBoard(
    @Args('id', { type: () => ID }) id: string,
    @Args('input') input: UpdateBoardInput
  ) {
    return this.boardsService.update(id, input)
  }

  /** Delete a board (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => BoardEntity)
  deleteBoard(@Args('id', { type: () => ID }) id: string) {
    return this.boardsService.delete(id)
  }
}
