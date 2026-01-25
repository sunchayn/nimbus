import RequestBuilderEndpointInput from '@/components/domain/Client/Request/RequestBuilderEndpointInput.vue';
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
    updateRequestEndpoint: vi.fn(),
    executeCurrentRequest: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
    };
});

vi.mock('@/composables/request/useRouteSegmentSelection', () => ({
    useRouteSegmentSelection: () => ({
        handleClick: vi.fn(),
    }),
}));

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(RequestBuilderEndpointInput, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('RequestBuilderEndpointInput', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        mockRequestStore.pendingRequestData = {
            endpoint: '/users/1',
            isProcessing: false,
        };
    });

    it('renders the current endpoint', () => {
        const wrapper = createWrapper();
        const input = wrapper.find('[data-testid="endpoint-input"]');
        expect((input.element as HTMLInputElement).value).toBe('/users/1');
    });

    it('updates the endpoint when input changes', async () => {
        const wrapper = createWrapper();
        const input = wrapper.find('[data-testid="endpoint-input"]');

        await input.setValue('/users/2');

        expect(mockRequestStore.updateRequestEndpoint).toHaveBeenCalledWith('/users/2');
    });

    it('executes the request when the send button is clicked', async () => {
        const wrapper = createWrapper();
        const button = wrapper.find('button');

        await button.trigger('click');

        expect(mockRequestStore.executeCurrentRequest).toHaveBeenCalled();
    });

    it('disables the send button when processing', () => {
        mockRequestStore.pendingRequestData.isProcessing = true;
        const wrapper = createWrapper();
        const button = wrapper.find('button');

        expect(button.attributes('disabled')).toBeDefined();
    });
});
