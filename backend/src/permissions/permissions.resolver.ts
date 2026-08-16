import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { PermissionsService } from './permissions.service'
import { UserPermissionEntity, PermissionKey } from './entities/permission.entity'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { RolesGuard } from '../auth/guards/roles.guard'
import { Roles } from '../auth/decorators/roles.decorator'
import { CurrentUser } from '../auth/decorators/current-user.decorator'
import { UserEntity } from '../users/entities/user.entity'

@Resolver()
export class PermissionsResolver {
  constructor(private permissionsService: PermissionsService) {}

  /** Get the current user's permissions */
  @UseGuards(JwtAuthGuard)
  @Query(() => [UserPermissionEntity], { name: 'myPermissions' })
  myPermissions(@CurrentUser() user: UserEntity) {
    return this.permissionsService.getForUser(user.id)
  }

  /** Get all permissions for a specific user (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Query(() => [UserPermissionEntity], { name: 'userPermissions' })
  userPermissions(@Args('userId', { type: () => ID }) userId: string) {
    return this.permissionsService.getForUser(userId)
  }

  /** Grant a permission to a user (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => UserPermissionEntity)
  grantPermission(
    @Args('userId', { type: () => ID }) userId: string,
    @Args('permissionKey', { type: () => PermissionKey }) permissionKey: PermissionKey,
  ) {
    return this.permissionsService.grant(userId, permissionKey)
  }

  /** Revoke a permission from a user (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => Boolean)
  async revokePermission(
    @Args('userId', { type: () => ID }) userId: string,
    @Args('permissionKey', { type: () => PermissionKey }) permissionKey: PermissionKey,
  ) {
    await this.permissionsService.revoke(userId, permissionKey)
    return true
  }
}
