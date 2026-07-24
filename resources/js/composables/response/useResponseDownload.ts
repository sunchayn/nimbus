import type { RequestLog } from '@/interfaces/history/logs';
import type { Request, Response } from '@/interfaces/http';
import { useConfigStore } from '@/stores';
import { getMimeTypeInfo } from '@/utils/http';
import { triggerDownload } from '@/utils/ui/download';
import axios from 'axios';
import { type ComputedRef, type Ref, computed, ref, watch } from 'vue';

export interface UseResponseDownloadResult {
    isResolvingShape: Ref<boolean>;
    resolvedShape: Ref<Record<string, unknown> | null>;
    isJsonResponse: ComputedRef<boolean>;
    downloadRawResponse: () => void;
    downloadResponseShape: () => Promise<void>;
}

/**
 * Extracts the content-type header value from a Response object.
 */
function extractContentType(response?: Response): string {
    if (!response?.headers) {
        return '';
    }

    const header = response.headers.find(h => h.key.toLowerCase() === 'content-type');

    return typeof header?.value === 'string' ? header.value : '';
}

/**
 * Generates a clean download filename slug based on route metadata or request endpoint.
 */
function getDownloadSlug(request?: Request): string {
    const routeName = request?.routeDefinition?.metadata?.name;

    if (typeof routeName === 'string' && routeName.trim() !== '') {
        return routeName.trim();
    }

    const rawPath = request?.endpoint ?? 'response';
    const cleanPath = rawPath.replace(/^\/+|\/+$/g, '');

    if (!cleanPath) {
        return 'response';
    }

    return cleanPath.replace(/\//g, '.');
}

/**
 * Composable for managing response download logic.
 */
function useResponseDownload(
    response:
        | Ref<RequestLog | null | undefined>
        | ComputedRef<RequestLog | null | undefined>,
): UseResponseDownloadResult {
    const configStore = useConfigStore();

    const isResolvingShape = ref(false);
    const resolvedShape = ref<Record<string, unknown> | null>(null);

    const isJsonResponse = computed(() => {
        const contentType = extractContentType(response.value?.response);

        return contentType.toLowerCase().includes('application/json');
    });

    const fetchJsonResponse = async () => {
        if (!isJsonResponse.value || resolvedShape.value || isResolvingShape.value) {
            return;
        }

        const log = response.value;
        if (!log?.response) {
            return;
        }

        isResolvingShape.value = true;

        try {
            const payload = {
                method: log.request.method,
                endpoint: log.request.endpoint,
                response_body: log.response.body,
            };

            const res = await axios.post(
                configStore.appBasePath + '/api/responses/shape',
                payload,
            );

            resolvedShape.value = res.data.shape;
        } catch (err) {
            console.error('Failed to resolve response shape:', err);
        } finally {
            isResolvingShape.value = false;
        }
    };

    const downloadRawResponse = () => {
        const log = response.value;
        if (!log?.response) {
            return;
        }

        const contentType = extractContentType(log.response);
        const { extension, mimeType } = getMimeTypeInfo(contentType || 'unknown');

        const slug = getDownloadSlug(log.request);
        const filename = `${slug}-response.${extension}`;

        const blob = new Blob([log.response.body], { type: mimeType });
        triggerDownload(blob, filename);
    };

    const downloadResponseShape = async () => {
        if (!isJsonResponse.value) {
            return;
        }

        if (!resolvedShape.value) {
            await fetchJsonResponse();
        }

        if (!resolvedShape.value) {
            return;
        }

        const slug = getDownloadSlug(response.value?.request);
        const filename = `${slug}.schema.json`;

        const blob = new Blob([JSON.stringify(resolvedShape.value, null, 2)], {
            type: 'application/json',
        });

        triggerDownload(blob, filename);
    };

    watch(response, () => {
        resolvedShape.value = null;
    });

    return {
        isResolvingShape,
        resolvedShape,
        isJsonResponse,
        downloadRawResponse,
        downloadResponseShape,
    };
}

export default useResponseDownload;
