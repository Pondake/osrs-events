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
const outDir = join(here, '..', 'public', 'images', 'osrs', 'skills');

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

await mkdir(outDir, { recursive: true });

const missing = [];
let written = 0;

for (const [metric, exportName] of Object.entries(SKILL_ICONS)) {
    const value = icons[exportName];

    if (typeof value !== 'string') {
        missing.push(`${metric} (no export named ${exportName})`);
        continue;
    }

    await writeFile(join(outDir, `${metric}.png`), decode(value));
    written += 1;
}

console.log(`wrote ${written} skill icons to public/images/osrs/skills/`);

// Loud rather than silent: an upstream rename would otherwise show up much
// later as one blank icon in a dropdown that nobody connects to this script.
if (missing.length) {
    console.error(`MISSING (${missing.length}):\n  ${missing.join('\n  ')}`);
    process.exitCode = 1;
}
