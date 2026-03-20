import { InputType, Field } from '@nestjs/graphql'

@InputType()
export class CreateTaskInput {
  @Field()
  title: string

  @Field({ nullable: true })
  iconUrl?: string

  @Field({ nullable: true })
  description?: string
}

@InputType()
export class UpdateTaskInput {
  @Field({ nullable: true })
  title?: string

  @Field({ nullable: true })
  iconUrl?: string

  @Field({ nullable: true })
  description?: string
}
