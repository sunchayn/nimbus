import { AppTooltip } from '@/components/base/tooltip';
import UApp from '@nuxt/ui/components/App.vue';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

const mountTooltip = (props = {}) =>
    mount(
        {
            components: { AppTooltip, UApp },
            props: ['text', 'open'],
            template: `
                <UApp>
                    <AppTooltip :text="text" :open="open">
                        <button data-testid="trigger">Hover me</button>
                    </AppTooltip>
                </UApp>
            `,
        },
        { props, attachTo: document.body },
    );

describe('AppTooltip', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('renders the trigger without showing the text', () => {
        // Arrange & Act

        mountTooltip({ text: 'Helpful hint' });

        // Assert

        expect(document.querySelector('[data-testid="trigger"]')?.textContent).toBe(
            'Hover me',
        );
        expect(document.body.textContent).not.toContain('Helpful hint');
    });

    it('shows the text with the tooltip role when open', async () => {
        // Arrange & Act

        mountTooltip({ text: 'Helpful hint', open: true });
        await nextTick();
        await nextTick();

        // Assert

        expect(document.querySelector('[role="tooltip"]')?.textContent).toContain(
            'Helpful hint',
        );
    });
});
