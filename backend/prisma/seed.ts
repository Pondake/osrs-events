import { PrismaClient, BoardSize, BoardMode, TileType } from '../src/generated/prisma/index.js'
import { PrismaPg } from '@prisma/adapter-pg'
import 'dotenv/config'

const adapter = new PrismaPg({ connectionString: process.env.DATABASE_URL })
const prisma = new PrismaClient({ adapter })

/**
 * Database seed — safe to re-run (upserts where possible, deletes boards for a clean slate).
 *
 * Boards seeded:
 *   1. Dragon Slayer Journey  — 7×7, TEAM mode, Dragon Slayers team
 *   2. F2P Challenge          — 5×5, TEAM mode, Iron Maidens team
 *   3. Champion's Gauntlet    — 9×9, TEAM mode, all 3 teams competing
 *   4. Solo Speedrun          — 7×7, SOLO mode, 6 individual players
 */
async function main() {
  // ─── ROLES ─────────────────────────────────────────────────────────────────
  const roleDefs = [
    { name: 'PLAYER',       description: 'Default role — can join boards and play' },
    { name: 'ADMIN',        description: 'Full access — manage boards, tiles, tasks and users' },
    { name: 'EDITOR',       description: 'Can create and edit boards they are assigned to' },
    { name: 'TEAM_MANAGER', description: 'Can create and manage teams' },
  ]
  const roleMap: Record<string, string> = {}
  for (const role of roleDefs) {
    const r = await prisma.role.upsert({
      where:  { name: role.name },
      update: { description: role.description },
      create: role,
    })
    roleMap[role.name] = r.id
    console.log(`✅ Role: ${role.name}`)
  }

  // ─── TASKS ─────────────────────────────────────────────────────────────────
  const taskDefs = [
    // Gathering
    { title: 'Catch a Raw Lobster',       iconUrl: 'https://oldschool.runescape.wiki/images/Raw_lobster.png',        description: 'Fish a raw lobster at a cage fishing spot (requires 40 Fishing).' },
    { title: 'Catch a Raw Shark',         iconUrl: 'https://oldschool.runescape.wiki/images/Raw_shark.png',          description: 'Fish a raw shark with a harpoon (requires 76 Fishing).' },
    { title: 'Catch a Raw Swordfish',     iconUrl: 'https://oldschool.runescape.wiki/images/Raw_swordfish.png',      description: 'Harpoon a raw swordfish at a fishing spot (requires 50 Fishing).' },
    { title: 'Mine Runite Ore',           iconUrl: 'https://oldschool.runescape.wiki/images/Runite_ore.png',         description: 'Mine Runite ore from a Runite rock (requires 85 Mining).' },
    { title: 'Smelt a Rune Bar',          iconUrl: 'https://oldschool.runescape.wiki/images/Runite_bar.png',         description: 'Smelt a Rune bar in a furnace (requires 85 Smithing).' },
    { title: 'Chop Magic Logs',           iconUrl: 'https://oldschool.runescape.wiki/images/Magic_logs.png',         description: 'Chop magic logs from a magic tree (requires 75 Woodcutting).' },
    { title: 'Craft a Nature Rune',       iconUrl: 'https://oldschool.runescape.wiki/images/Nature_rune.png',        description: 'Craft a Nature rune at the Nature Altar (requires 44 Runecraft).' },
    { title: 'Catch Red Chinchompas',     iconUrl: 'https://oldschool.runescape.wiki/images/Red_chinchompa.png',     description: 'Box-trap red chinchompas in Feldip Hills (requires 63 Hunter).' },
    // Early gear
    { title: 'Obtain a Rune Scimitar',    iconUrl: 'https://oldschool.runescape.wiki/images/Rune_scimitar.png',      description: 'Obtain a Rune scimitar — a staple mid-game melee weapon.' },
    { title: 'Obtain a Rune Platebody',   iconUrl: 'https://oldschool.runescape.wiki/images/Rune_platebody.png',     description: 'Equip a Rune platebody (requires Dragon Slayer I and 40 Defence).' },
    { title: 'Obtain a Rune 2h Sword',    iconUrl: 'https://oldschool.runescape.wiki/images/Rune_2h_sword.png',      description: 'Wield a Rune 2h sword (requires 40 Attack).' },
    { title: 'Obtain a Rune Crossbow',    iconUrl: 'https://oldschool.runescape.wiki/images/Rune_crossbow.png',      description: 'Equip a Rune crossbow (requires 61 Ranged).' },
    { title: 'Obtain Dragon Bones',       iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_bones.png',       description: 'Loot Dragon bones from any dragon for Prayer experience.' },
    // Mid-game gear
    { title: 'Obtain a Dragon Scimitar',  iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_scimitar.png',    description: 'Equip a Dragon scimitar — reward for completing Monkey Madness I.' },
    { title: 'Obtain a Dragon Med Helm',  iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_med_helm.png',    description: 'Obtain a Dragon med helm from a rare monster drop.' },
    { title: 'Obtain a Dragon Chainbody', iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_chainbody.png',   description: 'Obtain a Dragon chainbody from the Kalphite Queen or rare drop.' },
    { title: 'Obtain a Dragon 2h Sword',  iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_2h_sword.png',    description: 'Wield the powerful Dragon 2h sword (requires 60 Attack).' },
    { title: 'Obtain an Abyssal Whip',    iconUrl: 'https://oldschool.runescape.wiki/images/Abyssal_whip.png',       description: 'Obtain an Abyssal whip from an Abyssal demon (requires 85 Slayer).' },
    { title: 'Obtain an Ancient Staff',   iconUrl: 'https://oldschool.runescape.wiki/images/Ancient_staff.png',      description: 'Wield the Ancient staff — reward for completing Desert Treasure I.' },
    // Capes & prestige
    { title: 'Obtain a Fire Cape',        iconUrl: 'https://oldschool.runescape.wiki/images/Fire_cape.png',          description: 'Survive the TzHaar Fight Cave and defeat TzTok-Jad.' },
    { title: 'Obtain an Obsidian Cape',   iconUrl: 'https://oldschool.runescape.wiki/images/Obsidian_cape.png',      description: 'Purchase an Obsidian cape with Tokkul from the TzHaar shop.' },
    { title: 'Obtain an Infernal Cape',   iconUrl: 'https://oldschool.runescape.wiki/images/Infernal_cape.png',      description: 'Defeat TzKal-Zuk in the Inferno to claim the Infernal cape.' },
    { title: 'Achieve 99 in Any Skill',   iconUrl: 'https://oldschool.runescape.wiki/images/Max_cape.png',           description: 'Train any skill to level 99 and claim your skill cape.' },
    // Late-game gear
    { title: 'Obtain an Amulet of Fury',  iconUrl: 'https://oldschool.runescape.wiki/images/Amulet_of_fury.png',     description: 'Craft or purchase an Amulet of fury (requires 87 Crafting to make).' },
    { title: 'Obtain a Berserker Ring',   iconUrl: 'https://oldschool.runescape.wiki/images/Berserker_ring.png',     description: 'Obtain a Berserker ring drop from Dagannoth Rex.' },
    { title: 'Obtain Barrows Gloves',     iconUrl: 'https://oldschool.runescape.wiki/images/Barrows_gloves.png',     description: 'Complete Recipe for Disaster and purchase the best-in-slot gloves.' },
    { title: 'Obtain a Slayer Helmet',    iconUrl: 'https://oldschool.runescape.wiki/images/Slayer_helmet.png',      description: 'Craft a Slayer helmet from components (requires 55 Crafting).' },
    { title: 'Obtain Void Knight Top',    iconUrl: 'https://oldschool.runescape.wiki/images/Void_knight_top.png',    description: 'Earn 850 Void Knight commendation points at the Pest Control minigame.' },
    // Boss loot
    { title: 'Obtain Bandos Chestplate',  iconUrl: 'https://oldschool.runescape.wiki/images/Bandos_chestplate.png',  description: 'Obtain a Bandos chestplate drop from General Graardor in GWD.' },
    { title: 'Obtain Bandos Tassets',     iconUrl: 'https://oldschool.runescape.wiki/images/Bandos_tassets.png',     description: 'Obtain Bandos tassets from General Graardor in the God Wars Dungeon.' },
    { title: 'Obtain Armadyl Chestplate', iconUrl: 'https://oldschool.runescape.wiki/images/Armadyl_chestplate.png', description: "Obtain the Armadyl chestplate from Kree'arra in the God Wars Dungeon." },
    { title: 'Obtain Armadyl Chainskirt', iconUrl: 'https://oldschool.runescape.wiki/images/Armadyl_chainskirt.png', description: "Obtain the Armadyl chainskirt from Kree'arra in the God Wars Dungeon." },
    { title: 'Obtain Dragon Claws',       iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_claws.png',       description: 'Obtain Dragon claws — a powerful spec weapon from the Chambers of Xeric.' },
    { title: 'Obtain a Twisted Bow',      iconUrl: 'https://oldschool.runescape.wiki/images/Twisted_bow.png',        description: 'Obtain the legendary Twisted bow from the Chambers of Xeric.' },
    { title: 'Obtain a Dragon Full Helm', iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_full_helm.png',   description: 'Obtain a Dragon full helm — a very rare drop from Mithril Dragons.' },
  ]

  const taskIds: Record<string, string> = {}
  for (const def of taskDefs) {
    const existing = await prisma.task.findFirst({ where: { title: def.title } })
    const task = existing ?? await prisma.task.create({ data: def })
    taskIds[def.title] = task.id
  }
  console.log(`✅ ${taskDefs.length} tasks seeded`)

  // ─── FAKE USERS ─────────────────────────────────────────────────────────────
  // 8 users: 5 regular players + 1 editor + 1 team_manager + 1 with both
  const fakeUserDefs = [
    { discordId: '999000000000000001', discordUsername: 'ZezimaClan',    nickname: 'Zezima',     avatarUrl: 'https://cdn.discordapp.com/embed/avatars/0.png', roles: [] },
    { discordId: '999000000000000002', discordUsername: 'PuroPuro99',    nickname: null,          avatarUrl: 'https://cdn.discordapp.com/embed/avatars/1.png', roles: [] },
    { discordId: '999000000000000003', discordUsername: 'RuneKnight42',  nickname: 'RuneKnight', avatarUrl: 'https://cdn.discordapp.com/embed/avatars/2.png', roles: [] },
    { discordId: '999000000000000004', discordUsername: 'IronMaidHD',    nickname: 'IronMaid',   avatarUrl: 'https://cdn.discordapp.com/embed/avatars/3.png', roles: [] },
    { discordId: '999000000000000005', discordUsername: 'BarrowsLoot',   nickname: null,          avatarUrl: 'https://cdn.discordapp.com/embed/avatars/4.png', roles: [] },
    { discordId: '999000000000000006', discordUsername: 'BoardBuilder',  nickname: 'Builder',    avatarUrl: 'https://cdn.discordapp.com/embed/avatars/5.png', roles: ['EDITOR'] },
    { discordId: '999000000000000007', discordUsername: 'TeamCaptain',   nickname: 'Captain',    avatarUrl: 'https://cdn.discordapp.com/embed/avatars/0.png', roles: ['TEAM_MANAGER'] },
    { discordId: '999000000000000008', discordUsername: 'ClanLeader',    nickname: 'Leader',     avatarUrl: 'https://cdn.discordapp.com/embed/avatars/1.png', roles: ['EDITOR', 'TEAM_MANAGER'] },
  ]

  const fakeUsers: { id: string; discordUsername: string; nickname: string | null }[] = []
  for (const def of fakeUserDefs) {
    const user = await prisma.user.upsert({
      where:  { discordId: def.discordId },
      update: { discordUsername: def.discordUsername, avatarUrl: def.avatarUrl, nickname: def.nickname },
      create: { discordId: def.discordId, discordUsername: def.discordUsername, avatarUrl: def.avatarUrl, nickname: def.nickname },
    })
    fakeUsers.push({ id: user.id, discordUsername: user.discordUsername, nickname: user.nickname })

    // Assign roles
    for (const roleName of def.roles) {
      const roleId = roleMap[roleName]
      if (roleId) {
        await prisma.userRole.upsert({
          where:  { userId_roleId: { userId: user.id, roleId } },
          update: {},
          create: { userId: user.id, roleId },
        })
      }
    }
    console.log(`✅ User: ${def.discordUsername}${def.roles.length ? ` [${def.roles.join(', ')}]` : ''}`)
  }

  // Grant canCreateBoards to ClanLeader (user index 7)
  const clanLeader = fakeUsers[7]
  await prisma.userPermission.upsert({
    where:  { userId_permissionKey: { userId: clanLeader.id, permissionKey: 'canCreateBoards' } },
    update: {},
    create: { userId: clanLeader.id, permissionKey: 'canCreateBoards' },
  })
  console.log(`✅ Permission canCreateBoards granted to ${clanLeader.discordUsername}`)

  // ─── FIND REAL ADMIN ────────────────────────────────────────────────────────
  const adminRole = await prisma.role.findFirst({ where: { name: 'ADMIN' } })
  const adminUserRole = adminRole
    ? await prisma.userRole.findFirst({ where: { roleId: adminRole.id } })
    : null
  const adminUserId = adminUserRole?.userId ?? null
  console.log(adminUserId ? `✅ Admin found — added as board author` : `ℹ️  No admin — fake user as only author`)

  // ─── TEAMS ──────────────────────────────────────────────────────────────────
  // Dragon Slayers : Zezima (0), PuroPuro99 (1), RuneKnight (2)
  // Iron Maidens   : IronMaid (3), BarrowsLoot (4), ClanLeader (7)
  // Barrows Bros   : BoardBuilder (5), TeamCaptain (6), ClanLeader (7)
  const teamDefs = [
    {
      name: 'Dragon Slayers',
      iconUrl: 'https://oldschool.runescape.wiki/images/Dragon_scimitar.png',
      memberIndices: [0, 1, 2],
    },
    {
      name: 'Iron Maidens',
      iconUrl: 'https://oldschool.runescape.wiki/images/Rune_platebody.png',
      memberIndices: [3, 4, 7],
    },
    {
      name: 'Barrows Brothers',
      iconUrl: 'https://oldschool.runescape.wiki/images/Bandos_chestplate.png',
      memberIndices: [5, 6, 7],
    },
  ]

  // Map: teamName → { id, memberIndices }
  const teamMap: Record<string, { id: string; memberIndices: number[] }> = {}

  for (const def of teamDefs) {
    const existing = await prisma.team.findFirst({ where: { name: def.name } })
    const team = existing
      ? await prisma.team.update({ where: { id: existing.id }, data: { iconUrl: def.iconUrl } })
      : await prisma.team.create({ data: { name: def.name, iconUrl: def.iconUrl } })

    for (const idx of def.memberIndices) {
      const userId = fakeUsers[idx].id
      await prisma.teamMember.upsert({
        where:  { teamId_userId: { teamId: team.id, userId } },
        update: {},
        create: { teamId: team.id, userId },
      })
    }

    teamMap[def.name] = { id: team.id, memberIndices: def.memberIndices }
    console.log(`✅ Team: ${def.name} (${def.memberIndices.length} members)`)
  }

  const dragonSlayersId  = teamMap['Dragon Slayers'].id
  const ironMaidensId    = teamMap['Iron Maidens'].id
  const barrowsBrothersId = teamMap['Barrows Brothers'].id

  // ─── BOARDS ─────────────────────────────────────────────────────────────────
  // Delete existing seed boards for a clean slate on every run
  const boardTitles = ['Dragon Slayer Journey', 'F2P Challenge', "Champion's Gauntlet", 'Solo Speedrun']
  for (const title of boardTitles) {
    const existing = await prisma.board.findFirst({ where: { title } })
    if (existing) {
      await prisma.board.delete({ where: { id: existing.id } })
    }
  }

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

  // ── Board 1: Dragon Slayer Journey — TEAM mode, Dragon Slayers ────────────
  {
    const board = await prisma.board.create({
      data: {
        title:         'Dragon Slayer Journey',
        description:   'Journey through Gielinor completing OSRS tasks on your path to becoming a Dragon Slayer. The Dragon Slayers team competes here — watch out for the snakes!',
        size:          BoardSize.SIZE_7X7,
        mode:          BoardMode.TEAM,
        diceRollLimit: 1,
        startDate:     new Date('2026-03-01'),
        endDate:       new Date('2026-06-01'),
        // Link the Dragon Slayers team to this board
        boardTeams: { create: [{ teamId: dragonSlayersId }] },
      },
    })
    await seedTiles(board.id, 49,
      [[48, 5], [38, 15], [29, 8], [22, 3], [43, 33]],
      [[2, 19], [11, 26], [17, 36], [30, 46], [35, 44]],
      taskOrder,
      { 0: taskIds['Catch a Raw Lobster'], 48: taskIds['Achieve 99 in Any Skill'] },
    )
    await seedBoardAuthors(board.id, [fakeUsers[0].id, fakeUsers[5].id], adminUserId)
    // One shared PlayerBoard for the Dragon Slayers team (first member as representative)
    await seedTeamPlayerBoard(board.id, dragonSlayersId, fakeUsers[0].id, 25)
    console.log(`✅ Board: ${board.title} — TEAM, Dragon Slayers at tile 26`)
  }

  // ── Board 2: F2P Challenge — TEAM mode, Iron Maidens ─────────────────────
  {
    const board = await prisma.board.create({
      data: {
        title:         'F2P Challenge',
        description:   'Free-to-play only! No membership required. The Iron Maidens team battles it out on this beginner board.',
        size:          BoardSize.SIZE_5X5,
        mode:          BoardMode.TEAM,
        diceRollLimit: 2,
        startDate:     new Date('2026-04-01'),
        endDate:       new Date('2026-05-01'),
        boardTeams: { create: [{ teamId: ironMaidensId }] },
      },
    })
    await seedTiles(board.id, 25,
      [[23, 4], [20, 8], [16, 1]],
      [[2, 11], [6, 17], [13, 22]],
      taskOrder.slice(0, 20),
      { 0: taskIds['Catch a Raw Lobster'], 24: taskIds['Obtain a Dragon Scimitar'] },
    )
    await seedBoardAuthors(board.id, [fakeUsers[7].id], adminUserId)
    // One shared PlayerBoard for the Iron Maidens team
    await seedTeamPlayerBoard(board.id, ironMaidensId, fakeUsers[3].id, 14)
    console.log(`✅ Board: ${board.title} — TEAM, Iron Maidens at tile 15`)
  }

  // ── Board 3: Champion's Gauntlet — TEAM mode, all 3 teams ────────────────
  {
    const board = await prisma.board.create({
      data: {
        title:         "Champion's Gauntlet",
        description:   "The ultimate OSRS challenge. Three teams compete for glory across 81 tiles. Bosses, quests, and 99s await.",
        size:          BoardSize.SIZE_9X9,
        mode:          BoardMode.TEAM,
        diceRollLimit: 1,
        startDate:     new Date('2026-01-01'),
        endDate:       new Date('2026-12-31'),
        boardTeams: {
          create: [
            { teamId: dragonSlayersId },
            { teamId: ironMaidensId },
            { teamId: barrowsBrothersId },
          ],
        },
      },
    })
    await seedTiles(board.id, 81,
      [[79, 10], [68, 35], [60, 22], [50, 18], [42, 7], [75, 56]],
      [[4, 26], [16, 44], [30, 65], [48, 73], [63, 77]],
      taskOrder,
      { 0: taskIds['Catch a Raw Lobster'], 80: taskIds['Obtain a Twisted Bow'] },
    )
    await seedBoardAuthors(board.id, [fakeUsers[5].id, fakeUsers[7].id], adminUserId)
    // One shared PlayerBoard per team — different positions for a real leaderboard
    await seedTeamPlayerBoard(board.id, dragonSlayersId,   fakeUsers[0].id, 45)
    await seedTeamPlayerBoard(board.id, ironMaidensId,     fakeUsers[3].id, 30)
    await seedTeamPlayerBoard(board.id, barrowsBrothersId, fakeUsers[5].id, 65)
    console.log(`✅ Board: ${board.title} — TEAM, 3 teams competing`)
  }

  // ── Board 4: Solo Speedrun — SOLO mode, 6 individual players ─────────────
  {
    const board = await prisma.board.create({
      data: {
        title:         'Solo Speedrun',
        description:   'A personal challenge board — play at your own pace with no roll limits. All players compete individually.',
        size:          BoardSize.SIZE_7X7,
        mode:          BoardMode.SOLO,
        diceRollLimit: null,         // unlimited rolls
        startDate:     new Date('2026-03-15'),
        endDate:       new Date('2026-05-15'),
      },
    })
    await seedTiles(board.id, 49,
      [[46, 2], [35, 14], [27, 5]],
      [[1, 20], [9, 32], [22, 44]],
      taskOrder,
      { 0: taskIds['Catch a Raw Lobster'], 48: taskIds['Obtain an Infernal Cape'] },
    )
    await seedBoardAuthors(board.id, [fakeUsers[2].id], adminUserId)
    // All 8 fake users playing individually — rich leaderboard for testing
    await seedPlayerBoards(board.id, [
      { userId: fakeUsers[0].id, position: 40 },
      { userId: fakeUsers[1].id, position: 32 },
      { userId: fakeUsers[2].id, position: 12 },
      { userId: fakeUsers[3].id, position: 24 },
      { userId: fakeUsers[4].id, position: 5  },
      { userId: fakeUsers[5].id, position: 46 },
      { userId: fakeUsers[6].id, position: 18 },
      { userId: fakeUsers[7].id, position: 37 },
    ])
    console.log(`✅ Board: ${board.title} — SOLO, 8 players`)
  }

  console.log('\n🌱 Seed complete!')
}

// ─── HELPERS ────────────────────────────────────────────────────────────────

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
    const type: TileType = snakeMap.has(pos) ? TileType.SNAKE
      : ladderMap.has(pos) ? TileType.LADDER
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

async function seedBoardAuthors(
  boardId: string,
  fakeAuthorIds: string[],
  adminUserId: string | null,
) {
  const ids = [...new Set([...(adminUserId ? [adminUserId] : []), ...fakeAuthorIds])]
  for (let i = 0; i < ids.length; i++) {
    const userId = ids[i]
    await prisma.boardAuthor.upsert({
      where:  { boardId_userId: { boardId, userId } },
      update: {},
      create: { boardId, userId, isOwner: i === 0 },
    })
  }
}

/**
 * Seed SOLO-mode player boards — one entry per user.
 */
async function seedPlayerBoards(
  boardId: string,
  players: { userId: string; position: number }[],
) {
  for (const p of players) {
    await prisma.playerBoard.upsert({
      where:  { userId_boardId: { userId: p.userId, boardId } },
      update: { currentPosition: p.position },
      create: { userId: p.userId, boardId, currentPosition: p.position },
    })
  }
}

/**
 * Seed a single shared PlayerBoard for a TEAM-mode board.
 * Uses findFirst + create/update because the teamId_boardId unique is on a
 * nullable column and Prisma requires a non-null value to use the unique index.
 */
async function seedTeamPlayerBoard(
  boardId: string,
  teamId: string,
  representativeUserId: string,
  position: number,
) {
  const existing = await prisma.playerBoard.findFirst({ where: { teamId, boardId } })
  if (existing) {
    await prisma.playerBoard.update({
      where: { id: existing.id },
      data:  { currentPosition: position, userId: representativeUserId },
    })
  } else {
    await prisma.playerBoard.create({
      data: {
        userId:          representativeUserId,
        boardId,
        teamId,
        currentPosition: position,
      },
    })
  }
}

main()
  .catch(e => {
    console.error('Seed failed:', e)
    process.exit(1)
  })
  .finally(async () => {
    await prisma.$disconnect()
  })
