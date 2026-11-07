import RequestBuilderOptionsMenu from '@/components/domain/Client/Request/RequestBuilder/RequestBuilderOptionsMenu.vue';
import type { PendingRequest } from '@/interfaces/http';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive, ref } from 'vue';

/*
 * Fixtures.
 */

const mockRequestStore = reactive({
    pendingRequestData: ref<PendingRequest | null>(null),
    updateTransactionMode: vi.fn(),
    updateOptions: vi.fn(),
});

const mockConfigStore = reactive({
    apiUrl: 'http://localhost',
    activeApplication: 'main',
    appBasePath: '/nimbus',
});

const mockHistoryStore = reactive({
    lastLog: null,
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
        useConfigStore: () => mockConfigStore,
        useRequestsHistoryStore: () => mockHistoryStore,
    };
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(RequestBuilderOptionsMenu, {
        ...options,
        global: {
            plugins: [createPinia()],
            stubs: {
                CurlExportDialog: true,
                ShareableLinkDialog: true,
                AppPopover: true,
                AppPopoverContent: true,
                AppPopoverTrigger: true,
            },
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('RequestBuilderOptionsMenu', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        mockRequestStore.pendingRequestData = {
            transactionMode: false,
        } as unknown as PendingRequest;
    });

    it('renders the options button', () => {
        const wrapper = createWrapper();
        expect(wrapper.find('[data-testid="request-options-button"]').exists()).toBe(
            true,
        );
    });

    it('updates transaction mode via computed property', async () => {
        const wrapper = createWrapper();
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const vm = wrapper.vm as any;

        vm.transactionMode = true;

        expect(mockRequestStore.updateTransactionMode).toHaveBeenCalledWith(true);
    });

    describe('request options', () => {
        interface OptionsVm {
            followRedirects: boolean;
            timeout: string | number;
        }

        const optionsVm = () => createWrapper().vm as unknown as OptionsVm;

        it('saves the follow redirects switch through the store', () => {
            optionsVm().followRedirects = false;

            expect(mockRequestStore.updateOptions).toHaveBeenCalledWith({
                followRedirects: false,
            });
        });

        it('saves a timeout in range and ignores one out of range', () => {
            const vm = optionsVm();

            vm.timeout = '12';
            vm.timeout = '0';

            expect(mockRequestStore.updateOptions).toHaveBeenCalledWith({ timeout: 12 });
            expect(mockRequestStore.updateOptions).toHaveBeenCalledTimes(1);
        });
    });
});
