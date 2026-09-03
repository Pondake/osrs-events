import { onMounted, ref, toValue } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { xsrfHeader } from '@/Support/csrf';

/**
 * An event's invite links: the list, and the two things a host does to it.
 *
 * Lifted out of BoardSettingsModal on 2026-09-03, when the event page grew
 * its own way in (HostInviteCard). Two copies of this would be two ways for
 * the same list to go stale, and the endpoints' one-shape-per-response
 * contract is the whole reason the list can never drift — see
 * BoardInviteController::list().
 *
 * `eventPath` is read through toValue on every call, so a computed may be
 * handed in: the settings modal's path changes when a different event is
 * opened for editing.
 *
 * Fetched with plain fetch() rather than Inertia's router — an Inertia visit
 * re-renders the whole underlying page, and neither caller is a page
 * component with a natural way to refresh just this list.
 */
export function useInvites(eventPath) {
    const invites = ref([]);
    const openCount = ref(0);
    const maxOpen = ref(null);
    const creating = ref(false);

    // useToast statically imports the virtual '#imports' specifier, and
    // pulling that into the SSR module graph crashes the SSR process at
    // startup for every page — see AppRoot.vue. Optional-called below, since
    // a toast raised before hydration finishes is not worth a crash.
    let toast;
    onMounted(async () => {
        const { useToast } = await import('@nuxt/ui/composables/useToast');
        toast = useToast();
    });

    const path = () => toValue(eventPath);

    /**
     * All three endpoints answer with the same shape — the full list plus the
     * open count — so creating and revoking need no separate refetch and the
     * list can never drift from what the server just did.
     */
    function apply(data) {
        invites.value = Array.isArray(data?.invites) ? data.invites : [];
        openCount.value = data?.openCount ?? invites.value.length;
        maxOpen.value = data?.maxOpen ?? null;
    }

    /**
     * What actually went wrong, said out loud.
     *
     * `data.message` is empty on an `abort_unless`, and absent entirely when
     * the server answered with HTML — a 419 page, a redirect to the lock
     * screen, a 500. All three used to arrive as the same shrug.
     */
    function errorFor(response, data) {
        if (data?.message) return data.message;

        if (response.status === 419) return trans('errors.session_expired');
        if (response.status === 403) return trans('errors.forbidden');

        return trans('errors.generic_with_status', { status: response.status });
    }

    async function request(url, { headers = {}, ...rest } = {}) {
        let response;

        try {
            // `headers` is pulled out of the options rather than spread over
            // afterwards. Written the other way round — `{ headers: merged,
            // ...options }` — a caller that passes any header of its own
            // replaces the whole merged object, and the CSRF token goes with
            // it. Every POST and DELETE here does pass one (Content-Type), so
            // every write went out unsigned; Laravel then answers 419 and the
            // page says the session expired, which is a lie about a request
            // that was simply missing a header. Chrome hid it: Laravel 13's
            // PreventRequestForgery lets a request through on
            // `Sec-Fetch-Site: same-origin` alone, so the token is only
            // actually needed where that header does not arrive.
            response = await fetch(url, {
                ...rest,
                headers: { Accept: 'application/json', ...xsrfHeader(), ...headers },
            });
        } catch (error) {
            // The network never answered — a dropped connection or a server
            // that is not there. Worth saying, because it is the one case
            // where trying again really is the advice.
            console.error('invite request could not be sent', url, error);
            toast?.add({ id: 'invite-error', title: trans('errors.network'), color: 'error' });

            return null;
        }

        const body = await response.text();
        let data = null;

        try {
            data = JSON.parse(body);
        } catch {
            data = null;
        }

        if (!response.ok) {
            // Everything the next person needs to work out what happened.
            // This used to be one generic "something went wrong" for every
            // failure — reported as "invite links do not work", with nothing
            // to go on and no way to tell a stale session from a permission
            // problem.
            console.error('invite request failed', { url, status: response.status, body: body.slice(0, 500) });

            toast?.add({ id: 'invite-error', title: errorFor(response, data), color: 'error' });

            return null;
        }

        apply(data);

        return data;
    }

    // Whether a list fetch is already in the air. The modal is mounted lazily
    // and asks for the list as soon as it is open, and that arrangement asked
    // twice — harmless but pointless, and a second answer overwriting the
    // first is a race nobody needs. Only the list is guarded: a create or a
    // revoke must always be sent.
    let fetching = false;

    async function fetchInvites() {
        if (fetching) return;

        fetching = true;

        try {
            await request(`${path()}/invites`);
        } finally {
            fetching = false;
        }
    }

    async function createInvite() {
        creating.value = true;

        try {
            const data = await request(`${path()}/invites`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({}),
            });

            if (data) toast?.add({ id: 'invite-created', title: trans('admin.invite_created'), color: 'success' });
        } finally {
            // In a finally so a failure cannot leave the button spinning
            // forever, which is what a spam-click session turns into
            // otherwise.
            creating.value = false;
        }
    }

    async function revokeInvite(invite) {
        const data = await request(`${path()}/invites/${invite.id}`, { method: 'DELETE' });

        if (data) toast?.add({ id: 'invite-revoked', title: trans('admin.invite_revoked'), color: 'success' });
    }

    return { invites, openCount, maxOpen, creating, fetchInvites, createInvite, revokeInvite };
}
