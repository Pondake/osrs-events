import { Injectable } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { PermissionKey } from './entities/permission.entity'

@Injectable()
export class PermissionsService {
  constructor(private prisma: PrismaService) {}

  /** Get all permissions for a user */
  getForUser(userId: string) {
    return this.prisma.userPermission.findMany({ where: { userId } })
  }

  /** Check if a user has a specific permission */
  async has(userId: string, permissionKey: PermissionKey): Promise<boolean> {
    const perm = await this.prisma.userPermission.findUnique({
      where: { userId_permissionKey: { userId, permissionKey } },
    })
    return !!perm
  }

  /** Check if a user has admin role (shortcut — admins bypass all permission checks) */
  async isAdmin(userId: string): Promise<boolean> {
    const userRole = await this.prisma.userRole.findFirst({
      where: { userId, role: { name: 'ADMIN' } },
      include: { role: true },
    })
    return !!userRole
  }

  /**
   * Returns true if the user is admin OR has the given permission.
   * Use this in services/guards where admins should bypass explicit grants.
   */
  async hasOrAdmin(userId: string, permissionKey: PermissionKey): Promise<boolean> {
    if (await this.isAdmin(userId)) return true
    return this.has(userId, permissionKey)
  }

  /** Grant a permission to a user (idempotent) */
  async grant(userId: string, permissionKey: PermissionKey) {
    return this.prisma.userPermission.upsert({
      where: { userId_permissionKey: { userId, permissionKey } },
      create: { userId, permissionKey },
      update: {},
    })
  }

  /** Revoke a permission from a user */
  async revoke(userId: string, permissionKey: PermissionKey) {
    return this.prisma.userPermission.deleteMany({ where: { userId, permissionKey } })
  }

  /** Get all permissions for multiple users at once (for admin list view) */
  getForUsers(userIds: string[]) {
    return this.prisma.userPermission.findMany({ where: { userId: { in: userIds } } })
  }
}
