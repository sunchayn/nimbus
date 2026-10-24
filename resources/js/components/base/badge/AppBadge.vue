<script setup lang="ts">
/**
 * @component AppBadge
 * @description Small status or label indicator built on Nuxt UI.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { BadgeProps } from '@nuxt/ui/components/Badge.vue';
import UBadge from '@nuxt/ui/components/Badge.vue';

/*
 * Types & Interfaces.
 */

export interface AppBadgeProps extends BadgeProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppBadgeProps>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UBadge v-bind="forwarded">
        <slot />
    </UBadge>
</template>
