<script
    setup
    lang="ts"
    generic="G extends CommandPaletteGroup<T>, T extends CommandPaletteItem"
>
/**
 * @component AppCommand
 * @description Command palette built on Nuxt UI. Pass `groups` of items and bind the query with `v-model:search-term`.
 * The `header` slot renders between the search input and the list.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type {
    CommandPaletteGroup,
    CommandPaletteItem,
    CommandPaletteProps,
    CommandPaletteSlots,
} from '@nuxt/ui/components/CommandPalette.vue';
import UCommandPalette from '@nuxt/ui/components/CommandPalette.vue';
import { type ComponentPublicInstance, computed, useTemplateRef } from 'vue';

/*
 * Component Setup.
 */

const props = defineProps<CommandPaletteProps<G, T> & { searchTerm?: string }>();

const emit = defineEmits<{
    'update:modelValue': [value: T];
    'update:searchTerm': [value: string];
    'update:open': [value: boolean];
    highlight: [payload: { ref: HTMLElement; value: T } | undefined];
    leave: [event: Event];
    entryFocus: [event: CustomEvent];
}>();

const slots = defineSlots<
    CommandPaletteSlots<T, G> & { header?(props: Record<string, never>): unknown }
>();

const forwarded = useForwardedProps(props);

const palette = useTemplateRef<ComponentPublicInstance>('palette');

/**
 * Moves the focus to the search input.
 */
const focusInput = (): void => {
    const anchor: unknown = palette.value?.$el;

    // Nuxt UI renders a fragment, so the root element is the first one after the anchor node.
    let root = anchor instanceof Node ? anchor : null;

    while (root && !(root instanceof Element)) {
        root = root.nextSibling;
    }

    root?.querySelector('input')?.focus();
};

defineExpose({ focusInput });

const passthroughSlots = computed(() =>
    Object.keys(slots).filter(name => name !== 'header' && name !== 'footer'),
);
</script>

<template>
    <UCommandPalette
        ref="palette"
        v-bind="forwarded"
        @update:model-value="value => emit('update:modelValue', value as T)"
        @update:search-term="value => emit('update:searchTerm', value)"
        @update:open="value => emit('update:open', value)"
        @highlight="payload => emit('highlight', payload as never)"
        @leave="event => emit('leave', event)"
        @entry-focus="event => emit('entryFocus', event)"
    >
        <template v-for="name in passthroughSlots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="(slotProps ?? {}) as any" />
        </template>
        <!-- Nuxt UI has no slot above the list, so the preset reorders these parts with CSS order. -->
        <template v-if="slots.header || slots.footer" #footer="slotProps">
            <div v-if="slots.header" data-slot="command-header">
                <slot name="header" />
            </div>
            <div v-if="slots.footer" data-slot="command-footer">
                <slot name="footer" v-bind="slotProps" />
            </div>
        </template>
    </UCommandPalette>
</template>
