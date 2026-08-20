<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * What Jagex actually allows in an account name: 1–12 characters, letters,
 * digits, and single spaces, underscores or hyphens between them.
 *
 * A rule object rather than an inline array because three places ask the same
 * question — registration, the post-login gate, and profile settings — and
 * three copies of a regex is three chances for them to disagree about what a
 * valid name is.
 *
 * This checks the shape, not existence. A name that cannot exist is worth
 * rejecting outright; whether a name that *could* exist actually does is a
 * question only the hiscores can answer, and Wise Old Man returns 404 for any
 * real account nobody has ever looked up there — so checking would reject
 * genuine new players. The standings page reports that state instead, per
 * participant, as "not tracked".
 */
class OsrsUsername implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = trim((string) $value);

        if ($name === '' || mb_strlen($name) > 12) {
            $fail(trans('validation.osrs_username_length'));

            return;
        }

        // Separators may not lead, trail, or repeat — " Zezima", "Zez  ima"
        // and "Zezima_" are not names anyone can log in with.
        if (! preg_match('/^[a-zA-Z0-9]+(?:[ _-][a-zA-Z0-9]+)*$/', $name)) {
            $fail(trans('validation.osrs_username_format'));
        }
    }
}
