import type { HttpHeadersArray, ResponseCookie } from '@/interfaces/http';
import type { ResponseHeader } from '@/interfaces/http/headers';
import type { STATUS } from '@/interfaces/http/status';

export interface ResponseDownload {
    filename: string;
    mimeType: string;
    sizeInBytes: number;
}

export interface RelayProxyDownload extends ResponseDownload {
    content: string;
}

export interface SentRequest {
    method: string;
    url: string;
    protocolVersion: string;
    headers: { key: string; value: string }[];
    body: string;
    bodyTruncated: boolean;
}

export interface Response {
    status: STATUS;
    statusCode: number;
    statusText: string;
    body: string;
    /** Set when the stored copy of the body was cut to fit the browser storage. */
    bodyTruncated?: boolean;
    sizeInBytes: number;
    headers: ResponseHeader[];
    cookies: ResponseCookie[];
    timestamp: number;
    download?: ResponseDownload | null;
    sent?: SentRequest | null;
}

export interface ErrorPlainResponse {
    message: string;
    body?: string;
    status?: number;
}

export interface RelayProxyResponse {
    statusCode: number;
    statusText: string;
    body: string;
    headers: HttpHeadersArray;
    cookies: ResponseCookie[];
    duration: number;
    timestamp: number;
    download?: RelayProxyDownload | null;
    sent?: SentRequest | null;
}

export interface ResponseBody {
    content: string;
    contentType: string;
    isJson: boolean;
    parsedJson: Record<string, unknown> | null;
}
