import { useRequestBody } from '@/composables/request/useRequestBody';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, reactive } from 'vue';

const requestStore = reactive<{
    pendingRequestData: PendingRequest | null;
}>({
    pendingRequestData: null,
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => requestStore,
    };
});

const payloadMocks = vi.hoisted(() => ({
    generatePlaceholderPayload: vi.fn(() => ({ placeholder: true })),
    generateRandomPayload: vi.fn(() => ({ random: true })),
    serializeSchemaPayload: vi.fn(() => '{"serialized":true}'),
}));

vi.mock('@/utils/payload', () => payloadMocks);

const createPendingRequest = (): PendingRequest => ({
    method: 'POST',
    endpoint: 'api/users',
    headers: [],
    body: {},
    payloadType: RequestBodyTypeEnum.JSON,
    schema: {
        shape: {
            'x-name': 'root',
            'x-required': true,
            properties: {
                name: {
                    'x-name': 'name',
                    'x-required': false,
                    type: 'string',
                },
            },
        },
        extractionErrors: null,
    },
    queryParameters: [],
    authorization: { type: AuthorizationType.None },
    supportedRoutes: [],
    routeDefinition: {
        method: 'POST',
        endpoint: 'api/users',
        shortEndpoint: 'api/users',
        schema: {
            shape: {
                'x-name': 'root',
                'x-required': true,
                properties: {
                    name: {
                        'x-name': 'name',
                        'x-required': false,
                        type: 'string',
                    },
                },
            },
            extractionErrors: null,
        },
    },
    isProcessing: false,
    wasExecuted: false,
    durationInMs: 0,
});

describe('useRequestBody', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        requestStore.pendingRequestData = createPendingRequest();
        payloadMocks.generatePlaceholderPayload.mockClear();
        payloadMocks.generateRandomPayload.mockClear();
        payloadMocks.serializeSchemaPayload.mockClear();
    });

    const runComposable = () => {
        let composable: ReturnType<typeof useRequestBody>;

        effectScope().run(() => {
            composable = useRequestBody();
        });

        // @ts-expect-error composable assigned within scope
        return composable as ReturnType<typeof useRequestBody>;
    };

    it('generates placeholder payload when none memoized', () => {
        const composable = runComposable();

        composable.payloadType.value = RequestBodyTypeEnum.JSON;
        const payload = composable.generateCurrentPayload();

        expect(payloadMocks.generatePlaceholderPayload).toHaveBeenCalled();
        expect(payloadMocks.serializeSchemaPayload).toHaveBeenCalled();
        expect(payload).toBe('{"serialized":true}');
    });

    it('hydrates payload from memoized body when available', () => {
        const pending = requestStore.pendingRequestData!;
        pending.body = {
            POST: {
                [RequestBodyTypeEnum.JSON]: '{"cached":true}',
            },
        };

        const composable = runComposable();

        composable.payloadType.value = RequestBodyTypeEnum.JSON;
        const payload = composable.generateCurrentPayload();

        expect(payload).toBe('{"cached":true}');
    });

    it('updates content-type header when payload type changes', async () => {
        const composable = runComposable();

        composable.payloadType.value = RequestBodyTypeEnum.JSON;

        await nextTick();

        expect(
            requestStore.pendingRequestData?.headers.find(
                header => header.key === 'content-type',
            )?.value,
        ).toBe('application/json');
    });

    it('autofills payload using random generator', () => {
        const composable = runComposable();

        composable.payloadType.value = RequestBodyTypeEnum.JSON;
        composable.autofill();

        expect(payloadMocks.generateRandomPayload).toHaveBeenCalled();
        expect(payloadMocks.serializeSchemaPayload).toHaveBeenCalled();
        expect(composable.payload.value).toBe('{"serialized":true}');
    });
});
