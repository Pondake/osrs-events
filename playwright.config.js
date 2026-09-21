import { defineConfig, devices } from '@playwright/test';
import { ORIGIN, PORT, REPORT_DIR, RESULTS_DIR } from './tests/e2e/support/env.js';

/**
 * The browser suite: a real Chromium against the real built app, on a
 * database that is created and thrown away by tests/e2e/serve.js.
 *
 *   pnpm e2e            build, then run everything
 *   pnpm e2e:run        run against the existing build
 *   pnpm e2e:ui         Playwright's own UI, for writing a test
 *
 * One worker: the specs share one database, and PHP's built-in server is a
 * single process anyway.
 *
 * One retry, for one reason: on Windows Chromium now and then fails a request
 * with ERR_NO_BUFFER_SPACE (the operating system ran out of socket buffers,
 * nothing the app did). A retry that passes is reported as flaky rather than
 * hidden, and a real failure fails both times.
 */
export default defineConfig({
    testDir: 'tests/e2e',
    outputDir: RESULTS_DIR,
    fullyParallel: false,
    workers: 1,
    retries: 1,
    timeout: 45_000,
    expect: { timeout: 8_000 },
    reporter: [['list'], ['html', { open: 'never', outputFolder: REPORT_DIR }]],
    use: {
        baseURL: ORIGIN,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'node tests/e2e/serve.js',
        port: PORT,
        timeout: 180_000,
        reuseExistingServer: false,
        stdout: 'ignore',
        stderr: 'pipe',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } },
            dependencies: ['setup'],
            testMatch: /\.spec\.js$/,
        },
    ],
});
