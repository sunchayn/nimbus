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
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

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

describe('SingleDumpRenderer', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Non-nestable Types', () => {
        it('renders StringDumpRenderer for string type', async () => {
            const dump = createStringDump('test-string');

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('string-dump-renderer')).toBeInTheDocument();
            expect(screen.getByTestId('string-dump-renderer')).toHaveTextContent(
                'test-string',
            );
        });

        it('renders NumberDumpRenderer for number type', async () => {
            const dump = createNumberDump(42);

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('number-dump-renderer')).toBeInTheDocument();
            expect(screen.getByTestId('number-dump-renderer')).toHaveTextContent('42');
        });

        it('renders ConstDumpRenderer for const type', async () => {
            const dump = createConstDump(true);

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('const-dump-renderer')).toBeInTheDocument();
            expect(screen.getByTestId('const-dump-renderer')).toHaveTextContent('true');
        });

        it('renders ClosureDumpRenderer for closure type', async () => {
            const dump = createClosureDump(
                'Closure(Application $app)',
                'MyClass',
                'thisValue',
            );

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('app-collapsible')).toBeInTheDocument();

            expect(screen.getByTestId('app-collapsible')).toHaveTextContent(
                'Closure(Application $app)',
            );
        });

        it('displays key name when provided for string type', async () => {
            const dump = createStringDump('test');

            const { container } = renderWithProviders(SingleDumpRenderer, {
                props: { dump, keyName: 'myKey' },
            });

            await nextTick();

            expect(container.textContent).toEqual('"myKey": test');
            expect(screen.getByTestId('string-dump-renderer')).toHaveTextContent('test');
        });

        it('shows error message for unknown types', async () => {
            const dump = { type: 'unknown-type' } as DumpValue;

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByText(/Invalid dump value type/)).toBeInTheDocument();
            expect(screen.getByText(/`unknown-type`/)).toBeInTheDocument();
        });
    });

    describe('Nestable Types', () => {
        it('renders collapsible for object type', async () => {
            const dump = createObjectDump('MyClass', {});

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('app-collapsible')).toBeInTheDocument();
            expect(screen.getByTestId('collapsible-trigger')).toBeInTheDocument();
            expect(screen.getByTestId('chevron-right')).toBeInTheDocument();
        });

        it('renders collapsible for array type', async () => {
            const dump = createArrayDump({});

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('app-collapsible')).toBeInTheDocument();
            expect(screen.getByTestId('collapsible-trigger')).toBeInTheDocument();
            expect(screen.getByTestId('chevron-right')).toBeInTheDocument();
        });

        it('shows correct summary text for object with properties', async () => {
            const dump = createObjectDump('MyClass', {
                prop1: {
                    visibility: 'public',
                    value: createStringDump('value1'),
                },
                prop2: { visibility: 'private', value: createNumberDump(42) },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const trigger = screen.getByTestId('collapsible-trigger');
            expect(trigger.textContent).toContain('MyClass: 2 properties');
        });

        it('shows correct summary text for object with single property', async () => {
            const dump = createObjectDump('MyClass', {
                prop1: {
                    visibility: 'public',
                    value: createStringDump('value1'),
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const trigger = screen.getByTestId('collapsible-trigger');
            expect(trigger.textContent).toContain('MyClass: 1 property');
        });

        it('shows correct summary text for object with no properties', async () => {
            const dump = createObjectDump('MyClass', {});

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const trigger = screen.getByTestId('collapsible-trigger');
            expect(trigger.textContent).toContain('{}');
        });

        it('shows correct summary text for array with items', async () => {
            const dump = createArrayDump({
                '0': createStringDump('item1'),
                '1': createStringDump('item2'),
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const trigger = screen.getByTestId('collapsible-trigger');
            expect(trigger.textContent).toContain('array: 2 items');
        });

        it('shows correct summary text for array with single item', async () => {
            const dump = createArrayDump({
                '0': createStringDump('item1'),
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const trigger = screen.getByTestId('collapsible-trigger');
            expect(trigger.textContent).toContain('array: 1 item');
        });

        it('shows correct summary text for empty array', async () => {
            const dump = createArrayDump({});

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const trigger = screen.getByTestId('collapsible-trigger');
            expect(trigger.textContent).toContain('[]');
        });

        it('is open by default when depth is 0', async () => {
            const dump = createObjectDump('MyClass', {
                prop1: {
                    visibility: 'public',
                    value: createStringDump('value1'),
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump, depth: 0 },
            });

            await nextTick();

            const collapsible = screen.getByTestId('app-collapsible');
            expect(collapsible.getAttribute('data-default-open')).toBe('true');
        });

        it('is closed by default when depth > 0', async () => {
            const dump = createObjectDump('MyClass', {
                prop1: {
                    visibility: 'public',
                    value: createStringDump('value1'),
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump, depth: 1 },
            });

            await nextTick();

            const collapsible = screen.getByTestId('app-collapsible');
            expect(collapsible.getAttribute('data-default-open')).toBe('false');
        });

        it('displays key name for nestable types', async () => {
            const dump = createObjectDump('MyClass', {});

            const { container } = renderWithProviders(SingleDumpRenderer, {
                props: { dump, keyName: 'myObject' },
            });

            await nextTick();

            expect(container.textContent).toContain('"myObject": {}');
        });
    });

    describe('Nested Rendering', () => {
        it('recursively renders nested object properties', async () => {
            const dump = createObjectDump('MyClass', {
                prop1: {
                    visibility: 'public',
                    value: createStringDump('value1'),
                },
                prop2: { visibility: 'private', value: createNumberDump(42) },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            // Should render property keys
            const propertyKeys = screen.getAllByTestId('property-key');
            expect(propertyKeys).toHaveLength(2);

            // Should render the actual values
            expect(screen.getByTestId('string-dump-renderer')).toBeInTheDocument();
            expect(screen.getByTestId('number-dump-renderer')).toBeInTheDocument();
        });

        it('recursively renders array items', async () => {
            const dump = createArrayDump(
                {
                    value: createStringDump('item1'),
                    'value-2': createNumberDump(42),
                },
                false,
            );

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const content = screen.getByTestId('collapsible-content');
            expect(content).toBeInTheDocument();

            // Should render both item values
            expect(screen.getByTestId('string-dump-renderer')).toBeInTheDocument();
            expect(screen.getByTestId('number-dump-renderer')).toBeInTheDocument();
        });

        it('passes correct depth to nested renderers', async () => {
            const nestedDump = createObjectDump('NestedClass', {
                nestedProp: {
                    visibility: 'public',
                    value: createStringDump('nested'),
                },
            });
            const dump = createObjectDump('MyClass', {
                nested: { visibility: 'public', value: nestedDump },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump, depth: 0 },
            });

            await nextTick();

            // Both outer and nested objects should have collapsibles
            const collapsibles = screen.getAllByTestId('app-collapsible');
            expect(collapsibles.length).toBeGreaterThanOrEqual(2);

            // Outer should be open (depth 0)
            expect(collapsibles[0].getAttribute('data-default-open')).toBe('true');
        });

        it('passes correct key names to nested renderers', async () => {
            const dump = createObjectDump('MyClass', {
                myProperty: {
                    visibility: 'public',
                    value: createStringDump('value'),
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const propertyKey = screen.getByTestId('property-key');
            expect(propertyKey).toHaveTextContent('myProperty');
        });

        it('uses ObjectDumpValuePropertyKey for object properties', async () => {
            const dump = createObjectDump('MyClass', {
                prop1: {
                    visibility: 'public',
                    value: createStringDump('value1'),
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('property-key')).toBeInTheDocument();
            expect(screen.getByTestId('property-key')).toHaveTextContent('prop1');
        });

        it('renders array items with numeric keys', async () => {
            const dump = createArrayDump({
                '0': createStringDump('item1'),
                '1': createStringDump('item2'),
            });

            const { container } = renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            // Array items should show their index keys
            expect(container.textContent).toContain('0:');
            expect(container.textContent).toContain('1:');
        });
    });

    describe('Edge Cases', () => {
        it('handles invalid nested value types in objects', async () => {
            const dump = createObjectDump('MyClass', {
                prop1: {
                    visibility: 'public',
                    value: { type: 'invalid-type' } as DumpValue,
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByText(/Invalid dump value type/)).toBeInTheDocument();
        });

        it('handles missing properties gracefully', async () => {
            const dump = createObjectDump('MyClass', {});

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            const trigger = screen.getByTestId('collapsible-trigger');
            expect(trigger.textContent).toContain('{}');

            // Should still render collapsible structure
            expect(screen.getByTestId('app-collapsible')).toBeInTheDocument();
        });

        it('handles deeply nested structures', async () => {
            const level3 = createObjectDump('Level3', {
                prop: { visibility: 'public', value: createStringDump('deep') },
            });
            const level2 = createObjectDump('Level2', {
                nested: { visibility: 'public', value: level3 },
            });
            const level1 = createObjectDump('Level1', {
                nested: { visibility: 'public', value: level2 },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump: level1 },
            });

            await nextTick();

            // Should render multiple nested collapsibles
            const collapsibles = screen.getAllByTestId('app-collapsible');
            expect(collapsibles.length).toBeGreaterThanOrEqual(3);

            // The deepest string value should be rendered
            expect(screen.getByTestId('string-dump-renderer')).toBeInTheDocument();
            expect(screen.getByTestId('string-dump-renderer')).toHaveTextContent('deep');
        });

        it('handles mixed nested types', async () => {
            const dump = createObjectDump('MyClass', {
                stringProp: {
                    visibility: 'public',
                    value: createStringDump('text'),
                },
                numberProp: {
                    visibility: 'public',
                    value: createNumberDump(123),
                },
                constProp: {
                    visibility: 'public',
                    value: createConstDump(true),
                },
                arrayProp: {
                    visibility: 'public',
                    value: createArrayDump({ '0': createStringDump('item') }),
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getAllByTestId('string-dump-renderer').length).toEqual(2);

            expect(screen.getByTestId('number-dump-renderer')).toBeInTheDocument();

            expect(screen.getByTestId('const-dump-renderer')).toBeInTheDocument();

            // Nested array should also be rendered
            const collapsibles = screen.getAllByTestId('app-collapsible');
            expect(collapsibles.length).toBeGreaterThanOrEqual(2); // Main object + nested array
        });

        it('handles empty arrays in object properties', async () => {
            const dump = createObjectDump('MyClass', {
                emptyArray: {
                    visibility: 'public',
                    value: createArrayDump({}),
                },
            });

            renderWithProviders(SingleDumpRenderer, {
                props: { dump },
            });

            await nextTick();

            expect(screen.getByTestId('property-key')).toHaveTextContent('emptyArray');

            // Should show [] for empty array
            const content = screen.getAllByTestId('collapsible-trigger')[1];

            console.log(content);

            expect(content.textContent).toContain('[]');
        });
    });
});
