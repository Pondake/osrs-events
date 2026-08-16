import { ObjectType, Field, ID, Int, registerEnumType } from '@nestjs/graphql'
import { UserEntity } from '../../users/entities/user.entity'

// Lightweight board summary — avoids circular imports with board.entity.ts
@ObjectType()
export class PlayerBoardBoardSummary {
  @Field(() => ID)
  id: string

  @Field()
  title: string

  @Field()
  size: string
}

// Lightweight team summary — avoids circular imports with team.entity.ts
@ObjectType()
export class PlayerBoardTeamSummary {
  @Field(() => ID)
  id: string

  @Field()
  name: string

  @Field({ nullable: true })
  iconUrl?: string
}

export enum CompletionSource {
  MANUAL = 'MANUAL',
  RUNELITE = 'RUNELITE'
}

registerEnumType(CompletionSource, { name: 'CompletionSource' })

@ObjectType()
export class CompletedTileEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  tileId: string

  @Field()
  completedAt: Date

  @Field(() => CompletionSource)
  completedVia: CompletionSource
}

@ObjectType()
export class PlayerBoardEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  userId: string

  @Field(() => ID)
  boardId: string

  @Field(() => ID, { nullable: true })
  teamId?: string

  @Field(() => Int)
  currentPosition: number

  @Field(() => Int)
  diceRollsToday: number

  @Field({ nullable: true })
  lastRollDate?: Date

  @Field(() => [CompletedTileEntity])
  completedTiles: CompletedTileEntity[]

  @Field(() => PlayerBoardBoardSummary, { nullable: true })
  board?: PlayerBoardBoardSummary

  @Field(() => UserEntity, { nullable: true })
  user?: UserEntity

  @Field(() => PlayerBoardTeamSummary, { nullable: true })
  team?: PlayerBoardTeamSummary

  @Field()
  createdAt: Date

  @Field()
  updatedAt: Date
}

@ObjectType()
export class RollResultEntity {
  @Field(() => Int)
  rolled: number

  @Field(() => Int)
  previousPosition: number

  @Field(() => Int)
  newPosition: number

  @Field(() => Int, { nullable: true, description: 'Position before snake/ladder jump' })
  landedOn?: number

  @Field({ nullable: true, description: 'snake | ladder | null' })
  jump?: string

  @Field(() => PlayerBoardEntity)
  playerBoard: PlayerBoardEntity
}
