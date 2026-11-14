/**
 * HTTP-related interfaces and types
 */

export type { HttpHeaders, HttpHeadersArray, RequestHeader } from './headers';

export { GeneratorType } from '../generated/global-request-types';

export type { SourceGlobalHeaders } from '../generated/global-request-types';

export type { CookieValue, ResponseCookie } from './cookies';

export type { PendingRequest, Request, RequestOptions } from './request';

export { RequestBodyTypeEnum } from './request';

export type {
    ErrorPlainResponse,
    RelayProxyDownload,
    RelayProxyResponse,
    Response,
    ResponseBody,
    ResponseDownload,
    SentRequest,
} from './response';

export type {
    Insight,
    InsightCacheEntry,
    InsightEventEntry,
    InsightExceptionInfo,
    InsightFrame,
    InsightGate,
    InsightHttpEntry,
    InsightJobEntry,
    InsightLog,
    InsightMailEntry,
    InsightNotificationEntry,
    InsightQuery,
    InsightSandbox,
} from './insight';
export { STATUS } from './status';
