import { Injectable, Logger, OnModuleInit } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { BoardSize, TileType } from '../generated/prisma/index.js'

/**
 * Runs once on every app startup.
 * All operations are idempotent — data is only created if it doesn't exist yet.
 * Safe to run in production: will never overwrite or delete existing records.
 */
@Injectable()
export class SeedService implements OnModuleInit {
  private readonly logger = new Logger(SeedService.name)

  constructor(private readonly prisma: PrismaService) {}

  async onModuleInit() {
    await this.seedRoles()
    await this.seedTasks()
    await this.seedFakeUsers()
    await this.seedBoards()
  }

  // ─── ROLES ───────────────────────────────────────────────────────────────

  private async seedRoles() {
    const roleDefs = [
      { name: 'PLAYER',       description: 'Default role — can join boards and play' },
      { name: 'ADMIN',        description: 'Full access — manage boards, tiles, tasks and users' },
      { name: 'EDITOR',       description: 'Phase 2 — can create and edit boards' },
      { name: 'TEAM_MANAGER', description: 'Phase 2 — can create and manage teams' },
    ]
    for (const role of roleDefs) {
      await this.prisma.role.upsert({
        where:  { name: role.name },
        update: { description: role.description },
        create: role,
      })
    }
    this.logger.log('Roles OK')
  }

  // ─── TASKS ───────────────────────────────────────────────────────────────

  private async seedTasks() {
    const taskDefs = [
      // Gathering
      { title: 'Catch a Raw Lobster',          iconUrl: 'https://oldschool.runescape.wiki/images/Raw_lobster.png',          description: 'Fish a raw lobster at a cage fishing spot (requires 40 Fishing).' },
      { title: 'Catch a Raw Shark',            iconUrl: 'https://oldschool.runescape.wiki/images/Raw_shark.png',            description: 'Fish a raw shark with a harpoon (requires 76 Fishing).' },
      { title: 'Catch a Raw Swordfish',        iconUrl: 'https://oldschool.runescape.wiki/images/Raw_swordfish.png',        description: 'Harpoon a raw swordfish at a fishing spot (requires 50 Fishing).' },
      { title: 'Mine Runite Ore',              iconUrl: 'https://oldschool.runescape.wiki/images/Runite_ore.png',           description: 'Mine Runite ore from a Runite rock (requires 85 Mining).' },
      { title: 'Smelt a Rune Bar',             iconUrl: 'https://oldschool.runescape.wiki/images/Rune_bar.png',             description: 'Smelt a Rune bar in a furnace (requires 85 Smithing).' },
      { title: 'Chop Magic Logs',              iconUrl: 'https://oldschool.runescape.wiki/images/Magic_logs.png',           description: 'Chop magic logs from a magic tree (requires 75 Woodcutting).' },
      { title: 'Craft a Nature Rune',          iconUrl: 'https://oldschool.runescape.wiki/images/Nature_rune.png',          description: 'Craft a Nature rune at the Nature Altar (requires 44 Runecraft).' },
      { title: 'Catch Red Chinchompas',        iconUrl: 'https://oldschool.runescape.wiki/images/Red_chinchompa.png',       description: 'Box-trap red chinchompas in Feldip Hills (requires 63 Hunter).' },
      // Early gear
      { title: 'Obtain a Rune Scimitar',       iconUrl: 'https://oldschool.runescape.wiki/images/Rune_scimitar.png',        description: 'Obtain a Rune scimitar — a staple mid-game melee weapon.' },
      { title: 'Obtain a Rune Platebody',      iconUrl: 'https://oldschool.runescape.wiki/images/Rune_platebody.png',       description: 'Equip a Rune platebody (requires Dragon Slayer I and 40 Defence).' },
      { title: 'Obtain a Rune 2h Sword',       iconUrl: 'https://oldschool.runescape.wiki/images/Rune_2h_sword.png',        description: 'Wield a Rune 2h sword (requires 40 Attack).' },
      { title: 'Obtain a Rune Crossbow',       iconUrl: 'https://oldschool.runescape.wiki/images/Rune_crossbow.png',        description: 'Equip a Rune crossbow (requires 61 Ranged).' },
      { title: 'Obtain Dragon Bones',          iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_bones.png',         description: 'Loot Dragon bones from any dragon for Prayer experience.' },
      // Mid-game gear
      { title: 'Obtain a Dragon Scimitar',     iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_scimitar.png',      description: 'Equip a Dragon scimitar — reward for completing Monkey Madness I.' },
      { title: 'Obtain a Dragon Med Helm',     iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_med_helm.png',      description: 'Obtain a Dragon med helm from a rare monster drop.' },
      { title: 'Obtain a Dragon Chainbody',    iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_chainbody.png',     description: 'Obtain a Dragon chainbody from the Kalphite Queen or rare drop.' },
      { title: 'Obtain a Dragon 2h Sword',     iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_2h_sword.png',      description: 'Wield the powerful Dragon 2h sword (requires 60 Attack).' },
      { title: 'Obtain an Abyssal Whip',       iconUrl: 'https://oldschool.runescape.wiki/images/Abyssal_whip.png',         description: 'Obtain an Abyssal whip from an Abyssal demon (requires 85 Slayer).' },
      { title: 'Obtain an Ancient Staff',      iconUrl: 'https://oldschool.runescape.wiki/images/Ancient_staff.png',        description: 'Wield the Ancient staff — reward for completing Desert Treasure I.' },
      // Capes & prestige
      { title: 'Obtain a Fire Cape',           iconUrl: 'https://oldschool.runescape.wiki/images/Fire_cape.png',            description: 'Survive the TzHaar Fight Cave and defeat TzTok-Jad.' },
      { title: 'Obtain an Obsidian Cape',      iconUrl: 'https://oldschool.runescape.wiki/images/Obsidian_cape.png',        description: 'Purchase an Obsidian cape with Tokkul from the TzHaar shop.' },
      { title: 'Obtain an Infernal Cape',      iconUrl: 'https://oldschool.runescape.wiki/images/Infernal_cape.png',        description: 'Defeat TzKal-Zuk in the Inferno to claim the Infernal cape.' },
      { title: 'Achieve 99 in Any Skill',      iconUrl: 'https://oldschool.runescape.wiki/images/Max_cape.png',             description: 'Train any skill to level 99 and claim your skill cape.' },
      // Late-game gear
      { title: 'Obtain an Amulet of Fury',     iconUrl: 'https://oldschool.runescape.wiki/images/Amulet_of_fury.png',       description: 'Craft or purchase an Amulet of fury (requires 87 Crafting to make).' },
      { title: 'Obtain a Berserker Ring',      iconUrl: 'https://oldschool.runescape.wiki/images/Berserker_ring.png',       description: 'Obtain a Berserker ring drop from Dagannoth Rex.' },
      { title: 'Obtain Barrows Gloves',        iconUrl: 'https://oldschool.runescape.wiki/images/Barrows_gloves.png',       description: 'Complete Recipe for Disaster and purchase the best-in-slot gloves.' },
      { title: 'Obtain a Slayer Helmet',       iconUrl: 'https://oldschool.runescape.wiki/images/Slayer_helmet.png',        description: 'Craft a Slayer helmet from components (requires 55 Crafting).' },
      { title: 'Obtain Void Knight Top',       iconUrl: 'https://oldschool.runescape.wiki/images/Void_knight_top.png',      description: 'Earn 850 Void Knight commendation points at the Pest Control minigame.' },
      // Boss loot
      { title: 'Obtain Bandos Chestplate',     iconUrl: 'https://oldschool.runescape.wiki/images/Bandos_chestplate.png',    description: 'Obtain a Bandos chestplate drop from General Graardor in GWD.' },
      { title: 'Obtain Bandos Tassets',        iconUrl: 'https://oldschool.runescape.wiki/images/Bandos_tassets.png',       description: 'Obtain Bandos tassets from General Graardor in the God Wars Dungeon.' },
      { title: 'Obtain Armadyl Chestplate',    iconUrl: 'https://oldschool.runescape.wiki/images/Armadyl_chestplate.png',   description: "Obtain the Armadyl chestplate from Kree'arra in the God Wars Dungeon." },
      { title: 'Obtain Armadyl Chainskirt',    iconUrl: 'https://oldschool.runescape.wiki/images/Armadyl_chainskirt.png',   description: "Obtain the Armadyl chainskirt from Kree'arra in the God Wars Dungeon." },
      { title: 'Obtain Dragon Claws',          iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_claws.png',         description: 'Obtain Dragon claws — a powerful spec weapon from the Chambers of Xeric.' },
      { title: 'Obtain a Twisted Bow',         iconUrl: 'https://oldschool.runescape.wiki/images/Twisted_bow.png',          description: 'Obtain the legendary Twisted bow from the Chambers of Xeric.' },
      { title: 'Obtain a Dragon Full Helm',    iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_full_helm.png',     description: 'Obtain a Dragon full helm — a very rare drop from Mithril Dragons.' },
    ]

    const taskIds: Record<string, string> = {}
    for (const def of taskDefs) {
      const existing = await this.prisma.task.findFirst({ where: { title: def.title } })
      const task = existing ?? await this.prisma.task.create({ data: def })
      taskIds[def.title] = task.id
    }
    this.logger.log('Tasks OK')
    return taskIds
  }

  // ─── FAKE USERS ──────────────────────────────────────────────────────────

  private async seedFakeUsers() {
    const fakeUserDefs = [
      { discordId: '999000000000000001', discordUsername: 'ZezimaClan',   avatarUrl: 'https://cdn.discordapp.com/embed/avatars/0.png' },
      { discordId: '999000000000000002', discordUsername: 'PuroPuro99',   avatarUrl: 'https://cdn.discordapp.com/embed/avatars/1.png' },
      { discordId: '999000000000000003', discordUsername: 'RuneKnight42', avatarUrl: 'https://cdn.discordapp.com/embed/avatars/2.png' },
      { discordId: '999000000000000004', discordUsername: 'IronMaidHD',   avatarUrl: 'https://cdn.discordapp.com/embed/avatars/3.png' },
      { discordId: '999000000000000005', discordUsername: 'BarrowsLoot',  avatarUrl: 'https://cdn.discordapp.com/embed/avatars/4.png' },
    ]
    for (const def of fakeUserDefs) {
      await this.prisma.user.upsert({
        where:  { discordId: def.discordId },
        update: { discordUsername: def.discordUsername, avatarUrl: def.avatarUrl },
        create: def,
      })
    }
    this.logger.log('Fake users OK')
  }

  // ─── BOARDS ──────────────────────────────────────────────────────────────

  private async seedBoards() {
    // Re-fetch task IDs (tasks are guaranteed to exist at this point)
    const tasks = await this.prisma.task.findMany({ select: { id: true, title: true } })
    const taskIds = Object.fromEntries(tasks.map(t => [t.title, t.id]))

    const fakeUsers = await this.prisma.user.findMany({
      where: { discordId: { in: ['999000000000000001','999000000000000002','999000000000000003','999000000000000004','999000000000000005'] } },
      select: { id: true, discordId: true },
    })
    const u = Object.fromEntries(fakeUsers.map(u => [u.discordId, u.id]))

    const adminRole = await this.prisma.role.findFirst({ where: { name: 'ADMIN' } })
    const adminUserRole = adminRole
      ? await this.prisma.userRole.findFirst({ where: { roleId: adminRole.id } })
      : null
    const adminUserId = adminUserRole?.userId ?? null

    const taskOrder = [
      taskIds['Catch a Raw Lobster'],          taskIds['Obtain a Rune Scimitar'],
      taskIds['Catch a Raw Swordfish'],         taskIds['Mine Runite Ore'],
      taskIds['Craft a Nature Rune'],           taskIds['Obtain a Rune Platebody'],
      taskIds['Chop Magic Logs'],               taskIds['Smelt a Rune Bar'],
      taskIds['Obtain a Rune 2h Sword'],        taskIds['Obtain a Rune Crossbow'],
      taskIds['Obtain Dragon Bones'],           taskIds['Catch Red Chinchompas'],
      taskIds['Obtain a Dragon Scimitar'],      taskIds['Obtain a Dragon Med Helm'],
      taskIds['Obtain a Dragon Chainbody'],     taskIds['Obtain an Ancient Staff'],
      taskIds['Obtain a Dragon 2h Sword'],      taskIds['Obtain an Abyssal Whip'],
      taskIds['Obtain a Fire Cape'],            taskIds['Obtain an Obsidian Cape'],
      taskIds['Obtain an Amulet of Fury'],      taskIds['Obtain a Berserker Ring'],
      taskIds['Obtain Barrows Gloves'],         taskIds['Obtain a Slayer Helmet'],
      taskIds['Obtain Void Knight Top'],        taskIds['Obtain Bandos Chestplate'],
      taskIds['Obtain Bandos Tassets'],         taskIds['Obtain Armadyl Chestplate'],
      taskIds['Obtain Armadyl Chainskirt'],     taskIds['Obtain Dragon Claws'],
      taskIds['Catch a Raw Shark'],             taskIds['Obtain a Twisted Bow'],
      taskIds['Obtain an Infernal Cape'],       taskIds['Achieve 99 in Any Skill'],
      taskIds['Obtain a Dragon Full Helm'],
    ]

    const boardDefs = [
      {
        title: 'Dragon Slayer Journey',
        data: {
          description:   'Journey through Gielinor completing OSRS tasks on your path to becoming a Dragon Slayer. Watch out for the snakes!',
          size:          BoardSize.SIZE_7X7,
          diceRollLimit: 1,
          startDate:     new Date('2026-03-01T00:00:00.000Z'),
          endDate:       new Date('2026-06-01T00:00:00.000Z'),
        },
        tileCount: 49,
        snakes:  [[48,5],[38,15],[29,8],[22,3],[43,33]] as [number,number][],
        ladders: [[2,19],[11,26],[17,36],[30,46],[35,44]] as [number,number][],
        overrides: { 0: taskIds['Catch a Raw Lobster'], 48: taskIds['Achieve 99 in Any Skill'] } as Record<number, string>,
        players: [
          { discordId: '999000000000000001', position: 5  },
          { discordId: '999000000000000002', position: 15 },
          { discordId: '999000000000000003', position: 28 },
          { discordId: '999000000000000004', position: 38 },
        ],
        authorDiscordIds: ['999000000000000001','999000000000000002'],
        tasks: taskOrder,
      },
      {
        title: 'F2P Challenge',
        data: {
          description:   'A free-to-play themed bingo board. No membership required — just pure skill and determination!',
          size:          BoardSize.SIZE_5X5,
          diceRollLimit: 2,
          startDate:     new Date('2026-04-01T00:00:00.000Z'),
          endDate:       new Date('2026-05-01T00:00:00.000Z'),
        },
        tileCount: 25,
        snakes:  [[23,4],[20,8],[16,1]] as [number,number][],
        ladders: [[2,11],[6,17],[13,22]] as [number,number][],
        overrides: { 0: taskIds['Catch a Raw Lobster'], 24: taskIds['Obtain a Dragon Scimitar'] } as Record<number, string>,
        players: [
          { discordId: '999000000000000001', position: 3  },
          { discordId: '999000000000000003', position: 10 },
          { discordId: '999000000000000005', position: 18 },
        ],
        authorDiscordIds: ['999000000000000003','999000000000000005'],
        tasks: taskOrder.slice(0, 20),
      },
      {
        title: "Champion's Gauntlet",
        data: {
          description:   'The ultimate OSRS challenge. Only the most dedicated players will reach the end. Bosses, quests, and 99s await.',
          size:          BoardSize.SIZE_9X9,
          diceRollLimit: 1,
          startDate:     new Date('2026-01-01T00:00:00.000Z'),
          endDate:       new Date('2026-12-31T00:00:00.000Z'),
        },
        tileCount: 81,
        snakes:  [[79,10],[68,35],[60,22],[50,18],[42,7],[75,56]] as [number,number][],
        ladders: [[4,26],[16,44],[30,65],[48,73],[63,77]] as [number,number][],
        overrides: { 0: taskIds['Catch a Raw Lobster'], 80: taskIds['Obtain a Twisted Bow'] } as Record<number, string>,
        players: [
          { discordId: '999000000000000001', position: 10 },
          { discordId: '999000000000000002', position: 25 },
          { discordId: '999000000000000004', position: 50 },
          { discordId: '999000000000000005', position: 70 },
        ],
        authorDiscordIds: ['999000000000000002','999000000000000004'],
        tasks: taskOrder,
      },
      {
        title: 'Solo Speedrun',
        data: {
          description:   'A personal challenge board. One player, one goal — reach the finish as fast as possible!',
          size:          BoardSize.SIZE_7X7,
          diceRollLimit: null,
          startDate:     new Date('2026-03-15T00:00:00.000Z'),
          endDate:       new Date('2026-05-15T00:00:00.000Z'),
        },
        tileCount: 49,
        snakes:  [[46,2],[35,14],[27,5]] as [number,number][],
        ladders: [[1,20],[9,32],[22,44]] as [number,number][],
        overrides: { 0: taskIds['Catch a Raw Lobster'], 48: taskIds['Obtain an Infernal Cape'] } as Record<number, string>,
        players: [
          { discordId: '999000000000000003', position: 12 },
        ],
        authorDiscordIds: ['999000000000000003'],
        tasks: taskOrder,
      },
    ]

    for (const def of boardDefs) {
      const existing = await this.prisma.board.findFirst({ where: { title: def.title } })
      if (existing) continue  // Already seeded — never touch it

      const board = await this.prisma.board.create({ data: { title: def.title, ...def.data } })
      await this.seedTilesForBoard(board.id, def.tileCount, def.snakes, def.ladders, def.tasks, def.overrides)

      for (const p of def.players) {
        const userId = u[p.discordId]
        if (!userId) continue
        await this.prisma.playerBoard.upsert({
          where:  { userId_boardId: { userId, boardId: board.id } },
          update: {},
          create: { userId, boardId: board.id, currentPosition: p.position },
        })
      }

      const authorIds = [...new Set([
        ...(adminUserId ? [adminUserId] : []),
        ...def.authorDiscordIds.map(d => u[d]).filter(Boolean),
      ])]
      for (const userId of authorIds) {
        await this.prisma.boardAuthor.upsert({
          where:  { boardId_userId: { boardId: board.id, userId } },
          update: {},
          create: { boardId: board.id, userId },
        })
      }
    }

    this.logger.log('Boards OK')
  }

  private async seedTilesForBoard(
    boardId: string,
    total: number,
    snakes: [number, number][],
    ladders: [number, number][],
    taskOrder: string[],
    overrides: Record<number, string> = {},
  ) {
    const snakeMap  = new Map(snakes)
    const ladderMap = new Map(ladders)

    for (let pos = 0; pos < total; pos++) {
      const type: TileType = snakeMap.has(pos) ? TileType.SNAKE
        : ladderMap.has(pos) ? TileType.LADDER
        : TileType.NORMAL
      const targetPosition = snakeMap.get(pos) ?? ladderMap.get(pos) ?? null
      const taskId = overrides[pos] ?? taskOrder[pos % taskOrder.length]

      await this.prisma.tile.upsert({
        where:  { boardId_position: { boardId, position: pos } },
        update: { type, targetPosition, taskId },
        create: { boardId, position: pos, type, targetPosition, taskId },
      })
    }
  }
}
