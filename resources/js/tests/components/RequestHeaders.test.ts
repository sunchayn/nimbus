import RequestHeaders from '@/components/domain/Client/Request/RequestHeader/RequestHeaders.vue';
import { mountWithPlugins } from '@/tests/_utils/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive, ref } from 'vue';

// Mocks for stores consumed via '@/stores' barrel
const mockRequestStore = reactive({
    pendingRequestData: ref<any>(null), // eslint-disable-line @typescript-eslint/no-explicit-any
    updateRequestHeaders: vi.fn(),
});

const mockConfigStore = reactive({
    headers: [
        {
            header: 'X-Global',
            type: 'raw',
            value: 'foo',
        },
    ],
});

const mockValueGeneratorStore = reactive({
    generateValue: vi.fn(),
});

vi.mock('@/stores', () => ({
    useRequestStore: () => mockRequestStore,
    useConfigStore: () => mockConfigStore,
    useValueGeneratorStore: () => mockValueGeneratorStore,
}));

describe('RequestHeaders.vue', () => {
    beforeEach(() => {
        mockRequestStore.pendingRequestData = ref<any>(null); // eslint-disable-line @typescript-eslint/no-explicit-any
        mockRequestStore.updateRequestHeaders.mockReset();
    });

    it('re-initializes and syncs headers when endpoint and method changes', async () => {
        mountWithPlugins(RequestHeaders);

        // Simulate initial pending request on endpoint with method GET and no headers set in store
        mockRequestStore.pendingRequestData = ref({
            method: 'GET',
            endpoint: '/api/users',
            headers: [],
        });

        await nextTick();

        // Change only the method (same endpoint). This should re-initialize headers and sync to store.
        const callsBefore = mockRequestStore.updateRequestHeaders.mock.calls.length;

        mockRequestStore.pendingRequestData = ref({
            method: 'POST',
            endpoint: '/api/users',
            headers: [],
        });

        await nextTick();

        // Expect updateRequestHeaders called with the global header present
        expect(mockRequestStore.updateRequestHeaders.mock.calls.length).toBeGreaterThan(
            callsBefore,
        );

        const lastCallArgs = mockRequestStore.updateRequestHeaders.mock.calls.at(-1)?.[0];

        expect(Array.isArray(lastCallArgs)).toBe(true);

        expect(lastCallArgs).toEqual(
            expect.arrayContaining([
                expect.objectContaining({ key: 'X-Global', value: 'foo' }),
            ]),
        );
    });

    it('re-initializes and syncs headers when only endpoint changes', async () => {
        mountWithPlugins(RequestHeaders);

        // Seed initial state
        mockRequestStore.pendingRequestData = ref({
            method: 'GET',
            endpoint: '/api/users',
            headers: [],
        });
        await nextTick();

        const callsBefore = mockRequestStore.updateRequestHeaders.mock.calls.length;

        // Change endpoint only (method remains the same)
        mockRequestStore.pendingRequestData = {
            method: 'GET',
            endpoint: '/api/accounts',
            headers: [],
        };
        await nextTick();

        expect(mockRequestStore.updateRequestHeaders.mock.calls.length).toBeGreaterThan(
            callsBefore,
        );

        const args = mockRequestStore.updateRequestHeaders.mock.calls.at(-1)?.[0];
        expect(Array.isArray(args)).toBe(true);
        expect(args).toEqual(
            expect.arrayContaining([
                expect.objectContaining({ key: 'X-Global', value: 'foo' }),
            ]),
        );
    });

    it('does not re-initialize when endpoint and method are unchanged', async () => {
        mountWithPlugins(RequestHeaders);

        mockRequestStore.pendingRequestData = ref({
            method: 'GET',
            endpoint: '/api/users',
            headers: [],
        });
        await nextTick();

        const callsBefore = mockRequestStore.updateRequestHeaders.mock.calls.length;

        // Re-emit the same values (new object but same endpoint and method)
        mockRequestStore.pendingRequestData = {
            method: 'GET',
            endpoint: '/api/users',
            headers: [],
        };
        await nextTick();

        // No additional sync should have occurred
        expect(mockRequestStore.updateRequestHeaders.mock.calls.length).toBe(callsBefore);
    });
});
