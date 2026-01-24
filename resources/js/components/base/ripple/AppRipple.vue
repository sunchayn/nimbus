<script setup lang="ts">
/**
 * @component AppRipple
 * @description An animated ripple effect background component.
 */
import AppRippleCircle from '@/components/base/ripple/AppRippleCircle.vue';

/*
 * Types & Interfaces.
 */

export interface AppRippleProps {
    baseCircleSize?: number;
    baseCircleOpacity?: number;
    spaceBetweenCircle?: number;
    circleOpacityDowngradeRatio?: number;
    circleClass?: string;
    waveSpeed?: number;
    numberOfCircles?: number;
}

/*
 * Component Setup.
 */

withDefaults(defineProps<AppRippleProps>(), {
    baseCircleSize: 210,
    baseCircleOpacity: 0.24,
    circleOpacityDowngradeRatio: 0.03,
    waveSpeed: 80,
    spaceBetweenCircle: 70,
    numberOfCircles: 7,
    circleClass: '',
});
</script>

<template>
    <div class="absolute inset-0">
        <AppRippleCircle
            v-for="index in numberOfCircles"
            :key="index"
            :opacity="baseCircleOpacity - index * circleOpacityDowngradeRatio"
            :size="baseCircleSize + index * spaceBetweenCircle"
            :animation-delay="index * waveSpeed"
            :border-style="index === numberOfCircles - 1 ? 'dashed' : 'solid'"
            :class="circleClass"
        />
    </div>
</template>
