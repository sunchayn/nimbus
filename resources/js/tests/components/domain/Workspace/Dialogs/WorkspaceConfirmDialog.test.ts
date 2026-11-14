import WorkspaceConfirmDialog from '@/components/domain/Workspace/Dialogs/WorkspaceConfirmDialog.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';

const wrappers: ReturnType<typeof mount>[] = [];

const createWrapper = (props: Record<string, unknown> = {}) => {
    const wrapper = mount(WorkspaceConfirmDialog, {
        props: {
            open: true,
            title: 'Delete folder',
            description: 'This deletes the folder.',
            confirmLabel: 'Delete',
            testId: 'confirm',
            ...props,
        },
        attachTo: document.body,
    });

    wrappers.push(wrapper);

    return wrapper;
};

const find = (selector: string) => document.body.querySelector<HTMLElement>(selector);

describe('WorkspaceConfirmDialog', () => {
    afterEach(() => {
        wrappers.splice(0).forEach(wrapper => wrapper.unmount());
    });

    it('shows the title and the description', async () => {
        createWrapper();
        await flushPromises();

        expect(document.body.textContent).toContain('Delete folder');
        expect(document.body.textContent).toContain('This deletes the folder.');
    });

    it('emits confirm when the confirm button is clicked', async () => {
        const wrapper = createWrapper();
        await flushPromises();

        find('[data-testid="confirm"]')?.click();
        await flushPromises();

        expect(wrapper.emitted('confirm')).toHaveLength(1);
    });

    it('closes without confirming when cancel is clicked', async () => {
        const wrapper = createWrapper();
        await flushPromises();

        [...document.body.querySelectorAll<HTMLElement>('button')]
            .find(button => button.textContent?.trim() === 'Cancel')
            ?.click();
        await flushPromises();

        expect(wrapper.emitted('update:open')).toEqual([[false]]);
        expect(wrapper.emitted('confirm')).toBeUndefined();
    });
});
