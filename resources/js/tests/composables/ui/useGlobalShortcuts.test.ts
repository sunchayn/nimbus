import { useCommandPalette } from '@/composables/ui/useCommandPalette';
import { useGlobalShortcuts } from '@/composables/ui/useGlobalShortcuts';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, reactive } from 'vue';

/*
 * Fixtures.
 */

const tabsStore = reactive({
    tabs: [{ id: 'a' }, { id: 'b' }],
    setActiveTab: vi.fn(),
});

vi.mock('@/stores', () => ({
    useTabsStore: () => tabsStore,
}));

let isMain = true;

const mountShortcuts = () =>
    mount(
        defineComponent({
            setup() {
                useGlobalShortcuts(() => isMain);

                return () => null;
            },
        }),
    );

const press = (init: KeyboardEventInit): KeyboardEvent => {
    const event = new KeyboardEvent('keydown', {
        bubbles: true,
        cancelable: true,
        ...init,
    });

    window.dispatchEvent(event);

    return event;
};

describe('useGlobalShortcuts', () => {
    beforeEach(() => {
        isMain = true;
        vi.clearAllMocks();
        useCommandPalette().closePalette();
        useCommandPalette().isHelpOpen.value = false;
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('toggles the palette with Mod+K', () => {
        // Arrange

        const wrapper = mountShortcuts();
        const { isPaletteOpen } = useCommandPalette();

        // Act

        press({ key: 'k', ctrlKey: true });

        // Assert

        expect(isPaletteOpen.value).toBe(true);

        press({ key: 'k', metaKey: true });

        expect(isPaletteOpen.value).toBe(false);
        wrapper.unmount();
    });

    it('switches tabs with Alt and a digit', () => {
        // Arrange

        const wrapper = mountShortcuts();

        // Act

        press({ key: '¡', code: 'Digit2', altKey: true });
        press({ key: '£', code: 'Digit3', altKey: true });

        // Assert

        expect(tabsStore.setActiveTab).toHaveBeenCalledTimes(1);
        expect(tabsStore.setActiveTab).toHaveBeenCalledWith('b');
        wrapper.unmount();
    });

    it('clicks the send button with Mod+Enter', () => {
        // Arrange

        const wrapper = mountShortcuts();
        const send = document.createElement('button');
        const onClick = vi.fn();

        send.dataset.shortcut = 'send';
        send.addEventListener('click', onClick);
        document.body.appendChild(send);

        // Act

        press({ key: 'Enter', ctrlKey: true });

        // Assert

        expect(onClick).toHaveBeenCalledTimes(1);
        wrapper.unmount();
    });

    it('does not send twice when the event was already handled', () => {
        // Arrange

        const wrapper = mountShortcuts();
        const send = document.createElement('button');
        const onClick = vi.fn();

        send.dataset.shortcut = 'send';
        send.addEventListener('click', onClick);
        document.body.appendChild(send);

        const input = document.createElement('input');

        input.addEventListener('keydown', event => event.preventDefault());
        document.body.appendChild(input);

        // Act

        input.dispatchEvent(
            new KeyboardEvent('keydown', {
                key: 'Enter',
                ctrlKey: true,
                bubbles: true,
                cancelable: true,
            }),
        );

        // Assert

        expect(onClick).not.toHaveBeenCalled();
        wrapper.unmount();
    });

    it('focuses the endpoint with Mod+L', () => {
        // Arrange

        const wrapper = mountShortcuts();
        const input = document.createElement('input');

        input.dataset.testid = 'endpoint-input';
        document.body.appendChild(input);

        // Act

        press({ key: 'l', ctrlKey: true });

        // Assert

        expect(document.activeElement).toBe(input);
        wrapper.unmount();
    });

    it('ignores the page shortcuts outside the main page', () => {
        // Arrange

        isMain = false;

        const wrapper = mountShortcuts();
        const input = document.createElement('input');

        input.dataset.testid = 'endpoint-input';
        document.body.appendChild(input);

        // Act

        press({ key: 'l', ctrlKey: true });
        press({ key: 'k', ctrlKey: true });

        // Assert

        expect(document.activeElement).not.toBe(input);
        expect(useCommandPalette().isPaletteOpen.value).toBe(true);
        wrapper.unmount();
    });

    it('opens the help overlay on question mark unless typing', () => {
        // Arrange

        const wrapper = mountShortcuts();
        const { isHelpOpen } = useCommandPalette();
        const input = document.createElement('input');

        document.body.appendChild(input);

        // Act

        input.dispatchEvent(
            new KeyboardEvent('keydown', { key: '?', bubbles: true, cancelable: true }),
        );

        // Assert

        expect(isHelpOpen.value).toBe(false);

        press({ key: '?' });

        expect(isHelpOpen.value).toBe(true);
        wrapper.unmount();
    });
});
