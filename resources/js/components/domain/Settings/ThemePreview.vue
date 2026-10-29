<script setup lang="ts">
/**
 * @component ThemePreview
 * @description Preview of a theme choice. The system choice splits the drawing diagonally between light and dark.
 */
import ThemeDrawing from './ThemeDrawing.vue';

defineProps<{ theme: 'light' | 'dark' | 'system' }>();
</script>

<template>
    <div class="relative aspect-[16/9] w-full">
        <ThemeDrawing :mode="theme === 'dark' ? 'dark' : 'light'" />
        <div
            v-if="theme === 'system'"
            class="absolute inset-0 [clip-path:polygon(100%_0,100%_100%,0_100%)]"
        >
            <ThemeDrawing mode="dark" />
        </div>
    </div>
</template>
