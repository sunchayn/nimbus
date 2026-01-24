<script setup lang="ts">
/**
 * @component AppRoundIndicator
 * @description A small circular indicator used for visual status or marking.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoundIndicatorProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppRoundIndicatorProps>();
</script>

<template>
    <div
        :class="
            cn(
                props.class,
                'bg-background relative z-10 flex size-[12px] items-center justify-center rounded-full',
            )
        "
    >
        <div
            class="size-[8px] rounded-full border-[1px] border-[currentColor] bg-[radial-gradient(circle_at_bottom,currentColor_75%,rgb(255,255,255)_100%)]"
        />
        <div
            class="absolute z-[-1] size-[5px] rounded-full opacity-40 shadow-[5px_0_5px_currentColor,-5px_0_5px_currentColor]"
        />
    </div>
</template>
