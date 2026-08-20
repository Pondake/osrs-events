<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BoardInvite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every invite across every board in one place.
 *
 * Until now a BoardInvite was only visible inside its own board's settings
 * modal, which answers "what invites does this board have" but never "who
 * has been handing out links, and which of them are still live".
 */
class InviteController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * Status is derived, not stored — expiry is a moment passing, not an
     * event anything writes a row for. Kept as scopes so the filter and the
     * per-row label can't disagree about what "expired" means.
     */
    public const STATUSES = ['active', 'expired', 'exhausted', 'unused'];

    public function index(Request $request): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $invites = BoardInvite::query()
            ->with(['event:id,title', 'creator:id,discord_username,nickname,email'])
            ->withCount('accesses')
            ->when(
                $request->string('status')->isNotEmpty(),
                fn ($q) => $this->applyStatus($q, $request->string('status')->toString()),
            )
            ->when(
                $request->string('board')->isNotEmpty(),
                fn ($q) => $q->where('event_id', $request->string('board')),
            )
            ->when(
                $request->string('creator')->isNotEmpty(),
                fn ($q) => $q->where('created_by', $request->string('creator')),
            )
            // short_code is what someone reads out loud or pastes into chat,
            // so it's the field most likely to be searched for verbatim.
            ->when(
                $request->string('search')->isNotEmpty(),
                fn ($q) => $q->where(function ($sub) use ($request) {
                    $term = '%'.$request->string('search').'%';
                    $sub->where('label', 'like', $term)
                        ->orWhere('short_code', 'like', $term)
                        ->orWhereHas('event', fn ($b) => $b->where('title', 'like', $term));
                }),
            )
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Counts for the whole table, not the filtered page — they're a
        // summary of the system, and recomputing them per filter would make
        // them move as you narrow, which is the opposite of a reference point.
        $totals = [
            'all' => BoardInvite::count(),
            ...collect(self::STATUSES)
                ->mapWithKeys(fn ($status) => [$status => $this->applyStatus(BoardInvite::query(), $status)->count()])
                ->all(),
        ];

        return Inertia::render('Admin/Invites', [
            'invites' => $invites->through(fn (BoardInvite $invite) => [
                'id' => $invite->id,
                'label' => $invite->label,
                'short_code' => $invite->short_code,
                'board' => $invite->event ? ['id' => $invite->event->id, 'title' => $invite->event->title] : null,
                'creator' => $invite->creator?->displayName(),
                'created_at' => $invite->created_at,
                'expires_at' => $invite->expires_at,
                'max_uses' => $invite->max_uses,
                'use_count' => $invite->use_count,
                // How many people actually got in through this link, which is
                // not the same number as use_count if a row was ever
                // backfilled or an access later revoked.
                'accepted' => $invite->accesses_count,
                'status' => $this->statusOf($invite),
            ]),
            'statuses' => self::STATUSES,
            'boards' => $this->boardOptions(),
            'creators' => $this->creatorOptions(),
            'totals' => $totals,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'board' => $request->string('board')->toString(),
                'creator' => $request->string('creator')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    /**
     * Revoking is a delete, same as the per-board action — an invite row IS
     * the permission, so there is no "disabled" state to move it into. The
     * BoardAccess rows it already granted are untouched: revoking a link
     * stops new joins, it doesn't throw out the people who used it.
     */
    public function destroy(BoardInvite $invite)
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        // Before the delete, so the label is still readable — and logged at
        // all because this page turns revoking into a one-click admin action
        // across every board, which is exactly the kind of thing the audit
        // log exists to leave a trace of.
        AuditLog::record('invite.revoked', $invite, [
            'board' => $invite->event?->title,
            'short_code' => $invite->short_code,
            'use_count' => $invite->use_count,
        ]);

        $invite->delete();

        return back()->with('board-save', trans('admin.invite_revoked'));
    }

    /**
     * The four states are deliberately mutually exclusive and ordered:
     * expiry beats exhaustion (an expired link is dead whether or not it had
     * uses left), and "unused" only describes a link that is otherwise still
     * usable — a expired link nobody used reads as expired, not as unused.
     */
    private function statusOf(BoardInvite $invite): string
    {
        if ($invite->expires_at && $invite->expires_at->isPast()) {
            return 'expired';
        }

        if ($invite->max_uses !== null && $invite->use_count >= $invite->max_uses) {
            return 'exhausted';
        }

        return $invite->use_count === 0 ? 'unused' : 'active';
    }

    /** SQL mirror of statusOf(). */
    private function applyStatus(Builder $query, string $status): Builder
    {
        $live = fn (Builder $q) => $q
            ->where(fn ($e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($u) => $u->whereNull('max_uses')->orWhereColumn('use_count', '<', 'max_uses'));

        return match ($status) {
            'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
            'exhausted' => $query
                ->where(fn ($e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->whereNotNull('max_uses')
                ->whereColumn('use_count', '>=', 'max_uses'),
            'unused' => $query->where($live)->where('use_count', 0),
            'active' => $query->where($live)->where('use_count', '>', 0),
            default => $query,
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    private function boardOptions(): array
    {
        return BoardInvite::query()
            ->with('event:id,title')
            ->get(['event_id'])
            ->pluck('event')
            ->filter()
            ->unique('id')
            ->map(fn ($board) => ['value' => $board->id, 'label' => $board->title])
            ->sortBy('label')
            ->values()
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function creatorOptions(): array
    {
        return BoardInvite::query()
            ->with('creator:id,discord_username,nickname,email')
            ->get(['created_by'])
            ->pluck('creator')
            ->filter()
            ->unique('id')
            ->map(fn ($user) => ['value' => $user->id, 'label' => $user->displayName()])
            ->sortBy('label')
            ->values()
            ->all();
    }
}
