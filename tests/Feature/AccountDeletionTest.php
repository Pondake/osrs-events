<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\BoardInvite;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventStanding;
use App\Models\PlayerBoard;
use App\Models\PushSubscription;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Closing an account without taking other people's evening with it.
 *
 * The delete itself is the easy half. The hard half is that one account can be
 * the only person able to run something other people are still in, and neither
 * default is acceptable: silently ending their event, or refusing to let
 * somebody leave until they find a replacement. So the rules under test are
 * about **what survives, what is handed over, and what is refused**.
 *
 * The line is whether a thing has finished. A race that ended in July had a
 * winner and still does — that row stays, keeps the OSRS name it was scored
 * on, and loses only its link to the account.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function player(string $name): User
    {
        return User::factory()->create(['osrs_username' => $name]);
    }

    private function eventOwnedBy(User $owner, array $attributes = []): Event
    {
        $event = Event::create([
            'title' => 'Clan night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            ...$attributes,
        ]);

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        return $event;
    }

    private function service(): AccountDeletionService
    {
        return app(AccountDeletionService::class);
    }

    // ----------------------------------------------------------- preflight

    #[Test]
    public function it_lists_a_running_event_you_own_as_something_to_decide(): void
    {
        $user = $this->player('Host');
        $this->eventOwnedBy($user, ['end_date' => Carbon::now()->addWeek()]);

        $preflight = $this->service()->preflight($user);

        $this->assertCount(1, $preflight['events']);
        $this->assertSame('Clan night', $preflight['events'][0]['title']);
    }

    /**
     * A finished event needs no owner: nothing about it can change any more,
     * and an admin can still reach it. Asking somebody to rehome their
     * archive is asking a question with no useful answer.
     */
    #[Test]
    public function a_finished_event_is_not_something_to_decide(): void
    {
        $user = $this->player('Host');
        $this->eventOwnedBy($user, ['end_date' => Carbon::now()->subMonth()]);

        $preflight = $this->service()->preflight($user);

        $this->assertSame([], $preflight['events']);
        $this->assertSame(1, $preflight['keptEvents']);
    }

    /** Co-hosts first — handing it to them changes nothing anybody notices. */
    #[Test]
    public function co_hosts_are_offered_before_participants(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);

        $coHost = $this->player('CoHost');
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $coHost->id, 'is_owner' => false]);

        $player = $this->player('Player');
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $player->id]);

        $candidates = $this->service()->preflight($user)['events'][0]['candidates'];

        $this->assertCount(1, $candidates);
        $this->assertSame($coHost->id, $candidates[0]['id']);
        $this->assertSame('cohost', $candidates[0]['role']);
    }

    /** With nobody else running it, the players are the only claim on it. */
    #[Test]
    public function participants_are_offered_when_there_is_no_co_host(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);

        $player = $this->player('Player');
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $player->id]);

        $candidates = $this->service()->preflight($user)['events'][0]['candidates'];

        $this->assertSame([$player->id], array_column($candidates, 'id'));
        $this->assertSame('participant', $candidates[0]['role']);
    }

    /**
     * Same reasoning as events' co-hosts-first: a manager already has the
     * run of the team, so handing over to one changes nothing anybody
     * notices. Reported as confusing when it wasn't happening — the
     * candidate list used to be every member in storage order, with no
     * indication whether a manager or a plain member would be offered.
     */
    #[Test]
    public function a_teams_managers_are_offered_before_plain_members(): void
    {
        $user = $this->player('Owner');
        $team = Team::create(['name' => 'Iron Fist']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $manager = $this->player('Manager');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $manager->id, 'role' => TeamMember::MANAGER]);

        $member = $this->player('Member');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::MEMBER]);

        $candidates = $this->service()->preflight($user)['teams'][0]['candidates'];

        $this->assertSame([$manager->id], array_column($candidates, 'id'));
    }

    /** With no manager to hand it to, every other member is offered. */
    #[Test]
    public function a_teams_plain_members_are_offered_when_there_is_no_manager(): void
    {
        $user = $this->player('Owner');
        $team = Team::create(['name' => 'Iron Fist']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $member = $this->player('Member');
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::MEMBER]);

        $candidates = $this->service()->preflight($user)['teams'][0]['candidates'];

        $this->assertSame([$member->id], array_column($candidates, 'id'));
    }

    // -------------------------------------------------------------- refusal

    /**
     * A default here would be either "silently delete somebody else's event"
     * or "silently hand it to somebody". Both are worse than a failed request.
     */
    #[Test]
    public function it_refuses_to_delete_while_a_choice_is_unanswered(): void
    {
        $user = $this->player('Host');
        $this->eventOwnedBy($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service()->delete($user);
    }

    // ------------------------------------------------------------- handover

    #[Test]
    public function handing_an_event_over_leaves_it_running_under_its_new_owner(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);
        $heir = $this->player('Heir');

        $this->service()->delete($user, [$event->id => $heir->id]);

        $this->assertNotNull($event->fresh());
        $this->assertTrue($heir->fresh()->isEventOwner($event));
        $this->assertSame(1, $event->authors()->count());
    }

    /** The heir was already an author, so the handover must not duplicate. */
    #[Test]
    public function handing_over_to_an_existing_co_host_promotes_rather_than_duplicates(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);
        $coHost = $this->player('CoHost');
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $coHost->id, 'is_owner' => false]);

        $this->service()->delete($user, [$event->id => $coHost->id]);

        $this->assertSame(1, $event->authors()->count());
        $this->assertTrue($coHost->fresh()->isEventOwner($event));
    }

    #[Test]
    public function choosing_delete_soft_deletes_the_event_so_an_admin_can_put_it_back(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);

        $this->service()->delete($user, [$event->id => 'delete']);

        $this->assertNull(Event::find($event->id));
        $this->assertNotNull(Event::withTrashed()->find($event->id));
    }

    // ------------------------------------------------------ end it in place

    /**
     * "End it" freezes a live event rather than hiding it — the split asked
     * for after "delete the event and everyone's progress" turned out to
     * only ever soft-delete (hide, admin-restorable) and never actually touch
     * anyone's progress. This is the softer of the two: nothing about the
     * event's own row, board or progress changes at all, only its end date.
     */
    #[Test]
    public function ending_it_in_place_leaves_the_event_fully_visible(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user, ['end_date' => Carbon::now()->addWeek()]);
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        $playerBoard = PlayerBoard::create(['user_id' => $this->player('Other')->id, 'board_id' => $board->id, 'current_position' => 3]);

        $this->service()->delete($user, [$event->id => 'end']);

        $fresh = Event::find($event->id);
        $this->assertNotNull($fresh, 'the event must not be soft-deleted');
        $this->assertTrue($fresh->end_date->isPast());
        $this->assertNotNull(PlayerBoard::find($playerBoard->id), 'progress must survive');
    }

    /**
     * An event whose end date has already passed earlier TODAY is left
     * exactly where it was. `ownedLiveEvents()` still offers it as something
     * to decide — its own boundary is the calendar day, not the exact
     * instant, same as `boardEventStatus()` on the JS side — but
     * `endEventInPlace()` should not need to move a date that is already in
     * the past just because it isn't in the future either.
     */
    #[Test]
    public function ending_an_already_ended_event_does_not_move_its_date_again(): void
    {
        $user = $this->player('Host');
        $originalEnd = Carbon::now()->subHours(2);
        $event = $this->eventOwnedBy($user, ['end_date' => $originalEnd]);

        $this->service()->delete($user, [$event->id => 'end']);

        // Second precision, not an exact Carbon equalTo(): the column does
        // not store microseconds, so the round trip alone would fail an
        // exact comparison even when nothing moved it.
        $this->assertSame($originalEnd->format('Y-m-d H:i:s'), Event::find($event->id)->end_date->format('Y-m-d H:i:s'));
    }

    /**
     * The ownership row is never touched directly by "end" — it disappears
     * on its own via board_authors.user_id's cascadeOnDelete the moment
     * $user->delete() runs, same as it always has for a finished event this
     * account happened to own.
     */
    #[Test]
    public function ending_it_in_place_still_removes_the_ownership_row(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);

        $this->service()->delete($user, [$event->id => 'end']);

        $this->assertSame(0, BoardAuthor::where('event_id', $event->id)->count());
    }

    // -------------------------------------------------- delete with progress

    /**
     * The harder option — what "delete the event and everyone's progress"
     * always claimed to do and never actually did. Everyone's progress is
     * genuinely gone; the event ROW is still only soft-deleted, so an admin
     * restoring it gets its settings back, not a resurrection of what this
     * just destroyed.
     */
    #[Test]
    public function deleting_it_removes_every_players_progress(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        $other = $this->player('Other');
        $playerBoard = PlayerBoard::create(['user_id' => $other->id, 'board_id' => $board->id, 'current_position' => 3]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $other->id]);

        $this->service()->delete($user, [$event->id => 'delete']);

        $this->assertNull(PlayerBoard::find($playerBoard->id));
        $this->assertSame(0, EventParticipant::where('event_id', $event->id)->count());
        $this->assertNull(Event::find($event->id));
        $this->assertNotNull(Event::withTrashed()->find($event->id), 'the shell stays restorable');
    }

    #[Test]
    public function deleting_it_removes_standings_and_bingo_completions_too(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user, ['type' => 'BINGO']);
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 5, 'win_condition' => 'LINE']);
        $square = BingoSquare::create(['bingo_card_id' => $card->id, 'position' => 0]);
        $other = $this->player('Other');
        $completion = BingoCompletion::create(['bingo_square_id' => $square->id, 'user_id' => $other->id]);
        $standing = EventStanding::create(['event_id' => $event->id, 'user_id' => $other->id, 'username' => 'Other', 'start_value' => 0]);

        $this->service()->delete($user, [$event->id => 'delete']);

        $this->assertNull(BingoCompletion::find($completion->id));
        $this->assertNull(EventStanding::find($standing->id));
        // The square and card themselves are the event's own structure, not
        // anyone's progress — they stay, same as the event row's settings do.
        $this->assertNotNull(BingoSquare::find($square->id));
    }

    #[Test]
    public function a_team_can_be_handed_over_or_ended(): void
    {
        $user = $this->player('Owner');
        $heir = $this->player('Heir');

        $kept = Team::create(['name' => 'Kept']);
        TeamMember::create(['team_id' => $kept->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);
        TeamMember::create(['team_id' => $kept->id, 'user_id' => $heir->id, 'role' => TeamMember::MEMBER]);

        $ended = Team::create(['name' => 'Ended']);
        TeamMember::create(['team_id' => $ended->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->service()->delete($user, [], [$kept->id => $heir->id, $ended->id => 'delete']);

        $this->assertNotNull($kept->fresh());
        $this->assertSame(TeamMember::OWNER, $kept->members()->where('user_id', $heir->id)->value('role'));
        $this->assertNull(Team::find($ended->id));
    }

    // -------------------------------------------------------------- history

    /**
     * The whole point. A finished race keeps its leaderboard, and the row keeps
     * the OSRS name it was scored on — the account link is all that goes.
     */
    #[Test]
    public function a_finished_race_keeps_its_leaderboard_without_the_account(): void
    {
        $user = $this->player('Racer');
        $event = Event::create(['title' => 'Mining race', 'type' => 'SKILL_RACE', 'metric' => 'mining', 'mode' => 'SOLO']);

        $standing = EventStanding::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'username' => 'Racer',
            'gained' => 1_000_000,
        ]);

        $this->service()->delete($user);

        $fresh = $standing->fresh();

        $this->assertNotNull($fresh, 'The standings row must outlive the account.');
        $this->assertNull($fresh->user_id);
        $this->assertSame('Racer', $fresh->username);
        $this->assertSame(1_000_000, $fresh->gained);
    }

    #[Test]
    public function a_bingo_card_keeps_the_squares_that_were_scored(): void
    {
        $user = $this->player('Claimer');
        $event = $this->eventOwnedBy($this->player('Host'), ['end_date' => Carbon::now()->subMonth()]);
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 3]);
        $square = BingoSquare::create(['bingo_card_id' => $card->id, 'position' => 0, 'points' => 10]);

        $completion = BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $user->id,
            'marked_by' => $user->id,
            'status' => 'APPROVED',
        ]);

        $this->service()->delete($user);

        $fresh = $completion->fresh();

        $this->assertNotNull($fresh);
        $this->assertNull($fresh->user_id);
        $this->assertSame('APPROVED', $fresh->status);
    }

    #[Test]
    public function a_board_keeps_the_space_somebody_occupied(): void
    {
        $user = $this->player('Roller');
        $event = $this->eventOwnedBy($this->player('Host'), ['type' => 'SNAKES_LADDERS']);
        $board = Board::create(['event_id' => $event->id]);

        $playerBoard = PlayerBoard::create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'current_position' => 7,
        ]);

        $this->service()->delete($user);

        $fresh = $playerBoard->fresh();

        $this->assertNotNull($fresh);
        $this->assertNull($fresh->user_id);
        $this->assertSame(7, $fresh->current_position);
    }

    /**
     * The bug this feature had to fix on the way past.
     *
     * `board_invites.created_by` was NOT NULL with a plain `constrained()`,
     * which defaults to RESTRICT — so deleting any account that had ever made
     * an invite link failed on a foreign key violation. Since an admin
     * deleting a user was the only route that existed, deletion has been
     * broken for every host since invites shipped.
     */
    #[Test]
    public function an_account_that_handed_out_an_invite_can_still_be_deleted(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user, ['end_date' => Carbon::now()->subMonth()]);

        $invite = BoardInvite::create([
            'event_id' => $event->id,
            'created_by' => $user->id,
            'token' => 'token-123',
            'short_code' => 'ABC123',
        ]);

        $this->service()->delete($user);

        $this->assertNull(User::find($user->id));
        $this->assertNull($invite->fresh()->created_by);
    }

    // ------------------------------------------------------------- personal

    #[Test]
    public function everything_personal_goes_with_the_account(): void
    {
        $user = $this->player('Leaver');
        $event = $this->eventOwnedBy($this->player('Host'));

        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id]);
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/leaver',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        $this->service()->delete($user);

        $this->assertNull(User::find($user->id));
        $this->assertSame(0, EventParticipant::where('user_id', $user->id)->count());
        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }

    /**
     * The half that is easy to leave out: the row survives, and the page has
     * to say whose it was. An unlabelled entry reads as a rendering bug rather
     * than as somebody who left.
     */
    #[Test]
    public function a_kept_row_is_labelled_rather_than_left_blank(): void
    {
        $host = $this->player('Host');
        $user = $this->player('Leaver');
        $event = $this->eventOwnedBy($host, ['end_date' => Carbon::now()->subMonth()]);
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 3]);
        $square = BingoSquare::create(['bingo_card_id' => $card->id, 'position' => 0, 'points' => 10]);

        BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $user->id,
            'marked_by' => $user->id,
            'status' => 'APPROVED',
        ]);

        $this->service()->delete($user);

        $standings = app(\App\Services\BingoService::class)->standings($event->fresh(), $card->fresh());

        $this->assertNotEmpty($standings);
        $this->assertSame(trans('common.deleted_user'), $standings->first()['name']);
    }

    /** And the board page still renders, rather than dereferencing a gap. */
    #[Test]
    public function a_board_with_a_departed_player_still_renders(): void
    {
        $host = $this->player('Host');
        $user = $this->player('Leaver');
        $event = $this->eventOwnedBy($host, ['type' => 'SNAKES_LADDERS']);
        $board = Board::create(['event_id' => $event->id]);
        PlayerBoard::create(['board_id' => $board->id, 'user_id' => $user->id, 'current_position' => 3]);

        $this->service()->delete($user);

        $this->actingAs($host)->get("/events/{$event->id}")->assertOk();
        $this->actingAs($host)->get("/events/{$event->id}/leaderboard")->assertOk();
    }

    /**
     * The seat a walkthrough is for: signed up, has not given an OSRS name
     * yet, and wants back out.
     *
     * Every route in the settings group sits behind `require-osrs-username`,
     * which redirects any write from an account without one. So the person
     * most likely to want to leave — somebody who has just realised the site
     * wants their RuneScape name — is exactly the one who cannot.
     */
    #[Test]
    public function an_account_that_never_gave_an_osrs_name_can_still_leave(): void
    {
        $user = User::factory()->create([
            'osrs_username' => null,
            'discord_username' => 'newcomer',
            'onboarding_completed_at' => now(),
        ]);

        // The page has to be reachable at all, first.
        $this->actingAs($user)->get('/settings/account')->assertOk();

        $this->actingAs($user)
            ->delete('/settings/account', ['confirmation' => 'newcomer'])
            ->assertRedirect('/');

        $this->assertNull(User::find($user->id));
    }

    // ------------------------------------------------------------ as an admin

    /**
     * The crash this feature had to fix on the way past: until self-serve
     * deletion existed, an admin was the only way an account got removed, and
     * that route failed outright for anyone who had ever made an invite link.
     */
    #[Test]
    public function an_admin_can_delete_a_host_who_handed_out_invites(): void
    {
        $admin = $this->player('Staff');
        $admin->assignRole(\App\Models\Role::findOrCreate('ADMIN', 'web'));

        $host = $this->player('Host');
        $event = $this->eventOwnedBy($host);

        BoardInvite::create([
            'event_id' => $event->id,
            'created_by' => $host->id,
            'token' => 'token-admin',
            'short_code' => 'ADM123',
        ]);

        $this->actingAs($admin)->delete("/admin/users/{$host->id}")->assertRedirect();

        $this->assertNull(User::find($host->id));
    }

    /**
     * An admin makes no decisions on somebody else's behalf, so the event they
     * owned simply loses its owner — still reachable from /admin/events, which
     * is where the person doing this already is.
     */
    #[Test]
    public function an_admin_deleting_an_account_leaves_its_events_standing(): void
    {
        $admin = $this->player('Staff');
        $admin->assignRole(\App\Models\Role::findOrCreate('ADMIN', 'web'));

        $host = $this->player('Host');
        $event = $this->eventOwnedBy($host);

        $this->actingAs($admin)->delete("/admin/users/{$host->id}")->assertRedirect();

        $this->assertNotNull(Event::find($event->id));
        $this->assertSame(0, $event->authors()->count());
    }

    // ----------------------------------------------------------------- http

    #[Test]
    public function the_page_offers_the_decisions_that_have_to_be_made(): void
    {
        $user = $this->player('Host');
        $this->eventOwnedBy($user);

        $this->actingAs($user)
            ->get('/settings/account')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('deletion.events', 1));
    }

    /** Not a checkbox: this deletes other people's evening as well as yours. */
    #[Test]
    public function a_mistyped_confirmation_deletes_nothing(): void
    {
        $user = $this->player('Careful');

        $this->actingAs($user)
            ->delete('/settings/account', ['confirmation' => 'something else'])
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull(User::find($user->id));
    }

    #[Test]
    public function the_confirmation_is_the_osrs_name_and_case_does_not_matter(): void
    {
        $user = $this->player('Zezima');

        $this->actingAs($user)
            ->delete('/settings/account', ['confirmation' => '  zezima '])
            ->assertRedirect('/');

        $this->assertNull(User::find($user->id));
        $this->assertGuest();
    }

    /** Same rule as changing the email: a borrowed session cannot do this. */
    #[Test]
    public function an_account_with_a_password_has_to_give_it(): void
    {
        $user = $this->player('Careful');
        $user->update(['email' => 'careful@example.test', 'password' => 'sup3rSecret!']);

        $this->actingAs($user)
            ->delete('/settings/account', ['confirmation' => 'Careful'])
            ->assertSessionHasErrors('current_password');

        $this->assertNotNull(User::find($user->id));
    }

    /** A page that rendered before somebody made another event in a second tab. */
    #[Test]
    public function a_stale_page_is_refused_rather_than_half_applied(): void
    {
        $user = $this->player('Host');
        $this->eventOwnedBy($user);

        $this->actingAs($user)
            ->delete('/settings/account', ['confirmation' => 'Host'])
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull(User::find($user->id));
    }

    // --------------------------------------------- the "delete everything" fast path

    /**
     * The quick action next to the ordinary delete button: no per-item
     * decisions, everything owned gets the hard delete outright.
     */
    #[Test]
    public function delete_everything_skips_per_item_decisions_and_hard_deletes_them_all(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        $other = $this->player('Other');
        $playerBoard = PlayerBoard::create(['user_id' => $other->id, 'board_id' => $board->id, 'current_position' => 3]);

        $team = Team::create(['name' => 'Mine']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)
            ->delete('/settings/account', ['confirmation' => 'Host', 'delete_everything' => true])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
        $this->assertNull(PlayerBoard::find($playerBoard->id), 'progress must be hard-deleted, not just handed off');
        $this->assertNotNull(Event::withTrashed()->find($event->id), 'the event shell stays admin-restorable');
        $this->assertNull(Event::find($event->id));
        $this->assertNull(Team::find($team->id));
    }

    /** Explicit per-item choices are ignored when the fast path is on — it means what it says. */
    #[Test]
    public function delete_everything_ignores_any_events_or_teams_sent_alongside_it(): void
    {
        $user = $this->player('Host');
        $heir = $this->player('Heir');
        $event = $this->eventOwnedBy($user);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $heir->id, 'is_owner' => false]);

        $this->actingAs($user)->delete('/settings/account', [
            'confirmation' => 'Host',
            'delete_everything' => true,
            // A handover choice sent alongside it must not survive — the
            // fast path is "everything, hard", not "everything except what
            // I'd already picked".
            'events' => [$event->id => $heir->id],
        ])->assertRedirect('/');

        $this->assertNull(Event::find($event->id));
        $this->assertFalse($heir->fresh()->isEventOwner($event));
    }

    // -------------------------------------------------- settle one item now

    /**
     * The per-item "confirm" button beside each row — settles a single event
     * right away, with the account never touched at all. Asked for
     * explicitly: deciding a dozen events in one sitting right before an
     * irreversible account close is the wrong moment to be making a dozen
     * decisions.
     */
    #[Test]
    public function settling_one_event_as_ended_does_not_touch_the_account(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user, ['end_date' => Carbon::now()->addWeek()]);

        $this->actingAs($user)
            ->patch("/settings/account/events/{$event->id}", ['choice' => 'end'])
            ->assertRedirect();

        $this->assertNotNull(User::find($user->id), 'the account must survive');
        $this->assertTrue(Event::find($event->id)->end_date->isPast());
        $this->assertSame(0, BoardAuthor::where('event_id', $event->id)->count());
    }

    #[Test]
    public function settling_one_event_as_delete_removes_only_that_events_progress(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);
        $other = $this->player('Other');
        $playerBoard = PlayerBoard::create(['user_id' => $other->id, 'board_id' => $board->id, 'current_position' => 3]);

        $untouched = $this->eventOwnedBy($user, ['title' => 'Untouched']);

        $this->actingAs($user)
            ->patch("/settings/account/events/{$event->id}", ['choice' => 'delete'])
            ->assertRedirect();

        $this->assertNotNull(User::find($user->id));
        $this->assertNull(PlayerBoard::find($playerBoard->id));
        $this->assertNull(Event::find($event->id));
        $this->assertNotNull(Event::find($untouched->id), 'a sibling event must be untouched');
    }

    #[Test]
    public function settling_one_event_by_handover_works_on_its_own(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user);
        $heir = $this->player('Heir');
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $heir->id, 'is_owner' => false]);

        $this->actingAs($user)
            ->patch("/settings/account/events/{$event->id}", ['choice' => $heir->id])
            ->assertRedirect();

        $this->assertTrue($heir->fresh()->isEventOwner($event));
        $this->assertNotNull(User::find($user->id));
    }

    /** An event this account does not own is not this account's to settle. */
    #[Test]
    public function settling_an_event_you_do_not_own_is_refused(): void
    {
        $owner = $this->player('Owner');
        $event = $this->eventOwnedBy($owner);
        $stranger = $this->player('Stranger');

        $this->actingAs($stranger)
            ->patch("/settings/account/events/{$event->id}", ['choice' => 'end'])
            ->assertForbidden();

        $this->assertNotNull(Event::find($event->id));
    }

    /** Already-finished events are not something to settle at all — see ownedLiveEvents(). */
    #[Test]
    public function settling_an_already_finished_event_is_refused(): void
    {
        $user = $this->player('Host');
        $event = $this->eventOwnedBy($user, ['end_date' => Carbon::now()->subMonth()]);

        $this->actingAs($user)
            ->patch("/settings/account/events/{$event->id}", ['choice' => 'end'])
            ->assertForbidden();
    }

    #[Test]
    public function settling_one_team_works_on_its_own(): void
    {
        $user = $this->player('Owner');
        $team = Team::create(['name' => 'Mine']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)
            ->patch("/settings/account/teams/{$team->id}", ['choice' => 'delete'])
            ->assertRedirect();

        $this->assertNotNull(User::find($user->id));
        $this->assertNull(Team::find($team->id));
    }

    #[Test]
    public function settling_a_team_you_do_not_own_is_refused(): void
    {
        $owner = $this->player('Owner');
        $team = Team::create(['name' => 'Mine']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => TeamMember::OWNER]);
        $stranger = $this->player('Stranger');

        $this->actingAs($stranger)
            ->patch("/settings/account/teams/{$team->id}", ['choice' => 'delete'])
            ->assertForbidden();

        $this->assertNotNull(Team::find($team->id));
    }
}
