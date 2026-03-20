import { Injectable, NotFoundException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { CreateBoardInput, UpdateBoardInput } from './dto/create-board.input'

@Injectable()
export class BoardsService {
  constructor(private prisma: PrismaService) {}

  async findAll() {
    return this.prisma.board.findMany({
      include: {
        authors: {
          include: {
            user: {
              include: {
                userRoles: { include: { role: true } }
              }
            }
          }
        }
      },
      orderBy: { startDate: 'desc' }
    })
  }

  async findById(id: string) {
    const board = await this.prisma.board.findUnique({
      where: { id },
      include: {
        authors: {
          include: {
            user: {
              include: {
                userRoles: { include: { role: true } }
              }
            }
          }
        },
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
    // Build the full list of authors (include creator always)
    const authorIds = Array.from(new Set([...input.authorIds, creatorId]))

    return this.prisma.board.create({
      data: {
        title: input.title,
        description: input.description,
        startDate: input.startDate,
        endDate: input.endDate,
        size: input.size,
        diceRollLimit: input.diceRollLimit ?? null,
        authors: {
          create: authorIds.map((userId) => ({ userId }))
        }
      },
      include: {
        authors: {
          include: {
            user: {
              include: {
                userRoles: { include: { role: true } }
              }
            }
          }
        }
      }
    })
  }

  async update(id: string, input: UpdateBoardInput) {
    const { authorIds, ...rest } = input

    return this.prisma.$transaction(async (tx) => {
      if (authorIds !== undefined) {
        await tx.boardAuthor.deleteMany({ where: { boardId: id } })
        await tx.boardAuthor.createMany({
          data: authorIds.map((userId) => ({ boardId: id, userId }))
        })
      }

      return tx.board.update({
        where: { id },
        data: rest,
        include: {
          authors: {
            include: {
              user: {
                include: {
                  userRoles: { include: { role: true } }
                }
              }
            }
          }
        }
      })
    })
  }

  async delete(id: string) {
    return this.prisma.board.delete({ where: { id } })
  }
}
