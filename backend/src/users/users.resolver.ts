import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { UsersService } from './users.service'
import { UserEntity } from './entities/user.entity'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { RolesGuard } from '../auth/guards/roles.guard'
import { Roles } from '../auth/decorators/roles.decorator'
import { CurrentUser } from '../auth/decorators/current-user.decorator'

@Resolver(() => UserEntity)
export class UsersResolver {
  constructor(private usersService: UsersService) {}

  /**
   * Get all users — optionally filtered by search term (admin only)
   */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Query(() => [UserEntity], { name: 'users' })
  findAll(@Args('search', { nullable: true }) search?: string) {
    return this.usersService.findAll(search)
  }

  /**
   * Get the current logged-in user
   */
  @UseGuards(JwtAuthGuard)
  @Query(() => UserEntity, { name: 'me', nullable: true })
  getMe(@CurrentUser() user: UserEntity) {
    return user
  }

  /**
   * Assign a role to a user (admin only)
   */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => UserEntity)
  async assignRole(
    @Args('userId', { type: () => ID }) userId: string,
    @Args('roleName') roleName: string
  ) {
    await this.usersService.assignRole(userId, roleName)
    return this.usersService.findById(userId)
  }

  /**
   * Update the current user's display nickname (any authenticated user)
   */
  @UseGuards(JwtAuthGuard)
  @Mutation(() => UserEntity)
  async updateProfile(
    @CurrentUser() user: UserEntity,
    @Args('nickname', { nullable: true }) nickname?: string,
  ) {
    return this.usersService.updateProfile(user.id, nickname ?? null)
  }

  /**
   * Remove a role from a user (admin only)
   */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => UserEntity)
  async removeRole(
    @Args('userId', { type: () => ID }) userId: string,
    @Args('roleName') roleName: string
  ) {
    await this.usersService.removeRole(userId, roleName)
    return this.usersService.findById(userId)
  }

  /**
   * Delete a user account (admin only).
   * Admins cannot delete other admins — remove their ADMIN role first.
   */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => Boolean)
  async deleteUser(
    @CurrentUser() currentUser: UserEntity,
    @Args('userId', { type: () => ID }) userId: string,
  ) {
    if (userId === currentUser.id) {
      throw new Error('You cannot delete your own account.')
    }
    await this.usersService.deleteUser(userId)
    return true
  }
}
