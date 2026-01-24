import type { MountingOptions, VueWrapper } from "@vue/test-utils";
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import type { ObjectDumpProperty } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import ObjectDumpValuePropertyKey from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/ObjectDumpValuePropertyKey.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';

/*
 * Fixtures.
 */

const createProperty = (
    visibility: 'public' | 'protected' | 'private',
): ObjectDumpProperty => ({
    visibility,
    value: {
        type: DumpValueType.String,
        value: 'test',
    },
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(ObjectDumpValuePropertyKey, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global ?? {}),
        },
    });
};

describe('ObjectDumpValuePropertyKey', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('displays public property with + symbol and emerald color', async () => {
            // Arrange

            const property = createProperty('public');

            const wrapper = createWrapper({
                props: { property, keyName: 'publicProp' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('+');
            expect(wrapper.text()).toContain('publicProp:');

            const span = wrapper.find('span > span');
            expect(span.classes()).toContain('text-emerald-600');
        });

        it('displays protected property with # symbol and amber color', async () => {
            // Arrange

            const property = createProperty('protected');

            const wrapper = createWrapper({
                props: { property, keyName: 'protectedProp' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('#');
            expect(wrapper.text()).toContain('protectedProp:');

            const span = wrapper.find('span > span');
            expect(span.classes()).toContain('text-amber-600');
        });

        it('displays private property with - symbol and zinc color', async () => {
            // Arrange

            const property = createProperty('private');

            const wrapper = createWrapper({
                props: { property, keyName: 'privateProp' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('-');
            expect(wrapper.text()).toContain('privateProp:');

            const span = wrapper.find('span > span');
            expect(span.classes()).toContain('text-zinc-500');
        });

        it('displays key name after visibility symbol', async () => {
            // Arrange

            const property = createProperty('public');

            const wrapper = createWrapper({
                props: { property, keyName: 'myProperty' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('+');
            expect(wrapper.text()).toContain('myProperty:');
        });

        it('applies correct CSS classes for public visibility', async () => {
            // Arrange

            const property = createProperty('public');

            const wrapper = createWrapper({
                props: { property, keyName: 'prop' },
            });

            // Act

            await nextTick();

            // Assert

            const visibilitySpan = wrapper.find('span > span');
            expect(visibilitySpan.classes()).toContain('text-emerald-600');
            expect(visibilitySpan.classes()).toContain('dark:text-emerald-500');
        });

        it('applies correct CSS classes for protected visibility', async () => {
            // Arrange

            const property = createProperty('protected');

            const wrapper = createWrapper({
                props: { property, keyName: 'prop' },
            });

            // Act

            await nextTick();

            // Assert

            const visibilitySpan = wrapper.find('span > span');
            expect(visibilitySpan.classes()).toContain('text-amber-600');
            expect(visibilitySpan.classes()).toContain('dark:text-amber-500');
        });

        it('applies correct CSS classes for private visibility', async () => {
            // Arrange

            const property = createProperty('private');

            const wrapper = createWrapper({
                props: { property, keyName: 'prop' },
            });

            // Act

            await nextTick();

            // Assert

            const visibilitySpan = wrapper.find('span > span');
            expect(visibilitySpan.classes()).toContain('text-zinc-500');
            expect(visibilitySpan.classes()).toContain('dark:text-zinc-400');
        });
    });

    /*
     * Edge Cases.
     */

    describe('Edge Cases', () => {
        it('handles empty key name', async () => {
            // Arrange

            const property = createProperty('public');

            const wrapper = createWrapper({
                props: { property, keyName: '' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('+');
            expect(wrapper.text()).toContain(':');
        });

        it('handles special characters in key name', async () => {
            // Arrange

            const property = createProperty('public');

            const wrapper = createWrapper({
                props: { property, keyName: 'prop_with_underscore' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('prop_with_underscore:');
        });
    });
});
