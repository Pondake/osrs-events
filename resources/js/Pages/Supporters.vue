<template>
    <seo-head :options="seo" />

    <u-main>
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6 lg:py-10 space-y-4">
            <header class="rounded-lg ring ring-default bg-elevated/50 px-5 py-5 sm:px-6 sm:py-6">
                <h1 class="font-bold text-highlighted">{{ $t('supporters.title') }}</h1>
                <p class="mt-1.5 text-muted leading-relaxed max-w-2xl">{{ $t('supporters.subtitle') }}</p>
            </header>

            <section v-for="group in groups" :key="group.role" class="rounded-lg ring ring-default bg-default overflow-hidden">
                <h2 class="flex items-center gap-2 bg-elevated px-5 py-2.5 border-b border-default">
                    <u-icon :name="ROLE_ICONS[group.role]" class="size-4 text-primary shrink-0" />
                    <span class="text-sm uppercase tracking-wide font-semibold text-highlighted">{{ $t(`supporters.role_${group.role}`) }}</span>
                    <span class="text-xs text-muted tabular-nums">{{ group.supporters.length }}</span>
                </h2>

                <div class="p-5">
                    <p class="text-sm text-muted">{{ $t(`supporters.role_${group.role}_desc`) }}</p>

                    <ul class="mt-4 grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        <li v-for="supporter in group.supporters" :key="supporter.id" class="min-w-0">
                            <span class="block font-medium text-highlighted break-words">{{ supporter.name }}</span>
                            <a
                                v-if="isUrl(supporter.link)"
                                :href="supporter.link"
                                target="_blank"
                                rel="nofollow ugc noopener noreferrer"
                                class="inline-flex max-w-full items-center gap-1 text-xs text-muted hover:text-primary transition-colors max-sm:min-h-11"
                            >
                                <span class="truncate">{{ displayUrl(supporter.link) }}</span>
                                <u-icon name="i-lucide-external-link" class="size-3 shrink-0" />
                            </a>
                            <span v-else-if="supporter.link" class="block text-xs text-muted break-words">{{ supporter.link }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section v-if="!groups.length" class="rounded-lg ring ring-default bg-elevated/50 px-5 py-10 text-center">
                <u-icon name="i-lucide-heart-handshake" class="size-8 text-primary mx-auto" />
                <p class="mt-3 font-semibold text-highlighted">{{ $t('supporters.empty_title') }}</p>
                <p class="mt-1.5 text-sm text-muted leading-relaxed max-w-md mx-auto">{{ $t('supporters.empty_body') }}</p>
            </section>

            <footer class="rounded-lg ring ring-default bg-elevated/50 px-5 py-4 text-sm text-muted leading-relaxed space-y-1">
                <p v-if="groups.length">{{ $t('supporters.consent_note') }}</p>
                <p v-if="discordInviteUrl">
                    <a href="/discord" class="inline-flex items-center underline underline-offset-2 hover:text-primary transition-colors max-sm:min-h-11">{{ $t('supporters.consent_discord') }}</a>
                </p>
                <p>
                    {{ $t('supporters.kofi_prompt') }}
                    <a
                        :href="kofiUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 underline underline-offset-2 hover:text-primary transition-colors max-sm:min-h-11"
                    >
                        <u-icon name="i-lucide-coffee" class="size-3.5 shrink-0" />
                        {{ $t('supporters.kofi_link') }}
                    </a>
                </p>
            </footer>
        </div>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';
import { useCurrentPage } from '@/Support/pageState';

defineProps({
    // { role, supporters: { id, name, link }[] }[] — empty groups left out server-side.
    groups: { type: Array, required: true },
});

const ROLE_ICONS = {
    tester: 'i-lucide-bug',
    ideas: 'i-lucide-lightbulb',
    donor: 'i-lucide-coffee',
};

const page = useCurrentPage();
const discordInviteUrl = computed(() => page.value.props?.site?.discordInviteUrl ?? null);
const kofiUrl = computed(() => page.value.props?.site?.kofiUrl ?? 'https://ko-fi.com');

// Only http(s) becomes a link; anything else, a javascript: scheme included, stays text.
const isUrl = (value) => typeof value === 'string' && /^https?:\/\/\S+$/i.test(value);

const displayUrl = (value) => value.replace(/^https?:\/\/(www\.)?/i, '').replace(/\/$/, '');

const seo = {
    title: trans('seo.supporters_title'),
    description: trans('seo.supporters_desc'),
};
</script>
