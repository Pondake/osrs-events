<template>
    <Head :title="$t('settings.notifications_title')" />

    <settings-layout current="notifications">
        <!-- This device.

             First, and its own card, because it is the half people get wrong.
             Turning a category off is meant to apply everywhere; turning THIS
             BROWSER off is not. Two separate questions, so two separate
             boxes. -->
        <u-card>
            <template #header>
                <div class="flex items-center gap-2">
                    <u-icon name="i-lucide-bell" class="size-4 shrink-0" />
                    <span class="font-semibold">{{ $t('notifications.device_heading') }}</span>
                </div>
            </template>

            <!-- ClientOnly for the whole block: every word of it depends on
                 browser state (Notification.permission, an existing
                 subscription, whether this is an installed PWA) that does not
                 exist during SSR. Server-rendering a guess here would mean
                 the page states a reason, then silently changes it on
                 hydration — on the one screen whose entire job is telling
                 people the truth about why something is off. -->
            <client-only>
                <template #fallback>
                    <p class="text-sm text-muted">{{ $t('notifications.state_checking') }}</p>
                </template>

                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-highlighted">{{ stateTitle }}</p>
                            <p class="text-sm text-muted mt-0.5">{{ stateDescription }}</p>
                        </div>

                        <u-button
                            v-if="canToggle"
                            :color="isOn ? 'neutral' : 'primary'"
                            :variant="isOn ? 'outline' : 'solid'"
                            size="sm"
                            :loading="push.busy.value"
                            :label="isOn ? $t('notifications.turn_off') : $t('notifications.turn_on')"
                            @click="toggleDevice"
                        />
                    </div>

                    <!-- The blocked case gets its own line rather than a
                         disabled button, because the fix is not in this app
                         at all: a browser-level denial can only be undone
                         from the browser's own site settings, and a button
                         that cannot help is worse than a sentence that
                         explains. -->
                    <u-alert
                        v-if="push.reason.value === push.REASON.BLOCKED"
                        color="warning"
                        variant="soft"
                        icon="i-lucide-shield-off"
                        :title="$t('notifications.blocked_title')"
                        :description="$t('notifications.blocked_help')"
                    />

                    <u-alert
                        v-else-if="push.reason.value === push.REASON.IOS_NEEDS_INSTALL"
                        color="info"
                        variant="soft"
                        icon="i-lucide-share"
                        :title="$t('notifications.ios_title')"
                        :description="$t('notifications.ios_help')"
                    />

                    <u-alert
                        v-else-if="push.reason.value === push.REASON.SERVER_UNCONFIGURED"
                        color="error"
                        variant="soft"
                        icon="i-lucide-server-off"
                        :title="$t('notifications.server_title')"
                        :description="$t('notifications.server_help')"
                    />

                    <u-alert
                        v-else-if="push.reason.value === push.REASON.INSECURE"
                        color="error"
                        variant="soft"
                        icon="i-lucide-lock-open"
                        :title="$t('notifications.insecure_title')"
                        :description="$t('notifications.insecure_help')"
                    />
                </div>
            </client-only>

            <!-- Every browser that has ever registered, so "it works on my
                 laptop but not my phone" is something you can look at rather
                 than infer. Never the endpoint itself — that is the address a
                 push is delivered to. -->
            <div v-if="devices.length" class="mt-6 pt-4 border-t border-default space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $t('notifications.devices_heading') }}</p>

                <div
                    v-for="device in devices"
                    :key="device.id"
                    class="flex items-center justify-between gap-3 text-sm"
                >
                    <div class="min-w-0">
                        <span class="text-highlighted">{{ device.label }}</span>
                        <span class="text-dimmed ms-1.5 font-mono text-xs">{{ device.fingerprint }}</span>

                        <u-badge v-if="device.expired" color="neutral" variant="subtle" size="sm" class="ms-2" :label="$t('notifications.device_expired')" />
                        <!-- Named rather than guessed at. After a VAPID key
                             change these rows behave identically to healthy
                             ones — the push service keeps accepting sends to
                             them — so nothing but this badge distinguishes a
                             device that will never receive anything again. -->
                        <u-badge v-else-if="device.stale" color="warning" variant="subtle" size="sm" class="ms-2" :label="$t('notifications.device_stale')" />

                        <p class="text-xs text-muted">
                            {{ device.lastUsed ? $t('notifications.device_last_used', { when: device.lastUsed }) : $t('notifications.device_never_used') }}
                        </p>
                    </div>

                    <u-button
                        icon="i-lucide-trash-2"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        :aria-label="$t('notifications.device_forget')"
                        @click="forget(device)"
                    />
                </div>
            </div>
        </u-card>

        <!-- What you want to hear about. Shared by every device on purpose. -->
        <u-card>
            <template #header>
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-2">
                        <u-icon name="i-lucide-sliders-horizontal" class="size-4 shrink-0" />
                        <span class="font-semibold">{{ $t('notifications.categories_heading') }}</span>
                    </div>

                    <u-button
                        color="primary"
                        size="sm"
                        :loading="form.processing"
                        :disabled="! dirty"
                        :label="$t('notifications.save_preferences')"
                        @click="save"
                    />
                </div>
            </template>

            <p class="text-sm text-muted mb-4">{{ $t('notifications.categories_desc') }}</p>

            <div v-for="group in grouped" :key="group.audience" class="mb-6 last:mb-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted mb-2">
                    {{ group.audience === 'host' ? $t('notifications.audience_host') : $t('notifications.audience_player') }}
                </p>

                <div class="divide-y divide-default border-y border-default">
                    <div
                        v-for="category in group.items"
                        :key="category.key"
                        class="flex items-start gap-3 py-3"
                    >
                        <u-icon :name="category.icon" class="size-4 mt-0.5 shrink-0 text-muted" />

                        <div class="min-w-0 flex-1">
                            <label :for="`cat-${category.key}`" class="text-sm font-medium text-highlighted">{{ category.label }}</label>
                            <p class="text-sm text-muted">{{ category.description }}</p>

                            <!-- The preview is the only way anybody sees most
                                 of these before the moment they matter: the
                                 real triggers are a host approving a claim, a
                                 race ending, a sync breaking. Sending a real
                                 push is also the only proof the whole chain
                                 works on THIS device. -->
                            <u-button
                                v-if="canPreview"
                                class="mt-1 -ms-2"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                icon="i-lucide-send"
                                :loading="previewing === category.key"
                                :label="$t('notifications.send_preview')"
                                @click="preview(category.key)"
                            />
                        </div>

                        <!-- u-switch is one of the interactive @nuxt/ui
                             components that pulls in the '#imports' virtual
                             specifier and crashes the SSR build — see
                             ClientOnly.vue and the SSR gotchas list. -->
                        <client-only>
                            <u-switch :id="`cat-${category.key}`" v-model="form.preferences[category.key]" />
                        </client-only>
                    </div>
                </div>
            </div>
        </u-card>
    </settings-layout>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import { usePush } from '@/Composables/usePush';

const props = defineProps({
    categories: { type: Array, required: true },
    preferences: { type: Object, required: true },
    optedOut: { type: Boolean, default: false },
    serverConfigured: { type: Boolean, default: true },
    devices: { type: Array, default: () => [] },
});

const push = usePush();

onMounted(async () => {
    // The two facts the browser cannot know. Hydrated before refresh() so the
    // reason shown on first paint is already the right one, rather than
    // flipping from "ready" to "the server has no keys" a moment later.
    push.hydrate({ serverConfigured: props.serverConfigured, optedOut: props.optedOut });

    await push.refresh();
});

const form = useForm({ preferences: { ...props.preferences } });

// Compared against the server's copy rather than tracked with a flag, so
// switching something off and back on correctly reads as no change.
const dirty = computed(
    () => JSON.stringify(form.preferences) !== JSON.stringify(props.preferences),
);

const grouped = computed(() => {
    const order = ['player', 'host'];

    return order
        .map((audience) => ({
            audience,
            items: props.categories.filter((category) => category.audience === audience),
        }))
        .filter((group) => group.items.length > 0);
});

const isOn = computed(
    () => push.reason.value === push.REASON.SUBSCRIBED,
);

/**
 * Only where pressing it could actually change something. The three states
 * that a button cannot fix — blocked at the browser level, iOS without an
 * installed app, no server keys — get a sentence instead, above.
 */
const canToggle = computed(() => {
    const blocked = [
        push.REASON.UNSUPPORTED,
        push.REASON.INSECURE,
        push.REASON.IOS_NEEDS_INSTALL,
        push.REASON.BLOCKED,
        push.REASON.SERVER_UNCONFIGURED,
    ];

    return ! blocked.includes(push.reason.value);
});

const canPreview = computed(() => isOn.value && props.devices.some((device) => ! device.expired));

const STATE_KEYS = {
    subscribed: 'notifications.state_on',
    ready: 'notifications.state_ready',
    opted_out: 'notifications.state_off',
    blocked: 'notifications.state_blocked',
    ios_needs_install: 'notifications.state_ios',
    insecure: 'notifications.state_insecure',
    unsupported: 'notifications.state_unsupported',
    server_unconfigured: 'notifications.state_server',
};

const stateTitle = computed(() => trans(STATE_KEYS[push.reason.value] ?? 'notifications.state_unsupported'));
const stateDescription = computed(() => trans(`${STATE_KEYS[push.reason.value] ?? 'notifications.state_unsupported'}_desc`));

async function toggleDevice() {
    const ok = isOn.value ? await push.disable() : await push.enable();

    // Reloaded either way: the device list is server state and both branches
    // change it. `only` keeps it to the one prop rather than re-rendering a
    // page the user is looking at.
    if (ok) router.reload({ only: ['devices', 'optedOut'] });
}

function save() {
    form.put('/settings/notifications', {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
    });
}

const previewing = ref(null);

function preview(category) {
    previewing.value = category;

    router.post(
        '/settings/notifications/preview',
        { category },
        {
            preserveScroll: true,
            onError: (errors) => console.error(errors),
            onFinish: () => (previewing.value = null),
        },
    );
}

function forget(device) {
    router.delete(`/settings/notifications/devices/${device.id}`, {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
    });
}
</script>
