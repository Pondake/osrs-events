import { computed } from 'vue';

import { useCurrentPage } from '@/Support/pageState';

/**
 * Whether the pre-launch door is shut for the person looking at this page.
 *
 * The public pages stay readable while the site is locked — that is the whole
 * point of letting them through — but everything they normally invite you to
 * do is behind the door. A "Create a board" button that lands on a password
 * box, or a "Log in with Discord" that refuses to make an account, is worse
 * than no button: it reads as broken rather than as not-yet.
 *
 * The prop already means "shut for THIS visitor": false for an admin, and
 * false for anyone who has typed the shared password. Re-checking isAdmin
 * here would get the second of those wrong.
 */
export function useSiteLock() {
    const page = useCurrentPage();

    const locked = computed(() => Boolean(page.value.props?.site?.locked));

    return { locked };
}
