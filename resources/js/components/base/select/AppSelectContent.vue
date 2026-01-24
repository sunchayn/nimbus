```html
<script setup lang="ts">
/**
 * @component AppSelectContent
 * @description The container for select items, including portal, viewport, and scroll buttons.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import {
    SelectContent,
    type SelectContentEmits,
    type SelectContentProps,
    SelectPortal,
    SelectViewport,
    useForwardPropsEmits,
} from 'reka-ui';
import { type HTMLAttributes } from 'vue';
import { AppSelectScrollDownButton, AppSelectScrollUpButton } from './index';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppSelectContentProps extends SelectContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSelectContentProps>(), {
    position: 'popper',
    class: '',
});

const emits = defineEmits<SelectContentEmits>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <SelectPortal>
        <SelectContent
            v-bind="{ ...forwarded, ...$attrs }"
            :class="
                cn(
                    'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 relative z-50 max-h-96 min-w-32 overflow-hidden rounded-md border border-zinc-200 bg-white text-zinc-950 shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50',
                    position === 'popper' &&
                        'data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1',
                    props.class,
                )
            "
        >
            <AppSelectScrollUpButton />
            <SelectViewport
                :class="
                    cn(
                        'p-1',
                        position === 'popper' &&
                            'h-[--reka-select-trigger-height] w-full min-w-[--reka-select-trigger-width]',
                    )
                "
            >
                <slot />
            </SelectViewport>
            <AppSelectScrollDownButton />
        </SelectContent>
    </SelectPortal>
</template>
