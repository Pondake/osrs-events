import { ObjectType, Field, ID, Int } from '@nestjs/graphql'
import { UserEntity } from '../../users/entities/user.entity'
import { PlayerBoardTeamSummary } from './player-board.entity'

@ObjectType()
export class LeaderboardEntryEntity {
  @Field(() => Int)
  rank: number

  @Field(() => ID)
  playerId: string

  @Field(() => UserEntity)
  user: UserEntity

  /** Set for TEAM mode boards — the team this PlayerBoard belongs to */
  @Field(() => PlayerBoardTeamSummary, { nullable: true })
  team?: PlayerBoardTeamSummary

  @Field(() => Int)
  currentPosition: number

  @Field(() => Int)
  tilesRemaining: number

  /**
   * True if there is at least one LADDER tile on the path between the player's
   * current position (exclusive) and the finish (inclusive).
   * Used by the frontend to colour tilesRemaining green.
   */
  @Field()
  pathHasLadder: boolean

  /**
   * True if there is at least one SNAKE tile on the path between the player's
   * current position (exclusive) and the finish (inclusive).
   * Used by the frontend to colour tilesRemaining red.
   */
  @Field()
  pathHasSnake: boolean
}

@ObjectType()
export class LeaderboardEntity {
  @Field(() => ID)
  boardId: string

  @Field(() => Int)
  totalTiles: number

  /** Full ranked list, highest position first */
  @Field(() => [LeaderboardEntryEntity])
  entries: LeaderboardEntryEntity[]
}
