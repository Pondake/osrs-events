<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LastSeenTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $admin;
    }

    #[Test]
    public function a_request_stamps_last_seen_without_touching_updated_at(): void
    {
        $user = User::factory()->create();
        $updatedAt = $user->updated_at;
        $this->travel(5)->minutes();

        $this->actingAs($user)->get('/');

        $user->refresh();
        $this->assertTrue($user->last_seen_at->gt(now()->subSeconds(5)));
        $this->assertTrue($user->updated_at->equalTo($updatedAt));
    }

    #[Test]
    public function last_seen_is_written_at_most_once_a_minute(): void
    {
        $seen = now()->subSeconds(30)->startOfSecond();
        $user = User::factory()->create(['last_seen_at' => $seen]);

        $this->actingAs($user)->get('/');

        $this->assertTrue($user->fresh()->last_seen_at->equalTo($seen));
    }

    #[Test]
    public function the_dashboard_lists_recently_active_users_and_who_is_online(): void
    {
        $admin = $this->admin();
        User::factory()->create(['nickname' => 'Online One', 'last_seen_at' => now()->subMinutes(2)]);
        User::factory()->create(['nickname' => 'Away One', 'last_seen_at' => now()->subHours(3)]);
        User::factory()->create(['nickname' => 'Never Seen']);

        $this->actingAs($admin)->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                // The admin's own request counts.
                ->where('onlineCount', 2)
                ->has('recentlyActiveUsers', 3)
                ->where('recentlyActiveUsers.1.name', 'Online One')
                ->where('recentlyActiveUsers.1.online', true)
                ->where('recentlyActiveUsers.2.name', 'Away One')
                ->where('recentlyActiveUsers.2.online', false)
                ->has('newestUsers', 4)
            );
    }
}
