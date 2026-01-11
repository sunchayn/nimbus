import { test, expect } from '@playwright/test';

test('Page loads', async ({ page }) => {
  await page.goto('/demo');

  await expect(page).toHaveTitle('E2E Tests - Nimbus');

  await expect(page.getByTestId('app-tabs-container')).toBeInViewport();
});

test('Clicking around', async ({ page }) => {
    // Note: Generated with Playwright codegen.

    await page.goto('/demo');
    await expect(page.getByTestId('response-empty')).toBeVisible();
    await page.getByRole('tab', { name: 'Parameters' }).click();
    await expect(page.getByText('Pick an endpoint to start')).toBeVisible();
    await page.getByRole('tab', { name: 'Authorization' }).click();
    await expect(page.getByRole('heading', { name: 'Please log in first' })).toBeVisible();
    await page.getByRole('tab', { name: 'Headers' }).click();
    await expect(page.getByTestId('response-status-text')).toContainText('No request yet');
    await page.getByRole('button', { name: 'authentication' }).click();
    await expect(page.getByRole('button', { name: 'GET /show-logged-in-user' })).toBeVisible();
    await page.getByRole('button', { name: 'inline-validation' }).click();
    await page.getByRole('button', { name: 'shapes' }).click();
    await page.getByRole('button', { name: 'POST /nested-object' }).click();
    await expect(page.getByRole('textbox', { name: '<endpoint>' })).toHaveValue('_demo/shapes/nested-object');
    await expect(page.getByTestId('request-builder-root')).toContainText('POST');
    await page.getByRole('tab', { name: 'Body' }).click();
    await expect(page.getByTestId('app-tabs-container').getByRole('textbox')).toContainText('"ip": "<placeholder>",');
    await page.getByRole('button', { name: 'Auto Fill' }).click();
    await page.getByRole('button', { name: 'Send ( )' }).click();
    await expect(page.getByTestId('response-status-text')).toContainText('Success');
    await expect(page.getByTestId('response-status-badge')).toContainText('201 - Created');
    await expect(page.getByTestId('response-content').getByRole('textbox')).toContainText('"message": "Order created successfully",');
    await page.getByTestId('response-content').getByRole('tab', { name: 'Headers' }).click();
    await expect(page.getByRole('cell', { name: 'Host' })).toBeVisible();
    await page.getByRole('tab', { name: 'Cookies' }).click();
    await expect(page.getByRole('cell', { name: 'appearance' })).toBeVisible();
    await expect(page.getByRole('rowgroup')).toContainText('Non-Encrypted Value light');
    await page.getByRole('button', { name: 'Decrypt' }).click();
    await expect(page.getByRole('combobox').filter({ hasText: 'POST' })).toBeVisible();
    await page.getByRole('combobox').filter({ hasText: 'POST' }).click();
});
