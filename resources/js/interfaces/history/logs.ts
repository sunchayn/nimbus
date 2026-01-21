import { ErrorPlainResponse, Request, Response } from '@/interfaces/http';
import { Ref } from 'vue';

export interface RequestLog {
    durationInMs: number;
    isProcessing: boolean;
    request: Request;
    response?: Response;
    error?: ErrorPlainResponse;
    importedFromShare?: boolean;
}

export type RequestLogRef = Ref<RequestLog | null>;
