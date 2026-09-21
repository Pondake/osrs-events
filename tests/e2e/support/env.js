import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
export const PORT = Number(process.env.E2E_PORT ?? 9317);

/**
 * A run on another port keeps its own database, sessions and results, so two
 * runs (two checkouts of a task, CI beside a local run) never share a file.
 */
export const RUN_NAME = process.env.E2E_PORT ? `-${PORT}` : '';
export const RUN_DIR = path.join(ROOT, `tests/e2e/.run${RUN_NAME}`);
export const DB_FILE = path.join(RUN_DIR, 'e2e.sqlite');
export const STORAGE_DIR = path.join(RUN_DIR, 'storage');
export const GAINS_FILE = path.join(RUN_DIR, 'wom-gains.json');

export const WOM_PORT = PORT + 1;
export const PHP_PORT = PORT + 2;
export const ORIGIN = `http://127.0.0.1:${PORT}`;

/**
 * Node cannot start a .bat file directly, and Herd puts php on the path as
 * one. Read the real executable out of the shim, so the server can be
 * stopped by its own process id rather than through a shell that would leave
 * it running.
 */
function findPhp() {
    if (process.env.PHP_BIN) return process.env.PHP_BIN;
    if (process.platform !== 'win32') return 'php';

    for (const dir of (process.env.PATH ?? '').split(path.delimiter)) {
        if (existsSync(path.join(dir, 'php.exe'))) return path.join(dir, 'php.exe');

        const shim = path.join(dir, 'php.bat');

        if (existsSync(shim)) {
            const exe = readFileSync(shim, 'utf8').match(/"([^"]+php.exe)"/i)?.[1];

            if (exe && existsSync(exe)) return exe;
        }
    }

    throw new Error('php was not found on the path — set PHP_BIN to its full path.');
}

export const PHP = findPhp();

/**
 * The environment every PHP process of the suite runs in: the server, and the
 * artisan calls the tests make between requests.
 *
 * Real environment variables win over .env, which is the whole isolation: a
 * database file, a storage directory and a Wise Old Man of its own. The suite
 * never touches the development database, its sessions, or its persona
 * snapshot.
 */
export const phpEnv = {
    ...process.env,
    APP_ENV: 'local',
    APP_DEBUG: 'false',
    APP_URL: ORIGIN,
    LARAVEL_STORAGE_PATH: STORAGE_DIR,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: DB_FILE,
    DB_URL: '',
    SESSION_DRIVER: 'file',
    SESSION_SECURE_COOKIE: 'false',
    SESSION_DOMAIN: '',
    CACHE_STORE: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'log',
    LOG_CHANNEL: 'single',
    LOG_LEVEL: 'debug',
    BROADCAST_CONNECTION: 'null',
    INERTIA_SSR_ENABLED: 'false',
    PULSE_ENABLED: 'false',
    TELESCOPE_ENABLED: 'false',
    NIGHTWATCH_ENABLED: 'false',
    BCRYPT_ROUNDS: '4',
    WOM_BASE_URL: `http://127.0.0.1:${WOM_PORT}/v2`,
    WOM_THROTTLE: 'false',
    // Nothing else may reach the internet from a run. HTTPS goes to a port
    // nothing listens on, so a stray call fails at once and the app handles it
    // as it would an outage; loopback (the Wise Old Man stand-in) is exempt.
    HTTPS_PROXY: 'http://127.0.0.1:9',
    NO_PROXY: '127.0.0.1,localhost',
};

export const AUTH_DIR = path.join(ROOT, `tests/e2e/.auth${RUN_NAME}`);
export const RESULTS_DIR = path.join(ROOT, `tests/e2e/.results${RUN_NAME}`);
export const REPORT_DIR = path.join(ROOT, `playwright-report${RUN_NAME}`);

export const authFile = (name) => path.join(AUTH_DIR, `${name}.json`);
