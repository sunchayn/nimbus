import type { ParameterContract, PendingRequest } from '@/interfaces';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { useEnvironmentVariablesStore, useRequestStore } from '@/stores';
import {
    generatePlaceholderPayload,
    generateRandomPayload,
    serializeSchemaPayload,
} from '@/utils/payload';
import type { TypeShape } from '@/utils/request/contentTypeHeaderGenerator';
import { types } from '@/utils/request/contentTypeHeaderGenerator';
import {
    type ComputedRef,
    type WritableComputedRef,
    computed,
    onMounted,
    watch,
} from 'vue';

export interface UseRequestBodyResult {
    payloadType: WritableComputedRef<RequestBodyTypeEnum>;
    payload: WritableComputedRef<FormData | string | null>;
    pendingRequestData: ComputedRef<
        ReturnType<typeof useRequestStore>['pendingRequestData']
    >;
    supportsAutoFill: ComputedRef<boolean>;
    autofill: () => void;
    generateCurrentPayload: () => FormData | string | null;
    types: TypeShape[];
}

/**
 * Composable for managing the request body state and payload generation.
 */
export const useRequestBody = (): UseRequestBodyResult => {
    /*
     * Dependencies.
     */

    const requestStore = useRequestStore();
    const environmentVariablesStore = useEnvironmentVariablesStore();

    /*
     * Computed.
     */

    const pendingRequestData = computed(() => requestStore.pendingRequestData);

    const supportsAutoFill = computed(() => {
        return (
            types.find(type => type.id === payloadType.value)?.autoFillable === true &&
            pendingRequestData.value?.schema?.shape !== undefined
        );
    });

    const payloadType = computed<RequestBodyTypeEnum>({
        get: () => pendingRequestData.value?.payloadType ?? RequestBodyTypeEnum.EMPTY,
        set: value => {
            if (pendingRequestData.value) {
                pendingRequestData.value.payloadType = value;
            }
        },
    });

    // The body is memoized per method and type, and the request store is the only source.
    const payload = computed<FormData | string | null>({
        get: () => {
            const request = pendingRequestData.value;

            return request?.body?.[request.method]?.[payloadType.value] ?? null;
        },
        set: value => {
            const request = pendingRequestData.value;

            if (!request) {
                return;
            }

            const body = (request.body ??= {} as PendingRequest['body']);
            const methodBody = (body[request.method] ??= {});

            methodBody[payloadType.value] = value;
        },
    });

    /*
     * Actions.
     */

    /**
     * Generates the current payload based on request data and type.
     *
     * Returns the memoized payload for the current method and type, or generates
     * a placeholder payload from the schema if none exists.
     */
    const generateCurrentPayload = (): FormData | string | null => {
        if (!pendingRequestData.value) {
            return null;
        }

        if (payloadType.value === RequestBodyTypeEnum.EMPTY) {
            return null;
        }

        const method = pendingRequestData.value.method;
        const body = pendingRequestData.value.body;

        const memoizedBody =
            (body as PendingRequest['body'])?.[method]?.[payloadType.value] ?? null;

        if (memoizedBody) {
            return memoizedBody as FormData | string | null;
        }

        // If we don't have the value memoized, we make up a new placeholder initial state.
        const JSONSchema7 = pendingRequestData.value?.schema?.shape;

        if (!JSONSchema7) {
            return null;
        }

        const placeholderPayload = generatePlaceholderPayload(JSONSchema7);

        return serializeSchemaPayload(placeholderPayload, payloadType.value);
    };

    /**
     * Initializes payload type from existing Content-Type headers.
     *
     * Reads the current Content-Type header and sets the payload type
     * to match the corresponding MIME type if found.
     */
    const initializePayloadTypeFromHeaders = () => {
        const currentContentType: ParameterContract | undefined =
            pendingRequestData.value?.headers.find(
                (header: ParameterContract) => header.key === 'Content-Type',
            );

        if (!currentContentType) {
            return;
        }

        const matchingTypeFromContentType: TypeShape | undefined = types.find(
            function (type) {
                const currentType = environmentVariablesStore.resolve(
                    currentContentType.value,
                );

                return type.mimeType === currentType;
            },
        );

        if (!matchingTypeFromContentType) {
            return;
        }

        payloadType.value = matchingTypeFromContentType.id;
    };

    /**
     * Generates and applies random data to the request body.
     *
     * Uses the schema shape to generate realistic random data and applies
     * it to the current payload, updating the request store.
     */
    const autofill = () => {
        if (!pendingRequestData.value) {
            return;
        }

        const schemaShape = pendingRequestData.value.schema?.shape;

        if (!schemaShape) {
            return;
        }

        const generatedPayload = generateRandomPayload(schemaShape);
        const serializedPayload = serializeSchemaPayload(
            generatedPayload,
            payloadType.value,
        );

        payload.value = serializedPayload;
    };

    /*
     * Watchers.
     */

    // A body that is missing or cleared gets the schema placeholder so the editor is never blank.
    watch(
        () => [
            pendingRequestData.value,
            pendingRequestData.value?.method,
            payloadType.value,
            payload.value,
        ],
        () => {
            if (payload.value) {
                return;
            }

            const generated = generateCurrentPayload();

            if (generated !== null) {
                payload.value = generated;
            }
        },
        { immediate: true },
    );

    /*
     * Lifecycle.
     */

    onMounted(() => {
        initializePayloadTypeFromHeaders();
    });

    return {
        // State
        payloadType,
        payload,

        // Computed
        pendingRequestData,
        supportsAutoFill,

        // Actions
        autofill,
        generateCurrentPayload,

        // Constants
        types,
    };
};
