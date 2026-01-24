<script setup lang="ts">
/**
 * @component AppInputGroupAddon
 * @description An addon element (text or icon) for an input group.
 */
import { cn } from '@/utils';
import type { HTMLAttributes } from 'vue';
import type { InputGroupVariants } from '.';
import { inputGroupAddonVariants } from '.';

/*
 * Types & Interfaces.
 */

export interface AppInputGroupAddonProps {
    align?: InputGroupVariants['align'];
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppInputGroupAddonProps>(), {
    align: 'inline-start',
    class: undefined,
});

/*
 * Computed & Methods.
 */

function handleInputGroupAddonClick(e: MouseEvent) {
    const currentTarget = e.currentTarget as HTMLElement | null;
    const target = e.target as HTMLElement | null;
    if (target && target.closest('button')) {
        return;
    }
    if (currentTarget && currentTarget?.parentElement) {
        currentTarget.parentElement?.querySelector('input')?.focus();
    }
}
</script>

<template>
    <div
        role="group"
        data-slot="input-group-addon"
        :data-align="props.align"
        :class="cn(inputGroupAddonVariants({ align: props.align }), props.class)"
        @click="handleInputGroupAddonClick"
    >
        <slot />
    </div>
</template>
