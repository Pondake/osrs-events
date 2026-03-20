import { Injectable } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { UpsertTileInput } from './dto/upsert-tile.input'

@Injectable()
export class TilesService {
  constructor(private prisma: PrismaService) {}

  async findByBoard(boardId: string) {
    return this.prisma.tile.findMany({
      where: { boardId },
      include: { task: true },
      orderBy: { position: 'asc' }
    })
  }

  async findById(id: string) {
    return this.prisma.tile.findUnique({
      where: { id },
      include: { task: true }
    })
  }

  /**
   * Upsert a tile at a specific position on a board.
   * Creates if not exists, updates if it does.
   */
  async upsert(input: UpsertTileInput) {
    const existing = await this.prisma.tile.findUnique({
      where: {
        boardId_position: {
          boardId: input.boardId,
          position: input.position
        }
      }
    })

    if (existing) {
      return this.prisma.tile.update({
        where: { id: existing.id },
        data: {
          taskId: input.taskId,
          titleOverride: input.titleOverride,
          type: input.type,
          targetPosition: input.targetPosition
        },
        include: { task: true }
      })
    }

    return this.prisma.tile.create({
      data: {
        boardId: input.boardId,
        position: input.position,
        taskId: input.taskId,
        titleOverride: input.titleOverride,
        type: input.type ?? 'NORMAL',
        targetPosition: input.targetPosition
      },
      include: { task: true }
    })
  }

  async delete(id: string) {
    return this.prisma.tile.delete({ where: { id } })
  }

  async clearSnakeLadder(id: string) {
    return this.prisma.tile.update({
      where: { id },
      data: { type: 'NORMAL', targetPosition: null },
      include: { task: true }
    })
  }
}
