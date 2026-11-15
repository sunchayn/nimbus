import { formatResponsePlainView } from '@/utils/request/formatResponsePlainView';
import { describe, expect, it } from 'vitest';
import { createMockResponse } from '../../_utils/testFactories';

describe('formatResponsePlainView', () => {
    it('prints the request as sent followed by the response', () => {
        const text = formatResponsePlainView(
            createMockResponse({
                statusCode: 201,
                statusText: 'Created',
                body: '{"id":1}',
                headers: [{ key: 'X-A', value: 'b' }],
                sent: {
                    method: 'POST',
                    url: 'https://a.test/users?page=2',
                    protocolVersion: '1.1',
                    headers: [{ key: 'Accept', value: 'application/json' }],
                    body: '{"name":"Ada"}',
                    bodyTruncated: false,
                },
            }),
        );

        expect(text).toBe(
            [
                '> Request',
                'POST https://a.test/users?page=2 HTTP/1.1',
                'Accept: application/json',
                '',
                '{"name":"Ada"}',
                '',
                '< Response',
                'HTTP 201 Created',
                'X-A: b',
                '',
                '{"id":1}',
            ].join('\n'),
        );
    });

    it('marks a truncated body', () => {
        const text = formatResponsePlainView(
            createMockResponse({
                sent: {
                    method: 'GET',
                    url: 'https://a.test',
                    protocolVersion: '1.1',
                    headers: [],
                    body: 'abc',
                    bodyTruncated: true,
                },
            }),
        );

        expect(text).toContain('abc\n[truncated]');
    });

    it('says so when the sent request is missing', () => {
        const text = formatResponsePlainView(createMockResponse({ sent: null }));

        expect(text).toContain('The sent request is not available');
    });

    it('prints every redirect hop before the final response', () => {
        const text = formatResponsePlainView(
            createMockResponse({
                statusCode: 200,
                statusText: 'OK',
                body: 'done',
                headers: [],
                sent: {
                    method: 'GET',
                    url: 'https://a.test/final',
                    protocolVersion: '1.1',
                    headers: [],
                    body: '',
                    bodyTruncated: false,
                },
                redirects: [
                    {
                        sent: {
                            method: 'GET',
                            url: 'https://a.test/start',
                            protocolVersion: '1.1',
                            headers: [],
                            body: '',
                            bodyTruncated: false,
                        },
                        statusCode: 302,
                        statusText: 'Found',
                        headers: [{ key: 'Location', value: 'https://a.test/final' }],
                    },
                ],
            }),
        );

        expect(text).toBe(
            [
                '> Request',
                'GET https://a.test/start HTTP/1.1',
                '',
                '',
                '',
                '< Response',
                'HTTP 302 Found',
                'Location: https://a.test/final',
                '',
                '',
                '> Request',
                'GET https://a.test/final HTTP/1.1',
                '',
                '',
                '',
                '< Response',
                'HTTP 200 OK',
                '',
                'done',
            ].join('\n'),
        );
    });
});
