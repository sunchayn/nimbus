import type { StringDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import StringDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/StringDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(StringDumpRenderer, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('StringDumpRenderer', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders string value in quotes', async () => {
            // Arrange

            const dump: StringDump = {
                type: DumpValueType.String,
                value: 'test-string',
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('"test-string"');
        });

        it('displays string length in parentheses', async () => {
            // Arrange

            const dump: StringDump = {
                type: DumpValueType.String,
                value: 'hello',
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('(5)');
        });

        it('applies correct CSS classes', async () => {
            // Arrange

            const dump: StringDump = {
                type: DumpValueType.String,
                value: 'test',
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
        it('handles empty strings', async () => {
            // Arrange

            const dump: StringDump = {
                type: DumpValueType.String,
                value: '',
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('""');
            expect(wrapper.text()).toContain('(0)');
        });

        it('handles long strings', async () => {
            // Arrange

            const longString = 'a'.repeat(1000);
            const dump: StringDump = {
                type: DumpValueType.String,
                value: longString,
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain(`"${longString}"`);
            expect(wrapper.text()).toContain('(1000)');
        });

        it('handles unicode characters', async () => {
            // Arrange

            const dump: StringDump = {
                type: DumpValueType.String,
                value: '测试 🎉',
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('"测试 🎉"');
            expect(wrapper.text()).toContain('(5)');
        });

        it('handles strings with quotes', async () => {
            // Arrange

            const dump: StringDump = {
                type: DumpValueType.String,
                value: 'string with "quotes"',
            };

            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('"string with "quotes""');
        });
    });
});
