import { ObjectType, Field, ID, Int, registerEnumType } from '@nestjs/graphql'
import { UserEntity } from '../../users/entities/user.entity'
import { TileEntity } from '../../tiles/entities/tile.entity'

export enum BoardSize {
  SIZE_5X5 = 'SIZE_5X5',
  SIZE_7X7 = 'SIZE_7X7',
  SIZE_9X9 = 'SIZE_9X9'
}

registerEnumType(BoardSize, { name: 'BoardSize' })

export enum BoardMode {
  SOLO = 'SOLO',
  TEAM = 'TEAM'
}

registerEnumType(BoardMode, { name: 'BoardMode' })

@ObjectType()
export class BoardAuthorEntity {
  @Field(() => ID)
  id: string

  @Field()
  isOwner: boolean

  @Field(() => UserEntity)
  user: UserEntity
}

/** Lightweight team summary embedded in BoardTeamEntity — avoids circular imports */
@ObjectType()
export class BoardTeamTeamSummary {
  @Field(() => ID)
  id: string

  @Field()
  name: string

  @Field({ nullable: true })
  iconUrl?: string
}

@ObjectType()
export class BoardTeamEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  boardId: string

  @Field(() => ID)
  teamId: string

  @Field(() => BoardTeamTeamSummary)
  team: BoardTeamTeamSummary

  @Field()
  createdAt: Date
}

@ObjectType()
export class BoardEntity {
  @Field(() => ID)
  id: string

  @Field()
  title: string

  @Field({ nullable: true })
  description?: string

  @Field({ nullable: true })
  startDate?: Date

  @Field({ nullable: true })
  endDate?: Date

  @Field(() => BoardSize)
  size: BoardSize

  @Field(() => BoardMode)
  mode: BoardMode

  @Field(() => Int, { nullable: true, description: 'null = unlimited rolls per day' })
  diceRollLimit?: number

  @Field(() => [BoardAuthorEntity])
  authors: BoardAuthorEntity[]

  @Field(() => [BoardTeamEntity], { nullable: true })
  boardTeams?: BoardTeamEntity[]

  @Field(() => [TileEntity], { nullable: true })
  tiles?: TileEntity[]

  @Field()
  createdAt: Date

  @Field()
  updatedAt: Date
}
