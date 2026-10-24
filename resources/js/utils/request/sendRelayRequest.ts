import type { PendingRequest, RelayProxyResponse, Response } from '@/interfaces/http';
import { convertPayloadToFormData } from '@/utils/http';
import {
    createRelayPayload,
    transformRelayResponse,
    type ResolveEnvironmentVariables,
} from '@/utils/request/relayRequest';
import type { AxiosError, AxiosResponse } from 'axios';
import axios from 'axios';

export interface RequestResult {
    response: Response;
    duration: number;
}

export interface SendRelayRequestOptions {
    apiUrl: string;
    appBasePath: string;
    resolve: ResolveEnvironmentVariables;
    signal?: AbortSignal;
}

/**
 * Sends a request through the relay proxy.
 *
 * Resolves with null when the request was canceled through the signal.
 * Rejects with a plain object holding the message, and the status and body for HTTP errors.
 */
export const sendRelayRequest = (
    request: PendingRequest,
    { apiUrl, appBasePath, resolve, signal }: SendRelayRequestOptions,
): Promise<RequestResult | null> => {
    return new Promise<RequestResult | null>((resolveResult, reject) => {
        const url = appBasePath + '/api/relay';
        const payload = createRelayPayload(request, apiUrl, resolve);
        const formData = convertPayloadToFormData(payload);

        const headers: Record<string, string> = {
            'Content-Type': 'multipart/form-data',
        };

        if (request.transactionMode) {
            headers['X-Nimbus-Transaction-Mode'] = '1';
        }

        axios
            .post(url, formData, {
                headers,
                // Prevent Axios from parsing JSON automatically as we are handling it manually.
                transformResponse: response => response,
                signal,
            })
            .then((axiosResponse: AxiosResponse) => {
                const relayResponse = JSON.parse(
                    axiosResponse.data,
                ) as RelayProxyResponse;

                resolveResult({
                    response: transformRelayResponse(relayResponse),
                    duration: relayResponse.duration,
                });
            })
            .catch((error: AxiosError) => {
                // Cancellation is not an error state.
                if (error.code === 'ERR_CANCELED') {
                    resolveResult(null);

                    return;
                }

                // HTTP error responses (4xx, 5xx) include status and body for debugging.
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

                reject({ message: error.message });
            });
    });
};
