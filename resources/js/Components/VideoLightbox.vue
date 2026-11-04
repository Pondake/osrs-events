<template>
    <!-- Mounted only while open, so the server never renders a teleport. -->
    <Teleport v-if="open" to="body">
        <div
            class="fixed inset-0 z-[75] flex items-center justify-center bg-black/85 p-3 sm:p-8 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            :aria-label="label"
            @click.self="close"
        >
            <div class="relative w-full" :style="{ maxWidth: `min(100%, calc((100dvh - 4rem) * ${width / height}))` }">
                <video
                    ref="video"
                    :poster="poster"
                    :width="width"
                    :height="height"
                    muted
                    loop
                    playsinline
                    class="block w-full h-auto rounded-lg bg-black shadow-2xl cursor-pointer"
                    @click="toggle"
                    @play="playing = true"
                    @pause="playing = false"
                    @loadedmetadata="start"
                    @timeupdate="sync"
                    @durationchange="sync"
                >
                    <source v-for="url in sources" :key="url" :src="url" :type="url.endsWith('.webm') ? 'video/webm' : 'video/mp4'" />
                </video>

                <!-- Play/pause, a seek bar and the time, over the bottom of the video. -->
                <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 sm:gap-3 rounded-b-lg bg-linear-to-t from-black/75 to-transparent px-2 pt-6 pb-1.5 sm:px-3 sm:pb-2">
                    <button
                        type="button"
                        :aria-label="playing ? $t('landing.video_pause') : $t('landing.video_play')"
                        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-sm cursor-pointer transition hover:bg-primary hover:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        @click="toggle"
                    >
                        <u-icon :name="playing ? 'i-lucide-pause' : 'i-lucide-play'" :class="playing ? 'size-5' : 'size-5 ms-0.5'" />
                    </button>
                    <input
                        type="range"
                        min="0"
                        :max="duration || 0"
                        step="0.05"
                        :value="time"
                        :aria-label="$t('landing.video_seek')"
                        :aria-valuetext="$t('landing.video_time', { current: clock(time), total: clock(duration) })"
                        class="h-11 min-w-0 flex-1 cursor-pointer appearance-none bg-transparent focus-visible:outline-none
                            [&::-webkit-slider-runnable-track]:h-1.5 [&::-webkit-slider-runnable-track]:rounded-full [&::-webkit-slider-runnable-track]:bg-(image:--track)
                            [&::-webkit-slider-thumb]:-mt-[5px] [&::-webkit-slider-thumb]:size-4 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-primary [&::-webkit-slider-thumb]:shadow [&::-webkit-slider-thumb]:transition-transform hover:[&::-webkit-slider-thumb]:scale-125
                            focus-visible:[&::-webkit-slider-thumb]:ring-2 focus-visible:[&::-webkit-slider-thumb]:ring-white
                            [&::-moz-range-track]:h-1.5 [&::-moz-range-track]:rounded-full [&::-moz-range-track]:bg-white/25
                            [&::-moz-range-progress]:h-1.5 [&::-moz-range-progress]:rounded-full [&::-moz-range-progress]:bg-primary
                            [&::-moz-range-thumb]:size-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:bg-primary
                            focus-visible:[&::-moz-range-thumb]:ring-2 focus-visible:[&::-moz-range-thumb]:ring-white"
                        :style="{ '--track': `linear-gradient(to right, var(--ui-primary) ${percent}%, rgb(255 255 255 / 0.25) ${percent}%)` }"
                        @input="seek"
                        @keydown="step"
                    />
                    <span class="shrink-0 font-mono text-xs tabular-nums text-white/90 sm:text-sm">{{ clock(time) }} / {{ clock(duration) }}</span>
                </div>
            </div>

            <button
                ref="closeButton"
                type="button"
                :aria-label="$t('common.close')"
                class="absolute top-3 right-3 flex size-11 items-center justify-center rounded-full bg-black/60 text-white cursor-pointer transition hover:bg-primary hover:text-inverted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                @click="close"
            >
                <u-icon name="i-lucide-x" class="size-6" />
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { seekVideo } from '@/Support/video';

const props = defineProps({
    open: { type: Boolean, default: false },
    sources: { type: Array, required: true },
    poster: { type: String, default: null },
    width: { type: Number, required: true },
    height: { type: Number, required: true },
    // Already translated: the player that opens this has it.
    label: { type: String, required: true },
    // Where the small player was, so the large one carries on from there.
    startAt: { type: Number, default: 0 },
});

// The time it was closed at, so the small player can pick up from there.
const emit = defineEmits(['close']);

const video = ref(null);
const closeButton = ref(null);
const playing = ref(false);
const time = ref(0);
const duration = ref(0);
const percent = computed(() => (duration.value ? (time.value / duration.value) * 100 : 0));

function sync() {
    if (!video.value) return;
    time.value = video.value.currentTime;
    duration.value = Number.isFinite(video.value.duration) ? video.value.duration : 0;
}

function seek(event) {
    time.value = Number(event.target.value);
    seekVideo(video.value, time.value);
}

// Five seconds per arrow press; the range's own step is fine-grained for dragging.
function step(event) {
    const by = { ArrowLeft: -5, ArrowDown: -5, ArrowRight: 5, ArrowUp: 5 }[event.key];
    const to = { Home: 0, End: duration.value }[event.key];
    if (by === undefined && to === undefined) return;
    event.preventDefault();
    time.value = Math.min(duration.value, Math.max(0, to ?? time.value + by));
    seekVideo(video.value, time.value);
}

function clock(seconds) {
    const s = Math.max(0, Math.floor(seconds || 0));
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}

function start() {
    seekVideo(video.value, props.startAt, true);
}

function toggle() {
    const el = video.value;
    if (!el) return;
    if (el.paused) {
        el.play().catch((error) => console.error(error));
    } else {
        el.pause();
    }
}

function close() {
    emit('close', video.value?.currentTime ?? props.startAt);
}

function onKey(event) {
    if (event.key === 'Escape') close();
}

watch(() => props.open, async (open) => {
    document.documentElement.style.overflow = open ? 'hidden' : '';
    if (open) {
        window.addEventListener('keydown', onKey);
        await nextTick();
        closeButton.value?.focus();
    } else {
        window.removeEventListener('keydown', onKey);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    document.documentElement.style.overflow = '';
});
</script>
