import HistoryItem from '@/components/domain/Client/Response/ResponseStatus/History/HistoryItem.vue';
import type { Request, Response } from '@/interfaces/http';
import { createMockRequestLog } from '@/tests/_utils/testFactories';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

vi.mock('@/components/base/dropdown-menu', () => ({
    AppDropdownMenuItem: {
        name: 'AppDropdownMenuItem',
        template: '<div><slot /></div>',
    },
}));

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(HistoryItem, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global ?? {}),
        },
    });
};

describe('HistoryItem', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    const mockLog = createMockRequestLog({
        durationInMs: 150,
        request: {
            method: 'GET',
            endpoint: '/api/test',
        } as unknown as Request,
        response: {
            statusCode: 200,
            statusText: 'OK',
            timestamp: Math.floor(Date.now() / 1000) - 60, // 1 minute ago
            sizeInBytes: 1024,
        } as unknown as Response,
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders history item details correctly including relative timestamp', () => {
            // Arrange

            const wrapper = createWrapper({
                props: {
                    log: mockLog,
                    index: 0,
                },
            });

            // Assert

            expect(wrapper.get('[data-testid="history-item-method"]').text()).toBe('GET');
            expect(wrapper.get('[data-testid="history-item-endpoint"]').text()).toBe(
                '/api/test',
            );
            expect(wrapper.get('[data-testid="response-status-badge"]').text()).toContain(
                '200 - OK',
            );

            // Assert relative timestamp
            const timestamp = wrapper.find('small');
            expect(timestamp.text()).toMatch(/1 minute ago|1 min ago/);
        });
    });
});
