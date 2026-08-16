import { Injectable, ForbiddenException, NotFoundException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { InvitesService } from '../invites/invites.service'

@Injectable()
export class AccessService {
  constructor(
    private prisma: PrismaService,
    private invitesService: InvitesService
  ) {}

  /**
   * Check whether a user may join a board without yet having a BoardAccess record.
   * Board authors always pass regardless of access mode.
   */
  async canJoin(userId: string, boardId: string): Promise<{ allowed: boolean; reason?: string }> {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: { authors: true }
    })
    if (!board) throw new NotFoundException(`Board ${boardId} not found`)

    // Board authors bypass access checks
    if (board.authors.some(a => a.userId === userId)) {
      return { allowed: true }
    }

    if (board.accessMode === 'OPEN') {
      return { allowed: true }
    }

    if (board.accessMode === 'GUILD') {
      if (!board.requiredGuildId) return { allowed: true }
      const guild = await this.prisma.userGuild.findUnique({
        where: { userId_guildId: { userId, guildId: board.requiredGuildId } }
      })
      if (guild) return { allowed: true }
      return { allowed: false, reason: 'You must be a member of the required Discord server to join this board' }
    }

    if (board.accessMode === 'INVITE') {
      return { allowed: false, reason: 'This board requires an invite code or magic link' }
    }

    return { allowed: false, reason: 'Access denied' }
  }

  /**
   * Check whether a user already has confirmed access to a board.
   * True if: BoardAccess record exists, OR user is a board author, OR board is OPEN.
   */
  async hasAccess(userId: string, boardId: string): Promise<boolean> {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: { authors: true }
    })
    if (!board) return false
    if (board.accessMode === 'OPEN') return true
    if (board.authors.some(a => a.userId === userId)) return true

    const access = await this.prisma.boardAccess.findUnique({
      where: { boardId_userId: { boardId, userId } }
    })
    return access !== null
  }

  /**
   * Grant access to a board. For INVITE mode, tokenOrCode is required.
   * Returns the BoardAccess record (idempotent).
   */
  async joinBoard(userId: string, boardId: string, tokenOrCode?: string) {
    // Idempotent: return existing access
    const existing = await this.prisma.boardAccess.findUnique({
      where: { boardId_userId: { boardId, userId } }
    })
    if (existing) return existing

    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: { authors: true }
    })
    if (!board) throw new NotFoundException(`Board ${boardId} not found`)

    // Board authors are implicitly granted access — create record if not exists
    const isAuthor = board.authors.some(a => a.userId === userId)
    if (!isAuthor) {
      const check = await this.canJoin(userId, boardId)
      if (!check.allowed) throw new ForbiddenException(check.reason)
    }

    if (board.accessMode === 'INVITE' && tokenOrCode) {
      // InvitesService.useInvite creates the BoardAccess record
      return this.invitesService.useInvite(boardId, tokenOrCode, userId)
    }

    // OPEN, GUILD, or board author
    return this.prisma.boardAccess.create({
      data: {
        boardId,
        userId,
        accessMode: board.accessMode as 'OPEN' | 'GUILD' | 'INVITE'
      }
    })
  }

  async getMyBoardAccess(userId: string, boardId: string) {
    return this.prisma.boardAccess.findUnique({
      where: { boardId_userId: { boardId, userId } }
    })
  }
}
