import { Injectable, NotFoundException, ForbiddenException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { CreateTeamInput, UpdateTeamInput } from './dto/team.input'

const TEAM_INCLUDE = {
  members: {
    include: {
      user: {
        include: { userRoles: { include: { role: true } } },
      },
    },
    orderBy: { createdAt: 'asc' as const },
  },
}

@Injectable()
export class TeamsService {
  constructor(private prisma: PrismaService) {}

  findAll() {
    return this.prisma.team.findMany({
      include: TEAM_INCLUDE,
      orderBy: { name: 'asc' },
    })
  }

  findById(id: string) {
    return this.prisma.team.findUnique({ where: { id }, include: TEAM_INCLUDE })
  }

  /** Teams the given user belongs to */
  findByUser(userId: string) {
    return this.prisma.team.findMany({
      where: { members: { some: { userId } } },
      include: TEAM_INCLUDE,
      orderBy: { name: 'asc' },
    })
  }

  async create(input: CreateTeamInput, creatorId: string) {
    const team = await this.prisma.team.create({
      data: { name: input.name, iconUrl: input.iconUrl },
    })
    // Auto-add the creator as the first member
    await this.prisma.teamMember.create({
      data: { teamId: team.id, userId: creatorId },
    })
    return this.prisma.team.findUnique({ where: { id: team.id }, include: TEAM_INCLUDE })
  }

  async update(id: string, input: UpdateTeamInput) {
    await this.findByIdOrThrow(id)
    return this.prisma.team.update({
      where: { id },
      data: { name: input.name, iconUrl: input.iconUrl },
      include: TEAM_INCLUDE,
    })
  }

  async delete(id: string) {
    await this.findByIdOrThrow(id)
    return this.prisma.team.delete({ where: { id }, include: TEAM_INCLUDE })
  }

  async addMember(teamId: string, userId: string) {
    await this.findByIdOrThrow(teamId)
    // upsert to be idempotent
    await this.prisma.teamMember.upsert({
      where: { teamId_userId: { teamId, userId } },
      create: { teamId, userId },
      update: {},
    })
    return this.findById(teamId)
  }

  async removeMember(teamId: string, userId: string) {
    await this.findByIdOrThrow(teamId)
    await this.prisma.teamMember.deleteMany({ where: { teamId, userId } })
    return this.findById(teamId)
  }

  /** Guard helper: is the user a TEAM_MANAGER for the given team? */
  async assertManagerOrAdmin(teamId: string, userId: string) {
    const member = await this.prisma.teamMember.findUnique({
      where: { teamId_userId: { teamId, userId } },
      include: { user: { include: { userRoles: { include: { role: true } } } } },
    })
    const isAdmin = member?.user.userRoles.some(r => r.role.name === 'ADMIN')
    const isManager = member?.user.userRoles.some(r => r.role.name === 'TEAM_MANAGER')
    if (!isAdmin && !isManager) {
      throw new ForbiddenException('Only admins and team managers can manage team membership')
    }
  }

  private async findByIdOrThrow(id: string) {
    const team = await this.prisma.team.findUnique({ where: { id } })
    if (!team) throw new NotFoundException(`Team ${id} not found`)
    return team
  }
}
