<script setup lang="ts">
/**
 * @component AppDropdownMenuCheckboxItem
 * @description A dropdown menu item that can be toggled on/off.
 */
import { cn } from '@/utils/ui';
import { Check } from 'lucide-vue-next';
import {
    DropdownMenuCheckboxItem,
    type DropdownMenuCheckboxItemEmits,
    type DropdownMenuCheckboxItemProps,
    DropdownMenuItemIndicator,
    useForwardPropsEmits,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuCheckboxItemProps extends DropdownMenuCheckboxItemProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuCheckboxItemProps>();
const emits = defineEmits<DropdownMenuCheckboxItemEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DropdownMenuCheckboxItem
        v-bind="forwarded"
        :class="
            cn(
                'relative flex cursor-default items-center rounded-sm py-1.5 pr-2 pl-8 text-sm transition-colors outline-none select-none focus:bg-zinc-100 focus:text-zinc-900 data-[disabled]:pointer-events-none data-[disabled]:opacity-50 dark:focus:bg-zinc-800 dark:focus:text-zinc-50',
                props.class,
            )
        "
    >
        <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
            <DropdownMenuItemIndicator>
                <Check class="h-4 w-4" />
            </DropdownMenuItemIndicator>
        </span>
        <slot />
    </DropdownMenuCheckboxItem>
</template>
