# Demo videos

Scripts that record and cut the videos in `public/videos/` and the finish
jingles in `public/sounds/`. Everything is the real app with demo data:
Playwright drives the e2e environment, frames come from the Chrome DevTools
screencast, Python (Pillow) composes them, ffmpeg encodes the webm and the
FilmCraft CLI the mp4. Nothing these scripts write is committed.

## Needs

- `pnpm exec playwright install chromium` (its bundled ffmpeg encodes VP9;
  set `FFMPEG` to use another one)
- Python 3 with Pillow and numpy
- the FilmCraft CLI for mp4/m4a (`FILMCRAFT`, default
  `C:/Program Files/ArtCraft/FilmCraft/filmcraft-cli`)
- a built app (`pnpm build`) and the e2e server with workers, so the live
  stream reaches the page:

```
E2E_PORT=9600 E2E_PHP_WORKERS=4 node tests/e2e/serve.js
```

Each `art.mjs` runs artisan (tinker, `dev:login-link`) against that same
environment. The database is fresh on every server start, so seed once per run.

## How a clip is made

1. `node <clip>/art.mjs tinker <clip>/seed.php` (or `reset.php`) sets the scene.
2. `node <clip>/record.mjs` records. The page clock runs `K` times slower
   (default 5) so the screencast catches every frame; frames land in
   `<clip>/frames*/` with a `frames.json` of timestamps.
3. `python <clip>/encode.py [crf]` (or `fast.py`, which cuts the pauses
   between syncs) composes and writes the webm and a master for the mp4.
4. `node filmcraft.mjs <clip>/mp4.jsonl` writes the mp4.
5. Copy to `public/videos/<name>-<first 8 of sha256>.<ext>` and update the
   reference in the page.

| Clip | Page | Notes |
|---|---|---|
| `sl-video` | Snakes & Ladders guide | `retile.php` adjusts the board |
| `race` | Skill race guide | `wave.php` writes each sync; `fast.py` |
| `drop` | Drop race guide | as `race`, Zulrah kills |
| `bingo` | Bingo guide | player and host recorded at once |
| `create` | Clan events guide | `reset.php` seeds templates |
| `push` | Bingo guide, teams | the notification is drawn on top; `score.php` |
| `vertical` | 9:16 cuts for sharing | `bingo_seq.py` is the one-page-at-a-time bingo cut the guide and intro use |
| `intro` | Home | built from the clips above; `cards.mjs` renders the title and step cards |
| `plugin` | Home | see below |
| `setup` | Settings, RuneLite plugin | a second raw recording; `setup.py` crops each step and blurs the code and names |
| `jingles` | finish sounds | see below |

### Plugin demo

The game half comes from a raw RuneLite recording, never from these scripts.
Export the windows around the claims to frames, then record the card live:

```
RAW_VIDEO=path/to/recording.mp4 node tools/videos/filmcraft.mjs plugin/raw.jsonl
RAW_VIDEO=path/to/recording.mp4 node tools/videos/filmcraft.mjs plugin/rawd.jsonl
node tools/videos/plugin/cardlive.mjs
python tools/videos/plugin/compose.py        # 16:9
node tools/videos/plugin/bandsp.mjs && python tools/videos/plugin/vertical.py   # 9:16
```

The claim times in `compose.py` (`SEGS`) belong to that one recording; a new
recording needs new ones. The chat window is cropped out of the game image on
purpose: it shows the account name.

Headless Chromium's screencast delivers frames in CSS pixels whatever the
device scale factor, so a 2x recording made that way is a 1x one upscaled.
`plugin/cardlive.mjs` therefore captures with `page.screenshot` in a loop, at
a clock slowed 8x so the slower capture still lands every frame.

### Plugin setup

Also from a raw recording. Export the windows (`raw.jsonl`, `rawe.jsonl`) with
`RAW_VIDEO` set, render the cards with `node tools/videos/setup/cards.mjs`, then
`python tools/videos/setup/setup.py peek` and look at every frame before the
full run: the crops and blur boxes in `setup.py` keep the plugin code, account
names, the RuneLite side panel and other windows out of the picture, and they
belong to that one recording.

### Jingles

Original melodies played with the FluidR3_GM soundfont (CC BY 3.0, credited on
the About page). Fetch the instruments once into `sf/`:

```
for i in trumpet french_horn trombone timpani orchestral_harp string_ensemble_1 glockenspiel tubular_bells; do
  curl -sf -o tools/videos/sf/$i.js https://gleitz.github.io/midi-js-soundfonts/FluidR3_GM/$i-ogg.js
done
node tools/videos/jingles/sampled.mjs            # all, or one name: boss
node tools/videos/filmcraft.mjs jingles/a-boss.jsonl
```
