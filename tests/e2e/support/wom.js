import { rmSync, writeFileSync } from 'node:fs';
import { GAINS_FILE } from './env.js';

/**
 * What the Wise Old Man stand-in (serve.js) answers for a name's gains, keyed
 * by OSRS name and then by metric. A name that is not here gets Wise Old Man's
 * own answer for a player it has no history of: a 404.
 *
 *     setGains({ 'E2E Member': { vorkath: 12 } });
 */
export function setGains(gains) {
    writeFileSync(GAINS_FILE, JSON.stringify(gains));
}

export function clearGains() {
    rmSync(GAINS_FILE, { force: true });
}
