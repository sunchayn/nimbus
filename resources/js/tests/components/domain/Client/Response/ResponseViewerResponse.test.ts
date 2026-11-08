import ResponseDownload from '@/components/domain/Client/Response/ResponseBody/ResponseDownload.vue';
import ResponseBodyViewer from '@/components/domain/Client/Response/ResponseTools/ResponseBodyViewer.vue';
import ResponseViewerResponse from '@/components/domain/Client/Response/ResponseViewerResponse.vue';
import type { RequestLog } from '@/interfaces';
import { STATUS } from '@/interfaces/http';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

/*
 * Fixtures.
 */

const mockRequestStore = reactive({
    pendingRequestData: null,
});

const mockConfigStore = reactive({
    appBasePath: '/nimbus',
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
        useConfigStore: () => mockConfigStore,
    };
});

const createWrapper = (props = {}): VueWrapper => {
    return mount(ResponseViewerResponse, {
        props: {
            response: {
                request: {
                    method: 'GET',
                    endpoint: '/api/users',
                    headers: [],
                    queryParameters: [],
                    payloadType: 'json',
                    schema: { shape: {}, extractionErrors: null },
                    authorization: { type: 'none', value: null },
                    supportedRoutes: [],
                    routeDefinition: {
                        endpoint: '/api/users',
                        method: 'GET',
                        schema: { shape: {}, extractionErrors: null },
                        shortEndpoint: 'users',
                    },
                },
                response: {
                    status: STATUS.SUCCESS,
                    statusCode: 200,
                    statusText: 'OK',
                    body: JSON.stringify({ id: 1, name: 'John Doe' }),
                    sizeInBytes: 25,
                    headers: [{ key: 'Content-Type', value: 'application/json' }],
                    cookies: [],
                    timestamp: 123456789,
                },
                durationInMs: 15,
                isProcessing: false,
            } as unknown as RequestLog,
            ...props,
        },
        global: {
            plugins: [createPinia()],
            stubs: {
                ResponseBodyViewer: true,
                ResponseDumpAndDie: true,
                ResponseCookies: true,
                ResponseHeaders: true,
                AppScrollArea: { template: '<div><slot /></div>' },
                ResponseDownloadDropdown: true,
            },
        },
    });
};

describe('ResponseViewerResponse', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        global.URL.createObjectURL = vi.fn(() => 'blob:mock-url');
        global.URL.revokeObjectURL = vi.fn();
    });

    it('renders the body viewer for a regular response', () => {
        const wrapper = createWrapper();

        expect(wrapper.findComponent(ResponseBodyViewer).exists()).toBe(true);
        expect(wrapper.findComponent(ResponseDownload).exists()).toBe(false);
    });

    it('renders the download viewer instead of the body for a download response', () => {
        const response = (createWrapper().props() as { response: RequestLog }).response;

        const wrapper = createWrapper({
            response: {
                ...response,
                response: {
                    ...response.response,
                    download: {
                        filename: 'report.pdf',
                        mimeType: 'application/pdf',
                        sizeInBytes: 10,
                    },
                },
            },
        });

        expect(wrapper.findComponent(ResponseDownload).exists()).toBe(true);
        expect(wrapper.findComponent(ResponseBodyViewer).exists()).toBe(false);
    });

    it('leaves the download action to the status row', () => {
        const wrapper = createWrapper();
        const dropdown = wrapper.findComponent({ name: 'ResponseDownloadDropdown' });
        expect(dropdown.exists()).toBe(false);
    });

    describe('insight tab', () => {
        const insight = {
            queries: [
                {
                    sql: 'select 1',
                    bindings: [],
                    time_ms: 1,
                    connection: 'sqlite',
                    offset_ms: 0,
                },
            ],
            logs: [{ level: 'info', message: 'hi', context: {} }],
            gates: [],
            cache: [],
            jobs: [],
            mail: [],
            notifications: [],
            http: [],
            events: [{ name: 'App\\Events\\Foo' }],
            exception: null,
            peak_memory: 0,
            duration_ms: 10,
            dropped: {
                queries: 0,
                logs: 0,
                cache: 0,
                jobs: 0,
                mail: 0,
                notifications: 0,
                http: 0,
                events: 0,
            },
            truncated: false,
            sandbox: {},
        };

        const withInsight = (value: unknown) => {
            const base = (createWrapper().props() as { response: RequestLog }).response;

            return createWrapper({
                response: { ...base, response: { ...base.response, insight: value } },
            });
        };

        it('is hidden when the response has no insight', () => {
            expect(withInsight(null).text()).not.toContain('Insight');
        });

        it('is shown with the count of recorded items', () => {
            const text = withInsight(insight).text();

            expect(text).toContain('Insight');
            expect(text).toContain('3');
        });
    });
});
