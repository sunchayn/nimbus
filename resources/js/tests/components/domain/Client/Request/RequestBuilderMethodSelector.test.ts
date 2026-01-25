import RequestBuilderMethodSelector from '@/components/domain/Client/Request/RequestBuilderMethodSelector.vue';
import type { PendingRequest } from '@/interfaces/http';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive, ref } from 'vue';

/*
 * Fixtures.
 */

const mockRequestStore = reactive({
    pendingRequestData: ref<PendingRequest | null>(null),
    updateRequestMethod: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
    };
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(RequestBuilderMethodSelector, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('RequestBuilderMethodSelector', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        mockRequestStore.pendingRequestData = {
            method: 'GET',
            supportedRoutes: [
                { method: 'GET', endpoint: '/test' },
                { method: 'POST', endpoint: '/test' },
            ],
        };
    });

    it('renders the current method', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('GET');
    });

    it('updates the method when a new one is selected', async () => {
        const wrapper = createWrapper();

        // Simulate selection (depends on how AppSelect works, but we can verify the computed property)
        // Since we are unit testing the component's internal logic:
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const vm = wrapper.vm as any;
        vm.method = 'POST';

        expect(mockRequestStore.updateRequestMethod).toHaveBeenCalledWith('POST');
    });

    it('identifies supported and unsupported methods correctly', () => {
        const wrapper = createWrapper();
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const vm = wrapper.vm as any;

        expect(vm.currentRouteSupportedMethods).toContain('GET');
        expect(vm.currentRouteSupportedMethods).toContain('POST');
        expect(vm.currentRouteUnsupportedMethods).toContain('PUT');
        expect(vm.currentRouteUnsupportedMethods).toContain('PATCH');
        expect(vm.currentRouteUnsupportedMethods).toContain('DELETE');
    });
});
