<?php

namespace App\Support;

/**
 * The name the game prints in a boss's kill count chat message, per Wise Old
 * Man metric — the name the RuneLite plugin reports a kill under.
 *
 * Taken from RuneLite's ChatCommandsPlugin and the game lines in its tests,
 * not from running the game. A boss missing here has no live count and is
 * measured by Wise Old Man alone: `mimic` has no kill count line, and the
 * wording for `bryophyta`, `obor`, `mad_angel`, `maggot_king`,
 * `shellbane_gryphon` and `the_royal_titans` is unverified. The awakened
 * bosses report under their own "(awakened)" name and are left out on purpose.
 */
class BossKillNames
{
    public const BY_METRIC = [
        'abyssal_sire' => 'Abyssal Sire',
        'alchemical_hydra' => 'Alchemical Hydra',
        'amoxliatl' => 'Amoxliatl',
        'araxxor' => 'Araxxor',
        'artio' => 'Artio',
        'barrows_chests' => 'Barrows chest',
        'brutus' => 'Brutus',
        'callisto' => 'Callisto',
        'calvarion' => "Calvar'ion",
        'cerberus' => 'Cerberus',
        'chambers_of_xeric' => 'Chambers of Xeric',
        'chambers_of_xeric_challenge_mode' => 'Chambers of Xeric Challenge Mode',
        'chaos_elemental' => 'Chaos Elemental',
        'chaos_fanatic' => 'Chaos Fanatic',
        'commander_zilyana' => 'Commander Zilyana',
        'corporeal_beast' => 'Corporeal Beast',
        'crazy_archaeologist' => 'Crazy Archaeologist',
        'dagannoth_prime' => 'Dagannoth Prime',
        'dagannoth_rex' => 'Dagannoth Rex',
        'dagannoth_supreme' => 'Dagannoth Supreme',
        'deranged_archaeologist' => 'Deranged Archaeologist',
        'doom_of_mokhaiotl' => 'Doom of Mokhaiotl',
        'duke_sucellus' => 'Duke Sucellus',
        'general_graardor' => 'General Graardor',
        'giant_mole' => 'Giant Mole',
        'grotesque_guardians' => 'Grotesque Guardians',
        'hespori' => 'Hespori',
        'kalphite_queen' => 'Kalphite Queen',
        'king_black_dragon' => 'King Black Dragon',
        'kraken' => 'Kraken',
        'kreearra' => "Kree'arra",
        'kril_tsutsaroth' => "K'ril Tsutsaroth",
        'lunar_chests' => 'Lunar Chest',
        'nex' => 'Nex',
        'nightmare' => 'Nightmare',
        'phosanis_nightmare' => "Phosani's Nightmare",
        'phantom_muspah' => 'Phantom Muspah',
        'sarachnis' => 'Sarachnis',
        'scorpia' => 'Scorpia',
        'scurrius' => 'Scurrius',
        'skotizo' => 'Skotizo',
        'sol_heredit' => 'Sol Heredit',
        'spindel' => 'Spindel',
        'tempoross' => 'Tempoross',
        'the_gauntlet' => 'Gauntlet',
        'the_corrupted_gauntlet' => 'Corrupted Gauntlet',
        'the_hueycoatl' => 'Hueycoatl',
        'the_leviathan' => 'Leviathan',
        'the_whisperer' => 'Whisperer',
        'theatre_of_blood' => 'Theatre of Blood',
        'theatre_of_blood_hard_mode' => 'Theatre of Blood: Hard Mode',
        'thermonuclear_smoke_devil' => 'Thermonuclear Smoke Devil',
        'tombs_of_amascut' => 'Tombs of Amascut',
        'tombs_of_amascut_expert' => 'Tombs of Amascut: Expert Mode',
        'tzkal_zuk' => 'TzKal-Zuk',
        'tztok_jad' => 'TzTok-Jad',
        'vardorvis' => 'Vardorvis',
        'venenatis' => 'Venenatis',
        'vetion' => "Vet'ion",
        'vorkath' => 'Vorkath',
        'wintertodt' => 'Wintertodt',
        'yama' => 'Yama',
        'zalcano' => 'Zalcano',
        'zulrah' => 'Zulrah',
    ];

    /** The name as the plugin reports it, or null when this boss has no live count. */
    public static function for(?string $metric): ?string
    {
        return self::BY_METRIC[$metric] ?? null;
    }
}
