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

    const user = computed(() => page.props.auth.user);
    const isAuthenticated = computed(() => user.value !== null);
    const isAdmin = computed(() => user.value?.isAdmin ?? false);
    const canCreateBoards = computed(() => user.value?.canCreateBoards ?? false);
    const canCreateTiles = computed(() => user.value?.canCreateTiles ?? false);

    return { user, isAuthenticated, isAdmin, canCreateBoards, canCreateTiles };
}
