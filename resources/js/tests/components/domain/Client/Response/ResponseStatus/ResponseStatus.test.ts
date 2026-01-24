import ResponseStatus from '@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue';
import type { RequestLog } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import {
    type PendingRequest,
    type Request,
    RequestBodyTypeEnum,
    STATUS,
} from '@/interfaces/http';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

/*
 * Fixtures.
 */

const mockRequestStore = reactive({
    pendingRequestData: null as PendingRequest | null,
    cancelCurrentRequest: vi.fn(),
    restoreFromHistory: vi.fn(),
});

const mockRequestsHistoryStore = reactive({
    lastLog: null as RequestLog | null,
    allLogs: [] as RequestLog[],
    setActiveLog: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
        useRequestsHistoryStore: () => mockRequestsHistoryStore,
    };
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(ResponseStatus, {
        ...options,
        global: {
            plugins: [createPinia()],
            stubs: {
                RequestHistory: true,
            },
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('ResponseStatus', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        mockRequestStore.pendingRequestData = null;
        mockRequestsHistoryStore.lastLog = null;
        mockRequestsHistoryStore.allLogs = [];
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('shows pending status and cancel option while processing', async () => {
            // Arrange

            mockRequestStore.pendingRequestData = {
                isProcessing: true,
                durationInMs: 1234,
            } as unknown as PendingRequest;
            const wrapper = createWrapper();

            // Assert

            expect(wrapper.find('[data-testid="response-badge"]').exists()).toBe(false);
            expect(
                wrapper.find('[data-testid="response-status-indicator"]').exists(),
            ).toBe(true);
            expect(wrapper.find('button').text()).toContain('Cancel');
        });

        it('shows empty status when nothing processed yet', async () => {
            // Arrange

            mockRequestStore.pendingRequestData = {
                isProcessing: false,
                durationInMs: 0,
                wasExecuted: false,
            } as unknown as PendingRequest;

            const wrapper = createWrapper();

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="response-status-text"]').text()).toBe(
                String(STATUS.EMPTY),
            );
        });

        it('derives status details from last successful log', async () => {
            // Arrange

            mockRequestStore.pendingRequestData = {
                isProcessing: false,
                wasExecuted: true,
            } as unknown as PendingRequest;

            const mockRequest: Request = {
                method: 'GET',
                endpoint: '/api/test',
                headers: [],
                queryParameters: [],
                body: null,
                payloadType: RequestBodyTypeEnum.EMPTY,
                authorization: { type: AuthorizationType.None },
                routeDefinition: {
                    method: 'GET',
                    endpoint: '/api/test',
                    shortEndpoint: '/api/test',
                    schema: { shape: {}, extractionErrors: null },
                },
            };

            mockRequestsHistoryStore.lastLog = {
                durationInMs: 3000,
                isProcessing: false,
                request: mockRequest,
                response: {
                    status: STATUS.SUCCESS,
                    statusCode: 201,
                    statusText: 'Created',
                    sizeInBytes: 4096,
                    timestamp: Math.floor(Date.now() / 1000),
                    body: '',
                    headers: [],
                    cookies: [],
                },
            };

            const wrapper = createWrapper();

            // Act

            await nextTick();

            // Assert

            expect(
                wrapper.find('[data-testid="response-status-badge"]').text(),
            ).toContain('201 - Created');
            expect(wrapper.find('[data-testid="response-status-size"]').text()).toBe(
                '4.1kB',
            );
            expect(wrapper.find('[data-testid="response-status-duration"]').text()).toBe(
                '3.00s',
            );
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('cancels request when cancel button clicked', async () => {
            // Arrange

            mockRequestStore.pendingRequestData = {
                isProcessing: true,
                durationInMs: 0,
            } as unknown as PendingRequest;
            const wrapper = createWrapper();

            // Act

            await nextTick();
            await wrapper.find('button').trigger('click');

            // Assert

            expect(mockRequestStore.cancelCurrentRequest).toHaveBeenCalled();
        });
    });
});
