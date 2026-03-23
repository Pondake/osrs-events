import type { TileEntity, UpsertTileInput } from '~/types/graphql'

// ─── Queries & mutations ─────────────────────────────────────────────────────

const TILE_FIELDS = `
  id boardId position type targetPosition titleOverride displayTitle iconUrl
  createdAt updatedAt
  task { id title iconUrl description }
`

const TILES_QUERY = `
  query Tiles($boardId: ID!) {
    tiles(boardId: $boardId) { ${TILE_FIELDS} }
  }
`

const UPSERT_TILE_MUTATION = `
  mutation UpsertTile($input: UpsertTileInput!) {
    upsertTile(input: $input) { ${TILE_FIELDS} }
  }
`

const DELETE_TILE_MUTATION = `
  mutation DeleteTile($id: ID!) {
    deleteTile(id: $id) { id }
  }
`

const CLEAR_SNAKE_LADDER_MUTATION = `
  mutation ClearSnakeLadder($id: ID!) {
    clearSnakeLadder(id: $id) { ${TILE_FIELDS} }
  }
`

// ─── Composable ──────────────────────────────────────────────────────────────

/**
 * Reactive tile list for a given board (SSR-safe, public).
 * Note: tiles are also embedded in the full board query from useBoard().
 * Use this composable when you need standalone tile management.
 */
export async function useTiles(boardId: string) {
  const { data, pending, error, refresh } = await useGql<{ tiles: TileEntity[] }>(TILES_QUERY, {
    boardId,
  })

  async function upsertTile(input: UpsertTileInput): Promise<TileEntity> {
    const result = await useGqlMutation<{ upsertTile: TileEntity }>(UPSERT_TILE_MUTATION, { input })
    return result.upsertTile
  }

  async function deleteTile(id: string): Promise<TileEntity> {
    const result = await useGqlMutation<{ deleteTile: TileEntity }>(DELETE_TILE_MUTATION, { id })
    return result.deleteTile
  }

  async function clearSnakeLadder(id: string): Promise<TileEntity> {
    const result = await useGqlMutation<{ clearSnakeLadder: TileEntity }>(
      CLEAR_SNAKE_LADDER_MUTATION,
      { id },
    )
    return result.clearSnakeLadder
  }

  return {
    tiles: computed(() => data.value?.tiles ?? []),
    pending,
    error,
    refresh,
    upsertTile,
    deleteTile,
    clearSnakeLadder,
  }
}
