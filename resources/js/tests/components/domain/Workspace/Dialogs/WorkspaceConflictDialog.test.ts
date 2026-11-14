import WorkspaceConflictDialog from '@/components/domain/Workspace/Dialogs/WorkspaceConflictDialog.vue';
import { useWorkspaceStore } from '@/stores';
import { createTestingPinia } from '@pinia/testing';
import type { VueWrapper } from '@vue/test-utils';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

let mounted: VueWrapper | null = null;

const createWrapper = (isConflictOpen: boolean) => {
    const pinia = createTestingPinia({
        createSpy: vi.fn,
        stubActions: true,
        initialState: { workspace: { isConflictOpen } },
    });

    mounted = mount(WorkspaceConflictDialog, {
        global: { plugins: [pinia] },
        attachTo: document.body,
    });

    return useWorkspaceStore();
};

const press = async (testId: string) => {
    document.body.querySelector<HTMLElement>(`[data-testid="${testId}"]`)?.click();
    await flushPromises();
};

describe('WorkspaceConflictDialog', () => {
    afterEach(() => {
        mounted?.unmount();
        document.body.innerHTML = '';
    });

    it('stays closed without a conflict', async () => {
        createWrapper(false);
        await flushPromises();

        expect(
            document.body.querySelector('[data-testid="workspace-conflict-reload"]'),
        ).toBeNull();
    });

    it('reloads the file', async () => {
        const store = createWrapper(true);

        await flushPromises();
        await press('workspace-conflict-reload');

        expect(store.resolveConflict).toHaveBeenCalledWith('reload');
    });

    it('overwrites the file', async () => {
        const store = createWrapper(true);

        await flushPromises();
        await press('workspace-conflict-overwrite');

        expect(store.resolveConflict).toHaveBeenCalledWith('overwrite');
    });

    it('dismisses the dialog', async () => {
        const store = createWrapper(true);

        await flushPromises();
        await press('workspace-conflict-cancel');

        expect(store.dismissConflict).toHaveBeenCalled();
    });
});
