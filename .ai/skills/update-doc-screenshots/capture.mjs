/**
 * Captures the screenshots used by wiki/user-guide against the running demo app.
 *
 * Run it with `node .ai/skills/update-doc-screenshots/capture.mjs`.
 * Options are `--only layout,dd`, `--out <dir>`, and `--base <url>`.
 * The available features are listed in the FEATURES map at the bottom of this file.
 */
import { chromium } from '@playwright/test';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const args = process.argv.slice(2);
const option = (name, fallback) => {
    const index = args.indexOf(`--${name}`);

    return index === -1 ? fallback : args[index + 1];
};

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const OUT = path.resolve(option('out', path.join(root, 'wiki/user-guide/assets')));
const BASE = option('base', 'http://127.0.0.1:8000/demo');
const ONLY = option('only', '')
    .split(',')
    .filter(Boolean);

const VIEWPORT = { width: 1440, height: 900 };

/*
 * Helpers.
 */

const wait = (page, ms = 400) => page.waitForTimeout(ms);

const shot = (page, name, clip) =>
    page.screenshot({
        path: path.join(OUT, `${name}.png`),
        ...(clip && { clip: { x: clip[0], y: clip[1], width: clip[2], height: clip[3] } }),
    });

const open = async (browser, query = '') => {
    const context = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2 });
    const page = await context.newPage();

    await page.goto(`${BASE}${query}`);
    await page.waitForLoadState('networkidle');
    await wait(page, 1200);

    return page;
};

const openGroup = async (page, name) => {
    const button = page.getByRole('button', { name, exact: true });

    if ((await button.getAttribute('aria-expanded')) !== 'true') {
        await button.click();
    }

    await wait(page, 300);
};

const openRoute = async (page, group, name) => {
    await openGroup(page, group);
    await page.getByRole('button', { name }).first().click();
    await wait(page, 600);
};

const send = async (page) => {
    await page.getByRole('button', { name: 'Send', exact: true }).click();
    await page.waitForFunction(
        () => {
            const status = document.querySelector('[data-testid="response-status-text"]');

            return status && !/Pending/.test(status.textContent);
        },
        null,
        { timeout: 20000 },
    );
    await wait(page, 600);
};

const autoFill = async (page) => {
    await page.getByRole('tab', { name: 'Body' }).click();
    await page.getByRole('button', { name: 'Auto Fill' }).click();
    await wait(page, 500);
};

const removeOverlays = (page) =>
    page.evaluate(() => document.querySelectorAll('.__overlay').forEach((el) => el.remove()));

/*
 * This runs inside the page, so it cannot use the helpers above.
 */
const addBoxes = (page, boxes) =>
    page.evaluate((boxes) => {
        for (const { x, y, w, h, color, label, radius } of boxes) {
            const box = document.createElement('div');
            box.className = '__overlay';
            box.style.cssText = `position:fixed;left:${x}px;top:${y}px;width:${w}px;height:${h}px;border:2px solid ${color};border-radius:${radius ?? 0};z-index:99999;pointer-events:none`;

            if (label) {
                const tag = document.createElement('div');
                tag.textContent = label;
                tag.style.cssText =
                    'position:absolute;right:12px;bottom:12px;background:#fef08a;font:600 34px sans-serif;padding:0 6px;color:#000';
                box.appendChild(tag);
            }

            document.body.appendChild(box);
        }
    }, boxes);

/*
 * Each shot runs in its own try block, so a failure is logged and the next shot still runs.
 */
const step = async (name, fn) => {
    try {
        await fn();
        console.log(`ok    ${name}`);
    } catch (error) {
        console.log(`FAIL  ${name}  ${error.message.split('\n')[0]}`);
    }
};

/*
 * Features. Each one starts from a fresh browser context so state never leaks between them.
 */

const layout = async (browser) => {
    const page = await open(browser);

    await step('empty-client-sections', async () => {
        await addBoxes(page, [
            { x: 8, y: 45, w: 283, h: 780, color: '#ef4444', label: '1' },
            { x: 300, y: 80, w: 563, h: 811, color: '#3b82f6', label: '2' },
            { x: 868, y: 80, w: 563, h: 811, color: '#4ade80', label: '3' },
        ]);
        await shot(page, 'empty-client-sections');
        await removeOverlays(page);
    });

    await step('request-builder', () => shot(page, 'request-builder', [300, 45, 565, 330]));
    await step('applications-switcher', () => shot(page, 'applications-switcher', [0, 848, 300, 52]));

    await step('routes', async () => {
        await openGroup(page, 'inline-validation');
        await openGroup(page, 'responses');
        await shot(page, 'routes', [0, 0, 300, 900]);
    });
};

const schema = async (browser) => {
    const page = await open(browser);

    await step('schema-validation', async () => {
        await openRoute(page, 'inline-validation', /POST \/conditional/);
        await page.getByRole('tab', { name: 'Body' }).click();
        await wait(page, 800);
        await page.locator('.cm-lint-marker-error, .cm-lintRange-error').first().hover();
        await wait(page, 900);
        await shot(page, 'schema-validation', [0, 0, 1440, 560]);
    });

    await step('auto-fill', async () => {
        await page.mouse.move(700, 700);
        await page.getByRole('button', { name: 'Auto Fill' }).click();
        await wait(page, 700);

        const box = await page.getByRole('button', { name: 'Auto Fill' }).boundingBox();
        await addBoxes(page, [
            { x: box.x - 14, y: box.y - 12, w: box.width + 28, h: box.height + 24, color: '#ef4444', radius: '50%' },
        ]);
        await page.mouse.move(700, 800);
        await wait(page, 300);
        await shot(page, 'auto-fill', [0, 0, 1440, 560]);
        await removeOverlays(page);
    });

    await step('auth-options', async () => {
        await page.getByRole('tab', { name: 'Authorization' }).click();
        await wait(page);
        await page.getByTestId('request-authorization').getByRole('combobox').click();
        await wait(page, 500);
        await shot(page, 'auth-options', [300, 125, 400, 330]);
    });
};

const generator = async (browser) => {
    const page = await open(browser);

    await step('value-generator', async () => {
        await openRoute(page, 'inline-validation', /POST \/conditional/);
        await page.getByRole('tab', { name: 'Parameters' }).click();
        await page.getByTestId('request-parameters').getByRole('button', { name: 'Add' }).click();
        await wait(page, 300);
        await page.getByTestId('request-parameters').getByTestId('kv-value').first().click();
        await page.getByTestId('request-parameters').getByTestId('generator-button').first().click();
        await wait(page, 700);
        await shot(page, 'value-generator', [300, 45, 700, 700]);
    });
};

const response = async (browser) => {
    const page = await open(browser);

    await step('response', async () => {
        await openRoute(page, 'shapes', /POST \/array-of-primitives/);
        await autoFill(page);
        await send(page);
        await shot(page, 'response', [300, 45, 1140, 650]);
    });

    await step('cookies', async () => {
        await page.getByRole('tab', { name: 'Cookies' }).click();
        await wait(page, 500);
        await shot(page, 'cookies', [868, 70, 572, 340]);
        await page.getByRole('button', { name: 'Decrypt' }).click();
        await wait(page, 600);
        await shot(page, 'decrypted-cookies', [868, 70, 572, 340]);
        await page.getByRole('tab', { name: 'Response' }).click();
        await wait(page);
    });

    await step('response-download', async () => {
        await page.getByTestId('download-dropdown-trigger').click();
        await wait(page, 500);
        await shot(page, 'response-download', [868, 80, 572, 200]);
    });
};

const dd = async (browser) => {
    const page = await open(browser);

    await step('dd-viewer', async () => {
        await openRoute(page, 'dd', 'GET /');
        await send(page);
        await send(page);
        await shot(page, 'dd-viewer', [300, 45, 1140, 855]);
        await shot(page, 'dd-viewer-pagination', [868, 45, 572, 240]);
    });
};

const history = async (browser) => {
    const page = await open(browser);

    await step('history-viewer', async () => {
        await openRoute(page, 'shapes', /POST \/array-of-primitives/);
        await autoFill(page);
        await send(page);
        await openRoute(page, 'dd', 'GET /');
        await send(page);
        await send(page);
        await page.getByTestId('response-history-trigger').click();
        await wait(page, 700);
        await shot(page, 'history-viewer', [868, 70, 572, 540]);
    });
};

const environments = async (browser) => {
    const page = await open(browser);

    const addCollection = async (name, variables) => {
        await page.getByTestId('add-collection-btn').click();
        await wait(page);

        const editor = page.getByTestId('collection-name-editor');
        await editor.click({ force: true });
        await editor.locator('input').fill(name);
        await editor.locator('input').press('Enter');
        await wait(page, 300);

        for (const [index, [key, value]] of variables.entries()) {
            if (index > 0) {
                await page.getByTestId('kv-container').getByRole('button', { name: 'Add' }).click();
                await wait(page, 200);
            }

            await page.getByTestId('kv-key').nth(index).fill(key);

            if (value) {
                await page.getByTestId('kv-value').nth(index).fill(value);
            }
        }
    };

    await step('env-page', async () => {
        await page.getByTestId('environment-switcher').click();
        await page.getByRole('menuitem', { name: 'Manage environments' }).click();
        await wait(page, 600);
        await addCollection('Staging', [['token', 'abc123']]);
        await addCollection('Local', [['example', 'value'], ['empty', '']]);
        await page.mouse.move(700, 700);
        await wait(page, 500);
        await shot(page, 'env-page', [0, 0, 1440, 400]);
        await page.getByRole('button', { name: 'HTTP Client' }).click();
        await wait(page, 600);
    });

    await step('env-variables-client-different-states', async () => {
        await openRoute(page, 'dd', 'GET /');
        await page.getByTestId('endpoint-input').click();
        await page.keyboard.press('ControlOrMeta+a');
        await page.keyboard.type('_demo/dd?{{not-found}}={{empty}}');
        await wait(page, 300);
        await page.getByRole('tab', { name: 'Parameters' }).click();
        await page.getByTestId('request-parameters').getByRole('button', { name: 'Add' }).click();
        await wait(page, 300);
        await page.getByTestId('request-parameters').getByTestId('kv-key').last().fill('variable');
        await page.getByTestId('request-parameters').getByTestId('kv-value').last().fill('{{example}}');
        await send(page);
        await shot(page, 'env-variables-client-different-states', [300, 45, 1140, 300]);
    });

    /*
     * The hover tooltip did not open under headless Chromium when this was written.
     * Check the output image before trusting it.
     */
    await step('env-variables-resolved-value', async () => {
        const value = page.getByTestId('request-parameters').getByTestId('kv-value').last();
        const box = await value.boundingBox();

        await page.mouse.move(box.x + 100, box.y + box.height / 2);
        await page.mouse.move(box.x + 30, box.y + box.height / 2, { steps: 8 });
        await wait(page, 1200);
        await shot(page, 'env-variables-resolved-value', [box.x, box.y - 6, 420, 90]);
    });
};

const requestOptions = async (browser) => {
    const page = await open(browser);
    const menu = [620, 70, 260, 210];

    await step('request-options', async () => {
        await openRoute(page, 'inline-validation', /POST \/conditional/);
        await autoFill(page);
        await send(page);

        await page.getByTestId('request-options-button').click();
        await wait(page, 500);
        await page.getByRole('menu').getByRole('switch').click();
        await wait(page, 500);
        await page.mouse.move(700, 600);
        await shot(page, 'transaction-mode', menu);

        await page.getByRole('menuitem', { name: /Export to cURL/i }).hover();
        await wait(page, 300);
        await shot(page, 'export-to-curl', menu);

        await page.getByRole('menuitem', { name: /Shareable Link/i }).hover();
        await wait(page, 300);
        await shot(page, 'shareable-link', menu);

        await page.getByRole('menuitem', { name: /Export to cURL/i }).click();
        await wait(page, 800);
        await page.getByRole('dialog').screenshot({ path: path.join(OUT, 'curl-command-export.png') });
        await page.keyboard.press('Escape');
        await wait(page, 500);

        await page.getByTestId('request-options-button').click();
        await wait(page, 500);
        await page.getByRole('menuitem', { name: /Shareable Link/i }).click();
        await wait(page, 800);
        await page.getByRole('dialog').screenshot({ path: path.join(OUT, 'shareable-link-2.png') });
    });
};

const openapi = async (browser) => {
    const page = await open(browser, '?application=internal');

    await step('open-api-support', async () => {
        await openGroup(page, 'products');
        await page.getByRole('button', { name: /createProduct/ }).first().click();
        await wait(page, 800);
        await page.getByRole('tab', { name: 'Body' }).click();
        await wait(page, 500);
        await shot(page, 'open-api-support', [0, 0, 1000, 540]);
    });
};

const FEATURES = {
    layout,
    schema,
    generator,
    response,
    dd,
    history,
    environments,
    'request-options': requestOptions,
    openapi,
};

const unknown = ONLY.filter((name) => !(name in FEATURES));

if (unknown.length > 0) {
    console.error(`Unknown feature(s) ${unknown.join(', ')}. Available: ${Object.keys(FEATURES).join(', ')}`);
    process.exit(1);
}

const browser = await chromium.launch();

for (const [name, run] of Object.entries(FEATURES)) {
    if (ONLY.length > 0 && !ONLY.includes(name)) {
        continue;
    }

    console.log(`\n# ${name}`);
    await run(browser);
}

await browser.close();
