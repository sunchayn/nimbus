import { test, expect } from './fixtures';


test.describe('Search Functionality', () => {
    test('filters history by endpoint', async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();

        // Act - Send 3 requests to different endpoints
        await basePage.sendRequest('authentication', 'GET /show-logged-in-user');
        await basePage.sendRequest('shapes', 'POST /nested-object', { autoFill: true });
        await basePage.sendRequest('request-parameters', 'GET /');

        await basePage.openHistory();

        // Assert - Verify all 3 items are visible
        await expect(page.getByTestId('history-item')).toHaveCount(3);

        // Act - Search for "nested"
        await basePage.searchHistory('nested');

        // Assert - Only nested-object should be visible
        await expect(page.getByTestId('history-item')).toHaveCount(1);
        await expect(page.getByTestId('history-item').first()).toHaveAttribute(
            'data-endpoint',
            /_demo\/shapes\/nested-object/,
        );

        // Act - Search for "authentication"
        await basePage.searchHistory('authentication');

        // Assert - Only show-logged-in-user should be visible
        await expect(page.getByTestId('history-item')).toHaveCount(1);
        await expect(page.getByTestId('history-item').first()).toHaveAttribute(
            'data-endpoint',
            /_demo\/authentication\/show-logged-in-user/,
        );
    });

    test('search is case-insensitive', async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();
        await basePage.sendRequest('authentication', 'GET /show-logged-in-user');
        await basePage.openHistory();

        // Act & Assert - Search with uppercase
        await basePage.searchHistory('AUTHENTICATION');
        await expect(page.getByTestId('history-item')).toHaveCount(1);

        // Act & Assert - Search with mixed case
        await basePage.searchHistory('AuThEnTiCaTiOn');
        await expect(page.getByTestId('history-item')).toHaveCount(1);
    });

    test('shows empty state when no matches found', async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();
        await basePage.sendRequest('authentication', 'GET /show-logged-in-user');
        await basePage.openHistory();

        // Act - Search for non-existent endpoint
        await basePage.searchHistory('nonexistent-endpoint');

        // Assert - Should show empty state
        await expect(page.getByTestId('history-empty-state')).toBeVisible();
        await expect(page.getByTestId('history-empty-state')).toContainText(
            'No results found matching your keyword',
        );
        await expect(page.getByTestId('history-item')).toHaveCount(0);
    });

    test('clearing search shows all items', async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();
        await basePage.sendRequest('authentication', 'GET /show-logged-in-user');
        await basePage.sendRequest('shapes', 'POST /nested-object', { autoFill: true });
        await basePage.openHistory();

        // Act - Search for something
        await basePage.searchHistory('authentication');

        // Assert
        await expect(page.getByTestId('history-item')).toHaveCount(1);

        // Act - Clear search
        await page.getByTestId('history-search-input').clear();

        // Assert - All items should be visible again
        await expect(page.getByTestId('history-item')).toHaveCount(2);
    });
});

test.describe('Mutation Prevention', () => {
    test("rewinding doesn't mutate history - headers", async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();

        // Act - Send first request with custom header
        await page.getByRole("button", { name: "authentication" }).click();
        await page.getByRole("button", { name: "GET /show-logged-in-user" }).click();

        const { headerKey, headerValue } = await basePage.addHeader(
            "X-Test-Header",
            "original-value-1",
            2,
        );

        await basePage.executeRequest();

        // Act - Send second request with different header value
        await page.getByRole("button", { name: "shapes" }).click();
        await page.getByRole("button", { name: "POST /nested-object" }).click();

        await headerKey.fill("X-Test-Header");
        await headerValue.fill("original-value-2");

        await basePage.executeRequest();

        // Act - Rewind to first request
        await basePage.rewindToHistoryItem("show-logged-in-user");

        // Assert - Verify header is restored to original-value-1
        await expect(headerKey).toHaveValue("X-Test-Header");
        await expect(headerValue).toHaveValue("original-value-1");

        // Act - Modify the header
        await headerValue.fill("modified-value");

        // Act - Rewind to first request AGAIN
        await basePage.rewindToHistoryItem("show-logged-in-user");

        // Assert - CRITICAL: Header should STILL be original-value-1, NOT modified-value
        await expect(headerValue).toHaveValue("original-value-1");
    });

    test("rewinding doesn't mutate history - query parameters", async ({
        page,
        basePage,
    }) => {
        // Arrange
        await basePage.goto();

        // Act - Send first request with query parameter
        await page.getByRole("button", { name: "authentication" }).click();
        await page.getByRole("button", { name: "GET /show-logged-in-user" }).click();

        const { paramKey, paramValue } = await basePage.addQueryParameter(
            "test-param",
            "original-param-1",
        );

        await basePage.executeRequest();

        // Act - Send second request with different parameter value
        await page.getByRole("button", { name: "shapes" }).click();
        await page.getByRole("button", { name: "POST /nested-object" }).click();

        await paramKey.fill("test-param");
        await paramValue.fill("original-param-2");

        await basePage.executeRequest();

        // Act - Rewind to first request
        await basePage.rewindToHistoryItem("show-logged-in-user");

        // Assert - Verify parameter is restored
        await expect(paramKey).toHaveValue("test-param");
        await expect(paramValue).toHaveValue("original-param-1");

        // Act - Modify the parameter
        await paramValue.fill("modified-param");

        // Act - Rewind to first request AGAIN
        await basePage.rewindToHistoryItem("show-logged-in-user");

        // Assert - CRITICAL: Parameter should STILL be original-param-1, NOT modified-param
        await expect(paramValue).toHaveValue("original-param-1");
    });

    test("multiple rewinds maintain independent state", async ({
        page,
        basePage,
    }) => {
        // Arrange
        await basePage.goto();

        const requests = [
            {
                endpoint: "GET /show-logged-in-user",
                group: "authentication",
                headerValue: "request-1",
                autoFill: false,
            },
            {
                endpoint: "POST /nested-object",
                group: "shapes",
                headerValue: "request-2",
                autoFill: true,
            },
            {
                endpoint: "POST /array-of-primitives",
                group: null,
                headerValue: "request-3",
                autoFill: true,
            },
        ];

        // Act - Send 3 requests with different headers
        for (const request of requests) {
            if (request.group) {
                await page.getByRole("button", { name: request.group }).click();
            }

            await page.getByRole("button", { name: request.endpoint }).click();

            if (request.autoFill) {
                await page.getByRole("tab", { name: "Body" }).click();
                await page.getByRole("button", { name: "Auto Fill" }).click();
            }

            const { headerValue } = await basePage.addHeader(
                "X-Request-ID",
                request.headerValue,
            );

            await basePage.executeRequest();
        }

        // Act & Assert - Rewind to request #1, modify header
        await basePage.rewindToHistoryItem("show-logged-in-user");
        await expect(
            page.getByTestId("request-headers").getByTestId("kv-value").first(),
        ).toHaveValue("request-1");
        await page
            .getByTestId("request-headers")
            .getByTestId("kv-value")
            .first()
            .fill("modified-1");

        // Act & Assert - Rewind to request #2, modify header
        await basePage.rewindToHistoryItem("nested-object");
        await expect(
            page.getByTestId("request-headers").getByTestId("kv-value").first(),
        ).toHaveValue("request-2");
        await page
            .getByTestId("request-headers")
            .getByTestId("kv-value")
            .first()
            .fill("modified-2");

        // Act & Assert - Rewind to request #1 AGAIN
        await basePage.rewindToHistoryItem("show-logged-in-user");
        await expect(
            page.getByTestId("request-headers").getByTestId("kv-value").first(),
        ).toHaveValue("request-1");

        // Act & Assert - Rewind to request #3
        await basePage.rewindToHistoryItem("array-of-primitives");
        await expect(
            page.getByTestId("request-headers").getByTestId("kv-value").first(),
        ).toHaveValue("request-3");
    });
});

test.describe('Rewind Functionality', () => {
    test('basic rewind flow', async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();

        // Assert - Initial State
        await expect(page.getByTestId('response-empty')).toBeVisible();

        // Act - Send first request (Show logged in user)
        await basePage.sendRequest('authentication', 'GET /show-logged-in-user');

        // Act - Send second request (Nested object)
        await basePage.sendRequest('shapes', 'POST /nested-object', { autoFill: true });

        // Assert
        await expect(page.getByRole('textbox', { name: '<endpoint>' })).toHaveValue('_demo/shapes/nested-object');

        // Act - Open history and go back to first request
        await basePage.rewindToHistoryItem('show-logged-in-user');

        // Assert - Verify Rewind
        await expect(page.getByRole('textbox', { name: '<endpoint>' })).toHaveValue(
            '_demo/authentication/show-logged-in-user',
        );
        await expect(page.getByTestId('request-builder-root')).toContainText('GET');
        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');
    });

    test('preserves request body when rewinding', async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();

        // Act - Send POST request with body
        await page.getByRole('button', { name: 'shapes' }).click();
        await page.getByRole('button', { name: 'POST /nested-object' }).click();
        await page.getByRole('button', { name: 'Auto Fill' }).click();

        const bodyEditor = page.getByTestId('request-builder-root').locator('.cm-content');

        // Assert - Verify body is filled
        await expect(bodyEditor).toContainText('"name"');

        // Act - Send request and then another request
        await basePage.executeRequest();

        await basePage.sendRequest(null, 'POST /array-of-primitives');

        // Act - Rewind to POST request
        await basePage.rewindToHistoryItem('nested-object');

        // Assert - Verify body is restored
        await expect(bodyEditor).toContainText('"name"');
    });

    test('updates all UI elements on rewind', async ({ page, basePage }) => {
        // Arrange
        await basePage.goto();

        // Act - Send first request with headers and params
        await page.getByRole('button', { name: 'authentication' }).click();
        await page.getByRole('button', { name: 'GET /show-logged-in-user' }).click();

        await basePage.addHeader('X-Custom', 'header-value');

        await basePage.addQueryParameter('query-key', 'query-value');

        await basePage.executeRequest();

        // Act - Send second request (different everything)
        await basePage.sendRequest('shapes', 'POST /nested-object', { autoFill: true });

        // Act - Rewind to first request
        await basePage.rewindToHistoryItem('show-logged-in-user');

        // Assert - Verify ALL elements are updated
        await expect(page.getByTestId('request-builder-root')).toContainText('GET');
        await expect(page.getByRole('textbox', { name: '<endpoint>' })).toHaveValue(
            '_demo/authentication/show-logged-in-user',
        );

        await basePage.page.getByTestId('request-builder-root').getByRole('tab', { name: 'Headers' }).click();
        await expect(
            page
                .getByTestId("request-headers")
                .getByTestId("kv-key")
                .first(),
        ).toHaveValue("X-Custom");
        await expect(
            page
                .getByTestId("request-headers")
                .getByTestId("kv-value")
                .first(),
        ).toHaveValue("header-value");

        await page.getByRole('tab', { name: 'Parameters' }).click();

        await expect(page.getByTestId('request-parameters').getByTestId('kv-key').first()).toHaveValue('query-key');

        await expect(
            page
                .getByTestId("request-parameters")
                .getByTestId("kv-value")
                .first(),
        ).toHaveValue("query-value");

        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');
    });
});
