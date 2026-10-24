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

export interface RouteExtractorExceptionRendererProps {
    error: RouteExtractorException;
}

/*
 * Component Setup.
 */

defineProps<RouteExtractorExceptionRendererProps>();
</script>

<template>
    <RouteSpecificExtractorExceptionRenderer :error="error" />
</template>
