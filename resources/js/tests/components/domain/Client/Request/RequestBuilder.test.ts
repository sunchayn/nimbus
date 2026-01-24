import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RequestBuilder from '@/components/domain/Client/Request/RequestBuilder.vue';

/*
 * Fixtures.
 */

vi.mock('@/components/domain/Client/Request', () => ({
    RequestBuilderEndpoint: {
        name: 'RequestBuilderEndpoint',
        template: '<div data-testid="request-builder-endpoint">Endpoint</div>',
    },
    RequestParameters: {
        name: 'RequestParameters',
        template: '<div data-testid="request-parameters">Parameters Panel</div>',
    },
    RequestBody: {
        name: 'RequestBody',
        template: '<div data-testid="request-body">Body Panel</div>',
    },
    RequestAuthorization: {
        name: 'RequestAuthorization',
        template: '<div data-testid="request-authorization">Authorization Panel</div>',
    },
    RequestHeaders: {
        name: 'RequestHeaders',
        template: '<div data-testid="request-headers">Headers Panel</div>',
    },
}));

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount(RequestBuilder, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('RequestBuilder', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders endpoint selector and body tab by default', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.find('[data-testid="request-builder-endpoint"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="request-body"]').exists()).toBe(true);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('switches between panels when different tabs are selected', async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act

            const triggers = wrapper.findAll('[role="tab"]');
            const parametersTrigger = triggers.find(t => t.text() === 'Parameters');
            await parametersTrigger?.trigger('click');

            // Assert

            expect(wrapper.find('[data-testid="request-parameters"]').exists()).toBe(true);
        });
    });
});
