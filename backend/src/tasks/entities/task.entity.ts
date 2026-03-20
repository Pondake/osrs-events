import { ObjectType, Field, ID } from '@nestjs/graphql'

@ObjectType()
export class TaskEntity {
  @Field(() => ID)
  id: string

  @Field()
  title: string

  @Field({ nullable: true })
  iconUrl?: string

  @Field({ nullable: true })
  description?: string

  @Field()
  createdAt: Date

  @Field()
  updatedAt: Date
}
