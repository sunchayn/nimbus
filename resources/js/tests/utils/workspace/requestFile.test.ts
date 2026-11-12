import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterType } from '@/interfaces/ui';
import type { Tab } from '@/interfaces/ui/tabs';
import {
    fromRequestFile,
    isTabDirty,
    serializeRequestFile,
    toRequestFile,
    UnsupportedWorkspaceRequestError,
} from '@/utils/workspace';
import { describe, expect, it } from 'vitest';

const createRequest = (overrides: Partial<PendingRequest> = {}): PendingRequest => ({
    method: 'POST',
    endpoint: '/api/users',
    headers: [
        {
            type: ParameterType.Text,
            key: 'Accept',
            value: 'application/json',
            enabled: true,
        },
        { type: ParameterType.File, key: 'avatar', value: '', enabled: true },
    ],
    queryParameters: [
        { type: ParameterType.Text, key: 'page', value: '1', enabled: false },
    ],
    body: { POST: { [RequestBodyTypeEnum.JSON]: '{"name":"{{name}}"}' } },
    payloadType: RequestBodyTypeEnum.JSON,
    schema: { shape: {}, extractionErrors: null },
    authorization: { type: AuthorizationType.Bearer, value: '{{token}}' },
    supportedRoutes: [],
    routeDefinition: {
        endpoint: '/api/users',
        method: 'POST',
        shortEndpoint: 'users',
        schema: { shape: {}, extractionErrors: null },
        metadata: { name: 'users.store' },
    },
    ...overrides,
});

describe('requestFile', () => {
    it('builds the payload without resolving variables', () => {
        const payload = toRequestFile(createRequest(), 'Create user');

        expect(payload).toEqual({
            name: 'Create user',
            method: 'POST',
            endpoint: '/api/users',
            route: 'users.store',
            headers: [{ key: 'Accept', value: 'application/json', enabled: true }],
            query: [{ key: 'page', value: '1', enabled: false }],
            body: { type: 'json', content: '{"name":"{{name}}"}' },
            authorization: { type: AuthorizationType.Bearer, value: '{{token}}' },
        });
    });

    it('writes an empty body without content', () => {
        const payload = toRequestFile(
            createRequest({ payloadType: RequestBodyTypeEnum.EMPTY }),
            'List users',
        );

        expect(payload.body).toEqual({ type: 'empty' });
    });

    it('keeps no value for an authorization without one', () => {
        const payload = toRequestFile(
            createRequest({ authorization: { type: AuthorizationType.CurrentUser } }),
            'List users',
        );

        expect(payload.authorization).toEqual({ type: AuthorizationType.CurrentUser });
    });

    it('refuses a form data body', () => {
        expect(() =>
            toRequestFile(
                createRequest({ payloadType: RequestBodyTypeEnum.FORM_DATA }),
                'Upload',
            ),
        ).toThrow(UnsupportedWorkspaceRequestError);
    });

    it('restores the parts of a request from a file', () => {
        const restored = fromRequestFile({
            id: 'request-1234',
            name: 'Create user',
            method: 'post',
            endpoint: '/api/users',
            route: 'users.store',
            headers: [{ key: 'Accept', value: 'application/json', enabled: false }],
            query: [],
            body: { type: 'plain-text', content: 'hello' },
            authorization: { type: AuthorizationType.None },
        });

        expect(restored.method).toBe('POST');
        expect(restored.payloadType).toBe(RequestBodyTypeEnum.PLAIN_TEXT);
        expect(restored.body).toBe('hello');
        expect(restored.headers).toEqual([
            {
                key: 'Accept',
                value: 'application/json',
                enabled: false,
                type: ParameterType.Text,
            },
        ]);
    });

    describe('isTabDirty', () => {
        const createTab = (request: PendingRequest): Tab => ({
            id: 'tab',
            title: 'users',
            method: 'POST',
            request,
            response: null,
            workspace: {
                id: 'request-1234',
                name: 'Create user',
                hash: 'hash',
                saved: serializeRequestFile(
                    toRequestFile(createRequest(), 'Create user'),
                ),
            },
        });

        it('is clean until the request changes', () => {
            const tab = createTab(createRequest());

            expect(isTabDirty(tab)).toBe(false);

            tab.request.endpoint = '/api/other';

            expect(isTabDirty(tab)).toBe(true);
        });

        it('is clean for a tab that is not saved', () => {
            const tab = createTab(createRequest());

            tab.workspace = undefined;

            expect(isTabDirty(tab)).toBe(false);
        });

        it('is dirty when the body cannot be saved', () => {
            const tab = createTab(
                createRequest({ payloadType: RequestBodyTypeEnum.FORM_DATA }),
            );

            expect(isTabDirty(tab)).toBe(true);
        });
    });
});
