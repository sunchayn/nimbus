/**
 * Records the Nimbus demo against the running demo app and converts it to a GIF.
 *
 * Run it with `node .ai/skills/record-demo-video/record.mjs`.
 * Options are `--out <gif>`, `--base <url>`, and `--keep-video` to also save the raw webm next to the GIF.
 * The storyline is the list of steps inside `play`, keep it in sync with SKILL.md.
 */
import { chromium } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { copyFileSync, mkdtempSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const args = process.argv.slice(2);
const option = (name, fallback) => {
    const index = args.indexOf(`--${name}`);

    return index === -1 ? fallback : args[index + 1];
};

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const OUT = path.resolve(option('out', path.join(root, 'art/nimbus-demo-video.gif')));
const BASE = option('base', 'http://127.0.0.1:8000/demo');
const KEEP_VIDEO = args.includes('--keep-video');

const VIEWPORT = { width: 1280, height: 800 };
const FPS = 15;

/*
 * Playwright does not draw the pointer, so a dot follows the mouse events inside the page.
 * The dot shrinks while a button is pressed to give click feedback.
 */
const CURSOR_SCRIPT = () => {
    window.addEventListener('DOMContentLoaded', () => {
        const dot = document.createElement('div');
        dot.style.cssText =
            'position:fixed;left:0;top:0;width:18px;height:18px;margin:-9px 0 0 -9px;border-radius:50%;background:rgba(75,85,99,.65);z-index:2147483647;pointer-events:none;transition:transform .12s;transform:translate(640px,400px)';
        document.body.appendChild(dot);

        let x = 640;
        let y = 400;
        const render = (scale) => (dot.style.transform = `translate(${x}px,${y}px) scale(${scale})`);

        document.addEventListener(
            'mousemove',
            (event) => {
                x = event.clientX;
                y = event.clientY;
                render(1);
            },
            true,
        );
        document.addEventListener('mousedown', () => render(0.6), true);
        document.addEventListener('mouseup', () => render(1), true);
    });
};

const pause = (page, ms) => page.waitForTimeout(ms);

const glideTo = async (page, locator) => {
    const box = await locator.boundingBox();

    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 18 });
    await pause(page, 150);
};

const click = async (page, locator) => {
    await glideTo(page, locator);
    await page.mouse.down();
    await pause(page, 90);
    await page.mouse.up();
};

/*
 * The storyline. Each beat is one thing a viewer should notice, followed by a short pause to read it.
 */
const play = async (page) => {
    const responsePanel = page.getByRole('tablist').nth(1);

    await pause(page, 700);

    // 1. Browse the routes by opening a resource group.
    await click(page, page.getByRole('button', { name: 'shapes', exact: true }));
    await pause(page, 400);

    // 2. Open a route, the body is prefilled from the validation rules and invalid values are flagged.
    await click(page, page.getByRole('button', { name: /POST \/nested-object/ }));
    await pause(page, 1300);

    // 3. Auto Fill replaces the placeholders with realistic values.
    await click(page, page.getByRole('button', { name: 'Auto Fill' }));
    await pause(page, 1000);

    // 4. Send the request and show the response.
    await click(page, page.getByRole('button', { name: 'Send', exact: true }));
    await page.waitForFunction(
        () => {
            const status = document.querySelector('[data-testid="response-status-text"]');

            return status && !/Pending/.test(status.textContent);
        },
        null,
        { timeout: 20000 },
    );
    await pause(page, 1200);

    // 5. Inspect the response headers.
    await click(page, responsePanel.getByRole('tab', { name: /Headers/ }));
    await pause(page, 1000);

    // 6. Inspect the cookies.
    await click(page, responsePanel.getByRole('tab', { name: /Cookies/ }));
    await pause(page, 1300);
};

/*
 * Recording and conversion.
 */

const workdir = mkdtempSync(path.join(tmpdir(), 'nimbus-demo-'));
const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: VIEWPORT,
    recordVideo: { dir: workdir, size: VIEWPORT },
});

await context.addInitScript(CURSOR_SCRIPT);

const page = await context.newPage();
const startedAt = Date.now();

await page.goto(BASE);
await page.waitForLoadState('networkidle');
await pause(page, 500);

// The video starts at page creation, so the load time is cut from the start of the GIF.
const trimSeconds = (Date.now() - startedAt) / 1000;

await play(page);

const video = page.video();
await context.close();
await browser.close();

const webm = await video.path();

if (KEEP_VIDEO) {
    copyFileSync(webm, OUT.replace(/\.gif$/, '.webm'));
}

const filter = [
    `fps=${FPS},scale=${VIEWPORT.width}:-1:flags=lanczos,split[a][b]`,
    '[a]palettegen=stats_mode=diff[p]',
    '[b][p]paletteuse=dither=bayer:bayer_scale=5:diff_mode=rectangle',
].join(';');

execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-ss', trimSeconds.toFixed(2), '-i', webm, '-filter_complex', filter, '-loop', '0', OUT], {
    stdio: 'inherit',
});

const megabytes = (statSync(OUT).size / 1024 / 1024).toFixed(1);
console.log(`Wrote ${OUT} (${megabytes} MB)`);
