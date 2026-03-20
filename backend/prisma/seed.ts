import { PrismaClient, BoardSize, TileType } from '@prisma/client'
import { PrismaPg } from '@prisma/adapter-pg'
import 'dotenv/config'

const adapter = new PrismaPg({ connectionString: process.env.DATABASE_URL })
const prisma = new PrismaClient({ adapter })

/**
 * Database seed — safe to re-run.
 * Always recreates all demo boards so content stays consistent.
 * Seeds: roles, OSRS tasks (all with verified item icons), 4 boards, 5 fake players.
 */
async function main() {
  // ─── ROLES ─────────────────────────────────────────────────────────
  const roleDefs = [
    { name: 'PLAYER',       description: 'Default role — can join boards and play' },
    { name: 'ADMIN',        description: 'Full access — manage boards, tiles, tasks and users' },
    { name: 'EDITOR',       description: 'Phase 2 — can create and edit boards' },
    { name: 'TEAM_MANAGER', description: 'Phase 2 — can create and manage teams' },
  ]
  for (const role of roleDefs) {
    await prisma.role.upsert({
      where:  { name: role.name },
      update: { description: role.description },
      create: role,
    })
    console.log(`✅ Role: ${role.name}`)
  }

  // ─── TASKS ─────────────────────────────────────────────────────────
  // All iconUrls use OSRS wiki item sprites — only reliable, verified item filenames.
  // No quest icons, NPC images, or skill icons (those have unpredictable paths).
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
    { title: 'Obtain Armadyl Chestplate',    iconUrl: 'https://oldschool.runescape.wiki/images/Armadyl_chestplate.png',   description: 'Obtain the Armadyl chestplate from Kree\'arra in the God Wars Dungeon.' },
    { title: 'Obtain Armadyl Chainskirt',    iconUrl: 'https://oldschool.runescape.wiki/images/Armadyl_chainskirt.png',   description: 'Obtain the Armadyl chainskirt from Kree\'arra in the God Wars Dungeon.' },
    { title: 'Obtain Dragon Claws',          iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_claws.png',         description: 'Obtain Dragon claws — a powerful spec weapon from the Chambers of Xeric.' },
    { title: 'Obtain a Twisted Bow',         iconUrl: 'https://oldschool.runescape.wiki/images/Twisted_bow.png',          description: 'Obtain the legendary Twisted bow from the Chambers of Xeric.' },
    { title: 'Obtain a Dragon Full Helm',    iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_full_helm.png',     description: 'Obtain a Dragon full helm — a very rare drop from Mithril Dragons.' },
  ]

  const taskIds: Record<string, string> = {}
  for (const def of taskDefs) {
    const existing = await prisma.task.findFirst({ where: { title: def.title } })
    const task = existing ?? await prisma.task.create({ data: def })
    taskIds[def.title] = task.id
    console.log(`✅ Task: ${def.title}`)
  }

  // ─── FAKE PLAYERS ──────────────────────────────────────────────────
  // Fake users with realistic OSRS-style usernames and default Discord avatars.
  const fakeUserDefs = [
    { discordId: '999000000000000001', discordUsername: 'ZezimaClan',   avatarUrl: 'https://cdn.discordapp.com/embed/avatars/0.png' },
    { discordId: '999000000000000002', discordUsername: 'PuroPuro99',   avatarUrl: 'https://cdn.discordapp.com/embed/avatars/1.png' },
    { discordId: '999000000000000003', discordUsername: 'RuneKnight42', avatarUrl: 'https://cdn.discordapp.com/embed/avatars/2.png' },
    { discordId: '999000000000000004', discordUsername: 'IronMaidHD',   avatarUrl: 'https://cdn.discordapp.com/embed/avatars/3.png' },
    { discordId: '999000000000000005', discordUsername: 'BarrowsLoot',  avatarUrl: 'https://cdn.discordapp.com/embed/avatars/4.png' },
  ]
  const fakeUsers: { id: string, discordUsername: string }[] = []
  for (const def of fakeUserDefs) {
    const user = await prisma.user.upsert({
      where:  { discordId: def.discordId },
      update: { discordUsername: def.discordUsername, avatarUrl: def.avatarUrl },
      create: def,
    })
    fakeUsers.push({ id: user.id, discordUsername: user.discordUsername })
    console.log(`✅ Fake user: ${def.discordUsername}`)
  }

  // ─── FIND EXISTING ADMIN ───────────────────────────────────────────
  // If an admin has logged in, include them as a board author.
  const adminRole = await prisma.role.findFirst({ where: { name: 'ADMIN' } })
  const adminUserRole = adminRole
    ? await prisma.userRole.findFirst({ where: { roleId: adminRole.id } })
    : null
  const adminUserId = adminUserRole?.userId ?? null
  if (adminUserId) {
    console.log(`✅ Admin found — will be added as board author`)
  } else {
    console.log(`ℹ️  No admin user found — fake user will be the only author`)
  }

  // ─── BOARDS ────────────────────────────────────────────────────────
  const boardTitles = [
    'Dragon Slayer Journey',
    'F2P Challenge',
    "Champion's Gauntlet",
    'Solo Speedrun',
  ]
  for (const title of boardTitles) {
    const existing = await prisma.board.findFirst({ where: { title } })
    if (existing) {
      await prisma.board.delete({ where: { id: existing.id } })
      console.log(`🗑️  Deleted existing "${title}" for reseed`)
    }
  }

  // Ordered task list (beginner → endgame) for cycling through tiles
  const taskOrder = [
    taskIds['Catch a Raw Lobster'],
    taskIds['Obtain a Rune Scimitar'],
    taskIds['Catch a Raw Swordfish'],
    taskIds['Mine Runite Ore'],
    taskIds['Craft a Nature Rune'],
    taskIds['Obtain a Rune Platebody'],
    taskIds['Chop Magic Logs'],
    taskIds['Smelt a Rune Bar'],
    taskIds['Obtain a Rune 2h Sword'],
    taskIds['Obtain a Rune Crossbow'],
    taskIds['Obtain Dragon Bones'],
    taskIds['Catch Red Chinchompas'],
    taskIds['Obtain a Dragon Scimitar'],
    taskIds['Obtain a Dragon Med Helm'],
    taskIds['Obtain a Dragon Chainbody'],
    taskIds['Obtain an Ancient Staff'],
    taskIds['Obtain a Dragon 2h Sword'],
    taskIds['Obtain an Abyssal Whip'],
    taskIds['Obtain a Fire Cape'],
    taskIds['Obtain an Obsidian Cape'],
    taskIds['Obtain an Amulet of Fury'],
    taskIds['Obtain a Berserker Ring'],
    taskIds['Obtain Barrows Gloves'],
    taskIds['Obtain a Slayer Helmet'],
    taskIds['Obtain Void Knight Top'],
    taskIds['Obtain Bandos Chestplate'],
    taskIds['Obtain Bandos Tassets'],
    taskIds['Obtain Armadyl Chestplate'],
    taskIds['Obtain Armadyl Chainskirt'],
    taskIds['Obtain Dragon Claws'],
    taskIds['Catch a Raw Shark'],
    taskIds['Obtain a Twisted Bow'],
    taskIds['Obtain an Infernal Cape'],
    taskIds['Achieve 99 in Any Skill'],
    taskIds['Obtain a Dragon Full Helm'],
  ]

  // ── Board 1: Dragon Slayer Journey (7×7 = 49 tiles) ──────────────
  // 4 fake players, varied positions
  {
    const board = await prisma.board.create({
      data: {
        title:         'Dragon Slayer Journey',
        description:   'Journey through Gielinor completing OSRS tasks on your path to becoming a Dragon Slayer. Watch out for the snakes!',
        size:          BoardSize.SIZE_7X7,
        diceRollLimit: 1,
        startDate:     new Date('2026-03-01T00:00:00.000Z'),
        endDate:       new Date('2026-06-01T00:00:00.000Z'),
      },
    })
    console.log(`✅ Board: ${board.title} (${board.size})`)

    const snakes: [number, number][] = [
      [48, 5],  // Near finish → almost start
      [38, 15], // High → lower mid
      [29, 8],  // Mid → near start
      [22, 3],  // Early mid → near start
      [43, 33], // Near end → mid-high
    ]
    const ladders: [number, number][] = [
      [2,  19], // Near start → mid
      [11, 26], // Lower mid → mid
      [17, 36], // Lower mid → high
      [30, 46], // Mid → near end
      [35, 44], // Mid-high → near end
    ]
    await seedTiles(board.id, 49, snakes, ladders, taskOrder, {
      0:  taskIds['Catch a Raw Lobster'],
      48: taskIds['Achieve 99 in Any Skill'],
    })
    console.log(`✅ 49 tiles seeded for "${board.title}"`)

    // 4 players at positions 5, 15, 28, 38
    await seedPlayerBoards(board.id, [
      { userId: fakeUsers[0].id, position: 5 },
      { userId: fakeUsers[1].id, position: 15 },
      { userId: fakeUsers[2].id, position: 28 },
      { userId: fakeUsers[3].id, position: 38 },
    ])
    console.log(`✅ 4 fake players added to "${board.title}"`)
    await seedBoardAuthors(board.id, [fakeUsers[0].id, fakeUsers[1].id], adminUserId)
    console.log(`✅ Authors set for "${board.title}"`)
  }

  // ── Board 2: F2P Challenge (5×5 = 25 tiles) ──────────────────────
  // Smaller board, beginner-friendly, 3 fake players
  {
    const board = await prisma.board.create({
      data: {
        title:         'F2P Challenge',
        description:   'A free-to-play themed bingo board. No membership required — just pure skill and determination!',
        size:          BoardSize.SIZE_5X5,
        diceRollLimit: 2,
        startDate:     new Date('2026-04-01T00:00:00.000Z'),
        endDate:       new Date('2026-05-01T00:00:00.000Z'),
      },
    })
    console.log(`✅ Board: ${board.title} (${board.size})`)

    const snakes: [number, number][] = [
      [23, 4],  // Near finish → near start
      [20, 8],  // Near end → mid
      [16, 1],  // Mid → almost start
    ]
    const ladders: [number, number][] = [
      [2,  11], // Early → mid
      [6,  17], // Early-mid → mid-high
      [13, 22], // Mid → near end
    ]
    // F2P board uses only early-game tasks
    const f2pTasks = taskOrder.slice(0, 20)
    await seedTiles(board.id, 25, snakes, ladders, f2pTasks, {
      0:  taskIds['Catch a Raw Lobster'],
      24: taskIds['Obtain a Dragon Scimitar'],
    })
    console.log(`✅ 25 tiles seeded for "${board.title}"`)

    await seedPlayerBoards(board.id, [
      { userId: fakeUsers[0].id, position: 3 },
      { userId: fakeUsers[2].id, position: 10 },
      { userId: fakeUsers[4].id, position: 18 },
    ])
    console.log(`✅ 3 fake players added to "${board.title}"`)
    await seedBoardAuthors(board.id, [fakeUsers[2].id, fakeUsers[4].id], adminUserId)
    console.log(`✅ Authors set for "${board.title}"`)
  }

  // ── Board 3: Champion's Gauntlet (9×9 = 81 tiles) ────────────────
  // Big board, endgame, 4 fake players
  {
    const board = await prisma.board.create({
      data: {
        title:         "Champion's Gauntlet",
        description:   'The ultimate OSRS challenge. Only the most dedicated players will reach the end. Bosses, quests, and 99s await.',
        size:          BoardSize.SIZE_9X9,
        diceRollLimit: 1,
        startDate:     new Date('2026-01-01T00:00:00.000Z'),
        endDate:       new Date('2026-12-31T00:00:00.000Z'),
      },
    })
    console.log(`✅ Board: ${board.title} (${board.size})`)

    const snakes: [number, number][] = [
      [79, 10], // Near finish → near start — the killer
      [68, 35], // High → mid
      [60, 22], // Mid-high → low-mid
      [50, 18], // Mid → low
      [42, 7],  // Mid → early
      [75, 56], // Near end → upper-mid
    ]
    const ladders: [number, number][] = [
      [4,  26], // Early → lower-mid
      [16, 44], // Lower-mid → mid
      [30, 65], // Mid → high
      [48, 73], // Mid-high → near end
      [63, 77], // High → near finish
    ]
    await seedTiles(board.id, 81, snakes, ladders, taskOrder, {
      0:  taskIds['Catch a Raw Lobster'],
      80: taskIds['Obtain a Twisted Bow'],
    })
    console.log(`✅ 81 tiles seeded for "${board.title}"`)

    await seedPlayerBoards(board.id, [
      { userId: fakeUsers[0].id, position: 10 },
      { userId: fakeUsers[1].id, position: 25 },
      { userId: fakeUsers[3].id, position: 50 },
      { userId: fakeUsers[4].id, position: 70 },
    ])
    console.log(`✅ 4 fake players added to "${board.title}"`)
    await seedBoardAuthors(board.id, [fakeUsers[1].id, fakeUsers[3].id], adminUserId)
    console.log(`✅ Authors set for "${board.title}"`)
  }

  // ── Board 4: Solo Speedrun (7×7 = 49 tiles) ──────────────────────
  // Only 1 fake player — this is the solo board
  {
    const board = await prisma.board.create({
      data: {
        title:         'Solo Speedrun',
        description:   'A personal challenge board. One player, one goal — reach the finish as fast as possible!',
        size:          BoardSize.SIZE_7X7,
        diceRollLimit: null, // unlimited rolls
        startDate:     new Date('2026-03-15T00:00:00.000Z'),
        endDate:       new Date('2026-05-15T00:00:00.000Z'),
      },
    })
    console.log(`✅ Board: ${board.title} (${board.size})`)

    const snakes: [number, number][] = [
      [46, 2],  // Near finish → near start
      [35, 14], // Mid → early-mid
      [27, 5],  // Mid → early
    ]
    const ladders: [number, number][] = [
      [1,  20], // Very early → mid
      [9,  32], // Early → mid-high
      [22, 44], // Mid → near end
    ]
    await seedTiles(board.id, 49, snakes, ladders, taskOrder, {
      0:  taskIds['Catch a Raw Lobster'],
      48: taskIds['Obtain an Infernal Cape'],
    })
    console.log(`✅ 49 tiles seeded for "${board.title}"`)

    // Solo board — just 1 player
    await seedPlayerBoards(board.id, [
      { userId: fakeUsers[2].id, position: 12 },
    ])
    console.log(`✅ 1 fake player added to "${board.title}" (solo board)`)
    await seedBoardAuthors(board.id, [fakeUsers[2].id], adminUserId)
    console.log(`✅ Authors set for "${board.title}"`)
  }

  console.log('\n🌱 Seed complete!')
}

// ─── HELPERS ───────────────────────────────────────────────────────────────

/**
 * Seed all tiles for a board with snakes, ladders, and a task rotation.
 */
async function seedTiles(
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
    const type: TileType = snakeMap.has(pos)
      ? TileType.SNAKE
      : ladderMap.has(pos)
        ? TileType.LADDER
        : TileType.NORMAL

    const targetPosition = snakeMap.get(pos) ?? ladderMap.get(pos) ?? null
    const taskId = overrides[pos] ?? taskOrder[pos % taskOrder.length]

    await prisma.tile.upsert({
      where:  { boardId_position: { boardId, position: pos } },
      update: { type, targetPosition, taskId },
      create: { boardId, position: pos, type, targetPosition, taskId },
    })
  }
}

/**
 * Create BoardAuthor entries — includes admin (if found) + specified fake users.
 */
async function seedBoardAuthors(boardId: string, fakeAuthorIds: string[], adminUserId: string | null) {
  const ids = [...new Set([...(adminUserId ? [adminUserId] : []), ...fakeAuthorIds])]
  for (const userId of ids) {
    await prisma.boardAuthor.upsert({
      where:  { boardId_userId: { boardId, userId } },
      update: {},
      create: { boardId, userId },
    })
  }
}

/**
 * Create PlayerBoard entries for fake players on a board.
 * Each player gets a currentPosition set so the board looks active.
 */
async function seedPlayerBoards(
  boardId: string,
  players: { userId: string, position: number }[],
) {
  for (const p of players) {
    await prisma.playerBoard.upsert({
      where:  { userId_boardId: { userId: p.userId, boardId } },
      update: { currentPosition: p.position },
      create: { userId: p.userId, boardId, currentPosition: p.position },
    })
  }
}

main()
  .catch((e) => {
    console.error('Seed failed:', e)
    process.exit(1)
  })
  .finally(async () => {
    await prisma.$disconnect()
  })
