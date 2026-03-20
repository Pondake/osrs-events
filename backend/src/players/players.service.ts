import { Injectable, BadRequestException, NotFoundException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import type { Board, Tile } from '../generated/prisma/index.js'

@Injectable()
export class PlayersService {
  constructor(private prisma: PrismaService) {}

  /**
   * Get or create a player's board state
   */
  async getOrCreatePlayerBoard(userId: string, boardId: string) {
    const existing = await this.prisma.playerBoard.findUnique({
      where: { userId_boardId: { userId, boardId } },
      include: {
        completedTiles: true
      }
    })

    if (existing) return existing

    return this.prisma.playerBoard.create({
      data: { userId, boardId, currentPosition: 0 },
      include: { completedTiles: true }
    })
  }

  /**
   * Get all player boards for the current user (with board info)
   */
  async getMyPlayerBoards(userId: string) {
    return this.prisma.playerBoard.findMany({
      where: { userId },
      include: {
        completedTiles: true,
        board: true,
      },
      orderBy: { createdAt: 'desc' },
    })
  }

  /**
   * Get all player boards for a given board
   */
  async getPlayerBoardsByBoard(boardId: string) {
    return this.prisma.playerBoard.findMany({
      where: { boardId },
      include: {
        completedTiles: true,
        user: {
          include: {
            userRoles: { include: { role: true } }
          }
        }
      }
    })
  }

  /**
   * Roll the dice and move the player
   */
  async rollDice(userId: string, boardId: string) {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: {
        tiles: { orderBy: { position: 'asc' } }
      }
    }) as (Board & { tiles: Tile[] }) | null

    if (!board) throw new NotFoundException('Bord niet gevonden')

    const playerBoard = await this.getOrCreatePlayerBoard(userId, boardId)
    const totalTiles = board.tiles.length
    const maxPosition = totalTiles - 1

    // Check roll limit
    if (board.diceRollLimit !== null) {
      const today = new Date()
      today.setHours(0, 0, 0, 0)

      const lastRoll = playerBoard.lastRollDate
      const isToday = lastRoll ? new Date(lastRoll) >= today : false
      const rollsToday = isToday ? playerBoard.diceRollsToday : 0

      if (rollsToday >= board.diceRollLimit) {
        throw new BadRequestException(`Je hebt het maximaal aantal dobbelworpen bereikt (${board.diceRollLimit}/dag)`)
      }
    }

    // Roll d6
    const rolled = Math.floor(Math.random() * 6) + 1
    const previousPosition = playerBoard.currentPosition

    let newPosition = Math.min(previousPosition + rolled, maxPosition)
    const landedOn = newPosition

    // Check if tile has snake or ladder
    const tile = board.tiles.find((t) => t.position === newPosition) as Tile | undefined
    let jump: string | undefined

    if (tile && tile.targetPosition !== null && tile.targetPosition !== undefined) {
      if (tile.type === 'SNAKE') {
        newPosition = tile.targetPosition
        jump = 'snake'
      }
      else if (tile.type === 'LADDER') {
        newPosition = tile.targetPosition
        jump = 'ladder'
      }
    }

    // Update player board
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const lastRoll = playerBoard.lastRollDate
    const isToday = lastRoll ? new Date(lastRoll) >= today : false

    const updatedPlayerBoard = await this.prisma.playerBoard.update({
      where: { id: playerBoard.id },
      data: {
        currentPosition: newPosition,
        diceRollsToday: isToday ? playerBoard.diceRollsToday + 1 : 1,
        lastRollDate: new Date()
      },
      include: { completedTiles: true }
    })

    return {
      rolled,
      previousPosition,
      newPosition,
      landedOn,
      jump,
      playerBoard: updatedPlayerBoard
    }
  }

  /**
   * Mark a tile as completed manually
   */
  async completeTile(userId: string, boardId: string, tileId: string) {
    const playerBoard = await this.getOrCreatePlayerBoard(userId, boardId)

    const completed = await this.prisma.completedTile.upsert({
      where: {
        playerBoardId_tileId: {
          playerBoardId: playerBoard.id,
          tileId
        }
      },
      update: {},
      create: {
        playerBoardId: playerBoard.id,
        tileId,
        completedVia: 'MANUAL'
      }
    })

    return this.prisma.playerBoard.findUnique({
      where: { id: playerBoard.id },
      include: { completedTiles: true }
    })
  }

  /**
   * Remove tile completion (unmark)
   */
  async uncompleteTile(userId: string, boardId: string, tileId: string) {
    const playerBoard = await this.prisma.playerBoard.findUnique({
      where: { userId_boardId: { userId, boardId } }
    })

    if (!playerBoard) return null

    await this.prisma.completedTile.deleteMany({
      where: { playerBoardId: playerBoard.id, tileId }
    })

    return this.prisma.playerBoard.findUnique({
      where: { id: playerBoard.id },
      include: { completedTiles: true }
    })
  }
}
