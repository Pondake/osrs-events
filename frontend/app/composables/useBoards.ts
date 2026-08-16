import type { ComputedRef } from 'vue';
import type {
  BoardAuthorEntity,
  BoardEntity,
  CreateBoardInput,
  UpdateBoardInput,
} from '~/types/graphql';

// ─── Field selections ────────────────────────────────────────────────────────

const BOARD_SUMMARY_FIELDS = `
  id title description startDate endDate size mode diceRollLimit
  isListed accessMode requiredGuildId
  createdAt updatedAt
  authors { id isOwner user { id discordUsername nickname avatarUrl } }
  boardTeams { id boardId teamId team { id name iconUrl } }
`;

const BOARD_FULL_FIELDS = `
  ${BOARD_SUMMARY_FIELDS}
  tiles {
    id position type targetPosition titleOverride displayTitle iconUrl
    task { id title iconUrl description }
  }
`;

// ─── Queries & mutations ─────────────────────────────────────────────────────

const BOARDS_QUERY = `
  query Boards {
    boards { ${BOARD_SUMMARY_FIELDS} }
  }
`;

const ALL_BOARDS_QUERY = `
  query AllBoards {
    allBoards { ${BOARD_SUMMARY_FIELDS} }
  }
`;

const BOARD_QUERY = `
  query Board($id: ID!) {
    board(id: $id) { ${BOARD_FULL_FIELDS} }
  }
`;

const CREATE_BOARD_MUTATION = `
  mutation CreateBoard($input: CreateBoardInput!) {
    createBoard(input: $input) { id }
  }
`;

const UPDATE_BOARD_MUTATION = `
  mutation UpdateBoard($id: ID!, $input: UpdateBoardInput!) {
    updateBoard(id: $id, input: $input) { id }
  }
`;

const DELETE_BOARD_MUTATION = `
  mutation DeleteBoard($id: ID!) {
    deleteBoard(id: $id) { id }
  }
`;

const ADD_BOARD_AUTHOR_MUTATION = `
  mutation AddBoardAuthor($boardId: ID!, $userId: ID!) {
    addBoardAuthor(boardId: $boardId, userId: $userId) {
      id isOwner user { id discordUsername nickname avatarUrl }
    }
  }
`;

const REMOVE_BOARD_AUTHOR_MUTATION = `
  mutation RemoveBoardAuthor($boardId: ID!, $userId: ID!) {
    removeBoardAuthor(boardId: $boardId, userId: $userId)
  }
`;

const ADD_TEAM_TO_BOARD_MUTATION = `
  mutation AddTeamToBoard($boardId: ID!, $teamId: ID!) {
    addTeamToBoard(boardId: $boardId, teamId: $teamId) {
      id boardId teamId team { id name iconUrl }
    }
  }
`;

const REMOVE_TEAM_FROM_BOARD_MUTATION = `
  mutation RemoveTeamFromBoard($boardId: ID!, $teamId: ID!) {
    removeTeamFromBoard(boardId: $boardId, teamId: $teamId)
  }
`;

// ─── Composables ─────────────────────────────────────────────────────────────

/**
 * Reactive list of all boards (public, SSR-safe).
 * Includes board summary fields + authors + boardTeams. No tiles.
 */
export async function useBoards() {
  const { data, pending, error, refresh } = await useGql<{ boards: BoardEntity[] }>(BOARDS_QUERY);

  return {
    boards: computed(() => data.value?.boards ?? []),
    pending,
    error,
    refresh,
    createBoard,
    deleteBoard,
  };
}

/**
 * Create a new board — imperative mutation.
 */
export async function createBoard(input: CreateBoardInput): Promise<BoardEntity> {
  const result = await useGqlMutation<{ createBoard: BoardEntity }>(CREATE_BOARD_MUTATION, {
    input,
  });
  return result.createBoard;
}

/**
 * Delete a board by id — imperative mutation.
 */
export async function deleteBoard(id: string): Promise<BoardEntity> {
  const result = await useGqlMutation<{ deleteBoard: BoardEntity }>(DELETE_BOARD_MUTATION, { id });
  return result.deleteBoard;
}

/**
 * Update board metadata — imperative mutation.
 */
export async function updateBoard(id: string, input: UpdateBoardInput): Promise<BoardEntity> {
  const result = await useGqlMutation<{ updateBoard: BoardEntity }>(UPDATE_BOARD_MUTATION, {
    id,
    input,
  });
  return result.updateBoard;
}

/**
 * Add a co-editor to a board — imperative mutation.
 */
export async function addBoardAuthor(boardId: string, userId: string): Promise<BoardAuthorEntity> {
  const result = await useGqlMutation<{ addBoardAuthor: BoardAuthorEntity }>(
    ADD_BOARD_AUTHOR_MUTATION,
    { boardId, userId },
  );
  return result.addBoardAuthor;
}

/**
 * Remove a co-editor from a board — imperative mutation.
 */
export async function removeBoardAuthor(boardId: string, userId: string): Promise<boolean> {
  const result = await useGqlMutation<{ removeBoardAuthor: boolean }>(
    REMOVE_BOARD_AUTHOR_MUTATION,
    { boardId, userId },
  );
  return result.removeBoardAuthor;
}

/**
 * Add a team to a TEAM-mode board — imperative mutation.
 */
export async function addTeamToBoard(boardId: string, teamId: string) {
  const result = await useGqlMutation<{
    addTeamToBoard: {
      id: string;
      boardId: string;
      teamId: string;
      team: { id: string; name: string; iconUrl?: string };
    };
  }>(ADD_TEAM_TO_BOARD_MUTATION, { boardId, teamId });
  return result.addTeamToBoard;
}

/**
 * Remove a team from a board — imperative mutation.
 */
export async function removeTeamFromBoard(boardId: string, teamId: string): Promise<boolean> {
  const result = await useGqlMutation<{ removeTeamFromBoard: boolean }>(
    REMOVE_TEAM_FROM_BOARD_MUTATION,
    { boardId, teamId },
  );
  return result.removeTeamFromBoard;
}

/**
 * Reactive list of all boards including unlisted — for admin pages.
 */
export async function useAllBoards() {
  const { data, pending, error, refresh } = await useGql<{ allBoards: BoardEntity[] }>(
    ALL_BOARDS_QUERY,
  );

  return {
    boards: computed(() => data.value?.allBoards ?? []),
    pending,
    error,
    refresh,
    createBoard,
    deleteBoard,
  };
}

/**
 * Reactive single board with full tile data (SSR-safe).
 */
export async function useBoard(boardId: string | ComputedRef<string>) {
  const vars = computed(() => ({ id: toValue(boardId) }));
  const { data, pending, error, refresh } = await useGql<{ board: BoardEntity | null }>(
    BOARD_QUERY,
    vars,
  );

  return {
    board: computed(() => data.value?.board ?? null),
    pending,
    error,
    refresh,
    updateBoard,
    addBoardAuthor,
    removeBoardAuthor,
    addTeamToBoard,
    removeTeamFromBoard,
  };
}
