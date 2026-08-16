import { InputType, Field, ID, Int } from '@nestjs/graphql'

@InputType()
export class CreateInviteInput {
  @Field(() => ID)
  boardId: string

  @Field({ nullable: true })
  label?: string

  @Field({ nullable: true })
  expiresAt?: Date

  @Field(() => Int, { nullable: true })
  maxUses?: number
}
