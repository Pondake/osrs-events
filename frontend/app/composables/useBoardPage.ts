import { parseDate } from '@internationalized/date'
import type { Ref } from 'vue'
import type { TileEntity, RollResultEntity, BoardEntity, PlayerBoardEntity } from '~/types/graphql'
import { useAuthStore } from '~/stores/auth'
import { usePlayerBoard, useBoardPlayerStates } from '~/composables/usePlayers'
import { usePermissions } from '~/composables/usePermissions'
import { BOARD_TILE_COUNT, BOARD_MIN_WIDTH } from '~/utils/board'
import type { BoardFormData } from '~/components/Board/SettingsForm.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface EditingTileState {
  id?: string
  position: number
  boardId: string
  task: TileEntity['task']
  titleOverride: string | null
  type: TileEntity['type']
  targetPosition: number | null
}

// ─── Composable ───────────────────────────────────────────────────────────────

/**
 * Encapsulates all board game logic for the boards/[id] page.
 *
 * Accepts SSR-loaded board data from the page and handles all reactive state:
 * player moves, dice rolling, tile completion, settings editing, leaderboard refresh.
 *
 * @param boardId   - The board's UUID
 * @param board     - SSR-loaded board ref (from useBoard)
 * @param refresh   - Callback to re-fetch board data
 * @param updateBoard - Callback to update board settings
 */
export function useBoardPage(
  boardId: string,
  board: Ref<BoardEntity | null>,
  refresh: () => Promise<void>,
  updateBoard: (id: string, input: Record<string, unknown>) => Promise<void>,
) {
  const authStore = useAuthStore()
  const toast = useToast()
  const { t } = useI18n()
  const permissions = usePermissions()

  // ─── Board computed ───────────────────────────────────────────────────────

  const totalTiles = computed(() => BOARD_TILE_COUNT[board.value?.size ?? 'SIZE_7X7'] ?? 49)
  const boardMinWidth = computed(() => BOARD_MIN_WIDTH[board.value?.size ?? 'SIZE_7X7'] ?? 'min-w-[380px]')
  const isTeamBoard = computed(() => (board.value as any)?.mode === 'TEAM')

  // ─── Player state (client-side, loaded in onMounted) ─────────────────────

  const {
    playerBoard,
    loading: playerBoardLoading,
    load: loadPlayerBoard,
    rollDice,
    completeTile,
    uncompleteTile,
  } = usePlayerBoard(boardId)

  const { playerStates: allPlayerStates, load: loadPlayerStates } = useBoardPlayerStates(boardId)

  onMounted(() => {
    loadPlayerBoard()
    loadPlayerStates()
  })

  // ─── Permissions ──────────────────────────────────────────────────────────

  const canEdit = computed(() =>
    board.value ? permissions.canEditBoard(board.value.authors) : false,
  )

  // ─── Filtered player states ───────────────────────────────────────────────

  const otherPlayerStates = computed(() => {
    if (isTeamBoard.value) {
      const myTeamId = playerBoard.value?.teamId
      return allPlayerStates.value.filter(p => (p as any).teamId !== myTeamId)
    }
    return allPlayerStates.value.filter(p => p.userId !== authStore.user?.id)
  })

  const completedPositions = computed(() => {
    if (!playerBoard.value || !board.value) return []
    const tileIdToPos = new Map((board.value.tiles ?? []).map(t => [t.id, t.position]))
    return playerBoard.value.completedTiles
      .map(ct => tileIdToPos.get(ct.tileId) ?? -1)
      .filter(p => p >= 0)
  })

  // ─── Display state ────────────────────────────────────────────────────────

  const showOtherPlayers = ref(false)
  const editMode = ref(false)

  /** Augments playerBoard with user data for the board token */
  const myPlayerForBoard = computed(() => {
    if (!playerBoard.value) return null
    if (isTeamBoard.value) return playerBoard.value
    if (!authStore.user) return null
    return {
      ...playerBoard.value,
      userId: authStore.user.id,
      user: {
        id: authStore.user.id,
        discordUsername: authStore.user.discordUsername,
        avatarUrl: authStore.user.avatarUrl ?? null,
      },
    }
  })

  const boardPlayerStates = computed((): PlayerBoardEntity[] => [
    ...(myPlayerForBoard.value ? [myPlayerForBoard.value as PlayerBoardEntity] : []),
    ...(showOtherPlayers.value ? otherPlayerStates.value : []),
  ])

  // ─── Tile interaction ─────────────────────────────────────────────────────

  const clickedTile = ref<TileEntity | null>(null)
  const editingTile = ref<EditingTileState | null>(null)

  function handleTileClick(position: number) {
    const tile = (board.value?.tiles ?? []).find(t => t.position === position) ?? null
    if (editMode.value && canEdit.value) {
      editingTile.value = {
        id: tile?.id,
        position,
        boardId,
        task: tile?.task ?? null,
        titleOverride: tile?.titleOverride ?? null,
        type: tile?.type ?? 'NORMAL',
        targetPosition: tile?.targetPosition ?? null,
      }
    } else {
      clickedTile.value = tile
    }
  }

  /** Update a tile's task in-place without a full board reload */
  function onTaskUpdated({ tileId, task }: { tileId: string | undefined; task: TileEntity['task'] }) {
    if (!tileId || !board.value) return
    const tile = (board.value.tiles ?? []).find(t => t.id === tileId)
    if (tile) tile.task = task
  }

  // ─── Roll limit editing ───────────────────────────────────────────────────

  const editRollLimit = ref(3)
  const editUnlimited = ref(false)
  const savingRollLimit = ref(false)

  watch(
    board,
    b => {
      if (b) {
        editRollLimit.value = b.diceRollLimit ?? 3
        editUnlimited.value = b.diceRollLimit === null
      }
    },
    { immediate: true },
  )

  async function saveRollLimit() {
    savingRollLimit.value = true
    try {
      await updateBoard(boardId, {
        diceRollLimit: editUnlimited.value ? null : editRollLimit.value,
      })
      await refresh()
      toast.add({ title: t('admin.board_updated'), color: 'success' })
    } catch (e) {
      toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
    } finally {
      savingRollLimit.value = false
    }
  }

  // ─── Board settings modal ─────────────────────────────────────────────────

  const showSettingsModal = ref(false)

  const boardSettingsData = computed((): Partial<BoardFormData> | undefined => {
    if (!board.value) return undefined
    return {
      title: board.value.title,
      description: board.value.description ?? '',
      size: board.value.size as 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9',
      mode: ((board.value as any).mode ?? 'SOLO') as 'SOLO' | 'TEAM',
      diceRollLimit: board.value.diceRollLimit ?? 3,
      unlimitedRolls: board.value.diceRollLimit === null,
      selectedAuthors: board.value.authors.map(a => ({
        id: a.user.id,
        discordUsername: a.user.discordUsername,
        avatarUrl: a.user.avatarUrl,
      })),
      assignedTeams: ((board.value as any).boardTeams ?? []).map((bt: any) => ({
        teamId: bt.teamId,
        team: bt.team,
      })),
      startDate: board.value.startDate
        ? parseDate(board.value.startDate.toString().slice(0, 10))
        : null,
      endDate: board.value.endDate
        ? parseDate(board.value.endDate.toString().slice(0, 10))
        : null,
    }
  })

  async function onSettingsSaved() {
    await refresh()
  }

  // ─── Game actions ─────────────────────────────────────────────────────────

  const rolling = ref(false)
  const completing = ref(false)
  const showBingo = ref(false)
  const lastRollResult = ref<RollResultEntity | null>(null)

  /**
   * Incrementing this key forces the leaderboard component to remount,
   * triggering its onMounted(load) and refreshing scores automatically.
   */
  const leaderboardKey = ref(0)

  async function onRoll() {
    if (rolling.value) return
    rolling.value = true
    try {
      const roll = await rollDice()
      clickedTile.value = null
      lastRollResult.value = roll
      leaderboardKey.value++

      if (roll.jump === 'snake') {
        toast.add({
          title: t('board.rolled_snake', {
            from: (roll.landedOn ?? 0) + 1,
            to: roll.newPosition + 1,
          }),
          color: 'error',
        })
      } else if (roll.jump === 'ladder') {
        toast.add({
          title: t('board.rolled_ladder', {
            from: (roll.landedOn ?? 0) + 1,
            to: roll.newPosition + 1,
          }),
          color: 'success',
        })
      } else {
        toast.add({ title: t('board.you_rolled', { value: roll.rolled }), color: 'primary' })
      }
    } catch (e) {
      toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
    } finally {
      rolling.value = false
    }
  }

  async function onCompleteTile(tile: TileEntity) {
    completing.value = true
    try {
      await completeTile(tile.id)
      leaderboardKey.value++
      toast.add({ title: t('board.tile_completed'), color: 'success' })
      if (tile.position === totalTiles.value - 1) showBingo.value = true
    } catch (e) {
      toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
    } finally {
      completing.value = false
    }
  }

  async function onUncompleteTile(tile: TileEntity) {
    completing.value = true
    try {
      await uncompleteTile(tile.id)
      leaderboardKey.value++
      toast.add({ title: t('board.tile_uncompleted'), color: 'neutral' })
    } catch (e) {
      toast.add({ title: t('errors.generic'), description: (e as Error).message, color: 'error' })
    } finally {
      completing.value = false
    }
  }

  return {
    // Board
    totalTiles,
    boardMinWidth,
    isTeamBoard,
    // Player
    playerBoard,
    playerBoardLoading,
    otherPlayerStates,
    completedPositions,
    // Display
    showOtherPlayers,
    editMode,
    boardPlayerStates,
    // Permissions
    canEdit,
    // Tile interaction
    clickedTile,
    editingTile,
    handleTileClick,
    onTaskUpdated,
    // Roll limit
    editRollLimit,
    editUnlimited,
    savingRollLimit,
    saveRollLimit,
    // Settings modal
    showSettingsModal,
    boardSettingsData,
    onSettingsSaved,
    // Game actions
    rolling,
    completing,
    showBingo,
    lastRollResult,
    leaderboardKey,
    onRoll,
    onCompleteTile,
    onUncompleteTile,
  }
}
