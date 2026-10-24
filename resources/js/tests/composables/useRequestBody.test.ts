import { useRequestBody } from '@/composables/request/useRequestBody';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, reactive } from 'vue';

/*
 * Fixtures.
 */

const requestStore = reactive({
    pendingRequestData: null as unknown as PendingRequest | null,
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

            const payload = composable.generateCurrentPayload() as string;

            // Assert

            expect(payloadMocks.generatePlaceholderPayload).toHaveBeenCalled();
            expect(payload).toBe('{"serialized":true}');
        });

        it('hydrates payload from memoized body when available', () => {
            // Arrange

            const pending = requestStore.pendingRequestData!;
            pending.body = {
                POST: {
                    [RequestBodyTypeEnum.JSON]: '{"cached":true}',
                },
            };
            const composable = runComposable();
            composable.payloadType.value = RequestBodyTypeEnum.JSON;

            // Act

            const payload = composable.generateCurrentPayload() as string;

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
            expect(composable.payload.value as string).toBe('{"serialized":true}');
        });
    });

    it('initializes immediately from store during setup', () => {
        requestStore.pendingRequestData = createPendingRequest();
        requestStore.pendingRequestData.payloadType = RequestBodyTypeEnum.JSON;
        requestStore.pendingRequestData.body = {
            POST: {
                [RequestBodyTypeEnum.JSON]: '{"immediate":true}',
            },
        };

        const composable = runComposable();

        expect(composable.payloadType.value).toBe(RequestBodyTypeEnum.JSON);
        expect(composable.payload.value as string).toBe('{"immediate":true}');
    });

    it('stores the placeholder in the request body on setup', () => {
        runComposable();

        expect(requestStore.pendingRequestData?.body.POST?.json).toBe(
            '{"serialized":true}',
        );
    });

    it('writes payload edits into the body of the current method and type', () => {
        const composable = runComposable();

        composable.payload.value = '{"edited":true}';

        expect(requestStore.pendingRequestData?.body.POST?.json).toBe('{"edited":true}');
    });

    it('refills the placeholder when the payload is cleared', async () => {
        const composable = runComposable();
        composable.payload.value = '{"edited":true}';

        composable.payload.value = '';
        await nextTick();

        expect(composable.payload.value).toBe('{"serialized":true}');
    });

    it('keeps a cleared payload empty when there is no schema shape', async () => {
        const pending = requestStore.pendingRequestData!;
        pending.schema = { extractionErrors: null } as PendingRequest['schema'];
        const composable = runComposable();

        composable.payload.value = 'something';
        composable.payload.value = '';
        await nextTick();

        expect(composable.payload.value).toBe('');
    });

    it('returns null for the empty body type', async () => {
        const composable = runComposable();

        composable.payloadType.value = RequestBodyTypeEnum.EMPTY;
        await nextTick();

        expect(composable.payload.value).toBeNull();
        expect(composable.generateCurrentPayload()).toBeNull();
    });

    it('restores the form data when switching to another type and back', async () => {
        const composable = runComposable();
        const formData = new FormData();
        formData.set('name', 'Jane');

        composable.payloadType.value = RequestBodyTypeEnum.FORM_DATA;
        composable.payload.value = formData;
        composable.payloadType.value = RequestBodyTypeEnum.JSON;
        await nextTick();
        composable.payloadType.value = RequestBodyTypeEnum.FORM_DATA;
        await nextTick();

        expect(composable.payload.value).toBe(formData);
    });

    it('keeps each method body when the method changes and changes back', async () => {
        const composable = runComposable();
        composable.payload.value = '{"post":true}';

        requestStore.pendingRequestData!.method = 'PUT';
        await nextTick();
        composable.payload.value = '{"put":true}';

        requestStore.pendingRequestData!.method = 'POST';
        await nextTick();

        expect(composable.payload.value).toBe('{"post":true}');
    });

    it('keeps a separate payload per type and updates the request type', async () => {
        const composable = runComposable();
        composable.payload.value = '{"json":true}';

        composable.payloadType.value = RequestBodyTypeEnum.PLAIN_TEXT;
        await nextTick();

        expect(requestStore.pendingRequestData?.payloadType).toBe(
            RequestBodyTypeEnum.PLAIN_TEXT,
        );
        expect(composable.payload.value).toBe('{"serialized":true}');

        composable.payloadType.value = RequestBodyTypeEnum.JSON;

        expect(composable.payload.value).toBe('{"json":true}');
    });

    it('follows the active request when it is replaced', async () => {
        const composable = runComposable();
        const other = createPendingRequest();
        other.payloadType = RequestBodyTypeEnum.PLAIN_TEXT;
        other.body = { POST: { [RequestBodyTypeEnum.PLAIN_TEXT]: 'other' } };

        requestStore.pendingRequestData = other;
        await nextTick();

        expect(composable.payloadType.value).toBe(RequestBodyTypeEnum.PLAIN_TEXT);
        expect(composable.payload.value).toBe('other');
    });
});
