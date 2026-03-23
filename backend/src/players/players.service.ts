import { Injectable, BadRequestException, NotFoundException } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import type { Board, Tile } from '../generated/prisma/index.js'

/** Common include for PlayerBoard queries */
const PLAYER_BOARD_INCLUDE = {
  completedTiles: true,
  user: {
    include: {
      userRoles: { include: { role: true } }
    }
  },
  team: true,
} as const

@Injectable()
export class PlayersService {
  constructor(private prisma: PrismaService) {}

  /**
   * Resolve the correct PlayerBoard for a user + board combination.
   * - SOLO boards: one PlayerBoard per user (existing behaviour)
   * - TEAM boards: one shared PlayerBoard per team. Returns null if the user
   *   has no team assigned to this board.
   */
  async getOrCreatePlayerBoard(userId: string, boardId: string) {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: {
        boardTeams: { include: { team: { include: { members: true } } } }
      }
    })
    if (!board) throw new NotFoundException(`Board ${boardId} not found`)

    if (board.mode === 'TEAM') {
      // Find which of the board's teams this user belongs to
      const boardTeamEntry = board.boardTeams.find(bt =>
        bt.team.members.some(m => m.userId === userId)
      )
      if (!boardTeamEntry) return null // user has no team on this TEAM board

      const teamId = boardTeamEntry.teamId

      // Look up or create the single shared PlayerBoard for this team on this board
      const existing = await this.prisma.playerBoard.findFirst({
        where: { teamId, boardId },
        include: PLAYER_BOARD_INCLUDE,
      })
      if (existing) return existing

      return this.prisma.playerBoard.create({
        data: { userId, boardId, teamId, currentPosition: 0 },
        include: PLAYER_BOARD_INCLUDE,
      })
    }

    // SOLO mode — original behaviour
    const existing = await this.prisma.playerBoard.findUnique({
      where: { userId_boardId: { userId, boardId } },
      include: PLAYER_BOARD_INCLUDE,
    })
    if (existing) return existing

    return this.prisma.playerBoard.create({
      data: { userId, boardId, currentPosition: 0 },
      include: PLAYER_BOARD_INCLUDE,
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
        team: true,
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
      include: PLAYER_BOARD_INCLUDE,
    })
  }

  /**
   * Resolve the PlayerBoard to operate on — respects TEAM mode.
   * Throws if not found; returns null for TEAM boards where user has no team.
   */
  private async resolvePlayerBoard(userId: string, boardId: string) {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: {
        tiles: { orderBy: { position: 'asc' } },
        boardTeams: { include: { team: { include: { members: true } } } }
      }
    })
    if (!board) throw new NotFoundException('Bord niet gevonden')

    let playerBoardId: string | null = null

    if (board.mode === 'TEAM') {
      const boardTeamEntry = board.boardTeams.find(bt =>
        bt.team.members.some(m => m.userId === userId)
      )
      if (!boardTeamEntry) return { board, playerBoard: null }

      const teamId = boardTeamEntry.teamId
      const pb = await this.prisma.playerBoard.findFirst({
        where: { teamId, boardId },
        include: PLAYER_BOARD_INCLUDE,
      })
      return { board, playerBoard: pb }
    }

    const pb = await this.prisma.playerBoard.findUnique({
      where: { userId_boardId: { userId, boardId } },
      include: PLAYER_BOARD_INCLUDE,
    })
    return { board, playerBoard: pb }
  }

  /**
   * Roll the dice and move the player (or the team's shared board in TEAM mode)
   */
  async rollDice(userId: string, boardId: string) {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: {
        tiles: { orderBy: { position: 'asc' } },
        boardTeams: { include: { team: { include: { members: true } } } }
      }
    }) as (Board & { tiles: Tile[]; mode: string; boardTeams: any[] }) | null

    if (!board) throw new NotFoundException('Bord niet gevonden')

    const playerBoard = await this.getOrCreatePlayerBoard(userId, boardId)
    if (!playerBoard) throw new BadRequestException('Je hebt geen team op dit bord')

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

    await this.prisma.playerBoard.update({
      where: { id: playerBoard.id },
      data: {
        currentPosition: newPosition,
        diceRollsToday: isToday ? playerBoard.diceRollsToday + 1 : 1,
        lastRollDate: new Date()
      },
    })

    // Snake: uncomplete the target tile and every tile between it and the snake's head
    if (jump === 'snake') {
      const tileIdsToUncomplete = board.tiles
        .filter(t => t.position >= newPosition && t.position <= landedOn)
        .map(t => t.id)
      if (tileIdsToUncomplete.length > 0) {
        await this.prisma.completedTile.deleteMany({
          where: {
            playerBoardId: playerBoard.id,
            tileId: { in: tileIdsToUncomplete },
          },
        })
      }
    }

    // Re-fetch the final player board state
    const finalPlayerBoard = await this.prisma.playerBoard.findUnique({
      where: { id: playerBoard.id },
      include: PLAYER_BOARD_INCLUDE,
    })

    return {
      rolled,
      previousPosition,
      newPosition,
      landedOn,
      jump,
      playerBoard: finalPlayerBoard,
    }
  }

  /**
   * Mark a tile as completed manually
   */
  async completeTile(userId: string, boardId: string, tileId: string) {
    const playerBoard = await this.getOrCreatePlayerBoard(userId, boardId)
    if (!playerBoard) throw new BadRequestException('Je hebt geen team op dit bord')

    await this.prisma.completedTile.upsert({
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
      include: PLAYER_BOARD_INCLUDE,
    })
  }

  /**
   * Build the leaderboard for a board.
   * SOLO: sorted by position, shows user info.
   * TEAM: one entry per team, shows team info.
   */
  async getLeaderboard(boardId: string) {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: { tiles: { orderBy: { position: 'asc' } } },
    })

    if (!board) throw new NotFoundException('Board not found')

    const totalTiles = board.tiles.length
    const maxPosition = totalTiles - 1

    const playerBoards = await this.prisma.playerBoard.findMany({
      where: { boardId },
      include: {
        user: { include: { userRoles: { include: { role: true } } } },
        completedTiles: true,
        team: true,
      },
      orderBy: { currentPosition: 'desc' },
    })

    const entries = playerBoards.map((pb, index) => {
      const tilesRemaining = maxPosition - pb.currentPosition
      const pathTiles = board.tiles.filter(
        t => t.position > pb.currentPosition && t.position <= maxPosition,
      )
      const pathHasLadder = pathTiles.some(t => t.type === 'LADDER' && t.targetPosition !== null)
      const pathHasSnake = pathTiles.some(t => t.type === 'SNAKE' && t.targetPosition !== null)

      return {
        rank: index + 1,
        playerId: pb.id,
        user: pb.user,
        team: pb.team ?? null,
        currentPosition: pb.currentPosition,
        tilesRemaining,
        pathHasLadder,
        pathHasSnake,
      }
    })

    return { boardId, totalTiles, entries }
  }

  /**
   * Remove tile completion (unmark)
   */
  async uncompleteTile(userId: string, boardId: string, tileId: string) {
    const board = await this.prisma.board.findUnique({
      where: { id: boardId },
      include: { boardTeams: { include: { team: { include: { members: true } } } } }
    })
    if (!board) return null

    let playerBoard

    if (board.mode === 'TEAM') {
      const boardTeamEntry = board.boardTeams.find(bt =>
        bt.team.members.some(m => m.userId === userId)
      )
      if (!boardTeamEntry) return null

      playerBoard = await this.prisma.playerBoard.findFirst({
        where: { teamId: boardTeamEntry.teamId, boardId },
      })
    } else {
      playerBoard = await this.prisma.playerBoard.findUnique({
        where: { userId_boardId: { userId, boardId } }
      })
    }

    if (!playerBoard) return null

    await this.prisma.completedTile.deleteMany({
      where: { playerBoardId: playerBoard.id, tileId }
    })

    return this.prisma.playerBoard.findUnique({
      where: { id: playerBoard.id },
      include: PLAYER_BOARD_INCLUDE,
    })
  }
}
