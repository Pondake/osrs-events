<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * The task catalogue hosts pick from — reference data, not demo data.
 *
 * It lived inside DemoDataSeeder, which also invents users, events and played
 * boards, so the only way to get these rows was to fake a clan alongside them.
 * On production that left the picker empty until somebody imported a wiki page
 * by hand (WikiController::importTask writes the same table).
 *
 * Safe to re-run: titles are the key, and a row is filled rather than replaced,
 * so a description a host has edited comes back to what this file says while
 * the row keeps its id and every tile pointing at it.
 */
class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $this->sync();
    }

    /**
     * Write the catalogue and hand it back, so DemoDataSeeder can hang its
     * boards off the same rows instead of keeping a second copy.
     *
     * @return Collection<int, Task>
     */
    public function sync(): Collection
    {
        // icon_url points at real OSRS Wiki item/NPC thumbnails, looked up
        // once via the wiki's public MediaWiki API (action=query&prop=pageimages)
        // and hardcoded here — not a live fetch at seed time (no network call
        // happens when this runs), just a stable URL a real wiki-search feature
        // (TileEditModal, not yet built — see docs/backlog.md) would have
        // stored the same way. wiki_url is the plain /w/ page link — that's
        // what WikiController::importTask stores on a real wiki-sourced task,
        // and what the "your task" card and the bingo claim dialog link out
        // to (see docs/backlog.md, "Active bingo/S&L tasks need a wiki link").
        //
        // Deliberately more entries than any one board has NORMAL tiles for
        // (9x9 is the largest at ~73): seedTiles() assigns one to every
        // NORMAL tile now instead of ~30% of them, and a demo board with 73
        // tiles drawing from 14 tasks read as the same handful of cows and
        // shrimp repeated six times over.
        $tasks = [
            'Kill 50 cows' => [
                'description' => 'Head to Lumbridge or Falador and rack up 50 cow kills.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Cow_%281%29.png/100px-Cow_%281%29.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Cow',
            ],
            'Fish 100 shrimp' => [
                'description' => 'Small net fishing at any beginner spot — Lumbridge Swamp works.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Raw_shrimps_detail.png/100px-Raw_shrimps_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Shrimps',
            ],
            'Chop 300 logs' => [
                'description' => 'Any tree counts. Bring an axe you can actually swing.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Logs_detail.png/100px-Logs_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Woodcutting',
            ],
            'Mine 200 iron ore' => [
                'description' => 'The Al Kharid mine is usually the least crowded.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Iron_ore_detail.png/100px-Iron_ore_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Iron_ore',
            ],
            'Cook 100 trout' => [
                'description' => "Don't forget a fire or range nearby — raw trout doesn't count.",
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Raw_trout_detail.png/100px-Raw_trout_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Trout',
            ],
            'Craft 30 gold rings' => [
                'description' => 'Needs a furnace and gold bars — Crafting level 5.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Gold_ring_detail.png/100px-Gold_ring_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Gold_ring',
            ],
            'Fletch 200 arrows' => [
                'description' => 'Headless arrows + feathers at a fletching table, or from scratch.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Headless_arrow_detail.png/100px-Headless_arrow_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Headless_arrow',
            ],
            'Smith 50 bronze bars' => [
                'description' => 'Smithing 1 — bronze bars from tin and copper ore.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Bronze_bar_detail.png/100px-Bronze_bar_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Bronze_bar',
            ],
            'Complete a clue scroll' => [
                'description' => 'Any difficulty counts. Screenshot the reward.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Clue_scroll_%28easy%29_detail.png/100px-Clue_scroll_%28easy%29_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Clue_scroll',
            ],
            'Reach level 50 Woodcutting' => [
                'description' => 'Grinding maples is the usual mid-level route.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Maple_logs_detail.png/100px-Maple_logs_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Woodcutting',
            ],
            'Kill the Giant Mole' => [
                'description' => "Falador Park's secret entrance. Bring a spade to find it.",
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Giant_Mole.png/100px-Giant_Mole.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Giant_Mole',
            ],
            'Complete a Barbarian Assault wave' => [
                'description' => 'Solo or in a team — any completed wave counts.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Barbarian_Assault_gameplay.png/100px-Barbarian_Assault_gameplay.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Barbarian_Assault',
            ],
            'Catch 50 lobsters' => [
                'description' => 'Lobster pot fishing, Fishing level 40 required.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Lobster_detail.png/100px-Lobster_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Lobster',
            ],
            'Enchant 20 sapphire rings' => [
                'description' => "Lvl-1 Enchant — cheapest jewelry enchant in the game.",
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Sapphire_ring_detail.png/100px-Sapphire_ring_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Lvl-1_Enchant',
            ],

            // Bossing.
            'Kill Vorkath once' => [
                'description' => 'Song of the Elves required. Anti-fire and ranged gear recommended.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Vorkath.png/100px-Vorkath.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Vorkath',
            ],
            'Kill 5 Zulrah' => [
                'description' => 'Learn the rotation or bring a guide — it changes phase on you.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Zulrah.png/100px-Zulrah.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Zulrah',
            ],
            'Kill the Kraken' => [
                'description' => 'Kraken Cove, south of the Fossil Island entrance. Whirlpool phase counts.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Kraken.png/100px-Kraken.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Kraken',
            ],
            'Kill General Graardor' => [
                'description' => 'Bandos, God Wars Dungeon. Bring a team or a lot of food.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/General_Graardor.png/100px-General_Graardor.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/General_Graardor',
            ],
            'Kill Cerberus once' => [
                'description' => 'Taverley Dungeon. Watch the lava pools and the ghost phases.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Cerberus.png/100px-Cerberus.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Cerberus',
            ],
            'Defeat the Corporeal Beast' => [
                'description' => "A team boss — solo is possible but slow. Spectral spirit shield helps.",
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Corporeal_Beast.png/100px-Corporeal_Beast.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Corporeal_Beast',
            ],
            'Kill King Black Dragon' => [
                'description' => 'Wilderness or the King Black Dragon Lair — bring anti-fire either way.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/King_Black_Dragon.png/100px-King_Black_Dragon.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/King_Black_Dragon',
            ],
            'Complete a Chambers of Xeric raid' => [
                'description' => 'Solo or with a team — any completed raid counts, points aside.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Chambers_of_Xeric',
            ],
            'Kill TzTok-Jad' => [
                'description' => 'The Fight Cave. Fire cape is the proof.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/TzTok-Jad.png/100px-TzTok-Jad.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/TzTok-Jad',
            ],
            'Kill the Alchemical Hydra' => [
                'description' => 'Karuulm Slayer Dungeon, level 95 Slayer. Four phases, watch the attack switches.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Alchemical_Hydra.png/100px-Alchemical_Hydra.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Alchemical_Hydra',
            ],

            // Slayer.
            'Complete a Slayer task' => [
                'description' => 'Any assigned task, from any Slayer master, finished start to end.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Slayer',
            ],
            'Kill 50 monsters on a single Slayer task' => [
                'description' => "Doesn't need to finish the task — 50 kills against the assigned monster is enough.",
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Slayer',
            ],

            // Clues.
            'Complete a medium clue scroll' => [
                'description' => 'Screenshot the reward, same as the easy one.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Clue_scroll_%28medium%29_detail.png/100px-Clue_scroll_%28medium%29_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Clue_scroll_(medium)',
            ],
            'Complete a hard clue scroll' => [
                'description' => 'Screenshot the reward, same as the easy one.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Clue_scroll_%28hard%29_detail.png/100px-Clue_scroll_%28hard%29_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Clue_scroll_(hard)',
            ],
            'Complete an elite clue scroll' => [
                'description' => 'Screenshot the reward, same as the easy one.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Clue_scroll_%28elite%29_detail.png/100px-Clue_scroll_%28elite%29_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Clue_scroll_(elite)',
            ],

            // Minigames.
            'Complete a game of Pest Control' => [
                'description' => 'Void Knight outpost. Win or lose, a completed game counts.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Pest_Control',
            ],
            'Win a game of Castle Wars' => [
                'description' => 'Either team, either flag captured or barricade defended to a win.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Castle_Wars',
            ],
            'Complete a Tempoross kill' => [
                'description' => 'Southwest of Land\'s End. Reward pool spinning counts as complete.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Tempoross.png/100px-Tempoross.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Tempoross',
            ],
            'Complete a Wintertodt kill' => [
                'description' => 'Ver Sinhaza, north of the Feldip Hills. Any subdued Wintertodt counts.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Wintertodt.png/100px-Wintertodt.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Wintertodt',
            ],
            'Catch a Guardian at Zalcano' => [
                'description' => "Song of the Elves required. A fight that ends in a kill counts.",
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Zalcano.png/100px-Zalcano.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Zalcano',
            ],
            'Complete a Nightmare Zone session' => [
                'description' => 'Any length session, any bosses picked, points spent or not.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Nightmare_Zone',
            ],
            'Win a game of Last Man Standing' => [
                'description' => 'Any map, any bracket — the win is the proof.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Last_Man_Standing',
            ],
            'Complete a Fishing Trawler trip' => [
                'description' => 'Port Khazard. The boat has to actually stay afloat.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Fishing_Trawler',
            ],
            'Complete a Barrows run' => [
                'description' => "All six brothers, chest opened — reward roll doesn't matter.",
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Barrows',
            ],
            'Complete a game of Trouble Brewing' => [
                'description' => 'Braindeath Island. Either crew, any finished round.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Trouble_Brewing',
            ],

            // Skilling, beyond the starter set above.
            'Reach level 40 Herblore' => [
                'description' => 'Unlocks Super Attack and a good chunk of the useful potions.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Herblore',
            ],
            'Make 50 prayer potions' => [
                'description' => 'Ranarr weed + snape grass, Herblore level 38.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Prayer_potion%284%29_detail.png/100px-Prayer_potion%284%29_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Prayer_potion',
            ],
            'Cast High Level Alchemy 100 times' => [
                'description' => 'Level 55 Magic. An alchemy staff or a bank of nature runes helps.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/High_Level_Alchemy',
            ],
            'Craft 50 nature runes' => [
                'description' => 'The Runecrafting Guild or the Abyss, Runecrafting level 44.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Nature_rune_detail.png/100px-Nature_rune_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Nature_rune',
            ],
            'Complete 20 laps of a rooftop agility course' => [
                'description' => 'Any single rooftop course, any level — Draynor to Ardougne all count.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Agility',
            ],
            'Catch 30 implings' => [
                'description' => 'Puro-Puro or the open world — any impling jar counts.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Impling_jar_detail.png/100px-Impling_jar_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Hunter',
            ],
            'Build a room in a player-owned house' => [
                'description' => 'Any room, any furniture — a house with one more room than it started with.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Construction',
            ],
            'Grow a full patch of herbs' => [
                'description' => 'Plant, protect and harvest one herb patch start to finish.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Farming',
            ],
            'Bake 50 chocolate cakes' => [
                'description' => 'Cooking level 5. A range beats a fire for the burn rate.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Chocolate_cake_detail.png/100px-Chocolate_cake_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Chocolate_cake',
            ],
            'Spin 100 bow strings' => [
                'description' => 'Flax at a spinning wheel — Lumbridge or Seers\' Village both work.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Bow_string_detail.png/100px-Bow_string_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Bow_string',
            ],
            'Kill 20 Hill Giants' => [
                'description' => 'Edgeville Dungeon is the usual spot. Big bones are the tell.',
                'icon_url' => 'https://oldschool.runescape.wiki/images/thumb/Big_bones_detail.png/100px-Big_bones_detail.png',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Hill_Giant',
            ],

            // Quests & diaries.
            'Complete Dragon Slayer I' => [
                'description' => 'The quest that unlocks rune platebody and green dragonhide.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Dragon_Slayer_I',
            ],
            'Complete an Achievement Diary tier' => [
                'description' => 'Any region, any tier — Easy through Elite all count.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Achievement_Diary',
            ],
            'Complete Monkey Madness II' => [
                'description' => 'One of the longest quests in the game — budget a real session for it.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Monkey_Madness_II',
            ],
            'Complete Recipe for Disaster' => [
                'description' => 'Every subquest finished, Culinaromancer freed.',
                'wiki_url' => 'https://oldschool.runescape.wiki/w/Recipe_for_Disaster',
            ],
        ];

        // firstOrNew + manual save (not updateOrCreate) so an existing row's
        // 'id' is never included in the update payload — updateOrCreate would
        // otherwise try to overwrite the primary key with a fresh uuid() on
        // every single re-run. Filling every re-run (not just on create) is
        // what backfills wiki_url onto rows seeded before that column was
        // read here — a plain firstOrCreate would leave those permanently
        // null.
        return collect($tasks)->map(function ($data, $title) {
            $task = Task::firstOrNew(['title' => $title]);
            if (! $task->exists) {
                $task->id = (string) str()->uuid();
            }
            $task->fill($data);
            $task->save();

            return $task;
        })->values();
    }
}
