<?php

namespace App\Http\Controllers;

use App\Models\Board;
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

        $boards = Board::query()
            ->where('is_listed', true)
            ->where(fn ($q) => $q
                ->where('access_mode', 'OPEN')
                ->orWhere(fn ($guild) => $guild
                    ->where('access_mode', 'GUILD')
                    ->whereIn('required_guild_id', $guildIds)))
            ->orderByDesc('start_date')
            ->limit(4)
            ->get(['id', 'title', 'description', 'size', 'mode', 'access_mode']);

        return response()->json(['boards' => $boards]);
    }
}
