export interface RequestHeader {
    key: string;
    value: string | number | boolean | null;
}

export interface ResponseHeader {
    key: string;
    value: string | number | boolean | null;
}

export interface HttpHeaders {
    key: string;
    value: string | number | boolean | null;
}

export type HttpHeadersArray = HttpHeaders[];
