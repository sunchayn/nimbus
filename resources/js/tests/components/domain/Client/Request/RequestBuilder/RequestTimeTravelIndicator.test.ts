import RequestTimeTravelIndicator from '@/components/domain/Client/Request/RequestBuilder/RequestTimeTravelIndicator.vue';
import TimeTravelDialog from '@/components/domain/Client/Request/RequestBuilder/TimeTravelDialog.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

const store = reactive({
    pendingRequestData: { timeTravel: '' } as { timeTravel?: string } | null,
    updateTimeTravel: vi.fn(),
});

vi.mock('@/stores', () => ({ useRequestStore: () => store }));

const mountIndicator = () =>
    mount(RequestTimeTravelIndicator, {
        attachTo: document.body,
        global: {
            stubs: {
                TimeTravelDialog: true,
            },
        },
    });

const openPopover = async (wrapper: ReturnType<typeof mountIndicator>) => {
    await wrapper.find('[data-testid="time-travel-indicator"]').trigger('click');
    await flushPromises();
};

const inBody = (testId: string) =>
    document.querySelector<HTMLElement>(`[data-testid="${testId}"]`);

describe('RequestTimeTravelIndicator', () => {
    beforeEach(() => {
        store.pendingRequestData = { timeTravel: '' };
        store.updateTimeTravel.mockClear();
        document.body.innerHTML = '';
    });

    it('is hidden when no time is set', () => {
        const wrapper = mountIndicator();

        expect(wrapper.find('[data-testid="time-travel-indicator"]').exists()).toBe(
            false,
        );

        wrapper.unmount();
    });

    it('shows an icon with the time as its title when a time is set', () => {
        store.pendingRequestData = { timeTravel: '+31 days' };

        const wrapper = mountIndicator();

        expect(
            wrapper.find('[data-testid="time-travel-indicator"]').attributes('title'),
        ).toContain('+31 days');

        wrapper.unmount();
    });

    it('clears the time from the popover', async () => {
        store.pendingRequestData = { timeTravel: '+31 days' };

        const wrapper = mountIndicator();
        await openPopover(wrapper);

        inBody('time-travel-indicator-clear')!.click();

        expect(store.updateTimeTravel).toHaveBeenCalledWith('');

        wrapper.unmount();
    });

    it('opens the dialog from the change option', async () => {
        store.pendingRequestData = { timeTravel: '+31 days' };

        const wrapper = mountIndicator();
        await openPopover(wrapper);

        inBody('time-travel-indicator-change')!.click();
        await flushPromises();

        expect(wrapper.findComponent(TimeTravelDialog).props('open')).toBe(true);
        expect(wrapper.findComponent(TimeTravelDialog).props('value')).toBe('+31 days');

        wrapper.unmount();
    });

    it('saves the time chosen in the dialog', () => {
        store.pendingRequestData = { timeTravel: '+31 days' };

        const wrapper = mountIndicator();

        wrapper.findComponent(TimeTravelDialog).vm.$emit('save', '-1 day');

        expect(store.updateTimeTravel).toHaveBeenCalledWith('-1 day');

        wrapper.unmount();
    });
});
