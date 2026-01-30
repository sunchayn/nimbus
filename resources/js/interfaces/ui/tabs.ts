import type { RequestLog } from '../history/logs';
import type { PendingRequest } from '../http/request';

export interface Tab {
    id: string;
    title: string;
    method: string;
    request: PendingRequest;
    response: RequestLog | null;
}
