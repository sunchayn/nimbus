import { defineConfig, devices } from '@playwright/test';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));

/*
 * The relay endpoint calls back into the app, so the workbench needs a second server.
 * Both ports match NIMBUS_RELAY_ENDPOINT and APP_URL in workbench/.env.example.
 * Locally, running servers (for example from `composer dev`) are reused.
 */
const workbenchServer = (port: number) => ({
    command: `php vendor/bin/testbench serve --port=${port}`,
    cwd: root,
    url: `http://127.0.0.1:${port}/demo`,
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
    // The Dump and Die demo adds the app container dump only in the testing environment.
    env: { APP_ENV: 'testing' },
});

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
    testDir: "./tests",
    timeout: 160_000,
    // Stops the run before the CI job limit so the report is still written.
    globalTimeout: process.env.CI ? 6 * 60_000 : undefined,
    fullyParallel: true,
    // Fail the build on CI if you accidentally left test.only in the source code.
    forbidOnly: !!process.env.CI,
    retries: 0,
    workers: process.env.CI ? 4 : 8,
    // Reporter to use. See https://playwright.dev/docs/test-reporters
    reporter: process.env.CI ? [["list"], ["html"]] : "html",
    // Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions.
    use: {
        baseURL: "http://127.0.0.1:8000",
        trace: "retain-on-failure",
        ignoreHTTPSErrors: true,
        navigationTimeout: 60_000,
        screenshot: "only-on-failure",
        testIdAttribute: "data-testid",
        video: "retain-on-failure",
        actionTimeout: process.env.CI ? 0 : 2_000,
    },

    webServer: [workbenchServer(8000), workbenchServer(8001)],

    // Configure projects for major browsers
    projects: !process.env.CI
        ? [
            {
                name: "chromium",
                use: { ...devices["Desktop Chrome"] },
            },

            {
                name: "firefox",
                use: { ...devices["Desktop Firefox"] },
            },
        ]
        : [
            {
                name: "chromium",
                use: { ...devices["Desktop Chrome"] },
            },
        ],
});
