import type { Page } from '@playwright/test';
import { test, expect } from '../core/fixtures';

const rows = (page: Page) => page.locator('[data-testid=parameter-row]:visible');

const selectPayloadType = async (page: Page, current: RegExp, next: RegExp) => {
    await page.getByRole('combobox').filter({ hasText: current }).first().click();
    await page.getByRole('option', { name: next }).click();
};

const openFormBody = async (page: Page) => {
    await page.getByRole('tab', { name: 'Body' }).click();
    await selectPayloadType(page, /Empty|JSON|Plain Text|Form/, /Form/);
};

const addFileRow = async (page: Page, index: number, key: string, name: string) => {
    await page.getByTestId('request-body').getByTestId('add-button').click();

    const row = rows(page).nth(index);

    await row.getByTestId('type-selector').click();
    await page.getByRole('option', { name: 'File' }).click();
    await row.getByTestId('kv-key').fill(key);
    await row.locator('input[type=file]').setInputFiles({
        name,
        mimeType: 'text/plain',
        buffer: Buffer.from(`content of ${name}`),
    });
};

test.describe('File uploads', () => {
    test.beforeEach(async ({ page, basePage }) => {
        await basePage.goto();
        await page.getByRole('button', { name: 'uploads' }).click();
        await page.getByRole('button', { name: 'multipart' }).click();
    });

    test('sends several files under an array key as multipart', async ({
        page,
        basePage,
    }) => {
        // Arrange

        await page.getByRole('button', { name: /POST \/multiple/ }).click();
        await openFormBody(page);

        await rows(page).first().getByTestId('kv-key').fill('title');
        await rows(page).first().getByTestId('kv-value').fill('Holiday');

        await addFileRow(page, 1, 'files[]', 'first.txt');
        await addFileRow(page, 2, 'files[]', 'second.txt');

        // Act

        await basePage.executeRequest();

        // Assert

        await expect(page.getByTestId('response-status-badge')).toContainText('201');
        await expect(page.getByTestId('response-content')).toContainText('first.txt');
        await expect(page.getByTestId('response-content')).toContainText('second.txt');
    });

    test('keeps the file name but asks to select it again after a reload', async ({
        page,
        basePage,
    }) => {
        // Arrange

        await page.getByRole('button', { name: /POST \/single/ }).click();
        await openFormBody(page);
        await addFileRow(page, 0, 'file', 'report.txt');

        // Act

        await page.reload();
        await page.getByRole('tab', { name: 'Body' }).click();

        // Assert

        await expect(page.getByTestId('kv-file').first()).toContainText(
            'report.txt (select the file again)',
        );

        await basePage.executeRequest();

        await expect(page.getByTestId('response-status-badge')).toContainText('422');
    });
});
