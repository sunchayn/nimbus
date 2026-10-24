import { AppDialog } from '@/components/base/dialog';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

const mountDialog = (props = {}) =>
    mount(
        {
            components: { AppDialog },
            props: ['open', 'title', 'description'],
            template: `
                <AppDialog :open="open" :title="title" :description="description">
                    <button data-testid="trigger">Open</button>
                    <template #body>Body text</template>
                    <template #footer="{ close }">
                        <button data-testid="dismiss" @click="close">Dismiss</button>
                    </template>
                </AppDialog>
            `,
        },
        { props, attachTo: document.body },
    );

describe('AppDialog', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('renders the trigger and keeps the content closed by default', () => {
        // Arrange & Act

        mountDialog();

        // Assert

        expect(document.querySelector('[data-testid="trigger"]')).not.toBeNull();
        expect(document.body.textContent).not.toContain('Body text');
    });

    it('renders title, description and slots when open', async () => {
        // Arrange & Act

        mountDialog({ open: true, title: 'My title', description: 'My description' });
        await nextTick();

        // Assert

        const dialog = document.querySelector('[role="dialog"]');
        expect(dialog).not.toBeNull();
        expect(dialog?.textContent).toContain('My title');
        expect(dialog?.textContent).toContain('My description');
        expect(dialog?.textContent).toContain('Body text');
    });

    it('opens when the trigger is clicked and closes through the footer slot', async () => {
        // Arrange

        mountDialog({ title: 'T' });

        // Act

        document.querySelector<HTMLElement>('[data-testid="trigger"]')?.click();
        await nextTick();
        await nextTick();

        // Assert

        expect(document.querySelector('[role="dialog"]')).not.toBeNull();

        // Act

        document.querySelector<HTMLElement>('[data-testid="dismiss"]')?.click();
        await new Promise(resolve => setTimeout(resolve, 300));

        // Assert

        expect(document.querySelector('[role="dialog"]')).toBeNull();
    });
});
