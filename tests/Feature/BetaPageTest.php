<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * /beta — the page a beta tester is handed in Discord.
 *
 * Two of its properties are the whole reason it exists and neither is
 * visible from the component: it stays readable while the pre-launch door is
 * shut (a page about getting in, readable only by people already in, would
 * be useless), and it stays out of the index and the sitemap (it describes a
 * closed beta whose password is not on it).
 *
 * Only the sitemap half is asserted here. The `noindex` meta is emitted by
 * Beta.vue's <Head>, which Laravel never renders — Inertia's SSR server does,
 * and that is not running under phpunit — so a test for it would assert
 * against HTML that has no head tags in it at all and pass or fail for
 * reasons unrelated to the page. It is verified in a browser against the SSR
 * output instead, which is the only place the claim is even meaningful.
 */
class BetaPageTest extends TestCase
{
    use RefreshDatabase;

    private function lock(): void
    {
        Setting::setMany([
            'site_lock_enabled' => true,
            'site_lock_password' => Hash::make('clan-secret'),
        ]);
    }

    #[Test]
    public function it_renders_every_track(): void
    {
        $this->get('/beta')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Beta')
                ->has('accessSteps', 3)
                ->has('hostSteps', 5)
                ->has('playerSteps', 4)
                ->has('expectations', 3)
                ->has('reportPoints', 5)
                ->has('knownIssues', 4)
                ->has('groundRules', 2));
    }

    #[Test]
    public function a_stranger_can_read_it_while_the_door_is_shut(): void
    {
        $this->lock();

        // Not a redirect to /locked, which is what every app route answers
        // in this state. The link is handed out to people who have not typed
        // the password yet — that is the entire audience.
        $this->get('/beta')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Beta'));
    }

    #[Test]
    public function it_stays_out_of_the_sitemap(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('/beta', $xml);
    }

    #[Test]
    public function the_discord_invite_reaches_the_page_only_once_an_admin_sets_one(): void
    {
        $this->get('/beta')
            ->assertInertia(fn ($page) => $page->where('site.discordInviteUrl', null));

        Setting::set('discord_invite_url', 'https://discord.gg/example');

        $this->get('/beta')
            ->assertInertia(fn ($page) => $page->where('site.discordInviteUrl', 'https://discord.gg/example'));
    }
}
