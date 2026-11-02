<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserShowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $admin;
    }

    private function event(string $title, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title' => $title,
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ], $attributes));
    }

    #[Test]
    public function an_admin_sees_every_event_the_account_is_tied_to(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Subject']);

        $hosted = $this->event('Hosted night');
        BoardAuthor::create(['event_id' => $hosted->id, 'user_id' => $user->id, 'is_owner' => true]);

        $solo = $this->event('Solo race');
        EventParticipant::create(['event_id' => $solo->id, 'user_id' => $user->id]);

        $teamEvent = $this->event('Team bingo', ['type' => 'BINGO', 'mode' => 'TEAM']);
        $team = Team::create(['name' => 'Iron Fist']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::MEMBER]);
        BoardTeam::create(['event_id' => $teamEvent->id, 'team_id' => $team->id]);

        $deleted = $this->event('Gone event');
        EventParticipant::create(['event_id' => $deleted->id, 'user_id' => $user->id]);
        $deleted->delete();

        $this->event('Unrelated');

        $this->actingAs($this->admin())
            ->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserShow')
                ->where('profile.id', $user->id)
                ->has('events', 4)
                ->where('events', fn ($events) => collect($events)->pluck('title')->sort()->values()->all()
                    === ['Gone event', 'Hosted night', 'Solo race', 'Team bingo'])
                ->where('events', fn ($events) => collect($events)->firstWhere('title', 'Gone event')['status'] === 'deleted')
                ->where('events', fn ($events) => collect($events)->firstWhere('title', 'Team bingo')['links'][0]
                    == ['kind' => 'team', 'team' => 'Iron Fist'])
                ->has('teams', 1)
                ->has('characters', 1));
    }

    #[Test]
    public function claims_and_audit_entries_about_the_account_are_listed(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['osrs_username' => 'Subject']);

        $event = $this->event('Bingo night', ['type' => 'BINGO']);
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 3]);
        $square = BingoSquare::create(['bingo_card_id' => $card->id, 'position' => 0, 'title_override' => 'Kill Zulrah']);
        BingoCompletion::create(['bingo_square_id' => $square->id, 'user_id' => $user->id, 'marked_by' => $user->id, 'status' => 'PENDING']);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/roles", ['role' => 'EDITOR']);
        AuditLog::create(['action' => 'team.created', 'actor_label' => 'Someone else']);

        $this->actingAs($admin)
            ->get("/admin/users/{$user->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('claims', 1)
                ->where('claims.0.label', 'Kill Zulrah')
                ->where('claims.0.event.title', 'Bingo night')
                ->where('claims.0.event.deleted', false)
                ->has('activity', 1)
                ->where('activity.0.action', 'user.role_granted'));
    }

    #[Test]
    public function access_says_where_each_permission_comes_from(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Subject']);
        $user->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $editor = Role::findOrCreate('EDITOR', 'web');
        $editor->givePermissionTo(Permission::findOrCreate('canCreateTiles', 'web'));
        $user->assignRole($editor);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get("/admin/users/{$user->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.access', [
                    ['key' => 'canCreateBoards', 'source' => 'direct', 'role' => null],
                    ['key' => 'canCreateTiles', 'source' => 'role', 'role' => 'EDITOR'],
                ]));

        $this->actingAs($admin)
            ->get("/admin/users/{$admin->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.access.0.source', 'admin')
                ->where('profile.access.1.source', 'admin'));
    }

    #[Test]
    public function only_an_admin_can_open_it(): void
    {
        $user = User::factory()->create(['osrs_username' => 'Subject']);

        $creator = User::factory()->create(['osrs_username' => 'Creator']);
        $creator->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));

        $editor = User::factory()->create(['osrs_username' => 'Editor']);
        $editor->givePermissionTo(Permission::findOrCreate('canCreateTiles', 'web'));

        $this->actingAs($creator)->get("/admin/users/{$user->id}")->assertForbidden();
        $this->actingAs($editor)->get("/admin/users/{$user->id}")->assertForbidden();
        $this->actingAs($user)->get("/admin/users/{$user->id}")->assertForbidden();
    }
}
