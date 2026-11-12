import { expect, type Locator, type Page } from '@playwright/test';

interface ListedRequest {
    id: string;
    name: string;
    folder: string;
    hash: string;
}

interface Listing {
    folders: string[];
    requests: ListedRequest[];
}

export class WorkspacePage {
    /**
     * Marks everything this instance creates, so parallel tests never clean up each other's files.
     */
    public readonly prefix = `e2e-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;

    constructor(public readonly page: Page) {}

    /**
     * A name that is unique per test, because the workspace lives on disk and is shared by all tests.
     */
    name(label: string): string {
        return `${this.prefix}-${label}`;
    }

    get panel(): Locator {
        return this.page.getByTestId('workspace-panel');
    }

    async openPanel() {
        await this.page
            .getByTestId('explorer-sidebar-tabs')
            .getByRole('tab', { name: 'Workspace' })
            .click();
        await expect(this.panel).toBeVisible();
    }

    async openRoutes() {
        await this.page
            .getByTestId('explorer-sidebar-tabs')
            .getByRole('tab', { name: 'Routes' })
            .click();
        await expect(this.page.getByTestId('routes-panel')).toBeVisible();
    }

    item(name: string): Locator {
        return this.panel.getByTestId('workspace-item').filter({ hasText: name });
    }

    folder(path: string): Locator {
        return this.panel.locator(`[data-testid="workspace-folder"][data-path="${path}"]`);
    }

    folderToggle(path: string): Locator {
        return this.folder(path).getByTestId('workspace-folder-toggle').first();
    }

    sidebarTab(title: string): Locator {
        return this.page.getByTestId('sidebar-tab').filter({ hasText: title });
    }

    async openOptionsMenu() {
        await this.page.getByTestId('request-options-button').click();
    }

    async chooseOption(testId: string) {
        await this.openOptionsMenu();
        await this.page.getByTestId(testId).click();
    }

    async saveCurrentTab(name: string, folder = '') {
        await this.chooseOption('save-request-option');

        await this.page.getByTestId('save-request-name').fill(name);

        if (folder) {
            await this.page.getByTestId('save-request-folder').fill(folder);
        }

        await this.page.getByTestId('save-request-submit').click();
        await expect(this.page.getByTestId('save-request-form')).toBeHidden();
    }

    async createFolder(path: string) {
        await this.panel.getByTestId('workspace-new-folder').click();
        await this.page.getByTestId('workspace-folder-input').fill(path);
        await this.page.getByTestId('workspace-folder-input').press('Enter');
    }

    async rightClick(locator: Locator) {
        await locator.click({ button: 'right' });
    }

    async chooseContextItem(name: string) {
        await this.page.getByRole('menuitem', { name, exact: true }).click();
    }

    /*
     * Direct API access, used to arrange and clean up the files on disk.
     */

    private url(path = ''): string {
        return `/demo/api/workspace${path}`;
    }

    async list(): Promise<Listing> {
        const response = await this.page.request.get(this.url());

        return response.json();
    }

    async findByName(name: string): Promise<ListedRequest | undefined> {
        return (await this.list()).requests.find(request => request.name === name);
    }

    async fetchStored(id: string) {
        const response = await this.page.request.get(this.url(`/requests/${id}`));

        return (await response.json()) as {
            request: Record<string, unknown> & { headers: unknown[] };
            hash: string;
        };
    }

    /**
     * Rewrites a request file behind the app's back, like a git pull would.
     */
    async changeOnDisk(id: string, changes: Record<string, unknown>) {
        const stored = await this.fetchStored(id);
        const response = await this.page.request.put(this.url(`/requests/${id}`), {
            data: { ...stored.request, ...changes, hash: stored.hash },
        });

        expect(response.ok()).toBe(true);
    }

    /**
     * Removes every request and folder a test created, identified by the prefix.
     */
    async cleanUp(extraNames: string[] = []) {
        const prefix = this.prefix;
        const listing = await this.list();

        for (const request of listing.requests) {
            if (
                request.name.startsWith(prefix) ||
                request.folder.startsWith(prefix) ||
                extraNames.includes(request.name)
            ) {
                await this.page.request.delete(this.url(`/requests/${request.id}`));
            }
        }

        const folders = listing.folders
            .filter(folder => folder.startsWith(prefix))
            .sort((a, b) => b.length - a.length);

        for (const folder of folders) {
            await this.page.request.delete(this.url('/folders'), { data: { folder } });
        }
    }
}
