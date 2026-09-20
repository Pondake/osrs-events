import { execFile, execFileSync } from 'node:child_process';
import { promisify } from 'node:util';
import { PHP, ROOT, phpEnv } from './env.js';

/** Runs an artisan command against the suite's own database and returns stdout. */
export function artisan(...args) {
    return execFileSync(PHP, ['artisan', ...args], { cwd: ROOT, env: phpEnv, encoding: 'utf8' });
}

const run = promisify(execFile);

/** The same, without waiting: an artisan boot costs seconds, so callers with several run them together. */
export async function artisanAsync(...args) {
    const { stdout } = await run(PHP, ['artisan', ...args], { cwd: ROOT, env: phpEnv });

    return stdout;
}
