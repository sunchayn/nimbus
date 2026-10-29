import { expect, test } from '../core/fixtures';

const delayRoutes = async (page: import('@playwright/test').Page, ms: number) => {
    await page.route('**/api/routes', async route => {
        await new Promise(resolve => setTimeout(resolve, ms));
        await route.continue();
    });
};

test.describe('Loading shell', () => {
    test('shows the skeleton and the loading message while routes load', async ({ page }) => {
        await delayRoutes(page, 4_000);

        await page.goto('/demo/');

        await expect(page.getByTestId('routes-skeleton-message')).toHaveText(
            'Loading routes',
            { timeout: 3_000 },
        );
        await expect(page.getByText('The first load can take longer')).toBeVisible({
            timeout: 5_000,
        });

        await expect(page.getByTestId('routes-skeleton')).toHaveCount(0, {
            timeout: 10_000,
        });
        await expect(page.getByTestId('routes-panel').getByRole('button').first()).toBeVisible();
    });

    test('keeps layout shift low when the routes arrive', async ({ page }) => {
        await page.addInitScript(() => {
            const w = window as unknown as { __cls: number };
            w.__cls = 0;

            new PerformanceObserver(list => {
                for (const entry of list.getEntries() as unknown as {
                    value: number;
                    hadRecentInput: boolean;
                }[]) {
                    if (!entry.hadRecentInput) {
                        w.__cls += entry.value;
                    }
                }
            }).observe({ type: 'layout-shift', buffered: true });
        });

        await delayRoutes(page, 1_500);

        await page.goto('/demo/');

        await expect(page.getByTestId('routes-panel').getByRole('button').first()).toBeVisible({
            timeout: 10_000,
        });

        const cls = await page.evaluate(() => (window as unknown as { __cls: number }).__cls);

        expect(cls).toBeLessThan(0.05);
    });

    test('offers a retry when the routes request fails', async ({ page }) => {
        await page.route('**/api/routes', route => route.abort());

        await page.goto('/demo/');

        await expect(page.getByTestId('routes-error')).toBeVisible({ timeout: 10_000 });

        await page.unroute('**/api/routes');
        await page.getByTestId('routes-retry').click();

        await expect(page.getByTestId('routes-panel').getByRole('button').first()).toBeVisible({
            timeout: 10_000,
        });
    });

    test('uses the saved theme and sidebar state before the app mounts', async ({ page }) => {
        await page.emulateMedia({ colorScheme: 'light' });

        await page.addInitScript(() => {
            localStorage.setItem('nimbus-user-preferences', JSON.stringify({ theme: 'dark' }));
            localStorage.setItem('nimbus:explorer-open', 'false');
        });

        // The app never gets the routes, so the first paint stays on the skeleton.
        await delayRoutes(page, 3_000);

        await page.goto('/demo/', { waitUntil: 'commit' });

        await expect(page.locator('html')).toHaveClass(/dark/);

        const sidebar = page.getByTestId('skeleton-sidebar');
        await expect(sidebar).toBeAttached();
        expect((await sidebar.boundingBox())?.width ?? 0).toBeLessThan(1);
    });

    test('uses the saved request and response split before the app mounts', async ({ page }) => {
        await page.addInitScript(() => {
            localStorage.setItem(
                'reka:client-splitter-group',
                JSON.stringify({ 'saved-panels': { layout: [70, 30] } }),
            );
        });

        await delayRoutes(page, 3_000);

        await page.goto('/demo/', { waitUntil: 'commit' });

        // The head script runs before first paint, so the variables exist before the shell does.
        await expect
            .poll(() =>
                page.evaluate(() => [
                    document.documentElement.style.getPropertyValue('--shell-request-size'),
                    document.documentElement.style.getPropertyValue('--shell-response-size'),
                ]),
            )
            .toEqual(['70', '30']);
    });
});
