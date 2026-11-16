import WorkspaceSaveOverlay from '@/components/domain/Workspace/Tree/WorkspaceSaveOverlay.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const request = {
    id: 'request-1234',
    name: 'Create user',
    method: 'POST',
    endpoint: '/api/users',
    route: 'users.store',
    file: 'requests/create-user.request.json',
    folder: '',
    hash: 'hash-1',
};

describe('WorkspaceSaveOverlay', () => {
    it('shows the unsaved dot and the save button', () => {
        const wrapper = mount(WorkspaceSaveOverlay, { props: { request } });

        expect(wrapper.find('[data-testid="workspace-item-dirty"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="workspace-save"]').exists()).toBe(true);
    });

    it('emits save when the save button is clicked', async () => {
        const wrapper = mount(WorkspaceSaveOverlay, { props: { request } });

        await wrapper.find('[data-testid="workspace-save"]').trigger('click');

        expect(wrapper.emitted('save')).toEqual([[request]]);
    });
});
