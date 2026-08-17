import { readdir, mkdir } from 'node:fs/promises';
import { dirname, join, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

/**
 * Renders every SVG in this folder to a 1200×630 PNG in `public/`.
 *
 * Social scrapers (Discord, Twitter/X, Facebook, Slack) do not render SVG, so
 * the committed PNG is the artefact that actually ships — the SVG is kept only
 * as the editable source. Run `pnpm og` after changing one.
 *
 * The SVGs use Georgia rather than the site's Cinzel, because this renders
 * through librsvg with only locally installed fonts available.
 */
const OG_WIDTH = 1200;
const OG_HEIGHT = 630;

const here = dirname(fileURLToPath(import.meta.url));
const publicDir = join(here, '..', '..', 'public');

await mkdir(publicDir, { recursive: true });

const svgs = (await readdir(here)).filter(file => file.endsWith('.svg'));

if (!svgs.length) {
  console.warn(`[og] no SVG sources found in ${here}`);
}

for (const svg of svgs) {
  const out = join(publicDir, `${basename(svg, '.svg')}.png`);

  const info = await sharp(join(here, svg), { density: 144 })
    .resize(OG_WIDTH, OG_HEIGHT, { fit: 'fill' })
    .png({ compressionLevel: 9 })
    .toFile(out);

  console.log(
    `[og] ${svg} -> public/${basename(out)} (${info.width}x${info.height}, ${info.size} bytes)`,
  );
}
