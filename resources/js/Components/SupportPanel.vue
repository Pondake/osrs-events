<template>
    <section class="rounded-lg ring ring-default bg-elevated/50 overflow-hidden">
        <!-- Size on the span, not the h2: see About.vue's titleBar note. -->
        <h2 class="flex items-center gap-2 bg-elevated px-5 py-2.5 border-b border-default">
            <u-icon name="i-lucide-heart" class="size-4 text-primary shrink-0" />
            <span class="text-sm uppercase tracking-wide font-semibold text-highlighted">{{ $t('about.free_title') }}</span>
        </h2>

        <div class="p-5">
            <p class="text-sm text-muted leading-relaxed">{{ $t('about.free_body') }}</p>

            <u-button
                :href="kofiUrl"
                target="_blank"
                rel="noopener noreferrer"
                size="sm"
                color="warning"
                variant="outline"
                icon="i-lucide-coffee"
                class="mt-4 max-sm:min-h-11"
                :label="$t('about.donate_cta')"
            />
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { useCurrentPage } from '@/Support/pageState';

/** The Ko-fi panel, shared by /about and /supporters. */
const page = useCurrentPage();

const kofiUrl = computed(() => page.value.props?.site?.kofiUrl ?? 'https://ko-fi.com/pondake');
</script>
