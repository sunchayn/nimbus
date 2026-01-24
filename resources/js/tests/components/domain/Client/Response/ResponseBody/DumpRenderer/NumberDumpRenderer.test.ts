import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import type { NumberDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import NumberDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/NumberDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { RenderWithProvidersOptions } from "@/tests/_utils/test-utils";

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount(NumberDumpRenderer, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('NumberDumpRenderer', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders number value correctly', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: 42,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('42');
        });

        it('handles positive numbers', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: 123,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('123');
        });

        it('handles negative numbers', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: -42,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('-42');
        });

        it('handles zero', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: 0,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('0');
        });

        it('handles decimal numbers', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: 3.14159,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('3.14159');
        });

        it('handles very large numbers', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: 1e20,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('100000000000000000000');
        });

        it('handles very small numbers', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: 1e-10,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('1e-10');
        });

        it('applies correct CSS classes', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: 42,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            const span = wrapper.find('span');
            expect(span.classes()).toContain('text-xs');
            expect(span.classes()).toContain('font-mono');
        });
    });

    /*
     * Edge Cases.
     */

    describe('Edge Cases', () => {
        it('handles Infinity', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: Infinity,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('Infinity');
        });

        it('handles -Infinity', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: -Infinity,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('-Infinity');
        });

        it('handles NaN', async () => {
            // Arrange

            const dump: NumberDump = {
                type: DumpValueType.Number,
                value: NaN,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('NaN');
        });
    });
});
