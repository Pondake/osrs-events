<template>
    <!-- This is a public, indexable page (BoardController::index is
         deliberately outside the auth middleware) but it carried no meta at
         all — no canonical, no og:, no twitter card. The seo.boards_*
         translation keys already existed and were simply never wired up. -->
    <seo-head :options="seo" />

    <u-main>
        <u-page>
            <u-container class="py-12">
                <u-breadcrumb :items="breadcrumbs" class="mb-4" />

                <!-- Stacks on a phone. Side by side, the title block was left
                     with a ~150px column and the description broke into
                     four ragged lines beside a button that needed none of
                     the room it was taking. -->
                <div class="flex flex-col items-start gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-highlighted">{{ $t('events.hub_title') }}</h1>
                        <p class="text-sm text-muted mt-1">{{ $t('events.hub_subtitle') }}</p>
                    </div>

                    <u-button
                        v-if="canCreateBoards"
                        color="primary"
                        icon="i-lucide-plus"
                        :label="$t('admin.create_board')"
                        @click="showCreateModal = true"
                    />
                </div>

                <!-- An event dashboard, not one flat list. Four rows in the
                     order you actually care about them: what you run, what
                     you play, what anyone can join, and what is coming. Each
                     shows a few and links to its own overview.

                     Hosting and playing are separate rows because they are
                     separate questions — the host of a race is very often not
                     entered in it, and one merged "yours" bucket hid that. -->
                <div class="space-y-10">
                    <section v-if="!showAll && hosted.length">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-xl font-semibold text-highlighted">{{ $t('events.hub_hosted') }}</h2>
                            <u-button
                                href="/my-events?filter=hosted"
                                size="sm"
                                variant="ghost"
                                color="neutral"
                                trailing-icon="i-lucide-arrow-right"
                                :label="$t('events.hub_view_all')"
                            />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <board-card v-for="board in hosted" :key="board.id" :board="board" />
                        </div>
                    </section>

                    <section v-if="!showAll && playing.length">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-xl font-semibold text-highlighted">{{ $t('events.hub_playing') }}</h2>
                            <u-button
                                href="/my-events?filter=playing"
                                size="sm"
                                variant="ghost"
                                color="neutral"
                                trailing-icon="i-lucide-arrow-right"
                                :label="$t('events.hub_view_all')"
                            />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <board-card v-for="board in playing" :key="board.id" :board="board" />
                        </div>
                    </section>

                    <section>
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-xl font-semibold text-highlighted">{{ showAll ? $t('boards.title') : $t('events.hub_public') }}</h2>
                            <u-button
                                v-if="boards.length > publicSlice.length"
                                href="/events/all"
                                size="sm"
                                variant="ghost"
                                color="neutral"
                                trailing-icon="i-lucide-arrow-right"
                                :label="$t('events.hub_view_all')"
                            />
                        </div>

                        <div v-if="!boards.length" class="text-center py-12 rounded-lg ring ring-default bg-default">
                            <u-icon name="i-lucide-layout-grid" class="size-10 text-dimmed mx-auto mb-3" />
                            <p class="text-sm text-muted">{{ $t('events.hub_public_empty') }}</p>
                        </div>
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <board-card v-for="board in publicSlice" :key="board.id" :board="board" />
                        </div>
                    </section>

                    <!-- Advertised in the nav as Soon already; saying so here
                         too beats a section that silently isn't there. -->
                    <section v-if="!showAll">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-semibold text-highlighted">{{ $t('events.hub_calendar') }}</h2>
                            <u-badge :label="$t('nav.badge_soon')" color="neutral" variant="subtle" size="sm" />
                        </div>
                        <div class="rounded-lg ring ring-default bg-default px-5 py-8 text-center">
                            <u-icon name="i-lucide-calendar" class="size-10 text-dimmed mx-auto mb-3" />
                            <p class="text-sm text-muted max-w-md mx-auto">{{ $t('events.hub_calendar_desc') }}</p>
                        </div>
                    </section>
                </div>

            </u-container>
        </u-page>

        <client-only>
            <board-settings-modal v-model:open="showCreateModal" :board="null" />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import BoardCard from '@/Components/BoardCard.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import SeoHead from '@/Components/SeoHead.vue';

const seo = {
    title: trans('seo.boards_title'),
    description: trans('seo.boards_desc'),
};

// No href on the last crumb — it's this page, not a link to itself.
const breadcrumbs = [
    { label: trans('nav.home'), icon: 'i-lucide-house', href: '/' },
    { label: trans('nav.events') },
];

// Dynamic import, not a static one — a static import would still pull
// BoardSettingsModal (and the @nuxt/ui form components it uses) into the SSR
// module graph even though <client-only> keeps it from ever rendering
// server-side. See ClientOnly.vue and vite.config.js for why that matters.
const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

const props = defineProps({
    boards: { type: Array, required: true },
    hosted: { type: Array, default: () => [] },
    hostedTotal: { type: Number, default: 0 },
    playing: { type: Array, default: () => [] },
    playingTotal: { type: Number, default: 0 },
    /** /events/all renders the same component without the hub slicing. */
    showAll: { type: Boolean, default: false },
});

// The hub shows a slice; /events/all renders the same prop in full.
const HUB_SLICE = 6;

const publicSlice = computed(() => (props.showAll ? props.boards : props.boards.slice(0, HUB_SLICE)));

const { canCreateBoards } = useAuth();
const showCreateModal = ref(false);
</script>
