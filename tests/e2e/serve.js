/**
 * Starts everything the browser suite needs, and stays up until Playwright
 * stops it: a fresh, seeded database, a Wise Old Man stand-in, and the app.
 *
 * Playwright's webServer waits for the app's port, which only opens once
 * the database exists — so the preparation happens here, before listening,
 * and no test ever races a migration.
 */
import { spawn, execFileSync } from 'node:child_process';
import { createServer, request as httpRequest } from 'node:http';
import { createReadStream, readFileSync, statSync } from 'node:fs';
import net from 'node:net';
import { existsSync, mkdirSync, openSync, rmSync } from 'node:fs';
import path from 'node:path';
import { DB_FILE, GAINS_FILE, PHP, PHP_PORT, PORT, ROOT, RUN_DIR, STORAGE_DIR, WOM_PORT, phpEnv } from './support/env.js';

if (existsSync(path.join(ROOT, 'public/hot'))) {
    console.error('public/hot exists — a Vite dev server is running, and the app would load assets from it instead of public/build. Stop it, then rerun.');
    process.exit(1);
}

if (!existsSync(path.join(ROOT, 'public/build/manifest.json'))) {
    console.error('public/build is missing — run `pnpm build` first (or use `pnpm e2e`, which does).');
    process.exit(1);
}

rmSync(RUN_DIR, { recursive: true, force: true });

for (const dir of ['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'framework/testing', 'logs']) {
    mkdirSync(path.join(STORAGE_DIR, dir), { recursive: true });
}

// An empty file, not a missing one: SQLite creates it, but the app's own
// "database does not exist" check runs first.
openSync(DB_FILE, 'w');

try {
    execFileSync(PHP, ['artisan', 'migrate:fresh', '--force', '--seed', '--seeder=E2eSeeder'], {
        cwd: ROOT,
        env: phpEnv,
        encoding: 'utf8',
    });
} catch (error) {
    console.error(error.stdout, error.stderr);
    process.exit(1);
}

/**
 * Wise Old Man, as far as this app can tell. A name starting with "Unknown"
 * is one they have never tracked (their real 404), everything else exists and
 * comes back as typed. Gains are available only for what a spec has put in
 * GAINS_FILE (see support/wom.js) — the honest answer for a player who has no
 * history in a database created a minute ago is a 404, and it stays the answer
 * for everybody a spec has not given a number.
 */
const wom = createServer((request, response) => {
    const match = request.url.match(/^\/v2\/players\/([^/?]+)(\/gained)?/);
    const name = match ? decodeURIComponent(match[1]) : null;

    response.setHeader('Content-Type', 'application/json');

    if (name && match[2] && !name.startsWith('Unknown')) {
        let gains = {};

        try {
            gains = JSON.parse(readFileSync(GAINS_FILE, 'utf8'));
        } catch {
            // No file is the ordinary case: nobody has any gains.
        }

        if (gains[name]) {
            const data = { skills: {}, bosses: {} };

            for (const [metric, gained] of Object.entries(gains[name])) {
                const delta = { gained, start: 100, end: 100 + gained };

                data.skills[metric] = { experience: delta };
                data.bosses[metric] = { kills: delta };
            }

            response.end(JSON.stringify({ data }));

            return;
        }
    }

    if (!name || match[2] || name.startsWith('Unknown')) {
        response.statusCode = 404;
        response.end(JSON.stringify({ code: 'PLAYER_NOT_FOUND' }));

        return;
    }

    response.end(JSON.stringify({ displayName: name }));
});

wom.listen(WOM_PORT, '127.0.0.1');

const log = openSync(path.join(RUN_DIR, 'server.log'), 'w');

const server = spawn(
    PHP,
    ['-S', `127.0.0.1:${PHP_PORT}`, path.join(ROOT, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
    {
        cwd: path.join(ROOT, 'public'),
        // PHP's built-in server is one process, and every event page holds a
        // connection open for its live channel. Where it can fork it does so;
        // on Windows it cannot, which is why the suite stubs that channel.
        env: process.platform === 'win32' ? phpEnv : { ...phpEnv, PHP_CLI_SERVER_WORKERS: '4' },
        stdio: ['ignore', log, log],
    },
);

const stop = () => {
    server.kill();
    wom.close();
    process.exit(0);
};

process.on('SIGINT', stop);
process.on('SIGTERM', stop);
server.on('exit', (code) => process.exit(code ?? 1));

const PUBLIC = path.join(ROOT, 'public');

const TYPES = {
    '.js': 'text/javascript',
    '.mjs': 'text/javascript',
    '.css': 'text/css',
    '.svg': 'image/svg+xml',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.webp': 'image/webp',
    '.gif': 'image/gif',
    '.ico': 'image/x-icon',
    '.json': 'application/json',
    '.webmanifest': 'application/manifest+json',
    '.woff': 'font/woff',
    '.woff2': 'font/woff2',
    '.map': 'application/json',
    '.txt': 'text/plain',
};

/** A real file under public/ that is not a script, or null. */
function staticFile(pathname) {
    let file;

    try {
        file = path.join(PUBLIC, decodeURIComponent(pathname));
    } catch {
        return null;
    }

    if (!file.startsWith(PUBLIC + path.sep) || path.extname(file) === '.php') return null;

    try {
        return statSync(file).isFile() ? file : null;
    } catch {
        return null;
    }
}

/**
 * In front of PHP, for two reasons that are both about PHP's built-in server.
 *
 * It closes the connection after every response, and a page loads a hundred
 * script chunks: a few hundred pages into a run Windows runs out of sockets
 * (ERR_NO_BUFFER_SPACE) and the browser starts failing to fetch modules. And
 * it is one process, so every asset request queues behind every page render.
 * Static files are served from here, kept alive; everything else is passed
 * through untouched, Host header included, so the app builds the URLs it would
 * build itself.
 */
const front = createServer((request, response) => {
    const pathname = new URL(request.url, 'http://localhost').pathname;
    const file = request.method === 'GET' ? staticFile(pathname) : null;

    if (file) {
        response.writeHead(200, {
            'Content-Type': TYPES[path.extname(file)] ?? 'application/octet-stream',
            'Content-Length': statSync(file).size,
            'Cache-Control': 'public, max-age=3600',
        });
        createReadStream(file).pipe(response);

        return;
    }

    const upstream = httpRequest(
        { host: '127.0.0.1', port: PHP_PORT, path: request.url, method: request.method, headers: request.headers },
        (answer) => {
            response.writeHead(answer.statusCode, answer.headers);
            answer.pipe(response);
        },
    );

    upstream.on('error', (error) => {
        response.writeHead(502);
        response.end(String(error));
    });

    request.pipe(upstream);
});

// Listening only once PHP does, so Playwright's wait for this port means the app is up.
const waitForPhp = () =>
    new Promise((resolve) => {
        const attempt = () => {
            const socket = net.connect(PHP_PORT, '127.0.0.1');

            socket.on('connect', () => {
                socket.destroy();
                resolve();
            });
            socket.on('error', () => setTimeout(attempt, 100));
        };

        attempt();
    });

await waitForPhp();

front.listen(PORT, '127.0.0.1');
