import type {
    ErrorPlainResponse,
    PendingRequest,
    Request,
    Response,
} from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';

import type { RequestLog } from '@/interfaces';
import type { RouteDefinition } from '@/interfaces/routes';
import type { JSONSchema7Definition } from 'json-schema';

/**
 * Selects default payload type from route definition.
 * Routes that expect a file upload use form data body type.
 * Other routes with a schema use JSON, and the rest use an empty payload.
 */
export function getDefaultPayloadTypeForRoute(
    route: RouteDefinition,
): RequestBodyTypeEnum {
    // A file can only be sent as multipart, so form data is the natural body.
    if (schemaExpectsFile(route.schema.shape)) {
        return RequestBodyTypeEnum.FORM_DATA;
    }

    // If a schema exists, then we can switch directly to JSON body.
    // Otherwise, we fall back to an empty (no payload) body.
    return Object.keys(route.schema.shape.properties ?? {}).length > 0
        ? RequestBodyTypeEnum.JSON
        : RequestBodyTypeEnum.EMPTY;
}

/**
 * Whether any property of the schema, at any depth, is a file upload.
 */
export function schemaExpectsFile(schema: JSONSchema7Definition | undefined): boolean {
    if (typeof schema !== 'object') {
        return false;
    }

    if (schema.format === 'binary') {
        return true;
    }

    const children = [
        ...Object.values(schema.properties ?? {}),
        ...(Array.isArray(schema.items) ? schema.items : [schema.items]),
    ];

    return children.some(schemaExpectsFile);
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
    const methodBody = request.body[request.method] ?? null;
    const currentBody = methodBody ? (methodBody[request.payloadType] ?? null) : null;

    return {
        method: request.method,
        endpoint: request.endpoint,
        headers: request.headers.map(header => ({ ...header })),
        body: currentBody,
        queryParameters: request.queryParameters.map(param => ({ ...param })),
        payloadType: request.payloadType,
        authorization: { ...request.authorization },
        routeDefinition: { ...request.routeDefinition },
        transactionMode: request.transactionMode,
        options: request.options ? { ...request.options } : undefined,
        timeTravel: request.timeTravel,
        sandbox: request.sandbox ? [...request.sandbox] : [],
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
