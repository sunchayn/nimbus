import { test, expect } from '../core/fixtures';
import {getByTestId} from "@testing-library/dom";

test.describe('Environment Variables', () => {
    test('sanity check: creating, managing and using environment variables', async ({ page, basePage }) => {
        // 1. Arrange - Start on the demo page
        await basePage.goto();

        // 2. Act - Navigate to Environments and create an environment
        await basePage.goToEnvironments();
        await basePage.addEnvironment();

        // 3. Act - Rename environment and add variables
        await basePage.renameEditingEnvironment('Staging Environment');

        // Add first variable
        await basePage.setVariable(0, 'user_id', '12345');

        // Add second variable
        await page.getByTestId('kv-container').getByRole('button', { name: 'Add' }).click();
        await basePage.setVariable(1, 'api_token', 'staging-secret-token');

        // 4. Assert - Verify the environment exists, then make it the active one
        await expect(page.getByTestId('environment-item').filter({ hasText: 'Staging Environment' })).toBeVisible();
        await basePage.switchEnvironment('Staging Environment');
        await expect(page.getByTestId('environment-active-badge')).toBeVisible();

        // 5. Act - Go back to HTTP Client and use variables
        await basePage.goToHttpClient();

        // Select any route to expand the request builder
        await page.getByRole('button', { name: 'authentication' }).click();
        await page.getByRole('button', { name: 'GET /show-logged-in-user' }).click();

        // Use user_id in endpoint
        const endpointInput = page.getByTestId('endpoint-input');
        await endpointInput.fill('_demo/users/{{user_id}}');

        // Verify visual feedback (grey highlight for resolved)
        const resolvedSegment = page.locator('[data-segment-index].text-highlighted').first();
        await expect(resolvedSegment).toBeVisible();
        await expect(resolvedSegment).toHaveText('{{user_id}}');

        // Check for tooltip with resolved value
        // await resolvedSegment.hover();
        // await page.waitForTimeout(300); // <- Wait for next tick.
        //
        // const tooltip = page.locator('[role="tooltip"]');
        // await expect(tooltip).toBeVisible();
        // await expect(tooltip).toContainText('12345');

        // Assert resolved URL preview
        await page.getByRole('tab', { name: 'Parameters' }).click();

        await expect(page.locator('p').filter({ hasText: '_demo/users/12345' })).toBeVisible();

        // Use api_token in headers
        await basePage.addHeader('Authorization', 'Bearer {{api_token}}');

        const headerValueInput = page.getByTestId('request-headers').getByTestId('kv-value').first();
        await expect(headerValueInput).toHaveValue('Bearer {{api_token}}');

        // Verify another segment is resolved in headers
        const headerResolvedSegment = page.getByTestId('request-headers').locator('[data-segment-index].text-highlighted');
        await expect(headerResolvedSegment).toBeVisible();
        await expect(headerResolvedSegment).toHaveText('{{api_token}}');

        // 6. Act - Switch environment and verify resolution changes
        await basePage.goToEnvironments();
        await basePage.addEnvironment();
        await basePage.renameEditingEnvironment('Production Environment');
        await basePage.switchEnvironment('Production Environment');

        // Set different values
        await basePage.setVariable(0, 'user_id', '67890');

        // Go back and verify
        await basePage.goToHttpClient();
        await expect(endpointInput).toHaveValue('_demo/users/{{user_id}}');

        await resolvedSegment.waitFor();
        // await resolvedSegment.hover();
        // await expect(tooltip).toContainText('67890');

        // Assert resolved URL preview again
        await page.getByRole('tab', { name: 'Parameters' }).click();
        await expect(page.locator('p').filter({ hasText: '_demo/users/67890' })).toBeVisible();
    });

    test('shows warning for empty variables and error for missing ones', async ({ page, basePage }) => {
        await basePage.goto();
        await basePage.goToEnvironments();
        await basePage.addEnvironment();
        await basePage.renameEditingEnvironment('Test Env');
        await basePage.switchEnvironment('Test Env');

        // Variable with empty value
        await basePage.setVariable(0, 'empty_var', '');

        await basePage.goToHttpClient();
        await page.getByRole('button', { name: 'authentication' }).click();
        await page.getByRole('button', { name: 'GET /show-logged-in-user' }).click();

        const endpointInput = page.getByTestId('endpoint-input');

        // Test Empty variable (Orange highlight)
        await endpointInput.fill('{{empty_var}}');
        const emptySegment = page.locator('[data-segment-index="0"].text-warning')
        await expect(emptySegment).toBeVisible();
        await expect(emptySegment).toHaveText('{{empty_var}}');

        // await emptySegment.hover();
        // await expect(page.locator('[role="tooltip"]')).toContainText('Variable value is empty');

        // Test Missing variable (Red highlight)
        await endpointInput.fill('{{missing_var}}');
        const missingSegment = page.locator('[data-segment-index="0"].text-error');
        await expect(missingSegment).toBeVisible();
        await expect(missingSegment).toHaveText('{{missing_var}}');

        // await missingSegment.hover();
        // await expect(page.locator('[role="tooltip"]')).toContainText('Variable cannot be found');
    });

    test('switching to a non-current-host environment is reflected in the switcher', async ({ page, basePage }) => {
        await basePage.goto();
        await basePage.goToEnvironments();
        await basePage.addEnvironment();
        await basePage.renameEditingEnvironment('Remote');
        await basePage.switchEnvironment('Remote');

        await expect(page.getByTestId('environment-switcher')).toHaveText(/Remote/);
        await expect(page.getByTestId('environment-switcher')).toHaveAttribute('data-environment-current-host', 'false');

        await basePage.switchEnvironment('Local');
        await expect(page.getByTestId('environment-switcher')).toHaveAttribute('data-environment-current-host', 'true');
    });
});
