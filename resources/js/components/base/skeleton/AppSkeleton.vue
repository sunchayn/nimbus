<script setup lang="ts">
/**
 * @component AppSkeleton
 * @description Placeholder block built on Nuxt UI. It is decoration, so assistive technology skips it.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { SkeletonProps } from '@nuxt/ui/components/Skeleton.vue';
import USkeleton from '@nuxt/ui/components/Skeleton.vue';

/*
 * Types & Interfaces.
 */

export interface AppSkeletonProps extends SkeletonProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSkeletonProps>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <USkeleton v-bind="forwarded" role="presentation" aria-hidden="true" />
</template>
