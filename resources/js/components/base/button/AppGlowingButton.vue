<script setup lang="ts">
/**
 * @component AppGlowingButton
 * @description A button with a glowing animated border effect.
 */
import { AppButton } from '@/components/base/button/index';
import AppBorderBeam from '@/components/base/glow-border/AppBorderBeam.vue';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import { cn } from '@/utils/ui';
import type { ButtonProps } from '@nuxt/ui/components/Button.vue';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppGlowingButtonProps extends ButtonProps {
    duration?: number;
    beamSize?: number;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppGlowingButtonProps>(), {
    size: 'xs',
    color: 'neutral',
    variant: 'subtle',
    duration: 10,
    beamSize: 200,
});

const forwarded = useForwardedProps(props);

const buttonProps = computed(() => {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { duration, beamSize, class: _class, ...rest } = forwarded.value;

    return { ...rest, size: props.size, color: props.color, variant: props.variant };
});
</script>

<template>
    <AppButton v-bind="buttonProps" :class="cn('group relative', props.class)">
        <AppBorderBeam
            :size="beamSize"
            :duration="duration"
            :delay="0"
            :border-width="1"
            class="group-disabled:hidden"
        />
        <slot>{{ props.label }}</slot>
    </AppButton>
</template>
