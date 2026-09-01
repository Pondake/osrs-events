<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Wise Old Man refused a request because we asked too often.
 *
 * Its own exception rather than another null return, because null already
 * means something specific and wrong here. `WiseOldManService::gained()`
 * answers null for "no data", and `EventStandingsService::refresh()` turns
 * that into `sync_error = 'not_tracked'` — whose message tells the player to
 * go and search their own name on wiseoldman.net. For a rate limit that is
 * advice which cannot help and blames the wrong party: the account is tracked
 * fine, we simply asked too fast.
 *
 * The service's own docblock on isKnown() already states the rule this
 * enforces for gains too — a timeout, a 500 or a rate-limit rejection must
 * never be reported as "that account doesn't exist".
 */
class WiseOldManRateLimited extends RuntimeException
{
    public function __construct(public readonly ?string $username = null)
    {
        parent::__construct($username === null
            ? 'Wise Old Man rate limit reached.'
            : "Wise Old Man rate limit reached while reading {$username}.");
    }
}
