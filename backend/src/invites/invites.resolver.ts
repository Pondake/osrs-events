import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { InvitesService } from './invites.service'
import { BoardInviteEntity } from './entities/invite.entity'
import { CreateInviteInput } from './dto/invite.input'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { CurrentUser } from '../auth/decorators/current-user.decorator'
import { UserEntity } from '../users/entities/user.entity'

@Resolver(() => BoardInviteEntity)
export class InvitesResolver {
  constructor(private invitesService: InvitesService) {}

  @UseGuards(JwtAuthGuard)
  @Mutation(() => BoardInviteEntity)
  createInvite(@Args('input') input: CreateInviteInput, @CurrentUser() user: UserEntity) {
    return this.invitesService.createInvite(input, user.id)
  }

  @UseGuards(JwtAuthGuard)
  @Mutation(() => Boolean)
  revokeInvite(
    @Args('inviteId', { type: () => ID }) inviteId: string,
    @CurrentUser() user: UserEntity
  ) {
    return this.invitesService.revokeInvite(inviteId, user.id)
  }

  @UseGuards(JwtAuthGuard)
  @Query(() => [BoardInviteEntity], { name: 'boardInvites' })
  boardInvites(
    @Args('boardId', { type: () => ID }) boardId: string,
    @CurrentUser() user: UserEntity
  ) {
    return this.invitesService.getInvitesByBoard(boardId, user.id)
  }
}
