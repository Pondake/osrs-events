// Puts a soundtrack under the two videos that have one (the intro and the plugin
// demo); the scripts that render them write silent video.
//   node tools/videos/sound.mjs intro  <video.mp4> <video.webm>
//   SOUND_FROM=<9:16 cut> node tools/videos/sound.mjs plugin <video.mp4> <video.webm>
// Writes public/videos/<name>-<hash>.{mp4,webm}. The video stream is copied as is.
import { execFileSync, spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { copyFileSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const REPO = path.resolve(HERE, '../..');
const FF = process.env.FFMPEG ?? 'ffmpeg';
const LUFS = -14;

// The finish jingles the site plays, at seconds into the intro: the claim going
// in, the bingo line, and Bob taking first place in the race.
const INTRO = [
    ['finish-quest-f57e76cc.m4a', 23.6, -6],
    ['finish-triumph-e2ebbc89.m4a', 29.2, 0],
    ['finish-medal-a0467a91.m4a', 43.3, 0],
];

const ff = (args) => execFileSync(FF, ['-hide_banner', '-y', '-loglevel', 'error', ...args], { encoding: 'utf8' });
const duration = (file) => Number(execFileSync(FF.replace(/ffmpeg(\.exe)?$/, 'ffprobe$1'),
    ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', file], { encoding: 'utf8' }));

// ebur128 reports on stderr, which execFileSync only hands back on failure.
function integrated(file) {
    const r = spawnSync(FF, ['-hide_banner', '-nostats', '-i', file, '-af', 'ebur128', '-f', 'null', '-'], { encoding: 'utf8' });
    return Number(/I:\s+(-?[\d.]+) LUFS/.exec(r.stderr.split('Summary:').pop())[1]);
}

function introTrack(seconds, out) {
    const inputs = INTRO.flatMap(([file]) => ['-i', path.join(REPO, 'public/sounds', file)]);
    const parts = INTRO.map(([, at, gain], i) => `[${i}:a]volume=${gain}dB,adelay=${Math.round(at * 1000)}:all=1[j${i}]`);
    const mix = `${INTRO.map((_, i) => `[j${i}]`).join('')}amix=inputs=${INTRO.length}:normalize=0,apad=whole_dur=${seconds},atrim=end=${seconds}`;
    const raw = out.replace(/\.wav$/, '-raw.wav');
    ff([...inputs, '-filter_complex', [...parts, mix].join(';'), '-ar', '48000', '-ac', '2', raw]);
    ff(['-i', raw, '-af', `volume=${(LUFS - integrated(raw)).toFixed(2)}dB,alimiter=limit=0.84:level=false`, out]);
    rmSync(raw);
}

function publish(name, file) {
    const hash = createHash('sha256').update(readFileSync(file)).digest('hex').slice(0, 8);
    const target = `${name}-${hash}${path.extname(file)}`;
    copyFileSync(file, path.join(REPO, 'public/videos', target));
    console.log(target, integrated(file).toFixed(1), 'LUFS');
}

const [clip, mp4, webm] = process.argv.slice(2);
const name = { intro: 'intro', plugin: 'plugin-demo' }[clip];
if (!name || !mp4 || !webm) throw new Error('Usage: sound.mjs intro|plugin <video.mp4> <video.webm>');

const tmp = path.join(tmpdir(), `sound-${Date.now()}`);
let track;
if (clip === 'intro') {
    track = `${tmp}.wav`;
    introTrack(duration(mp4), track);
} else {
    // The 9:16 cut is rendered from the same timeline, so its mixed soundtrack
    // (game audio plus the jingles) lines up with the 16:9 frame for frame.
    if (!process.env.SOUND_FROM) throw new Error('Set SOUND_FROM to the 9:16 cut with sound (plugin/reddit.jsonl).');
    track = process.env.SOUND_FROM;
}

ff(['-i', mp4, '-i', track, '-map', '0:v', '-map', '1:a', '-c:v', 'copy', '-c:a', 'aac', '-b:a', '160k', '-shortest', '-movflags', '+faststart', `${tmp}.mp4`]);
ff(['-i', webm, '-i', track, '-map', '0:v', '-map', '1:a', '-c:v', 'copy', '-c:a', 'libopus', '-b:a', '128k', '-shortest', `${tmp}.webm`]);
publish(name, `${tmp}.mp4`);
publish(name, `${tmp}.webm`);
for (const f of [`${tmp}.mp4`, `${tmp}.webm`, `${tmp}.wav`]) rmSync(f, { force: true });
