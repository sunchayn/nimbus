<script setup lang="ts">
/**
 * @component RouteExtractorExceptionRenderer
 * @description Renders route-specific extraction errors.
 */
import { type RouteExtractorException } from '@/interfaces/routes';
import RouteSpecificExtractorExceptionRenderer from './RouteSpecificExtractorExceptionRenderer.vue';

/*
 * Types & Interfaces.
 */

export interface AppRouteExtractorExceptionRendererProps {
    error: RouteExtractorException;
}

/*
 * Component Setup.
 */

defineProps<AppRouteExtractorExceptionRendererProps>();
</script>

<template>
    <RouteSpecificExtractorExceptionRenderer :error="error" />
</template>
