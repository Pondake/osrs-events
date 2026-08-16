import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { ForbiddenException, UseGuards } from '@nestjs/common'
import { BoardsService } from './boards.service'
import { BoardAuthorEntity, BoardEntity, BoardTeamEntity } from './entities/board.entity'
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

  /** List all boards — public, only shows listed boards */
  @UseGuards(OptionalJwtAuthGuard)
  @Query(() => [BoardEntity], { name: 'boards' })
  findAll() {
    return this.boardsService.findAll()
  }

  /** List all boards including unlisted — admin/editor only */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'EDITOR')
  @Query(() => [BoardEntity], { name: 'allBoards' })
  findAllAdmin() {
    return this.boardsService.findAllAdmin()
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

  /** Update a board (admin always; editor only if they are the board owner) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'EDITOR')
  @Mutation(() => BoardEntity)
  async updateBoard(
    @Args('id', { type: () => ID }) id: string,
    @Args('input') input: UpdateBoardInput,
    @CurrentUser() user: UserEntity
  ) {
    const isAdmin = (user.userRoles ?? []).some((ur) => ur.role.name === 'ADMIN')
    if (!isAdmin) {
      const isOwner = await this.boardsService.isBoardOwner(id, user.id)
      if (!isOwner) throw new ForbiddenException('You are not the owner of this board')
    }
    return this.boardsService.update(id, input)
  }

  /** Add a co-editor to a board (admin always; editor only if they are the board owner) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'EDITOR')
  @Mutation(() => BoardAuthorEntity)
  async addBoardAuthor(
    @Args('boardId', { type: () => ID }) boardId: string,
    @Args('userId', { type: () => ID }) userId: string,
    @CurrentUser() user: UserEntity
  ) {
    const isAdmin = (user.userRoles ?? []).some((ur) => ur.role.name === 'ADMIN')
    if (!isAdmin) {
      const isOwner = await this.boardsService.isBoardOwner(boardId, user.id)
      if (!isOwner) throw new ForbiddenException('You are not the owner of this board')
    }
    return this.boardsService.addAuthor(boardId, userId)
  }

  /** Remove a co-editor from a board. The board owner cannot be removed. */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'EDITOR')
  @Mutation(() => Boolean)
  async removeBoardAuthor(
    @Args('boardId', { type: () => ID }) boardId: string,
    @Args('userId', { type: () => ID }) userId: string,
    @CurrentUser() user: UserEntity
  ) {
    const isAdmin = (user.userRoles ?? []).some((ur) => ur.role.name === 'ADMIN')
    if (!isAdmin) {
      const isOwner = await this.boardsService.isBoardOwner(boardId, user.id)
      if (!isOwner) throw new ForbiddenException('You are not the owner of this board')
    }
    return this.boardsService.removeAuthor(boardId, userId)
  }

  /** Add a team to a TEAM-mode board (admin or board owner) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'EDITOR')
  @Mutation(() => BoardTeamEntity)
  async addTeamToBoard(
    @Args('boardId', { type: () => ID }) boardId: string,
    @Args('teamId', { type: () => ID }) teamId: string,
    @CurrentUser() user: UserEntity
  ) {
    const isAdmin = (user.userRoles ?? []).some((ur) => ur.role.name === 'ADMIN')
    if (!isAdmin) {
      const isOwner = await this.boardsService.isBoardOwner(boardId, user.id)
      if (!isOwner) throw new ForbiddenException('You are not the owner of this board')
    }
    return this.boardsService.addTeamToBoard(boardId, teamId)
  }

  /** Remove a team from a board (admin or board owner) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'EDITOR')
  @Mutation(() => Boolean)
  async removeTeamFromBoard(
    @Args('boardId', { type: () => ID }) boardId: string,
    @Args('teamId', { type: () => ID }) teamId: string,
    @CurrentUser() user: UserEntity
  ) {
    const isAdmin = (user.userRoles ?? []).some((ur) => ur.role.name === 'ADMIN')
    if (!isAdmin) {
      const isOwner = await this.boardsService.isBoardOwner(boardId, user.id)
      if (!isOwner) throw new ForbiddenException('You are not the owner of this board')
    }
    return this.boardsService.removeTeamFromBoard(boardId, teamId)
  }

  /** Delete a board (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => BoardEntity)
  deleteBoard(@Args('id', { type: () => ID }) id: string) {
    return this.boardsService.delete(id)
  }
}
