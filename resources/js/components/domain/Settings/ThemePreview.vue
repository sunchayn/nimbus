<script setup lang="ts">
/**
 * @component ThemePreview
 * @description Preview of a theme choice. The system choice splits the drawing diagonally between light and dark.
 */
import { NAMED_THEMES, type ThemePreference } from '@/composables/ui/useThemePreference';
import { computed } from 'vue';
import ThemeDrawing from './ThemeDrawing.vue';

const props = defineProps<{ theme: ThemePreference }>();

const named = computed(() => NAMED_THEMES[props.theme]);
const mode = computed(() =>
    props.theme === 'dark' || named.value?.dark ? 'dark' : 'light',
);
</script>

<template>
    <div class="relative aspect-[16/9] w-full">
        <ThemeDrawing :mode="mode" :theme="named ? theme : undefined" />
        <div
            v-if="theme === 'system'"
            class="absolute inset-0 [clip-path:polygon(100%_0,100%_100%,0_100%)]"
        >
            <ThemeDrawing mode="dark" />
        </div>
    </div>
</template>
