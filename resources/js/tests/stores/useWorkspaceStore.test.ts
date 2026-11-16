import { AuthorizationType } from '@/interfaces/generated';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { Tab } from '@/interfaces/ui/tabs';
import { useTabsStore } from '@/stores/request/useTabsStore';
import { useWorkspaceStore } from '@/stores/workspace/useWorkspaceStore';
import * as api from '@/utils/workspace/workspaceApi';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const toast = vi.hoisted(() => ({
    success: vi.fn(),
    warning: vi.fn(),
    error: vi.fn(),
}));

vi.mock('@/components/base/toast', () => ({ useAppToast: () => toast }));

vi.mock('@/stores/core/useConfigStore', () => ({
    useConfigStore: () => ({ workspaceEnabled: true }),
}));

vi.mock('@/utils/workspace/workspaceApi');

const createTab = (): Tab => ({
    id: 'tab-1',
    title: 'users',
    method: 'POST',
    response: null,
    request: {
        method: 'POST',
        endpoint: '/api/users',
        headers: [],
        queryParameters: [],
        body: { POST: { [RequestBodyTypeEnum.JSON]: '{}' } },
        payloadType: RequestBodyTypeEnum.JSON,
        schema: { shape: {}, extractionErrors: null },
        authorization: { type: AuthorizationType.None },
        supportedRoutes: [],
        routeDefinition: {
            endpoint: '/api/users',
            method: 'POST',
            shortEndpoint: 'users',
            schema: { shape: {}, extractionErrors: null },
        },
    },
});

describe('useWorkspaceStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    const setup = () => {
        const tabsStore = useTabsStore();
        const tab = createTab();

        tabsStore.tabs = [tab];
        tabsStore.activeTabId = tab.id;

        return { tabsStore, workspaceStore: useWorkspaceStore() };
    };

    it('loads the listing', async () => {
        const listing = {
            folders: [],
            requests: [],
        };

        vi.mocked(api.fetchWorkspace).mockResolvedValue(listing);

        const { workspaceStore } = setup();

        await workspaceStore.load();

        expect(workspaceStore.listing).toEqual(listing);
    });

    it('keeps the error when the listing fails', async () => {
        vi.mocked(api.fetchWorkspace).mockRejectedValue(new Error('Offline'));
        vi.mocked(api.workspaceErrorMessage).mockReturnValue('Offline');

        const { workspaceStore } = setup();

        await workspaceStore.load();

        expect(workspaceStore.error).toBe('Offline');
    });

    it('asks for a name before the first save', async () => {
        const { workspaceStore } = setup();

        const isSaved = await workspaceStore.save();

        expect(isSaved).toBe(false);
        expect(workspaceStore.isSaveDialogOpen).toBe(true);
        expect(api.saveWorkspaceRequest).not.toHaveBeenCalled();
    });

    it('links the tab to its file on save', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockResolvedValue({
            request: {} as never,
            hash: 'hash-1',
        });
        vi.mocked(api.fetchWorkspace).mockResolvedValue({
            folders: [],
            requests: [],
        });

        const { workspaceStore, tabsStore } = setup();

        const isSaved = await workspaceStore.save({ name: ' Create user ' });

        expect(isSaved).toBe(true);
        expect(api.saveWorkspaceRequest).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({ name: 'Create user', method: 'POST' }),
            null,
            undefined,
        );
        expect(tabsStore.tabs[0].workspace).toMatchObject({
            name: 'Create user',
            hash: 'hash-1',
        });
        expect(toast.success).toHaveBeenCalledWith('Request saved to workspace');
    });

    it('sends the last known hash when it saves a linked tab again', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockResolvedValue({
            request: {} as never,
            hash: 'hash-2',
        });
        vi.mocked(api.fetchWorkspace).mockResolvedValue({
            folders: [],
            requests: [],
        });

        const { workspaceStore, tabsStore } = setup();

        tabsStore.tabs[0].workspace = {
            id: 'request-1234',
            name: 'Create user',
            hash: 'hash-1',
            saved: '',
        };

        await workspaceStore.save();

        expect(api.saveWorkspaceRequest).toHaveBeenCalledWith(
            'request-1234',
            expect.any(Object),
            'hash-1',
            undefined,
        );
        expect(toast.success).toHaveBeenCalledWith('Request saved to workspace');
    });

    it('writes a new file for save as', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockResolvedValue({
            request: {} as never,
            hash: 'hash-3',
        });
        vi.mocked(api.fetchWorkspace).mockResolvedValue({
            folders: [],
            requests: [],
        });

        const { workspaceStore, tabsStore } = setup();

        tabsStore.tabs[0].workspace = {
            id: 'request-1234',
            name: 'Create user',
            hash: 'hash-1',
            saved: '',
        };

        await workspaceStore.save({ name: 'Copy', asNew: true });

        expect(api.saveWorkspaceRequest).toHaveBeenCalledWith(
            expect.not.stringMatching(/^request-1234$/),
            expect.objectContaining({ name: 'Copy' }),
            null,
            undefined,
        );
    });

    it('reports a failed save and keeps the tab unlinked', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockRejectedValue(new Error('Conflict'));
        vi.mocked(api.workspaceErrorMessage).mockReturnValue('Conflict');

        const { workspaceStore, tabsStore } = setup();

        const isSaved = await workspaceStore.save({ name: 'Create user' });

        expect(isSaved).toBe(false);
        expect(tabsStore.tabs[0].workspace).toBeUndefined();
        expect(toast.error).toHaveBeenCalledWith(
            'The request was not saved',
            expect.objectContaining({ description: 'Conflict' }),
        );
    });

    it('refuses to save a form data body', async () => {
        const { workspaceStore, tabsStore } = setup();

        tabsStore.tabs[0].request.payloadType = RequestBodyTypeEnum.FORM_DATA;
        vi.mocked(api.workspaceErrorMessage).mockReturnValue('Form data');

        const isSaved = await workspaceStore.save({ name: 'Upload' });

        expect(isSaved).toBe(false);
        expect(api.saveWorkspaceRequest).not.toHaveBeenCalled();
    });

    it('unlinks the tabs of a deleted file', async () => {
        vi.mocked(api.deleteWorkspaceRequest).mockResolvedValue(undefined);
        vi.mocked(api.fetchWorkspace).mockResolvedValue({
            folders: [],
            requests: [],
        });

        const { workspaceStore, tabsStore } = setup();

        tabsStore.tabs[0].workspace = {
            id: 'request-1234',
            name: 'Create user',
            hash: 'hash-1',
            saved: '',
        };

        await workspaceStore.remove('request-1234');

        expect(tabsStore.tabs[0].workspace).toBeUndefined();
    });
});

describe('useWorkspaceStore folders', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    const summary = {
        id: 'request-1234',
        name: 'Create user',
        method: 'POST',
        endpoint: '/api/users',
        route: null,
        file: 'requests/create-user.request.json',
        folder: '',
        hash: 'hash-1',
    };

    const setup = () => {
        const tabsStore = useTabsStore();
        const tab = createTab();

        tab.workspace = {
            id: 'request-1234',
            name: 'Create user',
            hash: 'hash-1',
            saved: JSON.stringify({ name: 'Create user', method: 'POST' }),
        };

        tabsStore.tabs = [tab];
        tabsStore.activeTabId = tab.id;

        const workspaceStore = useWorkspaceStore();

        workspaceStore.listing = {
            folders: [],
            requests: [summary],
        };

        vi.mocked(api.fetchWorkspace).mockResolvedValue(workspaceStore.listing);

        return { tabsStore, workspaceStore };
    };

    it('creates a folder and reloads the listing', async () => {
        vi.mocked(api.createWorkspaceFolder).mockResolvedValue('users');

        const { workspaceStore } = setup();

        const isCreated = await workspaceStore.createFolder('Users');

        expect(isCreated).toBe(true);
        expect(api.createWorkspaceFolder).toHaveBeenCalledWith('Users');
        expect(api.fetchWorkspace).toHaveBeenCalled();
    });

    it('deletes a folder and unlinks the tabs of the requests inside it', async () => {
        vi.mocked(api.deleteWorkspaceFolder).mockResolvedValue(undefined);

        const { workspaceStore, tabsStore } = setup();

        workspaceStore.listing = {
            folders: ['users', 'users/admin'],
            requests: [{ ...summary, folder: 'users/admin' }],
        };

        await workspaceStore.removeFolder('users');

        expect(api.deleteWorkspaceFolder).toHaveBeenCalledWith('users');
        expect(tabsStore.tabs[0].workspace).toBeUndefined();
        expect(toast.success).toHaveBeenCalledWith('Folder deleted');
    });

    it('keeps the tabs linked when the folder is not deleted', async () => {
        vi.mocked(api.deleteWorkspaceFolder).mockRejectedValue(new Error('Offline'));

        const { workspaceStore, tabsStore } = setup();

        workspaceStore.listing = {
            folders: ['users'],
            requests: [{ ...summary, folder: 'users' }],
        };

        await workspaceStore.removeFolder('users');

        expect(tabsStore.tabs[0].workspace).toBeDefined();
        expect(toast.error).toHaveBeenCalled();
    });

    it('shows a new folder before the server answers', async () => {
        let resolve: (folder: string) => void = () => {};

        vi.mocked(api.createWorkspaceFolder).mockReturnValue(
            new Promise(done => {
                resolve = done;
            }),
        );

        const { workspaceStore } = setup();

        const pending = workspaceStore.createFolder('Users / Admin');

        expect(workspaceStore.listing?.folders).toEqual(['users', 'users/admin']);

        resolve('users/admin');

        expect(await pending).toBe(true);
    });

    it('removes a request from the listing before the server answers', async () => {
        vi.mocked(api.deleteWorkspaceRequest).mockReturnValue(new Promise(() => {}));

        const { workspaceStore } = setup();

        void workspaceStore.remove('request-1234');

        expect(workspaceStore.listing?.requests).toEqual([]);
    });

    it('restores the listing when a deletion fails', async () => {
        vi.mocked(api.deleteWorkspaceRequest).mockRejectedValue(new Error('Offline'));

        const { workspaceStore } = setup();

        await workspaceStore.remove('request-1234');

        expect(toast.error).toHaveBeenCalled();
        expect(api.fetchWorkspace).toHaveBeenCalled();
        expect(workspaceStore.listing?.requests).toEqual([summary]);
    });

    it('shows a renamed request before the server answers', async () => {
        vi.mocked(api.moveWorkspaceRequest).mockReturnValue(new Promise(() => {}));

        const { workspaceStore } = setup();

        void workspaceStore.move('request-1234', { name: 'Register user' });

        expect(workspaceStore.listing?.requests[0].name).toBe('Register user');
    });

    it('puts a new folder at the end of the list', async () => {
        vi.mocked(api.createWorkspaceFolder).mockReturnValue(new Promise(() => {}));

        const { workspaceStore } = setup();

        workspaceStore.listing = {
            folders: ['zebra', 'apple'],
            requests: [],
        };

        void workspaceStore.createFolder('Banana');

        expect(workspaceStore.listing?.folders).toEqual(['zebra', 'apple', 'banana']);
    });

    it('shows the new folder order before the server answers', async () => {
        vi.mocked(api.reorderWorkspaceFolders).mockReturnValue(new Promise(() => {}));

        const { workspaceStore } = setup();

        workspaceStore.listing = {
            folders: ['a', 'b', 'c'],
            requests: [],
        };

        void workspaceStore.reorderFolders(['c', 'a']);

        expect(workspaceStore.listing?.folders).toEqual(['c', 'a', 'b']);
        expect(api.reorderWorkspaceFolders).toHaveBeenCalledWith(['c', 'a']);
    });

    it('shows the new request order before the server answers', () => {
        vi.mocked(api.reorderWorkspaceRequests).mockReturnValue(new Promise(() => {}));

        const { workspaceStore } = setup();

        const request = (id: string, folder: string) =>
            ({ id, folder, name: id }) as never;

        workspaceStore.listing = {
            folders: ['users'],
            requests: [request('a', 'users'), request('b', 'users'), request('c', '')],
        };

        void workspaceStore.reorderRequests('users', ['b']);

        expect(workspaceStore.listing?.requests.map(item => item.id)).toEqual([
            'c',
            'b',
            'a',
        ]);
        expect(api.reorderWorkspaceRequests).toHaveBeenCalledWith('users', ['b']);
    });

    it('shows a renamed folder with its folders and requests before the server answers', () => {
        vi.mocked(api.renameWorkspaceFolder).mockReturnValue(new Promise(() => {}));

        const { workspaceStore } = setup();

        workspaceStore.listing = {
            folders: ['users', 'users/admin', 'other'],
            requests: [
                {
                    id: 'a',
                    folder: 'users/admin',
                    file: 'requests/users/admin/a.request.json',
                } as never,
            ],
        };

        void workspaceStore.renameFolder('users', 'People');

        expect(workspaceStore.listing?.folders).toEqual([
            'people',
            'people/admin',
            'other',
        ]);
        expect(workspaceStore.listing?.requests[0]).toMatchObject({
            folder: 'people/admin',
            file: 'requests/people/admin/a.request.json',
        });
        expect(api.renameWorkspaceFolder).toHaveBeenCalledWith('users', 'People');
    });

    it('reports a folder that could not be renamed', async () => {
        vi.mocked(api.renameWorkspaceFolder).mockRejectedValue(new Error('Taken'));
        vi.mocked(api.fetchWorkspace).mockResolvedValue({ folders: [], requests: [] });

        const { workspaceStore } = setup();

        workspaceStore.listing = { folders: ['users'], requests: [] };

        const isRenamed = await workspaceStore.renameFolder('users', 'People');

        expect(isRenamed).toBe(false);
        expect(toast.error).toHaveBeenCalledWith(
            'The folder was not renamed',
            expect.anything(),
        );
    });

    it('moves a request with the hash of its listing', async () => {
        vi.mocked(api.moveWorkspaceRequest).mockResolvedValue({
            request: { name: 'Create user' } as never,
            hash: 'hash-2',
        });

        const { workspaceStore, tabsStore } = setup();

        const isMoved = await workspaceStore.move('request-1234', {
            folder: 'users',
        });

        expect(isMoved).toBe(true);
        expect(api.moveWorkspaceRequest).toHaveBeenCalledWith(
            'request-1234',
            { folder: 'users' },
            'hash-1',
        );
        expect(tabsStore.tabs[0].workspace?.hash).toBe('hash-2');
    });

    it('puts a moved request at the end of the new folder before the server answers', async () => {
        vi.mocked(api.moveWorkspaceRequest).mockResolvedValue({
            request: { name: 'Create user' } as never,
            hash: 'hash-2',
        });

        const { workspaceStore } = setup();

        workspaceStore.listing = {
            folders: ['users'],
            requests: [summary, { ...summary, id: 'request-5678', folder: 'users' }],
        };

        let ids: string[] = [];

        vi.mocked(api.moveWorkspaceRequest).mockImplementationOnce(async () => {
            ids = workspaceStore.listing?.requests.map(request => request.id) ?? [];

            return { request: { name: 'Create user' } as never, hash: 'hash-2' };
        });

        await workspaceStore.move('request-1234', { folder: 'users' });

        expect(ids).toEqual(['request-5678', 'request-1234']);
    });

    it('moves a request next to another one and reloads once', async () => {
        vi.mocked(api.moveWorkspaceRequest).mockResolvedValue({
            request: { name: 'Create user' } as never,
            hash: 'hash-2',
        });

        const { workspaceStore } = setup();
        const other = { ...summary, id: 'request-5678', folder: 'users' };

        workspaceStore.listing = {
            folders: ['users'],
            requests: [summary, other],
        };

        vi.mocked(api.reorderWorkspaceRequests).mockResolvedValue(undefined);

        await workspaceStore.moveBeside('request-1234', 'request-5678', 'before');

        expect(api.moveWorkspaceRequest).toHaveBeenCalledWith(
            'request-1234',
            { folder: 'users' },
            'hash-1',
        );
        expect(api.reorderWorkspaceRequests).toHaveBeenCalledWith('users', [
            'request-1234',
            'request-5678',
        ]);
        expect(api.fetchWorkspace).toHaveBeenCalledTimes(1);
    });

    it('remembers which folders are closed', () => {
        const { workspaceStore } = setup();

        expect(workspaceStore.isFolderOpen('users')).toBe(true);

        workspaceStore.toggleFolder('users');

        expect(workspaceStore.isFolderOpen('users')).toBe(false);
        expect(workspaceStore.collapsedFolders).toEqual(['users']);

        workspaceStore.toggleFolder('users');

        expect(workspaceStore.isFolderOpen('users')).toBe(true);
    });

    it('moves the closed state of a renamed folder with it', async () => {
        vi.mocked(api.renameWorkspaceFolder).mockResolvedValue('people');

        const { workspaceStore } = setup();

        workspaceStore.listing = { folders: ['users', 'users/admin'], requests: [] };
        workspaceStore.toggleFolder('users/admin');

        await workspaceStore.renameFolder('users', 'People');

        expect(workspaceStore.collapsedFolders).toEqual(['people/admin']);
    });

    it('keeps a renamed tab clean', async () => {
        vi.mocked(api.moveWorkspaceRequest).mockResolvedValue({
            request: { name: 'Register user' } as never,
            hash: 'hash-2',
        });

        const { workspaceStore, tabsStore } = setup();

        await workspaceStore.move('request-1234', { name: 'Register user' });

        expect(tabsStore.tabs[0].workspace?.name).toBe('Register user');
        expect(JSON.parse(tabsStore.tabs[0].workspace?.saved ?? '{}').name).toBe(
            'Register user',
        );
    });

    it('does nothing when the folder did not change', async () => {
        const { workspaceStore } = setup();

        const isMoved = await workspaceStore.move('request-1234', {
            folder: '',
        });

        expect(isMoved).toBe(true);
        expect(api.moveWorkspaceRequest).not.toHaveBeenCalled();
    });

    it('reports a failed move', async () => {
        vi.mocked(api.moveWorkspaceRequest).mockRejectedValue(new Error('Nope'));
        vi.mocked(api.workspaceErrorMessage).mockReturnValue('Nope');

        const { workspaceStore } = setup();

        const isMoved = await workspaceStore.move('request-1234', {
            folder: 'users',
        });

        expect(isMoved).toBe(false);
        expect(toast.error).toHaveBeenCalledWith(
            'The request was not moved',
            expect.objectContaining({ description: 'Nope' }),
        );
    });

    it('asks to try again when the file changed on disk before a move', async () => {
        vi.mocked(api.moveWorkspaceRequest).mockRejectedValue(new Error('409'));
        vi.mocked(api.isWorkspaceConflict).mockReturnValue(true);

        const { workspaceStore } = setup();

        const isMoved = await workspaceStore.move('request-1234', {
            folder: 'users',
        });

        expect(isMoved).toBe(false);
        expect(workspaceStore.isConflictOpen).toBe(false);
        expect(toast.warning).toHaveBeenCalledWith(
            'The request was not moved',
            expect.objectContaining({
                description: expect.stringContaining('changed on disk'),
            }),
        );
        expect(toast.error).not.toHaveBeenCalled();
    });
});

describe('useWorkspaceStore conflicts and migration', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        vi.mocked(api.fetchWorkspace).mockResolvedValue({
            folders: [],
            requests: [],
        });
        vi.mocked(api.workspaceErrorMessage).mockImplementation(
            error => (error as Error).message,
        );
    });

    const linkedSetup = () => {
        const tabsStore = useTabsStore();
        const tab = createTab();

        tab.workspace = {
            id: 'request-1234',
            name: 'Create user',
            hash: 'hash-1',
            saved: '{}',
        };

        tabsStore.tabs = [tab];
        tabsStore.activeTabId = tab.id;

        return { tabsStore, workspaceStore: useWorkspaceStore() };
    };

    const stored = (hash: string) => ({
        request: { id: 'request-1234', name: 'Create user' } as never,
        hash,
    });

    it('opens the conflict dialog when the file changed on disk', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockRejectedValue(new Error('409'));
        vi.mocked(api.isWorkspaceConflict).mockReturnValue(true);

        const { workspaceStore } = linkedSetup();

        const isSaved = await workspaceStore.save();

        expect(isSaved).toBe(false);
        expect(workspaceStore.isConflictOpen).toBe(true);
        expect(toast.error).not.toHaveBeenCalled();
    });

    it('overwrites with the current hash of the file', async () => {
        vi.mocked(api.saveWorkspaceRequest)
            .mockRejectedValueOnce(new Error('409'))
            .mockResolvedValueOnce(stored('hash-3'));
        vi.mocked(api.isWorkspaceConflict).mockReturnValue(true);
        vi.mocked(api.fetchWorkspaceRequest).mockResolvedValue(stored('hash-2'));

        const { workspaceStore, tabsStore } = linkedSetup();

        await workspaceStore.save();
        await workspaceStore.resolveConflict('overwrite');

        expect(api.saveWorkspaceRequest).toHaveBeenLastCalledWith(
            'request-1234',
            expect.anything(),
            'hash-2',
            undefined,
        );
        expect(workspaceStore.isConflictOpen).toBe(false);
        expect(tabsStore.tabs[0].workspace?.hash).toBe('hash-3');
    });

    it('writes the file again when it was removed and the choice is overwrite', async () => {
        vi.mocked(api.saveWorkspaceRequest)
            .mockRejectedValueOnce(new Error('409'))
            .mockResolvedValueOnce(stored('hash-3'));
        vi.mocked(api.isWorkspaceConflict).mockReturnValue(true);
        vi.mocked(api.fetchWorkspaceRequest).mockRejectedValue(new Error('404'));

        const { workspaceStore } = linkedSetup();

        await workspaceStore.save();
        await workspaceStore.resolveConflict('overwrite');

        expect(api.saveWorkspaceRequest).toHaveBeenLastCalledWith(
            'request-1234',
            expect.anything(),
            null,
            undefined,
        );
    });

    it('reloads the file into the tab', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockRejectedValue(new Error('409'));
        vi.mocked(api.isWorkspaceConflict).mockReturnValue(true);
        vi.mocked(api.fetchWorkspaceRequest).mockResolvedValue(stored('hash-2'));

        const { workspaceStore, tabsStore } = linkedSetup();

        const reload = vi
            .spyOn(tabsStore, 'reloadSavedRequest')
            .mockImplementation(() => {});

        await workspaceStore.save();
        await workspaceStore.resolveConflict('reload');

        expect(reload).toHaveBeenCalledWith(stored('hash-2'));
        expect(workspaceStore.isConflictOpen).toBe(false);
    });

    it('unlinks the tab when a reload finds no file', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockRejectedValue(new Error('409'));
        vi.mocked(api.isWorkspaceConflict).mockReturnValue(true);
        vi.mocked(api.fetchWorkspaceRequest).mockRejectedValue(new Error('404'));

        const { workspaceStore, tabsStore } = linkedSetup();

        await workspaceStore.save();
        await workspaceStore.resolveConflict('reload');

        expect(tabsStore.tabs[0].workspace).toBeUndefined();
        expect(toast.warning).toHaveBeenCalled();
    });

    it('closes the dialog without saving when it is dismissed', async () => {
        vi.mocked(api.saveWorkspaceRequest).mockRejectedValue(new Error('409'));
        vi.mocked(api.isWorkspaceConflict).mockReturnValue(true);

        const { workspaceStore } = linkedSetup();

        await workspaceStore.save();
        workspaceStore.dismissConflict();

        expect(workspaceStore.isConflictOpen).toBe(false);
        expect(api.saveWorkspaceRequest).toHaveBeenCalledTimes(1);
    });

    it('moves the tabs that have no file and skips the ones that fail', async () => {
        vi.mocked(api.saveWorkspaceRequest)
            .mockResolvedValueOnce(stored('hash-1'))
            .mockRejectedValueOnce(new Error('Use a variable.'));

        const tabsStore = useTabsStore();
        const first = { ...createTab(), id: 'tab-a', title: 'users' };
        const second = { ...createTab(), id: 'tab-b', title: 'posts' };
        const linked = {
            ...createTab(),
            id: 'tab-c',
            workspace: { id: 'request-1', name: 'x', hash: 'h', saved: '{}' },
        };

        tabsStore.tabs = [first, second, linked];

        const workspaceStore = useWorkspaceStore();

        await workspaceStore.migrateTabs();

        expect(api.saveWorkspaceRequest).toHaveBeenCalledTimes(2);
        expect(tabsStore.tabs[0].workspace).toMatchObject({
            name: '/users',
            hash: 'hash-1',
        });
        expect(tabsStore.tabs[1].workspace).toBeUndefined();
        expect(toast.warning).toHaveBeenCalledWith(
            '1 tabs moved, 1 skipped',
            expect.objectContaining({
                description: expect.stringContaining('/posts: Use a variable.'),
            }),
        );
    });

    it('skips a form data tab without calling the API', async () => {
        const tabsStore = useTabsStore();
        const tab = createTab();

        tab.request.payloadType = RequestBodyTypeEnum.FORM_DATA;
        tabsStore.tabs = [tab];

        await useWorkspaceStore().migrateTabs();

        expect(api.saveWorkspaceRequest).not.toHaveBeenCalled();
        expect(toast.warning).toHaveBeenCalled();
    });
});
