import type { BoardAccessEntity, PlayerBoardEntity } from '~/types/graphql';

const BOARD_ACCESS_FIELDS = `id boardId userId inviteId accessMode joinedAt`;

const MY_BOARD_ACCESS_QUERY = `
  query MyBoardAccess($boardId: ID!) {
    myBoardAccess(boardId: $boardId) { ${BOARD_ACCESS_FIELDS} }
  }
`;

const JOIN_BOARD_MUTATION = `
  mutation JoinBoard($boardId: ID!, $tokenOrCode: String) {
    joinBoard(boardId: $boardId, tokenOrCode: $tokenOrCode) { ${BOARD_ACCESS_FIELDS} }
  }
`;

/**
 * Reactive lookup of the current user's board access record.
 * Does not auto-create — purely a read. Returns null if user has not joined.
 */
export function useMyBoardAccess(boardId: string) {
  const access = ref<BoardAccessEntity | null>(null);
  const loading = ref(false);

  async function load() {
    loading.value = true;
    try {
      const result = await useGqlMutation<{ myBoardAccess: BoardAccessEntity | null }>(
        MY_BOARD_ACCESS_QUERY,
        { boardId },
      );
      access.value = result.myBoardAccess ?? null;
    } catch {
      access.value = null;
    } finally {
      loading.value = false;
    }
  }

  return { access, loading, load };
}

/**
 * Join a board — validates access and creates a BoardAccess record.
 * For INVITE boards, pass the invite tokenOrCode.
 * Returns the BoardAccess record.
 */
export async function joinBoard(boardId: string, tokenOrCode?: string): Promise<BoardAccessEntity> {
  const result = await useGqlMutation<{ joinBoard: BoardAccessEntity }>(JOIN_BOARD_MUTATION, {
    boardId,
    tokenOrCode: tokenOrCode ?? null,
  });
  return result.joinBoard;
}
