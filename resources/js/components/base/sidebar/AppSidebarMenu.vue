<script setup lang="ts" generic="T extends ArrayOrNested<NavigationMenuItem>">
/**
 * @component AppSidebarMenu
 * @description Vertical navigation list that shows tooltips when collapsed.
 */
import type { ArrayOrNested, NestedItem } from '@/components/base/types';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type {
    NavigationMenuItem,
    NavigationMenuProps,
    NavigationMenuSlots,
} from '@nuxt/ui/components/NavigationMenu.vue';
import UNavigationMenu from '@nuxt/ui/components/NavigationMenu.vue';
import { computed, type ComputedRef } from 'vue';

/*
 * Component Setup.
 */

const props = defineProps<Omit<NavigationMenuProps<T>, 'orientation'>>();

const emit = defineEmits<{
    'update:modelValue': [value: string | string[] | undefined];
}>();

defineSlots<NavigationMenuSlots<T, NestedItem<T>>>();

// The forwarded props are re-checked by Nuxt UI generics that cannot be matched from inside the wrapper.
const forwarded = useForwardedProps(props) as unknown as ComputedRef<
    Record<string, unknown>
>;

const withAriaLabel = (item: NavigationMenuItem): NavigationMenuItem => ({
    'aria-label': item.label,
    ...item,
});

// A collapsed menu hides the labels, so each icon-only link needs an accessible name.
const items = computed(() => {
    if (!props.collapsed || !props.items) {
        return props.items;
    }

    return props.items.map(entry =>
        Array.isArray(entry) ? entry.map(withAriaLabel) : withAriaLabel(entry),
    ) as T;
});
</script>

<template>
    <UNavigationMenu
        orientation="vertical"
        tooltip
        v-bind="forwarded"
        :items="items"
        @update:model-value="value => emit('update:modelValue', value as never)"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UNavigationMenu>
</template>
