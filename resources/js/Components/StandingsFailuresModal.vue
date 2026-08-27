<template>
    <u-modal v-model:open="isOpen" :title="$t('diagnostics.standings_modal_title')">
        <template #body>
            <p class="text-sm text-muted mb-4">{{ $t('diagnostics.standings_modal_desc') }}</p>

            <div v-if="loading" class="text-center py-8 text-muted text-sm">
                {{ $t('common.loading') }}
            </div>

            <p v-else-if="!users.length" class="text-sm text-muted text-center py-8">
                {{ $t('diagnostics.standings_modal_empty') }}
            </p>

            <div v-else class="divide-y divide-default -my-2">
                <div v-for="user in users" :key="user.id ?? user.name" class="py-3 space-y-2">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-highlighted">{{ user.name }}</p>
                            <p v-if="user.osrsUsername" class="text-xs text-muted">{{ user.osrsUsername }}</p>
                        </div>

                        <div class="flex flex-wrap gap-1.5">
                            <u-badge
                                v-for="(event, i) in user.events"
                                :key="i"
                                size="xs"
                                color="neutral"
                                variant="subtle"
                                :label="`${event.title} — ${errorLabel(event.error)}`"
                            />
                        </div>
                    </div>

                    <template v-if="user.id">
                        <p class="text-xs text-muted">
                            {{ user.nudgeCount > 0
                                ? $t('diagnostics.osrs_nudged_before', { count: user.nudgeCount, when: user.lastNudge })
                                : $t('diagnostics.osrs_never_nudged') }}
                        </p>

                        <div class="flex flex-wrap gap-2">
                            <confirm-popover
                                :message="$t('diagnostics.osrs_nudge_confirm', { name: user.name })"
                                :confirm-label="$t('diagnostics.osrs_send_reminder')"
                                color="primary"
                                :loading="acting === `nudge:${user.id}`"
                                @confirm="(note, done) => nudge(user, done)"
                            >
                                <u-button size="xs" color="primary" variant="outline" icon="i-lucide-bell" :label="$t('diagnostics.osrs_send_reminder')" />
                            </confirm-popover>

                            <!-- Offered regardless of nudge count — a repeat
                                 failure is more likely a typo baked in at
                                 signup than something a reminder fixes, but
                                 that's a judgement call for whoever is
                                 looking at this, not a rule enforced here. -->
                            <confirm-popover
                                :message="$t('diagnostics.osrs_reset_confirm', { name: user.name })"
                                :confirm-label="$t('diagnostics.osrs_reset_username')"
                                color="error"
                                :loading="acting === `reset:${user.id}`"
                                @confirm="(note, done) => resetUsername(user, done)"
                            >
                                <u-button size="xs" color="error" variant="outline" icon="i-lucide-user-round-x" :label="$t('diagnostics.osrs_reset_username')" />
                            </confirm-popover>
                        </div>
                    </template>

                    <p v-else class="text-xs text-muted italic">{{ $t('diagnostics.standings_no_account') }}</p>
                </div>
            </div>
        </template>

        <template #footer>
            <u-button color="neutral" variant="outline" :label="$t('common.close')" class="ml-auto" @click="isOpen = false" />
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ConfirmPopover from '@/Components/ConfirmPopover.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const loading = ref(false);
const users = ref([]);
const acting = ref(null);

function errorLabel(error) {
    return trans(`events.error_${error}`);
}

async function load() {
    loading.value = true;

    try {
        const response = await fetch('/admin/diagnostics/standings', { headers: { Accept: 'application/json' } });

        // A 403 (permission lost mid-session) or a throttle answers with a
        // body that is not the shape expected — same reasoning as
        // TeamMembersModal's own search: take an empty list over a crash.
        const data = response.ok ? await response.json() : { users: [] };
        users.value = Array.isArray(data.users) ? data.users : [];
    } catch (error) {
        console.error(error);
        users.value = [];
    } finally {
        loading.value = false;
    }
}

// Reload every time the modal opens rather than once — the underlying data
// changes independently of this modal (the scheduled sync runs on its own),
// so a stale list from an earlier open would show the wrong nudge counts.
watch(() => props.open, (open) => {
    if (open) load();
});

// Inertia's router, not a raw fetch — the endpoints return an ordinary
// `back()` redirect carrying a flash toast, same as every other mutation in
// the app, not a JSON body. `preserveState`/`preserveScroll` keep the
// diagnostics page itself (and this modal's own open state) untouched; the
// modal's own list is a separate fetch, refreshed explicitly afterwards
// since Inertia only reloads the page's own shared props, not this one.
function nudge(user, done) {
    acting.value = `nudge:${user.id}`;

    router.post(`/admin/diagnostics/standings/${user.id}/nudge`, {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: load,
        onFinish: () => {
            acting.value = null;
            done?.();
        },
    });
}

function resetUsername(user, done) {
    acting.value = `reset:${user.id}`;

    router.delete(`/admin/diagnostics/standings/${user.id}/username`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: load,
        onFinish: () => {
            acting.value = null;
            done?.();
        },
    });
}
</script>
