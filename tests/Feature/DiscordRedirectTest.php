<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Discord OAuth kickoff has to leave the app, and leaving the app is the
 * one thing an Inertia visit cannot do with an ordinary redirect.
 *
 * A bare 302 to discord.com looks completely correct server-side and in curl.
 * It only fails in a browser, and only when the click came from a component
 * @nuxt/ui routed through Inertia: the XHR follows the redirect cross-origin,
 * carrying X-Inertia and X-XSRF-TOKEN, which preflights and is refused. The
 * user gets "AxiosError: Network Error" and the server logs nothing.
 *
 * So both directions are pinned here — 409 for an Inertia request, 302 for a
 * plain one — because the wrong one of those is invisible in every test that
 * does not specifically look for it.
 */
class DiscordRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const AUTHORIZE_URL = 'https://discord.com/api/oauth2/authorize';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.discord', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/discord/callback',
        ]);
    }

    /**
     * Inertia answers a GET whose version does not match with its OWN 409 +
     * X-Inertia-Location pointing back at the same URL — indistinguishable
     * from the response under test if you only assert the status code. So
     * the version is computed exactly as the middleware computes it (a hash
     * of the built manifest), and every assertion below checks the header
     * VALUE, not just the 409.
     */
    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()),
        ];
    }

    public function test_an_inertia_visit_gets_a_409_with_the_discord_url_in_the_location_header(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders())->get('/auth/discord/redirect');

        // 409 + X-Inertia-Location is the only thing Inertia's client turns
        // into a real browser navigation. A 302 here is the bug.
        $response->assertStatus(409);
        $this->assertStringStartsWith(
            self::AUTHORIZE_URL,
            $response->headers->get('X-Inertia-Location'),
        );
    }

    public function test_a_plain_request_still_gets_an_ordinary_redirect(): void
    {
        $response = $this->get('/auth/discord/redirect');

        $response->assertStatus(302);
        $this->assertStringStartsWith(
            self::AUTHORIZE_URL,
            $response->headers->get('Location'),
        );
    }

    public function test_the_requested_scopes_replace_the_drivers_defaults(): void
    {
        // setScopes() vs scopes(): the latter merges, quietly adding `email`
        // to what this app asks Discord for. Asserted on the real URL rather
        // than trusted, since the difference is invisible until you read it.
        $location = $this->get('/auth/discord/redirect')->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);

        $this->assertSame('identify guilds', $query['scope'] ?? null);
    }

    /**
     * The reason `login` names the page and not the OAuth kickoff.
     *
     * Laravel's auth middleware, redirect()->guest() and every "sign in"
     * CTA all resolve route('login'). While that pointed at the Discord
     * redirect, all of them dropped the visitor straight into an OAuth
     * consent screen — a dead end for anyone holding an email/password
     * account, since the form they needed was never reachable from there.
     */
    public function test_a_guest_hitting_a_protected_page_lands_on_the_login_page_not_discord(): void
    {
        $this->get('/my-events')->assertRedirect('/login');
    }

    public function test_connecting_discord_to_an_existing_account_also_leaves_via_inertia_location(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->get('/settings/account/discord/connect');

        $response->assertStatus(409);
        $this->assertStringStartsWith(
            self::AUTHORIZE_URL,
            $response->headers->get('X-Inertia-Location'),
        );
    }
}
