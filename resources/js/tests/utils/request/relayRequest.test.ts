import { AuthorizationType } from '@/interfaces/generated';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterType } from '@/interfaces/ui';
import {
    buildRelayAuthorization,
    buildUrlFromRequest,
    createRelayPayload,
    getMemoizedBody,
    transformRelayResponse,
} from '@/utils/request/relayRequest';
import { describe, expect, it } from 'vitest';
import {
    createMockHeader,
    createMockPendingRequest,
    createMockRelayProxyResponse,
} from '../../_utils/testFactories';

const resolve = (value: string | number | boolean | null | undefined): string =>
    String(value ?? '').replace('{{token}}', 'secret');

describe('relayRequest', () => {
    describe('buildUrlFromRequest', () => {
        it('strips leading slashes, resolves variables and skips disabled parameters', () => {
            // Arrange

            const request = createMockPendingRequest({
                endpoint: '//users/{{token}}',
                queryParameters: [
                    { key: 'a', value: '1', type: ParameterType.Text, enabled: true },
                    { key: 'b', value: '2', type: ParameterType.Text, enabled: false },
                ],
            });

            // Act

            const url = buildUrlFromRequest(request, 'https://api.test', resolve);

            // Assert

            expect(url).toContain('users/secret');
            expect(url).not.toContain('//users');
            expect(url).toContain('a=1');
            expect(url).not.toContain('b=2');
        });
    });

    describe('getMemoizedBody', () => {
        it('resolves the string body for the method and payload type', () => {
            const request = createMockPendingRequest({
                method: 'POST',
                payloadType: RequestBodyTypeEnum.JSON,
                body: { POST: { [RequestBodyTypeEnum.JSON]: '{"t":"{{token}}"}' } },
            });

            expect(getMemoizedBody(request, resolve)).toBe('{"t":"secret"}');
        });

        it('returns FormData untouched', () => {
            const formData = new FormData();
            const request = createMockPendingRequest({
                method: 'POST',
                payloadType: RequestBodyTypeEnum.FORM_DATA,
                body: { POST: { [RequestBodyTypeEnum.FORM_DATA]: formData } },
            });

            expect(getMemoizedBody(request, resolve)).toBe(formData);
        });

        it('returns null when no body exists for the method', () => {
            expect(getMemoizedBody(createMockPendingRequest(), resolve)).toBeNull();
        });
    });

    describe('buildRelayAuthorization', () => {
        it('resolves bearer tokens', () => {
            const result = buildRelayAuthorization(
                { type: AuthorizationType.Bearer, value: '{{token}}' },
                resolve,
            );

            expect(result).toEqual({ type: AuthorizationType.Bearer, value: 'secret' });
        });

        it('resolves basic credentials', () => {
            const result = buildRelayAuthorization(
                {
                    type: AuthorizationType.Basic,
                    value: { username: 'u', password: '{{token}}' },
                },
                resolve,
            );

            expect(result).toEqual({
                type: AuthorizationType.Basic,
                value: { username: 'u', password: 'secret' },
            });
        });

        it('passes other types through', () => {
            const auth = { type: AuthorizationType.None } as const;

            expect(buildRelayAuthorization(auth, resolve)).toBe(auth);
        });
    });

    describe('createRelayPayload', () => {
        it('drops disabled headers and adds the content type header', () => {
            const request = createMockPendingRequest({
                method: 'POST',
                payloadType: RequestBodyTypeEnum.JSON,
                headers: [
                    createMockHeader({
                        key: 'X-A',
                        value: '{{token}}',
                        type: ParameterType.Text,
                    }),
                    createMockHeader({ key: 'X-B', value: 'no', enabled: false }),
                    createMockHeader({ key: '  ', value: 'empty' }),
                ],
            });

            const payload = createRelayPayload(request, 'https://api.test', resolve);

            expect(payload.method).toBe('POST');
            expect(payload.headers).toContainEqual({ key: 'X-A', value: 'secret' });
            expect(payload.headers.map(header => header.key)).not.toContain('X-B');
            expect(
                payload.headers.some(
                    header => header.key.toLowerCase() === 'content-type',
                ),
            ).toBe(true);
        });
    });

    describe('transformRelayResponse', () => {
        it('prefers the content-length header for the size', () => {
            const result = transformRelayResponse(
                createMockRelayProxyResponse({
                    body: 'abc',
                    headers: [{ key: 'Content-Length', value: 99 }],
                }),
            );

            expect(result.sizeInBytes).toBe(99);
        });

        it('falls back to the body length', () => {
            const result = transformRelayResponse(
                createMockRelayProxyResponse({ body: 'abcd', headers: [] }),
            );

            expect(result.sizeInBytes).toBe(4);
        });
    });
});
