<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\UserGuild;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * First-run flow state. The flow itself is a client-side modal
 * (Components/OnboardingModal.vue) that reuses the existing board-create
 * endpoint for its one real action — this controller only records that the
 * user is through it, so it doesn't reappear on every page load.
 */
class OnboardingController extends Controller
{
    public function complete(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return back();
    }

    /** Re-open the flow on demand ("show me that tour again"). */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => null]);

        return back();
    }

    /**
     * Boards this user could actually join right now, for the "find a board"
     * step shown to anyone without canCreateBoards. Plain JSON over fetch()
     * rather than Inertia props: the modal lives in AppRoot, not on a page
     * that could receive them, and this is only needed when that one step
     * opens (same reasoning as BoardSettingsModal's invite list).
     *
     * INVITE boards are excluded — they need a code the user doesn't have,
     * so listing them would just be a wall of things they can't click.
     * GUILD boards are included only for guilds the user is actually in,
     * which for an email-only account is none (UserGuild rows come solely
     * from Discord's guild sync) — hence the connect-Discord step.
     */
    public function joinableBoards(Request $request): JsonResponse
    {
        $guildIds = UserGuild::where('user_id', $request->user()->id)->pluck('guild_id');

        $boards = Event::query()
            ->where('is_listed', true)
            // Nothing that would turn them away on arrival. An event that
            // finished last week and one a host has stopped both refuse a
            // join — offering either as somebody's first act on the site is
            // a worse introduction than showing three events instead of four.
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->startOfDay()))
            ->whereNull('paused_at')
            ->where(fn ($q) => $q
                ->where('access_mode', 'OPEN')
                ->orWhere(fn ($guild) => $guild
                    ->where('access_mode', 'GUILD')
                    ->whereIn('required_guild_id', $guildIds)))
            ->orderByDesc('start_date')
            ->limit(4)
            // `size` lives on the board since the split, and selecting it here
            // does NOT fail loudly: SQLite reads the unknown identifier as a
            // string literal and hands back the word "size" under a quoted
            // key, so dev looks fine. PostgreSQL — production — raises
            // "column does not exist" and 500s the whole endpoint.
            ->with('board:id,event_id,size')
            ->get(['id', 'title', 'description', 'mode', 'access_mode'])
            ->map(fn (Event $event) => [
                ...$event->only(['id', 'title', 'description', 'mode', 'access_mode']),
                // Null for event types that have no board; the modal shows
                // the size only when there is one.
                'size' => $event->board?->size,
            ]);

        return response()->json(['boards' => $boards]);
    }
}
