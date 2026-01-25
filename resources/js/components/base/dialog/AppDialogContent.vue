<script setup lang="ts">
/**
 * @component AppDialogContent
 * @description The main content area of a dialog, including its overlay and portal.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import { X } from 'lucide-vue-next';
import type { DialogContentEmits, DialogContentProps } from 'reka-ui';
import { DialogClose, DialogContent, DialogPortal, useForwardPropsEmits } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import AppDialogOverlay from './AppDialogOverlay.vue';

/*
 * Types & Interfaces.
 */

export interface AppDialogContentProps extends DialogContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogContentProps>();
const emits = defineEmits<DialogContentEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DialogPortal>
        <AppDialogOverlay />
        <DialogContent
            data-slot="dialog-content"
            v-bind="forwarded"
            :class="
                cn(
                    'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-[50%] left-[50%] z-50 grid w-full max-w-[calc(100%-2rem)] translate-x-[-50%] translate-y-[-50%] gap-2.5 rounded-sm border border-zinc-200 bg-white p-3 shadow-sm duration-200 sm:max-w-lg dark:border-zinc-800 dark:bg-zinc-950',
                    'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-[50%] left-[50%] z-50 grid w-full max-w-[calc(100%-2rem)] translate-x-[-50%] translate-y-[-50%] gap-2.5 rounded-sm border border-zinc-200 bg-white p-3 shadow-sm duration-200 sm:max-w-lg dark:border-zinc-800 dark:bg-zinc-950',
                    props.class,
                )
            "
        >
            <slot />

            <DialogClose
                class="absolute top-4 right-4 rounded-xs opacity-70 ring-offset-white transition-opacity hover:opacity-100 focus-visible:ring-1 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none data-[state=open]:bg-zinc-100 data-[state=open]:text-zinc-500 dark:ring-offset-zinc-950 dark:focus-visible:ring-zinc-300 dark:data-[state=open]:bg-zinc-800 dark:data-[state=open]:text-zinc-400 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
            >
                <X />
                <span class="sr-only">Close</span>
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
