import type { RequestLog } from '@/interfaces/history/logs';
import type { PendingRequest } from '@/interfaces/http/request';

export interface Tab {
    id: string;
    title: string;
    method: string;
    request: PendingRequest;
    response: RequestLog | null;
}
