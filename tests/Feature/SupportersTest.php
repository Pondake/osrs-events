<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Supporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * /supporters and its admin list. A name is published only with consent and
 * while visible, and only an admin may change the list — a creator reaches
 * /admin but must not reach this.
 */
class SupportersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $user->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $user;
    }

    private function creator(): User
    {
        $user = User::factory()->create(['osrs_username' => 'Host']);
        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));
        $user->assignRole($role);

        return $user;
    }

    private function supporter(array $attributes = []): Supporter
    {
        return Supporter::create([
            'name' => 'Zezima',
            'roles' => ['tester'],
            'consented_at' => now(),
            'is_visible' => true,
            ...$attributes,
        ]);
    }

    /** @return array<int, array{role: string, supporters: array}> */
    private function publicGroups(): array
    {
        return $this->get('/supporters')->assertOk()->viewData('page')['props']['groups'];
    }

    private function publishedNames(): array
    {
        return collect($this->publicGroups())->flatMap(fn ($g) => array_column($g['supporters'], 'name'))->unique()->values()->all();
    }

    // ------------------------------------------------------------ public page

    #[Test]
    public function the_page_is_public_and_empty_by_default(): void
    {
        $page = $this->get('/supporters')->assertOk()->viewData('page');

        $this->assertSame('Supporters', $page['component']);
        $this->assertSame([], $page['props']['groups']);
    }

    #[Test]
    public function the_page_stays_readable_behind_the_site_lock(): void
    {
        Setting::setMany([
            'site_lock_enabled' => true,
            'site_lock_password' => Hash::make('clan-secret'),
        ]);
        $this->supporter();

        $this->get('/events')->assertRedirect('/locked');
        $this->assertSame(['Zezima'], $this->publishedNames());
    }

    #[Test]
    public function it_is_in_the_sitemap(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/supporters').'<', false);
    }

    #[Test]
    public function only_consented_and_visible_rows_are_published(): void
    {
        $this->supporter(['name' => 'Agreed']);
        $this->supporter(['name' => 'NeverAsked', 'consented_at' => null]);
        $this->supporter(['name' => 'TakenDown', 'is_visible' => false]);

        $this->assertSame(['Agreed'], $this->publishedNames());
    }

    #[Test]
    public function names_are_grouped_by_role_in_order(): void
    {
        $this->supporter(['name' => 'Second', 'roles' => ['donor', 'tester'], 'sort_order' => 2]);
        $this->supporter(['name' => 'First', 'roles' => ['tester'], 'sort_order' => 1]);

        $groups = collect($this->publicGroups())->mapWithKeys(fn ($g) => [$g['role'] => array_column($g['supporters'], 'name')]);

        $this->assertSame(['tester', 'donor'], $groups->keys()->all());
        $this->assertSame(['First', 'Second'], $groups['tester']);
        $this->assertSame(['Second'], $groups['donor']);
    }

    #[Test]
    public function the_public_page_carries_no_admin_fields(): void
    {
        $this->supporter();

        $row = $this->publicGroups()[0]['supporters'][0];

        $this->assertSame(['id', 'link', 'name'], collect($row)->keys()->sort()->values()->all());
    }

    // ------------------------------------------------------------ admin

    #[Test]
    public function a_creator_who_reaches_admin_cannot_manage_supporters(): void
    {
        $creator = $this->creator();
        $row = $this->supporter();

        $this->actingAs($creator)->get('/admin/blueprints')->assertOk();

        $this->actingAs($creator)->get('/admin/supporters')->assertForbidden();
        $this->actingAs($creator)->post('/admin/supporters', [
            'name' => 'Sneaky', 'roles' => ['donor'], 'consented' => true, 'is_visible' => true,
        ])->assertForbidden();
        $this->actingAs($creator)->patch("/admin/supporters/{$row->id}", [
            'name' => 'Renamed', 'roles' => ['donor'], 'consented' => true, 'is_visible' => true,
        ])->assertForbidden();
        $this->actingAs($creator)->delete("/admin/supporters/{$row->id}")->assertForbidden();

        $this->assertSame(1, Supporter::count());
        $this->assertSame('Zezima', $row->fresh()->name);
    }

    #[Test]
    public function a_guest_is_sent_to_login(): void
    {
        $this->get('/admin/supporters')->assertRedirect();
        $this->assertSame(0, Supporter::count());
    }

    #[Test]
    public function an_admin_adds_a_supporter_without_consent_and_it_stays_off_the_page(): void
    {
        $this->actingAs($this->admin())->post('/admin/supporters', [
            'name' => 'Lynx Titan',
            'roles' => ['ideas'],
            'link' => 'Lynx Titan#0001',
            'sort_order' => 3,
            'consented' => false,
            'is_visible' => true,
        ])->assertRedirect();

        $row = Supporter::sole();
        $this->assertNull($row->consented_at);
        $this->assertSame(['ideas'], $row->roles);
        $this->assertSame([], $this->publishedNames());
    }

    #[Test]
    public function consent_is_dated_once_and_cleared_when_withdrawn(): void
    {
        $admin = $this->admin();
        $row = $this->supporter(['consented_at' => now()->subMonth()]);
        $original = $row->consented_at;
        $payload = ['name' => 'Zezima', 'roles' => ['tester'], 'is_visible' => true];

        $this->actingAs($admin)->patch("/admin/supporters/{$row->id}", [...$payload, 'consented' => true])->assertRedirect();
        $this->assertTrue($original->equalTo($row->fresh()->consented_at));

        $this->actingAs($admin)->patch("/admin/supporters/{$row->id}", [...$payload, 'consented' => false])->assertRedirect();
        $this->assertNull($row->fresh()->consented_at);
    }

    #[Test]
    public function roles_must_come_from_the_list(): void
    {
        $this->actingAs($this->admin())->post('/admin/supporters', [
            'name' => 'Nobody', 'roles' => ['whale'], 'consented' => true, 'is_visible' => true,
        ])->assertSessionHasErrors('roles.0');

        $this->actingAs($this->admin())->post('/admin/supporters', [
            'name' => 'Nobody', 'roles' => [], 'consented' => true, 'is_visible' => true,
        ])->assertSessionHasErrors('roles');

        $this->assertSame(0, Supporter::count());
    }

    #[Test]
    public function an_admin_can_list_and_delete(): void
    {
        $admin = $this->admin();
        $row = $this->supporter(['consented_at' => null]);

        $page = $this->actingAs($admin)->get('/admin/supporters')->assertOk()->viewData('page');
        $this->assertSame('Admin/Supporters', $page['component']);
        $this->assertFalse($page['props']['supporters'][0]['consented']);

        $this->actingAs($admin)->delete("/admin/supporters/{$row->id}")->assertRedirect();
        $this->assertSame(0, Supporter::count());
    }
}
