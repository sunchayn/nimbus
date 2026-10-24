<script setup lang="ts" generic="T extends ArrayOrNested<DropdownMenuItem>">
/**
 * @component AppDropdownMenu
 * @description A dropdown menu built on Nuxt UI. The default slot is the trigger.
 */
import type { AppDropdownMenuSlots } from '@/components/base/dropdown-menu/types';
import type { ArrayOrNested, NestedItem } from '@/components/base/types';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type {
    DropdownMenuItem,
    DropdownMenuProps,
} from '@nuxt/ui/components/DropdownMenu.vue';
import UDropdownMenu from '@nuxt/ui/components/DropdownMenu.vue';

/*
 * Types & Interfaces.
 */

export type AppDropdownMenuProps<I extends ArrayOrNested<DropdownMenuItem>> =
    DropdownMenuProps<I> & { searchTerm?: string };

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuProps<T>>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:searchTerm': [value: string];
}>();

defineSlots<AppDropdownMenuSlots<NestedItem<T>>>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UDropdownMenu
        v-bind="forwarded"
        @update:open="value => emit('update:open', value)"
        @update:search-term="value => emit('update:searchTerm', value)"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UDropdownMenu>
</template>
