<template>
    <Head :title="liveBoard.title">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="py-12">
                <u-breadcrumb :items="breadcrumbs" class="mb-4" />

                <!-- u-page-header dropped entirely, not just its title slot:
                     it styles everything inside #title as a title, so the
                     description came out in the same uppercase display face
                     as the heading. A plain flex row instead, matching the
                     bingo and race pages so all three announce themselves the
                     same way. -->
                <!-- `flex-col sm:flex-row`, not `flex flex-wrap`: with a
                     `flex-1` first child and `justify-between`, the browser
                     decides whether to wrap using each child's HYPOTHETICAL
                     size — `flex-1`'s basis is 0, so the heading "wants" 0px
                     going into that decision, the action bar's own auto
                     width (its widest wrapped line) already fits beside it,
                     and the row never wraps at all. What actually happens at
                     375px: the action bar claims ~320 of 343px and the
                     heading is squeezed to ~5px, so its title renders one
                     character per line. Measured, not guessed — a real
                     event page, real viewport. `flex-col` below `sm`
                     sidesteps the whole heuristic: the two stack, full width
                     each, no wrap decision to get wrong. -->
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                    <event-type-heading
                        :event="liveBoard"
                        :can-edit="canEdit"
                        :viewing-as-admin="viewingAsAdmin"
                        :admin-edit-url="adminEditUrl"
                        :streaming="streaming"
                        :stale="stale"
                    >
                            <template #meta>
                                <span class="inline-flex items-center gap-1.5" :title="$t('admin.board_size_desc')">
                                    <u-icon name="i-lucide-grid-3x3" class="size-4 shrink-0" />
                                    {{ sizeLabel }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1.5"
                                    :title="liveBoard.mode === 'TEAM' ? $t('admin.board_mode_team_hint') : $t('admin.board_mode_solo_hint')"
                                >
                                    <u-icon :name="liveBoard.mode === 'TEAM' ? 'i-lucide-users' : 'i-lucide-user'" class="size-4 shrink-0" />
                                    {{ liveBoard.mode === 'TEAM' ? $t('admin.board_mode_team') : $t('admin.board_mode_solo') }}
                                </span>
                            </template>
                    </event-type-heading>

                    <!-- Wraps on a phone. `shrink-0` kept this bar at its full
                         natural width, so it never wrapped and simply ran
                         off the side — 772px of controls on a 375px screen
                         on the bingo card. It only needs to hold its ground
                         once there is room for it to. -->
                    <div class="flex flex-wrap gap-2 sm:shrink-0">
                            <!-- Two buttons that both sound like "see who
                                 else is here", so they say what they
                                 actually do: this one draws everyone's
                                 marker ON the board, the one beside it opens
                                 the list of who is taking part. -->
                            <u-button
                                v-if="otherPlayers.length > 0"
                                :color="showOtherPlayers ? 'primary' : 'neutral'"
                                :variant="showOtherPlayers ? 'subtle' : 'outline'"
                                size="sm"
                                icon="i-lucide-map-pin"
                                :label="$t('board.show_players')"
                                :title="$t('board.show_players_desc')"
                                @click="showOtherPlayers = !showOtherPlayers"
                            />
                            <u-button
                                :href="`/events/${liveBoard.id}/participants`"
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-users-round"
                                :label="$t('participants.open')"
                            />

                            <!-- Opening the board used to enrol you in it,
                                 which put every passer-by on the leaderboard
                                 at square one. Joining is a decision now, and
                                 this is where it is made. -->
                            <join-event-button v-if="joined || (!isPaused && !isEnded)" :event-id="liveBoard.id" :joined="joined" size="sm" />
                            <event-manage-menu v-if="canEdit" :items="manageItems" @select="onManage" />
                            <u-button
                                :href="`/events/${liveBoard.id}/leaderboard`"
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-trophy"
                                :label="$t('leaderboard.title')"
                            />
                    </div>
                </div>

                <u-card v-if="!hasTeam" class="mt-8">
                    <div class="flex flex-col items-center text-center gap-3 py-6">
                        <u-icon name="i-lucide-users" class="size-10 text-muted" />
                        <p class="font-semibold text-lg">{{ $t('board.no_team_title') }}</p>
                        <p class="text-sm text-muted max-w-md">{{ $t('board.no_team_desc') }}</p>
                        <u-button color="primary" :label="$t('board.go_to_teams')" href="/teams" />
                    </div>
                </u-card>

                <div v-else class="mt-8 flex flex-col lg:flex-row gap-8 items-start">
                    <div class="flex-1 w-full min-w-0 overflow-x-auto">
                        <!-- board-parchment/osrs-border ported as Tailwind utilities rather
                             than the old app's custom CSS classes (main.css) — same look,
                             but this codebase's convention is Tailwind-first custom CSS only
                             when Tailwind can't express it, and this can. -->
                        <div class="relative rounded-xl p-3 border-2 border-stone-400 dark:border-stone-600 bg-amber-50/90 dark:bg-stone-900" :class="minWidthClass">
                            <div :class="gridClass" class="grid gap-1">
                                <!-- Not gated on playerBoard existing — reaching this
                                     page at all already implies BoardAccess (see
                                     BoardController::show()'s access-gate redirect),
                                     and PlayerBoardController lazily creates the
                                     PlayerBoard row on first roll/toggle, same as the
                                     old getOrCreatePlayerBoard(). Gating the click on
                                     playerBoard already existing was a genuine
                                     dead-end bug: a brand-new player could never
                                     start, since nothing before this point ever
                                     creates that row. Caught by testing the actual
                                     cold-start flow through a real browser, not just
                                     curling a pre-seeded player's board. -->
                                <button
                                    v-for="tile in orderedTiles"
                                    :key="tile.position"
                                    type="button"
                                    class="aspect-square rounded-md relative cursor-pointer overflow-hidden hover:scale-105 transition-transform"
                                    :class="tileClasses(tile)"
                                    :title="tileTitle(tile) ?? trans('board.tile', { n: tile.position + 1 })"
                                    @click="handleTileClick(tile)"
                                >
                                    <!-- No z-index anywhere in here on purpose — see tileClasses()'s
                                         note. Paint order relies entirely on DOM order (later =
                                         on top), so nothing here can leak into the root stacking
                                         context and climb above a teleported modal. -->
                                    <!-- The one tile that gets a top label, not just the
                                         --current ring/tint app.css already applies: the
                                         ring reads as "highlighted" on a screenshot but
                                         doesn't say WHICH highlight it is next to the
                                         --past styling on tiles behind it. -->
                                    <div
                                        v-if="isCurrentTile(tile)"
                                        class="board-tile--here-label absolute top-0 inset-x-0 z-10 text-[6px] sm:text-[7px] font-bold uppercase tracking-wide text-center leading-tight py-0.5 truncate px-0.5"
                                    >
                                        {{ $t('board.you_are_here') }}
                                    </div>

                                    <div class="absolute inset-0 flex flex-col items-center justify-center px-1 overflow-hidden">
                                        <img
                                            v-if="tile.task?.icon_url"
                                            :src="tile.task.icon_url"
                                            :alt="tileTitle(tile)"
                                            class="max-h-[24%] max-w-[36%] object-contain shrink-0 mb-0.5"
                                            loading="lazy"
                                        />
                                        <u-icon v-else-if="isTileEmpty(tile)" name="i-lucide-plus" class="size-5 text-muted/50 shrink-0" />

                                        <p v-if="!isTileEmpty(tile)" class="w-full text-xs text-center leading-tight line-clamp-2 text-muted shrink-0">
                                            {{ tileTitle(tile) }}
                                        </p>
                                    </div>

                                    <span class="absolute top-1 left-1 text-xs font-bold text-muted leading-none">{{ tile.position + 1 }}</span>

                                    <span v-if="tile.type === 'SNAKE'" class="absolute top-1 right-1 text-error">
                                        <u-icon name="i-lucide-move-down" class="size-3" />
                                    </span>
                                    <span v-else-if="tile.type === 'LADDER'" class="absolute top-1 right-1 text-success">
                                        <u-icon name="i-lucide-move-up" class="size-3" />
                                    </span>

                                    <span v-if="isTileCompleted(tile)" class="absolute inset-0 flex items-center justify-center bg-success/20 rounded-md">
                                        <u-icon name="i-lucide-check-circle-2" class="size-5 text-success" />
                                    </span>

                                    <div v-if="playersOnTile(tile.position).length" class="absolute bottom-0.5 right-0.5 flex flex-wrap-reverse justify-end gap-0.5 max-w-[calc(100%-4px)]">
                                        <u-avatar
                                            v-for="p in playersOnTile(tile.position).slice(0, 3)"
                                            :key="p.id"
                                            :src="p.avatarUrl ?? undefined"
                                            :alt="p.name"
                                            size="3xs"
                                            class="ring-1 ring-default"
                                        />
                                        <span
                                            v-if="playersOnTile(tile.position).length > 3"
                                            class="text-[8px] leading-none bg-elevated rounded-full size-3.5 flex items-center justify-center ring-1 ring-default"
                                        >
                                            +{{ playersOnTile(tile.position).length - 3 }}
                                        </span>
                                    </div>
                                </button>
                            </div>

                            <!-- Percentage-based coordinates (viewBox 0 0 100 100),
                                 not pixel measurements like the old app's version —
                                 this grid is fluid-width (Tailwind grid-cols-N +
                                 aspect-square, no fixed tile size to measure), so a
                                 percentage overlay scales with it for free with no
                                 ResizeObserver. Approximates gap-1.5 as included in
                                 each cell's share rather than accounted separately;
                                 close enough at this gap size to not be visually off. -->
                            <svg
                                v-if="snakeLadderConnections.length"
                                class="board-svg-overlay"
                                viewBox="0 0 100 100"
                                preserveAspectRatio="none"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                            >
                                <defs>
                                    <marker id="arrow-snake" markerWidth="5" markerHeight="5" refX="4" refY="2.5" orient="auto">
                                        <path d="M0,0 L0,5 L5,2.5 z" fill="rgba(239,68,68,0.55)" />
                                    </marker>
                                    <marker id="arrow-ladder" markerWidth="5" markerHeight="5" refX="4" refY="2.5" orient="auto">
                                        <path d="M0,0 L0,5 L5,2.5 z" fill="rgba(34,197,94,0.55)" />
                                    </marker>
                                </defs>
                                <path
                                    v-for="(conn, i) in snakeLadderConnections"
                                    :key="i"
                                    :d="connectionPath(conn)"
                                    :stroke="conn.type === 'SNAKE' ? 'rgba(239,68,68,0.4)' : 'rgba(34,197,94,0.4)'"
                                    stroke-width="0.5"
                                    stroke-dasharray="2,1.2"
                                    :marker-end="conn.type === 'SNAKE' ? 'url(#arrow-snake)' : 'url(#arrow-ladder)'"
                                    fill="none"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="w-full lg:w-64 shrink-0 flex flex-col gap-4">
                        <!-- The thing to do comes first. This card used to sit
                             below "Roll the dice", whose own disabled-state
                             copy says "Mark it complete above" — a lie by
                             position, since the task card was the one BELOW
                             it. Reported directly. Swapped order rather than
                             reworded the copy: the task is what a player
                             actually acts on next, the dice are the reward
                             for finishing it (see the comment on that card,
                             now following this one), so "above" is what the
                             text should have said all along, not "below". -->
                        <u-card v-if="currentTile">
                            <template #header>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold">{{ $t('board.your_task') }}</span>
                                    <!-- A proper button, not a small icon
                                         tucked beside the title — a wiki link
                                         is the one external action on this
                                         card and deserves to read as one. -->
                                    <u-button
                                        v-if="currentTile.task?.wiki_url"
                                        :href="currentTile.task.wiki_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        size="xs"
                                        color="neutral"
                                        variant="outline"
                                        trailing-icon="i-lucide-external-link"
                                        :label="$t('tile_editor.open_wiki_page')"
                                    />
                                </div>
                            </template>
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="currentTile.task?.icon_url"
                                    :src="currentTile.task.icon_url"
                                    :alt="currentTileTitle"
                                    class="size-10 object-contain shrink-0"
                                />
                                <u-icon v-else name="i-lucide-scroll-text" class="size-10 text-muted shrink-0" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm">{{ currentTileTitle }}</p>
                                    <p class="text-xs text-muted mt-0.5">{{ $t('board.tile', { n: currentTile.position + 1 }) }}</p>
                                    <p v-if="currentTile.task?.description" class="text-xs text-muted mt-1 leading-relaxed">
                                        {{ currentTile.task.description }}
                                    </p>
                                    <u-badge v-if="currentTile.type !== 'NORMAL'" :color="currentTile.type === 'SNAKE' ? 'error' : 'success'" size="sm" class="mt-1">
                                        {{ currentTile.type === 'SNAKE' ? '🐍' : '🪜' }} → {{ $t('board.tile', { n: (currentTile.target_position ?? 0) + 1 }) }}
                                    </u-badge>
                                </div>
                            </div>

                            <!-- Joining before the start date creates a
                                 PlayerBoard at tile 0 (EventParticipationService
                                 ::join() doesn't gate on isUpcoming, only the
                                 dice do) — so this card, and the tick button
                                 on it, is reachable the same way the dice
                                 were. Same fix. -->
                            <div v-if="!isPaused && !isEnded && !isUpcoming" class="mt-3">
                                <!-- Same trust problem bingo solved, applied
                                     here: on a board that requires approval a
                                     click opens the claim dialog (proof, then
                                     wait on a host) instead of scoring itself.
                                     See docs/backlog.md, "Snakes & Ladders
                                     task tiles have no claim/approve flow". -->
                                <template v-if="requiresApproval">
                                    <u-button
                                        v-if="!currentClaim"
                                        color="success"
                                        variant="solid"
                                        size="sm"
                                        icon="i-lucide-check"
                                        block
                                        :label="$t('board.complete_tile')"
                                        @click="showClaimModal = true"
                                    />
                                    <div v-else class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm">
                                            <u-icon :name="claimStatusIcon" class="size-4 shrink-0" :class="claimStatusClass" />
                                            <span :class="claimStatusClass">{{ $t(`board.status_${currentClaim.status.toLowerCase()}`) }}</span>
                                        </div>
                                        <u-button
                                            size="sm"
                                            variant="outline"
                                            color="neutral"
                                            block
                                            :label="$t('board.view_claim')"
                                            @click="showClaimModal = true"
                                        />
                                    </div>
                                </template>
                                <template v-else>
                                    <u-button
                                        v-if="!currentTileCompleted"
                                        color="success"
                                        variant="solid"
                                        size="sm"
                                        icon="i-lucide-check"
                                        block
                                        :label="$t('board.complete_tile')"
                                        @click="toggleTile(currentTile)"
                                    />
                                    <u-button
                                        v-else
                                        color="neutral"
                                        variant="outline"
                                        size="sm"
                                        icon="i-lucide-x"
                                        block
                                        :label="$t('board.uncomplete_tile')"
                                        @click="toggleTile(currentTile)"
                                    />
                                </template>
                            </div>
                        </u-card>

                        <!-- Ported from Sidebar.vue: the dice roller only appears once the
                             CURRENT tile is marked complete — rolling isn't always available,
                             it's the reward for finishing what you're standing on. This isn't
                             enforced server-side either (old or new backend) — it's a UI pace,
                             not a hard rule — but the UI gate itself was missing entirely here. -->
                        <!-- Shown whether or not you may roll yet, and saying
                             which. Hiding the card until the current tile was
                             ticked off made the board look like it was
                             missing its main control — reported as "where is
                             the dice, snakes and ladders does not work at
                             all", which is the correct reading of a page with
                             no way to play on it. The gate is a pace, not a
                             secret. -->
                        <u-card v-if="playerBoard">
                            <template #header>
                                <span class="font-semibold">{{ $t('board.roll_dice') }}</span>
                            </template>

                            <dice-roller
                                v-if="canRoll && !isPaused && !isEnded && !isUpcoming"
                                :rolling="rolling"
                                :last-roll="lastRoll"
                                :rolls-today="playerBoard?.dice_rolls_today ?? 0"
                                :roll-limit="liveBoard.dice_roll_limit"
                                @roll="roll"
                            />

                            <!-- Paused/ended/upcoming all refuse the roll
                                 server-side either way; taking the dice away
                                 is so that the refusal is not the way anyone
                                 finds out. Upcoming used to be missing from
                                 both sides of this — the dice showed and
                                 worked on a board dated to start next month,
                                 reported directly from exactly that page. -->
                            <p v-else class="text-sm text-muted">
                                {{ isEnded ? $t('events.ended_notice') : (isPaused ? $t('events.paused_notice') : (isUpcoming ? $t('events.not_started') : (currentClaim?.status === 'PENDING' ? $t('board.roll_awaiting_review') : $t('board.roll_needs_current_tile')))) }}
                            </p>
                        </u-card>

                        <!-- No board of your own yet. Rolling still creates
                             one — playing is joining — so the dice stay, with
                             the deliberate way in above them.

                             Joining itself stays open while upcoming — that's
                             signing up ahead of the start, which is normal
                             and is what the header's own join button already
                             allows (EventParticipationService::join() has
                             never checked isUpcoming, only isPaused). Only
                             the dice — actually playing — wait for the start
                             date, same as everywhere else this pass touched. -->
                        <u-card v-if="!playerBoard">
                            <p class="text-sm text-muted">
                                {{ isEnded ? $t('events.ended_notice') : (isPaused ? $t('events.paused_notice') : (isUpcoming ? $t('events.not_started') : (joined ? $t('board.get_started_desc') : $t('events.join_hint')))) }}
                            </p>
                            <div v-if="!isPaused && !isEnded" class="mt-3 flex flex-col gap-3">
                                <join-event-button v-if="!joined" :event-id="liveBoard.id" :joined="false" />
                                <dice-roller v-if="!isUpcoming" :rolling="rolling" :last-roll="lastRoll" :rolls-today="0" :roll-limit="liveBoard.dice_roll_limit" @roll="roll" />
                            </div>
                        </u-card>

                        <u-card v-if="clickedTile && clickedTile.position !== playerBoard?.current_position">
                            <template #header>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold">{{ $t('board.tile_info') }}</span>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <u-button
                                            v-if="clickedTile.task?.wiki_url"
                                            :href="clickedTile.task.wiki_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            size="xs"
                                            color="neutral"
                                            variant="outline"
                                            trailing-icon="i-lucide-external-link"
                                            :label="$t('tile_editor.open_wiki_page')"
                                        />
                                        <u-button size="xs" variant="ghost" color="neutral" icon="i-lucide-x" :aria-label="$t('common.close')" @click="clickedTile = null" />
                                    </div>
                                </div>
                            </template>
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="clickedTile.task?.icon_url"
                                    :src="clickedTile.task.icon_url"
                                    :alt="clickedTileTitle"
                                    class="size-10 object-contain shrink-0"
                                />
                                <u-icon v-else name="i-lucide-scroll-text" class="size-10 text-muted shrink-0" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm">{{ clickedTileTitle }}</p>
                                    <p class="text-xs text-muted mt-0.5">{{ $t('board.tile', { n: clickedTile.position + 1 }) }}</p>
                                    <p v-if="clickedTile.task?.description" class="text-xs text-muted mt-1 leading-relaxed">
                                        {{ clickedTile.task.description }}
                                    </p>
                                    <u-badge v-if="clickedTile.type !== 'NORMAL'" :color="clickedTile.type === 'SNAKE' ? 'error' : 'success'" size="sm" class="mt-1">
                                        {{ clickedTile.type === 'SNAKE' ? '🐍' : '🪜' }} → {{ $t('board.tile', { n: (clickedTile.target_position ?? 0) + 1 }) }}
                                    </u-badge>
                                </div>
                            </div>
                        </u-card>

                        <u-card>
                            <template #header>
                                <span class="font-semibold">{{ $t('admin.editors') }}</span>
                            </template>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="author in liveBoard.authors" :key="author.id" class="flex items-center gap-1.5">
                                    <u-avatar :src="author.user.avatar_url ?? undefined" :alt="author.user.nickname || author.user.discord_username" size="xs" />
                                    <span class="text-xs">{{ author.user.nickname || author.user.discord_username }}</span>
                                </div>
                            </div>
                        </u-card>

                        <u-card>
                            <template #header>
                                <span class="font-semibold">{{ $t('boards.meta') }}</span>
                            </template>
                            <div class="flex flex-wrap gap-2">
                                <u-badge color="neutral" variant="subtle" icon="i-lucide-calendar" :title="$t('events.meta_dates_hint')">
                                    {{ formatDate(liveBoard.start_date) }} – {{ formatDate(liveBoard.end_date) }}
                                </u-badge>
                                <u-badge color="neutral" variant="subtle" icon="i-lucide-grid-3x3" :title="$t('admin.board_size_desc')">
                                    {{ formatBoardSize(liveBoard.size) }}
                                </u-badge>
                                <u-badge v-if="liveBoard.dice_roll_limit" color="neutral" variant="subtle" icon="i-lucide-dice-6" :title="$t('board.roll_limit_hint')">
                                    {{ $t('boards.roll_limit', { limit: liveBoard.dice_roll_limit }) }}
                                </u-badge>
                                <u-badge v-else color="neutral" variant="subtle" icon="i-lucide-dice-6" :title="$t('board.roll_limit_hint')">
                                    {{ $t('dice.unlimited') }}
                                </u-badge>
                                <u-badge
                                    :color="liveBoard.mode === 'TEAM' ? 'warning' : 'neutral'"
                                    variant="subtle"
                                    icon="i-lucide-users-round"
                                    :title="liveBoard.mode === 'TEAM' ? $t('admin.board_mode_team_hint') : $t('admin.board_mode_solo_hint')"
                                >
                                    {{ liveBoard.mode === 'TEAM' ? $t('board.mode_team') : $t('board.mode_solo') }}
                                </u-badge>
                                <u-badge
                                    color="neutral"
                                    variant="subtle"
                                    :icon="requiresApproval ? 'i-lucide-gavel' : 'i-lucide-zap'"
                                    :title="requiresApproval ? $t('board.info_reviewed') : $t('board.info_instant')"
                                >
                                    {{ requiresApproval ? $t('board.info_reviewed') : $t('board.info_instant') }}
                                </u-badge>
                            </div>

                            <!-- Same placement bingo uses: the fact that
                                 claims wait for a host sits right beside the
                                 button that acts on it. -->
                            <u-button
                                v-if="canEdit && requiresApproval"
                                :color="livePending.length ? 'warning' : 'neutral'"
                                variant="outline"
                                size="xs"
                                icon="i-lucide-gavel"
                                class="mt-3"
                                :label="livePending.length
                                    ? $t('bingo.review_pending_count', { count: livePending.length })
                                    : $t('bingo.review_nothing_waiting')"
                                @click="showReviewModal = true"
                            />
                        </u-card>

                        <u-card v-if="livePlayers.length">
                            <template #header>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">{{ $t('leaderboard.title') }}</span>
                                    <u-button :href="`/events/${liveBoard.id}/leaderboard`" variant="ghost" size="xs" color="neutral" trailing-icon="i-lucide-external-link" />
                                </div>
                            </template>
                            <div class="flex flex-col gap-1">
                                <div
                                    v-for="(p, i) in livePlayers.slice(0, 5)"
                                    :key="p.id"
                                    class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm"
                                    :class="isMyPlayerRow(p) ? 'bg-primary/10 ring-1 ring-primary/30' : ''"
                                >
                                    <span class="w-4 text-center text-xs font-bold shrink-0" :class="i < 3 ? 'text-primary' : 'text-muted'">{{ i + 1 }}</span>
                                    <img
                                        v-if="p.team?.icon_url"
                                        :src="p.team.icon_url"
                                        :alt="p.team.name"
                                        class="size-6 object-contain shrink-0"
                                        style="image-rendering: pixelated"
                                    />
                                    <span
                                        v-else-if="p.team"
                                        class="size-6 rounded shrink-0 bg-primary/20 flex items-center justify-center text-[8px] font-bold text-primary"
                                    >
                                        {{ p.team.name.slice(0, 2).toUpperCase() }}
                                    </span>
                                    <u-avatar v-else :src="p.user?.avatar_url ?? undefined" :alt="p.team?.name ?? p.user?.nickname ?? p.user?.discord_username ?? $t('common.deleted_user')" size="xs" class="shrink-0" />
                                    <span class="flex-1 min-w-0 truncate font-medium">{{ p.team?.name ?? p.user?.nickname ?? p.user?.discord_username ?? $t('common.deleted_user') }}</span>
                                    <span class="text-xs text-muted shrink-0">#{{ p.current_position + 1 }}</span>
                                    <span
                                        class="text-xs font-semibold w-10 text-right shrink-0"
                                        :class="p.pathHasSnake ? 'text-error' : p.pathHasLadder ? 'text-success' : 'text-muted'"
                                    >
                                        {{ p.tilesRemaining }}🔲
                                    </span>
                                </div>
                            </div>
                            <div v-if="livePlayers.length > 5" class="mt-1 text-center">
                                <u-button
                                    :href="`/events/${liveBoard.id}/leaderboard`"
                                    variant="ghost"
                                    size="xs"
                                    color="neutral"
                                    :label="$t('leaderboard.show_all', { count: livePlayers.length })"
                                />
                            </div>
                        </u-card>
                    </div>
                </div>
            </u-container>
        </u-page>

        <u-modal v-model:open="showCompleted" :title="$t('board.completed')">
            <template #body>
                <div class="text-center py-6">
                    <p class="text-6xl mb-4">🎉</p>
                    <p class="text-muted">{{ $t('board.completed_desc') }}</p>
                </div>
            </template>
            <template #footer>
                <u-button block color="primary" :label="$t('common.close')" @click="showCompleted = false" />
            </template>
        </u-modal>

        <client-only>
            <board-settings-modal v-model:open="showSettingsModal" :board="liveBoard" :webhook-url="webhookUrl" />
            <tile-list-editor
                v-model:open="showTileList"
                :event-id="liveBoard.id"
                type="SNAKES_LADDERS"
                :items="tiles"
                :total="tileCount"
            />
            <tile-edit-modal
                v-if="editingTile"
                :open="editingTile !== null"
                :event-id="liveBoard.id"
                :position="editingTile.position"
                :tile-count="tiles.length"
                :tile="editingTile.id ? editingTile : null"
                @update:open="(v) => !v && (editingTile = null)"
            />
            <tile-claim-modal
                v-if="currentTile"
                v-model:open="showClaimModal"
                :event-id="liveBoard.id"
                :tile="currentTile"
                :tile-title="currentTileTitle"
                :claim="currentClaim"
            />
            <tile-review-modal v-model:open="showReviewModal" :event-id="liveBoard.id" :claims="livePending" />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import EventTypeHeading from '@/Components/EventTypeHeading.vue';
import JoinEventButton from '@/Components/JoinEventButton.vue';
import DiceRoller from '@/Components/DiceRoller.vue';
import EventManageMenu from '@/Components/EventManageMenu.vue';
import { BOARD_TILE_COUNT, BOARD_MIN_WIDTH, formatBoardSize, formatDate, eventStatus } from '@/Support/board';
import { useEventStream } from '@/Composables/useEventStream';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));
const TileListEditor = defineAsyncComponent(() => import('@/Components/TileListEditor.vue'));
const TileEditModal = defineAsyncComponent(() => import('@/Components/TileEditModal.vue'));
const TileClaimModal = defineAsyncComponent(() => import('@/Components/TileClaimModal.vue'));
const TileReviewModal = defineAsyncComponent(() => import('@/Components/TileReviewModal.vue'));

const props = defineProps({
    board: { type: Object, required: true },
    tiles: { type: Array, required: true },
    playerBoard: { type: Object, default: null },
    players: { type: Array, default: () => [] },
    hasTeam: { type: Boolean, default: true },
    canEdit: { type: Boolean, default: false },
    // True only when a site admin is reading a private event they were never
    // invited to — the heading says so rather than letting it be silent.
    viewingAsAdmin: { type: Boolean, default: false },
    adminEditUrl: { type: String, default: null },
    // Editors only — see BoardSettingsModal's own note on why this is not
    // part of the event payload.
    webhookUrl: { type: String, default: null },
    joined: { type: Boolean, default: false },
    // The host's review queue — see BoardReviewService::pendingQueue(). Empty
    // for anyone else, so asking for it costs a player nothing.
    pending: { type: Array, default: () => [] },
});

/**
 * The event as it is now: the prop for the first paint, then whatever the
 * channel sends. Built from one place on the server (App\Support\EventCard)
 * so the page cannot tell which one it is looking at — a host moving the end
 * date or renaming the event has to reach everyone playing on it.
 */
const liveBoard = ref({ ...props.board });
watch(() => props.board, (value) => (liveBoard.value = { ...value }));

// The grid's own shape, for the heading's meta line. Via the shared helper
// rather than a second copy of the same lookup.
const sizeLabel = computed(() => formatBoardSize(liveBoard.value.size));

// Computed, not a static array — the title comes from the live channel too,
// and a host renaming the event mid-visit should not leave a stale trail.
const breadcrumbs = computed(() => [
    { label: trans('nav.home'), icon: 'i-lucide-house', href: '/' },
    { label: trans('nav.events'), href: '/events' },
    { label: liveBoard.value.title },
]);

// Everyone's positions, seeded from the render and then kept current by the
// board's own channel — a roll moves one player and everybody watching should
// see it, the same as a bingo square being ticked.
const livePlayers = ref([...props.players]);
const liveTiles = ref([...props.tiles]);
const livePending = ref([...props.pending]);
/**
 * A copy of a prop only stays right if something copies it again.
 *
 * The channel was the only thing that did, which made your OWN actions the
 * slowest ones on the page: the server sends fresh props straight back, and
 * this list kept the numbers from before until the stream got round to
 * saying so. Found on the bingo card — approving a claim left the standings
 * reading "nobody has marked a square yet" next to a counter saying 1 of 16.
 */
watch(() => props.players, (value) => (livePlayers.value = [...value]));
watch(() => props.tiles, (value) => (liveTiles.value = [...value]));
watch(() => props.pending, (value) => (livePending.value = [...value]));

/**
 * Claim state, as the server last saw it — same pattern Bingo.vue's
 * applyClaimsVersion() uses, for the same reason: what a host decided about
 * YOUR claim is yours, and cannot ride a channel every viewer shares. Held
 * from the first message rather than from the page, so a reconnect (every
 * 45 seconds by design) never counts as a change.
 */
let claimsVersion = null;

function applyClaimsVersion(version) {
    if (claimsVersion === null) {
        claimsVersion = version;

        return;
    }

    if (version === claimsVersion) return;

    claimsVersion = version;

    // `pending` is the host's review queue; it comes back empty for anyone
    // else, so asking for it costs a player nothing.
    router.reload({ only: ['playerBoard', 'pending'] });
}

// Destructured now, because the status dot in the heading reports the
// connection — see EventTypeHeading. This page never showed a live indicator
// at all, so a board quietly kept itself up to date and never said so.
const { streaming, stale } = useEventStream({
    url: () => `/events/${liveBoard.value.id}/stream`,
    event: 'players',
    onMessage: (payload) => {
        livePlayers.value = payload.players;

        // Merged, not replaced, so a field the channel does not know about
        // survives the first push.
        if (payload.event) liveBoard.value = { ...liveBoard.value, ...payload.event };

        // The board itself, so a host putting a task on a tile or moving a
        // ladder reaches everyone looking at it — the same way a bingo card's
        // squares always did. Guarded because an older SSR bundle may still
        // be streaming payloads without them.
        if (payload.tiles) liveTiles.value = payload.tiles;

        // What a host decided about a claim — approved, rejected — only
        // when it actually changed, rather than on every push.
        if (payload.claims_version) applyClaimsVersion(payload.claims_version);
    },
});


const showSettingsModal = ref(false);
const editingTile = ref(null);
const showClaimModal = ref(false);
const showReviewModal = ref(false);

// Opened automatically on ?setup=tiles — the redirect a freshly created
// event arrives on. onMounted because `location` does not exist during SSR.
const showTileList = ref(false);
onMounted(() => {
    if (props.canEdit && new URLSearchParams(window.location.search).get('setup') === 'tiles') {
        showTileList.value = true;
    }
});
const editMode = ref(false);
const rolling = ref(false);
const lastRoll = ref(null);
const showCompleted = ref(false);
const showOtherPlayers = ref(false);
// The tile the player last clicked (for inspection), separate from
// currentTile (where they actually are). Ported from the old Sidebar.vue's
// "Selected tile" panel — clicking a tile previews it, it does not toggle
// completion; only the current-position tile's own button in the sidebar
// can mark it complete, matching the old app (you complete tiles you've
// actually reached, not any tile you click).
const clickedTile = ref(null);

// Fed by PlayerBoardController::roll()'s 'last-roll' session flash (kept
// separate from the already-formatted 'board-save' toast text — see
// HandleInertiaRequests) — DiceRoller needs the raw number to pick a face.
const inertiaPage = usePage();
watch(
    () => inertiaPage.props?.flash?.lastRoll,
    (value) => {
        if (value !== null && value !== undefined) lastRoll.value = value;
    },
);

const GRID_CLASSES = { SIZE_5X5: 'grid-cols-5', SIZE_7X7: 'grid-cols-7', SIZE_9X9: 'grid-cols-9' };
const gridClass = computed(() => GRID_CLASSES[liveBoard.value.size] ?? GRID_CLASSES.SIZE_7X7);
const minWidthClass = computed(() => BOARD_MIN_WIDTH[liveBoard.value.size] ?? BOARD_MIN_WIDTH.SIZE_7X7);

const cols = computed(() => ({ SIZE_5X5: 5, SIZE_7X7: 7, SIZE_9X9: 9 }[liveBoard.value.size] ?? 7));
const tileCount = computed(() => BOARD_TILE_COUNT[liveBoard.value.size] ?? 49);

// Ported from the old Sidebar.vue's "your task" panel — shows the task
// (icon/title/description) for whichever real tile the player is currently
// standing on, plus the complete/uncomplete action. Distinct from clicking
// a tile directly in the grid: this is the always-visible "what do I do
// next" panel the old app had and this page was missing entirely.
const currentTile = computed(() => {
    if (!props.playerBoard) return null;
    return liveTiles.value.find((t) => t.position === props.playerBoard.current_position) ?? null;
});
const currentTileTitle = computed(() => currentTile.value?.title_override ?? currentTile.value?.task?.title ?? trans('tile_editor.no_task'));
const currentTileCompleted = computed(() => !!currentTile.value && (props.playerBoard?.completedTileIds.includes(currentTile.value.id) ?? false));

// Whether this board makes you prove it — same setting bingo cards carry,
// read off the live event so a host flipping it mid-event reaches an open
// board through the stream.
const requiresApproval = computed(() => Boolean(liveBoard.value.requires_approval));

// This player's own claim on the tile they are standing on, whatever its
// state — see PlayerBoard.claims (keyed by tile id) in BoardController::show.
const currentClaim = computed(() => (
    currentTile.value ? (props.playerBoard?.claims?.[currentTile.value.id] ?? null) : null
));

const CLAIM_STATUS_ICON = { PENDING: 'i-lucide-clock', APPROVED: 'i-lucide-circle-check', REJECTED: 'i-lucide-circle-x' };
const CLAIM_STATUS_CLASS = { PENDING: 'text-warning', APPROVED: 'text-success', REJECTED: 'text-error' };
const claimStatusIcon = computed(() => CLAIM_STATUS_ICON[currentClaim.value?.status] ?? 'i-lucide-circle-dot');
const claimStatusClass = computed(() => CLAIM_STATUS_CLASS[currentClaim.value?.status] ?? 'text-muted');

/**
 * Whether the square you are standing on actually asks for anything.
 *
 * Tiles are created when somebody edits them, so most squares on a board have
 * no row at all — and a square with no task has nothing to complete.
 */
const currentTileHasTask = computed(() => Boolean(
    currentTile.value && (currentTile.value.task_id || currentTile.value.title_override),
));

/**
 * Rolling is the reward for finishing what you are standing on — but only
 * when there is something to finish.
 *
 * Gating on `currentTileCompleted` alone meant landing on an empty square
 * left you with no dice, no "mark as complete" button, and no way forward:
 * the board simply stopped. Reported as joining a board and seeing no way to
 * begin, which is what happens on tile 1 of any board whose first square is
 * empty — that is to say, nearly all of them.
 */
const canRoll = computed(() => !currentTileHasTask.value || currentTileCompleted.value);

// Read off the live event rather than the initial prop, so a host pausing
// mid-game reaches an open board through the stream — the fingerprint carries
// paused_at (see SignalsEventEdits) and this is what it lands on.
const isPaused = computed(() => Boolean(liveBoard.value.paused_at));

// Same "ended outranks paused" rule EventTypeHeading already uses for its
// status badge (eventStatus() in Support/board.js) — the badge said "Ended"
// on this exact page while the dice/complete-tile controls stayed live,
// because they only ever checked isPaused. Ended events must refuse both.
const isEnded = computed(() => eventStatus(liveBoard.value) === 'ended');

// Same class of gap as isEnded's own comment above, a second time: the badge
// already says "Upcoming" for a board dated to start next month, but the
// dice and the tick-tile button worked anyway, because neither checked the
// start date at all. Reported directly from exactly that state.
const isUpcoming = computed(() => eventStatus(liveBoard.value) === 'upcoming');

/** The host's tools, in one menu — see EventManageMenu. */
const manageItems = computed(() => [
    { key: 'edit', label: editMode.value ? trans('bingo.editing_tiles') : trans('bingo.edit_tiles'), icon: 'i-lucide-grid-2x2-plus', active: editMode.value },
    { key: 'tiles', label: trans('tile_list.open'), icon: 'i-lucide-list-checks' },
    { key: 'settings', label: trans('board.event_settings'), icon: 'i-lucide-settings' },
    ...(requiresApproval.value ? [{ key: 'review', label: trans('bingo.review_queue'), icon: 'i-lucide-gavel', badge: livePending.value.length }] : []),
]);

function onManage(key) {
    if (key === 'edit') editMode.value = ! editMode.value;
    if (key === 'tiles') showTileList.value = true;
    if (key === 'settings') showSettingsModal.value = true;
    if (key === 'review') showReviewModal.value = true;
}

const clickedTileTitle = computed(() => clickedTile.value?.title_override ?? clickedTile.value?.task?.title ?? trans('tile_editor.no_task'));

// Ported from useBoardPage's otherPlayerStates/boardPlayerStates split — "me"
// is whichever row matches my user (SOLO) or my team (TEAM); everyone else
// only renders on the grid once the "show other players" toggle is on.
const authUser = computed(() => inertiaPage.props.auth?.user ?? null);
const isMyPlayerRow = (p) => (liveBoard.value.mode === 'TEAM' ? p.team_id === props.playerBoard?.team_id : p.user_id === authUser.value?.id);
const otherPlayers = computed(() => livePlayers.value.filter((p) => !isMyPlayerRow(p)));
// Reads the live list, so a roll by anyone moves their avatar on every
// open board rather than only after a refresh.
const visiblePlayers = computed(() => livePlayers.value.filter((p) => isMyPlayerRow(p) || showOtherPlayers.value));

function playersOnTile(position) {
    return visiblePlayers.value
        .filter((p) => p.current_position === position)
        .map((p) => ({
            id: p.team?.id ?? p.user_id,
            // Falls through to the deleted-player label rather than a hardcoded
            // 'Player': a board keeps the space somebody occupied after
            // they close their account, and an unlabelled row reads as a bug.
            name: p.team?.name ?? p.user?.nickname ?? p.user?.discord_username ?? trans('common.deleted_user'),
            avatarUrl: p.team?.icon_url ?? p.user?.avatar_url ?? null,
        }));
}

// Ported from the old GameBoard.vue's orderedTiles: a board doesn't get a
// full grid of Tile rows created on creation — only positions someone has
// actually configured exist. Missing positions render as NORMAL placeholder
// tiles (matching the old "empty-{position}" convention) so the grid is
// always complete regardless of how many tiles have been set up. Displayed
// in boustrophedon (snake) order — row 0 at the bottom-left, alternating
// direction per row — the actual Snakes & Ladders board numbering, not a
// plain reading order.
const orderedTiles = computed(() => {
    const n = cols.value;
    const tileMap = new Map(liveTiles.value.map((t) => [t.position, t]));
    const result = [];

    for (let row = n - 1; row >= 0; row--) {
        const leftToRight = row % 2 === 0;
        for (let col = 0; col < n; col++) {
            const adjustedCol = leftToRight ? col : n - 1 - col;
            const position = row * n + adjustedCol;
            if (position >= tileCount.value) continue;
            result.push(tileMap.get(position) ?? { id: null, position, type: 'NORMAL', target_position: null, task: null, title_override: null });
        }
    }

    return result;
});

// Ported from the old Board/SnakeLadder.vue, converted from pixel to
// percentage coordinates — see the template's comment on why.
const snakeLadderConnections = computed(() =>
    // The live copy, so a ladder moved mid-event redraws on every open board
    // rather than only after a reload.
    liveTiles.value
        .filter((t) => (t.type === 'SNAKE' || t.type === 'LADDER') && t.target_position !== null)
        .map((t) => ({ from: t.position, to: t.target_position, type: t.type })),
);

function tileCenterPercent(position) {
    const n = cols.value;
    const row = Math.floor(position / n);
    const col = position % n;
    const adjustedCol = row % 2 === 0 ? col : n - 1 - col;
    const visualRow = n - 1 - row;
    const cellSize = 100 / n;

    return { x: adjustedCol * cellSize + cellSize / 2, y: visualRow * cellSize + cellSize / 2 };
}

// Gentle quadratic bezier — same 0.18 curvature the old app used to keep
// lines subtle and clearly directed without cluttering the board.
function connectionPath(conn) {
    const start = tileCenterPercent(conn.from);
    const end = tileCenterPercent(conn.to);
    const dx = end.x - start.x;
    const dy = end.y - start.y;
    const cx = (start.x + end.x) / 2 - dy * 0.18;
    const cy = (start.y + end.y) / 2 + dx * 0.18;

    return `M ${start.x} ${start.y} Q ${cx} ${cy} ${end.x} ${end.y}`;
}

// Ported 1:1 from the old BoardTile.vue's tileClass computed: an unconfigured
// tile (no task, no title_override — includes the "not created yet"
// placeholder from orderedTiles) is "empty"; everything else gets a light
// primary tint. Outline-only (no background fill) for snake/ladder/current —
// .board-tile--snake/--ladder/--current in resources/css/app.css — plus
// --past for grayed-out already-passed tiles. This replaces an earlier
// version that improvised its own bg-tint palette instead of using the CSS
// classes that were already sitting there unused.
function isTileEmpty(tile) {
    return !tile.task && !tile.title_override;
}

function tileTitle(tile) {
    return tile.title_override ?? tile.task?.title ?? null;
}

function isTileCompleted(tile) {
    return props.playerBoard?.completedTileIds.includes(tile.id) ?? false;
}

function isCurrentTile(tile) {
    const current = props.playerBoard?.current_position;

    return current !== undefined && current !== null && tile.position === current;
}

function tileClasses(tile) {
    const current = props.playerBoard?.current_position;
    const classes = ['board-tile', isTileEmpty(tile) ? 'bg-muted/30' : 'bg-primary/5 dark:bg-primary/10'];

    if (tile.type === 'SNAKE') classes.push('board-tile--snake');
    if (tile.type === 'LADDER') classes.push('board-tile--ladder');

    if (current !== undefined && current !== null && tile.id !== null) {
        if (tile.position === current) classes.push('board-tile--current');
        else if (tile.position < current) classes.push('board-tile--past');
    }

    if (isTileCompleted(tile)) classes.push('board-tile--completed');

    return classes;
}

// Ported from useBoardPage's handleTileClick: in edit mode a click opens the
// tile editor (including on an unconfigured tile — that's how you configure
// one); otherwise it selects the tile for the sidebar's "Selected tile"
// preview. It does NOT toggle completion — see the clickedTile declaration
// above for why that's deliberate, not a missing feature.
function handleTileClick(tile) {
    if (editMode.value) {
        if (!props.canEdit) return;
        editingTile.value = tile;
        return;
    }
    clickedTile.value = tile;
}

function roll() {
    rolling.value = true;
    router.post(`/events/${liveBoard.value.id}/roll`, {}, { preserveScroll: true, onFinish: () => (rolling.value = false) });
}

// Ported from the old useBoardPage's onCompleteTile: completing the tile at
// the board's last position ends the run.
//
// Called "bingo" until 2026-08-20, which was a misnomer waiting to collide:
// this fires on finishing a Snakes & Ladders board, and BINGO is becoming
// a separate event type with its own line/full-board rules (ROADMAP phase 5).
function toggleTile(tile) {
    const wasCompleted = props.playerBoard?.completedTileIds.includes(tile.id) ?? false;
    const finishesBoard = !wasCompleted && tile.position === tileCount.value - 1;

    router.post(`/events/${liveBoard.value.id}/tiles/${tile.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (finishesBoard) showCompleted.value = true;
        },
    });
}
</script>
