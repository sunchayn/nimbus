import { RouteDefinition } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
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
                    'x-name': 'root',
                    'x-required': false,
                    properties: {
                        name: {
                            'x-name': 'name',
                            'x-required': false,
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
                shape: {
                    'x-name': 'root',
                    'x-required': false,
                },
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
                    'x-name': 'root',
                    'x-required': false,
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
                    shape: {
                        'x-name': 'root',
                        'x-required': false,
                    },
                    extractionErrors: null,
                },
            },
        } as PendingRequest;

        /* eslint-disable  @typescript-eslint/no-explicit-any */
        const success = generateSuccessRequestLog(request, 1200, {
            status: 200,
        } as any);
        /* eslint-enable  @typescript-eslint/no-explicit-any */

        expect(success.durationInMs).toBe(1200);
        expect(success.response).toEqual({ status: 200 });

        /* eslint-disable  @typescript-eslint/no-explicit-any */
        const error = generateErrorRequestLog(request, {
            message: 'fail',
        } as any);
        /* eslint-enable  @typescript-eslint/no-explicit-any */

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
