// Runs a FilmCraft CLI script from this folder, filling in absolute paths:
// {VIDEOS} is tools/videos, {RAW} the raw game recording (env RAW_VIDEO).
//   node tools/videos/filmcraft.mjs plugin/mp4.jsonl
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

const HERE = path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Za-z]:)/, '$1');
const CLI = process.env.FILMCRAFT ?? 'C:/Program Files/ArtCraft/FilmCraft/filmcraft-cli';
const script = readFileSync(path.join(HERE, process.argv[2]), 'utf8')
    .replaceAll('{VIDEOS}', HERE.replaceAll('\\', '/'))
    .replaceAll('{RAW}', (process.env.RAW_VIDEO ?? '').replaceAll('\\', '/'));
if (script.includes('"paths":[""]')) throw new Error('Set RAW_VIDEO to the raw recording first.');
const tmp = path.join(tmpdir(), `filmcraft-${Date.now()}.jsonl`);
writeFileSync(tmp, script);
process.stdout.write(execFileSync(CLI, ['run', tmp], { encoding: 'utf8' }));
