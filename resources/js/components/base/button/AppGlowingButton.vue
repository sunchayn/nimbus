<script setup lang="ts">
/**
 * @component AppGlowingButton
 * @description A button with a glowing animated border effect.
 */
import { type AppButtonProps } from '@/components/base/button/AppButton.vue';
import { AppButton } from '@/components/base/button/index';
import AppBorderBeam from '@/components/base/glow-border/AppBorderBeam.vue';
import { cn } from '@/utils/ui';

/*
 * Types & Interfaces.
 */

export interface AppGlowingButtonProps extends AppButtonProps {
    duration?: number;
    beamSize?: number;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppGlowingButtonProps>(), {
    size: 'xs',
    variant: 'secondary',
    duration: 10,
    beamSize: 200,
});
</script>

<template>
    <AppButton :size="size" :variant="variant" :class="cn('group relative', props.class)">
        <AppBorderBeam
            :size="beamSize"
            :duration="duration"
            :delay="0"
            :border-width="1"
            class="group-disabled:hidden"
        />
        <slot></slot>
    </AppButton>
</template>
