<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\BoardInvite;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Invite links, which had no coverage at all.
 *
 * The three endpoints are called by fetch() from the settings modal and now
 * answer in JSON. They used to return back()->with(flash), so fetch followed
 * the 302 into a full page render, the flash was spent on a response nobody
 * read, and it reappeared later as a stray toast on an unrelated action —
 * "create two, delete one" was enough to see it.
 */
class BoardInviteTest extends TestCase
{
    use RefreshDatabase;

    private function ownedEvent(User $owner): Event
    {
        $event = Event::create([
            'title' => 'Invite Board',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'INVITE',
            'is_listed' => false,
        ]);

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        return $event;
    }

    #[Test]
    public function creating_an_invite_returns_the_whole_list_not_a_redirect(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $event = $this->ownedEvent($owner);

        $response = $this->actingAs($owner)->postJson("/events/{$event->id}/invites");

        $response->assertOk()
            ->assertJsonCount(1, 'invites')
            ->assertJsonPath('openCount', 1);

        // No flash left behind to surface on somebody's next page load.
        $response->assertSessionMissing('board-save');
    }

    /**
     * An OPEN event can still hand out links.
     *
     * Every other test here uses an INVITE event, and the one reported as
     * broken was OPEN — a host wanting a link to paste into Discord for an
     * event anybody could have joined anyway. Nothing in the endpoint cares
     * about the access mode, and this says so rather than leaving the case
     * untested because it looked redundant.
     */
    #[Test]
    public function an_open_event_can_still_be_given_a_link(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $event = $this->ownedEvent($owner);
        $event->update(['access_mode' => 'OPEN', 'is_listed' => true]);

        $this->actingAs($owner)
            ->postJson("/events/{$event->id}/invites")
            ->assertOk()
            ->assertJsonCount(1, 'invites');
    }

    /** The exact sequence that corrupted the list. */ /** The exact sequence that corrupted the list. */
    #[Test]
    public function creating_two_and_revoking_one_leaves_exactly_one(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $event = $this->ownedEvent($owner);

        $this->actingAs($owner)->postJson("/events/{$event->id}/invites")->assertOk();
        $this->actingAs($owner)->postJson("/events/{$event->id}/invites")->assertOk();

        $doomed = BoardInvite::firstOrFail();

        $this->actingAs($owner)
            ->deleteJson("/events/{$event->id}/invites/{$doomed->id}")
            ->assertOk()
            ->assertJsonCount(1, 'invites')
            ->assertJsonPath('openCount', 1);

        $this->assertSame(1, BoardInvite::count());
    }

    #[Test]
    public function a_fourth_open_invite_is_refused_with_a_message(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $event = $this->ownedEvent($owner);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($owner)->postJson("/events/{$event->id}/invites")->assertOk();
        }

        $this->actingAs($owner)
            ->postJson("/events/{$event->id}/invites")
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertSame(3, BoardInvite::count());
    }

    /**
     * The limit counts only what could still let somebody in, so it is always
     * clearable — otherwise a host who used up three links could never make
     * another one.
     */
    #[Test]
    public function a_used_up_link_does_not_count_against_the_limit(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $event = $this->ownedEvent($owner);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($owner)->postJson("/events/{$event->id}/invites")->assertOk();
        }

        BoardInvite::firstOrFail()->update(['max_uses' => 1, 'use_count' => 1]);

        $this->actingAs($owner)
            ->postJson("/events/{$event->id}/invites")
            ->assertOk()
            ->assertJsonPath('openCount', 3);

        $this->assertSame(4, BoardInvite::count());
    }

    #[Test]
    public function an_expired_link_does_not_count_against_the_limit(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $event = $this->ownedEvent($owner);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($owner)->postJson("/events/{$event->id}/invites")->assertOk();
        }

        BoardInvite::firstOrFail()->update(['expires_at' => now()->subDay()]);

        $this->actingAs($owner)->postJson("/events/{$event->id}/invites")->assertOk();

        $this->assertSame(4, BoardInvite::count());
    }

    #[Test]
    public function someone_who_does_not_own_the_event_cannot_touch_its_invites(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $event = $this->ownedEvent($owner);

        $this->actingAs($owner)->postJson("/events/{$event->id}/invites")->assertOk();
        $invite = BoardInvite::firstOrFail();

        $stranger = User::factory()->create(['osrs_username' => 'Zezima']);

        $this->actingAs($stranger)->getJson("/events/{$event->id}/invites")->assertForbidden();
        $this->actingAs($stranger)->postJson("/events/{$event->id}/invites")->assertForbidden();
        $this->actingAs($stranger)->deleteJson("/events/{$event->id}/invites/{$invite->id}")->assertForbidden();

        $this->assertSame(1, BoardInvite::count());
    }

    /** An invite belonging to a different event must not be revocable here. */
    #[Test]
    public function an_invite_from_another_event_is_not_found(): void
    {
        $owner = User::factory()->create(['osrs_username' => 'Pondake']);
        $mine = $this->ownedEvent($owner);
        $theirs = $this->ownedEvent($owner);

        $this->actingAs($owner)->postJson("/events/{$theirs->id}/invites")->assertOk();
        $foreign = BoardInvite::where('event_id', $theirs->id)->firstOrFail();

        $this->actingAs($owner)
            ->deleteJson("/events/{$mine->id}/invites/{$foreign->id}")
            ->assertNotFound();

        $this->assertSame(1, BoardInvite::count());
    }
}
