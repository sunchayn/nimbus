import type { VNode } from 'vue';

type ItemSlotProps<I> = {
    item: I;
    active: boolean;
    index: number;
    ui: Record<string, (props?: Record<string, unknown>) => string>;
};

type ItemSlot<I> = (props: ItemSlotProps<I>) => VNode[];

/**
 * Nuxt UI types a custom item slot with the item narrowed by its `slot` key, which collapses to `never` unless every item has a literal `slot`.
 * Custom slots take the call site item type here instead.
 */
export type AppDropdownMenuSlots<I> = {
    default?(props: { open: boolean }): VNode[];
    empty?(props: { searchTerm: string }): VNode[];
    'content-top'?(props: { sub: boolean }): VNode[];
    'content-bottom'?(props: { sub: boolean }): VNode[];
    item?: ItemSlot<I>;
    'item-leading'?: ItemSlot<I>;
    'item-label'?(props: Pick<ItemSlotProps<I>, 'item' | 'active' | 'index'>): VNode[];
    'item-description'?(
        props: Pick<ItemSlotProps<I>, 'item' | 'active' | 'index'>,
    ): VNode[];
    'item-trailing'?: ItemSlot<I>;
} & { [slot: string]: ItemSlot<I> | undefined };
