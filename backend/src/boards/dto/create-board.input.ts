import { InputType, Field, Int, ID } from '@nestjs/graphql'
import { BoardSize, BoardMode } from '../entities/board.entity'
import { BoardAccessMode } from '../../access/entities/board-access.entity'

@InputType()
export class CreateBoardInput {
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

  @Field(() => BoardMode, { nullable: true })
  mode?: BoardMode

  @Field(() => Int, { nullable: true })
  diceRollLimit?: number

  @Field(() => [ID], { description: 'UUIDs of admin users to set as authors' })
  authorIds: string[]

  @Field({ nullable: true })
  isListed?: boolean

  @Field(() => BoardAccessMode, { nullable: true })
  accessMode?: BoardAccessMode

  @Field({ nullable: true })
  requiredGuildId?: string
}

@InputType()
export class UpdateBoardInput {
  @Field({ nullable: true })
  title?: string

  @Field({ nullable: true })
  description?: string

  @Field({ nullable: true })
  startDate?: Date

  @Field({ nullable: true })
  endDate?: Date

  @Field(() => BoardSize, { nullable: true })
  size?: BoardSize

  @Field(() => BoardMode, { nullable: true })
  mode?: BoardMode

  @Field(() => Int, { nullable: true })
  diceRollLimit?: number

  @Field(() => [ID], { nullable: true })
  authorIds?: string[]

  @Field({ nullable: true })
  isListed?: boolean

  @Field(() => BoardAccessMode, { nullable: true })
  accessMode?: BoardAccessMode

  @Field({ nullable: true })
  requiredGuildId?: string
}
