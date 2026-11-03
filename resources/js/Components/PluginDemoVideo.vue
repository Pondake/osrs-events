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
        </div>
        <figcaption class="text-xs text-muted" :class="captionClass">{{ $t(caption) }}</figcaption>
    </figure>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    captionClass: { type: String, default: '' },
    sources: { type: Array, default: () => ['/videos/plugin-demo-91eaea83.webm', '/videos/plugin-demo-ad7e4562.mp4'] },
    poster: { type: String, default: '/videos/plugin-demo-poster-e3b42295.webp' },
    width: { type: Number, default: 1920 },
    height: { type: Number, default: 1080 },
    // i18n keys
    label: { type: String, default: 'plugin.demo_label' },
    caption: { type: String, default: 'plugin.demo_caption' },
});

const video = ref(null);
const playing = ref(false);

function toggle() {
    const el = video.value;
    if (!el) return;
    if (el.paused) {
        el.play().catch((error) => console.error(error));
    } else {
        el.pause();
    }
}
</script>
