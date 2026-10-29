<script setup lang="ts" generic="T extends ArrayOrNested<ContextMenuItem>">
/**
 * @component AppContextMenu
 * @description A right click menu built on Nuxt UI. The default slot is the area that opens it.
 */
import type { ArrayOrNested } from '@/components/base/types';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type {
    ContextMenuItem,
    ContextMenuProps,
} from '@nuxt/ui/components/ContextMenu.vue';
import UContextMenu from '@nuxt/ui/components/ContextMenu.vue';

/*
 * Component Setup.
 */

const props = defineProps<ContextMenuProps<T>>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UContextMenu v-bind="forwarded" @update:open="value => emit('update:open', value)">
        <slot />
    </UContextMenu>
</template>
