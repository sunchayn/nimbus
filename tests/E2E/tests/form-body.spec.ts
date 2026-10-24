import type { Page } from '@playwright/test';
import { test, expect } from '../core/fixtures';

const keys = (page: Page) => page.locator('[data-testid=kv-key]:visible');

const selectPayloadType = async (page: Page, current: RegExp, next: RegExp) => {
    await page.getByRole('combobox').filter({ hasText: current }).first().click();
    await page.getByRole('option', { name: next }).click();
};

const openFormBody = async (page: Page) => {
    await page.getByRole('tab', { name: 'Body' }).click();
    await selectPayloadType(page, /Empty|JSON|Plain Text|Form/, /Form/);
};

test('Form body keeps the enabled flag when the payload type is switched', async ({
    page,
    basePage,
}) => {
    // Arrange

    await basePage.goto();
    await page.getByRole('button', { name: 'verbs' }).click();
    await page.getByRole('button', { name: 'POST /verbs' }).click();
    await openFormBody(page);

    // Act

    const toggle = page
        .locator('[data-testid=parameter-row]:visible')
        .first()
        .getByRole('switch');
    await toggle.click();
    await expect(toggle).not.toBeChecked();

    await selectPayloadType(page, /Form/, /JSON/);
    await selectPayloadType(page, /JSON/, /Form/);

    // Assert

    await expect(toggle).not.toBeChecked();
});

test('Form body edit made right before a tab switch is kept', async ({
    page,
    basePage,
}) => {
    // Arrange

    await basePage.goto();
    await page.getByRole('button', { name: 'verbs' }).click();
    await page.getByRole('button', { name: 'POST /verbs' }).click();
    await openFormBody(page);
    await page.getByRole('button', { name: 'PATCH /verbs' }).click();
    await page.getByRole('tab', { name: 'Body' }).click();
    await openFormBody(page);

    const tabs = page.getByTestId('sidebar-tab');

    // Act

    await tabs.filter({ hasText: /POST/ }).click();
    await keys(page).first().fill('typed');
    await tabs.filter({ hasText: /PATCH/ }).click();
    await expect(keys(page).first()).not.toHaveValue('typed');
    await tabs.filter({ hasText: /POST/ }).click();

    // Assert

    await expect(keys(page).first()).toHaveValue('typed');
});
