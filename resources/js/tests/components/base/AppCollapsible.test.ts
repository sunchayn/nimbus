import { AppCollapsible } from '@/components/base/collapsible';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

const mountCollapsible = (props = {}) =>
    mount(
        {
            components: { AppCollapsible },
            props: ['defaultOpen', 'disabled'],
            template: `
                <AppCollapsible :default-open="defaultOpen" :disabled="disabled">
                    <button data-testid="trigger">Toggle</button>
                    <template #content><p>Hidden body</p></template>
                </AppCollapsible>
            `,
        },
        { props, attachTo: document.body },
    );

describe('AppCollapsible', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('hides the content by default', () => {
        // Arrange & Act

        mountCollapsible();

        // Assert

        expect(document.body.textContent).not.toContain('Hidden body');
    });

    it('shows the content when default open', () => {
        // Arrange & Act

        mountCollapsible({ defaultOpen: true });

        // Assert

        expect(document.body.textContent).toContain('Hidden body');
    });

    it('toggles the content through the trigger', async () => {
        // Arrange

        mountCollapsible();

        // Act

        document.querySelector<HTMLElement>('[data-testid="trigger"]')?.click();
        await flushPromises();

        // Assert

        expect(document.body.textContent).toContain('Hidden body');
    });

    it('ignores the trigger when disabled', async () => {
        // Arrange

        mountCollapsible({ disabled: true });

        // Act

        document.querySelector<HTMLElement>('[data-testid="trigger"]')?.click();
        await nextTick();

        // Assert

        expect(document.body.textContent).not.toContain('Hidden body');
    });
});
