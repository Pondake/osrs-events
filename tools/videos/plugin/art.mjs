// Runs `php artisan ...` against the isolated e2e database on E2E_PORT.
process.env.E2E_PORT ??= '9600';
const { PHP, ROOT, phpEnv } = await import(new URL('../../../tests/e2e/support/env.js', import.meta.url).href);
const { execFileSync } = await import('node:child_process');
const out = execFileSync(PHP, ['artisan', ...process.argv.slice(2)], { cwd: ROOT, env: phpEnv, encoding: 'utf8' });
process.stdout.write(out);
