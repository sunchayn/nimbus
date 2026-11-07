import type { Response } from '@/interfaces/http';

/**
 * Formats the request as sent and the response received as raw HTTP text.
 */
export const formatResponsePlainView = (response: Response): string => {
    const sent = response.sent;

    const requestLines = sent
        ? [
              `${sent.method} ${sent.url} HTTP/${sent.protocolVersion}`,
              ...sent.headers.map(header => `${header.key}: ${header.value}`),
              '',
              sent.body + (sent.bodyTruncated ? '\n[truncated]' : ''),
          ]
        : ['The sent request is not available for this response.'];

    const responseLines = [
        `HTTP ${response.statusCode} ${response.statusText}`,
        ...response.headers.map(header => `${header.key}: ${header.value}`),
        '',
        response.body,
    ];

    return ['> Request', ...requestLines, '', '< Response', ...responseLines].join('\n');
};
