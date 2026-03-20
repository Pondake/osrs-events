import { ObjectType, Field, ID, Int, registerEnumType } from '@nestjs/graphql'
import { TaskEntity } from '../../tasks/entities/task.entity'

export enum TileType {
  NORMAL = 'NORMAL',
  SNAKE = 'SNAKE',
  LADDER = 'LADDER'
}

registerEnumType(TileType, { name: 'TileType' })

@ObjectType()
export class TileEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  boardId: string

  @Field(() => Int)
  position: number

  @Field(() => TaskEntity, { nullable: true })
  task?: TaskEntity

  @Field({ nullable: true })
  titleOverride?: string

  @Field(() => TileType)
  type: TileType

  @Field(() => Int, { nullable: true })
  targetPosition?: number

  /** The effective display title: titleOverride or task.title */
  @Field(() => String, { nullable: true })
  get displayTitle(): string | undefined {
    return this.titleOverride ?? (this.task as TaskEntity | undefined)?.title
  }

  /** The effective icon URL: from task */
  @Field(() => String, { nullable: true })
  get iconUrl(): string | undefined {
    return (this.task as TaskEntity | undefined)?.iconUrl
  }

  @Field()
  createdAt: Date

  @Field()
  updatedAt: Date
}
