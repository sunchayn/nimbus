import type { Page } from '@playwright/test';
import { test, expect } from '../core/fixtures';

/*
 * The extension of the generated file name must agree with the mime type that is sent.
 */
const MIME_TYPE_BY_EXTENSION: Record<string, string> = {
    png: 'image/png',
    gif: 'image/gif',
    jpg: 'image/jpeg',
    webp: 'image/webp',
    pdf: 'application/pdf',
    zip: 'application/zip',
    txt: 'text/plain',
    json: 'application/json',
    csv: 'text/csv',
    svg: 'image/svg+xml',
};

const readResponse = async (page: Page): Promise<string> =>
    (await page.getByTestId('response-content').innerText()).replace(/\s+/g, ' ');

const sentFiles = (response: string) =>
    [...response.matchAll(/"name":\s*"([^"]+)\.(\w+)"[^}]*?"mime_type":\s*"([^"]+)"/g)].map(
        match => ({ extension: match[2], mimeType: match[3] }),
    );

test.describe('JSON uploads', () => {
    test.beforeEach(async ({ page, basePage }) => {
        await basePage.goto();
        await page.getByRole('button', { name: 'uploads' }).click();
        await page.getByRole('button', { name: 'json' }).click();
    });

    test('auto-fills a single file with content that matches its name and mime type', async ({
        page,
        basePage,
    }) => {
        // Arrange

        await page.getByRole('button', { name: /POST \/single/ }).click();
        await page.getByRole('tab', { name: 'Body' }).click();

        // Act

        await page.getByRole('button', { name: 'Auto Fill' }).click();
        await basePage.executeRequest();

        // Assert

        await expect(page.getByTestId('response-status-badge')).toContainText('201');

        const [file] = sentFiles(await readResponse(page));

        expect(MIME_TYPE_BY_EXTENSION[file.extension]).toBe(file.mimeType);
    });

    test('auto-fills several files, each with its own matching mime type', async ({
        page,
        basePage,
    }) => {
        // Arrange

        await page.getByRole('button', { name: /POST \/multiple/ }).click();
        await page.getByRole('tab', { name: 'Body' }).click();

        // Act

        await page.getByRole('button', { name: 'Auto Fill' }).click();
        await basePage.executeRequest();

        // Assert

        await expect(page.getByTestId('response-status-badge')).toContainText('201');

        const files = sentFiles(await readResponse(page));

        expect(files.length).toBeGreaterThan(0);

        for (const file of files) {
            expect(MIME_TYPE_BY_EXTENSION[file.extension]).toBe(file.mimeType);
        }
    });
});
