<script setup lang="ts">
/**
 * @component AppInputGroupButton
 * @description A button intended for use within an input group.
 */
import { AppButton } from '@/components/base/button';
import { cn } from '@/utils';
import type { InputGroupButtonProps } from '.';
import { inputGroupButtonVariants } from '.';

/*
 * Types & Interfaces.
 */

export interface AppInputGroupButtonComponentProps extends InputGroupButtonProps {}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppInputGroupButtonComponentProps>(), {
    size: 'xs',
    variant: 'ghost',
});
</script>

<template>
    <AppButton
        :data-size="props.size"
        :variant="props.variant"
        :class="cn(inputGroupButtonVariants({ size: props.size }), props.class)"
    >
        <slot />
    </AppButton>
</template>
