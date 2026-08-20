<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laravel's stock smoke test, which had never actually run green: the landing
 * page reads site settings, so without migrations it 500s on "no such table:
 * settings". Kept rather than deleted — "the home page renders at all" is
 * worth one test, and it catches anything that breaks the shared Inertia
 * props for every page at once.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertStatus(200);
    }
}
