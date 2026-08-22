<?php

namespace App\Http\Controllers;

use App\Models\BoardAccess;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Who is taking part in an event — the teams, and the people.
 *
 * Its own page rather than another panel on the event: for a clan event this
 * is a list of forty names, and it is also where team management happens
 * (handing somebody the right to run a team is a thing you do while looking
 * at who is in it, not from a settings modal three clicks away).
 *
 * **Names are not public.** A listed, open event is indexed and reachable by
 * anyone, and turning it into a directory of who plays what is not something
 * anybody opted into by joining a board game. So the page tells a stranger
 * how many are taking part and nothing else; the names appear for people who
 * are in it themselves, and for whoever runs it.
 */
class ParticipantController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $user = $request->user();

        $canEdit = $user->canEditEvent($event);

        // Everyone who has a reason to be counted. Joining is its own record
        // now and is the first source read, but the rest stay: an access row
        // means somebody was let into a private event, and the play tables
        // still hold anyone whose rows predate the join record.
        $userIds = collect()
            ->merge(EventParticipant::where('event_id', $event->id)->pluck('user_id'))
            ->merge(BoardAccess::where('event_id', $event->id)->pluck('user_id'))
            ->merge(EventStanding::where('event_id', $event->id)->pluck('user_id'))
            ->merge($event->board ? PlayerBoard::where('board_id', $event->board->id)->pluck('user_id') : collect())
            ->merge($event->authors->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        // Admins keep seeing names, even though they no longer count as
        // editors here. Whether a page will SHOW you something is a reading
        // question, and reading is the half of the admin split that did not
        // change — BoardAccessService::canBypass() already lets an admin open
        // any event, so hiding the names on the way in would protect nothing
        // and stop a moderator answering "who is actually in this".
        $named = $canEdit || $user->isAdmin() || $this->isParticipant($user, $userIds, $event);

        return Inertia::render('Events/Participants', [
            'event' => $event->only(['id', 'title', 'type', 'mode', 'access_mode', 'start_date', 'end_date']),
            'canEdit' => $canEdit,
            // Whether this viewer may see names at all. The page shows counts
            // either way, so it is never a blank wall.
            'named' => $named,
            'teams' => $this->teams($event, $user, $named),
            'participants' => $named ? $this->participants($event, $userIds) : [],
            'participantCount' => $userIds->count(),
        ]);
    }

    /**
     * Anyone already in the event sees who else is. Being able to see a
     * public event is not the same as taking part in it.
     */
    private function isParticipant(User $user, Collection $userIds, Event $event): bool
    {
        if ($userIds->contains($user->id)) {
            return true;
        }

        // A TEAM event counts membership of an assigned team, since on those
        // it is the team that plays and a member may never have a row of
        // their own.
        return $event->mode === 'TEAM' && $event->eventTeams()
            ->whereHas('team.members', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    /** The teams assigned to this event, with their members. */
    private function teams(Event $event, User $user, bool $named): array
    {
        if ($event->mode !== 'TEAM') {
            return [];
        }

        return $event->eventTeams()
            ->with(['team.members.user:id,discord_username,nickname,avatar_url'])
            ->get()
            ->map(fn ($eventTeam) => $eventTeam->team)
            ->filter()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'iconUrl' => $team->icon_url,
                'guildName' => $team->guild_name,
                'memberCount' => $team->members->count(),
                // Managing a team is decided per team, not per event — a host
                // does not automatically run somebody else's clan roster.
                'canManage' => $team->isManagedBy($user),
                'members' => $named ? $team->members->map(fn ($member) => [
                    'id' => $member->user?->id,
                    'name' => $member->user?->nickname ?: $member->user?->discord_username,
                    'avatarUrl' => $member->user?->avatar_url,
                    'role' => $member->role,
                ])->values()->all() : [],
            ])
            ->values()
            ->all();
    }

    /** @param  Collection<int, string>  $userIds */
    private function participants(Event $event, Collection $userIds): array
    {
        $authorIds = $event->authors->pluck('user_id')->all();

        return User::whereIn('id', $userIds)
            ->orderBy('discord_username')
            ->get(['id', 'discord_username', 'nickname', 'avatar_url', 'osrs_username'])
            ->map(fn (User $participant) => [
                'id' => $participant->id,
                'name' => $participant->nickname ?: $participant->discord_username,
                'osrsUsername' => $participant->osrs_username,
                'avatarUrl' => $participant->avatar_url,
                'isHost' => in_array($participant->id, $authorIds, true),
            ])
            ->values()
            ->all();
    }
}
