/**
 * Copy this template when creating new component tests.
 */

import type { VueWrapper } from '@vue/test-utils';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
// import ExampleComponent from '@/components/domain/Example/ExampleComponent.vue';

/*
 * Fixtures.
 */

/**
 * Factory function to create default props.
 * Centralizes prop definitions and makes tests more maintainable.
 */
const createDefaultProps = () => ({
    modelValue: 'initial',
    variant: 'default' as const,
    disabled: false,
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 * Reduces boilerplate in individual test cases.
 */
const createWrapper = (props = {}): VueWrapper => {
    // Replace with actual component
    const ExampleComponent = {
        template: '<div><input @input="$emit(\'update:modelValue\', ($event.target as HTMLInputElement).value)"></div>',
        props: ['modelValue', 'variant', 'disabled', 'mode'],
        emits: ['update:modelValue', 'submit', 'error'],
    };

    return mount(ExampleComponent, {
        props: { ...createDefaultProps(), ...props },
        global: {
            plugins: [createPinia()],
            stubs: {
                // Stub heavy child components to isolate unit under test
                HeavyChildComponent: true,
            },
        },
    });
};

describe('ExampleComponent', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe("Rendering", () => {
        it("renders with default props", () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.exists()).toBe(true);
        });

        it("applies variant class correctly", () => {
            // Arrange

            const wrapper = createWrapper({ variant: "destructive" });

            // Assert

            expect(wrapper.classes()).toContain("variant-destructive");
        });
    });

    /*
     * State Transition tests.
     */


    describe("State Transitions", () => {
        it("transitions from enabled to disabled state", async () => {
            // Arrange

            const wrapper = createWrapper({ disabled: false });

            // Act

            await wrapper.setProps({ disabled: true });

            // Assert

            expect(wrapper.classes()).toContain("is-disabled");
        });

        it("emits update:modelValue when internal state changes", async () => {
            // Arrange

            const wrapper = createWrapper({ modelValue: "initial" });

            // Act

            await wrapper.find("input").setValue("updated");
            await nextTick();

            // Assert

            expect(wrapper.emitted("update:modelValue")).toHaveLength(1);
            expect(wrapper.emitted("update:modelValue")![0]).toEqual([
                "updated",
            ]);
        });

        it("caches and restores state when switching modes", async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act - Set mode A value
            await wrapper.setProps({ mode: "A" });
            await wrapper
                .find('[data-testid="internal-input"]')
                .setValue("value-for-A");

            // Act - Switch to mode B and back
            await wrapper.setProps({ mode: "B" });
            await wrapper.setProps({ mode: "A" });

            // Assert - Previous value should be restored
            const input = wrapper.find('[data-testid="internal-input"]');
            expect((input.element as HTMLInputElement).value).toBe(
                "value-for-A",
            );
        });
    });

    /*
     * Edge Cases.
     */

    describe("Edge Cases", () => {
        it("handles empty modelValue gracefully", () => {
            // Arrange

            const wrapper = createWrapper({ modelValue: "" });

            // Assert

            expect(wrapper.find('[data-testid="display"]').text()).toBe("");
            expect(wrapper.emitted("error")).toBeUndefined();
        });

        it("handles null/undefined values without crashing", () => {
            // Arrange & Assert

            expect(() =>
                createWrapper({ modelValue: null as unknown }),
            ).not.toThrow();
        });

        it("handles rapid prop changes", async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act - Simulate rapid changes
            for (let index = 0; index < 10; index++) {
                await wrapper.setProps({ modelValue: `value-${index}` });
            }
            await flushPromises();

            // Assert - Should handle all changes
            expect((wrapper as any).props("modelValue")).toBe("value-9");
        });

        it("cleans up subscriptions on unmount", async () => {
            // Arrange

            const removeEventListenerSpy = vi.spyOn(
                window,
                "removeEventListener",
            );
            const wrapper = createWrapper();

            // Act

            wrapper.unmount();

            // Assert

            expect(removeEventListenerSpy).toHaveBeenCalled();
            removeEventListenerSpy.mockRestore();
        });
    });

    /*
     * Store Integration.
     */

    describe("Store Integration", () => {
        it("reacts to store state changes", async () => {
            // Arrange

            const wrapper = createWrapper();
            // const store = useExampleStore();

            // Act

            // store.updateSomeState('new-value');
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="store-value"]').text()).toBe(
                "new-value",
            );
        });

        it("dispatches correct action on user interaction", async () => {
            // Arrange

            // const store = useExampleStore();
            // const actionSpy = vi.spyOn(store, 'someAction');
            const wrapper = createWrapper();

            // Act

            await wrapper
                .find('button[data-testid="action-trigger"]')
                .trigger("click");

            // Assert

            // expect(actionSpy).toHaveBeenCalledWith(expect.objectContaining({
            //     id: expect.any(String),
            // }));
        });
    });
});
