import useResponseDownload from '@/composables/response/useResponseDownload';
import type { RequestLog } from '@/interfaces/history/logs';
import { triggerDownload } from '@/utils/ui/download';
import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';

vi.mock('axios');
vi.mock('@/utils/ui/download', () => ({
    triggerDownload: vi.fn(),
}));

const mockConfigStore = {
    appBasePath: '/nimbus',
};

vi.mock('@/stores', () => ({
    useConfigStore: () => mockConfigStore,
}));

describe('useResponseDownload', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    const createMockResponseLog = (
        endpoint = '/api/users',
        contentType = 'application/json',
        body = '{"ok": true}',
    ): RequestLog => {
        return {
            request: {
                method: 'GET',
                endpoint,
                headers: [],
                queryParameters: [],
                payloadType: 'json',
            },
            response: {
                status: 'success',
                statusCode: 200,
                statusText: 'OK',
                body,
                sizeInBytes: 12,
                headers: [{ key: 'Content-Type', value: contentType }],
                cookies: [],
                timestamp: 123456,
            },
        } as unknown as RequestLog;
    };

    it('initializes with default state', () => {
        const responseRef = ref<RequestLog | null>(null);
        const { isResolvingShape, resolvedShape, isJsonResponse } =
            useResponseDownload(responseRef);

        expect(isResolvingShape.value).toBe(false);
        expect(resolvedShape.value).toBe(null);
        expect(isJsonResponse.value).toBe(false);
    });

    it('identifies json responses correctly', () => {
        const jsonLog = createMockResponseLog(
            '/api/users',
            'application/json; charset=utf-8',
        );
        const nonJsonLog = createMockResponseLog('/api/users', 'text/html');

        const responseRef = ref<RequestLog | null>(jsonLog);
        const { isJsonResponse } = useResponseDownload(responseRef);

        expect(isJsonResponse.value).toBe(true);

        responseRef.value = nonJsonLog;
        expect(isJsonResponse.value).toBe(false);
    });

    it('resets resolved shape when response reference changes', async () => {
        const responseRef = ref<RequestLog | null>(createMockResponseLog());
        const { resolvedShape, downloadResponseShape } = useResponseDownload(responseRef);

        vi.mocked(axios.post).mockResolvedValueOnce({
            data: { shape: { type: 'object' } },
        });

        await downloadResponseShape();
        expect(resolvedShape.value).toEqual({ type: 'object' });

        // Update response reference
        responseRef.value = createMockResponseLog('/api/other');
        await vi.waitFor(() => {
            expect(resolvedShape.value).toBe(null);
        });
    });

    it('downloads raw response with correct extension, route name or full URI slug', () => {
        const log = createMockResponseLog(
            '/api/users/profile',
            'text/html',
            '<h1>Hello</h1>',
        );
        const responseRef = computed(() => log);
        const { downloadRawResponse } = useResponseDownload(responseRef);

        downloadRawResponse();

        expect(triggerDownload).toHaveBeenCalledTimes(1);
        const [blob, filename] = vi.mocked(triggerDownload).mock.calls[0];
        expect(filename).toBe('api.users.profile-response.html');
        expect(blob.type).toBe('text/html');
    });

    it('downloads shape using route name or sanitized full URI slug and sets resolving flag', async () => {
        const log = createMockResponseLog('/api/users');
        const responseRef = computed(() => log);
        const { downloadResponseShape, isResolvingShape } =
            useResponseDownload(responseRef);

        vi.mocked(axios.post).mockImplementationOnce(async () => {
            expect(isResolvingShape.value).toBe(true);

            return { data: { shape: { type: 'object' } } };
        });

        await downloadResponseShape();

        expect(isResolvingShape.value).toBe(false);
        expect(triggerDownload).toHaveBeenCalledTimes(1);
        const [blob, filename] = vi.mocked(triggerDownload).mock.calls[0];
        expect(filename).toBe('api.users.schema.json');
        expect(blob.type).toBe('application/json');
    });

    it('uses route name metadata for download filename if available', async () => {
        const log = createMockResponseLog('/api/users');
        log.request.routeDefinition = {
            endpoint: '/api/users',
            method: 'GET',
            shortEndpoint: 'users',
            schema: { shape: {}, extractionErrors: null },
            metadata: { name: 'users.index' },
        };

        const responseRef = computed(() => log);
        const { downloadResponseShape } = useResponseDownload(responseRef);

        vi.mocked(axios.post).mockResolvedValueOnce({
            data: { shape: { type: 'object' } },
        });

        await downloadResponseShape();

        expect(triggerDownload).toHaveBeenCalledTimes(1);
        const [, filename] = vi.mocked(triggerDownload).mock.calls[0];
        expect(filename).toBe('users.index.schema.json');
    });
});
