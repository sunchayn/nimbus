import ShortcutsHelpDialog from '@/components/domain/CommandPalette/ShortcutsHelpDialog.vue';
import { useCommandPalette } from '@/composables/ui/useCommandPalette';
import { SHORTCUTS } from '@/config/shortcuts';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';

let mounted: VueWrapper | null = null;

describe('ShortcutsHelpDialog', () => {
    afterEach(async () => {
        useCommandPalette().isHelpOpen.value = false;
        await flushPromises();
        mounted?.unmount();
        mounted = null;
    });

    it('lists every shortcut when open', async () => {
        // Arrange

        mounted = mount(ShortcutsHelpDialog, { attachTo: document.body });

        // Act

        useCommandPalette().isHelpOpen.value = true;
        await flushPromises();

        // Assert

        const items = document.querySelectorAll('[data-testid="shortcuts-list"] li');

        expect(items).toHaveLength(SHORTCUTS.length);
    });
});
