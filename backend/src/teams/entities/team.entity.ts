import { ObjectType, Field, ID } from '@nestjs/graphql'
import { UserEntity } from '../../users/entities/user.entity'

@ObjectType()
export class TeamMemberEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  userId: string

  @Field(() => UserEntity)
  user: UserEntity

  @Field()
  createdAt: Date
}

@ObjectType()
export class TeamEntity {
  @Field(() => ID)
  id: string

  @Field()
  name: string

  @Field({ nullable: true })
  iconUrl?: string

  @Field(() => [TeamMemberEntity])
  members: TeamMemberEntity[]

  @Field()
  createdAt: Date

  @Field()
  updatedAt: Date
}
