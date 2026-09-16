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

        <u-card>
            <template #header>
                <span class="font-semibold">{{ $t('plugin.steps_title') }}</span>
            </template>

            <ol class="list-decimal ps-5 space-y-1.5 text-sm">
                <li>{{ $t('plugin.step_install') }}</li>
                <li>{{ $t('plugin.step_paste') }}</li>
                <li>{{ $t('plugin.step_enable') }}</li>
            </ol>

            <p v-if="osrsUsername" class="mt-4 text-sm text-muted">{{ $t('plugin.osrs_name', { name: osrsUsername }) }}</p>
            <u-alert
                v-else
                class="mt-4"
                color="warning"
                variant="subtle"
                icon="i-lucide-user-round"
                :description="$t('plugin.osrs_name_missing')"
            />
        </u-card>
    </settings-layout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import { formatDate } from '@/Support/board';

const props = defineProps({
    mode: { type: String, required: true },
    token: { type: Object, default: null },
    newCode: { type: String, default: null },
    osrsUsername: { type: String, default: null },
});

const confirming = ref(null);
const busy = ref(false);
const copied = ref(false);

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
