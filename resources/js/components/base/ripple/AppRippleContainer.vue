<script setup lang="ts">
/**
 * @component AppRippleContainer
 * @description A container component that adds a ripple effect background to its content.
 */
import AppRipple from '@/components/base/ripple/AppRipple.vue';

/*
 * Types & Interfaces.
 */

export interface AppRippleContainerProps {}
</script>

<template>
    <div class="relative">
        <slot />
        <AppRipple />
    </div>
</template>
