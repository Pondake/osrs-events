import { ObjectType, Field, ID, Int } from '@nestjs/graphql'

@ObjectType()
export class BoardInviteEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  boardId: string

  @Field()
  token: string

  @Field()
  shortCode: string

  @Field({ nullable: true })
  label?: string

  @Field({ nullable: true })
  expiresAt?: Date

  @Field(() => Int, { nullable: true })
  maxUses?: number

  @Field(() => Int)
  useCount: number

  @Field()
  createdAt: Date
}
