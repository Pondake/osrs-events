<template>
    <Head :title="$t('settings.profile_title')" />

    <settings-layout current="profile">
        <u-card>
            <div class="flex items-center gap-6">
                <u-avatar :src="user.avatarUrl ?? undefined" :alt="user.nickname ?? user.discordUsername" size="3xl" />

                <div class="flex-1 min-w-0">
                    <div v-if="!editing" class="flex items-center gap-2">
                        <h2 class="text-2xl font-bold text-highlighted">{{ user.nickname ?? user.discordUsername }}</h2>
                        <u-button size="xs" color="neutral" variant="ghost" icon="i-lucide-pencil" @click="startEditing" />
                    </div>
                    <div v-else class="flex items-center gap-2 flex-wrap">
                        <u-input v-model="nicknameInput" :placeholder="user.discordUsername ?? ''" size="sm" class="flex-1" />
                        <u-button size="sm" color="primary" icon="i-lucide-check" :loading="form.processing" @click="save" />
                        <u-button size="sm" color="neutral" variant="ghost" icon="i-lucide-x" @click="editing = false" />
                    </div>

                    <p v-if="user.nickname && user.discordUsername" class="text-xs text-muted mt-0.5">{{ $t('profile.username') }}: {{ user.discordUsername }}</p>

                    <div class="flex flex-wrap gap-2 mt-3">
                        <u-badge v-for="role in roles" :key="role" :color="roleColor(role)" variant="subtle" :icon="roleIcon(role)" :label="role" />
                    </div>
                </div>
            </div>
        </u-card>

        <!-- A separate identity from the Discord one above: skill races are
             scored off the OSRS hiscores, which are keyed by account name. -->
        <u-card>
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <p class="font-medium">{{ $t('profile.osrs_account') }}</p>
                    <p class="text-sm text-muted max-w-md">{{ $t('profile.osrs_account_help') }}</p>
                </div>

                <div class="flex items-center gap-2 flex-wrap shrink-0">
                    <u-input
                        v-model="osrsInput"
                        :placeholder="$t('profile.osrs_username')"
                        size="sm"
                        maxlength="12"
                        icon="i-lucide-user-round"
                    />
                    <u-button
                        size="sm"
                        color="primary"
                        icon="i-lucide-check"
                        :label="$t('common.save')"
                        :loading="osrsForm.processing"
                        @click="saveOsrsUsername"
                    />
                </div>
            </div>

            <p v-if="osrsForm.errors.osrs_username" class="text-sm text-error mt-2">{{ osrsForm.errors.osrs_username }}</p>
        </u-card>

        <u-card>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="font-medium">{{ $t('onboarding.title') }}</p>
                    <p class="text-sm text-muted">{{ $t('onboarding.welcome_body') }}</p>
                </div>
                <u-button color="neutral" variant="outline" size="sm" icon="i-lucide-rotate-ccw" :label="$t('onboarding.restart')" @click="replayOnboarding" />
            </div>
        </u-card>

        <div>
            <div class="flex items-end justify-between gap-4 flex-wrap mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">{{ $t('profile.your_events') }}</h3>
                    <p class="text-sm text-muted">{{ $t('profile.your_events_desc') }}</p>
                </div>

                <!-- Two different relationships were being shown as one list,
                     separated only by a badge: an event you set up and an
                     event you are playing in are not the same thing to look
                     at. A client-side toggle rather than links, because
                     unlike /my-events this is a section of a settings page,
                     not a destination anything links into. -->
                <div v-if="events.length" class="flex flex-wrap items-center gap-2">
                    <u-button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        size="xs"
                        :color="filter === tab.key ? 'primary' : 'neutral'"
                        :variant="filter === tab.key ? 'solid' : 'outline'"
                        :label="`${tab.label} (${tab.count})`"
                        @click="filter = tab.key"
                    />
                </div>
            </div>

            <div v-if="!events.length" class="text-center py-8 text-muted">
                <u-icon name="i-lucide-layout-grid" class="size-10 mx-auto mb-3 block" />
                <p>{{ $t('profile.no_events') }}</p>
            </div>

            <!-- Rows, not cards. Each one is a line you scan down a column
                 of — the detail that belongs to an event lives on the
                 event's own page, and this list exists to get you there. -->
            <div v-else class="divide-y divide-default rounded-lg ring ring-default bg-default">
                <a
                    v-for="event in visibleEvents"
                    :key="event.id"
                    :href="`/events/${event.id}`"
                    class="flex items-center gap-3 px-4 py-3 hover:bg-elevated transition-colors"
                >
                    <u-icon v-if="typeMeta(event)" :name="typeMeta(event).icon" class="size-4 text-primary shrink-0" />

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- The event id, not the board id. These are
                                 the same only for rows migrated at the
                                 split; anything created since has its own
                                 board uuid, and this link 404'd. -->
                            <span class="font-medium truncate">{{ event.title }}</span>
                            <u-badge v-if="event.isOwner" color="warning" variant="subtle" size="sm" :label="$t('profile.owner_badge')" />
                            <u-badge v-else-if="event.isHost" color="info" variant="subtle" size="sm" :label="$t('profile.editor_badge')" />
                        </div>

                        <div class="flex items-center gap-x-3 gap-y-0.5 flex-wrap text-xs text-muted mt-0.5">
                            <span v-if="typeMeta(event)">{{ typeMeta(event).label }}</span>
                            <span v-if="event.size">{{ formatBoardSize(event.size) }}</span>
                            <span v-if="event.progress">{{ $t('board.tile', { n: event.progress.position }) }}</span>
                            <span v-if="event.progress">{{ event.progress.completed }} {{ $t('profile.tiles_completed') }}</span>
                        </div>
                    </div>

                    <!-- Only a board has a position to be a percentage of.
                         A race is ranked, not traversed. -->
                    <div v-if="event.progress" class="hidden sm:block w-24 shrink-0">
                        <div class="flex justify-between text-xs text-muted mb-1">
                            <span>{{ $t('profile.progress') }}</span>
                            <span class="tabular-nums">{{ event.progress.pct }}%</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all" :style="{ width: `${event.progress.pct}%` }" />
                        </div>
                    </div>

                    <u-icon name="i-lucide-chevron-right" class="size-4 text-muted shrink-0" />
                </a>

                <p v-if="!visibleEvents.length" class="px-4 py-8 text-center text-sm text-muted">
                    {{ filter === 'created' ? $t('profile.no_created_events') : $t('profile.no_joined_events') }}
                </p>
            </div>
        </div>
    </settings-layout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import { formatBoardSize } from '@/Support/board';
import { eventTypeMeta } from '@/Support/eventTypes';
import SettingsLayout from '@/Components/SettingsLayout.vue';

const props = defineProps({
    roles: { type: Array, required: true },
    events: { type: Array, required: true },
    osrsUsername: { type: String, default: null },
});

const { user } = useAuth();

const typeMeta = (event) => eventTypeMeta(event.type);

// "Created" is being an author, not strictly the owner — an event you were
// added to as an editor is one you run rather than one you joined, and
// /my-events already draws the line in that same place. The Owner/Editor
// badge still tells those two apart inside the list.
const filter = ref('all');

const visibleEvents = computed(() => props.events.filter((event) => (
    filter.value === 'all'
        || (filter.value === 'created' ? event.isHost : !event.isHost)
)));

const filterTabs = computed(() => {
    const created = props.events.filter((e) => e.isHost).length;

    return [
        { key: 'all', label: trans('profile.filter_all'), count: props.events.length },
        { key: 'created', label: trans('profile.filter_created'), count: created },
        { key: 'joined', label: trans('profile.filter_joined'), count: props.events.length - created },
    ];
});

const editing = ref(false);
const nicknameInput = ref('');
const form = useForm({ nickname: '' });

function startEditing() {
    nicknameInput.value = user.value.nickname ?? '';
    editing.value = true;
}

function save() {
    form.nickname = nicknameInput.value.trim() || null;
    form.patch('/settings/profile', { onSuccess: () => (editing.value = false), preserveScroll: true });
}

// Its own form and its own endpoint, not folded into save() above: one
// validated action writing two fields blanks whichever one the submitted form
// didn't carry.
const osrsInput = ref(props.osrsUsername ?? '');
const osrsForm = useForm({ osrs_username: '' });

// Resynced after a save because the server may normalise what was typed —
// Wise Old Man returns the account's canonical casing, so "pondake" is stored
// as "Pondake". Seeded once, the field would keep showing the typed version
// and quietly disagree with what is actually saved.
watch(() => props.osrsUsername, (name) => (osrsInput.value = name ?? ''));

function saveOsrsUsername() {
    osrsForm.osrs_username = osrsInput.value.trim();
    osrsForm.put('/settings/profile/osrs', { preserveScroll: true });
}

// Clears onboarding_completed_at; AppRoot watches the shared prop and
// re-opens the modal once the response lands.
function replayOnboarding() {
    router.post('/onboarding/reset', {}, { preserveScroll: true });
}

const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', PLAYER: 'primary' };
const ROLE_ICONS = { ADMIN: 'i-lucide-shield-check', EDITOR: 'i-lucide-pencil', PLAYER: 'i-lucide-user' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';
const roleIcon = (name) => ROLE_ICONS[name] ?? 'i-lucide-user';
</script>
