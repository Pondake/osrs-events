<template>
    <u-main>
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <header class="max-w-3xl mb-10 lg:mb-14">
                <h1 class="text-3xl sm:text-4xl font-bold text-highlighted mb-3">{{ title }}</h1>
                <p class="text-lg text-muted leading-relaxed mb-6">{{ description }}</p>
                <div class="flex flex-wrap gap-3">
                    <slot name="cta" />
                </div>
            </header>

            <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_260px] lg:gap-12 lg:items-start">
                <!-- prose-width article column, not the full grid width — a
                     line of text stretched to 1440px is what made the old
                     u-page-section version unreadable in the first place. -->
                <article class="max-w-3xl min-w-0">
                    <slot />
                </article>

                <!-- Sidebar is desktop-only on purpose: the guides are short
                     enough on a phone that a jump-to-section nav would be
                     one more tap before you reach content you could already
                     see by scrolling. Wiki mobile views make the same call. -->
                <aside class="hidden lg:block sticky top-20 space-y-8 text-sm">
                    <nav v-if="sections?.length" aria-label="On this page">
                        <p class="uppercase text-xs tracking-wide font-semibold text-dimmed mb-2">{{ $t('guide.on_this_page') }}</p>
                        <ul class="space-y-1.5 border-l border-default">
                            <li v-for="section in sections" :key="section.id">
                                <a
                                    :href="`#${section.id}`"
                                    class="block pl-3 -ml-px border-l-2 border-transparent text-muted hover:text-primary hover:border-primary transition-colors py-0.5"
                                >{{ section.label }}</a>
                            </li>
                        </ul>
                    </nav>

                    <div v-if="quickFacts?.length">
                        <p class="uppercase text-xs tracking-wide font-semibold text-dimmed mb-2">{{ $t('guide.quick_facts') }}</p>
                        <dl class="rounded-lg ring ring-default bg-elevated divide-y divide-default">
                            <div v-for="fact in quickFacts" :key="fact.label" class="px-3 py-2">
                                <dt class="text-xs text-dimmed">{{ fact.label }}</dt>
                                <dd class="text-default font-medium">{{ fact.value }}</dd>
                            </div>
                        </dl>
                    </div>

                    <nav aria-label="Other guides">
                        <p class="uppercase text-xs tracking-wide font-semibold text-dimmed mb-2">{{ $t('guide.other_guides') }}</p>
                        <ul class="space-y-1.5">
                            <li v-for="link in otherGuides" :key="link.to">
                                <a :href="link.to" class="flex items-center gap-2 text-muted hover:text-primary transition-colors">
                                    <u-icon :name="link.icon" class="size-4 shrink-0" />
                                    <span>{{ link.label }}</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </aside>
            </div>
        </div>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { GUIDE_LINKS } from '@/Support/guides';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    // { id, label }[] — anchors an <h2 :id> in the slotted content is
    // expected to carry. Not derived from the DOM: the section list is
    // small and fixed per page, so each page just states it once rather
    // than this component walking rendered headings to reconstruct it.
    sections: { type: Array, default: () => [] },
    // { label, value }[] — a compact wiki-infobox-style fact box. Optional:
    // not every guide has enough fixed facts to make one worth showing.
    quickFacts: { type: Array, default: () => [] },
    // This page's own path, so it doesn't link to itself under "Other guides".
    currentPath: { type: String, required: true },
});

const otherGuides = computed(() => GUIDE_LINKS
    .filter((link) => link.to !== props.currentPath)
    .map((link) => ({ ...link, label: trans(link.labelKey) })));
</script>
