<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'discord_id' => fake()->unique()->numerify('##################'),
            'discord_username' => fake()->userName(),
            'avatar_url' => null,
            // Every account needs one now (RequireOsrsUsername), so a factory
            // that omits it produces users the app would immediately redirect
            // to the gate page.
            //
            // Built rather than taken from fake()->userName(): that returns
            // dotted handles longer than 12 characters, and neither the dots
            // nor the length are things a real RSN can have (App\Rules\
            // OsrsUsername). Name + number keeps it unique, valid, and at
            // most 12 characters.
            'osrs_username' => substr(preg_replace('/[^a-zA-Z]/', '', fake()->firstName()), 0, 6)
                .' '.fake()->unique()->numberBetween(100, 99999),
        ];
    }
}
