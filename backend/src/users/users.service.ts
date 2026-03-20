import { Injectable } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'

interface UpsertDiscordUserDto {
  discordId: string
  discordUsername: string
  avatarUrl: string | null
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
        userRoles: {
          include: { role: true }
        }
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
   * List all users with roles — optional case-insensitive search by username
   */
  async findAll(search?: string) {
    return this.prisma.user.findMany({
      where: search
        ? { discordUsername: { contains: search, mode: 'insensitive' } }
        : undefined,
      include: {
        userRoles: {
          include: { role: true }
        }
      },
      orderBy: { createdAt: 'desc' }
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
