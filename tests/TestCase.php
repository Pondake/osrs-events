<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach the real internet. Without this a missing or
        // mistyped Http::fake() silently falls through to the live Wise Old
        // Man API — which would make the suite slow, flaky, dependent on one
        // player's actual XP, and rude to a free service that rate-limits at
        // 20 requests a minute. Now it throws instead.
        Http::preventStrayRequests();
    }
}
