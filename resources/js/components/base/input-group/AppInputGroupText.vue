<script setup lang="ts">
import { cn } from '@/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <span
        :class="
            cn(
                'flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4',
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>
