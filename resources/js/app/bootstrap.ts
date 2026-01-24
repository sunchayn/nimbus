/*
 * Axios HTTP Client Bootstrap
 *
 * This file sets up Axios as the default HTTP client for the application.
 * Attaches Axios to the global window object for universal access in the app.
 */

import { httpClientConfig } from '@/config';
import type { AxiosResponse, InternalAxiosRequestConfig } from 'axios';
import axios from 'axios';

// Make Axios globally available (legacy compatibility and convenience)
window.axios = axios;

/*
 * Configure Axios defaults:
 * - Set 'X-Requested-With' to identify AJAX requests for backend frameworks.
 * - Enable sending credentials (cookies, auth headers) with cross-site requests.
 * @see https://axios-http.com/docs/req_config
 */
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

type AxiosExtendedConfig = InternalAxiosRequestConfig & {
    startedAt: DOMHighResTimeStamp;
};

/*
 * Set up Axios interceptors for request/response timing and delays.
 *
 * This is application-level configuration that affects all HTTP requests,
 * so it belongs in the bootstrap phase rather than in utility functions.
 */
const setupAxiosInterceptors = (
    minimumDelay: number = httpClientConfig.MINIMUM_DELAY,
): void => {
    // Request interceptor to track request start time
    axios.interceptors.request.use(
        (config: InternalAxiosRequestConfig): AxiosExtendedConfig => ({
            ...config,
            startedAt: performance.now(),
        }),
        error => Promise.reject(error),
    );

    axios.interceptors.response.use(
        async (response: AxiosResponse) => {
            const extendedConfig: AxiosExtendedConfig =
                response.config as AxiosExtendedConfig;

            const duration = Math.floor(performance.now() - extendedConfig.startedAt);

            // Add minimum delay for better UX (prevents flash of loading states)
            const remainder = minimumDelay - duration;
            if (remainder > 0) {
                await new Promise(resolve => setTimeout(resolve, remainder));
            }

            return {
                ...response,
                duration,
            };
        },
        error => {
            if (error.response) {
                const extendedConfig: AxiosExtendedConfig = error.response
                    .config as AxiosExtendedConfig;
                const duration = Math.floor(performance.now() - extendedConfig.startedAt);

                error.response = {
                    ...error.response,
                    duration,
                };
            }

            return Promise.reject(error);
        },
    );
};

// Initialize interceptors
setupAxiosInterceptors();
