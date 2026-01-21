import { test, expect } from '../core/fixtures';

test('Link Sharing complete workflow', async ({ page, basePage }) => {
    // Arrange

    await basePage.goto();

    // Act - Pick an endpoint and navigate to it

    await page.getByRole('button', { name: 'shapes' }).click();
    await page.getByRole('button', { name: 'POST /nested-object' }).click();

    // Act - Add query parameters

    await page.getByTestId('request-builder-root').getByRole('tab', { name: 'Parameters' }).click();

    const { paramKey: param1Key, paramValue: param1Value } = await basePage.addQueryParameter('param1', 'value1', 0, true);
    const { paramKey: param2Key, paramValue: param2Value } = await basePage.addQueryParameter('param2', 'value2', 1, true);

    // Act - Add JSON payload

    await page.getByRole('tab', { name: 'Body' }).click();
    await page.getByRole('button', { name: 'Auto Fill' }).click();

    const bodyEditor = page.getByTestId('request-builder-root').locator('.cm-content');
    await expect(bodyEditor).toContainText('"name"');

    // Act - Add Bearer token authorization

    await page.getByTestId('request-builder-root').getByRole('tab', { name: 'Authorization' }).click();

    // Select Bearer token from the authorization type dropdown
    await page.getByTestId('request-authorization').getByRole('combobox').click();
    await page.getByRole('option', { name: 'Bearer Token' }).click();

    // Set the Bearer token value
    await page.getByPlaceholder('Token').fill('test-bearer-token-12345');

    // Act - Add custom headers

    await page.getByTestId('request-builder-root').getByRole('tab', { name: 'Headers' }).click();

    const { headerKey: header1Key, headerValue: header1Value } = await basePage.addHeader('X-Custom-Header-1', 'custom-value-1', 0, true);
    const { headerKey: header2Key, headerValue: header2Value } = await basePage.addHeader('X-Custom-Header-2', 'custom-value-2', 1, true);

    // Act - Execute the request

    await basePage.executeRequest();

    // Assert - Verify response was received
    await expect(page.getByTestId('response-status-badge')).toContainText('201');

    // Act - Copy the shareable link

    await page.getByTestId('request-options-button').click();
    await page.getByTestId('copy-shareable-link-option').click();

    // Wait for the shareable link dialog to appear
    await expect(page.getByTestId('shareable-link-content')).toBeVisible();

    // Extract the shareable link
    const shareableLink = await page.getByTestId('shareable-link-content').textContent();

    // Close the dialog
    await page.keyboard.press('Escape');

    // Act - Clear browser storage and refresh

    // Navigate to a neutral page first to ensure store doesn't re-persist currently active route
    await page.goto('/demo');

    await page.context().clearCookies();
    await page.evaluate(() => {
        localStorage.clear();
        sessionStorage.clear();
    });

    await page.reload();

    // Assert - Verify state is empty

    await expect(page.getByTestId('response-empty')).toBeVisible();
    await expect(page.getByRole('textbox', { name: '<endpoint>' })).toHaveValue('');

    // Act - Navigate to the copied shared link

    if (!shareableLink) {
        throw new Error('Shareable link was not extracted');
    }

    await page.goto(shareableLink);

    // Assert - Verify toast notification is shown

    await expect(page.getByText('Shared Request Restored')).toBeVisible();
    await expect(page.getByText('Request and response have been imported from the shareable link.')).toBeVisible();

    // Assert - Verify notification is shown

    await expect(page.getByTestId('imported-badge')).toBeVisible();

    // Assert - Verify endpoint is populated

    await expect(page.getByRole('textbox', { name: '<endpoint>' })).toHaveValue('_demo/shapes/nested-object');

    // Assert - Verify request tabs are populated properly

    // Parameters tab
    await page.getByTestId('request-builder-root').getByRole('tab', { name: 'Parameters' }).click();
    await expect(page.getByTestId('request-parameters').getByTestId('kv-key').first()).toHaveValue('param1');
    await expect(page.getByTestId('request-parameters').getByTestId('kv-value').first()).toHaveValue('value1');
    await expect(page.getByTestId('request-parameters').getByTestId('kv-key').nth(1)).toHaveValue('param2');
    await expect(page.getByTestId('request-parameters').getByTestId('kv-value').nth(1)).toHaveValue('value2');

    // Body tab
    await page.getByRole('tab', { name: 'Body' }).click();
    await expect(bodyEditor).toContainText('"name"');

    // Authorization tab
    await page.getByTestId('request-builder-root').getByRole('tab', { name: 'Authorization' }).click();

    // Verify Bearer Token is selected
    await expect(page.getByTestId('request-authorization').getByRole('combobox')).toHaveText('Bearer Token');

    // Verify token value is restored
    await expect(page.getByPlaceholder('Token')).toHaveValue('test-bearer-token-12345');

    // Headers tab
    await page.getByTestId('request-builder-root').getByRole('tab', { name: 'Headers' }).click();
    await expect(page.getByTestId('request-headers').getByTestId('kv-key').first()).toHaveValue('X-Custom-Header-1');
    await expect(page.getByTestId('request-headers').getByTestId('kv-value').first()).toHaveValue('custom-value-1');
    await expect(page.getByTestId('request-headers').getByTestId('kv-key').nth(1)).toHaveValue('X-Custom-Header-2');
    await expect(page.getByTestId('request-headers').getByTestId('kv-value').nth(1)).toHaveValue('custom-value-2');

    // Assert - Verify response tabs are populated properly

    await expect(page.getByTestId('response-status-badge')).toContainText('201');
    await expect(page.getByTestId('response-status-duration')).not.toHaveText('0ms');
    await expect(page.getByTestId('response-status-size')).not.toHaveText('0B');

    // Response tab
    await page.getByTestId('response-content').getByRole('tab', { name: 'Response' }).click();
    await expect(page.getByTestId('response-content')).toContainText('"data"');

    // Headers tab
    await page.getByTestId('response-content').getByRole('tab', { name: 'Headers' }).click();
    await expect(page.getByTestId('response-content')).toBeVisible();

    // Cookies tab
    await page.getByTestId('response-content').getByRole('tab', { name: 'Cookies' }).click();
    await expect(page.getByTestId('response-content')).toBeVisible();
});
