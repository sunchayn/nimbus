<script setup lang="ts">
/**
 * @component AppIcon
 * @description Renders an offline HugeIcons icon through Nuxt UI, for example `i-hugeicons-cancel-01`.
 */
import UIcon from '@nuxt/ui/components/Icon.vue';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppIconProps {
    /**
     * Icon name. Write it as a full literal string so the build can bundle it.
     */
    name: string;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

defineProps<AppIconProps>();
</script>

<template>
    <UIcon :name="name" :class="$props.class" />
</template>
