import { AuthorizationContract } from "@/interfaces/auth/authorization";
import { RequestLog } from "@/interfaces/history/logs";
import { ParameterContract, ParameterType } from "@/interfaces";
import { STATUS } from "@/interfaces/http/status";
import { ErrorPlainResponse, Response } from "@/interfaces/http/response";
import { RelayProxyResponse } from "@/interfaces/http";
import { PendingRequest, Request, RequestBodyTypeEnum } from "@/interfaces/http/request";
import { RouteDefinition, RoutesGroup } from "@/interfaces/routes/routes";
import { vi } from "vitest";
import { AuthorizationType } from "@/interfaces/generated";

/*
 * Http & Request Factories.
 */
export interface MockRouteOverrides {
    method?: string;
    uri?: string;
    name?: string;
    action?: string;
    middleware?: string[];
}

export const createMockBackendRoute = (overrides: MockRouteOverrides = {}) => ({
    method: 'GET',
    uri: '/api/users',
    name: 'api.users.index',
    action: 'App\\Http\\Controllers\\UserController@index',
    middleware: ['api', 'auth'],
    ...overrides,
});

export const createMockRouteDefinition = (overrides: Partial<RouteDefinition> = {}): RouteDefinition => ({
    method: 'GET',
    endpoint: 'api/users',
    shortEndpoint: 'api/users',
    schema: {
        shape: {},
        extractionErrors: null,
    },
    ...overrides,
});

export const createMockRoutesGroup = (overrides: Partial<RoutesGroup> = {}): RoutesGroup => ({
    resource: 'users',
    routes: [createMockRouteDefinition()],
    ...overrides,
});

export const createMockRequest = (overrides: Partial<Request> = {}): Request => ({
    method: 'GET',
    endpoint: 'api/users',
    headers: [],
    body: null,
    queryParameters: [],
    payloadType: RequestBodyTypeEnum.JSON,
    authorization: { type: 'none' } as AuthorizationContract,
    routeDefinition: createMockRouteDefinition(),
    ...overrides,
});

export const createMockPendingRequest = (overrides: Partial<PendingRequest> = {}): PendingRequest => ({
    method: 'GET',
    endpoint: 'api/users',
    headers: [],
    body: {},
    queryParameters: [],
    payloadType: RequestBodyTypeEnum.JSON,
    schema: {
        shape: {},
        extractionErrors: null,
    },
    supportedRoutes: [],
    routeDefinition: createMockRouteDefinition(),
    isProcessing: false,
    durationInMs: 0,
    wasExecuted: false,
    authorization: {
        type: AuthorizationType.None,
    },
    ...overrides,
});

export interface MockHeaderOverrides {
    key?: string;
    value?: string;
    type?: ParameterType;
    enabled?: boolean;
}

export const createMockHeader = (overrides: MockHeaderOverrides = {}): ParameterContract => ({
    key: 'Content-Type',
    value: 'application/json',
    type: ParameterType.Text,
    enabled: true,
    ...overrides,
});

export const createMockHeaders = (count: number): ParameterContract[] => {
    return Array.from({ length: count }, (_, index) =>
        createMockHeader({
            key: `X-Custom-Header-${index}`,
            value: `value-${index}`,
        }),
    );
};

/*
 * Response Factories.
 */
export interface MockResponseOverrides {
    status?: number;
    statusText?: string;
    headers?: Record<string, string>;
    body?: unknown;
    duration?: number;
}

export const createMockResponse = (overrides: Partial<Response> = {}): Response => ({
    status: STATUS.SUCCESS,
    statusCode: 200,
    statusText: 'OK',
    body: JSON.stringify({ data: [] }),
    sizeInBytes: 100,
    headers: [],
    cookies: [],
    timestamp: Date.now(),
    ...overrides,
});

export const createMockErrorResponse = (overrides: Partial<Response> = {}): Response =>
    createMockResponse({
        status: STATUS.CLIENT_ERROR,
        statusCode: 422,
        statusText: 'Unprocessable Entity',
        body: JSON.stringify({
            message: 'The given data was invalid.',
            errors: {
                email: ['The email field is required.'],
            },
        }),
        ...overrides,
    });

export const createMockErrorPlainResponse = (overrides: Partial<ErrorPlainResponse> = {}): ErrorPlainResponse => ({
    message: 'Internal Server Error',
    ...overrides,
});

export const createMockRelayProxyResponse = (overrides: Partial<RelayProxyResponse> = {}): RelayProxyResponse => ({
    statusCode: 200,
    statusText: 'OK',
    headers: [],
    body: JSON.stringify({ success: true }),
    cookies: [],
    duration: 120,
    timestamp: Date.now(),
    ...overrides,
});

/*
 * Request Log Factories.
 */

export const createMockRequestLog = (overrides: Partial<RequestLog> = {}): RequestLog => ({
    durationInMs: 150,
    isProcessing: false,
    request: createMockRequest(),
    response: createMockResponse(),
    ...overrides,
});

/*
 * Authorization Factories.
 */

export const createMockBearerAuth = (token = 'test-token') => ({
    type: 'bearer' as const,
    value: token,
});

export const createMockBasicAuth = (username = 'user', password = 'pass') => ({
    type: 'basic' as const,
    value: { username, password },
});

/*
 * Utility Functions.
 */

/**
 * Creates a delayed promise for testing async behavior.
 */
export const delay = (ms: number): Promise<void> =>
    new Promise(resolve => setTimeout(resolve, ms));

/**
 * Creates a mock function that resolves after a delay.
 */
export const createDelayedMock = <T>(value: T, delayMs = 100) =>
    vi.fn().mockImplementation(() => delay(delayMs).then(() => value));
