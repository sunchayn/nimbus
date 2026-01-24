import type {
    ErrorPlainResponse,
    PendingRequest,
    Request,
    Response,
} from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';

import type { RequestLog } from '@/interfaces';
import type { RouteDefinition } from '@/interfaces/routes';

/**
 * Selects default payload type from route definition.
 *
 * Routes with schema definitions use JSON payload, others use empty payload.
 */
export function getDefaultPayloadTypeForRoute(
    route: RouteDefinition,
): RequestBodyTypeEnum {
    // If a schema exists, then we can switch directly to JSON body.
    // Otherwise, we fall back to an empty (no payload) body.
    return Object.keys(route.schema.shape.properties ?? {}).length > 0
        ? RequestBodyTypeEnum.JSON
        : RequestBodyTypeEnum.EMPTY;
}

/**
 * Generates a request log entry for successful requests.
 */
export function generateSuccessRequestLog(
    request: PendingRequest,
    duration: number,
    response: Response,
): RequestLog {
    return {
        durationInMs: duration,
        isProcessing: false,
        request: pendingRequestToRequestLogEntry(request),
        response: response,
    };
}

/**
 * Generates a request log entry for failed requests.
 */
export function generateErrorRequestLog(
    request: PendingRequest,
    error: ErrorPlainResponse,
): RequestLog {
    return {
        durationInMs: 0,
        isProcessing: false,
        request: pendingRequestToRequestLogEntry(request),
        error: error,
    };
}

const pendingRequestToRequestLogEntry = function (request: PendingRequest): Request {
    // Extract the memoized body for the current method and payload type
    const methodBody = request.body[request.method] ?? null;
    const currentBody = methodBody ? (methodBody[request.payloadType] ?? null) : null;

    return {
        method: request.method,
        endpoint: request.endpoint,
        headers: [...request.headers],
        body: currentBody,
        queryParameters: [...request.queryParameters],
        payloadType: request.payloadType,
        authorization: { ...request.authorization },
        routeDefinition: { ...request.routeDefinition },
    };
};

/**
 * Creates a timer for tracking request execution duration.
 *
 * Provides real-time updates to the UI during request execution,
 * allowing users to see progress.
 */
export function createRequestTimer(updateCallback: (elapsed: number) => void): {
    stop: () => number;
} {
    const startTimeResult = performance.now();

    // Update every ~86ms for smooth UI updates.
    // This frequency balances smoothness with performance impact
    const sweetSpotRequestTimerIntervalInMilliSeconds = 86;
    let intervalId: number | null = window.setInterval(() => {
        const elapsed = Math.floor(performance.now() - startTimeResult);
        updateCallback(elapsed);
    }, sweetSpotRequestTimerIntervalInMilliSeconds);

    return {
        /**
         * Stops the timer and returns final elapsed time.
         * Cleans up the interval to prevent memory leaks.
         */
        stop: (): number => {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
            }

            return Math.floor(performance.now() - startTimeResult);
        },
    };
}
