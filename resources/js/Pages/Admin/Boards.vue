<template>
    <Head :title="$t('admin.boards_title')" />

    <admin-layout current="boards" :title="$t('admin.boards_title')" :description="$t('admin.boards_subtitle')">

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <u-input
                v-model="search"
                icon="i-lucide-search"
                :placeholder="$t('admin.events_search_placeholder')"
                class="flex-1"
            />
            <u-select
                v-model="status"
                :items="statusOptions"
                :placeholder="$t('admin.events_status_all')"
                class="w-full sm:w-48"
            />
            <u-button
                v-if="hasFilters"
                color="neutral"
                variant="ghost"
                icon="i-lucide-x"
                :label="$t('admin.events_clear')"
                class="shrink-0"
                @click="clearFilters"
            />
        </div>

        <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div
                v-for="board in boards"
                :key="board.id"
                class="flex items-center justify-between gap-4 px-4 py-3"
                :class="board.deleted_at ? 'opacity-60' : ''"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium truncate" :class="board.deleted_at ? 'line-through' : ''">{{ board.title }}</span>
                        <!-- This list is the only place a deleted event is
                             visible at all — everywhere else the soft delete
                             takes it out of the query. Which makes this the
                             only place it can be put back. -->
                        <u-badge v-if="board.deleted_at" :label="$t('admin.event_deleted_badge')" size="xs" color="error" variant="subtle" />
                        <u-badge v-if="board.paused_at" :label="$t('boards.status_paused')" size="xs" color="warning" variant="subtle" />
                        <u-badge v-if="!board.is_listed" :label="$t('boards.unlisted')" size="xs" color="neutral" variant="subtle" />
                        <u-badge :label="board.access_mode" size="xs" color="primary" variant="subtle" />
                    </div>
                    <div class="text-xs text-muted truncate">
                        {{ board.authors.map((a) => a.user.nickname ?? a.user.discord_username).join(', ') || $t('admin.no_authors') }}
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <u-button
                        v-if="board.deleted_at"
                        icon="i-lucide-undo-2"
                        size="xs"
                        color="primary"
                        variant="ghost"
                        :label="$t('admin.event_restore')"
                        @click="restoreBoard(board)"
                    />
                    <template v-else>
                        <u-button :href="`/events/${board.id}`" icon="i-lucide-eye" size="xs" color="neutral" variant="ghost" :aria-label="$t('board.view_mode')" />
                        <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" :aria-label="$t('common.edit')" @click="editingBoard = board" />
                        <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" :aria-label="$t('common.delete')" @click="destroyBoard(board)" />
                    </template>
                </div>
            </div>
            <p v-if="!boards.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_boards') }}</p>
        </div>

        <client-only>
            <board-settings-modal
                :open="editingBoard !== null"
                :board="editingBoard"
                :webhook-url="editingBoard?.discord_webhook_url ?? null"
                base-path="/admin/events"
                @update:open="(v) => !v && (editingBoard = null)"
            />
        </client-only>
    </admin-layout>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import AdminLayout from '@/Components/AdminLayout.vue';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

const props = defineProps({
    boards: { type: Array, required: true },
    filters: { type: Object, required: true },
});

// Kept across a full navigation away and back, not just within this page's
// own query string — asked for explicitly: coming back from an event's own
// admin edit screen (or anywhere else in /admin) should not lose whatever was
// typed here. sessionStorage rather than localStorage: this is "where I left
// this browser tab", not a preference that should follow every device.
const STORAGE_KEY = 'admin-events-filters';

function readStoredFilters() {
    // Guarded the same way wantedEventId below already is: this page's
    // <script setup> runs during SSR too (the admin shell around it is
    // client-only, this page's own setup is not), and window does not exist
    // in that Node process.
    if (typeof window === 'undefined') return null;

    try {
        const raw = window.sessionStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch (error) {
        // Private mode and blocked storage both throw on access rather than
        // returning null — same fallback direction as the onboarding
        // snooze's own try/catch in AppRoot.vue: fail toward "nothing saved".
        console.error(error);

        return null;
    }
}

// The URL wins when it actually carries a filter (a bookmark, a shared link,
// a browser back/forward) — only an entirely bare visit falls back to
// whatever was last saved in this tab.
const urlHadFilters = Boolean(props.filters.search || (props.filters.status && props.filters.status !== 'all'));
const stored = urlHadFilters ? null : readStoredFilters();

const search = ref(stored?.search ?? props.filters.search ?? '');
// null for "All statuses" — matches u-select's own placeholder-vs-value
// convention, same as Audit.vue's action/user/scope menus.
const status = ref((stored?.status ?? props.filters.status) && (stored?.status ?? props.filters.status) !== 'all'
    ? (stored?.status ?? props.filters.status)
    : null);

const statusOptions = computed(() => ([
    { label: trans('admin.events_status_active'), value: 'active' },
    { label: trans('admin.events_status_paused'), value: 'paused' },
    { label: trans('admin.events_status_deleted'), value: 'deleted' },
]));

const hasFilters = computed(() => Boolean(search.value || status.value));

function persist() {
    if (typeof window === 'undefined') return;

    try {
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ search: search.value, status: status.value }));
    } catch (error) {
        console.error(error);
    }
}

// If this page loaded from a stored-but-not-in-the-URL state (a bare
// /admin/events visit with something saved from before), sync the URL to
// match immediately — otherwise the address bar disagrees with what's
// actually showing, and a refresh would silently drop back to "all".
if (stored && (stored.search || (stored.status && stored.status !== 'all'))) {
    router.get('/admin/events', {
        search: stored.search || undefined,
        status: stored.status && stored.status !== 'all' ? stored.status : undefined,
    }, { preserveState: true, replace: true });
}

let timer;
watch([search, status], () => {
    persist();
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            '/admin/events',
            { search: search.value || undefined, status: status.value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function clearFilters() {
    search.value = '';
    status.value = null;
}

/**
 * `?event=<id>` opens that event's settings on arrival.
 *
 * It is how an admin gets here from the event's own page (see
 * BoardController::adminEditUrl) — landing on a list of two dozen events and
 * being asked to find the one you just came from is not an answer to "where
 * did the buttons go".
 *
 * Read while setting up rather than in onMounted, so the dialog is open in
 * the component's first frame: set afterwards, the modal mounts closed and
 * whether it reopens depends on the order its own open-state emit and this
 * assignment happen to land in — which is a race that resolved the wrong way
 * here. The `typeof window` guard is for SSR, where the modal is not rendered
 * at all (it sits inside <client-only>).
 */
const wantedEventId = typeof window === 'undefined'
    ? null
    : new URLSearchParams(window.location.search).get('event');

const editingBoard = ref(wantedEventId
    ? props.boards.find((board) => board.id === wantedEventId) ?? null
    : null);

function destroyBoard(board) {
    // The admin route, not the public one — an admin deleting somebody
    // else's event is exactly the power that no longer exists out there.
    //
    // notify: false. This list has no room to ask, and a delete from the
    // admin screen is usually housekeeping — a test event, a duplicate,
    // something abandoned. Announcing those to whoever once joined them
    // would be worse than saying nothing. The host's own danger zone is
    // where the question gets asked, because that is where the answer is
    // usually yes.
    router.delete(`/admin/events/${board.id}`, { data: { notify: false }, preserveScroll: true });
}

function restoreBoard(board) {
    router.post(`/admin/events/${board.id}/restore`, {}, { preserveScroll: true });
}
</script>
