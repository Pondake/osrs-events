<template>
    <!-- Self-hosted on purpose: an embed would set third-party cookies the
         privacy page promises not to. Click to play, nothing downloads until
         then. The file has no audio track, so play/pause is the only control. -->
    <figure class="space-y-2">
        <div class="relative overflow-hidden rounded-xl border border-default shadow-2xl shadow-primary/10 ring-1 ring-default">
            <video
                ref="video"
                :poster="poster"
                :width="width"
                :height="height"
                muted
                playsinline
                loop
                preload="none"
                :aria-label="$t(label)"
                class="block w-full h-auto aspect-video bg-black cursor-pointer"
                @click="toggle"
                @play="playing = true"
                @pause="playing = false"
            >
                <source v-for="url in sources" :key="url" :src="url" :type="url.endsWith('.webm') ? 'video/webm' : 'video/mp4'" />
            </video>
            <button
                type="button"
                :aria-label="playing ? $t('plugin.demo_pause') : $t('plugin.demo_play')"
                class="absolute flex items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-sm cursor-pointer transition hover:bg-primary hover:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                :class="playing ? 'bottom-3 left-3 size-11' : 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-16'"
                @click="toggle"
            >
                <u-icon :name="playing ? 'i-lucide-pause' : 'i-lucide-play'" :class="playing ? 'size-5' : 'size-7 ms-1'" />
            </button>
            <button
                ref="enlargeButton"
                type="button"
                :aria-label="$t('landing.video_enlarge')"
                class="absolute bottom-3 right-3 flex size-11 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-sm cursor-pointer transition hover:bg-primary hover:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                @click="enlarge"
            >
                <u-icon name="i-lucide-maximize-2" class="size-5" />
            </button>
        </div>
        <video-lightbox
            :open="large"
            :sources="sources"
            :poster="poster"
            :width="width"
            :height="height"
            :label="$t(label)"
            :start-at="startAt"
            @close="shrink"
        />
        <figcaption v-if="caption" class="text-xs text-muted" :class="captionClass">{{ $t(caption) }}</figcaption>
    </figure>
</template>

<script setup>
import { nextTick, ref } from 'vue';
import VideoLightbox from '@/Components/VideoLightbox.vue';
import { seekVideo } from '@/Support/video';

defineProps({
    captionClass: { type: String, default: '' },
    sources: { type: Array, default: () => ['/videos/plugin-demo-01362f03.webm', '/videos/plugin-demo-d51eb362.mp4'] },
    poster: { type: String, default: '/videos/plugin-demo-poster-aa2ae22a.webp' },
    width: { type: Number, default: 1920 },
    height: { type: Number, default: 1080 },
    // i18n keys
    label: { type: String, default: 'plugin.demo_label' },
    caption: { type: String, default: 'plugin.demo_caption' },
});

const video = ref(null);
const enlargeButton = ref(null);
const playing = ref(false);
const large = ref(false);
const startAt = ref(0);
let resume = false;

function toggle() {
    const el = video.value;
    if (!el) return;
    if (el.paused) {
        el.play().catch((error) => console.error(error));
    } else {
        el.pause();
    }
}

function enlarge() {
    const el = video.value;
    resume = Boolean(el && !el.paused);
    startAt.value = el?.currentTime ?? 0;
    el?.pause();
    large.value = true;
}

// Back in the page where the large one stopped, playing again only if it was.
async function shrink(time) {
    large.value = false;
    seekVideo(video.value, time, resume);
    await nextTick();
    enlargeButton.value?.focus();
}
</script>
