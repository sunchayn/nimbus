import EnvironmentCollectionRequests from '@/components/domain/Environment/EnvironmentCollectionRequests.vue';
import type { SavedRequest } from '@/stores';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

const savedRequest: SavedRequest = {
    id: 'req-1',
    name: 'Create member',
    method: 'post',
    endpoint: '/v2/api/members',
    headers: [],
    queryParameters: [],
    body: { POST: { json: '{"email":"jane@example.test"}' } },
    payloadType: 'json',
    authorization: { type: 'bearer', value: '{{gym_token}}' },
};

const mockEnvironmentStore = reactive<{
    activeCollection: { id: string; name: string; requests?: SavedRequest[] } | null;
}>({
    activeCollection: { id: 'flow-1', name: 'Registration', requests: [savedRequest] },
});

const restoreFromSharedPayload = vi.fn();
const push = vi.fn();

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useEnvironmentVariablesStore: () => mockEnvironmentStore,
        useTabsStore: () => ({ restoreFromSharedPayload }),
    };
});

vi.mock('vue-router', () => ({
    useRouter: () => ({ push }),
}));

describe('EnvironmentCollectionRequests', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        setActivePinia(createPinia());
        mockEnvironmentStore.activeCollection = {
            id: 'flow-1',
            name: 'Registration',
            requests: [savedRequest],
        };
    });

    it('lists the active collection saved requests', () => {
        const wrapper = mount(EnvironmentCollectionRequests);

        expect(wrapper.find('[data-testid="collection-requests"]').exists()).toBe(true);
        expect(wrapper.findAll('[data-testid="collection-request-item"]')).toHaveLength(
            1,
        );
        expect(wrapper.text()).toContain('Create member');
        expect(wrapper.text()).toContain('/v2/api/members');
    });

    it('loads the request into a tab and navigates on open', async () => {
        const wrapper = mount(EnvironmentCollectionRequests);

        await wrapper.find('[data-testid="open-request-btn"]').trigger('click');

        expect(restoreFromSharedPayload).toHaveBeenCalledWith(savedRequest);
        expect(push).toHaveBeenCalledWith({ name: 'main' });
    });

    it('renders nothing when the collection has no requests', () => {
        mockEnvironmentStore.activeCollection = {
            id: 'flow-1',
            name: 'Registration',
            requests: [],
        };

        const wrapper = mount(EnvironmentCollectionRequests);

        expect(wrapper.find('[data-testid="collection-requests"]').exists()).toBe(false);
    });
});
