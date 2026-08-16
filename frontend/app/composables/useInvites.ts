import type { BoardInviteEntity, CreateInviteInput } from '~/types/graphql';

const INVITE_FIELDS = `id boardId token shortCode label expiresAt maxUses useCount createdAt`;

const BOARD_INVITES_QUERY = `
  query BoardInvites($boardId: ID!) {
    boardInvites(boardId: $boardId) { ${INVITE_FIELDS} }
  }
`;

const CREATE_INVITE_MUTATION = `
  mutation CreateInvite($input: CreateInviteInput!) {
    createInvite(input: $input) { ${INVITE_FIELDS} }
  }
`;

const REVOKE_INVITE_MUTATION = `
  mutation RevokeInvite($inviteId: ID!) {
    revokeInvite(inviteId: $inviteId)
  }
`;

/**
 * Reactive list of invites for a board.
 * Only usable by board owners and admins.
 */
export function useBoardInvites(boardId: string) {
  const invites = ref<BoardInviteEntity[]>([]);
  const loading = ref(false);

  async function load() {
    loading.value = true;
    try {
      const result = await useGqlMutation<{ boardInvites: BoardInviteEntity[] }>(
        BOARD_INVITES_QUERY,
        { boardId },
      );
      invites.value = result.boardInvites ?? [];
    } catch {
      invites.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function createInvite(
    input: Omit<CreateInviteInput, 'boardId'>,
  ): Promise<BoardInviteEntity> {
    const result = await useGqlMutation<{ createInvite: BoardInviteEntity }>(
      CREATE_INVITE_MUTATION,
      { input: { ...input, boardId } },
    );
    const invite = result.createInvite;
    invites.value = [invite, ...invites.value];
    return invite;
  }

  async function revokeInvite(inviteId: string): Promise<void> {
    await useGqlMutation(REVOKE_INVITE_MUTATION, { inviteId });
    invites.value = invites.value.filter(i => i.id !== inviteId);
  }

  return { invites, loading, load, createInvite, revokeInvite };
}
