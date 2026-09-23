<?php

namespace App\Rules;

use App\Models\OsrsAccount;
use App\Models\User;
use App\Services\OsrsIdentityService;
use App\Support\RuneliteName;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The list as a whole: no character twice, and no more than the limit.
 *
 * A list that only keeps or drops names the account already holds passes the
 * limit even above it — an admin lowering the setting takes nothing away,
 * it only stops new names coming in.
 */
class OsrsCharacterList implements ValidationRule
{
    public function __construct(private readonly User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $names = collect(is_array($value) ? $value : [])->map(fn ($name) => trim((string) $name))->filter()->values();

        foreach ($names as $i => $name) {
            if ($names->slice(0, $i)->contains(fn ($earlier) => RuneliteName::sameRsn($earlier, $name))) {
                $fail(trans('validation.osrs_character_twice', ['name' => $name]));

                return;
            }
        }

        $held = $this->user->osrsAccounts()->pluck('username');
        $adds = $names->contains(fn ($name) => ! $held->contains(fn ($old) => RuneliteName::sameRsn($old, $name)));

        if ($adds && $names->count() > OsrsIdentityService::maxCharacters()) {
            $fail(trans('validation.osrs_character_limit', ['max' => OsrsIdentityService::maxCharacters()]));
        }
    }
}
