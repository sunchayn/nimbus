import { useCommandPalette } from '@/composables/ui/useCommandPalette';
import { useTabsStore } from '@/stores';
import { useEventListener } from '@vueuse/core';

const isTyping = (target: EventTarget | null): boolean => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    return (
        target.isContentEditable ||
        ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
    );
};

/**
 * Registers the keyboard shortcuts of the client.
 * The tab and request shortcuts only apply while the getter reports the main page.
 */
export const useGlobalShortcuts = (isMainPage: () => boolean): void => {
    const tabsStore = useTabsStore();
    const { togglePalette, isHelpOpen, isToolboxOpen } = useCommandPalette();

    useEventListener(window, 'keydown', (event: KeyboardEvent) => {
        // The endpoint input already sends on Enter and marks the event as handled.
        if (event.defaultPrevented) {
            return;
        }

        const mod = event.metaKey || event.ctrlKey;
        const key = event.key.toLowerCase();

        if (mod && !event.altKey && !event.shiftKey && key === 'k') {
            event.preventDefault();
            togglePalette();

            return;
        }

        if (mod && !event.altKey && !event.shiftKey && key === 'e') {
            event.preventDefault();
            isToolboxOpen.value = !isToolboxOpen.value;

            return;
        }

        if (event.key === '?' && !mod && !isTyping(event.target)) {
            event.preventDefault();
            isHelpOpen.value = true;

            return;
        }

        if (!isMainPage()) {
            return;
        }

        if (event.altKey && !mod && /^Digit[1-9]$/.test(event.code)) {
            const tab = tabsStore.tabs[Number(event.code.slice(5)) - 1];

            if (tab) {
                event.preventDefault();
                tabsStore.setActiveTab(tab.id);
            }

            return;
        }

        if (!mod || event.altKey || event.shiftKey) {
            return;
        }

        if (key === 'enter') {
            event.preventDefault();
            document.querySelector<HTMLElement>('[data-shortcut="send"]')?.click();
        } else if (key === 'l') {
            event.preventDefault();
            const field = document.querySelector<HTMLElement>(
                '[data-testid="endpoint-input"]',
            );

            (field instanceof HTMLInputElement
                ? field
                : field?.querySelector('input')
            )?.focus();
        }
    });
};
