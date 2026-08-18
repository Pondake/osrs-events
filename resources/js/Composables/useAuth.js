import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Replaces the old stores/auth.ts + composables/usePermissions.ts. The
 * `auth.user` prop is shared on every request by HandleInertiaRequests, so
 * there's no client-side fetch-after-mount step like the old GraphQL
 * composables had — isAdmin/canCreateBoards/canCreateTiles are already
 * resolved server-side.
 */
export function useAuth() {
    const page = usePage();

    // Optional-chained on `props` itself, not just `auth` — a bare
    // page.props.auth.user threw "Cannot read properties of undefined
    // (reading 'auth')" from exactly this computed during an SSR sweep,
    // right after AppHeader started mounting on every page. Root cause
    // wasn't fully pinned down (didn't reproduce on retrying the same
    // route), but HandleInertiaRequests::share() always includes `auth`
    // unconditionally, so this is cheap, harmless defensive chaining either
    // way — matches the same pattern already applied to AppRoot.vue's flash
    // watchers for a confirmed-understood version of the same class of bug.
    const user = computed(() => page.props?.auth?.user ?? null);
    const isAuthenticated = computed(() => user.value !== null);
    const isAdmin = computed(() => user.value?.isAdmin ?? false);
    const canCreateBoards = computed(() => user.value?.canCreateBoards ?? false);
    const canCreateTiles = computed(() => user.value?.canCreateTiles ?? false);
    const isEditor = computed(() => user.value?.roles?.includes('EDITOR') ?? false);
    const isTeamManager = computed(() => user.value?.roles?.includes('TEAM_MANAGER') ?? false);

    return { user, isAuthenticated, isAdmin, canCreateBoards, canCreateTiles, isEditor, isTeamManager };
}
