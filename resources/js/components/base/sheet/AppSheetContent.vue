<script setup lang="ts">
/**
 * @component AppSheetContent
 * @description The content area for a sheet, including overlay and transition animations.
 */
import { cn } from '@/utils/ui';
import { X } from 'lucide-vue-next';
import {
    DialogClose,
    DialogContent,
    type DialogContentEmits,
    type DialogContentProps,
    DialogOverlay,
    DialogPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';
import { type SheetVariants, sheetVariants } from './index';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppSheetContentProps extends DialogContentProps {
    class?: HTMLAttributes['class'];
    side?: SheetVariants['side'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetContentProps>();
const emits = defineEmits<DialogContentEmits>();

const delegatedProps = computed(() => {
    /* eslint-disable @typescript-eslint/no-unused-vars */
    const { class: _, side, ...delegated } = props;
    /* eslint-enable @typescript-eslint/no-unused-vars */

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DialogPortal>
        <DialogOverlay
            class="data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/80"
        />
        <DialogContent
            :class="cn(sheetVariants({ side }), props.class)"
            v-bind="{ ...forwarded, ...$attrs }"
        >
            <slot />

            <DialogClose
                class="absolute top-4 right-4 rounded-sm opacity-70 ring-offset-white transition-opacity hover:opacity-100 focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 focus:outline-none disabled:pointer-events-none data-[state=open]:bg-zinc-100 dark:ring-offset-zinc-950 dark:focus:ring-zinc-300 dark:data-[state=open]:bg-zinc-800"
            >
                <X class="h-4 w-4" />
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
