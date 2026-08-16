import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { TeamsService } from './teams.service'
import { TeamEntity } from './entities/team.entity'
import { CreateTeamInput, UpdateTeamInput, AddTeamMemberInput } from './dto/team.input'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { RolesGuard } from '../auth/guards/roles.guard'
import { Roles } from '../auth/decorators/roles.decorator'
import { CurrentUser } from '../auth/decorators/current-user.decorator'
import { UserEntity } from '../users/entities/user.entity'

@Resolver(() => TeamEntity)
export class TeamsResolver {
  constructor(private teamsService: TeamsService) {}

  /** List teams — admins see all; TEAM_MANAGERs see only teams in their Discord guilds */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'EDITOR', 'TEAM_MANAGER')
  @Query(() => [TeamEntity], { name: 'teams' })
  findAll(@CurrentUser() user: UserEntity) {
    const isAdmin = user.userRoles.some(r => r.role.name === 'ADMIN')
    return this.teamsService.findAll(user.id, isAdmin)
  }

  /** Get a single team by id */
  @UseGuards(JwtAuthGuard)
  @Query(() => TeamEntity, { name: 'team', nullable: true })
  findOne(@Args('id', { type: () => ID }) id: string) {
    return this.teamsService.findById(id)
  }

  /** Teams the current user belongs to */
  @UseGuards(JwtAuthGuard)
  @Query(() => [TeamEntity], { name: 'myTeams' })
  myTeams(@CurrentUser() user: UserEntity) {
    return this.teamsService.findByUser(user.id)
  }

  /** Create a team (admin or TEAM_MANAGER) — creator is auto-added as first member */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'TEAM_MANAGER')
  @Mutation(() => TeamEntity)
  createTeam(@Args('input') input: CreateTeamInput, @CurrentUser() user: UserEntity) {
    return this.teamsService.create(input, user.id)
  }

  /** Update a team (admin or TEAM_MANAGER) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'TEAM_MANAGER')
  @Mutation(() => TeamEntity)
  updateTeam(
    @Args('id', { type: () => ID }) id: string,
    @Args('input') input: UpdateTeamInput,
  ) {
    return this.teamsService.update(id, input)
  }

  /** Delete a team (admin or TEAM_MANAGER) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'TEAM_MANAGER')
  @Mutation(() => TeamEntity)
  deleteTeam(@Args('id', { type: () => ID }) id: string) {
    return this.teamsService.delete(id)
  }

  /** Add a member to a team (admin or TEAM_MANAGER) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'TEAM_MANAGER')
  @Mutation(() => TeamEntity)
  addTeamMember(@Args('input') input: AddTeamMemberInput) {
    return this.teamsService.addMember(input.teamId, input.userId)
  }

  /** Remove a member from a team (admin or TEAM_MANAGER) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN', 'TEAM_MANAGER')
  @Mutation(() => TeamEntity)
  removeTeamMember(
    @Args('teamId', { type: () => ID }) teamId: string,
    @Args('userId', { type: () => ID }) userId: string,
  ) {
    return this.teamsService.removeMember(teamId, userId)
  }
}
