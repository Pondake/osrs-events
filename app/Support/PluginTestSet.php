<?php

namespace App\Support;

/**
 * The fixed set every plugin tester plays the same way, and what the plugin
 * has to send for each step. The seeder builds the card from TARGETS, the
 * admin page and the tester's checklist judge reports against SCENARIOS.
 *
 * Everything is free-to-play and safe for a hardcore account, except the
 * optional scenarios, which depend on a drop. Drops come from the OSRS Wiki
 * (Chicken, Cow, Goblin, Collection log pages, 2026-09-24).
 *
 * A name belongs to one scenario only: the plugin reports a name while its
 * square is open, so a square one scenario claims would go quiet for the next.
 */
final class PluginTestSet
{
    public const EVENT_TITLE = 'Plugin test set';

    /** wiki page id => [title, min_quantity, required_count] */
    public const TARGETS = [
        12073 => ['Chicken', 1, 1],
        11608 => ['Raw chicken', 1, 1],
        11566 => ['Feather', 15, 1],
        12398 => ['Cow', 1, 3],
        11468 => ['Cowhide', 1, 1],
        11672 => ['Goblin', 1, 1],
        214234 => ['Clue scroll (beginner)', 1, 2],
        80432 => ['Obor', 1, 1],
        115742 => ['Bryophyta', 1, 1],
        80434 => ['Hill giant club', 1, 1],
        115745 => ["Bryophyta's essence", 1, 1],
        214246 => ['Mole slippers', 1, 1],
        214248 => ['Frog slippers', 1, 1],
        214249 => ['Bear feet', 1, 1],
        214250 => ['Demon feet', 1, 1],
        214251 => ['Jester cape', 1, 1],
        214252 => ['Shoulder parrot', 1, 1],
        214253 => ["Monk's robe top (t)", 1, 1],
        214255 => ["Monk's robe (t)", 1, 1],
        214265 => ['Amulet of defence (t)', 1, 1],
        214262 => ['Sandwich lady hat', 1, 1],
        214264 => ['Sandwich lady top', 1, 1],
        214263 => ['Sandwich lady bottom', 1, 1],
        214261 => ['Rune scimitar ornament kit (Guthix)', 1, 1],
        214260 => ['Rune scimitar ornament kit (Saradomin)', 1, 1],
        214259 => ['Rune scimitar ornament kit (Zamorak)', 1, 1],
        37346 => ['Black pickaxe', 1, 1],
    ];

    /** Every collection-log item on the card: the F2P boss uniques and the beginner clue uniques. */
    public const COLLECTION_LOG = [
        'Hill giant club', "Bryophyta's essence",
        'Mole slippers', 'Frog slippers', 'Bear feet', 'Demon feet', 'Jester cape', 'Shoulder parrot',
        "Monk's robe top (t)", "Monk's robe (t)", 'Amulet of defence (t)',
        'Sandwich lady hat', 'Sandwich lady top', 'Sandwich lady bottom',
        'Rune scimitar ornament kit (Guthix)', 'Rune scimitar ornament kit (Saradomin)', 'Rune scimitar ornament kit (Zamorak)',
        'Black pickaxe',
    ];

    /**
     * What a tester does, in order, and the reports that must arrive.
     *
     * An expectation matches a report on kind, name (any of `names`) and
     * context source. `fields` must be filled in the report's context,
     * `min_quantity` is the smallest quantity that counts, `count` how many
     * distinct reports it takes, and `outcome` what the server must have
     * answered on the last of them (claim, or progress on the ones before).
     */
    public static function scenarios(): array
    {
        return [
            'connect' => ['optional' => false, 'expect' => []],
            'chicken' => ['optional' => false, 'expect' => [
                ['kind' => 'npc_kill', 'names' => ['Chicken'], 'source' => 'npc_kill', 'fields' => ['npc_id', 'npc_name', 'npc_level', 'region_id', 'items'], 'outcome' => 'claim'],
                ['kind' => 'item', 'names' => ['Raw chicken'], 'source' => 'npc_kill', 'fields' => ['npc_name', 'items'], 'outcome' => 'claim'],
            ]],
            'feathers' => ['optional' => false, 'expect' => [
                ['kind' => 'item', 'names' => ['Feather'], 'source' => 'npc_kill', 'fields' => ['npc_name'], 'min_quantity' => 15, 'outcome' => 'claim'],
            ]],
            'cows' => ['optional' => false, 'expect' => [
                ['kind' => 'npc_kill', 'names' => ['Cow'], 'source' => 'npc_kill', 'fields' => ['npc_id', 'npc_level', 'region_id', 'items'], 'count' => 3, 'outcome' => 'claim'],
                ['kind' => 'item', 'names' => ['Cowhide'], 'source' => 'npc_kill', 'fields' => ['npc_name', 'items'], 'outcome' => 'claim'],
            ]],
            'goblin' => ['optional' => false, 'expect' => [
                ['kind' => 'npc_kill', 'names' => ['Goblin'], 'source' => 'npc_kill', 'fields' => ['npc_id', 'npc_level', 'region_id'], 'outcome' => 'claim'],
            ]],
            'clue' => ['optional' => true, 'expect' => [
                ['kind' => 'item', 'names' => ['Clue scroll (beginner)'], 'source' => 'npc_kill', 'fields' => ['npc_name'], 'outcome' => 'progress'],
                ['kind' => 'npc_kill', 'names' => ['Clue Scroll (Beginner)'], 'source' => 'loot', 'fields' => ['items'], 'outcome' => 'claim'],
            ]],
            'kill_count' => ['optional' => true, 'expect' => [
                ['kind' => 'npc_kill', 'names' => ['Obor', 'Bryophyta'], 'source' => null, 'fields' => ['kill_count']],
            ]],
            'collection_log' => ['optional' => true, 'expect' => [
                ['kind' => 'item', 'names' => self::COLLECTION_LOG, 'source' => 'collection_log', 'fields' => []],
            ]],
        ];
    }
}
