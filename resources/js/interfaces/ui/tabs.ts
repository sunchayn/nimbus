import type { RequestLog } from '@/interfaces/history/logs';
import type { PendingRequest } from '@/interfaces/http/request';
import type { WorkspaceLink } from '@/interfaces/workspace';

export interface Tab {
    id: string;
    title: string;
    customTitle?: string;
    pinned?: boolean;
    method: string;
    request: PendingRequest;
    response: RequestLog | null;
    /** Set when the tab is saved as a request file in the workspace. */
    workspace?: WorkspaceLink;
}
