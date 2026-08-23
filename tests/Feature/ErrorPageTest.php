<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The branded error page, and the two ways it silently stops being one.
 *
 * It can regress without anybody noticing: a 404 that renders correctly but
 * answers 200 is a soft 404 — the crawler indexes the dead URL and the miss
 * only shows up in Search Console weeks later. And an API client that starts
 * receiving a page of HTML where it expected JSON breaks at the caller, not
 * here. Both are pinned below.
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unknown_page_renders_the_error_page_with_a_404(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Error')
                ->where('status', 404));
    }

    /**
     * A path with more than one segment misses the `/{page}` CMS catch-all
     * and would, without the fallback route, throw before the web middleware
     * group runs — leaving the shell with no session and no shared props.
     */
    #[Test]
    public function a_deep_unknown_path_reaches_the_error_page_too(): void
    {
        $this->get('/events/no-such-event/deeper')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error'));
    }

    /**
     * A dead URL answers the same way whether the pre-launch lock is on or
     * off. `/{page}` already behaved that way (it is a public route), so
     * without the fallback being public too the two halves of "not found"
     * disagreed — one 404ing, the other redirecting to the password box.
     */
    #[Test]
    public function a_locked_site_still_serves_the_error_page(): void
    {
        Setting::setMany([
            'site_lock_enabled' => true,
            'site_lock_password' => Hash::make('clan-secret'),
        ]);

        $this->get('/no-such-page')->assertNotFound();
        $this->get('/events/no-such-event/deeper')->assertNotFound();
    }

    #[Test]
    public function an_api_request_still_gets_json(): void
    {
        $response = $this->getJson('/api/no-such-endpoint');

        $response->assertNotFound();
        $this->assertJson($response->getContent());
    }
}
