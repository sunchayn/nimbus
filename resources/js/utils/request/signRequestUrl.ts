import type { PendingRequest } from '@/interfaces/http';
import {
    buildUrlFromRequest,
    type ResolveEnvironmentVariables,
} from '@/utils/request/relayRequest';
import axios from 'axios';

interface SignRequestUrlOptions {
    apiUrl: string;
    appBasePath: string;
    resolve: ResolveEnvironmentVariables;
    signal?: AbortSignal;
}

/**
 * Asks the application to sign the URL of the request.
 *
 * Returns a copy of the request that targets the signed URL, with its query parameters
 * already part of the endpoint so they are not added a second time.
 */
export const signRequestUrl = async (
    request: PendingRequest,
    { apiUrl, appBasePath, resolve, signal }: SignRequestUrlOptions,
): Promise<PendingRequest> => {
    const url = buildUrlFromRequest(request, apiUrl, resolve);

    try {
        const response = await axios.post<{ url: string }>(
            appBasePath + '/api/magic/signed-url',
            { method: request.method, url },
            { signal },
        );

        const signed = new URL(response.data.url);

        return {
            ...request,
            endpoint: signed.pathname.replace(/^\/+/, '') + signed.search,
            queryParameters: [],
        };
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.data?.message) {
            throw new Error(error.response.data.message);
        }

        throw error;
    }
};
