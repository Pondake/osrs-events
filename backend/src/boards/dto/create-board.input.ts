import { InputType, Field, Int, ID } from '@nestjs/graphql'
import { BoardSize } from '../entities/board.entity'

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

  @Field(() => Int, { nullable: true })
  diceRollLimit?: number

  @Field(() => [ID], { description: 'UUIDs of admin users to set as authors' })
  authorIds: string[]
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

  @Field(() => Int, { nullable: true })
  diceRollLimit?: number

  @Field(() => [ID], { nullable: true })
  authorIds?: string[]
}
