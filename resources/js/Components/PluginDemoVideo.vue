<template>
    <!-- Self-hosted on purpose: an embed would set third-party cookies the
         privacy page promises not to. Click to play, nothing downloads until
         then — no autoplay to watch for viewport or reduced motion. -->
    <figure class="space-y-2">
        <video
            src="/videos/plugin-demo-144e3c84.mp4"
            poster="/videos/plugin-demo-poster-652d49b1.webp"
            width="1280"
            height="720"
            controls
            muted
            playsinline
            loop
            preload="none"
            :aria-label="$t('plugin.demo_label')"
            class="block w-full h-auto aspect-video rounded-xl border border-default bg-black"
        />
        <figcaption class="text-xs text-muted" :class="captionClass">{{ $t('plugin.demo_caption') }}</figcaption>
    </figure>
</template>

<script setup>
defineProps({
    captionClass: { type: String, default: '' },
});
</script>
