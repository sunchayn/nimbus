import ResponseViewer from '@/components/domain/Client/Response/ResponseViewer.vue';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

vi.mock('@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue', () => ({
    default: {
        name: 'ResponseStatus',
        template: '<div data-testid="response-status">Status</div>',
    },
}));

vi.mock('@/components/domain/Client/Response/ResponseViewerEmptyState.vue', () => ({
    default: {
        name: 'ResponseViewerEmptyState',
        template: '<div data-testid="response-empty">Empty</div>',
    },
}));

vi.mock('@/components/domain/Client/Response/ResponseViewerErrorState.vue', () => ({
    default: {
        name: 'ResponseViewerError',
        props: ['error'],
        template: '<div data-testid="response-error">{{ error.message }}</div>',
    },
}));

vi.mock('@/components/domain/Client/Response/ResponseViewerResponse.vue', () => ({
    default: {
        name: 'ResponseViewerResponse',
        template: '<div data-testid="response-content">Response</div>',
    },
}));

const mockRequestHistoryStore = reactive({
    logs: [],
    lastLog: null,
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestsHistoryStore: () => mockRequestHistoryStore,
    };
});

describe('ResponseViewer', () => {
    beforeEach(() => {
        mockRequestHistoryStore.logs = [];
    });

    it('renders empty state when no logs available', () => {
        renderWithProviders(ResponseViewer);

        expect(screen.getByTestId('response-status')).toBeInTheDocument();
        expect(screen.getByTestId('response-empty')).toBeInTheDocument();
    });

    it('renders error component when last log contains error', () => {
        mockRequestHistoryStore.logs = [{ error: { message: 'Something went wrong' } }];
        mockRequestHistoryStore.lastLog = mockRequestHistoryStore.logs[0];

        renderWithProviders(ResponseViewer);

        expect(screen.getByTestId('response-error')).toHaveTextContent(
            'Something went wrong',
        );
    });

    it('renders response component when last log has response', () => {
        mockRequestHistoryStore.logs = [{ response: { status: 200 } }];
        mockRequestHistoryStore.lastLog = mockRequestHistoryStore.logs[0];

        renderWithProviders(ResponseViewer);

        expect(screen.getByTestId('response-content')).toBeInTheDocument();
    });
});
