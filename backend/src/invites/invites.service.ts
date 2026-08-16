import { Injectable, NotFoundException, ForbiddenException, BadRequestException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { BoardsService } from '../boards/boards.service'
import type { CreateInviteInput } from './dto/invite.input'

@Injectable()
export class InvitesService {
  constructor(
    private prisma: PrismaService,
    private boardsService: BoardsService
  ) {}

  async createInvite(input: CreateInviteInput, creatorId: string) {
    await this.assertOwnerOrAdmin(input.boardId, creatorId)
    const shortCode = await this.generateUniqueShortCode(input.boardId)
    return this.prisma.boardInvite.create({
      data: {
        boardId: input.boardId,
        shortCode,
        label: input.label,
        expiresAt: input.expiresAt,
        maxUses: input.maxUses,
        createdBy: creatorId
      }
    })
  }

  /**
   * Validate and consume an invite (by full UUID token or 6-char shortCode).
   * Creates and returns the BoardAccess record. Idempotent — returns existing
   * access if the user already joined via this board.
   */
  async useInvite(boardId: string, tokenOrCode: string, userId: string) {
    // Check if user already has access (idempotent)
    const existing = await this.prisma.boardAccess.findUnique({
      where: { boardId_userId: { boardId, userId } }
    })
    if (existing) return existing

    const normalised = tokenOrCode.trim().toUpperCase()
    const invite = await this.prisma.boardInvite.findFirst({
      where: {
        boardId,
        OR: [
          { token: tokenOrCode },
          { shortCode: normalised }
        ]
      }
    })

    if (!invite) throw new NotFoundException('Invite not found')
    if (invite.expiresAt && invite.expiresAt < new Date()) {
      throw new BadRequestException('This invite has expired')
    }
    if (invite.maxUses !== null && invite.useCount >= invite.maxUses) {
      throw new BadRequestException('This invite has reached its maximum uses')
    }

    const [access] = await this.prisma.$transaction([
      this.prisma.boardAccess.create({
        data: { boardId, userId, inviteId: invite.id, accessMode: 'INVITE' }
      }),
      this.prisma.boardInvite.update({
        where: { id: invite.id },
        data: { useCount: { increment: 1 } }
      })
    ])

    return access
  }

  async revokeInvite(inviteId: string, requesterId: string) {
    const invite = await this.prisma.boardInvite.findUnique({ where: { id: inviteId } })
    if (!invite) throw new NotFoundException('Invite not found')
    await this.assertOwnerOrAdmin(invite.boardId, requesterId)
    await this.prisma.boardInvite.delete({ where: { id: inviteId } })
    return true
  }

  async getInvitesByBoard(boardId: string, requesterId: string) {
    await this.assertOwnerOrAdmin(boardId, requesterId)
    return this.prisma.boardInvite.findMany({
      where: { boardId },
      orderBy: { createdAt: 'desc' }
    })
  }

  private async assertOwnerOrAdmin(boardId: string, userId: string) {
    const isOwner = await this.boardsService.isBoardOwner(boardId, userId)
    if (isOwner) return

    const user = await this.prisma.user.findUnique({
      where: { id: userId },
      include: { userRoles: { include: { role: true } } }
    })
    const isAdmin = user?.userRoles.some(r => r.role.name === 'ADMIN')
    if (!isAdmin) throw new ForbiddenException('Only board owners and admins can manage invites')
  }

  private async generateUniqueShortCode(boardId: string): Promise<string> {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    for (let attempt = 0; attempt < 10; attempt++) {
      const code = Array.from({ length: 6 }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
      const exists = await this.prisma.boardInvite.findUnique({
        where: { boardId_shortCode: { boardId, shortCode: code } }
      })
      if (!exists) return code
    }
    throw new Error('Failed to generate unique short code after 10 attempts')
  }
}
