import { ObjectType, Field, ID } from '@nestjs/graphql'

@ObjectType()
export class RoleEntity {
  @Field(() => ID)
  id: string

  @Field()
  name: string

  @Field({ nullable: true })
  description?: string
}

@ObjectType()
export class UserRoleEntity {
  @Field(() => ID)
  id: string

  @Field(() => RoleEntity)
  role: RoleEntity
}

@ObjectType()
export class UserEntity {
  @Field(() => ID)
  id: string

  @Field()
  discordId: string

  @Field()
  discordUsername: string

  @Field({ nullable: true })
  nickname?: string

  @Field({ nullable: true })
  avatarUrl?: string

  @Field(() => [UserRoleEntity])
  userRoles: UserRoleEntity[]

  @Field()
  createdAt: Date

  @Field()
  updatedAt: Date
}
