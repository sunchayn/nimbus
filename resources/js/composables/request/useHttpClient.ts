import { httpClientConfig } from '@/config';
import type { AuthorizationContract, ParameterContract } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import type {
    HttpHeaders,
    PendingRequest,
    RelayProxyResponse,
    Response,
} from '@/interfaces/http';
import { useConfigStore, useEnvironmentVariablesStore } from '@/stores';
import { buildRequestUrl } from '@/utils';
import { convertPayloadToFormData, getStatusGroup } from '@/utils/http';
import { generateContentTypeHeader } from '@/utils/request/contentTypeHeaderGenerator';
import type { AxiosError, AxiosResponse } from 'axios';
import axios from 'axios';
import { type DeepReadonly, type Ref, readonly, ref } from 'vue';

export interface RequestResult {
    response: Response;
    duration: number;
}

export interface UseHttpClientResult {
    executeRequest: (request: PendingRequest) => Promise<RequestResult | null>;
    cancelCurrentRequest: () => void;
    buildUrlFromRequest: (request: PendingRequest) => string;
    isExecuting: DeepReadonly<Ref<boolean>>;
}

/**
 * Composable for handling HTTP requests through the relay proxy.
 */
export function useHttpClient(): UseHttpClientResult {
    /*
     * Dependencies.
     */

    const configStore = useConfigStore();
    const environmentVariablesStore = useEnvironmentVariablesStore();

    /*
     * State.
     */

    const abortController = ref<AbortController | null>(null);
    const isExecuting = ref(false);

    /*
     * Utilities.
     */

    const buildUrlFromRequest = (request: PendingRequest): string => {
        // Remove leading slashes to prevent double slashes in final URL.
        const endpoint = environmentVariablesStore
            .resolve(request.endpoint)
            .replace(/^\/+/, '');

        return buildRequestUrl(
            configStore.apiUrl,
            endpoint,
            // Only append enabled parameters with non-empty keys to avoid malformed URLs.
            request.queryParameters.filter(
                (parameter: ParameterContract) => parameter.enabled,
            ),
            environmentVariablesStore.resolve,
        );
    };

    /**
     * Body is memoized by method > payload type structure for better UX (keep-alive state).
     */
    const getMemoizedBody = (request: PendingRequest) => {
        // First extraction: get body for the specific HTTP method (GET, POST, etc.)
        const methodBodies = request.body[request.method] ?? null;

        // Second extraction: get body for the specific payload type (JSON, FormData, etc.)
        // This double extraction is necessary due to the nested memoization structure
        const body = methodBodies ? (methodBodies[request.payloadType] ?? null) : null;

        if (body instanceof FormData) {
            return body;
        }

        if (body === null) {
            return null;
        }

        return environmentVariablesStore.resolve(body as string);
    };

    function buildRelayAuthorization(authorization: AuthorizationContract) {
        switch (authorization.type) {
            case AuthorizationType.Basic:
                return {
                    type: authorization.type,
                    value: {
                        username: environmentVariablesStore.resolve(
                            authorization.value.username,
                        ),
                        password: environmentVariablesStore.resolve(
                            authorization.value.password,
                        ),
                    },
                };

            case AuthorizationType.Bearer:
                return {
                    type: authorization.type,
                    value: environmentVariablesStore.resolve(authorization.value),
                };

            default:
                return authorization;
        }
    }

    const createRelayPayload = (request: PendingRequest) => {
        // Generate Content-Type header just before making the request
        // This ensures the correct header is sent without persisting it in the store
        const headersWithContentType = generateContentTypeHeader(
            request.payloadType,
            request.headers
                .filter(
                    (parameter: ParameterContract) =>
                        parameter.enabled && parameter.key.trim() !== '',
                )
                .map(
                    (parameter): HttpHeaders => ({
                        key: parameter.key,
                        value: environmentVariablesStore.resolve(parameter.value),
                    }),
                ),
        );

        return {
            endpoint: buildUrlFromRequest(request),
            method: request.method,
            headers: headersWithContentType,
            authorization: buildRelayAuthorization(request.authorization),
            body: getMemoizedBody(request),
        };
    };

    const transformRelayResponse = (relayResponse: RelayProxyResponse): Response => {
        const headers = relayResponse.headers;
        const statusCode = relayResponse.statusCode;

        const contentLengthHeader = relayResponse.headers.find(
            (header: HttpHeaders) => header.key.toLowerCase() === 'content-length',
        );

        return {
            status: getStatusGroup(statusCode),
            statusCode,
            statusText: relayResponse.statusText,
            body: relayResponse.body,
            // Prefer content-length header for accurate size, fallback to body length
            sizeInBytes: contentLengthHeader
                ? Number(contentLengthHeader.value)
                : relayResponse.body.length,
            headers: headers,
            cookies: relayResponse.cookies.map(cookie => ({
                key: cookie.key,
                value: {
                    raw: cookie.value.raw,
                    decrypted: cookie.value.decrypted,
                },
            })),
            timestamp: relayResponse.timestamp,
        };
    };

    const sendRequest = (request: PendingRequest): Promise<RequestResult | null> => {
        return new Promise<RequestResult | null>((resolve, reject) => {
            const url = configStore.appBasePath + '/api/relay';
            const payload = createRelayPayload(request);
            const formData = convertPayloadToFormData(payload);

            const headers: Record<string, string> = {
                'Content-Type': 'multipart/form-data',
            };

            // Add transaction mode header if enabled
            if (request.transactionMode) {
                headers['X-Nimbus-Transaction-Mode'] = '1';
            }

            axios
                .post(url, formData, {
                    headers,
                    // Prevent Axios from parsing JSON automatically as we are handling it manually.
                    transformResponse: response => response,
                    signal: abortController.value?.signal,
                })
                .then((axiosResponse: AxiosResponse) => {
                    // Parse the relay response manually to maintain control over the process
                    const relayResponse = JSON.parse(
                        axiosResponse.data,
                    ) as RelayProxyResponse;

                    const response = transformRelayResponse(relayResponse);

                    resolve({ response, duration: relayResponse.duration });
                })
                .catch((error: AxiosError) => {
                    // Handle request cancellation gracefully -> not an error state.
                    if (error.code === 'ERR_CANCELED') {
                        resolve(null);

                        return;
                    }

                    // HTTP error responses (4xx, 5xx) -> include status and body for debugging.
                    if (error.response) {
                        let responseMessage = error.message;

                        try {
                            responseMessage =
                                JSON.parse(error.response.data as string).message ??
                                error.message;
                        } catch (e) {
                            console.error(e);
                        }

                        reject({
                            message: responseMessage,
                            status: error.response.status,
                            body: error.response.data,
                        });

                        return;
                    }

                    // Network errors or other failures -> just the error message
                    reject({ message: error.message });
                });
        });
    };

    /*
     * Actions.
     */

    const cancelCurrentRequest = () => {
        if (!abortController.value) {
            return;
        }

        abortController.value.abort();
    };

    const executeRequest = async (
        request: PendingRequest,
    ): Promise<RequestResult | null> => {
        // Prevent concurrent requests to avoid race conditions
        if (isExecuting.value) {
            throw new Error('Request already in progress');
        }

        isExecuting.value = true;
        abortController.value = new AbortController();

        try {
            const timeoutPromise = new Promise<never>((_, reject) =>
                setTimeout(
                    () => reject(new Error('Request timeout')),
                    httpClientConfig.TIMEOUT,
                ),
            );

            return await Promise.race([sendRequest(request), timeoutPromise]);
        } finally {
            // Always clean up state, even if request fails
            isExecuting.value = false;
            abortController.value = null;
        }
    };

    return {
        // Actions
        executeRequest,
        cancelCurrentRequest,
        buildUrlFromRequest,

        // State
        isExecuting: readonly(isExecuting),
    };
}
