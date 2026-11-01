/**
 * HTTP-related interfaces and types
 */

export type { HttpHeaders, HttpHeadersArray, RequestHeader } from './headers';

export { GeneratorType } from '../generated/global-request-types';

export type { SourceGlobalHeaders } from '../generated/global-request-types';

export type { CookieValue, ResponseCookie } from './cookies';

export type { PendingRequest, Request } from './request';

export { RequestBodyTypeEnum } from './request';

export type {
    ErrorPlainResponse,
    RelayProxyDownload,
    RelayProxyResponse,
    Response,
    ResponseBody,
    ResponseDownload,
} from './response';

export { STATUS } from './status';
