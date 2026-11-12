import { test as base, expect } from '../core/fixtures';
import { WorkspacePage } from '../pages/WorkspacePage';

// Requests created by the migration take the tab title, so they are tracked here.
const migratedNames: string[] = [];

const test = base.extend<{ workspace: WorkspacePage }>({
    workspace: async ({ page }, use) => {
        const workspace = new WorkspacePage(page);

        await use(workspace);
        await workspace.cleanUp(migratedNames);
    },
});

test.describe('Workspace panel', () => {
    test('shows the Workspace tab beside Routes and switches between them', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        await basePage.goto();

        // Act

        await workspace.openPanel();

        // Assert

        await expect(page.getByTestId('routes-panel')).toBeHidden();
        await expect(workspace.panel.getByTestId('workspace-new-folder')).toBeVisible();

        // Act

        await workspace.openRoutes();

        // Assert

        await expect(workspace.panel).toBeHidden();
    });

    test('remembers the selected sidebar tab after a reload', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        await basePage.goto();
        await workspace.openPanel();

        // Act

        await page.reload();

        // Assert

        await expect(workspace.panel).toBeVisible();
    });
});

test.describe('Folders', () => {
    test('creates a nested folder and deletes it', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const parent = workspace.name('folder');
        const path = `${parent}/admin`;

        await basePage.goto();
        await workspace.openPanel();

        // Act - Create

        await workspace.createFolder(path);

        // Assert

        await expect(workspace.folder(parent)).toBeVisible();
        await expect(workspace.folder(path)).toBeAttached();
        expect((await workspace.list()).folders).toContain(path);

        // Act - Delete the child folder

        await workspace.rightClick(workspace.folderToggle(path));
        await workspace.chooseContextItem('Delete folder');
        await page.getByTestId('workspace-delete-folder-confirm').click();

        // Assert

        await expect(workspace.folder(path)).toHaveCount(0);
        await expect(workspace.folder(parent)).toBeVisible();
        expect((await workspace.list()).folders).not.toContain(path);
    });

    test('renames a folder', async ({ page, basePage, workspace }) => {
        // Arrange

        const original = workspace.name('rename-folder');
        const renamed = workspace.name('renamed-folder');

        await basePage.goto();
        await workspace.openPanel();
        await workspace.createFolder(original);
        await expect(workspace.folder(original)).toBeVisible();

        // Act

        await workspace.rightClick(workspace.folderToggle(original));
        await workspace.chooseContextItem('Rename folder');

        const input = page.getByTestId('workspace-folder-rename-input');
        await input.fill(renamed);
        await input.press('Enter');

        // Assert

        await expect(workspace.folder(renamed)).toBeVisible();
        await expect(workspace.folder(original)).toHaveCount(0);

        const { folders } = await workspace.list();
        expect(folders).toContain(renamed);
        expect(folders).not.toContain(original);
    });

    test('toggles a folder open and closed', async ({ basePage, workspace }) => {
        // Arrange

        const folder = workspace.name('toggle');

        await basePage.goto();
        await workspace.openPanel();
        await workspace.createFolder(folder);

        const toggle = workspace.folderToggle(folder);
        await expect(toggle).toBeVisible();
        const wasOpen = await toggle.getAttribute('aria-expanded');

        // Act

        await toggle.click();

        // Assert

        await expect(toggle).toHaveAttribute(
            'aria-expanded',
            wasOpen === 'true' ? 'false' : 'true',
        );
    });
});

test.describe('Saving requests', () => {
    test('saves a request into a folder and shows it in the tree', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('save');
        const folder = workspace.name('saved-in');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();

        // Act

        await workspace.saveCurrentTab(name, folder);
        await workspace.openPanel();

        // Assert

        await expect(workspace.folder(folder)).toBeVisible();
        await expect(workspace.item(name)).toBeVisible();
        await expect(workspace.item(name)).toContainText('GET');
        await expect(workspace.sidebarTab(name)).toBeVisible();

        const saved = await workspace.findByName(name);
        expect(saved?.folder).toBe(folder);
    });

    test('does not save when the name is empty', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.chooseOption('save-request-option');

        // Act

        await page.getByTestId('save-request-name').fill('   ');

        // Assert

        await expect(page.getByTestId('save-request-submit')).toBeDisabled();
        await expect(page.getByTestId('save-request-form')).toBeVisible();
    });

    test('keeps the headers, query and body in the saved file', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('content');

        await basePage.goto();
        await page.getByRole('button', { name: 'inline-validation' }).click();
        await page.getByRole('button', { name: 'shapes' }).click();
        await page.getByRole('button', { name: 'POST /simple' }).click();
        await page.getByRole('tab', { name: 'Body' }).click();
        await page.getByRole('button', { name: 'Auto Fill' }).click();
        await basePage.addHeader('x-e2e-header', 'header-value');
        await basePage.addQueryParameter('e2e-key', 'e2e-value');

        // Act

        await workspace.saveCurrentTab(name);

        // Assert

        const saved = await workspace.findByName(name);
        expect(saved).toBeDefined();

        const { request } = await workspace.fetchStored(saved!.id);
        expect(request.method).toBe('POST');
        expect(JSON.stringify(request.headers)).toContain('x-e2e-header');
        expect(JSON.stringify(request.query)).toContain('e2e-key');
        expect(JSON.stringify(request.body)).toContain('name');
    });

    test('marks a saved tab as unsaved after a change and saves the change', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('dirty');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);
        await workspace.openPanel();

        await expect(workspace.item(name).getByTestId('workspace-item-dirty')).toHaveCount(0);

        // Act - Change the request

        await basePage.addHeader('x-dirty', 'one');

        // Assert

        const item = workspace.item(name);
        await expect(item.getByTestId('workspace-item-dirty')).toBeAttached();

        // Act - Save from the row

        await item.hover();
        await item.getByTestId('workspace-save').click();

        // Assert

        await expect(item.getByTestId('workspace-item-dirty')).toHaveCount(0);

        const saved = await workspace.findByName(name);
        const { request } = await workspace.fetchStored(saved!.id);
        expect(JSON.stringify(request.headers)).toContain('x-dirty');
    });

    test('saves changes from the options menu', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('menu-save');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);

        // Assert - Nothing to save yet

        await workspace.openOptionsMenu();
        await expect(page.getByTestId('save-request-option')).toBeVisible();
        await expect(
            page.getByRole('menuitem', { name: 'Save changes' }),
        ).toHaveAttribute('data-disabled', '');
        await page.keyboard.press('Escape');

        // Act

        await basePage.addHeader('x-menu', 'one');
        await workspace.chooseOption('save-request-option');

        // Assert

        await expect
            .poll(async () => {
                const saved = await workspace.findByName(name);

                return JSON.stringify((await workspace.fetchStored(saved!.id)).request.headers);
            })
            .toContain('x-menu');
    });

    test('saves a copy with Save as', async ({ page, basePage, workspace }) => {
        // Arrange

        const name = workspace.name('original');
        const copy = workspace.name('copy');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);

        // Act

        await workspace.chooseOption('save-as-option');
        await page.getByTestId('save-request-name').fill(copy);
        await page.getByTestId('save-request-submit').click();
        await expect(page.getByTestId('save-request-form')).toBeHidden();

        // Assert

        await workspace.openPanel();
        await expect(workspace.item(name)).toBeVisible();
        await expect(workspace.item(copy)).toBeVisible();

        const original = await workspace.findByName(name);
        const duplicate = await workspace.findByName(copy);
        expect(original?.id).not.toBe(duplicate?.id);
    });
});

test.describe('Opening requests', () => {
    test('opens a saved request in a tab with its saved values', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('open');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await basePage.addHeader('x-saved', 'saved-value');
        await workspace.saveCurrentTab(name);

        // Act - Close the tab, then open the request again from the workspace

        const tab = workspace.sidebarTab(name);
        await tab.hover();
        await tab.getByTestId('sidebar-tab-close').click();
        await expect(tab).toHaveCount(0);

        await workspace.openPanel();
        await workspace.item(name).click();

        // Assert

        await expect(workspace.sidebarTab(name)).toBeVisible();

        await page
            .getByTestId('request-builder-root')
            .getByRole('tab', { name: 'Headers' })
            .click();

        const keys = page.getByTestId('request-headers').getByTestId('kv-key');
        await expect(keys.filter({ hasText: '' }).first()).toBeVisible();
        await expect(
            page
                .getByTestId('request-headers')
                .locator('input[data-testid="kv-key"]')
                .and(page.locator('[value="x-saved"]')),
        ).toHaveCount(1);
    });

    test('opening a saved request twice reuses its tab', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('reuse');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);
        await workspace.openPanel();

        // Act

        await workspace.item(name).click();
        await workspace.item(name).click();

        // Assert

        await expect(workspace.sidebarTab(name)).toHaveCount(1);
    });

    test('can send a request opened from the workspace', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('send');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);

        // Act

        await basePage.executeRequest();

        // Assert

        await expect(page.getByTestId('response-status-badge')).toContainText('200');
    });

    test('keeps saved requests across a reload', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('persist');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);

        // Act

        await page.reload();
        await workspace.openPanel();

        // Assert

        await expect(workspace.item(name)).toBeVisible();
    });
});

test.describe('Managing requests', () => {
    test('renames a request from the context menu', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('before');
        const renamed = workspace.name('after');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);
        await workspace.openPanel();

        // Act

        await workspace.rightClick(workspace.item(name));
        await workspace.chooseContextItem('Rename');

        const input = page.getByTestId('workspace-rename-input');
        await input.fill(renamed);
        await input.press('Enter');

        // Assert

        await expect(workspace.item(renamed)).toBeVisible();
        await expect(workspace.item(name)).toHaveCount(0);
        expect(await workspace.findByName(renamed)).toBeDefined();
        expect(await workspace.findByName(name)).toBeUndefined();
    });

    test('renames a request by double clicking it', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('dbl');
        const renamed = workspace.name('dbl-renamed');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);
        await workspace.openPanel();

        // Act

        await workspace.item(name).dblclick();

        const input = page.getByTestId('workspace-rename-input');
        await input.fill(renamed);
        await input.press('Enter');

        // Assert

        await expect(workspace.item(renamed)).toBeVisible();
    });

    test('deletes a request after confirming', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('delete');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);
        await workspace.openPanel();

        // Act

        await workspace.rightClick(workspace.item(name));
        await workspace.chooseContextItem('Delete');
        await page.getByTestId('workspace-delete-confirm').click();

        // Assert

        await expect(workspace.item(name)).toHaveCount(0);
        expect(await workspace.findByName(name)).toBeUndefined();
    });

    test('moves a request into a folder by dragging it', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('drag');
        const folder = workspace.name('target');

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);
        await workspace.openPanel();
        await workspace.createFolder(folder);
        await expect(workspace.folder(folder)).toBeVisible();

        // Act

        await workspace.item(name).dragTo(workspace.folderToggle(folder));

        // Assert

        await expect.poll(async () => (await workspace.findByName(name))?.folder).toBe(folder);
        await expect(
            workspace.folder(folder).getByTestId('workspace-item').filter({ hasText: name }),
        ).toBeVisible();
    });
});

test.describe('External changes', () => {
    const openSavedRequest = async (
        { page, basePage, workspace }: { page: import('@playwright/test').Page; basePage: import('../pages/BasePage').BasePage; workspace: WorkspacePage },
        name: string,
    ) => {
        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.saveCurrentTab(name);
        await workspace.openPanel();

        const saved = await workspace.findByName(name);

        // The file changes on disk, like after a git pull.
        await workspace.changeOnDisk(saved!.id, {
            headers: [{ key: 'x-disk', value: 'disk', enabled: true }],
        });

        // The tab edits the request, so its save is based on a stale hash.
        await basePage.addHeader('x-local', 'local');
        await workspace.chooseOption('save-request-option');
    };

    test('asks what to do when the file changed on disk and can cancel', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange & Act

        const name = workspace.name('conflict-cancel');
        await openSavedRequest({ page, basePage, workspace }, name);

        // Assert

        await expect(page.getByText('The file changed on disk')).toBeVisible();

        // Act

        await page.getByTestId('workspace-conflict-cancel').click();

        // Assert

        await expect(page.getByText('The file changed on disk')).toBeHidden();

        const saved = await workspace.findByName(name);
        const { request } = await workspace.fetchStored(saved!.id);
        expect(JSON.stringify(request.headers)).not.toContain('x-local');
    });

    test('overwrites the file with the local changes', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange & Act

        const name = workspace.name('conflict-overwrite');
        await openSavedRequest({ page, basePage, workspace }, name);
        await page.getByTestId('workspace-conflict-overwrite').click();

        // Assert

        await expect(page.getByText('The file changed on disk')).toBeHidden();

        const saved = await workspace.findByName(name);
        const { request } = await workspace.fetchStored(saved!.id);
        expect(JSON.stringify(request.headers)).toContain('x-local');
    });

    test('reloads the file and drops the local changes', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange & Act

        const name = workspace.name('conflict-reload');
        await openSavedRequest({ page, basePage, workspace }, name);
        await page.getByTestId('workspace-conflict-reload').click();

        // Assert

        await expect(page.getByText('The file changed on disk')).toBeHidden();
        await expect(
            workspace.item(name).getByTestId('workspace-item-dirty'),
        ).toHaveCount(0);

        const saved = await workspace.findByName(name);
        const { request } = await workspace.fetchStored(saved!.id);
        expect(JSON.stringify(request.headers)).not.toContain('x-local');
    });

    test('shows files added on disk after a reload', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        const name = workspace.name('external');
        const id = crypto.randomUUID();

        await basePage.goto();
        await workspace.openPanel();

        // Act - A teammate's file arrives through git.

        const response = await page.request.put(`/demo/api/workspace/requests/${id}`, {
            data: {
                name,
                method: 'GET',
                endpoint: '_demo/verbs',
                route: null,
                headers: [],
                query: [],
                body: { type: 'empty' },
                authorization: { type: 'none', value: '' },
                hash: null,
            },
        });
        expect(response.ok()).toBe(true);

        await page.reload();
        await workspace.openPanel();

        // Assert

        await expect(workspace.item(name)).toBeVisible();
    });
});

test.describe('Migrating tabs', () => {
    test('moves unsaved tabs into the workspace', async ({
        page,
        basePage,
        workspace,
    }) => {
        // Arrange

        await basePage.goto();
        await page.getByRole('button', { name: 'verbs' }).click();
        await page.getByRole('button', { name: 'GET /verbs' }).click();
        await workspace.openPanel();

        const before = (await workspace.list()).requests.map(request => request.id);

        // Act

        await workspace.panel.getByTestId('workspace-migrate').click();
        await page.getByTestId('workspace-migrate-confirm').click();

        // Assert

        await expect(workspace.panel.getByTestId('workspace-migrate')).toBeHidden();

        const created = (await workspace.list()).requests.filter(
            request => !before.includes(request.id),
        );
        migratedNames.push(...created.map(request => request.name));

        expect(created.length).toBeGreaterThan(0);
        await expect(workspace.item(created[0].name).first()).toBeVisible();
    });
});
