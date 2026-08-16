import { Injectable, ForbiddenException, NotFoundException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'

interface UpsertDiscordUserDto {
  discordId: string
  discordUsername: string
  avatarUrl: string | null
}

export interface DiscordGuildDto {
  id: string
  name: string
  icon: string | null
}

@Injectable()
export class UsersService {
  constructor(private prisma: PrismaService) {}

  /**
   * Find a user by UUID including their roles
   */
  async findById(id: string) {
    return this.prisma.user.findUnique({
      where: { id },
      include: {
        userRoles: { include: { role: true } },
        userGuilds: true
      }
    })
  }

  /**
   * Find a user by Discord ID
   */
  async findByDiscordId(discordId: string) {
    return this.prisma.user.findUnique({
      where: { discordId },
      include: {
        userRoles: {
          include: { role: true }
        }
      }
    })
  }

  /**
   * Create or update a user from Discord OAuth data.
   * New users automatically get the PLAYER role.
   */
  async upsertFromDiscord(dto: UpsertDiscordUserDto) {
    // Ensure the PLAYER role exists
    const playerRole = await this.prisma.role.upsert({
      where: { name: 'PLAYER' },
      update: {},
      create: { name: 'PLAYER', description: 'Standaard spelerrol' }
    })

    const user = await this.prisma.user.upsert({
      where: { discordId: dto.discordId },
      update: {
        discordUsername: dto.discordUsername,
        avatarUrl: dto.avatarUrl
      },
      create: {
        discordId: dto.discordId,
        discordUsername: dto.discordUsername,
        avatarUrl: dto.avatarUrl,
        userRoles: {
          create: {
            roleId: playerRole.id
          }
        }
      },
      include: {
        userRoles: {
          include: { role: true }
        }
      }
    })

    return user
  }

  /**
   * Replace a user's cached Discord guild memberships (delete-all + re-insert in transaction).
   */
  async syncGuilds(userId: string, guilds: DiscordGuildDto[]) {
    await this.prisma.$transaction([
      this.prisma.userGuild.deleteMany({ where: { userId } }),
      this.prisma.userGuild.createMany({
        data: guilds.map(g => ({
          userId,
          guildId: g.id,
          guildName: g.name,
          guildIcon: g.icon ?? null,
          syncedAt: new Date()
        }))
      })
    ])
  }

  /**
   * List all users with roles — optional case-insensitive search by username,
   * optional limit to N most recently joined users.
   */
  async findAll(search?: string, limit?: number) {
    return this.prisma.user.findMany({
      where: search
        ? { discordUsername: { contains: search, mode: 'insensitive' } }
        : undefined,
      include: {
        userRoles: {
          include: { role: true }
        }
      },
      orderBy: { createdAt: 'desc' },
      ...(limit ? { take: limit } : {}),
    })
  }

  /**
   * Assign a role to a user
   */
  async assignRole(userId: string, roleName: string) {
    const role = await this.prisma.role.upsert({
      where: { name: roleName },
      update: {},
      create: { name: roleName }
    })

    return this.prisma.userRole.upsert({
      where: { userId_roleId: { userId, roleId: role.id } },
      update: {},
      create: { userId, roleId: role.id }
    })
  }

  /**
   * Update the user's optional display nickname
   */
  async updateProfile(userId: string, nickname: string | null) {
    return this.prisma.user.update({
      where: { id: userId },
      data: { nickname },
      include: {
        userRoles: {
          include: { role: true }
        }
      }
    })
  }

  /**
   * Delete a user account.
   * Admins cannot be deleted — assign/remove the ADMIN role first.
   */
  async deleteUser(targetUserId: string): Promise<void> {
    const target = await this.findById(targetUserId)
    if (!target) throw new NotFoundException('User not found')

    const isAdmin = target.userRoles.some(ur => ur.role.name === 'ADMIN')
    if (isAdmin) {
      throw new ForbiddenException('Admin users cannot be deleted. Remove the ADMIN role first.')
    }

    await this.prisma.user.delete({ where: { id: targetUserId } })
  }

  /**
   * Remove a role from a user
   */
  async removeRole(userId: string, roleName: string) {
    const role = await this.prisma.role.findUnique({ where: { name: roleName } })
    if (!role) return

    return this.prisma.userRole.deleteMany({
      where: { userId, roleId: role.id }
    })
  }
}
