import { mkdirSync, rmSync } from 'node:fs';
import { DatabaseSync } from 'node:sqlite';
import path from 'node:path';
import { DB_FILE, STORAGE_DIR } from './env.js';

/**
 * Reads and resets rows in the suite's database directly.
 *
 * For what a test needs to arrange or check between two page loads and which
 * the app has no button for: putting an account back into a state, looking up
 * an event by title. Never for an assertion about behaviour — that is read off
 * the page.
 */
function open() {
    const db = new DatabaseSync(DB_FILE);
    db.exec('PRAGMA busy_timeout = 5000');

    return db;
}

export function query(sql, params = []) {
    const db = open();

    try {
        return db.prepare(sql).all(...params);
    } finally {
        db.close();
    }
}

export function run(sql, params = []) {
    const db = open();

    try {
        db.prepare(sql).run(...params);
    } finally {
        db.close();
    }
}

export const user = (discordUsername) =>
    query('SELECT * FROM users WHERE discord_username = ?', [discordUsername])[0];

export const userByEmail = (email) => query('SELECT * FROM users WHERE email = ?', [email])[0];

export function eventId(title) {
    const row = query('SELECT id FROM events WHERE title = ?', [title])[0];

    if (!row) throw new Error(`No event titled "${title}" in the e2e database`);

    return row.id;
}

/** Puts an account back into the state of a first visit: no name, no email (unless kept), intro pending. */
export function resetNewcomer({ keepEmail = false, where = "discord_username = 'e2e_newcomer'" } = {}) {
    run(`UPDATE users SET osrs_username = NULL, osrs_verified_at = NULL, onboarding_completed_at = NULL${keepEmail ? '' : ', email = NULL'} WHERE ${where}`);
}

/**
 * Forgets every rate limit. The limiters live in the cache and are keyed by
 * user or address — and for the un-named `throttle:N,M` routes all of them
 * share one counter — so a spec that submits a few forms inherits the
 * previous spec's hits unless it starts clean.
 */
export function clearThrottles() {
    rmSync(path.join(STORAGE_DIR, 'framework/cache/data'), { recursive: true, force: true });
    mkdirSync(path.join(STORAGE_DIR, 'framework/cache/data'), { recursive: true });
}
