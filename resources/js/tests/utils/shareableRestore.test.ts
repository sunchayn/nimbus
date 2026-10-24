import { STATUS } from '@/interfaces/http';
import type { ShareableLinkPayload } from '@/interfaces/share';
import { ParameterType } from '@/interfaces/ui';
import {
    buildRequestFromPayload,
    createSynthesizedLog,
    mapHeadersToParameterContract,
    mapQueryParametersToParameterContract,
} from '@/utils/shareableRestore';
import { describe, expect, it } from 'vitest';

const createPayload = (
    overrides: Partial<ShareableLinkPayload> = {},
): ShareableLinkPayload => ({
    method: 'POST',
    endpoint: 'api/users',
    headers: [{ key: 'Accept', value: 'application/json' }],
    queryParameters: [{ key: 'page', value: '1' }],
    body: {},
    payloadType: 'json',
    authorization: { type: 'none' },
    ...overrides,
});

describe('shareableRestore', () => {
    describe('mapHeadersToParameterContract', () => {
        it('converts values to enabled text parameters', () => {
            const result = mapHeadersToParameterContract([
                { key: 'X-Count', value: 5 },
                { key: 'X-Empty', value: null },
            ]);

            expect(result).toEqual([
                { key: 'X-Count', value: '5', type: ParameterType.Text, enabled: true },
                { key: 'X-Empty', value: '', type: ParameterType.Text, enabled: true },
            ]);
        });
    });

    describe('mapQueryParametersToParameterContract', () => {
        it('maps the file type and defaults everything else to text', () => {
            const result = mapQueryParametersToParameterContract([
                { key: 'f', value: 'x', type: 'file' },
                { key: 'p', value: 'y' },
            ]);

            expect(result.map(parameter => parameter.type)).toEqual([
                ParameterType.File,
                ParameterType.Text,
            ]);
        });
    });

    describe('buildRequestFromPayload', () => {
        it('builds a request with an empty schema route definition', () => {
            const request = buildRequestFromPayload(createPayload());

            expect(request.method).toBe('POST');
            expect(request.body).toBeNull();
            expect(request.routeDefinition.shortEndpoint).toBe('api/users');
            expect(request.routeDefinition.schema.shape).toEqual({});
        });
    });

    describe('createSynthesizedLog', () => {
        it('marks the log as imported and uses the response duration', () => {
            const log = createSynthesizedLog(
                createPayload({
                    response: {
                        status: STATUS.SUCCESS,
                        statusCode: 200,
                        statusText: 'OK',
                        body: '{}',
                        sizeInBytes: 2,
                        headers: [{ key: 'A', value: 'b' }],
                        cookies: [],
                        timestamp: 1,
                        durationInMs: 42,
                    },
                }),
            );

            expect(log.importedFromShare).toBe(true);
            expect(log.durationInMs).toBe(42);
            expect(log.response?.statusCode).toBe(200);
        });

        it('throws when the payload has no response', () => {
            expect(() => createSynthesizedLog(createPayload())).toThrow();
        });
    });
});
