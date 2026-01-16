<script setup lang="ts">
import { cn } from '@/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        data-slot="input-group"
        role="group"
        :class="
            cn(
                'group/input-group relative flex w-full items-center rounded-sm border border-zinc-200 outline-none dark:border-zinc-800 dark:bg-zinc-200/30 dark:dark:bg-zinc-800/30',
                'h-9 min-w-0 has-[>textarea]:h-auto',

                // Variants based on alignment.
                'has-[>[data-align=inline-start]]:[&>input]:pl-2',
                'has-[>[data-align=inline-end]]:[&>input]:pr-2',
                'has-[>[data-align=block-start]]:h-auto has-[>[data-align=block-start]]:flex-col has-[>[data-align=block-start]]:[&>input]:pb-3',
                'has-[>[data-align=block-end]]:h-auto has-[>[data-align=block-end]]:flex-col has-[>[data-align=block-end]]:[&>input]:pt-3',

                // Focus state.
                'has-[[data-slot=input-group-control]:focus-visible]:ring-1 has-[[data-slot=input-group-control]:focus-visible]:ring-zinc-950 dark:has-[[data-slot=input-group-control]:focus-visible]:ring-zinc-300',

                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
