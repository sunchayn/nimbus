<script setup lang="ts">
/**
 * @component AppButton
 * @description Standard button component with multiple variants and sizes.
 */
import { cn } from '@/utils/ui';
import { Primitive, type PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { type ButtonVariants, buttonVariants } from './index';

/*
 * Types & Interfaces.
 */

export interface AppButtonProps extends PrimitiveProps {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppButtonProps>(), {
    as: 'button',
    variant: 'default',
    size: 'default',
    class: '',
});
</script>

<template>
    <Primitive
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </Primitive>
</template>
