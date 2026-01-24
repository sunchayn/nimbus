import type {
    ArrayDump,
    ClosureDump,
    ConstDump,
    DumpValue,
    NumberDump,
    ObjectDump,
    StringDump,
} from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import SingleDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/SingleDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

vi.mock('@/components/base/collapsible', () => ({
    AppCollapsible: {
        name: 'AppCollapsible',
        template: `
            <div data-testid="app-collapsible" :data-default-open="defaultOpen">
                <slot />
            </div>
        `,
        props: {
            defaultOpen: Boolean,
            class: String,
        },
    },
    AppCollapsibleContent: {
        name: 'AppCollapsibleContent',
        template: '<div data-testid="collapsible-content"><slot /></div>',
    },
    AppCollapsibleTrigger: {
        name: 'AppCollapsibleTrigger',
        template: '<button data-testid="collapsible-trigger"><slot /></button>',
        props: {
            class: String,
        },
    },
}));

vi.mock(
    '@/components/domain/Client/Response/ResponseBody/DumpRenderer/StringDumpRenderer.vue',
    () => ({
        default: {
            name: 'StringDumpRenderer',
            template: '<span data-testid="string-dump-renderer">{{ dump.value }}</span>',
            props: {
                dump: Object,
                keyName: String,
            },
        },
    }),
);

vi.mock(
    '@/components/domain/Client/Response/ResponseBody/DumpRenderer/NumberDumpRenderer.vue',
    () => ({
        default: {
            name: 'NumberDumpRenderer',
            template: '<span data-testid="number-dump-renderer">{{ dump.value }}</span>',
            props: {
                dump: Object,
                keyName: String,
            },
        },
    }),
);

vi.mock(
    '@/components/domain/Client/Response/ResponseBody/DumpRenderer/ConstDumpRenderer.vue',
    () => ({
        default: {
            name: 'ConstDumpRenderer',
            template: '<span data-testid="const-dump-renderer">{{ dump.value }}</span>',
            props: {
                dump: Object,
                keyName: String,
            },
        },
    }),
);

vi.mock(
    '@/components/domain/Client/Response/ResponseBody/DumpRenderer/ObjectDumpValuePropertyKey.vue',
    () => ({
        default: {
            name: 'ObjectDumpValuePropertyKey',
            template: '<span data-testid="property-key">{{ keyName }}</span>',
            props: {
                keyName: String,
                property: Object,
                class: String,
            },
        },
    }),
);

vi.mock('lucide-vue-next', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        ChevronRight: {
            name: 'ChevronRight',
            template: '<svg data-testid="chevron-right" />',
        },
    };
});

const createStringDump = (value: string): StringDump => ({
    type: DumpValueType.String,
    value,
});

const createNumberDump = (value: number): NumberDump => ({
    type: DumpValueType.Number,
    value,
});

const createConstDump = (value: boolean | null): ConstDump => ({
    type: DumpValueType.Constant,
    value,
});

const createClosureDump = (
    signature: string = 'Closure()',
    className: string | null = null,
    thisValue: string | null = null,
): ClosureDump => ({
    type: DumpValueType.Closure,
    value: {
        signature,
        class: className,
        this: thisValue,
    },
});

const createObjectDump = (
    className: string,
    properties: Record<
        string,
        { visibility: 'public' | 'protected' | 'private'; value: DumpValue }
    >,
): ObjectDump => ({
    type: DumpValueType.Object,
    value: {
        class: className,
        properties,
        propertiesCount: Object.keys(properties).length,
    },
});

const createArrayDump = (
    items: Record<string, DumpValue>,
    numericallyIndexed: boolean = true,
): ArrayDump => ({
    type: DumpValueType.Array,
    value: {
        items,
        length: Object.keys(items).length,
        numericallyIndexed,
    },
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(SingleDumpRenderer, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('SingleDumpRenderer', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering - Non-nestable Types', () => {
        it('renders StringDumpRenderer for string type', async () => {
            // Arrange

            const dump = createStringDump('test-string');
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="string-dump-renderer"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="string-dump-renderer"]').text()).toBe(
                'test-string',
            );
        });

        it('renders NumberDumpRenderer for number type', async () => {
            // Arrange

            const dump = createNumberDump(42);
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="number-dump-renderer"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="number-dump-renderer"]').text()).toBe(
                '42',
            );
        });

        it('renders ConstDumpRenderer for const type', async () => {
            // Arrange

            const dump = createConstDump(true);
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="const-dump-renderer"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="const-dump-renderer"]').text()).toBe(
                'true',
            );
        });

        it('renders ClosureDumpRenderer content', async () => {
            // Arrange

            const dump = createClosureDump(
                'Closure(Application $app)',
                'MyClass',
                'thisValue',
            );
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="app-collapsible"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="app-collapsible"]').text()).toContain(
                'Closure(Application $app)',
            );
        });

        it('displays key name when provided for string type', async () => {
            // Arrange

            const dump = createStringDump('test');
            const wrapper = createWrapper({
                props: { dump, keyName: 'myKey' },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('"myKey":');
            expect(wrapper.find('[data-testid="string-dump-renderer"]').text()).toBe(
                'test',
            );
        });

        it('shows error message for unknown types', async () => {
            // Arrange

            const dump = { type: 'unknown-type' } as DumpValue;
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('Invalid dump value type');
            expect(wrapper.text()).toContain('`unknown-type`');
        });
    });

    describe('Rendering - Nestable Types', () => {
        it('renders collapsible for object type', async () => {
            // Arrange

            const dump = createObjectDump('MyClass', {});
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="app-collapsible"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="collapsible-trigger"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="chevron-right"]').exists()).toBe(true);
        });

        it('renders collapsible for array type', async () => {
            // Arrange

            const dump = createArrayDump({});
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="app-collapsible"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="collapsible-trigger"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="chevron-right"]').exists()).toBe(true);
        });

        it('shows correct summary text for object with properties', async () => {
            // Arrange

            const dump = createObjectDump('MyClass', {
                prop1: { visibility: 'public', value: createStringDump('value1') },
                prop2: { visibility: 'private', value: createNumberDump(42) },
            });
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            const trigger = wrapper.find('[data-testid="collapsible-trigger"]');
            expect(trigger.text()).toContain('MyClass: 2 properties');
        });

        it('shows correct summary text for empty array', async () => {
            // Arrange

            const dump = createArrayDump({});
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            const trigger = wrapper.find('[data-testid="collapsible-trigger"]');
            expect(trigger.text()).toContain('[]');
        });

        it('is open by default when depth is 0', async () => {
            // Arrange

            const dump = createObjectDump('MyClass', {
                prop1: { visibility: 'public', value: createStringDump('value1') },
            });
            const wrapper = createWrapper({
                props: { dump, depth: 0 },
            });

            // Act

            await nextTick();

            // Assert

            const collapsible = wrapper.find('[data-testid="app-collapsible"]');
            expect(collapsible.attributes('data-default-open')).toBe('true');
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior - Nested Rendering', () => {
        it('recursively renders nested object properties', async () => {
            // Arrange

            const dump = createObjectDump('MyClass', {
                prop1: { visibility: 'public', value: createStringDump('value1') },
                prop2: { visibility: 'private', value: createNumberDump(42) },
            });
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            const propertyKeys = wrapper.findAll('[data-testid="property-key"]');
            expect(propertyKeys).toHaveLength(2);
            expect(wrapper.find('[data-testid="string-dump-renderer"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="number-dump-renderer"]').exists()).toBe(
                true,
            );
        });

        it('recursively renders array items', async () => {
            // Arrange

            const dump = createArrayDump(
                {
                    value: createStringDump('item1'),
                    'value-2': createNumberDump(42),
                },
                false,
            );
            const wrapper = createWrapper({
                props: { dump },
            });

            // Act

            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="collapsible-content"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="string-dump-renderer"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="number-dump-renderer"]').exists()).toBe(
                true,
            );
        });
    });

    /*
     * Edge Cases.
     */

    describe('Edge Cases', () => {
        it('handles deeply nested structures', async () => {
            // Arrange

            const level3 = createObjectDump('Level3', {
                prop: { visibility: 'public', value: createStringDump('deep') },
            });
            const level2 = createObjectDump('Level2', {
                nested: { visibility: 'public', value: level3 },
            });
            const level1 = createObjectDump('Level1', {
                nested: { visibility: 'public', value: level2 },
            });

            const wrapper = createWrapper({
                props: { dump: level1 },
            });

            // Act

            await nextTick();

            // Assert

            const collapsibles = wrapper.findAll('[data-testid="app-collapsible"]');
            expect(collapsibles.length).toBeGreaterThanOrEqual(3);
            expect(wrapper.find('[data-testid="string-dump-renderer"]').exists()).toBe(
                true,
            );
            expect(wrapper.find('[data-testid="string-dump-renderer"]').text()).toBe(
                'deep',
            );
        });
    });
});
