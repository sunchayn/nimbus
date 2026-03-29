import EnvironmentAwareInput from '@/components/common/EnvironmentAwareInput.vue';
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
        } as unknown as PendingRequest;
    });

    it('renders the current endpoint', () => {
        const wrapper = createWrapper();
        const input = wrapper.find('input');
        expect((input.element as HTMLInputElement).value).toBe('/users/1');
    });

    it('updates the endpoint when input changes', async () => {
        const wrapper = createWrapper();
        const input = wrapper.find('input');

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
        if (mockRequestStore.pendingRequestData) {
            mockRequestStore.pendingRequestData.isProcessing = true;
        }

        const wrapper = createWrapper();
        const button = wrapper.find('button');

        expect(button.attributes('disabled')).toBeDefined();
    });

    it('does not execute the request if the endpoint has placeholders', async () => {
        mockRequestStore.pendingRequestData = {
            endpoint: '/users/{id}',
            isProcessing: false,
        } as unknown as PendingRequest;

        const wrapper = createWrapper();
        const button = wrapper.find('button');

        await button.trigger('click');

        expect(mockRequestStore.executeCurrentRequest).not.toHaveBeenCalled();
    });

    it('executes the request even if the endpoint has environment variables', async () => {
        mockRequestStore.pendingRequestData = {
            endpoint: '/{{collection}}/users',
            isProcessing: false,
        } as unknown as PendingRequest;

        const wrapper = createWrapper();
        const button = wrapper.find('button');

        await button.trigger('click');

        expect(mockRequestStore.executeCurrentRequest).toHaveBeenCalled();
    });

    it('syncs the mirror scroll position when the input is scrolled', async () => {
        const wrapper = createWrapper();
        const input = wrapper.find('input');

        const environmentAwareInput = wrapper.getComponent(EnvironmentAwareInput);
        const mirror = environmentAwareInput.vm.mirrorRef as HTMLDivElement;

        // Mock scrollLeft behavior since it's not fully operational in JSDOM
        Object.defineProperty(mirror, 'scrollLeft', {
            value: 0,
            writable: true,
        });

        const inputElement = input.element as HTMLInputElement;
        Object.defineProperty(inputElement, 'scrollLeft', {
            value: 100,
            writable: true,
        });

        await input.trigger('scroll');

        expect(mirror.scrollLeft).toBe(100);
    });

    it('detects the hovered segment via elementFromPoint', async () => {
        const wrapper = createWrapper();
        const input = wrapper.find('input');

        // Mock elementFromPoint
        const mockSpan = document.createElement('span');
        mockSpan.setAttribute('data-segment-index', '2');

        // Add closest mock for JSDOM
        mockSpan.closest = vi.fn().mockReturnValue(mockSpan);

        Object.defineProperty(document, 'elementFromPoint', {
            value: vi.fn().mockReturnValue(mockSpan),
            configurable: true,
        });

        await input.trigger('mousemove', {
            clientX: 10,
            clientY: 10,
        });

        const environmentAwareInput = wrapper.getComponent(EnvironmentAwareInput);
        expect(
            (
                environmentAwareInput.vm as unknown as {
                    activelyHoveredEnvVariableSegment: number | null;
                }
            ).activelyHoveredEnvVariableSegment,
        ).toBe(2);
    });

    it('clears the active indicator on mouseleave', async () => {
        const wrapper = createWrapper();
        const input = wrapper.find('input');

        const environmentAwareInput = wrapper.getComponent(EnvironmentAwareInput);
        (
            environmentAwareInput.vm as unknown as {
                activelyHoveredEnvVariableSegment: number | null;
            }
        ).activelyHoveredEnvVariableSegment = 1;

        await input.trigger('mouseleave');

        expect(
            (
                environmentAwareInput.vm as unknown as {
                    activelyHoveredEnvVariableSegment: number | null;
                }
            ).activelyHoveredEnvVariableSegment,
        ).toBe(null);
    });
});
