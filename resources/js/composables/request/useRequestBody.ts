import type { PendingRequest, RequestHeader } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { useRequestStore } from '@/stores';
import {
    generatePlaceholderPayload,
    generateRandomPayload,
    serializeSchemaPayload,
} from '@/utils/payload';
import type { TypeShape } from '@/utils/request/content-type-header-generator';
import { types } from '@/utils/request/content-type-header-generator';
import { type ComputedRef, type Ref, computed, onMounted, ref, watch } from 'vue';

export function useRequestBody(): {
    payloadType: Ref<RequestBodyTypeEnum>;
    payload: Ref<FormData | string | null>;
    pendingRequestData: ComputedRef<
        ReturnType<typeof useRequestStore>['pendingRequestData']
    >;
    supportsAutoFill: ComputedRef<boolean>;
    autofill: () => void;
    generateCurrentPayload: () => FormData | string | null;
    initializePayloadTypeFromHeaders: () => void;
    types: TypeShape[];
} {
    /*
     * Stores & dependencies.
     */

    const requestStore = useRequestStore();
    const generateRandomPayloadFn = generateRandomPayload;
    const generatePlaceholderPayloadFn = generatePlaceholderPayload;

    /*
     * State.
     */

    const payloadType = ref<RequestBodyTypeEnum>(RequestBodyTypeEnum.EMPTY);
    const payload = ref<FormData | string | null>(null);

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

        // Use optional chaining and nullish coalescing for cleaner access
        const memoizedBody =
            (body as PendingRequest['body'])?.[method]?.[payloadType.value] ?? null;

        if (memoizedBody) {
            return memoizedBody;
        }

        // If we don't have the value memoized, we make up a new placeholder initial state.
        const JSONSchema7 = pendingRequestData.value?.schema?.shape;

        if (!JSONSchema7) {
            return null;
        }

        const placeholderPayload = generatePlaceholderPayloadFn(JSONSchema7);

        // Store and return the serialized payload
        return serializeSchemaPayload(placeholderPayload, payloadType.value);
    };

    /**
     * Initializes payload type from existing Content-Type headers.
     *
     * Reads the current Content-Type header and sets the payload type
     * to match the corresponding MIME type if found.
     */
    const initializePayloadTypeFromHeaders = () => {
        const currentContentType: RequestHeader | undefined =
            pendingRequestData.value?.headers.find(
                (header: RequestHeader) => header.key === 'Content-Type',
            );

        if (!currentContentType) {
            return;
        }

        const matchingTypeFromContentType: TypeShape | undefined = types.find(
            type => type.mimeType === currentContentType.value,
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

        const generatedPayload = generateRandomPayloadFn(schemaShape);
        const serializedPayload = serializeSchemaPayload(
            generatedPayload,
            payloadType.value,
        );

        payload.value = serializedPayload;
    };

    /*
     * Watchers.
     */

    watch(
        pendingRequestData,
        newValue => {
            payloadType.value = newValue?.payloadType ?? RequestBodyTypeEnum.EMPTY;
            payload.value = generateCurrentPayload();
        },
        { deep: true, immediate: true },
    );

    watch(payloadType, newValue => {
        if (pendingRequestData.value === null) {
            payload.value = generateCurrentPayload();

            return;
        }

        // Switch between payloads based on the current body type,
        // Meaning that each tab will have its state.
        payload.value = generateCurrentPayload();
        pendingRequestData.value.payloadType = newValue;
    });

    watch(
        payload,
        () => {
            if (!pendingRequestData.value) {
                return;
            }

            const method = pendingRequestData.value.method;

            // Initialize body as object if it's null
            if (!pendingRequestData.value.body) {
                pendingRequestData.value.body = {} as PendingRequest['body'];
            }

            const body = pendingRequestData.value.body as PendingRequest['body'];
            if (!body[method]) {
                body[method] = {};
            }

            body[method][payloadType.value] = payload.value;
        },
        { deep: true },
    );

    /*
     * Lifecycle.
     */

    onMounted(() => {
        initializePayloadTypeFromHeaders();

        payload.value = generateCurrentPayload();
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
        initializePayloadTypeFromHeaders,

        // Constants
        types,
    };
}
