import type { MaybeRef } from 'vue';
import type { UserEntity } from '~/types/graphql';

// ─── Field selections ────────────────────────────────────────────────────────

const USER_FIELDS = `
  id discordId discordUsername nickname avatarUrl createdAt updatedAt
  userRoles { id role { id name description } }
`;

// ─── Queries & mutations ─────────────────────────────────────────────────────

const USERS_QUERY = `
  query Users($search: String, $limit: Float) {
    users(search: $search, limit: $limit) { ${USER_FIELDS} }
  }
`;

const ME_QUERY = `
  query Me {
    me { ${USER_FIELDS} }
  }
`;

const UPDATE_PROFILE_MUTATION = `
  mutation UpdateProfile($nickname: String) {
    updateProfile(nickname: $nickname) { ${USER_FIELDS} }
  }
`;

const ASSIGN_ROLE_MUTATION = `
  mutation AssignRole($userId: ID!, $roleName: String!) {
    assignRole(userId: $userId, roleName: $roleName) { ${USER_FIELDS} }
  }
`;

const REMOVE_ROLE_MUTATION = `
  mutation RemoveRole($userId: ID!, $roleName: String!) {
    removeRole(userId: $userId, roleName: $roleName) { ${USER_FIELDS} }
  }
`;

const USER_PERMISSIONS_QUERY = `
  query UserPermissions($userId: ID!) {
    userPermissions(userId: $userId) { id permissionKey }
  }
`;

const GRANT_PERMISSION_MUTATION = `
  mutation GrantPermission($userId: ID!, $permissionKey: PermissionKey!) {
    grantPermission(userId: $userId, permissionKey: $permissionKey) { id permissionKey }
  }
`;

const REVOKE_PERMISSION_MUTATION = `
  mutation RevokePermission($userId: ID!, $permissionKey: PermissionKey!) {
    revokePermission(userId: $userId, permissionKey: $permissionKey)
  }
`;

const DELETE_USER_MUTATION = `
  mutation DeleteUser($userId: ID!) {
    deleteUser(userId: $userId)
  }
`;

// ─── Composables ─────────────────────────────────────────────────────────────

/**
 * Reactive user search — admin only.
 * Pass a reactive/computed search string to auto-refresh on change.
 *
 * @example
 * const search = ref('')
 * const { users } = await useUsers(computed(() => search.value))
 */
export async function useUsers(search?: MaybeRef<string | undefined>) {
  const vars = computed(() => ({ search: toValue(search) || undefined }));
  const { data, pending, error, refresh } = await useGql<{ users: UserEntity[] }>(
    USERS_QUERY,
    vars,
  );

  async function assignRole(userId: string, roleName: string): Promise<UserEntity> {
    const result = await useGqlMutation<{ assignRole: UserEntity }>(ASSIGN_ROLE_MUTATION, {
      userId,
      roleName,
    });
    return result.assignRole;
  }

  async function removeRole(userId: string, roleName: string): Promise<UserEntity> {
    const result = await useGqlMutation<{ removeRole: UserEntity }>(REMOVE_ROLE_MUTATION, {
      userId,
      roleName,
    });
    return result.removeRole;
  }

  return {
    users: computed(() => data.value?.users ?? []),
    pending,
    error,
    refresh,
    assignRole,
    removeRole,
  };
}

/**
 * Fetch a user's permissions by userId — imperative, admin only.
 */
export async function fetchUserPermissions(userId: string): Promise<string[]> {
  const result = await useGqlMutation<{
    userPermissions: Array<{ id: string; permissionKey: string }>;
  }>(USER_PERMISSIONS_QUERY, { userId });
  return (result.userPermissions ?? []).map(p => p.permissionKey);
}

/**
 * Grant a permission to a user — imperative mutation.
 */
export async function grantPermission(userId: string, permissionKey: string): Promise<void> {
  await useGqlMutation(GRANT_PERMISSION_MUTATION, { userId, permissionKey });
}

/**
 * Revoke a permission from a user — imperative mutation.
 */
export async function revokePermission(userId: string, permissionKey: string): Promise<void> {
  await useGqlMutation(REVOKE_PERMISSION_MUTATION, { userId, permissionKey });
}

/**
 * Imperative user search — for form autocompletes and similar UI.
 * Returns a filtered list without setting up reactive SSR state.
 *
 * @example
 * const results = await fetchUsers('zez')
 */
export async function fetchUsers(search: string): Promise<UserEntity[]> {
  if (!search || search.length < 2) return [];
  const result = await useGqlMutation<{ users: UserEntity[] }>(USERS_QUERY, {
    search: search || undefined,
  });
  return result.users ?? [];
}

/**
 * Fetch the N most recently joined users — for pre-loading suggestions in member modals.
 * Admin or TEAM_MANAGER only.
 */
export async function fetchRecentUsers(limit: number): Promise<UserEntity[]> {
  const result = await useGqlMutation<{ users: UserEntity[] }>(USERS_QUERY, { limit });
  return result.users ?? [];
}

/**
 * Delete a user account — admin only.
 * Will throw if the target user has the ADMIN role.
 */
export async function deleteUser(userId: string): Promise<void> {
  await useGqlMutation(DELETE_USER_MUTATION, { userId });
}

/**
 * Current logged-in user's profile.
 * Typically auth is managed via the auth store; use this when you need a
 * fresh server-side fetch of the current user (e.g. profile page).
 */
export async function useMe() {
  const { data, pending, error, refresh } = await useGql<{ me: UserEntity | null }>(
    ME_QUERY,
    undefined,
    { server: false },
  );

  async function updateProfile(nickname: string | null): Promise<UserEntity> {
    const result = await useGqlMutation<{ updateProfile: UserEntity }>(UPDATE_PROFILE_MUTATION, {
      nickname,
    });
    return result.updateProfile;
  }

  return {
    me: computed(() => data.value?.me ?? null),
    pending,
    error,
    refresh,
    updateProfile,
  };
}
