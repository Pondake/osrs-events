<template>
    <Head :title="$t('plugin.title')" />

    <settings-layout current="runelite">
        <div class="flex items-center gap-2 flex-wrap">
            <p class="text-sm text-muted">{{ $t('plugin.desc') }}</p>
            <u-badge v-if="mode === 'testing'" color="warning" variant="subtle" icon="i-lucide-flask-conical" :label="$t('plugin.testing_badge')" />
        </div>

        <u-alert
            v-if="mode === 'testing'"
            color="warning"
            variant="subtle"
            icon="i-lucide-flask-conical"
            :description="$t('plugin.testing_desc')"
        />

        <u-alert
            v-if="newCode"
            color="success"
            variant="subtle"
            icon="i-lucide-key-round"
            :title="$t('plugin.new_code_title')"
            :description="$t('plugin.new_code_desc')"
        >
            <template #actions>
                <div class="flex items-center gap-2 w-full flex-wrap">
                    <code class="flex-1 min-w-0 break-all rounded-md bg-default px-3 py-2 text-sm font-mono select-all">{{ newCode }}</code>
                    <u-button
                        color="success"
                        icon="i-lucide-copy"
                        :label="copied ? $t('plugin.copied') : $t('plugin.copy')"
                        @click="copy"
                    />
                </div>
            </template>
        </u-alert>

        <u-card v-if="token">
            <template #header>
                <span class="font-semibold">{{ $t('plugin.status_title') }}</span>
            </template>

            <div class="text-sm space-y-3">
                <div class="flex items-center gap-2">
                    <span class="relative flex size-2.5 shrink-0">
                        <span
                            v-if="status.connection.state === 'connected'"
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-75"
                        />
                        <span class="relative inline-flex size-2.5 rounded-full" :class="connectionDotClass" />
                    </span>
                    <span>{{ connectionLabel }}</span>
                </div>

                <!-- The reason to connect at all for somebody who only
                     claims by screenshot: one connection proves the name, and
                     an unproven name means a host checks everything. -->
                <div class="flex items-start gap-2">
                    <u-icon
                        :name="status.proven.at ? 'i-lucide-shield-check' : 'i-lucide-shield-alert'"
                        class="size-4 mt-0.5 shrink-0"
                        :class="status.proven.at ? 'text-success' : 'text-warning'"
                    />
                    <span>
                        {{ status.proven.at
                            ? $t('plugin.status_proven', { date: formatDate(status.proven.at) })
                            : (osrsUsername
                                ? $t('plugin.status_unproven', { name: osrsUsername })
                                : $t('plugin.status_unproven_no_name')) }}
                    </span>
                </div>

                <p>
                    {{ status.watching.count > 0
                        ? $t('plugin.status_watching', { count: status.watching.count, events: status.watching.events })
                        : $t('plugin.status_watching_none') }}
                </p>

                <div>
                    <p class="font-medium mb-1.5">{{ $t('plugin.status_reports_title') }}</p>
                    <p v-if="status.reports.length === 0" class="text-muted">{{ $t('plugin.status_no_reports') }}</p>
                    <ul v-else class="space-y-2">
                        <li v-for="report in status.reports" :key="report.id" class="rounded-lg ring ring-default px-3 py-2">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <span class="font-medium">{{ report.name }}</span>
                                <span class="text-xs text-muted">{{ formatDate(report.createdAt) }}</span>
                            </div>
                            <p v-if="report.claims.length === 0" class="text-muted text-xs mt-0.5">
                                {{ $t('plugin.status_report_no_match') }}
                            </p>
                            <ul v-else class="mt-0.5 space-y-0.5">
                                <li v-for="(claim, index) in report.claims" :key="index" class="text-xs text-muted">
                                    {{ claim.label }} — {{ claim.eventTitle }} ({{ claimStatusLabel(claim.status) }})
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </u-card>

        <u-card v-if="tests">
            <template #header>
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <span class="font-semibold">{{ $t('plugin_tests.checklist_title') }}</span>
                    <div class="flex items-center gap-2">
                        <u-button
                            v-if="tests.testSet"
                            :to="tests.testSet.url"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            icon="i-lucide-grid-3x3"
                            :label="$t('plugin_tests.open_event')"
                        />
                        <u-button
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            icon="i-lucide-rotate-ccw"
                            :label="$t('plugin_tests.reset')"
                            @click="confirmingReset = true"
                        />
                    </div>
                </div>
            </template>

            <p class="text-sm text-muted mb-3">{{ $t('plugin_tests.checklist_desc') }}</p>

            <div v-if="confirmingReset" class="mb-3 flex items-center justify-between gap-3 flex-wrap rounded-lg ring ring-default px-3 py-2">
                <p class="text-sm">{{ $t('plugin_tests.reset_warning') }}</p>
                <div class="flex items-center gap-2">
                    <u-button color="neutral" variant="ghost" size="sm" :label="$t('common.cancel')" @click="confirmingReset = false" />
                    <u-button color="primary" size="sm" :label="$t('plugin_tests.reset')" :loading="resetting" @click="resetTests" />
                </div>
            </div>

            <plugin-test-checklist :scenarios="tests.scenarioDetails" />

            <details class="group mt-4 rounded-lg ring ring-default">
                <summary class="flex items-center gap-3 px-3 py-2.5 list-none hover:bg-elevated/50 rounded-lg focus-visible:outline-2 focus-visible:outline-primary min-h-11">
                    <u-icon name="i-lucide-list" class="size-5 shrink-0 text-muted" />
                    <span class="flex-1 font-medium">{{ $t('plugin_tests.log_title', { count: tests.reportCount }) }}</span>
                    <u-icon name="i-lucide-chevron-down" class="size-4 shrink-0 text-muted transition-transform group-open:rotate-180" />
                </summary>
                <div class="px-3 pb-3">
                    <plugin-report-log :reports="tests.log" />
                </div>
            </details>
        </u-card>

        <u-card>
            <template #header>
                <span class="font-semibold">{{ $t('plugin.code_title') }}</span>
            </template>

            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div v-if="token" class="text-sm space-y-0.5">
                    <p class="font-mono">{{ $t('plugin.code_hint', { hint: token.hint }) }}</p>
                    <p class="text-muted">{{ $t('plugin.created_at', { date: formatDate(token.createdAt) }) }}</p>
                    <p class="text-muted">
                        {{ token.lastUsedAt ? $t('plugin.last_used_at', { date: formatDate(token.lastUsedAt) }) : $t('plugin.never_used') }}
                    </p>
                </div>
                <p v-else class="text-sm text-muted">{{ $t('plugin.no_code') }}</p>

                <div class="flex items-center gap-2 shrink-0">
                    <u-button
                        v-if="token"
                        color="error"
                        variant="ghost"
                        icon="i-lucide-trash-2"
                        :label="$t('plugin.revoke_code')"
                        @click="confirming = 'revoke'"
                    />
                    <u-button
                        color="primary"
                        :icon="token ? 'i-lucide-refresh-cw' : 'i-lucide-key-round'"
                        :label="token ? $t('plugin.replace_code') : $t('plugin.create_code')"
                        :loading="busy"
                        @click="token ? (confirming = 'replace') : createCode()"
                    />
                </div>
            </div>

            <!-- Until the first connection only: after that the steps are done. -->
            <ol v-if="!token || status.connection.state === 'never'" class="mt-4 list-decimal ps-5 space-y-1.5 text-sm text-muted">
                <li>{{ $t('plugin.step_install') }}</li>
                <li>{{ $t('plugin.step_paste') }}</li>
                <li>{{ $t('plugin.step_enable') }}</li>
            </ol>

            <div v-if="confirming" class="mt-4 flex items-center justify-between gap-3 flex-wrap rounded-lg ring ring-default px-3 py-2">
                <p class="text-sm">{{ $t(confirming === 'replace' ? 'plugin.replace_warning' : 'plugin.revoke_warning') }}</p>
                <div class="flex items-center gap-2">
                    <u-button color="neutral" variant="ghost" size="sm" :label="$t('common.cancel')" @click="confirming = null" />
                    <u-button
                        :color="confirming === 'replace' ? 'primary' : 'error'"
                        size="sm"
                        :label="$t(confirming === 'replace' ? 'plugin.replace_code' : 'plugin.revoke_code')"
                        :loading="busy"
                        @click="confirming === 'replace' ? createCode() : revokeCode()"
                    />
                </div>
            </div>
        </u-card>

        <u-alert
            v-if="!osrsUsername"
            color="warning"
            variant="subtle"
            icon="i-lucide-user-round"
            :description="$t('plugin.osrs_name_missing')"
        />
    </settings-layout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import PluginReportLog from '@/Components/PluginReportLog.vue';
import PluginTestChecklist from '@/Components/PluginTestChecklist.vue';
import { formatDate, relativeTime } from '@/Support/board';
import { useEventStream } from '@/Composables/useEventStream';

const props = defineProps({
    mode: { type: String, required: true },
    token: { type: Object, default: null },
    newCode: { type: String, default: null },
    tests: { type: Object, default: null },
    osrsUsername: { type: String, default: null },
    status: {
        type: Object,
        default: () => ({ connection: { state: 'none', lastUsedAt: null }, watching: { count: 0, events: 0 }, reports: [] }),
    },
});

const confirming = ref(null);
const busy = ref(false);
const copied = ref(false);
const confirmingReset = ref(false);
const resetting = ref(false);

const status = ref(props.status);

// A full Inertia visit (e.g. after creating/revoking the code) re-renders
// with fresh props on the same mounted component — the stream carries
// on updating from there, but that reload itself has to reach the ref too.
watch(() => props.status, (value) => (status.value = value));

useEventStream({
    url: () => (props.token ? '/settings/runelite/stream' : null),
    event: 'status',
    onMessage: (payload) => {
        status.value = payload;
        // The checklist is per viewer and heavier than the status, so it is
        // reloaded only when the stream says a report came in.
        if (props.tests) router.reload({ only: ['tests'] });
    },
});

const connectionLabel = computed(() => {
    switch (status.value.connection.state) {
        case 'connected':
            return trans('plugin.status_connected');
        case 'stale':
            return trans('plugin.status_stale', { time: relativeTime(status.value.connection.lastUsedAt) });
        default:
            return trans('plugin.status_never');
    }
});

const connectionDotClass = computed(() => (status.value.connection.state === 'connected' ? 'bg-success' : 'bg-muted'));

function claimStatusLabel(claimStatus) {
    switch (claimStatus) {
        case 'APPROVED':
            return trans('plugin.status_claim_approved');
        case 'REJECTED':
            return trans('plugin.status_claim_rejected');
        default:
            return trans('plugin.status_claim_pending');
    }
}

let toast = null;

onMounted(async () => {
    const { useToast } = await import('@nuxt/ui/composables/useToast');
    toast = useToast();
});

function createCode() {
    busy.value = true;
    router.post('/settings/runelite/code', {}, {
        preserveScroll: true,
        onSuccess: () => (confirming.value = null),
        onError: (errors) => console.error(errors),
        onFinish: () => (busy.value = false),
    });
}

function revokeCode() {
    busy.value = true;
    router.delete('/settings/runelite/code', {
        preserveScroll: true,
        onSuccess: () => (confirming.value = null),
        onError: (errors) => console.error(errors),
        onFinish: () => (busy.value = false),
    });
}

function resetTests() {
    resetting.value = true;
    router.post('/settings/runelite/test-reset', {}, {
        preserveScroll: true,
        onSuccess: () => (confirmingReset.value = false),
        onError: (errors) => console.error(errors),
        onFinish: () => (resetting.value = false),
    });
}

async function copy() {
    try {
        await navigator.clipboard.writeText(props.newCode ?? '');
        copied.value = true;
    } catch (error) {
        console.error(error);
        toast?.add({ id: 'plugin-code-copy', title: trans('errors.copy_failed'), color: 'error' });
    }
}
</script>
