<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * /discord — the short link to the server.
 *
 * The indirection is the point: the invite URL belongs to one environment's
 * database and never to this repository, so every "join us" on the site
 * points at this path and the actual invite is a form field. Three
 * properties follow from that and none of them are obvious from the route:
 * it 404s rather than redirecting home when nothing is set, it sends the
 * visitor off-site rather than prefixing the app's own origin, and it stays
 * open while the pre-launch door is shut — the server is where somebody
 * without the password goes to ask for it.
 */
class DiscordLinkTest extends TestCase
{
    use RefreshDatabase;

    private const INVITE = 'https://discord.gg/example';

    private function lock(): void
    {
        Setting::setMany([
            'site_lock_enabled' => true,
            'site_lock_password' => Hash::make('clan-secret'),
        ]);
    }

    #[Test]
    public function it_sends_the_visitor_to_the_invite(): void
    {
        Setting::set('discord_invite_url', self::INVITE);

        // The full URL, not '/'.self::INVITE — away() is what keeps this
        // from being resolved against the app's own origin.
        $this->get('/discord')->assertRedirect(self::INVITE);
    }

    #[Test]
    public function it_is_not_a_page_until_an_admin_sets_an_invite(): void
    {
        $this->get('/discord')->assertNotFound();
    }

    #[Test]
    public function a_stranger_can_follow_it_while_the_door_is_shut(): void
    {
        $this->lock();
        Setting::set('discord_invite_url', self::INVITE);

        $this->get('/discord')->assertRedirect(self::INVITE);
    }

    #[Test]
    public function the_lock_screen_offers_it_only_once_an_admin_sets_one(): void
    {
        $this->lock();

        $this->get('/events')
            ->assertRedirect('/locked');

        $this->get('/locked')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('SiteLock')
                ->where('site.discordInviteUrl', null));

        Setting::set('discord_invite_url', self::INVITE);

        $this->get('/locked')
            ->assertInertia(fn ($page) => $page->where('site.discordInviteUrl', self::INVITE));
    }

    #[Test]
    public function it_stays_out_of_the_sitemap(): void
    {
        Setting::set('discord_invite_url', self::INVITE);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        // A redirect has nothing to index, and the invite itself must never
        // be reachable from a file a crawler reads.
        $this->assertStringNotContainsString('/discord', $xml);
        $this->assertStringNotContainsString('discord.gg', $xml);
    }
}
