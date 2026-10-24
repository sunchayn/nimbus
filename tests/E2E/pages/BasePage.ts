import { expect, type Page } from '@playwright/test';

export class BasePage {
    constructor(public readonly page: Page) { }

    async goto() {
        await this.page.goto('/demo');
    }

    async executeRequest() {
        await this.page.getByTestId('endpoint-input').click();
        await this.page.waitForTimeout(300); // <- Wait for next tick.

        await this.page.getByRole('button', { name: 'Send', exact: true }).click();

        await expect(this.page.getByTestId('response-status-text')).not.toContainText(
            'Pending',
        );
    }

    async sendRequest(
        group: string | null,
        endpoint: string,
        options: { autoFill?: boolean; expectedStatus?: string } = {},
    ) {
        const { autoFill = false } = options;

        if (group) {
            await this.page.getByRole('button', { name: group }).click();
        }

        await this.page.getByRole('button', { name: endpoint, exact: true }).click();

        if (autoFill) {
            await this.page.getByRole('tab', { name: 'Body' }).click();
            await this.page.getByRole('button', { name: 'Auto Fill' }).click();
        }

        await this.executeRequest();
    }

    async addHeader(key: string, value: string, index: number = 0, skipTabNavigation: boolean = false) {
        if (!skipTabNavigation) {
            await this.page
                .getByTestId('request-builder-root')
                .getByRole('tab', { name: 'Headers' })
                .click();
        }

        await this.page
            .getByTestId('request-headers')
            .getByRole('button', { name: 'Add' })
            .click();

        const headerKey =
            index === 0
                ? this.page.getByTestId('request-headers').getByTestId('kv-key').first()
                : this.page.getByTestId('request-headers').getByTestId('kv-key').nth(index);

        const headerValue =
            index === 0
                ? this.page.getByTestId('request-headers').getByTestId('kv-value').first()
                : this.page.getByTestId('request-headers').getByTestId('kv-value').nth(index);

        await headerKey.fill(key);
        await headerValue.fill(value);

        return { headerKey, headerValue };
    }

    async addQueryParameter(key: string, value: string, index: number = 0, skipTabNavigation: boolean = false) {
        if (!skipTabNavigation) {
            await this.page
                .getByTestId('request-builder-root')
                .getByRole('tab', { name: 'Parameters' })
                .click();
        }

        await this.page
            .getByTestId('request-parameters')
            .getByRole('button', { name: 'Add' })
            .click();

        const paramKey =
            index === 0
                ? this.page.getByTestId('request-parameters').getByTestId('kv-key').first()
                : this.page.getByTestId('request-parameters').getByTestId('kv-key').nth(index);

        const paramValue =
            index === 0
                ? this.page.getByTestId('request-parameters').getByTestId('kv-value').first()
                : this.page.getByTestId('request-parameters').getByTestId('kv-value').nth(index);

        await paramKey.fill(key);
        await paramValue.fill(value);

        return { paramKey, paramValue };
    }

    async openHistory() {
        await this.page.getByTestId('response-history-trigger').click();
    }

    async rewindToHistoryItem(searchText: string) {
        await this.openHistory();
        await this.page
            .getByTestId('history-item')
            .filter({ has: this.page.getByText(searchText) })
            .click();
    }

    async searchHistory(query: string) {
        await this.page.getByTestId('history-search-input').fill(query);
    }

    async switchApplication(applicationKey: string) {
        await this.page.goto(`/demo?application=${applicationKey}`);
        await this.page.waitForLoadState('networkidle');
    }

    async getHeaderCount() {
        return await this.page.getByTestId('request-headers').getByTestId('kv-key').count();
    }

    async getHeaderAt(index: number) {
        const headerKey = this.page.getByTestId('request-headers').getByTestId('kv-key').nth(index);
        const headerValue = this.page.getByTestId('request-headers').getByTestId('kv-value').nth(index);

        return { headerKey, headerValue };
    }

    /*
     * Environment Management.
     */

    async goToEnvironments() {
        await this.page.getByTestId('environment-switcher').click();
        await this.page.getByRole('menuitem', { name: 'Manage environments' }).click();
        await expect(this.page.getByTestId('environment-page')).toBeVisible();
    }

    async addCollection() {
        await this.page.getByTestId('add-collection-btn').click();
    }

    async renameActiveCollection(name: string) {
        // AppEditable wraps the Reka Editable, so we need to click the preview to edit.
        const editor = this.page.getByTestId('collection-name-editor');
        await editor.click({ force: true });
        
        const input = editor.locator('input');
        await input.fill(name);
        await input.press('Enter');
    }

    async setVariable(index: number, key: string, value: string) {
        const container = this.page.getByTestId('kv-container');
        const keyInput = container.getByTestId('kv-key').nth(index);
        const valueInput = container.getByTestId('kv-value').nth(index);

        await keyInput.fill(key);
        await valueInput.fill(value);
    }

    async selectCollection(name: string) {
        await this.page.getByTestId('collection-item').filter({ hasText: name }).click();
    }

    async goToHttpClient() {
        await this.page.getByRole('button', { name: 'HTTP Client' }).click();
    }
}
