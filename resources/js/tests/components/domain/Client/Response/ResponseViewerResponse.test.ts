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
                ResponseBody: true,
                ResponseDumpAndDie: true,
                ResponseCookies: true,
                ResponseHeaders: true,
                AppScrollArea: true,
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

    it('renders the response download dropdown component', () => {
        const wrapper = createWrapper();
        const dropdown = wrapper.findComponent({ name: 'ResponseDownloadDropdown' });
        expect(dropdown.exists()).toBe(true);
    });
});
