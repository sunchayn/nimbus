import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive, ref } from 'vue';
import RequestHeaders from '@/components/domain/Client/Request/RequestHeaders/RequestHeaders.vue';
import { AuthorizationType } from '@/interfaces/generated';
import { GeneratorType, PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterContract, ParameterType } from '@/interfaces/ui';
import { RenderWithProvidersOptions } from "@/tests/_utils/test-utils";

/*
 * Fixtures.
 */

const mockConfigStore = reactive({
    headers: [
        { header: 'X-Global', type: 'raw', value: 'foo' },
        { header: 'X-Generated', type: 'generator', value: GeneratorType.Email },
    ],
});

const generateValue = vi.fn(() => 'generated@example.com');

const mockRequestStore = reactive({
    pendingRequestData: ref<PendingRequest | null>(null),
    updateRequestHeaders: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
        useConfigStore: () => mockConfigStore,
        useValueGeneratorStore: () => ({
            generateValue,
        }),
    };
});

const setPendingRequest = (request: PendingRequest | null) => {
    mockRequestStore.pendingRequestData = ref(request) as any;
};

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount(RequestHeaders, {
        ...options,
        global: {
            plugins: [createPinia()],
            stubs: {
                // Stub heavy child components if any
                KeyValueParameters: true,
            },
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('RequestHeaders', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
        generateValue.mockClear();

        mockConfigStore.headers = [
            { header: 'X-Global', type: 'raw', value: 'foo' },
            { header: 'X-Generated', type: 'generator', value: GeneratorType.Email },
        ];

        setPendingRequest({
            method: 'GET',
            endpoint: 'api/users',
            headers: [],
            body: {},
            payloadType: RequestBodyTypeEnum.EMPTY,
            schema: {
                shape: {},
                extractionErrors: null,
            },
            queryParameters: [],
            authorization: { type: AuthorizationType.None },
            supportedRoutes: [],
            routeDefinition: {
                method: 'GET',
                endpoint: 'api/users',
                schema: {
                    shape: {},
                    extractionErrors: null,
                },
                shortEndpoint: 'api/users',
            },
            isProcessing: false,
            wasExecuted: false,
            durationInMs: 0,
        });

        mockRequestStore.updateRequestHeaders.mockClear();
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('initializes headers with global defaults and syncs them to the store', async () => {
            // Arrange

            createWrapper();

            // Act

            await nextTick();
            vi.advanceTimersByTime(310);
            await nextTick();

            // Assert

            expect(mockRequestStore.updateRequestHeaders).toHaveBeenCalledWith(
                expect.arrayContaining([
                    expect.objectContaining({ key: 'X-Global', value: 'foo', enabled: true }),
                    expect.objectContaining({
                        key: 'X-Generated',
                        value: 'generated@example.com',
                        enabled: true,
                    }),
                ]),
            );
            expect(generateValue).toHaveBeenCalledWith('email');
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('preserves headers and does not re-inject globals when the endpoint changes', async () => {
            // Arrange

            createWrapper();

            await nextTick();
            vi.advanceTimersByTime(310);
            await nextTick();

            const firstSyncCall = vi.mocked(mockRequestStore.updateRequestHeaders).mock.calls[0][0];

            // Act - Simulate the store being updated with these headers
            setPendingRequest({
                ...mockRequestStore.pendingRequestData!,
                headers: firstSyncCall,
            });

            await nextTick();
            mockRequestStore.updateRequestHeaders.mockClear();

            // Act - Change the endpoint
            setPendingRequest({
                ...mockRequestStore.pendingRequestData!,
                endpoint: 'api/other-endpoint',
            });

            await nextTick();
            vi.advanceTimersByTime(310);
            await nextTick();

            // Assert - Should not have triggered a new update because effectiveHeaders returned currentHeaders
            expect(mockRequestStore.updateRequestHeaders).not.toHaveBeenCalled();
        });

        it('prefers existing store headers over global defaults', async () => {
            // Arrange

            const customHeaders: ParameterContract[] = [
                {
                    key: 'X-Custom',
                    value: 'custom-value',
                    enabled: true,
                    id: 1,
                    type: ParameterType.Text,
                },
            ];

            setPendingRequest({
                ...mockRequestStore.pendingRequestData!,
                headers: customHeaders,
            });

            createWrapper();

            // Act

            await nextTick();
            vi.advanceTimersByTime(310);
            await nextTick();

            // Assert - It should NOT have initialized with global headers
            expect(mockRequestStore.updateRequestHeaders).not.toHaveBeenCalledWith(
                expect.arrayContaining([expect.objectContaining({ key: 'X-Global' })]),
            );
        });
    });
});
