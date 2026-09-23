import { randomUUID } from 'node:crypto';
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
    // The characters live in their own table too (User::booted() keeps the
    // main in step, but raw SQL goes past it).
    run(`DELETE FROM osrs_accounts WHERE user_id IN (SELECT id FROM users WHERE ${where})`);
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

/**
 * Takes an event back to nobody having played it: no participants, claims,
 * standings, finishes or boards. For a spec that plays an event, so a retry
 * starts from the same place the first run did.
 */
export function resetEvent(title) {
    const id = eventId(title);
    const card = `SELECT id FROM bingo_squares WHERE bingo_card_id IN (SELECT id FROM bingo_cards WHERE event_id = ?)`;

    run(`DELETE FROM bingo_completions WHERE bingo_square_id IN (${card})`, [id]);
    run('DELETE FROM event_standings WHERE event_id = ?', [id]);
    run('DELETE FROM event_finishes WHERE event_id = ?', [id]);
    run('DELETE FROM event_participants WHERE event_id = ?', [id]);
    run('UPDATE events SET standings_stale_since = NULL, closed_at = NULL WHERE id = ?', [id]);
}

/**
 * Puts every site setting back to its default, and forgets the cached copy
 * the app reads them from. For a spec that changes them, so a failure half
 * way through cannot leave the site locked for everything that runs after.
 */
export function resetSettings() {
    run('DELETE FROM settings');
    clearThrottles();
}

/** The page the content specs edit, back to how the seeder made it. */
export function resetNotesPage() {
    run(
        `UPDATE pages SET title = 'E2E Notes', subtitle = 'A page to edit.', is_published = 1,
         blocks = '[{"type":"prose","props":{"text":"The original paragraph."}}]' WHERE slug = 'e2e-notes'`,
    );
}

/** Every task a spec made, trashed ones included. Specs name theirs "E2E …". */
export function removeE2eTasks() {
    run("DELETE FROM tasks WHERE title LIKE 'E2E %'");
}

/** Every blueprint a spec made. Specs name theirs "E2E …". */
export function removeE2eBlueprints() {
    run("DELETE FROM event_blueprints WHERE title LIKE 'E2E %'");
}

/** Nobody invited, nobody let in: the invite-only event back to how the seeder left it. */
export function resetInvites(title) {
    const id = eventId(title);

    run('DELETE FROM board_accesses WHERE event_id = ?', [id]);
    run('DELETE FROM event_participants WHERE event_id = ?', [id]);
    run('DELETE FROM board_invites WHERE event_id = ?', [id]);
}

/** Every boss icon an admin set, and every suggestion waiting for one. */
export function clearBossIcons() {
    run('DELETE FROM boss_icons');
}

/** A suggestion from the weekly check, waiting for an admin to say yes or no. */
export function suggestBossIcon(metric, url) {
    run("INSERT INTO boss_icons (id, metric, suggested_url, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))", [randomUUID(), metric, url]);
}

const EXTRA_USERS = ['e2e_victim', 'e2e_admin2'];

/**
 * The accounts the user-management spec changes, back to how the seeder made
 * them: no roles or permissions for the one an admin edits, and none of the
 * accounts it creates and deletes. Clears the app's cache too, since roles
 * and permissions are cached there.
 */
export function resetUsers() {
    const ids = `SELECT id FROM users WHERE discord_username IN ('e2e_roled', ${EXTRA_USERS.map((name) => `'${name}'`).join(', ')})`;

    run(`DELETE FROM model_has_roles WHERE model_uuid IN (${ids})`);
    run(`DELETE FROM model_has_permissions WHERE model_uuid IN (${ids})`);
    run(`DELETE FROM users WHERE discord_username IN (${EXTRA_USERS.map((name) => `'${name}'`).join(', ')})`);
    clearThrottles();
}

/** An account an admin can act on, made without going through the site. */
export function addUser(username, nickname, { admin = false } = {}) {
    const id = randomUUID();

    run("INSERT INTO users (id, discord_id, discord_username, nickname, created_at, updated_at) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))", [id, `e2e-${username}`, username, nickname]);

    if (admin) {
        run("INSERT INTO model_has_roles (role_id, model_type, model_uuid) SELECT id, 'App\\Models\\User', ? FROM roles WHERE name = 'ADMIN'", [id]);
    }

    return id;
}

/** The event the admin list edits, back to its name and to running. */
export function resetAdminEvent() {
    run(
        `UPDATE events SET title = 'E2E Admin Event', deleted_at = NULL, paused_at = NULL, pause_reason = NULL
         WHERE title IN ('E2E Admin Event', 'E2E Admin Event, renamed')`,
    );
    run(
        `UPDATE bingo_cards SET size = 3, win_condition = 'FULL_HOUSE', requires_approval = 0
         WHERE event_id IN (SELECT id FROM events WHERE title = 'E2E Admin Event')`,
    );
    run(
        `DELETE FROM bingo_squares WHERE position >= 9 AND bingo_card_id IN
         (SELECT id FROM bingo_cards WHERE event_id IN (SELECT id FROM events WHERE title = 'E2E Admin Event'))`,
    );
}

/** A standing that cannot sync, on an account that can be reset. */
export function strandAccount() {
    const eventRow = query("SELECT id FROM events WHERE title = 'E2E Drop Race'")[0];
    const account = user('e2e_stranded');

    const character = randomUUID();

    run("UPDATE users SET osrs_username = 'E2E Stranded' WHERE id = ?", [account.id]);
    run('DELETE FROM osrs_accounts WHERE user_id = ?', [account.id]);
    run(
        "INSERT INTO osrs_accounts (id, user_id, username, position, created_at, updated_at) VALUES (?, ?, 'E2E Stranded', 0, datetime('now'), datetime('now'))",
        [character, account.id],
    );
    run('DELETE FROM event_standings WHERE user_id = ?', [account.id]);
    run(
        "INSERT INTO event_standings (id, event_id, user_id, osrs_account_id, username, gained, sync_error, synced_at, created_at, updated_at) VALUES (?, ?, ?, ?, 'E2E Stranded', 0, 'not_tracked', datetime('now'), datetime('now'), datetime('now'))",
        [randomUUID(), eventRow.id, account.id, character],
    );
}

export function unstrandAccount() {
    run("DELETE FROM event_standings WHERE user_id = (SELECT id FROM users WHERE discord_username = 'e2e_stranded')");
}
