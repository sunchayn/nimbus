import { useRequestBody } from '@/composables/request/useRequestBody';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, reactive } from 'vue';

/*
 * Fixtures.
 */

const requestStore = reactive({
    pendingRequestData: null as PendingRequest | null,
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
        shape: { properties: { name: { type: 'string' } } },
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
            shape: { properties: { name: { type: 'string' } } },
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
        vi.clearAllMocks();
    });

    const runComposable = (): ReturnType<typeof useRequestBody> => {
        let composable: ReturnType<typeof useRequestBody>;
        effectScope().run(() => {
            composable = useRequestBody();
        });

        return composable!;
    };

    /*
     * Generation tests.
     */

    describe('Generation', () => {
        it('generates placeholder payload when none memoized', () => {
            // Arrange

            const composable = runComposable();
            composable.payloadType.value = RequestBodyTypeEnum.JSON;

            // Act

            const payload = composable.generateCurrentPayload();

            // Assert

            expect(payloadMocks.generatePlaceholderPayload).toHaveBeenCalled();
            expect(payload).toBe('{"serialized":true}');
        });

        it('hydrates payload from memoized body when available', () => {
            // Arrange

            const pending = requestStore.pendingRequestData!;
            pending.body = { POST: { [RequestBodyTypeEnum.JSON]: '{"cached":true}' } };
            const composable = runComposable();
            composable.payloadType.value = RequestBodyTypeEnum.JSON;

            // Act

            const payload = composable.generateCurrentPayload();

            // Assert

            expect(payload).toBe('{"cached":true}');
        });
    });

    /*
     * Behavior tests.
     */

    describe('Behavior', () => {
        it('autofills payload using random generator', () => {
            // Arrange

            const composable = runComposable();
            composable.payloadType.value = RequestBodyTypeEnum.JSON;

            // Act

            composable.autofill();

            // Assert

            expect(payloadMocks.generateRandomPayload).toHaveBeenCalled();
            expect(composable.payload.value).toBe('{"serialized":true}');
        });
    });
});
