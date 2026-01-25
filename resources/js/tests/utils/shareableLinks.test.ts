import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest, Response } from '@/interfaces/http';
import { RequestBodyTypeEnum, STATUS } from '@/interfaces/http';
import { ParameterType } from '@/interfaces/ui';
import { buildShareableUrl, encodeShareablePayload } from '@/utils/shareableLinks';
import { beforeEach, describe, expect, it } from 'vitest';

describe('shareableLinks', () => {
    describe('encodeShareablePayload', () => {
        it('encodes a pending request into a URL-safe string', () => {
            // Arrange

            const pendingRequest: PendingRequest = {
                method: 'POST',
                endpoint: '/api/users',
                headers: [
                    {
                        key: 'Content-Type',
                        value: 'application/json',
                        type: ParameterType.Text,
                        enabled: true,
                    },
                ],
                queryParameters: [
                    {
                        key: 'page',
                        value: '1',
                        type: ParameterType.Text,
                        enabled: true,
                    },
                ],
                body: {},
                payloadType: RequestBodyTypeEnum.JSON,
                schema: { shape: {}, extractionErrors: null },
                authorization: { type: AuthorizationType.None },
                supportedRoutes: [],
                routeDefinition: {
                    endpoint: '/api/users',
                    method: 'POST',
                    schema: { shape: {}, extractionErrors: null },
                    shortEndpoint: '/users',
                },
            };

            // Act

            const encoded = encodeShareablePayload(pendingRequest);

            // Assert

            expect(encoded).toBeDefined();
            expect(typeof encoded).toBe('string');
            expect(encoded.length).toBeGreaterThan(0);
            // Should be URL-safe (no +, /, or = characters)
            expect(encoded).not.toMatch(/[+/=]/);
        });

        it('encodes request with response data', () => {
            // Arrange

            const pendingRequest: PendingRequest = {
                method: 'GET',
                endpoint: '/api/health',
                headers: [],
                queryParameters: [],
                body: {},
                payloadType: RequestBodyTypeEnum.EMPTY,
                schema: { shape: {}, extractionErrors: null },
                authorization: { type: AuthorizationType.None },
                supportedRoutes: [],
                routeDefinition: {
                    endpoint: '/api/health',
                    method: 'GET',
                    schema: { shape: {}, extractionErrors: null },
                    shortEndpoint: '/health',
                },
            };

            const response: Response = {
                status: STATUS.SUCCESS,
                statusCode: 200,
                statusText: 'OK',
                body: '{"status": "healthy"}',
                sizeInBytes: 21,
                headers: [{ key: 'Content-Type', value: 'application/json' }],
                cookies: [],
                timestamp: 1234567890,
            };

            // Act

            const encoded = encodeShareablePayload(pendingRequest, response);

            // Assert

            expect(encoded).toBeDefined();
            expect(encoded.length).toBeGreaterThan(0);
        });
    });

    describe('buildShareableUrl', () => {
        beforeEach(() => {
            // Mock window.location for tests
            Object.defineProperty(globalThis, 'window', {
                value: {
                    location: {
                        origin: 'http://localhost:3000',
                    },
                },
                writable: true,
                configurable: true,
            });
        });

        it('builds a complete URL with share parameter', () => {
            // Arrange

            const basePath = '/nimbus';
            const encodedPayload = 'encodedPayloadString';

            // Act

            const url = buildShareableUrl(basePath, encodedPayload);

            // Assert

            expect(url).toBe('http://localhost:3000/nimbus?share=encodedPayloadString');
        });

        it('handles base path without leading slash', () => {
            // Arrange

            const basePath = 'nimbus';
            const encodedPayload = 'test123';

            // Act

            const url = buildShareableUrl(basePath, encodedPayload);

            // Assert

            expect(url).toBe('http://localhost:3000/nimbus?share=test123');
        });
    });
});
