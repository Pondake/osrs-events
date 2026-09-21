<?php

namespace App\Rules;

use App\Models\User;
use App\Services\OsrsIdentityService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The one case where a name IS refused.
 *
 * Two accounts carrying the same RSN is allowed on purpose — the migration
 * that made standings unique per event says why: nobody can tell who owns a
 * name, so letting whoever types it first keep everyone else out would hand
 * them somebody else's identity. Proof changes exactly that. An account with
 * `osrs_proven_at` has had a RuneLite client report it logged in as that
 * character, which is the one thing that does answer the ownership question
 * — so from then on the name is spoken for.
 *
 * Only forward. A name already stored on an unproven account stays where it
 * is when somebody else proves it; this stops a NEW claim, it does not go
 * back and take names away.
 */
class RsnNotProvenByAnother implements ValidationRule
{
    public function __construct(private readonly ?User $user = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app(OsrsIdentityService::class)->provenByAnother($this->user, (string) $value)) {
            $fail(trans('validation.osrs_username_proven'));
        }
    }
}
