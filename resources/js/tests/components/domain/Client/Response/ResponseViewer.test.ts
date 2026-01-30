import ResponseViewer from '@/components/domain/Client/Response/ResponseViewer.vue';
import type { RequestLog } from '@/interfaces';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Ref } from 'vue';
import { nextTick, reactive, ref } from 'vue';

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

const mockTabsStore = reactive({
    activeResponse: ref(null) as Ref<RequestLog | null>,
});

vi.mock('@/stores', () => ({
    useTabsStore: () => mockTabsStore,
    useRequestsHistoryStore: () => ({ allLogs: [], lastLog: null }), // Mock legacy if needed or just empty
}));

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (pinia: ReturnType<typeof createPinia>): VueWrapper => {
    return mount(ResponseViewer, {
        global: {
            plugins: [pinia],
        },
    });
};

describe('ResponseViewer', () => {
    let pinia: ReturnType<typeof createPinia>;

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

            // Act

            mockTabsStore.activeResponse = { error: { message: 'Failed' } };
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="response-error"]').exists()).toBe(true);
        });

        it('renders content when last log is successful', async () => {
            // Arrange

            const wrapper = createWrapper(pinia);

            // Act

            mockTabsStore.activeResponse = { response: { status: 200 } };
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="response-content"]').exists()).toBe(true);
        });
    });
});
