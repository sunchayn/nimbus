import WorkspaceEmptyState from '@/components/domain/Workspace/Explorer/WorkspaceEmptyState.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('WorkspaceEmptyState', () => {
    it('shows a hint and a link to the guide', () => {
        const wrapper = mount(WorkspaceEmptyState);

        expect(wrapper.text()).toContain('No saved requests yet');
        expect(wrapper.text()).toContain('Learn more');
        expect(wrapper.find('a').attributes('href')).toContain('workspace');
    });

    it('shows only a loading text while the workspace loads', () => {
        const wrapper = mount(WorkspaceEmptyState, { props: { isLoading: true } });

        expect(wrapper.text()).toContain('Loading...');
        expect(wrapper.text()).not.toContain('No saved requests yet');
    });
});
