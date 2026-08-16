import { ObjectType, Field, ID, registerEnumType } from '@nestjs/graphql'

/**
 * Granular permission keys — extend this enum as new permissions are added.
 * Stored as strings in the UserPermission table.
 */
export enum PermissionKey {
  CAN_CREATE_BOARDS = 'canCreateBoards',
  CAN_CREATE_TILES = 'canCreateTiles',
}

registerEnumType(PermissionKey, {
  name: 'PermissionKey',
  description: 'Granular permissions that can be granted to individual users',
})

@ObjectType()
export class UserPermissionEntity {
  @Field(() => ID)
  id: string

  @Field(() => ID)
  userId: string

  @Field(() => PermissionKey)
  permissionKey: PermissionKey

  @Field()
  createdAt: Date
}
