/**
 * Pulls the skill icons this app needs out of @dava96/osrs-icons and writes
 * them to public/images/osrs/skills/ as ordinary PNGs.
 *
 *     node scripts/extract-osrs-icons.mjs
 *
 * Why extract instead of importing the package at runtime:
 *
 *  - The package is ~20,000 base64-inlined sprites. It is tree-shakeable, so
 *    importing 24 of them would be fine for the client bundle — but these
 *    icons are also rendered server-side, and a data: URI in SSR output
 *    inlines the whole sprite into every HTML response instead of letting
 *    the browser cache one file.
 *  - The values are CSS `cursor` strings (`url('data:image/png;base64,…')`),
 *    not plain data URIs, so every consumer would have to unwrap them.
 *  - Committing the PNGs means the app has no runtime dependency on an
 *    upstream package that ships 20k exports; the dependency stays a
 *    devDependency purely so this script can be re-run.
 *  - Its ESM build does not load under Node at all (see the require below).
 *
 * Licensing: the icons are Jagex/OSRS Wiki property, available under
 * CC BY-NC-SA 3.0 (the package's own licence field). Same terms the rest of
 * the OSRS artwork in this project sits under — see public/fonts/README.md
 * for the equivalent note on the game font.
 */
import { createRequire } from 'node:module';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

// createRequire, not a plain `import` — the package's ESM build uses
// extensionless relative specifiers ("./generated/icons"), which Node's own
// resolver rejects outright with ERR_MODULE_NOT_FOUND. Its CJS build is
// fine. A bundler would paper over this; Node will not, which is a third
// reason not to reach for this package at runtime.
const icons = createRequire(import.meta.url)('@dava96/osrs-icons');

const here = dirname(fileURLToPath(import.meta.url));
const skillDir = join(here, '..', 'public', 'images', 'osrs', 'skills');
const bossDir = join(here, '..', 'public', 'images', 'osrs', 'bosses');

/**
 * Keyed by the Wise Old Man metric name (Event::SKILL_METRICS), because that
 * is what the app stores and what the filename has to match for the frontend
 * to find it without a second lookup table.
 *
 * `overall` is the one that is not a skill: Wise Old Man uses it for total
 * XP, and there is no "Overall" icon in the set. The generic skills icon —
 * the one the game itself puts on the stats tab — is the honest stand-in.
 */
const SKILL_ICONS = {
    overall: 'skillsIcon',
    attack: 'attackIcon',
    defence: 'defenceIcon',
    strength: 'strengthIcon',
    hitpoints: 'hitpointsIcon',
    ranged: 'rangedIcon',
    prayer: 'prayerIcon',
    magic: 'magicIcon',
    cooking: 'cookingIcon',
    woodcutting: 'woodcuttingIcon',
    fletching: 'fletchingIcon',
    fishing: 'fishingIcon',
    firemaking: 'firemakingIcon',
    crafting: 'craftingIcon',
    smithing: 'smithingIcon',
    mining: 'miningIcon',
    herblore: 'herbloreIcon',
    agility: 'agilityIcon',
    thieving: 'thievingIcon',
    slayer: 'slayerIcon',
    farming: 'farmingIcon',
    // Wise Old Man spells it `runecrafting`; the icon set (and the game)
    // spell it `runecraft`. This map is where that difference is absorbed.
    runecrafting: 'runecraftIcon',
    hunter: 'hunterIcon',
    construction: 'constructionIcon',
};

/**
 * A boss's pet, by Wise Old Man metric name (Event::BOSS_METRICS).
 *
 * Pets rather than a signature drop, because a pet is one sprite per boss and
 * unambiguous — "Zulrah's scales" could be anything, "Pet snakeling" is
 * Zulrah. The backlog carried this as a deliberate gap for exactly that
 * reason; pets are what closed it.
 *
 * **A null is a real answer, not a hole.** Several of these bosses drop no pet
 * at all (Barrows, Bryophyta, Hespori, the Mimic, Obor, the archaeologists,
 * Lunar Chests), and several are recent enough that their pet is not in this
 * package yet. Both render as no icon, which is the honest outcome — an
 * admin can fill a gap by hand, and a wrong icon would be worse than none.
 *
 * Where a pet exists only as phase variants, one is chosen and said so: any
 * of them reads as that pet, and the alternative is no icon at all.
 */
const BOSS_PETS = {
    abyssal_sire: 'abyssalOrphan',
    // Only phase variants exist for this one; any reads as Ikkle hydra.
    alchemical_hydra: 'ikkleHydraFire',
    amoxliatl: 'moxi',
    araxxor: 'nid',
    // Artio and Callisto are the same bear, and share the one cub.
    artio: 'callistoCub',
    barrows_chests: null,
    brutus: null,
    bryophyta: null,
    callisto: 'callistoCub',
    // Calvar'ion is Vet'ion's solo form; same pet.
    calvarion: 'vetionJr',
    cerberus: 'hellpuppy',
    chambers_of_xeric: 'olmlet',
    chambers_of_xeric_challenge_mode: 'olmlet',
    chaos_elemental: 'petChaosElemental',
    // The Fanatic drops the Chaos Elemental's own pet.
    chaos_fanatic: 'petChaosElemental',
    commander_zilyana: 'petZilyana',
    corporeal_beast: 'petDarkCore',
    crazy_archaeologist: null,
    dagannoth_prime: 'petDagannothPrime',
    dagannoth_rex: 'petDagannothRex',
    dagannoth_supreme: 'petDagannothSupreme',
    deranged_archaeologist: null,
    doom_of_mokhaiotl: null,
    duke_sucellus: 'baron',
    general_graardor: 'petGeneralGraardor',
    giant_mole: 'babyMole',
    // Noon and Midnight are the pair; Noon is the one the drop is named for.
    grotesque_guardians: 'noon',
    hespori: null,
    kalphite_queen: 'kalphitePrincess',
    king_black_dragon: 'princeBlackDragon',
    kraken: 'petKraken',
    kreearra: 'petKreearra',
    kril_tsutsaroth: 'petKrilTsutsaroth',
    lunar_chests: null,
    mad_angel: null,
    maggot_king: null,
    mimic: null,
    nex: 'nexling',
    nightmare: 'littleNightmare',
    phosanis_nightmare: 'littleNightmare',
    obor: null,
    // Phase variants again; the melee form is the one on the drop.
    phantom_muspah: 'muphinMelee',
    sarachnis: 'sraracha',
    scorpia: 'scorpiasOffspring',
    scurrius: 'scurry',
    shellbane_gryphon: null,
    skotizo: 'skotos',
    sol_heredit: 'smolHeredit',
    // Spindel is Venenatis's solo form; same spiderling.
    spindel: 'venenatisSpiderling',
    tempoross: 'tinyTempor',
    the_gauntlet: 'youngllef',
    the_corrupted_gauntlet: 'corruptedYoungllef',
    the_hueycoatl: 'huberte',
    the_leviathan: 'lilviathan',
    the_royal_titans: null,
    the_whisperer: 'wisp',
    theatre_of_blood: 'lilZik',
    theatre_of_blood_hard_mode: 'lilZik',
    thermonuclear_smoke_devil: 'petSmokeDevil',
    tombs_of_amascut: 'tumekensGuardian',
    tombs_of_amascut_expert: 'tumekensGuardian',
    tzkal_zuk: 'jalNibRek',
    tztok_jad: 'tzrekJad',
    vardorvis: 'butch',
    venenatis: 'venenatisSpiderling',
    vetion: 'vetionJr',
    vorkath: 'vorki',
    wintertodt: 'phoenix',
    yama: null,
    zalcano: 'smolcano',
    zulrah: 'petSnakeling',
};

/**
 * `url('data:image/png;base64,AAAA'), auto` -> a Buffer of the decoded PNG.
 *
 * Note the trailing `, auto`: these are complete CSS `cursor` declarations,
 * fallback keyword and all, not bare url() values — so the pattern matches
 * the base64 payload wherever it sits rather than anchoring to the end.
 */
function decode(cursorValue) {
    const match = /data:image\/png;base64,([A-Za-z0-9+/=]+)/.exec(cursorValue);

    if (!match) {
        throw new Error(`unexpected icon format: ${String(cursorValue).slice(0, 60)}…`);
    }

    return Buffer.from(match[1], 'base64');
}

/**
 * Writes one directory's worth of icons and reports what it could not find.
 *
 * A null mapping is skipped silently — it means "this one has no icon and we
 * know it", which is a decision, not a failure. A NAME that does not resolve
 * is loud: that is an upstream rename, and it would otherwise surface much
 * later as one blank icon nobody connects to this script.
 */
async function extract(dir, mapping, label, folder) {
    await mkdir(dir, { recursive: true });

    const missing = [];
    let written = 0;
    let blank = 0;

    for (const [metric, exportName] of Object.entries(mapping)) {
        if (exportName === null) {
            blank += 1;
            continue;
        }

        const value = icons[exportName];

        if (typeof value !== 'string') {
            missing.push(`${metric} (no export named ${exportName})`);
            continue;
        }

        await writeFile(join(dir, `${metric}.png`), decode(value));
        written += 1;
    }

    console.log(`wrote ${written} ${label} icons to public/images/osrs/${folder}/` +
        (blank ? ` (${blank} deliberately without one)` : ''));

    return missing;
}

const missing = [
    ...await extract(skillDir, SKILL_ICONS, 'skill', 'skills'),
    ...await extract(bossDir, BOSS_PETS, 'boss', 'bosses'),
];

/**
 * Which bosses ended up with a file, written where the app can read it.
 *
 * Needed because the answer is not "all of them": fifteen have no pet, and
 * asking for a PNG that was never written is a 404 and a broken image in the
 * page. The frontend must be able to ask before it renders, and the build is
 * what makes the answer true — same contract Support/iconCatalog.js has with
 * vite.config.js.
 */
const withIcons = Object.entries(BOSS_PETS)
    .filter(([, exportName]) => exportName !== null)
    .map(([metric]) => metric);

const NL = String.fromCharCode(10);

const generated = [
    '/**',
    ' * GENERATED by scripts/extract-osrs-icons.mjs - do not edit by hand.',
    ' *',
    ' * The bosses that have a pet sprite committed under',
    ' * public/images/osrs/bosses/. The rest have no pet at all, or none in',
    ' * the icon package yet; both render without an icon rather than as a',
    ' * broken image. Re-run the script after changing BOSS_PETS.',
    ' */',
    'export const BOSSES_WITH_ICONS = new Set([',
    ...withIcons.map((m) => `    '${m}',`),
    ']);',
].join(NL) + NL;

await writeFile(join(here, '..', 'resources', 'js', 'Support', 'bossIcons.js'), generated);

console.log(`wrote resources/js/Support/bossIcons.js (${withIcons.length} bosses)`);

// Loud rather than silent: an upstream rename would otherwise show up much
// later as one blank icon in a dropdown that nobody connects to this script.
if (missing.length) {
    console.error(`MISSING (${missing.length}):\n  ${missing.join('\n  ')}`);
    process.exitCode = 1;
}
