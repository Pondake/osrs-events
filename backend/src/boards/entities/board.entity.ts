import { ObjectType, Field, ID, Int, registerEnumType } from '@nestjs/graphql'
import { UserEntity } from '../../users/entities/user.entity'
import { TileEntity } from '../../tiles/entities/tile.entity'

export enum BoardSize {
  SIZE_5X5 = 'SIZE_5X5',
  SIZE_7X7 = 'SIZE_7X7',
  SIZE_9X9 = 'SIZE_9X9'
}

registerEnumType(BoardSize, { name: 'BoardSize' })

@ObjectType()
export class BoardAuthorEntity {
  @Field(() => ID)
  id: string

  @Field(() => UserEntity)
  user: UserEntity
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

  @Field(() => Int, { nullable: true, description: 'null = unlimited rolls per day' })
  diceRollLimit?: number

  @Field(() => [BoardAuthorEntity])
  authors: BoardAuthorEntity[]

  @Field(() => [TileEntity], { nullable: true })
  tiles?: TileEntity[]

  @Field()
  createdAt: Date

  @Field()
  updatedAt: Date
}
