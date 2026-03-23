import { useAuthStore } from '~/stores/auth'

// ─── GraphQL ─────────────────────────────────────────────────────────────────

const MY_PERMISSIONS_QUERY = `
  query MyPermissions {
    myPermissions { id userId permissionKey createdAt }
  }
`

const GRANT_PERMISSION_MUTATION = `
  mutation GrantPermission($userId: ID!, $permissionKey: PermissionKey!) {
    grantPermission(userId: $userId, permissionKey: $permissionKey) {
      id userId permissionKey createdAt
    }
  }
`

const REVOKE_PERMISSION_MUTATION = `
  mutation RevokePermission($userId: ID!, $permissionKey: PermissionKey!) {
    revokePermission(userId: $userId, permissionKey: $permissionKey)
  }
`

// ─── Types ───────────────────────────────────────────────────────────────────

interface BoardAuthorRef {
  user: { id: string }
}

interface TeamMemberRef {
  user: { id: string }
}

// ─── Composable ──────────────────────────────────────────────────────────────

/**
 * Central composable for all role + permission checks.
 *
 * Replaces scattered `isAdmin` / `isEditor` checks across components.
 * Call `loadPermissions()` in `onMounted` to hydrate granular permissions
 * from the backend.
 *
 * @example
 * const permissions = usePermissions()
 * onMounted(permissions.loadPermissions)
 *
 * // In template:
 * v-if="permissions.canCreateBoards.value"
 * v-if="permissions.canEditBoard(board.authors)"
 */
export function usePermissions() {
  const authStore = useAuthStore()

  // Granular permission keys loaded from the backend
  const grantedKeys = ref<string[]>([])
  const permissionsLoaded = ref(false)

  // ─── Role helpers (synchronous, from auth store) ───────────────────────

  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const isAdmin = computed(() => authStore.isAdmin)
  const isEditor = computed(() => authStore.user?.roles?.includes('EDITOR') ?? false)
  const isTeamManager = computed(() => authStore.user?.roles?.includes('TEAM_MANAGER') ?? false)

  // ─── Granular permissions ──────────────────────────────────────────────

  /** Can create new boards (admin always can; others need explicit grant) */
  const canCreateBoards = computed(
    () => isAdmin.value || grantedKeys.value.includes('canCreateBoards'),
  )

  /** Can create/edit tiles (admin always can; others need explicit grant + board access) */
  const canCreateTiles = computed(
    () => isAdmin.value || grantedKeys.value.includes('canCreateTiles'),
  )

  // ─── Context-aware helpers ─────────────────────────────────────────────

  /**
   * Can the current user edit a specific board?
   * True for admins and any user listed in the board's authors array.
   */
  function canEditBoard(authors: BoardAuthorRef[]): boolean {
    if (!authStore.user) return false
    if (isAdmin.value) return true
    return authors.some(a => a.user.id === authStore.user?.id)
  }

  /**
   * Can the current user manage a specific team's membership?
   * True for admins; also for TEAM_MANAGERs who are members of the team.
   */
  function canManageTeam(members: TeamMemberRef[]): boolean {
    if (!authStore.user) return false
    if (isAdmin.value) return true
    return isTeamManager.value && members.some(m => m.user.id === authStore.user?.id)
  }

  // ─── Load & mutation ───────────────────────────────────────────────────

  /** Fetch the current user's granted permissions from the backend. Call in onMounted. */
  async function loadPermissions() {
    if (!authStore.isAuthenticated) return
    try {
      const result = await useGqlMutation<{
        myPermissions: Array<{ permissionKey: string }>
      }>(MY_PERMISSIONS_QUERY)
      grantedKeys.value = (result.myPermissions ?? []).map(p => p.permissionKey)
    } catch {
      // Not authenticated or request failed — leave grantedKeys empty
    } finally {
      permissionsLoaded.value = true
    }
  }

  /** Grant a permission to a user (admin only, for the admin UI) */
  async function grantPermission(userId: string, permissionKey: string) {
    await useGqlMutation(GRANT_PERMISSION_MUTATION, { userId, permissionKey })
  }

  /** Revoke a permission from a user (admin only, for the admin UI) */
  async function revokePermission(userId: string, permissionKey: string) {
    await useGqlMutation(REVOKE_PERMISSION_MUTATION, { userId, permissionKey })
  }

  return {
    // Role checks
    isAuthenticated,
    isAdmin,
    isEditor,
    isTeamManager,
    // Granular permissions
    canCreateBoards,
    canCreateTiles,
    // Context-aware helpers
    canEditBoard,
    canManageTeam,
    // State
    grantedKeys,
    permissionsLoaded,
    // Actions
    loadPermissions,
    grantPermission,
    revokePermission,
  }
}
