import RequestBuilderOptionsMenu from '@/components/domain/Client/Request/RequestBuilder/RequestBuilderOptionsMenu.vue';
import TimeTravelDialog from '@/components/domain/Client/Request/RequestBuilder/TimeTravelDialog.vue';
import type { PendingRequest } from '@/interfaces/http';
import type { VueWrapper } from '@vue/test-utils';
import { flushPromises, mount } from '@vue/test-utils';
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
    updateSignUrl: vi.fn(),
    updateTimeTravel: vi.fn(),
    updateSandbox: vi.fn(),
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
                CodeExportDialog: true,
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

    it('adds and removes sandbox options', () => {
        mockRequestStore.pendingRequestData = {
            sandbox: ['mail'],
        } as unknown as PendingRequest;

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const vm = createWrapper().vm as any;

        vm.toggleSandbox('queue', true);
        expect(mockRequestStore.updateSandbox).toHaveBeenLastCalledWith([
            'mail',
            'queue',
        ]);

        vm.toggleSandbox('mail', false);
        expect(mockRequestStore.updateSandbox).toHaveBeenLastCalledWith([]);
    });

    it('lists the sandbox options in a sub-menu', async () => {
        // Arrange

        mockRequestStore.pendingRequestData = {
            sandbox: ['mail'],
        } as unknown as PendingRequest;
        const wrapper = createWrapper({ attachTo: document.body });

        // Act

        await wrapper
            .find('[data-testid="request-options-button"]')
            .trigger('keydown', { key: 'ArrowDown' });
        await flushPromises();
        const trigger = document.querySelector<HTMLElement>(
            '[data-testid="sandbox-option"]',
        );
        trigger
            ?.closest<HTMLElement>('[role="menuitem"]')
            ?.dispatchEvent(
                new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }),
            );
        await flushPromises();

        // Assert

        const checkboxes = document.querySelectorAll('[role="menuitemcheckbox"]');
        expect(checkboxes).toHaveLength(4);
        expect(checkboxes[0].getAttribute('aria-checked')).toBe('true');
        expect(checkboxes[1].getAttribute('aria-checked')).toBe('false');
        expect([...checkboxes].map(item => item.textContent?.trim())).toEqual([
            'Mail',
            'Notifications',
            'Queued jobs',
            'Events',
        ]);
        expect(
            document.querySelector('[data-testid="sandbox-intro"]')?.textContent,
        ).toContain('Pick side effects to fake');
        // The help for Events is a popover, and the other options have none.
        expect(checkboxes[3].querySelector('app-popover-stub')).not.toBeNull();
        expect(checkboxes[0].querySelector('app-popover-stub')).toBeNull();

        wrapper.unmount();
    });

    it('updates the sign option via computed property', () => {
        const wrapper = createWrapper();
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const vm = wrapper.vm as any;

        vm.signUrl = true;

        expect(mockRequestStore.updateSignUrl).toHaveBeenCalledWith(true);
    });

    it('exposes the sign option only for a signed route', () => {
        const signedMiddleware = 'Illuminate\\Routing\\Middleware\\ValidateSignature';

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const vm = () => createWrapper().vm as any;

        expect(vm().canSignUrl).toBe(false);

        mockRequestStore.pendingRequestData = {
            routeDefinition: { metadata: { middleware: [signedMiddleware] } },
        } as unknown as PendingRequest;

        expect(vm().canSignUrl).toBe(true);
    });

    it('saves the time chosen in the time travel dialog', () => {
        const wrapper = createWrapper();

        wrapper.findComponent(TimeTravelDialog).vm.$emit('save', '+31 days');

        expect(mockRequestStore.updateTimeTravel).toHaveBeenCalledWith('+31 days');
    });

    it('opens the time travel dialog from the menu item', async () => {
        // Arrange

        const wrapper = createWrapper({ attachTo: document.body });
        await wrapper
            .find('[data-testid="request-options-button"]')
            .trigger('keydown', { key: 'ArrowDown' });
        await flushPromises();

        // Act

        document
            .querySelector<HTMLElement>('[data-testid="time-travel-option"]')!
            .click();
        await flushPromises();

        // Assert

        expect(wrapper.findComponent(TimeTravelDialog).props('open')).toBe(true);

        wrapper.unmount();
    });
});
