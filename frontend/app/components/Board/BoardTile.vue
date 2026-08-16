<template>
  <div
    :class="tileClass"
    :title="title ?? `Tile ${displayNumber}`"
    @click="emit('click', position)"
  >
    <!-- Number -->
    <span class="absolute top-1 left-1 text-xs font-bold text-muted leading-none z-10">
      {{ displayNumber }}
    </span>

    <!-- Type icon -->
    <span v-if="typeIcon" class="absolute top-1 right-1 z-10" :class="typeColor">
      <u-icon :name="typeIcon" class="size-3" />
    </span>

    <!-- Completed overlay -->
    <span
      v-if="completed"
      class="absolute inset-0 flex items-center justify-center z-10 bg-green-500/20 rounded-[6px]"
    >
      <u-icon name="i-lucide-check-circle-2" class="size-5 text-green-500" />
    </span>

    <!-- Content -->
    <div class="absolute inset-0 flex flex-col items-center justify-center px-1 overflow-hidden">
      <img
        v-if="iconUrl"
        :src="iconUrl"
        :alt="title ?? ''"
        class="max-h-[48%] max-w-[75%] object-contain"
        loading="lazy"
      />

      <u-icon v-else-if="isEmpty" name="i-lucide-plus" class="size-5 text-muted/50" />

      <p v-if="title" class="text-sm text-center leading-tight line-clamp-2 text-muted mt-0.5">
        {{ title }}
      </p>
    </div>

    <!-- Player / team avatars -->
    <div v-if="players.length > 0" class="tile-avatar">
      <template v-for="player in players.slice(0, 4)" :key="player.id">
        <!-- Team token: show team icon (pixelated sprite style) -->
        <img
          v-if="player.isTeam && player.avatarUrl"
          :src="player.avatarUrl"
          :alt="player.discordUsername"
          class="size-5 object-contain rounded-sm ring-1 ring-background"
          style="image-rendering: pixelated"
        />
        <!-- Fallback team badge (no icon) -->
        <span
          v-else-if="player.isTeam"
          class="size-5 rounded-sm ring-1 ring-background bg-primary/20 flex items-center justify-center text-[7px] font-bold text-primary leading-none"
        >
          {{ player.discordUsername.slice(0, 2).toUpperCase() }}
        </span>
        <!-- Regular player avatar -->
        <u-avatar
          v-else
          :src="player.avatarUrl ?? undefined"
          :alt="player.discordUsername"
          size="2xs"
          class="ring-1 ring-background"
        />
      </template>

      <span v-if="players.length > 4" class="text-[8px] text-muted leading-none">
        +{{ players.length - 4 }}
      </span>
    </div>

    <!-- Edit mode pencil -->
    <div
      v-if="editMode"
      class="absolute inset-0 flex items-center justify-center bg-black/20 dark:bg-black/40 rounded-[6px] opacity-0 hover:opacity-100 transition-opacity z-20"
    >
      <u-icon name="i-lucide-pencil" class="size-5 text-white" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface PlayerAvatar {
  id: string;
  discordUsername: string;
  avatarUrl: string | null;
  isTeam?: boolean;
}

const props = withDefaults(
  defineProps<{
    position: number;
    displayNumber: number;
    title?: string | null;
    iconUrl?: string | null;
    type: 'NORMAL' | 'SNAKE' | 'LADDER';
    targetPosition?: number | null;
    completed?: boolean;
    isCurrent?: boolean;
    isPast?: boolean;
    players?: PlayerAvatar[];
    editMode?: boolean;
    isEmpty?: boolean;
  }>(),
  {
    title: null,
    iconUrl: null,
    targetPosition: null,
    completed: false,
    isCurrent: false,
    isPast: false,
    players: () => [],
    editMode: false,
    isEmpty: false,
  },
);

const emit = defineEmits<{ click: [position: number] }>();

const tileClass = computed(() => ({
  'board-tile': true,
  'board-tile--snake': props.type === 'SNAKE',
  'board-tile--ladder': props.type === 'LADDER',
  'board-tile--completed': props.completed,
  'board-tile--current': props.isCurrent,
  'board-tile--past': props.isPast && !props.isCurrent,
  'bg-muted/30': props.isEmpty,
  'bg-primary/5 dark:bg-primary/10': !props.isEmpty,
}));

const typeIcon = computed(() => {
  if (props.type === 'SNAKE') return 'i-lucide-move-down';
  if (props.type === 'LADDER') return 'i-lucide-move-up';
  return null;
});

const typeColor = computed(() => {
  if (props.type === 'SNAKE') return 'text-red-500';
  if (props.type === 'LADDER') return 'text-amber-500';
  return '';
});
</script>
