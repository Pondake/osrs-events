<?php

namespace Tests\Feature;

use App\Models\BossIcon;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use App\Services\BossIconService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Which picture a boss gets, and who may change it.
 *
 * The rule worth pinning down is the precedence: an admin's override beats the
 * committed pet sprite, and neither existing means no icon rather than a
 * broken one. Two of the 71 bosses have a pet on the wiki that the icon
 * package has not shipped, which is the whole reason the override exists.
 */
class BossIconTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $user->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $user;
    }

    private function icons(): BossIconService
    {
        return app(BossIconService::class);
    }

    /** @return array{metric: string, url: string|null, source: string} */
    private function entryFor(string $metric): array
    {
        return collect($this->icons()->all())->firstWhere('metric', $metric);
    }

    // ------------------------------------------------------------ resolution

    #[Test]
    public function a_boss_with_a_committed_sprite_uses_it(): void
    {
        $entry = $this->entryFor('zulrah');

        $this->assertSame('/images/osrs/bosses/zulrah.png', $entry['url']);
        $this->assertSame('sprite', $entry['source']);
    }

    /**
     * Aggy (Mad Angel) and Bran (The Royal Titans) are the live examples: a
     * pet exists on the wiki, the package has not shipped it, and nothing
     * should invent one.
     */
    #[Test]
    public function a_boss_without_a_sprite_has_no_icon_rather_than_a_broken_one(): void
    {
        $entry = $this->entryFor('mad_angel');

        $this->assertNull($entry['url']);
        $this->assertSame('none', $entry['source']);
    }

    #[Test]
    public function an_override_beats_the_committed_sprite(): void
    {
        BossIcon::create([
            'metric' => 'zulrah',
            'icon_url' => 'https://oldschool.runescape.wiki/images/Pet_snakeling.png',
        ]);

        $entry = $this->entryFor('zulrah');

        $this->assertSame('https://oldschool.runescape.wiki/images/Pet_snakeling.png', $entry['url']);
        $this->assertSame('custom', $entry['source']);
    }

    /** Every boss is listed, with or without an icon — the page fixes gaps. */
    #[Test]
    public function every_boss_is_listed(): void
    {
        $this->assertCount(count(Event::BOSS_METRICS), $this->icons()->all());
    }

    /** Only the overrides travel to the browser; the sprites are already known. */
    #[Test]
    public function only_overrides_are_shared_with_the_client(): void
    {
        BossIcon::create(['metric' => 'mad_angel', 'icon_url' => 'https://example.com/aggy.png']);

        $this->assertSame(['mad_angel' => 'https://example.com/aggy.png'], $this->icons()->overrides());
    }

    // ---------------------------------------------------------------- the CRUD

    #[Test]
    public function an_admin_can_set_and_reset_a_boss_icon(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/boss-icons', [
            'metric' => 'mad_angel',
            'icon_url' => 'https://oldschool.runescape.wiki/images/Aggy.png',
        ])->assertSessionHasNoErrors();

        $this->assertSame('custom', $this->entryFor('mad_angel')['source']);

        $this->actingAs($admin)->delete('/admin/boss-icons/mad_angel')->assertSessionHasNoErrors();

        $this->assertSame('none', $this->entryFor('mad_angel')['source']);
    }

    /** Saving twice for one boss updates rather than duplicating. */
    #[Test]
    public function setting_the_same_boss_twice_replaces_the_first(): void
    {
        $admin = $this->admin();

        foreach (['first', 'second'] as $which) {
            $this->actingAs($admin)->put('/admin/boss-icons', [
                'metric' => 'yama',
                'icon_url' => "https://example.com/{$which}.png",
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(1, BossIcon::where('metric', 'yama')->count());
        $this->assertSame('https://example.com/second.png', $this->entryFor('yama')['url']);
    }

    /** A metric that is not a boss must never get a row nothing can render. */
    #[Test]
    public function an_unknown_metric_is_refused(): void
    {
        $this->actingAs($this->admin())->put('/admin/boss-icons', [
            'metric' => 'not_a_boss',
            'icon_url' => 'https://example.com/x.png',
        ])->assertSessionHasErrors('metric');
    }

    /** http/https only — anything else is a scheme no <img> should be handed. */
    #[Test]
    public function a_non_http_url_is_refused(): void
    {
        $this->actingAs($this->admin())->put('/admin/boss-icons', [
            'metric' => 'yama',
            'icon_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('icon_url');

        $this->assertSame(0, BossIcon::count());
    }

    #[Test]
    public function it_is_shut_to_an_ordinary_player(): void
    {
        $player = User::factory()->create(['osrs_username' => 'Pondake']);

        $this->actingAs($player)->get('/admin/boss-icons')->assertForbidden();
        $this->actingAs($player)->put('/admin/boss-icons', [
            'metric' => 'yama',
            'icon_url' => 'https://example.com/x.png',
        ])->assertForbidden();
    }

    /**
     * Its own test, because actingAs() sticks for the rest of a test method —
     * asserting the signed-out case after a signed-in one in the same test
     * measures the signed-in session again and quietly passes for the wrong
     * reason. (It did, on the first attempt: a 403 read as "shut to guests".)
     */
    #[Test]
    public function it_sends_a_signed_out_visitor_to_the_login_page(): void
    {
        $this->get('/admin/boss-icons')->assertRedirect('/login');
    }
}
