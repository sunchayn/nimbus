import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import type { ConstDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import ConstDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/ConstDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { RenderWithProvidersOptions } from "@/tests/_utils/test-utils";

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount(ConstDumpRenderer, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('ConstDumpRenderer', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders true as string', async () => {
            // Arrange

            const dump: ConstDump = {
                type: DumpValueType.Constant,
                value: true,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('true');
        });

        it('renders false as string', async () => {
            // Arrange

            const dump: ConstDump = {
                type: DumpValueType.Constant,
                value: false,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('false');
        });

        it('renders null as string', async () => {
            // Arrange

            const dump: ConstDump = {
                type: DumpValueType.Constant,
                value: null,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('null');
        });

        it('applies italic styling', async () => {
            // Arrange

            const dump: ConstDump = {
                type: DumpValueType.Constant,
                value: true,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('span').classes()).toContain('italic');
        });

        it('applies correct CSS classes', async () => {
            // Arrange

            const dump: ConstDump = {
                type: DumpValueType.Constant,
                value: true,
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

        it('handles keyName prop when provided', async () => {
            // Arrange

            const dump: ConstDump = {
                type: DumpValueType.Constant,
                value: true,
            };

            const wrapper = createWrapper({
                props: { dump, keyName: 'myKey' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toBe('true');
        });
    });
});
