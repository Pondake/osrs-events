<?php

namespace App\Services;

use App\Models\BingoCompletion;
use App\Models\Event;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\NotificationCategory;
use App\Support\PushMessage;

/**
 * The two notifications a bingo card produces, and who each of them is for.
 *
 * These are the highest-signal pushes in the app, because a claim is the one
 * place a player is genuinely *waiting on another human*. They submit proof
 * and then have no way of knowing when somebody looked at it — the live
 * stream only helps while the page is open, and nobody sits on a bingo card
 * waiting for a verdict.
 */
class BingoNotifier
{
    public function __construct(private readonly PushNotifier $push) {}

    /**
     * Tell the claimant what the host decided.
     *
     * Goes to `marked_by` — whoever actually clicked — not to the team. On a
     * team card those are different questions: the claim counts for the team,
     * but the person who submitted proof and is waiting on an answer is one
     * named individual.
     *
     * A rejection carries the host's note when there is one. That note is the
     * entire value of the notification: "rejected" alone sends somebody back
     * to the card to guess what was wrong with their screenshot.
     *
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public function reviewed(Event $event, BingoCompletion $completion, ?User $reviewer = null): array
    {
        $claimant = $completion->markedBy;

        // Not to the host who just pressed the button on their own claim.
        // Telling somebody the outcome of a decision they made one second
        // ago reads as a bug, not a service.
        if ($claimant === null || ($reviewer !== null && $claimant->id === $reviewer->id)) {
            return WebPushService::emptyResult();
        }

        $square = $completion->square?->label() ?? trans('notifications.a_square');
        $approved = $completion->status === 'APPROVED';

        $body = match (true) {
            $approved => trans('notifications.push_claim_approved_body', [
                'square' => $square,
                'points' => $completion->square?->points ?? 0,
                'event' => $event->title,
            ]),
            filled($completion->review_note) => trans('notifications.push_claim_rejected_body_note', [
                'square' => $square,
                'event' => $event->title,
                'note' => $completion->review_note,
            ]),
            default => trans('notifications.push_claim_rejected_body', [
                'square' => $square,
                'event' => $event->title,
            ]),
        };

        return $this->push->toUser($claimant, new PushMessage(
            title: trans($approved ? 'notifications.push_claim_approved_title' : 'notifications.push_claim_rejected_title'),
            body: $body,
            path: "/events/{$event->id}",
            category: NotificationCategory::CLAIM_REVIEWED,
            // Per claim, not per event: two verdicts landing together are two
            // different squares and collapsing them would hide one outright.
            // This category has no throttle for the same reason.
            tag: 'claim:'.$completion->id,
        ));
    }

    /**
     * Tell the rest of the team a square landed.
     *
     * Off by default and throttled hourly per event, because on an active
     * card this is the highest-frequency thing here — a six-person team
     * clearing a 5x5 grid in an evening is twenty-five of these. The people
     * who want it really want it; nobody should get it without asking.
     *
     * Only for approved completions on team events. A solo card has no team
     * to tell, and a pending claim is not a fact yet.
     *
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public function teamScored(Event $event, BingoCompletion $completion): array
    {
        if ($completion->status !== 'APPROVED' || $completion->team_id === null) {
            return WebPushService::emptyResult();
        }

        $scorer = $completion->markedBy;

        $teammates = TeamMember::query()
            ->where('team_id', $completion->team_id)
            // Not the person who just scored — they were there.
            ->when($scorer !== null, fn ($query) => $query->where('user_id', '!=', $scorer->id))
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        if ($teammates->isEmpty()) {
            return WebPushService::emptyResult();
        }

        return $this->push->toUsers($teammates, new PushMessage(
            title: trans('notifications.push_team_activity_title'),
            body: trans('notifications.push_team_activity_body', [
                'player' => $scorer?->displayName() ?? trans('notifications.someone'),
                'square' => $completion->square?->label() ?? trans('notifications.a_square'),
                'event' => $event->title,
            ]),
            path: "/events/{$event->id}",
            category: NotificationCategory::TEAM_ACTIVITY,
            // Per event, which is also the throttle key: a burst of scoring
            // becomes one line that keeps being replaced by the newest.
            tag: 'team:'.$event->id,
        ));
    }
}
