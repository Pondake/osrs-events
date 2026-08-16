import { InputType, Field, ID } from '@nestjs/graphql'

@InputType()
export class CreateTeamInput {
  @Field()
  name: string

  @Field({ nullable: true })
  iconUrl?: string

  @Field({ nullable: true })
  guildId?: string

  @Field({ nullable: true })
  guildName?: string
}

@InputType()
export class UpdateTeamInput {
  @Field({ nullable: true })
  name?: string

  @Field({ nullable: true })
  iconUrl?: string

  @Field({ nullable: true })
  guildId?: string

  @Field({ nullable: true })
  guildName?: string
}

@InputType()
export class AddTeamMemberInput {
  @Field(() => ID)
  teamId: string

  @Field(() => ID)
  userId: string
}
