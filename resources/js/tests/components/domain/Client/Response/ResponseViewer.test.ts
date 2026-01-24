import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import ResponseViewer from '@/components/domain/Client/Response/ResponseViewer.vue';
import { useRequestsHistoryStore } from '@/stores';

/*
 * Fixtures.
 */

vi.mock('@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue', () => ({
    default: { template: '<div data-testid="response-status" />' },
}));

vi.mock('@/components/domain/Client/Response/ResponseViewerEmptyState.vue', () => ({
    default: { template: '<div data-testid="response-empty" />' },
}));

vi.mock('@/components/domain/Client/Response/ResponseViewerErrorState.vue', () => ({
    default: { template: '<div data-testid="response-error" />', props: ['error'] },
}));

vi.mock('@/components/domain/Client/Response/ResponseViewerResponse.vue', () => ({
    default: { template: '<div data-testid="response-content" />' },
}));

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (pinia: any): VueWrapper => {
    return mount(ResponseViewer, {
        global: {
            plugins: [pinia],
        },
    });
};

describe('ResponseViewer', () => {
    let pinia: any;

    beforeEach(() => {
        pinia = createPinia();
        setActivePinia(pinia);
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders empty state when no logs exist', () => {
            // Arrange

            const wrapper = createWrapper(pinia);

            // Assert

            expect(wrapper.find('[data-testid="response-empty"]').exists()).toBe(true);
        });

        it('renders error state when last log has error', async () => {
            // Arrange

            const wrapper = createWrapper(pinia);
            const historyStore = useRequestsHistoryStore();

            // Act

            historyStore.logs = [{ error: { message: 'Failed' } } as any];
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="response-error"]').exists()).toBe(true);
        });

        it('renders content when last log is successful', async () => {
            // Arrange

            const wrapper = createWrapper(pinia);
            const historyStore = useRequestsHistoryStore();

            // Act

            historyStore.logs = [{ response: { status: 200 } } as any];
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="response-content"]').exists()).toBe(true);
        });
    });
});
