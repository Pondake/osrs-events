import { ObjectType, Field, ID, registerEnumType } from '@nestjs/graphql'

export enum BoardAccessMode {
  OPEN = 'OPEN',
  GUILD = 'GUILD',
  INVITE = 'INVITE',
}

registerEnumType(BoardAccessMode, { name: 'BoardAccessMode' })

@ObjectType()
export class BoardAccessEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  boardId: string

  @Field(() => ID)
  userId: string

  @Field(() => ID, { nullable: true })
  inviteId?: string

  @Field(() => BoardAccessMode)
  accessMode: BoardAccessMode

  @Field()
  joinedAt: Date
}
