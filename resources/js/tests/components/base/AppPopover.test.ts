import { AppPopover } from '@/components/base/popover';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

const mountPopover = (props = {}, withAnchor = false) =>
    mount(
        {
            components: { AppPopover },
            props: ['open'],
            template: withAnchor
                ? `<AppPopover :open="open">
                    <template #anchor><div data-testid="anchor">Anchor</div></template>
                    <template #content><p>Popover body</p></template>
                </AppPopover>`
                : `<AppPopover :open="open">
                    <button data-testid="trigger">Toggle</button>
                    <template #content><p>Popover body</p></template>
                </AppPopover>`,
        },
        { props, attachTo: document.body },
    );

describe('AppPopover', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('keeps the content closed by default', () => {
        // Arrange & Act

        mountPopover();

        // Assert

        expect(document.body.textContent).not.toContain('Popover body');
    });

    it('opens when the trigger is clicked', async () => {
        // Arrange

        mountPopover();

        // Act

        document.querySelector<HTMLElement>('[data-testid="trigger"]')?.click();
        await nextTick();
        await nextTick();

        // Assert

        expect(document.body.textContent).toContain('Popover body');
    });

    it('shows content next to an anchor when controlled open', async () => {
        // Arrange & Act

        mountPopover({ open: true }, true);
        await nextTick();

        // Assert

        expect(document.querySelector('[data-testid="anchor"]')).not.toBeNull();
        expect(document.body.textContent).toContain('Popover body');
    });
});
