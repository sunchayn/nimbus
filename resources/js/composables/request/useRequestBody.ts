import { PendingRequest, RequestBodyTypeEnum, RequestHeader } from '@/interfaces/http';
import { useRequestStore } from '@/stores';
import {
    generatePlaceholderPayload,
    generateRandomPayload,
    serializeSchemaPayload,
} from '@/utils/payload';
import { computed, onMounted, ref, watch } from 'vue';

/*
 * Types & interfaces.
 */

interface TypeShape {
    id: RequestBodyTypeEnum;
    label: string;
    autoFillable: boolean;
    mimeType: string | null;
}

/*
 * Constants.
 */

const types: TypeShape[] = [
    {
        id: RequestBodyTypeEnum.EMPTY,
        label: 'Empty',
        autoFillable: false,
        mimeType: null,
    },
    {
        id: RequestBodyTypeEnum.JSON,
        label: 'JSON',
        autoFillable: true,
        mimeType: 'application/json',
    },
    {
        id: RequestBodyTypeEnum.PLAIN_TEXT,
        label: 'Plain Text',
        autoFillable: false,
        mimeType: 'text/plain',
    },
    {
        id: RequestBodyTypeEnum.FORM_DATA,
        label: 'Form Data',
        autoFillable: true,
        mimeType: 'multipart/form-data',
    },
];

export function useRequestBody() {
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
     * Updates the Content-Type header based on the selected payload type.
     *
     * Automatically manages the Content-Type header by adding, updating, or
     * removing it based on the payload type's associated MIME type.
     */
    const updateContentTypeHeader = (newValue: RequestBodyTypeEnum) => {
        if (pendingRequestData.value === null) {
            return;
        }

        const contentTypeIndex = pendingRequestData.value.headers.findIndex(
            (header: RequestHeader) => header.key === 'content-type',
        );

        const value =
            types.find(contentType => contentType.id === newValue)?.mimeType ?? null;

        if (contentTypeIndex !== -1) {
            if (value === null) {
                pendingRequestData.value.headers.splice(contentTypeIndex, 1);

                return;
            }

            pendingRequestData.value.headers[contentTypeIndex].value = value;

            return;
        }

        if (value === null) {
            return;
        }

        pendingRequestData.value.headers.push({
            key: 'content-type',
            value: value,
        });
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
        { deep: true },
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

        updateContentTypeHeader(newValue);
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
        updateContentTypeHeader,
        initializePayloadTypeFromHeaders,

        // Constants
        types,
    };
}
