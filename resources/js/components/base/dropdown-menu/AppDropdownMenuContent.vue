<script setup lang="ts">
/**
 * @component AppDropdownMenuContent
 * @description The container for dropdown menu items, including portal and animations.
 */
import { cn } from '@/utils/ui';
import {
    DropdownMenuContent,
    type DropdownMenuContentEmits,
    type DropdownMenuContentProps,
    DropdownMenuPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuContentProps extends DropdownMenuContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppDropdownMenuContentProps>(), {
    sideOffset: 4,
    class: '',
});
const emits = defineEmits<DropdownMenuContentEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DropdownMenuPortal>
        <DropdownMenuContent
            v-bind="forwarded"
            :class="
                cn(
                    'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 min-w-32 overflow-hidden rounded-md border border-zinc-200 bg-white p-1 text-zinc-950 shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50',
                    props.class,
                )
            "
        >
            <slot />
        </DropdownMenuContent>
    </DropdownMenuPortal>
</template>
