import { expect, type Page } from '@playwright/test';

export class BasePage {
    constructor(public readonly page: Page) { }

    async goto() {
        await this.page.goto('/demo');
    }

    async executeRequest() {
        await this.page.getByTestId('endpoint-input').click();
        await this.page.waitForTimeout(300); // <- Wait for next tick.

        await this.page.getByRole('button', { name: 'Send ( )' }).click();

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
}
