<script lang="ts" setup>
import { inject } from 'vue';
import type { ToasterProps } from 'vue-sonner';
import { Toaster as Sonner } from 'vue-sonner';

defineOptions({
    name: 'AppSonner',
});

const props = defineProps<ToasterProps>();

const appTheme = inject<'dark' | 'light'>('theme');
</script>

<template>
    <Sonner
        class="toaster group"
        v-bind="props"
        :style="{
            '--normal-bg': 'var(--color-popover)',
            '--normal-text': 'var(--color-popover-foreground)',
            '--normal-border': 'var(--color-border)',
        }"
        :theme="appTheme"
    />
</template>
