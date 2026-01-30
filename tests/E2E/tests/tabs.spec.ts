import { test, expect } from '../core/fixtures';

test('Multiple tabs support and isolated responses', async ({ page, basePage }) => {
    // Arrange

    await basePage.goto();

    // Act - Open first tab (Route 1)

    await page.getByRole('button', { name: 'verbs' }).click();
    await page.getByRole('button', { name: 'GET /verbs' }).click();

    // Act - Execute request in first tab

    await basePage.executeRequest();
    await expect(page.getByTestId('response-status-badge')).toContainText('200');

    // Assert - Verify it appears in Sidebar Tabs (Expanding first)
    await page.getByTestId('open-tabs-trigger').click();
    const tabs = page.getByTestId('sidebar-tab');
    await expect(tabs.filter({ hasText: /GET\s*verbs\/verbs/ })).toBeVisible();

    // Act - Open second tab (Route 2)

    await page.getByRole('button', { name: 'PATCH /verbs' }).click();

    // Assert - Verify second tab is created and response is empty

    await expect(tabs.filter({ hasText: /PATCH\s*verbs\/verbs/ })).toBeVisible();
    await expect(page.getByTestId('response-empty')).toBeVisible();

    // Act - Execute request in second tab

    await basePage.executeRequest();
    await expect(page.getByTestId('response-status-badge')).toContainText('422');

    // Act - Switch back to first tab via Open Tabs menu

    await tabs.filter({ hasText: /GET\s*verbs\/verbs/ }).click();

    // Assert - Verify isolated response (should be 200 from Route 1)

    await expect(page.getByTestId('response-status-badge')).toContainText('200');

    // Act - Switch back to second tab via Open Tabs menu

    await tabs.filter({ hasText: /PATCH\s*verbs\/verbs/ }).click();

    // Assert - Verify isolated response (should be 422 from Route 2)

    await expect(page.getByTestId('response-status-badge')).toContainText('422');

    // Act - Close a tab

    // Verify all tabs exist before closing
    await expect(tabs).toHaveCount(2);

    // Hover over the tab to show the close button and click it
    const getTab = tabs.filter({ hasText: /GET\s*verbs\/verbs/ });
    await getTab.hover();
    await getTab.getByTestId('sidebar-tab-close').click();

    // Assert - Verify tab is closed and only the other one remains
    await expect(tabs).toHaveCount(1);
    await expect(tabs.filter({ hasText: /GET\s*verbs\/verbs/ })).not.toBeVisible();
    await expect(tabs.filter({ hasText: /PATCH\s*verbs\/verbs/ })).toBeVisible();
});
