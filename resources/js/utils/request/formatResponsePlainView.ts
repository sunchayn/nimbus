import type { Response, SentRequest } from '@/interfaces/http';
import type { ResponseHeader } from '@/interfaces/http/headers';

const formatHeaders = (headers: ResponseHeader[]): string[] =>
    headers.map(header => `${header.key}: ${header.value}`);

const formatSentRequest = (sent: SentRequest | null | undefined): string[] =>
    sent
        ? [
              `${sent.method} ${sent.url} HTTP/${sent.protocolVersion}`,
              ...formatHeaders(sent.headers),
              '',
              sent.body + (sent.bodyTruncated ? '\n[truncated]' : ''),
          ]
        : ['The sent request is not available for this response.'];

/**
 * Formats the requests as sent and the responses received as raw HTTP text.
 * A followed redirect prints one request and response pair per hop, in order.
 */
export const formatResponsePlainView = (response: Response): string => {
    const redirectLines = (response.redirects ?? []).flatMap(hop => [
        '> Request',
        ...formatSentRequest(hop.sent),
        '',
        '< Response',
        `HTTP ${hop.statusCode} ${hop.statusText}`,
        ...formatHeaders(hop.headers),
        '',
        '',
    ]);

    const responseLines = [
        `HTTP ${response.statusCode} ${response.statusText}`,
        ...formatHeaders(response.headers),
        '',
        response.body,
    ];

    return [
        ...redirectLines,
        '> Request',
        ...formatSentRequest(response.sent),
        '',
        '< Response',
        ...responseLines,
    ].join('\n');
};
