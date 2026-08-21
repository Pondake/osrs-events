<?php

namespace Database\Seeders;

use App\Models\EventBlueprint;
use Illuminate\Database\Seeder;

/**
 * The starting set of event formats, kept in its own seeder rather than
 * folded into DatabaseSeeder so it can be re-run on an existing environment
 * to top the list back up:
 *
 *   php artisan db:seed --class=EventBlueprintSeeder
 *
 * Keyed on title via updateOrCreate, so re-running edits the row instead of
 * doubling the list — and so an admin's own edit to a blueprint's metric or
 * description does NOT survive a re-seed. Titles are the identity here;
 * renaming one in the admin UI creates a new row next re-seed rather than
 * updating the old one, which is the intended behaviour: a renamed format is
 * a different format.
 *
 * Deliberately mixed: some carry a type and metric, some carry only a title.
 * A blueprint is allowed to be nothing more than a name worth reusing.
 */
class EventBlueprintSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->blueprints() as $blueprint) {
            // No explicit id, unlike most create() calls in this codebase:
            // updateOrCreate() applies the second argument on UPDATE too, so
            // passing one would rewrite an existing blueprint's primary key
            // on every re-seed. HasUuids fills it in on insert anyway.
            EventBlueprint::updateOrCreate(['title' => $blueprint['title']], $blueprint);
        }
    }

    /**
     * @return array<int, array{title: string, type: ?string, metric: ?string, description: ?string}>
     */
    private function blueprints(): array
    {
        return [
            // The recurring skill formats — the reason this table exists.
            // Overall rather than a fixed skill: the month's skill is picked
            // per run, and leaving the metric on the generic one means the
            // creator changes exactly one field instead of correcting two.
            [
                'title' => 'Skill of the Week',
                'type' => 'SKILL_RACE',
                'metric' => 'overall',
                'description' => 'One skill, seven days. Most XP gained in the chosen skill takes it. Pick the skill before you publish.',
            ],
            [
                'title' => 'Skill of the Month',
                'type' => 'SKILL_RACE',
                'metric' => 'overall',
                'description' => 'A month-long grind in a single skill. Slower burn than the weekly, and the standings usually swing in the last few days.',
            ],
            [
                'title' => 'Total XP Race',
                'type' => 'SKILL_RACE',
                'metric' => 'overall',
                'description' => 'No skill restriction — every point of XP counts. The most forgiving format for a clan with mixed accounts.',
            ],
            [
                'title' => 'Slayer Grind',
                'type' => 'SKILL_RACE',
                'metric' => 'slayer',
                'description' => 'Slayer XP only. Tasks, blocks and skips are all fair game.',
            ],
            [
                'title' => 'Skilling Sunday',
                'type' => 'SKILL_RACE',
                'metric' => 'overall',
                'description' => 'A one-day sprint. Short enough that everyone can take part, and short enough that nobody falls hopelessly behind.',
            ],
            [
                'title' => 'Gathering Week',
                'type' => 'SKILL_RACE',
                'metric' => 'woodcutting',
                'description' => 'A gathering skill for the week — woodcutting, mining or fishing. Set the one you want before publishing.',
            ],
            [
                'title' => 'Combat Push',
                'type' => 'SKILL_RACE',
                'metric' => 'attack',
                'description' => 'Combat XP over a fixed window. Good filler between the bigger events.',
            ],
            [
                'title' => 'New Account Sprint',
                'type' => 'SKILL_RACE',
                'metric' => 'overall',
                'description' => 'Fresh accounts only, racing overall XP from scratch. Agree the start rules in Discord before kickoff.',
            ],

            // Boss killcount races.
            [
                'title' => 'Boss of the Week',
                'type' => 'DROP_RACE',
                'metric' => 'vorkath',
                'description' => 'Most kills of one boss inside a week. Pick the boss to suit the clan — an accessible one keeps the field wide.',
            ],
            [
                'title' => 'Boss of the Month',
                'type' => 'DROP_RACE',
                'metric' => 'zulrah',
                'description' => 'A month on a single boss. Long enough that learning it during the event is a real strategy.',
            ],
            [
                'title' => 'Raids Race',
                'type' => 'DROP_RACE',
                'metric' => 'chambers_of_xeric',
                'description' => 'Raid completions over the event window. Teams tend to organise themselves around this one.',
            ],
            [
                'title' => 'Wilderness Bosses',
                'type' => 'DROP_RACE',
                'metric' => 'callisto',
                'description' => 'A wilderness boss, with everything that implies. Expect the standings to move in bursts.',
            ],
            [
                'title' => 'Barrows Rush',
                'type' => 'DROP_RACE',
                'metric' => 'barrows_chests',
                'description' => 'Barrows chests opened. Low requirements, so almost anyone in the clan can enter.',
            ],
            [
                'title' => 'Wintertodt Weekend',
                'type' => 'DROP_RACE',
                'metric' => 'wintertodt',
                'description' => 'A weekend at the Wintertodt. Group content by nature, which makes it an easy first event for new members.',
            ],

            // Bingo cards.
            [
                'title' => 'Clan Bingo Night',
                'type' => 'BINGO',
                'metric' => null,
                'description' => 'A single evening on one card. Keep the squares achievable — a card nobody finishes is a card nobody remembers.',
            ],
            [
                'title' => 'Drop Bingo',
                'type' => 'BINGO',
                'metric' => null,
                'description' => 'Every square is a specific drop. Screenshots go to the host for approval.',
            ],
            [
                'title' => 'Weekend Bingo',
                'type' => 'BINGO',
                'metric' => null,
                'description' => 'Two days, one card, full house wins. Set the win condition before you publish.',
            ],

            // Snakes & Ladders boards.
            [
                'title' => 'Snakes & Ladders Season',
                'type' => 'SNAKES_LADDERS',
                'metric' => null,
                'description' => 'A full board run over several weeks. Give it a roll limit so it paces itself instead of finishing on day one.',
            ],
            [
                'title' => 'Clan Race to the Finish',
                'type' => 'SNAKES_LADDERS',
                'metric' => null,
                'description' => 'First to the last tile. Unlimited rolls turns this into a sprint; a daily limit turns it into a month.',
            ],

            // Title-only, on purpose: recurring names a clan reuses whose
            // format changes every time they run it.
            [
                'title' => 'Clan Anniversary Event',
                'type' => null,
                'metric' => null,
                'description' => null,
            ],
            [
                'title' => 'Summer Event',
                'type' => null,
                'metric' => null,
                'description' => null,
            ],
            [
                'title' => 'Christmas Event',
                'type' => null,
                'metric' => null,
                'description' => null,
            ],
        ];
    }
}
