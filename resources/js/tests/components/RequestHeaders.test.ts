import RequestHeaders from '@/components/domain/Client/Request/RequestHeader/RequestHeaders.vue';
import { AuthorizationType } from '@/interfaces/generated';
import { GeneratorType, PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
import { renderWithProviders } from '@/tests/_utils/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive, ref } from 'vue';

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

const renderComponent = () => renderWithProviders(RequestHeaders);

const setPendingRequest = (request: PendingRequest | null) => {
    mockRequestStore.pendingRequestData = ref(request);
};

describe('RequestHeaders', () => {
    beforeEach(() => {
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
                shape: {
                    'x-name': 'root',
                    'x-required': false,
                },
                extractionErrors: null,
            },
            queryParameters: [],
            authorization: { type: AuthorizationType.None },
            supportedRoutes: [],
            routeDefinition: {
                method: 'GET',
                endpoint: 'api/users',
                schema: {
                    shape: {
                        'x-name': 'root',
                        'x-required': false,
                    },
                    extractionErrors: null,
                },
                shortEndpoint: 'api/users',
            },
            isProcessing: false,
            wasExecuted: false,
            durationInMs: 0,
        });

        mockRequestStore.updateRequestHeaders.mockClear();
    });

    it('initializes headers with global defaults and syncs them to the store', async () => {
        renderComponent();

        await nextTick();

        expect(mockRequestStore.updateRequestHeaders).toHaveBeenCalledWith(
            expect.arrayContaining([
                expect.objectContaining({ key: 'X-Global', value: 'foo' }),
                expect.objectContaining({
                    key: 'X-Generated',
                    value: 'generated@example.com',
                }),
            ]),
        );
        expect(generateValue).toHaveBeenCalledWith('email');
    });

    it('reinitializes headers when the request method changes', async () => {
        renderComponent();

        await nextTick();

        mockRequestStore.updateRequestHeaders.mockClear();

        setPendingRequest({
            ...mockRequestStore.pendingRequestData!,
            method: 'PUT', // <- Different method that the original one.
            headers: [],
        });

        await nextTick();

        expect(mockRequestStore.updateRequestHeaders).toHaveBeenCalledWith(
            expect.arrayContaining([
                expect.objectContaining({ key: 'X-Global', value: 'foo' }),
            ]),
        );
    });

    it('reinitializes headers when the request endpoint changes', async () => {
        renderComponent();

        await nextTick();

        mockRequestStore.updateRequestHeaders.mockClear();

        setPendingRequest({
            ...mockRequestStore.pendingRequestData!,
            endpoint: 'api/accounts',
            headers: [],
        });

        await nextTick();

        expect(mockRequestStore.updateRequestHeaders).toHaveBeenCalledWith(
            expect.arrayContaining([
                expect.objectContaining({ key: 'X-Global', value: 'foo' }),
            ]),
        );
    });

    it('does not reinitialize when method and endpoint stay the same', async () => {
        renderComponent();

        await nextTick();

        mockRequestStore.updateRequestHeaders.mockClear();

        setPendingRequest({
            ...mockRequestStore.pendingRequestData!,
            headers: [],
        });

        await nextTick();

        expect(mockRequestStore.updateRequestHeaders).not.toHaveBeenCalled();
    });

    it('merges existing request headers with global ones when changing endpoints', async () => {
        mockRequestStore.pendingRequestData.headers = [
            { key: 'X-Existing', value: '123' },
            { key: 'X-Global', value: 'custom' },
        ];

        renderComponent();

        mockRequestStore.updateRequestHeaders.mockClear();

        setPendingRequest({
            ...mockRequestStore.pendingRequestData!,
            method: 'PUT', // <- Different method that the original one to re-trigger th.
            headers: [],
        });

        await nextTick();

        expect(mockRequestStore.updateRequestHeaders).toHaveBeenCalledWith(
            expect.arrayContaining([
                expect.objectContaining({ key: 'X-Existing', value: '123' }),
                expect.objectContaining({ key: 'X-Global', value: 'custom' }),
            ]),
        );
    });
});
