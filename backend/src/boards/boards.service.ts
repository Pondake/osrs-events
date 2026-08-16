import { ForbiddenException, Injectable, NotFoundException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { CreateBoardInput, UpdateBoardInput } from './dto/create-board.input'

/** Common include for board queries — includes authors + boardTeams with team summary */
const BOARD_INCLUDE = {
  authors: {
    include: {
      user: {
        include: {
          userRoles: { include: { role: true } }
        }
      }
    }
  },
  boardTeams: {
    include: {
      team: true
    }
  }
} as const

@Injectable()
export class BoardsService {
  constructor(private prisma: PrismaService) {}

  /** Public board list — only listed boards */
  async findAll() {
    return this.prisma.board.findMany({
      where: { isListed: true },
      include: BOARD_INCLUDE,
      orderBy: { startDate: 'desc' }
    })
  }

  /** Admin board list — all boards regardless of isListed */
  async findAllAdmin() {
    return this.prisma.board.findMany({
      include: BOARD_INCLUDE,
      orderBy: { startDate: 'desc' }
    })
  }

  async findById(id: string) {
    const board = await this.prisma.board.findUnique({
      where: { id },
      include: {
        ...BOARD_INCLUDE,
        tiles: {
          include: { task: true },
          orderBy: { position: 'asc' }
        }
      }
    })

    if (!board) throw new NotFoundException(`Bord met id ${id} niet gevonden`)
    return board
  }

  async create(input: CreateBoardInput, creatorId: string) {
    // Creator is always isOwner; additional authors are co-editors (isOwner: false)
    const extraAuthorIds = Array.from(
      new Set(input.authorIds.filter((id) => id !== creatorId))
    )

    return this.prisma.board.create({
      data: {
        title: input.title,
        description: input.description,
        startDate: input.startDate,
        endDate: input.endDate,
        size: input.size,
        mode: input.mode ?? 'SOLO',
        diceRollLimit: input.diceRollLimit ?? null,
        isListed: input.isListed ?? true,
        accessMode: input.accessMode ?? 'OPEN',
        requiredGuildId: input.requiredGuildId ?? null,
        authors: {
          create: [
            { userId: creatorId, isOwner: true },
            ...extraAuthorIds.map((userId) => ({ userId, isOwner: false })),
          ],
        },
      },
      include: BOARD_INCLUDE
    })
  }

  async update(id: string, input: UpdateBoardInput) {
    const { authorIds, ...rest } = input

    return this.prisma.$transaction(async (tx) => {
      if (authorIds !== undefined) {
        // Preserve existing owners — they cannot be replaced via bulk update
        const ownerRecords = await tx.boardAuthor.findMany({
          where: { boardId: id, isOwner: true },
        })
        const ownerIds = ownerRecords.map((o) => o.userId)

        // Remove all non-owner authors, then recreate the desired set
        await tx.boardAuthor.deleteMany({ where: { boardId: id, isOwner: false } })

        const newNonOwnerIds = authorIds.filter((uid) => !ownerIds.includes(uid))
        if (newNonOwnerIds.length > 0) {
          await tx.boardAuthor.createMany({
            data: newNonOwnerIds.map((userId) => ({ boardId: id, userId, isOwner: false })),
            skipDuplicates: true,
          })
        }
      }

      return tx.board.update({
        where: { id },
        data: rest,
        include: BOARD_INCLUDE
      })
    })
  }

  /** Add a single co-author (isOwner: false) to a board. Idempotent. */
  async addAuthor(boardId: string, userId: string) {
    const board = await this.prisma.board.findUnique({ where: { id: boardId } })
    if (!board) throw new NotFoundException(`Board ${boardId} not found`)

    return this.prisma.boardAuthor.upsert({
      where: { boardId_userId: { boardId, userId } },
      update: {},
      create: { boardId, userId, isOwner: false },
      include: {
        user: {
          include: { userRoles: { include: { role: true } } }
        }
      }
    })
  }

  /** Remove a co-author from a board. Throws if the author is the owner. */
  async removeAuthor(boardId: string, userId: string): Promise<boolean> {
    const author = await this.prisma.boardAuthor.findUnique({
      where: { boardId_userId: { boardId, userId } },
    })
    if (!author) throw new NotFoundException(`Author not found on board ${boardId}`)
    if (author.isOwner) throw new ForbiddenException('The board owner cannot be removed')

    await this.prisma.boardAuthor.delete({
      where: { boardId_userId: { boardId, userId } },
    })
    return true
  }

  /** Check whether a user is the owner (isOwner: true) of a board. */
  async isBoardOwner(boardId: string, userId: string): Promise<boolean> {
    const record = await this.prisma.boardAuthor.findUnique({
      where: { boardId_userId: { boardId, userId } },
    })
    return record?.isOwner ?? false
  }

  /** Add a team to a board (TEAM mode). Idempotent. */
  async addTeamToBoard(boardId: string, teamId: string) {
    const board = await this.prisma.board.findUnique({ where: { id: boardId } })
    if (!board) throw new NotFoundException(`Board ${boardId} not found`)

    return this.prisma.boardTeam.upsert({
      where: { boardId_teamId: { boardId, teamId } },
      update: {},
      create: { boardId, teamId },
      include: { team: true }
    })
  }

  /** Remove a team from a board. */
  async removeTeamFromBoard(boardId: string, teamId: string): Promise<boolean> {
    const entry = await this.prisma.boardTeam.findUnique({
      where: { boardId_teamId: { boardId, teamId } },
    })
    if (!entry) throw new NotFoundException(`Team ${teamId} not on board ${boardId}`)

    await this.prisma.boardTeam.delete({
      where: { boardId_teamId: { boardId, teamId } },
    })
    return true
  }

  async delete(id: string) {
    return this.prisma.board.delete({ where: { id } })
  }
}
