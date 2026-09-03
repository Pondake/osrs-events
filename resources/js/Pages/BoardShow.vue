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
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                    <event-type-heading
                        :event="liveBoard"
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
                         once there is room for it to.
                         `lg`, not `sm`: five buttons need about 470px, so from
                         `sm` up they sat beside the title and took nearly all
                         of it — on a 700px window the heading was squeezed to
                         one letter per line. There is only room for both from
                         `lg`, which is also where the title stops needing the
                         full width. -->
                    <div class="flex flex-wrap gap-2 lg:shrink-0">
                            <!-- Two buttons that both sound like "see who else is
                                 here", so each says what it does: this one draws
                                 the other players' markers on the board, the next
                                 opens the list. The label names what pressing it
                                 will do, since it starts off and an empty board
                                 otherwise reads as broken. -->
                            <u-button
                                v-if="otherPlayers.length > 0 && !isSpectator"
                                :color="showOtherPlayers ? 'primary' : 'neutral'"
                                :variant="showOtherPlayers ? 'subtle' : 'outline'"
                                size="sm"
                                icon="i-lucide-map-pin"
                                :label="showOtherPlayers ? $t('board.hide_players') : $t('board.show_players')"
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
                            <join-event-button
                                v-if="joined || (!isPaused && !isEnded)"
                                :event-id="liveBoard.id"
                                :joined="joined"
                                :needs-team="needsTeam"
                                :teams="teamOptions"
                                size="sm"
                            />
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

                <!-- Full page width, not the heading's half: these are
                     page-wide news and used to stop where the action bar
                     began. -->
                <event-notices
                    :event="liveBoard"
                    :finishes="liveFinishes"
                    :can-edit="canEdit"
                    :viewing-as-admin="viewingAsAdmin"
                    :admin-edit-url="adminEditUrl"
                    class="mb-6"
                />

                <!-- The way in, for a listed invite-only event. Since
                     2026-08-31 such an event opens for everyone, so it no
                     longer renders Boards/AccessGate — and the code field
                     lived there. Directly under the header, because it is the
                     first thing a reader without access needs. -->
                <invite-code-card v-if="needsInvite" :event-id="liveBoard.id" class="mb-6" />

                <!-- Being teamless used to REPLACE the board with this,
                     which meant somebody arriving at a public team event —
                     possibly to decide whether to join it — was shown a
                     roster problem instead of the event. The board is public
                     reading; the team is only needed to play, so it is said
                     once you have joined, next to what it is about. The
                     prompt for anyone who has not joined yet lives on the
                     join button, where the decision is. -->
                <u-alert
                    v-if="joined && needsTeam"
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-users"
                    :title="$t('board.no_team_title')"
                    :description="$t('board.no_team_desc')"
                    :actions="[{ label: $t('events.choose_team'), color: 'neutral', variant: 'outline', onClick: () => (showTeamEntry = true) }]"
                    class="mt-8"
                />

                <div class="mt-8 flex flex-col lg:flex-row gap-8 items-start">
                    <!-- `isolate` seals the pill's z-index into this box. Without
                         it that z-10 sits in the root stacking context and climbs
                         over a teleported modal. -->
                    <div class="flex-1 w-full min-w-0 isolate">
                        <!-- One button, not a save/cancel pair: every tile edit
                             posts and closes on its own, so a cancel here would
                             guard nothing.

                             It hangs on the board's top border from OUTSIDE the
                             scroller, which is the only way to have both: the
                             board needs `overflow-x-auto`, and once one axis
                             stops being `visible` the other does too, so an
                             overhang inside gets sliced. Padding on the scroller
                             does not buy the room back — clipping happens at the
                             padding box.
                             The pill keeps its own height and the BOARD is pulled
                             up under it; the board's top padding grows to clear
                             the overlap (20px against a 16px dip), so both
                             numbers are set here rather than fitted to a screen.
                             Do not invert it into a zero-height row: a flex row
                             stretches its children to the row's height.
                             Left aligned below `sm`, where the board is wider
                             than the screen. -->
                        <div v-if="editMode" class="relative z-10 flex justify-start sm:justify-center mb-2 sm:-mb-4">
                            <div class="inline-flex max-w-full items-center gap-2 rounded-full bg-default ring-1 ring-primary/50 shadow-sm py-1 pl-3 pr-1">
                                <u-icon name="i-lucide-grid-2x2-plus" class="size-3.5 shrink-0 text-primary" />
                                <span class="text-xs font-semibold truncate">{{ $t('board.editing_tiles_notice') }}</span>
                                <!-- Round, and inset from the pill's own edge: a
                                     square-cornered button flush against a fully
                                     rounded pill reads as two shapes fighting
                                     rather than one control. -->
                                <u-button
                                    size="xs"
                                    color="neutral"
                                    variant="solid"
                                    class="rounded-full shrink-0"
                                    icon="i-lucide-check"
                                    :label="$t('board.done_editing')"
                                    @click="editMode = false"
                                />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                        <!-- board-parchment/osrs-border ported as Tailwind utilities rather
                             than the old app's custom CSS classes (main.css) — same look,
                             but this codebase's convention is Tailwind-first custom CSS only
                             when Tailwind can't express it, and this can. -->
                        <!-- While editing, the board itself says so. The mode
                             lived only in a button at the far end of the header,
                             so the thing whose behaviour had changed — every
                             tile — looked identical either way. A breathing
                             ring rather than a colour change, so the tiles'
                             own snake/ladder/current states stay readable
                             underneath it. -->
                        <div
                            class="relative rounded-xl p-3 border-2 border-stone-400 dark:border-stone-600 bg-amber-50/90 dark:bg-stone-900"
                            :class="[minWidthClass, editMode ? 'board-editing sm:pt-5' : '']"
                        >

                            <!-- The overlay is positioned on THIS box, not on the
                                 parchment around it: the border's p-3 used to sit
                                 inside the overlay's inset-0, so a viewBox unit was
                                 a fraction of a box 24px wider than the grid and
                                 every connector landed up to ten pixels off its
                                 tile — worst at the edges, where a snake looked
                                 like it started outside the board. -->
                            <div class="relative">
                            <!-- Percentage coordinates, not pixels: the grid is
                                 fluid-width, so the overlay scales with it without a
                                 ResizeObserver. Geometry lives in
                                 Support/snakesLadders.js.

                                 Two passes with the grid between: the whole drawing
                                 underneath, then the same drawing clipped to each
                                 connector's own two tiles on top. -->
                            <board-connectors :connections="snakeLadderConnections" :active-key="activeConnector" :passed-position="playerBoard?.current_position ?? null" />
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
                                    class="aspect-square rounded-md relative cursor-pointer overflow-hidden"
                                    :class="tileClasses(tile)"
                                    :title="tileTitle(tile) ?? trans('board.tile', { n: tile.position + 1 })"
                                    @click="handleTileClick(tile)"
                                    @mouseenter="highlightConnector(tile)"
                                    @mouseleave="clearConnector"
                                    @focus="highlightConnector(tile)"
                                    @blur="clearConnector"
                                >
                                    <!-- Everything a tile SHOWS lives in here, so a passed
                                         tile can grey out what it says without its
                                         background going translucent and letting the
                                         connector through at full strength.
                                         No z-index: paint order here stays DOM order, so
                                         nothing can climb above a teleported modal. -->
                                    <span class="board-tile__content">
                                    <!-- The one tile that gets a top label, not just the
                                         --current ring/tint app.css already applies: the
                                         ring reads as "highlighted" on a screenshot but
                                         doesn't say WHICH highlight it is next to the
                                         --past styling on tiles behind it. -->
                                    <transition name="here-label">
                                        <div
                                            v-if="isCurrentTile(tile) && !walker && !editMode"
                                            class="board-tile--here-label absolute top-0 inset-x-0 z-10 text-[8px] sm:text-[10px] font-bold uppercase tracking-wide text-center leading-tight py-0.5 truncate px-0.5"
                                        >
                                            {{ $t('board.you_are_here') }}
                                        </div>
                                    </transition>

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
                                        <!-- A marker has to survive the tile it sits
                                             on: its own ground and a ring that
                                             does not borrow the tile's colour. -->
                                        <u-avatar
                                            v-for="p in playersOnTile(tile.position).slice(0, 3)"
                                            :key="p.id"
                                            :src="p.avatarUrl ?? undefined"
                                            :alt="namesArePublic ? p.name : undefined"
                                            :icon="namesArePublic ? undefined : 'i-lucide-user'"
                                            size="xs"
                                            class="ring-2 ring-primary bg-elevated shadow-md"
                                        />
                                        <span
                                            v-if="playersOnTile(tile.position).length > 3"
                                            class="text-[10px] font-semibold leading-none bg-elevated rounded-full size-5 flex items-center justify-center ring-2 ring-primary shadow-md"
                                        >
                                            +{{ playersOnTile(tile.position).length - 3 }}
                                        </span>
                                    </div>
                                    </span>
                                </button>
                            </div>

                            <board-connectors :connections="snakeLadderConnections" :active-key="activeConnector" :passed-position="playerBoard?.current_position ?? null" clip-ends />

                            <!-- The piece that moves. Bigger than the avatars
                                 stacked on a tile, and drawn after the top
                                 connector pass so it rides ON the snake rather
                                 than under it. -->
                            <div
                                v-for="piece in movingPieces"
                                :key="piece.id"
                                class="board-walker"
                                :style="{ left: `${piece.at.x}%`, top: `${piece.at.y}%`, '--walker-size': piece.at.size }"
                                aria-hidden="true"
                            >
                                <u-avatar
                                    :src="piece.avatarUrl ?? undefined"
                                    :alt="namesArePublic ? piece.name : undefined"
                                    :icon="namesArePublic ? undefined : 'i-lucide-user'"
                                    size="md"
                                    class="ring-2 ring-primary"
                                />
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-64 shrink-0 flex flex-col gap-4">

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
                        <!-- Finished. The dice card is replaced rather than
                             disabled: a player standing on the last tile
                             could still press it, and every roll clamped to
                             where they already were — so the button burnt one
                             of their daily rolls and moved nobody. This is
                             also the part that survives a refresh, which the
                             old celebration modal did not: the modal is the
                             moment, this is the state. -->
                        <u-card v-if="playerBoard && hasFinished">
                            <template #header>
                                <span class="font-semibold">{{ $t('board.finished_title') }}</span>
                            </template>

                            <!-- While an earlier claim is still being checked
                                 this says the run is in and nothing about
                                 which place it took: a place that is about to
                                 move is not one to print. -->
                            <div v-if="finishIsProvisional" class="text-center py-2 space-y-2">
                                <p class="text-4xl">🏁</p>
                                <p class="font-semibold">{{ $t('board.finished_pending_title') }}</p>
                                <p class="text-sm text-muted leading-relaxed">{{ $t('board.finished_pending_desc') }}</p>
                            </div>

                            <div v-else class="text-center py-2 space-y-2">
                                <p class="text-4xl">{{ medal(myFinish.rank) ?? '🏁' }}</p>
                                <p class="font-semibold">
                                    {{ myFinish.rank === 1 ? wonLabel : $t('board.finished_place', { place: ordinal(myFinish.rank) }) }}
                                </p>
                                <p class="text-sm text-muted leading-relaxed">
                                    {{ liveBoard.closed_at ? $t('board.finished_closed') : $t('board.finished_continue', { when: formatDate(liveBoard.end_date) }) }}
                                </p>
                                <u-button
                                    :href="`/events/${liveBoard.id}/leaderboard`"
                                    variant="soft"
                                    color="primary"
                                    size="sm"
                                    icon="i-lucide-trophy"
                                    :label="$t('leaderboard.title')"
                                />
                            </div>
                        </u-card>

                        <!-- Not playable right now: paused, upcoming, or
                             over. A card headed "Roll the dice" whose body
                             says the event has ended is a control promising
                             something it cannot do — reported from exactly
                             that state, on an ended board, with the dev
                             force-roll buttons still sitting under the
                             heading because they were only ever gated on the
                             environment. The heading names the state
                             instead, and nothing inside offers a roll. -->
                        <u-card v-else-if="playerBoard && !canPlayNow">
                            <template #header>
                                <span class="font-semibold">{{ $t(statusLabelKey) }}</span>
                            </template>

                            <p class="text-sm text-muted">
                                {{ isEnded ? $t('events.ended_notice') : (isPaused ? $t('events.paused_notice') : $t('events.not_started')) }}
                            </p>
                        </u-card>

                        <u-card v-else-if="playerBoard">
                            <template #header>
                                <span class="font-semibold">{{ $t('board.roll_dice') }}</span>
                            </template>

                            <!-- Local only: a chosen die, for the animation.
                                 Inside the playable branch, so it cannot
                                 offer a roll the server would refuse. -->
                            <div v-if="canForceRoll" class="flex flex-wrap items-center justify-center gap-1 mb-3">
                                <span class="text-[10px] uppercase tracking-wide text-muted w-full text-center">dev: force roll</span>
                                <u-button
                                    v-for="face in 6"
                                    :key="face"
                                    size="xs"
                                    color="neutral"
                                    variant="outline"
                                    :disabled="rolling"
                                    @click="roll(face)"
                                >
                                    {{ face }}
                                </u-button>
                            </div>

                            <dice-roller
                                v-if="canRoll"
                                :rolling="rolling"
                                :last-roll="lastRoll"
                                :rolls-today="playerBoard?.dice_rolls_today ?? 0"
                                :roll-limit="liveBoard.dice_roll_limit"
                                @roll="roll"
                            />

                            <!-- Playable, but not yet: the tile you are
                                 standing on has to be ticked off first. The
                                 paused/ended/upcoming wordings moved up to
                                 the status card above, which is the one that
                                 renders in those states — leaving them here
                                 meant this card had to be headed "Roll the
                                 dice" to say "you cannot roll". -->
                            <p v-else class="text-sm text-muted">
                                {{ currentClaim?.status === 'PENDING' ? $t('board.roll_awaiting_review') : $t('board.roll_needs_current_tile') }}
                            </p>
                        </u-card>

                        <!-- No board of your own yet. The dice wait for the
                             join now: rolling does still create a board, but
                             offering a passer-by the die made joining look
                             like the long way round to the same thing, and
                             put the decision after the act.

                             Joining itself stays open while upcoming — that's
                             signing up ahead of the start, which is normal
                             and is what the header's own join button already
                             allows (EventParticipationService::join() has
                             never checked isUpcoming, only isPaused). Only
                             the dice — actually playing — wait for the start
                             date, same as everywhere else this pass touched. -->
                        <u-card v-if="!playerBoard">
                            <p class="text-sm text-muted">
                                {{ sidebarStateText }}
                            </p>
                            <div v-if="!isPaused && !isEnded" class="mt-3 flex flex-col gap-3">
                                <join-event-button v-if="!joined" :event-id="liveBoard.id" :joined="false" :needs-team="needsTeam" :teams="teamOptions" />
                                <!-- No die without a team: rolling creates the
                                     board you play on, and on a team event
                                     that board is the TEAM's — so the server
                                     has nothing to make one from and answers
                                     with an error toast. Offering the roll
                                     anyway was how somebody with no team got
                                     a die and a refusal instead of the
                                     reason. -->
                                <dice-roller v-if="joined && !isUpcoming && !needsTeam" :rolling="rolling" :last-roll="lastRoll" :rolls-today="0" :roll-limit="liveBoard.dice_roll_limit" @roll="roll" />
                                <u-button v-else-if="joined && needsTeam" color="neutral" variant="outline" block icon="i-lucide-users" :label="$t('events.choose_team')" @click="showTeamEntry = true" />
                            </div>
                        </u-card>

                        <!-- Below the dice, by the owner's call: the action goes
                             at the top of the column.
                             These two cards have now been swapped twice, both
                             times because the roll card's disabled copy pointed
                             at this one by direction — "mark it complete above"
                             was a lie the first time round and would be one
                             again now. The copy no longer says where: it names
                             the card instead, so the order is free to change
                             without the text going stale a third time. -->
                        <u-card v-if="currentTile">
                            <template #header>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold">{{ $t('board.your_task') }}</span>
                                    <!-- A button rather than a bare icon — a wiki
                                         link is the one external action on this
                                         card and should read as one — but a short
                                         one. "Open the wiki page" beside the title
                                         in a 256px column left the heading three
                                         words on three lines. The full sentence
                                         stays as the accessible name. -->
                                    <u-button
                                        v-if="currentTile.task?.wiki_url"
                                        :href="currentTile.task.wiki_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        size="xs"
                                        color="neutral"
                                        variant="outline"
                                        class="shrink-0"
                                        trailing-icon="i-lucide-external-link"
                                        :label="$t('tile_editor.wiki')"
                                        :title="$t('tile_editor.open_wiki_page')"
                                        :aria-label="$t('tile_editor.open_wiki_page')"
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

                        <!-- Beside the editors, because handing out a link is
                             about who is in the event. Renders itself away
                             for anyone but a host of an invite-only one. -->
                        <host-invite-card
                            :event-id="liveBoard.id"
                            :access-mode="liveBoard.access_mode"
                            :can-edit="canEdit"
                        />

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
                                        v-if="p.team?.icon_url || p.team?.guild_icon_url"
                                        :src="p.team.icon_url || p.team.guild_icon_url"
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
                                    <!-- An icon rather than initials when there is
                                         nobody to initial: UAvatar derives them
                                         from `alt`, so the anonymous label came
                                         back as a monogram of itself ("Ap"). -->
                                    <u-avatar
                                        v-else
                                        :src="p.user?.avatar_url ?? undefined"
                                        :alt="namesArePublic ? playerName(p) : undefined"
                                        :icon="namesArePublic ? undefined : 'i-lucide-user'"
                                        size="xs"
                                        class="shrink-0"
                                    />
                                    <span class="flex-1 min-w-0 truncate font-medium" :class="namesArePublic ? '' : 'text-muted italic'">{{ playerName(p) }}</span>
                                    <!-- Said in a word, not only in a tint.
                                         A row shaded 10% against a dark
                                         panel is easy to miss, and "which of
                                         these is mine" is the first question
                                         anyone asks of a leaderboard. -->
                                    <u-badge
                                        v-if="isMyPlayerRow(p)"
                                        :label="liveBoard.mode === 'TEAM' ? $t('board.your_team') : $t('board.you')"
                                        color="primary"
                                        variant="soft"
                                        size="xs"
                                        class="shrink-0"
                                    />
                                    <!-- A finisher is not "at tile 49 with 0
                                         to go" like everybody parked on the
                                         last square; they are done, and the
                                         place they came in is the only number
                                         about them that still matters. -->
                                    <span v-if="finishRankFor(p)" class="text-xs shrink-0" :title="$t('board.finished_place', { place: ordinal(finishRankFor(p)) })">
                                        {{ medal(finishRankFor(p)) ?? `#${finishRankFor(p)}` }}
                                    </span>
                                    <span v-else class="text-xs text-muted shrink-0">#{{ p.current_position + 1 }}</span>
                                    <span
                                        v-if="!finishRankFor(p)"
                                        class="text-xs font-semibold w-10 text-right shrink-0"
                                        :class="p.pathHasSnake ? 'text-error' : p.pathHasLadder ? 'text-success' : 'text-muted'"
                                    >
                                        {{ p.tilesRemaining }}🔲
                                    </span>
                                    <span v-else class="text-xs font-semibold w-10 text-right shrink-0 text-success">
                                        {{ $t('board.finished_short') }}
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

        <!-- The moment, not the state: the card in the sidebar is what is
             still there tomorrow. It can say which place now, because the
             server decided it — the guess this replaces knew only that the
             last tile had been ticked. -->
        <u-modal v-model:open="showCompleted" :title="myFinish?.rank === 1 ? wonLabel : $t('board.completed')">
            <template #body>
                <div class="text-center py-6">
                    <p class="text-6xl mb-4">{{ medal(myFinish?.rank) ?? '🎉' }}</p>
                    <!-- First place gets told it won, in as many words. Every
                         other place gets told which one it is — "board
                         complete" said the same thing to whoever got home
                         first and to whoever came fourth. -->
                    <p class="font-semibold mb-1">
                        {{ myFinish?.rank === 1 ? $t('board.won_congrats') : $t('board.finished_place', { place: ordinal(myFinish?.rank) }) }}
                    </p>
                    <p class="text-muted">
                        {{ liveBoard.closed_at ? $t('board.completed_desc_closed') : $t('board.completed_desc') }}
                    </p>
                </div>
            </template>
            <template #footer>
                <u-button block color="primary" :label="$t('common.close')" @click="showCompleted = false" />
            </template>
        </u-modal>

        <client-only>
            <!-- Editor-only, and mounted only for an editor: the settings
                 modal loads the event's team list as soon as it exists, so a
                 player's console filled with 403s from a request they were
                 never allowed to make. Same gating SkillRace and Bingo
                 already had. -->
            <template v-if="canEdit">
                <board-settings-modal
                    v-model:open="showSettingsModal"
                    :board="liveBoard"
                    :webhook-url="webhookUrl"
                    :initial-tab="settingsTab"
                    :finishes="liveFinishes"
                />
                <tile-list-editor
                    v-model:open="showTileList"
                    :event-id="liveBoard.id"
                    type="SNAKES_LADDERS"
                    :items="tiles"
                    :total="tileCount"
                />
                <tile-review-modal v-model:open="showReviewModal" :event-id="liveBoard.id" :claims="livePending" />
            </template>
            <tile-edit-modal
                v-if="editingTile"
                :open="editingTile !== null"
                :event-id="liveBoard.id"
                :position="editingTile.position"
                :tile-count="tiles.length"
                :tile="editingTile.id ? editingTile : null"
                @update:open="(v) => !v && (editingTile = null)"
            />
            <team-entry-modal v-model:open="showTeamEntry" :event-id="liveBoard.id" :teams="teamOptions" />
            <tile-claim-modal
                v-if="currentTile"
                v-model:open="showClaimModal"
                :event-id="liveBoard.id"
                :tile="currentTile"
                :tile-title="currentTileTitle"
                :claim="currentClaim"
            />
        </client-only>
    </u-main>
</template>

<script setup>
import { computed, defineAsyncComponent, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import BoardConnectors from '@/Components/BoardConnectors.vue';
import EventTypeHeading from '@/Components/EventTypeHeading.vue';
import EventNotices from '@/Components/EventNotices.vue';
import InviteCodeCard from '@/Components/InviteCodeCard.vue';
import HostInviteCard from '@/Components/HostInviteCard.vue';
import JoinEventButton from '@/Components/JoinEventButton.vue';
import TeamEntryModal from '@/Components/TeamEntryModal.vue';
import DiceRoller from '@/Components/DiceRoller.vue';
import EventManageMenu from '@/Components/EventManageMenu.vue';
import { BOARD_STATUS_STYLE, BOARD_TILE_COUNT, BOARD_MIN_WIDTH, formatBoardSize, formatDate, eventStatus, ordinal } from '@/Support/board';
import { bridgeParts, connection, endTiles, isSameRow, ladderParts, snakeParts, tileCenter, travelPath } from '@/Support/snakesLadders';
import { useEventStream } from '@/Composables/useEventStream';

const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));
const TileListEditor = defineAsyncComponent(() => import('@/Components/TileListEditor.vue'));
const TileEditModal = defineAsyncComponent(() => import('@/Components/TileEditModal.vue'));
const TileClaimModal = defineAsyncComponent(() => import('@/Components/TileClaimModal.vue'));
const TileReviewModal = defineAsyncComponent(() => import('@/Components/TileReviewModal.vue'));

const props = defineProps({
    // True when this reader is looking at an invite-only event they are
    // not in yet — the code field lives on the page now, not on a gate.
    needsInvite: { type: Boolean, default: false },
    // Local development only — see BoardController::show.
    canForceRoll: { type: Boolean, default: false },
    board: { type: Object, required: true },
    tiles: { type: Array, required: true },
    playerBoard: { type: Object, default: null },
    players: { type: Array, default: () => [] },
    // Whether the pieces on this board may be named. False on a listed
    // invite-only event, where the progress is public and the roster is
    // not — see BoardAccessService::canSeeParticipants(). Sent as its own
    // flag because a row with no user is otherwise indistinguishable from
    // a deleted account, which is a different thing to say.
    namesArePublic: { type: Boolean, default: true },
    hasTeam: { type: Boolean, default: true },
    // Teams this reader runs that are not in the event yet — see
    // TeamEntryModal. Empty for everyone with nothing to offer.
    teamOptions: { type: Array, default: () => [] },
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
    // The podium, in the order it was earned — see EventFinishService. A
    // page prop rather than a browser-side guess: finishing used to be
    // worked out here, by comparing the ticked tile's position against the
    // tile count, which reached one person and did not survive their
    // refresh.
    finishes: { type: Array, default: () => [] },
    // This viewer's own place, if they have one. Kept apart from the list
    // above because that list is anonymised on a private event, and being
    // told your own name is not a leak.
    myFinish: { type: Object, default: null },
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

// Same first-paint-then-stream shape as the players list: somebody getting
// home is a fact about the board, so every open page hears it on the
// channel rather than on their next reload.
const liveFinishes = ref([...props.finishes]);
watch(() => props.finishes, (value) => (liveFinishes.value = [...value]));
/**
 * A player's label: their name, or the anonymous stand-in.
 *
 * Three states, not two. A named player, a genuinely deleted account, and a
 * player whose name this reader is not entitled to — the last one is new
 * (2026-08-31) and must not borrow the "deleted" wording, which would tell
 * every visitor to a listed invite-only board that its whole roster left.
 */
function playerName(p) {
    if (!props.namesArePublic) return trans('events.anonymous_player');

    return p.team?.name ?? p.user?.nickname ?? p.user?.discord_username ?? trans('common.deleted_user');
}

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
    //
    // `myFinish` rides along because it is the one thing on this page that
    // the stream cannot carry: the podium is public and arrives on the
    // channel, but *your own place* is yours, so it only ever comes back on
    // a visit. Reported directly — a player whose winning claim had just
    // been approved watched the banner update to say two teams had finished
    // while their own sidebar still offered them a dice roll, because
    // `hasFinished` reads this prop and nothing was refreshing it.
    router.reload({ only: ['playerBoard', 'pending', 'myFinish', 'finishes'] });
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

        // Guarded rather than assigned blind: an older SSR bundle may still
        // be streaming payloads from before this existed, and replacing the
        // podium with `undefined` would empty it on the first push.
        if (payload.finishes) liveFinishes.value = payload.finishes;
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

// --------------------------------------------------------------- finishing

/**
 * Whether a roll is on offer at all — which is a different question from
 * whether this player may roll *yet* (that is `canRoll`, and it is about the
 * tile they are standing on). Ended outranks the rest here the same way it
 * does in eventStatus(), and a finished player is done regardless.
 */
const canPlayNow = computed(() => !isPaused.value
    && !isEnded.value
    && !isUpcoming.value
    && !hasFinished.value);

/** The state's own label, for the card that stands in for the dice. */
const statusLabelKey = computed(() => (BOARD_STATUS_STYLE[eventStatus(liveBoard.value)] ?? BOARD_STATUS_STYLE.live).labelKey);

/** This viewer's own place, or null while they are still playing. */
const myFinish = computed(() => props.myFinish);
const hasFinished = computed(() => myFinish.value !== null);

/**
 * Whether this viewer's place is still up for grabs.
 *
 * True while an earlier claim is sitting in the review queue: approving it
 * pushes this finish down a place. The page says the run is in, and stays
 * quiet about which place it took.
 */
const finishIsProvisional = computed(() => myFinish.value?.provisional === true);

/** Any dialog that a celebration must not land on top of — or behind. */
const aDialogIsOpen = computed(() => showReviewModal.value
    || showClaimModal.value
    || showSettingsModal.value
    || showTileList.value
    || editingTile.value !== null);

/**
 * The celebration, fired by the server's answer rather than by a guess made
 * here. Watching `myFinish` rather than calling it from toggleTile() also
 * covers the case that guess could never have handled: on a board that
 * reviews claims, the run ends when the host approves the last tile, which
 * happens in somebody else's browser and arrives here on the live stream.
 *
 * Two things hold it back, both reported:
 *
 *  - **A provisional place.** A host who approves the second submission
 *    first must not set off a celebration for a competitor an earlier claim
 *    is about to overtake.
 *  - **Another dialog.** A host is usually also a player, so approving the
 *    claim that finishes their own team's run opened this *underneath* the
 *    review queue they were working in — a modal behind a modal. It waits
 *    for them to finish reviewing.
 */
const celebrationPending = ref(false);

watch(() => props.myFinish, (now, before) => {
    if (now && !before) celebrationPending.value = true;
});

watch([celebrationPending, finishIsProvisional, aDialogIsOpen], () => {
    if (celebrationPending.value && !finishIsProvisional.value && !aDialogIsOpen.value) {
        celebrationPending.value = false;
        showCompleted.value = true;
    }
}, { immediate: true });

/**
 * "You won" or "Your team won" — a team event is won by the team, and
 * telling one member they personally won it reads wrong to the other five.
 */
const wonLabel = computed(() => (liveBoard.value.mode === 'TEAM'
    ? trans('board.won_team')
    : trans('board.won_solo')));

/** 1st, 2nd, 3rd — then plain numbers, which is what a medal is for. */
function medal(rank) {
    return ['🥇', '🥈', '🥉'][rank - 1] ?? null;
}

/**
 * Where a player row sits on the podium, so the sidebar can medal it.
 * Matched on team on a TEAM event and on user otherwise — the same either/or
 * the finish itself is recorded against.
 */
function finishRankFor(player) {
    const key = liveBoard.value.mode === 'TEAM' ? player.team_id : player.user_id;

    if (!key) return null;

    const found = liveFinishes.value.find((f) => (liveBoard.value.mode === 'TEAM' ? f.teamId : f.userId) === key);

    return found?.rank ?? null;
}
const showOtherPlayers = ref(false);

/**
 * Somebody reading the event rather than playing it.
 *
 * The toggle exists to keep your own marker findable in a crowd, so it only
 * means anything to somebody who has one. Without a player board every row
 * is an "other" player, which made the default state hide the entire board —
 * a spectator opened a live event and saw an empty grid with a button
 * offering to show them the players, which is the wrong way round. They
 * cannot join, so there is nothing to withhold: they get the full board and
 * no button.
 */
const isSpectator = computed(() => props.playerBoard === null);
const showingOthers = computed(() => isSpectator.value || showOtherPlayers.value);

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

/**
 * What the sidebar card says while there is no board of your own yet, in the
 * order the states rule each other out: over, stopped, not started, no team,
 * joined-and-ready, not joined.
 */
const sidebarStateText = computed(() => {
    if (isEnded.value) return trans('events.ended_notice');
    if (isPaused.value) return trans('events.paused_notice');
    if (isUpcoming.value) return trans('events.not_started');
    if (! props.joined) return trans('events.join_hint');

    return needsTeam.value ? trans('board.no_team_desc') : trans('board.get_started_desc');
});

// A team event is played per team, so somebody on none of the event's teams
// has nothing to play on — but plenty to read. Drives the notice above the
// board and the prompt on the join button, neither of which hides the event.
const needsTeam = computed(() => liveBoard.value.mode === 'TEAM' && ! props.hasTeam);

const showTeamEntry = ref(false);

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
    // Its own entry rather than "go to settings, then the last tab": where
    // an event stands — and pausing, ending or reopening it — is the thing a
    // host opens this menu for most often, and it was four clicks deep in a
    // tab called "Stop". Same panel either way; this one just lands on it.
    { key: 'status', label: trans('events.status_menu'), icon: statusIcon.value },
    ...(requiresApproval.value ? [{ key: 'review', label: trans('bingo.review_queue'), icon: 'i-lucide-gavel', badge: livePending.value.length }] : []),
]);

function onManage(key) {
    if (key === 'edit') editMode.value = ! editMode.value;
    if (key === 'tiles') showTileList.value = true;
    if (key === 'settings') showSettingsModal.value = true;
    if (key === 'review') showReviewModal.value = true;

    // The same dialog, opened on the tab that was asked for. `settingsTab`
    // is read by BoardSettingsModal only when it opens, so setting it in the
    // same tick as `open` is enough.
    if (key === 'status') {
        settingsTab.value = 'danger';
        showSettingsModal.value = true;
    } else if (key === 'settings') {
        settingsTab.value = 'basics';
    }
}

/**
 * The menu row's icon reports the state rather than naming the action — a
 * host glancing at the menu can see that an event is paused without opening
 * anything.
 */
const statusIcon = computed(() => ({
    upcoming: 'i-lucide-clock',
    live: 'i-lucide-circle-play',
    paused: 'i-lucide-pause',
    ended: 'i-lucide-flag',
}[eventStatus(liveBoard.value)] ?? 'i-lucide-circle-play'));

const settingsTab = ref('basics');

const clickedTileTitle = computed(() => clickedTile.value?.title_override ?? clickedTile.value?.task?.title ?? trans('tile_editor.no_task'));

// Ported from useBoardPage's otherPlayerStates/boardPlayerStates split — "me"
// is whichever row matches my user (SOLO) or my team (TEAM); everyone else
// only renders on the grid once the "show other players" toggle is on.
const authUser = computed(() => inertiaPage.props.auth?.user ?? null);
const isMyPlayerRow = (p) => (liveBoard.value.mode === 'TEAM' ? p.team_id === props.playerBoard?.team_id : p.user_id === authUser.value?.id);
const otherPlayers = computed(() => livePlayers.value.filter((p) => !isMyPlayerRow(p)));
// Reads the live list, so a roll by anyone moves their avatar on every
// open board rather than only after a refresh.
const visiblePlayers = computed(() => livePlayers.value.filter((p) => isMyPlayerRow(p) || showingOthers.value));

/**
 * The pieces in motion. A roll is two motions: the walk the dice bought and,
 * if it lands on one, the ride that follows. Paced differently on purpose —
 * walking is the player's doing, the ride is the board's.
 */
const WALK_MS_PER_TILE = 300;
const HOP_MS_PER_RUNG = 105;
const SLIDE_MS_PER_UNIT = 11;
const SLIDE_MIN_MS = 420;
const LIFT_MS = 220;
const LAND_MS = 260;

/**
 * Every piece currently in motion, keyed by its player board id.
 *
 * A map rather than one piece, because a move no longer belongs only to the
 * person who rolled: the stream carries everybody's, and two people can be
 * walking at once on a busy board.
 */
const walkers = ref(new Map());

/** The viewer's own piece, which is the one the roll button drives. */
const walker = computed(() => walkers.value.get(props.playerBoard?.id) ?? null);

/** Every piece in motion, with the face to draw for it. */
const movingPieces = computed(() =>
    [...walkers.value.entries()].map(([id, at]) => {
        const player = livePlayers.value.find((p) => p.id === id);

        return {
            id,
            at,
            name: player ? playerName(player) : '',
            avatarUrl: player?.team?.icon_url ?? player?.team?.guild_icon_url ?? player?.user?.avatar_url ?? null,
        };
    }),
);

const prefersReducedMotion = () =>
    typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** Ease the piece into and out of every leg, so it does not jerk at corners. */
const easeInOut = (t) => (t < 0.5 ? 2 * t * t : 1 - (-2 * t + 2) ** 2 / 2);

/**
 * Run a piece along a list of points over `duration`.
 *
 * `sizeAt` turns a slide into a step: swelling through the middle of a leg is
 * the only way to read as a hop on a board seen from directly above.
 */
function glide(id, points, duration, sizeAt = null) {
    return new Promise((resolve) => {
        if (points.length === 0) {
            resolve();

            return;
        }

        const start = performance.now();

        const frame = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const at = easeInOut(t) * (points.length - 1);
            const i = Math.min(points.length - 1, Math.floor(at));
            const next = points[Math.min(points.length - 1, i + 1)];
            const f = at - i;

            place(id, {
                x: points[i].x + (next.x - points[i].x) * f,
                y: points[i].y + (next.y - points[i].y) * f,
                size: sizeAt ? sizeAt(t) : 1,
            });

            if (t < 1) {
                requestAnimationFrame(frame);
            } else {
                resolve();
            }
        };

        requestAnimationFrame(frame);
    });
}

/** Reassigns the map so Vue sees the change — a Map mutation is invisible. */
function place(id, at) {
    const next = new Map(walkers.value);

    if (at === null) {
        next.delete(id);
    } else {
        next.set(id, at);
    }

    walkers.value = next;
}

/** A step: a small bob, so a walk reads as footfalls rather than a drift. */
const stepSize = (t) => 1 + 0.1 * Math.sin(Math.PI * t);

/** A hop: a real one, because a ladder is climbed rung by rung. */
const hopSize = (t) => 1 + 0.34 * Math.sin(Math.PI * t);

/** Where a tile's avatar stack sits, which is where a piece rests. */
function restingSpot(position) {
    const n = cols.value;
    const centre = tileCenter(position, n);
    const cell = 100 / n;

    return { centre, corner: { x: centre.x + cell * 0.3, y: centre.y + cell * 0.3 } };
}

/** The size a resting marker is drawn at, relative to the moving piece. */
const RESTING_SIZE = 0.42;

/** Stand up off the tile before setting off — the mirror of land(). */
async function lift(id, position) {
    const { centre, corner } = restingSpot(position);

    await glide(id, [corner, centre], LIFT_MS, (t) => RESTING_SIZE + (1 - RESTING_SIZE) * t);
}

/**
 * Settle onto the destination: shrink into the corner where the tile's marker
 * stack sits, so the piece becomes the marker rather than swapping with it.
 */
async function land(id, position) {
    const { centre, corner } = restingSpot(position);

    await glide(id, [centre, corner], LAND_MS, (t) => 1 - (1 - RESTING_SIZE) * t);
}

/**
 * Walk the dice, then ride whatever was waiting.
 *
 * Driven by what the server flashed back, not by arithmetic repeated here.
 * Where a roll lands and where a snake then sends you is the one thing the
 * board must not get wrong twice — and the first version of this did re-derive
 * it in the browser, which was both a second source of truth and, as it turned
 * out, quietly broken.
 */
/**
 * One token per piece. A second roll landing mid-animation would otherwise run
 * two loops over the same piece; each run stands down once a newer one exists.
 */
/** The two animation switches, from the account's display preferences. */
const ownAnimationOn = computed(() => authUser.value?.display?.animate_own_moves ?? true);
const othersAnimationOn = computed(() => authUser.value?.display?.animate_other_moves ?? true);

const moveTokens = new Map();

/**
 * The newest move sequence already accounted for, per piece. Seeded on first
 * render, or the first stream tick replays everyone's last roll.
 */
const seenMoves = new Map();

async function playMove(id, { from, landed, to, jump }) {
    if (prefersReducedMotion()) {
        return;
    }

    const token = (moveTokens.get(id) ?? 0) + 1;
    moveTokens.set(id, token);
    const current = () => moveTokens.get(id) === token;

    const n = cols.value;
    const cell = 100 / n;

    await lift(id, from);

    if (!current()) {
        return;
    }

    for (let position = from + 1; position <= landed && current(); position++) {
        // eslint-disable-next-line no-await-in-loop -- a walk is sequential
        await glide(id, [tileCenter(position - 1, n), tileCenter(position, n)], WALK_MS_PER_TILE, stepSize);
    }

    if (jump && current()) {
        const points = travelPath(landed, to, n, jump === 'snake' ? 'SNAKE' : 'LADDER');
        const length = points.reduce(
            (sum, p, i) => (i === 0 ? 0 : sum + Math.hypot(p.x - points[i - 1].x, p.y - points[i - 1].y)),
            0,
        );

        if (jump === 'snake') {
            // A slide, in one motion. Nobody takes steps down a snake.
            await glide(id, points, Math.max(SLIDE_MIN_MS, length * SLIDE_MS_PER_UNIT), stepSize);
        } else {
            // Rung by rung. Spaced the same way the ladder's own rungs are, so
            // the piece lands on them rather than passing over them — one long
            // glide up a ladder was the one part of this that looked like a
            // line being drawn instead of somebody climbing.
            const hops = Math.max(2, Math.round(length / (cell * 0.42)));

            for (let i = 1; i <= hops && current(); i++) {
                const at = (k) => points[Math.min(points.length - 1, Math.round(((points.length - 1) * k) / hops))];

                // eslint-disable-next-line no-await-in-loop -- a climb is sequential
                await glide(id, [at(i - 1), at(i)], HOP_MS_PER_RUNG, hopSize);
            }
        }
    }

    if (!current()) {
        return;
    }

    await wait(120);
    await land(id, to);

    // Only the run that is still current clears the piece — otherwise a
    // superseded one wipes the walker out from under its replacement.
    if (current()) {
        place(id, null);
    }
}

/**
 * Play everybody else's moves off the live stream — without this the animation
 * belongs only to whoever rolled, and every other viewer sees a teleport.
 *
 * Driven by the sequence number, not the position: two rolls can finish on the
 * same tile, and that is the move most worth watching.
 */
watch(
    () => livePlayers.value.map((p) => `${p.id}:${p.move_seq ?? 0}`).join(','),
    () => {
        for (const player of livePlayers.value) {
            const seq = player.move_seq ?? 0;
            const seen = seenMoves.get(player.id);

            seenMoves.set(player.id, seq);

            // First sight of a piece is a baseline, not a move — otherwise
            // opening the page replays whatever everyone did last.
            if (seen === undefined || seq <= seen || player.last_move_from === null) {
                continue;
            }

            if (isMyPlayerRow(player) ? !ownAnimationOn.value : !othersAnimationOn.value) {
                continue;
            }

            playMove(player.id, {
                from: player.last_move_from,
                landed: player.last_move_landed,
                to: player.current_position,
                jump: player.last_move_jump,
            });
        }
    },
    { immediate: true },
);

// Local development only: drive the animation without a round trip.
if (typeof window !== 'undefined' && props.canForceRoll) {
    window.__boardPlayMove = (move) => playMove(props.playerBoard?.id ?? 'dev', move);
}

function playersOnTile(position) {
    // Nobody's piece while editing: the tiles are being rewritten, and a stack
    // of avatars on a tile you are about to change is noise about a game that
    // is paused for the moment.
    if (editMode.value) {
        return [];
    }

    return visiblePlayers.value
        .filter((p) => p.current_position === position)
        // While a piece is walking it is drawn by the walker instead, so it
        // is not also sitting on the tile it is leaving.
        // A piece that is walking is drawn by the walker instead, so it is
        // not also sitting on the tile it is leaving.
        .filter((p) => !walkers.value.has(p.id))
        .map((p) => ({
            id: p.team?.id ?? p.user_id,
            // Falls through to the deleted-player label rather than a hardcoded
            // 'Player': a board keeps the space somebody occupied after
            // they close their account, and an unlabelled row reads as a bug.
            name: playerName(p),
            avatarUrl: p.team?.icon_url ?? p.team?.guild_icon_url ?? p.user?.avatar_url ?? null,
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

// The shapes are built here rather than in the template, so a board with a
// dozen connectors walks its tiles once per change instead of once per render
// — and both overlay layers read the same computed array.
//
// The live copy, so a ladder moved mid-event redraws on every open board
// rather than only after a reload.
const snakeLadderConnections = computed(() => {
    const n = cols.value;

    return liveTiles.value
        .filter((t) => (t.type === 'SNAKE' || t.type === 'LADDER') && t.target_position !== null)
        .map((t) => {
            // Null for a tile pointing at itself — bad data rather than a
            // connector, and there is no shape to draw for it.
            const conn = connection(t.position, t.target_position, n);

            if (conn === null) {
                return null;
            }

            return {
                // Position, not index: a stable key across a tile being
                // added or removed.
                key: `${t.type}-${t.position}`,
                type: t.type,
                // A jump along a row gets a bridge instead of a ladder —
                // see isSameRow() for why that case is different in kind.
                parts: t.type === 'SNAKE'
                    ? snakeParts(conn)
                    : isSameRow(t.position, t.target_position, n)
                        ? bridgeParts(conn)
                        : ladderParts(conn),
                // The two tiles this connector belongs to. The top pass clips
                // itself to them, which is what lets its ends come out over
                // the grid while the rest of it stays behind.
                ends: endTiles(t.position, t.target_position, n),
                // Which tiles light this connector up when pointed at.
                tiles: [t.position, t.target_position],
                // Where you get on. Kept on the shape so the overlay can dim
                // what is behind you without the shapes being rebuilt every
                // time somebody moves.
                from: t.position,
            };
        })
        .filter(Boolean);
});

/**
 * The connector being pointed at. Either end lights the whole thing: the
 * question asked of a snake is always about the other tile.
 */
const activeConnector = ref(null);

const connectorByTile = computed(() => {
    const map = new Map();

    for (const conn of snakeLadderConnections.value) {
        for (const position of conn.tiles) {
            map.set(position, conn.key);
        }
    }

    return map;
});

function highlightConnector(tile) {
    activeConnector.value = connectorByTile.value.get(tile.position) ?? null;
}

function clearConnector() {
    activeConnector.value = null;
}

/** True for both ends of whichever connector is lit. */
function isLinkedTile(tile) {
    return activeConnector.value !== null && connectorByTile.value.get(tile.position) === activeConnector.value;
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

    // The ground the connector shows through, at 80%: enough that a task stays
    // legible, little enough that a snake behind it still reads.
    const ground = isTileEmpty(tile)
        ? 'bg-stone-200/80 dark:bg-stone-800/80'
        : 'bg-amber-100/80 dark:bg-stone-800/80';

    const classes = ['board-tile', ground, 'backdrop-blur-[2px]'];

    if (tile.type === 'SNAKE') classes.push('board-tile--snake');
    if (tile.type === 'LADDER') classes.push('board-tile--ladder');

    if (current !== undefined && current !== null && tile.id !== null) {
        if (tile.position === current) classes.push('board-tile--current');
        else if (tile.position < current) classes.push('board-tile--past');
    }

    if (isTileCompleted(tile)) classes.push('board-tile--completed');

    if (isLinkedTile(tile)) classes.push('board-tile--linked');

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

function roll(force = null) {
    rolling.value = true;

    router.post(
        `/events/${liveBoard.value.id}/roll`,
        force ? { force } : {},
        {
            preserveScroll: true,
            onFinish: () => (rolling.value = false),
            // The page Inertia hands the callback, not usePage() — that one
            // is read too early here and comes back with the previous visit's
            // flash, which is how this silently animated nothing at all.
            onSuccess: (page) => {
                const move = page?.props?.flash?.lastMove;

                if (move && props.playerBoard) {
                    // Remembered so the same move arriving on the stream a
                    // moment later is not walked a second time.
                    seenMoves.set(props.playerBoard.id, move.seq);

                    if (ownAnimationOn.value) {
                        playMove(props.playerBoard.id, move);
                    }
                }
            },
        },
    );
}

// Ported from the old useBoardPage's onCompleteTile.
//
// Called "bingo" until 2026-08-20, which was a misnomer waiting to collide:
// this fires on finishing a Snakes & Ladders board, and BINGO is becoming
// a separate event type with its own line/full-board rules (ROADMAP phase 5).
//
// Whether the run is over is no longer decided here. It used to be: this
// compared the ticked tile's position against the tile count and popped the
// celebration itself, which was wrong in three ways at once — a refresh
// erased it, nobody else was ever told, and on a board that reviews claims
// it congratulated the player before the host had seen the proof. The server
// stamps the finish (EventFinishService) and `myFinish` comes back with the
// visit; the watch below is what celebrates.
function toggleTile(tile) {
    router.post(`/events/${liveBoard.value.id}/tiles/${tile.id}/toggle`, {}, {
        preserveScroll: true,
    });
}
</script>
