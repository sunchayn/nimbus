<script setup lang="ts" generic="T extends ArrayOrNested<AppContextMenuItem>">
/**
 * @component AppContextMenu
 * @description A right click menu built on Nuxt UI. The default slot is the area that opens it.
 * Focus does not return to the area when the menu closes.
 * An item can set `afterClose` to run once the menu is closed, for example to open a popover.
 */
import type { ArrayOrNested } from '@/components/base/types';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { ContextMenuProps } from '@nuxt/ui/components/ContextMenu.vue';
import UContextMenu from '@nuxt/ui/components/ContextMenu.vue';
import { computed, nextTick } from 'vue';
import type { AppContextMenuItem } from './types';

/*
 * Component Setup.
 */

const props = defineProps<Omit<ContextMenuProps<T>, 'items'> & { items?: T }>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const forwarded = useForwardedProps(props);

/*
 * Deferred actions.
 * The menu returns focus to the area when it closes, which would dismiss a popover opened by an item.
 * So the action of such an item runs after the menu is closed.
 */

let pendingAction: (() => void) | null = null;

const withDeferredActions = (items: unknown): unknown =>
    Array.isArray(items)
        ? items.map(item => withDeferredActions(item))
        : defer(items as AppContextMenuItem);

const defer = (item: AppContextMenuItem): AppContextMenuItem => {
    if (!item || typeof item !== 'object') {
        return item;
    }

    return {
        ...item,
        children: item.children ? withDeferredActions(item.children) : undefined,
        onSelect: item.afterClose
            ? (event: Event) => {
                  pendingAction = item.afterClose ?? null;
                  item.onSelect?.(event);
              }
            : item.onSelect,
    } as AppContextMenuItem;
};

const handleOpen = async (open: boolean) => {
    emit('update:open', open);

    if (open || !pendingAction) {
        return;
    }

    const action = pendingAction;
    pendingAction = null;

    await nextTick();
    action();
};

const bound = computed(
    (): Record<string, unknown> => ({
        ...forwarded.value,
        items: props.items ? withDeferredActions(props.items) : undefined,
        content: {
            onCloseAutoFocus: (event: Event) => event.preventDefault(),
            ...(props.content as object | undefined),
        },
    }),
);
</script>

<template>
    <UContextMenu v-bind="bound" @update:open="handleOpen">
        <slot />
    </UContextMenu>
</template>
