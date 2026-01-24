import type { RouteDefinition } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import type { ErrorPlainResponse, PendingRequest, Response } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import {
    createRequestTimer,
    generateErrorRequestLog,
    generateSuccessRequestLog,
    getDefaultPayloadTypeForRoute,
} from '@/utils/request';
import { describe, expect, it, vi } from 'vitest';

describe('request-utils', () => {
    it('selects JSON payload when schema has properties', () => {
        const type = getDefaultPayloadTypeForRoute({
            method: 'POST',
            endpoint: 'users',
            shortEndpoint: 'users',
            schema: {
                shape: {
                    properties: {
                        name: {
                            type: 'string',
                        },
                    },
                },
                extractionErrors: null,
            },
        } as RouteDefinition);

        expect(type).toBe(RequestBodyTypeEnum.JSON);
    });

    it('selects empty payload when schema has no properties', () => {
        const type = getDefaultPayloadTypeForRoute({
            method: 'GET',
            endpoint: 'users',
            shortEndpoint: 'users',
            schema: {
                shape: {},
                extractionErrors: null,
            },
        } as RouteDefinition);

        expect(type).toBe(RequestBodyTypeEnum.EMPTY);
    });

    it('builds success and error request logs', () => {
        const request = {
            method: 'GET',
            endpoint: 'users',
            headers: [],
            queryParameters: [],
            payloadType: RequestBodyTypeEnum.EMPTY,
            body: {},
            schema: {
                shape: {
                    properties: {},
                },
                extractionErrors: null,
            },
            authorization: {
                type: AuthorizationType.None,
            },
            supportedRoutes: [],
            routeDefinition: {
                method: 'GET',
                endpoint: 'users',
                shortEndpoint: 'users',
                schema: {
                    shape: {},
                    extractionErrors: null,
                },
            },
        } as PendingRequest;

        const success = generateSuccessRequestLog(request, 1200, {
            status: 200,
        } as unknown as Response);

        expect(success.durationInMs).toBe(1200);
        expect(success.response).toEqual({ status: 200 });

        const error = generateErrorRequestLog(request, {
            message: 'fail',
        } as unknown as ErrorPlainResponse);

        expect(error.error).toEqual({ message: 'fail' });
    });

    it('tracks elapsed time with createRequestTimer', () => {
        vi.useFakeTimers();
        const callback = vi.fn();
        const timer = createRequestTimer(callback);

        vi.advanceTimersByTime(100);
        expect(callback).toHaveBeenCalled();

        const elapsed = timer.stop();
        expect(elapsed).toBeGreaterThanOrEqual(100);
        vi.useRealTimers();
    });
});
