import { InputType, Field, Int, ID } from '@nestjs/graphql'
import { TileType } from '../entities/tile.entity'

@InputType()
export class UpsertTileInput {
  @Field(() => ID)
  boardId: string

  @Field(() => Int)
  position: number

  @Field(() => ID, { nullable: true })
  taskId?: string

  @Field({ nullable: true })
  titleOverride?: string

  @Field(() => TileType, { nullable: true })
  type?: TileType

  @Field(() => Int, { nullable: true })
  targetPosition?: number
}
