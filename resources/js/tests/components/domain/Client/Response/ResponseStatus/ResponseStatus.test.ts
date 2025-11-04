import ResponseStatus from '@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue';
import { STATUS } from '@/interfaces/http';
import { mountWithPlugins } from '@/tests/_utils/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, reactive, ref } from 'vue';

// Mock child to expose received props for assertions
vi.mock(
    '@/components/domain/Client/Response/ResponseStatus/ResponseStatusCode.vue',
    () => ({
        default: {
            name: 'ResponseStatusCode',
            props: ['status', 'response'],
            template:
                '<div data-testid="status-code" :data-status="status">{{ status }}</div>',
        },
    }),
);

// Mock stores used via '@/stores'
const mockRequestStore = reactive({
    pendingRequestData: computed(() => mockPendingRequest.value),
    cancelCurrentRequest: vi.fn(),
});

const mockHistoryStore = reactive({
    lastLog: ref<any>(null), // eslint-disable-line @typescript-eslint/no-explicit-any
});

const mockPendingRequest = ref<any>(null); // eslint-disable-line @typescript-eslint/no-explicit-any

vi.mock('@/stores', () => ({
    useRequestStore: () => mockRequestStore,
    useRequestsHistoryStore: () => mockHistoryStore,
}));

describe('ResponseStatus', () => {
    beforeEach(() => {
        mockPendingRequest.value = null;
        mockHistoryStore.lastLog = ref(null);
        mockRequestStore.cancelCurrentRequest.mockReset();
    });

    it('shows PENDING status and cancel button while processing', () => {
        mockPendingRequest.value = { isProcessing: true, durationInMs: 1234 };
        const wrapper = mountWithPlugins(ResponseStatus);

        const statusCode = wrapper.get('[data-testid="status-code"]');
        expect(statusCode.attributes()['data-status']).toBe(String(STATUS.PENDING));

        // Cancel button visible
        const cancel = wrapper.find('button');
        expect(cancel.exists()).toBe(true);
    });

    it('shows EMPTY when no last response and not processing', () => {
        mockPendingRequest.value = { isProcessing: false, durationInMs: 0 };
        const wrapper = mountWithPlugins(ResponseStatus);

        const statusCode = wrapper.get('[data-testid="status-code"]');
        expect(statusCode.attributes()['data-status']).toBe(String(STATUS.EMPTY));
    });

    it('derives status from last response when available', () => {
        mockPendingRequest.value = {
            isProcessing: false,
            durationInMs: 0,
            wasExecuted: true,
        };
        mockHistoryStore.lastLog = ref({ response: { status: 201, sizeInBytes: 1024 } });
        const wrapper = mountWithPlugins(ResponseStatus);

        const statusCode = wrapper.get('[data-testid="status-code"]');
        expect(statusCode.attributes()['data-status']).toBe('201');

        expect(wrapper.text()).toMatch(/1.02kB/);
    });

    it('resets size to 0 when request not executed (new endpoint)', () => {
        mockPendingRequest.value = {
            isProcessing: false,
            wasExecuted: false,
            durationInMs: 0,
        };
        mockHistoryStore.lastLog = ref({ response: { sizeInBytes: 12345 } });
        const wrapper = mountWithPlugins(ResponseStatus);

        // Expects "0B" formatting from pretty-bytes with { space: false }
        expect(wrapper.text()).toMatch(/0B/);
    });

    it('uses pending duration when processing, otherwise last log duration', () => {
        // Processing case
        mockPendingRequest.value = {
            isProcessing: true,
            durationInMs: 1500,
            wasExecuted: false,
        };
        mockHistoryStore.lastLog = ref({ durationInMs: 9999 });
        let wrapper = mountWithPlugins(ResponseStatus);
        expect(wrapper.text()).toMatch(/1\.50s/);

        // Completed case
        mockPendingRequest.value = {
            isProcessing: false,
            durationInMs: 2500,
            wasExecuted: true,
        };
        mockHistoryStore.lastLog = ref({
            durationInMs: 3000,
            response: { timestamp: 1700000000, status: STATUS.SUCCESS },
        });
        wrapper = mountWithPlugins(ResponseStatus);
        expect(wrapper.text()).toMatch(/2\.50s/); // <- it prirotizes the one from the pending request.
    });

    it('shows readable time text when last response exists', () => {
        mockPendingRequest.value = { isProcessing: false, durationInMs: 0 };
        mockHistoryStore.lastLog = ref({
            response: { timestamp: Math.floor(Date.now() / 1000) },
        });
        const wrapper = mountWithPlugins(ResponseStatus);

        // The content is time-ago text; assert non-empty text region where it renders
        const small = wrapper.find('small');
        expect(small.exists()).toBe(true);
        expect((small.text() ?? '').length).toBeGreaterThan(0);
    });

    it('cancels request when cancel button clicked', async () => {
        mockPendingRequest.value = { isProcessing: true, durationInMs: 0 };
        const wrapper = mountWithPlugins(ResponseStatus);

        const btn = wrapper.get('button');
        await btn.trigger('click');
        expect(mockRequestStore.cancelCurrentRequest).toHaveBeenCalled();
    });
});
