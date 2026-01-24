import type { VueWrapper } from '@vue/test-utils';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { AppSelect, AppSelectContent, AppSelectItem, AppSelectTrigger, AppSelectValue } from '@/components/base/select';

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
    observe() { }
    unobserve() { }
    disconnect() { }
};

// Mock ScrollIntoView
window.HTMLElement.prototype.scrollIntoView = vi.fn();
window.HTMLElement.prototype.hasPointerCapture = vi.fn();
window.HTMLElement.prototype.releasePointerCapture = vi.fn();

// Mock PointerEvent
class MockPointerEvent extends Event {
    button: number;
    ctrlKey: boolean;
    metaKey: boolean;
    shiftKey: boolean;
    constructor(type: string, props: PointerEventInit) {
        super(type, props);
        this.button = props.button || 0;
        this.ctrlKey = props.ctrlKey || false;
        this.metaKey = props.metaKey || false;
        this.shiftKey = props.shiftKey || false;
    }
}
window.PointerEvent = MockPointerEvent as any;



/*
 * Fixtures.
 */

const createWrapper = (options= {}): VueWrapper => {
    return mount({
        components: { AppSelect, AppSelectTrigger, AppSelectValue, AppSelectContent, AppSelectItem },
        template: `
            <AppSelect v-bind="selectProps">
                <AppSelectTrigger>
                    <AppSelectValue placeholder="Select" />
                </AppSelectTrigger>
                <AppSelectContent>
                    <AppSelectItem value="opt1">Option 1</AppSelectItem>
                </AppSelectContent>
            </AppSelect>
        `,
        data() {
            // @ts-expect-error .props not found in object.
            return { selectProps: options.props || {} };
        }
    }, {
        global: { plugins: [createPinia()] },
        attachTo: document.body,
    });
};

describe('AppSelect', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    describe('Behavior', () => {
        it('opens options when trigger is clicked', async () => {
            // Arrange

            const wrapper = createWrapper();

            const buttonWrapper = wrapper.find('button');

            // Act
            // Radix/reka-ui often requires pointer interactions
            await buttonWrapper.trigger('pointerdown', { button: 0 });
            await buttonWrapper.trigger('pointerup', { button: 0 });
            await buttonWrapper.trigger('click', { button: 0 });

            await flushPromises();
            await nextTick();
            await nextTick();
            await nextTick();

            await nextTick();

            // Assert

            const options = document.querySelectorAll('[role="option"]');
            const hasOption = Array.from(options).some(opt => opt.textContent?.includes('Option 1'));

            expect(hasOption).toBe(true);
            wrapper.unmount();
        });
    });
});
