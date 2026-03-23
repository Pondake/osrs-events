import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { PlayersService } from './players.service'
import { PlayerBoardEntity, RollResultEntity } from './entities/player-board.entity'
import { LeaderboardEntity } from './entities/leaderboard.entity'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { OptionalJwtAuthGuard } from '../auth/guards/optional-jwt.guard'
import { CurrentUser } from '../auth/decorators/current-user.decorator'
import { UserEntity } from '../users/entities/user.entity'

@Resolver(() => PlayerBoardEntity)
export class PlayersResolver {
  constructor(private playersService: PlayersService) {}

  /** Get all boards the current player has joined */
  @UseGuards(JwtAuthGuard)
  @Query(() => [PlayerBoardEntity], { name: 'myPlayerBoards' })
  getMyPlayerBoards(@CurrentUser() user: UserEntity) {
    return this.playersService.getMyPlayerBoards(user.id)
  }

  /** Get current player's state on a board */
  @UseGuards(JwtAuthGuard)
  @Query(() => PlayerBoardEntity, { name: 'myBoardState', nullable: true })
  getMyBoardState(
    @Args('boardId', { type: () => ID }) boardId: string,
    @CurrentUser() user: UserEntity
  ) {
    return this.playersService.getOrCreatePlayerBoard(user.id, boardId)
  }

  /** Get all players' states for a board (for viewing others' positions) */
  @UseGuards(JwtAuthGuard)
  @Query(() => [PlayerBoardEntity], { name: 'boardPlayerStates' })
  getBoardPlayerStates(
    @Args('boardId', { type: () => ID }) boardId: string
  ) {
    return this.playersService.getPlayerBoardsByBoard(boardId)
  }

  /**
   * Leaderboard for a board: players ranked by position, with tiles remaining
   * and snake/ladder path indicators. Public (optional auth).
   */
  @UseGuards(OptionalJwtAuthGuard)
  @Query(() => LeaderboardEntity, { name: 'boardLeaderboard', nullable: true })
  getBoardLeaderboard(@Args('boardId', { type: () => ID }) boardId: string) {
    return this.playersService.getLeaderboard(boardId)
  }

  /** Roll the dice */
  @UseGuards(JwtAuthGuard)
  @Mutation(() => RollResultEntity)
  rollDice(
    @Args('boardId', { type: () => ID }) boardId: string,
    @CurrentUser() user: UserEntity
  ) {
    return this.playersService.rollDice(user.id, boardId)
  }

  /** Manually mark a tile as completed */
  @UseGuards(JwtAuthGuard)
  @Mutation(() => PlayerBoardEntity)
  completeTile(
    @Args('boardId', { type: () => ID }) boardId: string,
    @Args('tileId', { type: () => ID }) tileId: string,
    @CurrentUser() user: UserEntity
  ) {
    return this.playersService.completeTile(user.id, boardId, tileId)
  }

  /** Unmark a completed tile */
  @UseGuards(JwtAuthGuard)
  @Mutation(() => PlayerBoardEntity, { nullable: true })
  uncompleteTile(
    @Args('boardId', { type: () => ID }) boardId: string,
    @Args('tileId', { type: () => ID }) tileId: string,
    @CurrentUser() user: UserEntity
  ) {
    return this.playersService.uncompleteTile(user.id, boardId, tileId)
  }
}
