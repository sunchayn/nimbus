<script setup lang="ts">
import { AppSidebarProvider } from '@/components/base/sidebar';
import { AppSonner } from '@/components/base/sonner';
import ValueGenerator from '@/components/common/ValueGenerator/ValueGenerator.vue';
import ScreenNavigationSidebar from '@/components/layout/ScreenNavigationSidebar.vue';
import { useCollectionSync } from '@/composables/data/useCollectionSync';
import { useSettingsStore } from '@/stores';
import { onMounted, provide, ref, watch } from 'vue';

const settingsStore = useSettingsStore();
const collectionSync = useCollectionSync();

const currentTheme = ref('');

function applyTheme(preference: 'light' | 'dark' | 'system') {
    if (preference === 'system') {
        const isDark =
            window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches;

        preference = isDark ? 'dark' : 'light';
    }

    currentTheme.value = preference;

    if (preference === 'dark') {
        document.documentElement.classList.add('dark');

        return;
    }

    document.documentElement.classList.remove('dark');
}

let systemMedia: MediaQueryList | null = null;

function handleSystemChange(e: MediaQueryListEvent) {
    if (settingsStore.preferences.theme !== 'system') {
        return;
    }

    applyTheme(e.matches ? 'dark' : 'light');
}

onMounted(() => {
    void collectionSync.init();

    applyTheme(settingsStore.preferences.theme);

    // Listen to system changes only when preference is system.
    systemMedia = window.matchMedia
        ? window.matchMedia('(prefers-color-scheme: dark)')
        : null;

    if (systemMedia) {
        // Modern browsers
        if (typeof systemMedia.addEventListener === 'function') {
            systemMedia.addEventListener('change', handleSystemChange);
        } else if (typeof systemMedia.addListener === 'function') {
            // Legacy Safari
            systemMedia.addListener(handleSystemChange);
        }
    }
});

watch(
    () => settingsStore.preferences.theme,
    (theme: 'light' | 'dark' | 'system') => {
        applyTheme(theme);
    },
);

provide('theme', currentTheme);
</script>

<template>
    <AppSidebarProvider>
        <ScreenNavigationSidebar />
        <main
            class="min-h-full max-w-[calc(100vw_-_var(--sidebar-width-icon)_-_1px)] flex-1"
        >
            <router-view v-slot="{ Component, route }">
                <keep-alive>
                    <Component :is="Component" :key="route.name" class="h-full" />
                </keep-alive>
            </router-view>
        </main>

        <!-- The following are global components -->

        <!-- ValueGenerator: Provides a global menu for generating values throughout the app -->
        <ValueGenerator />

        <!-- AppSonner: Handles toast notifications globally -->
        <AppSonner />
    </AppSidebarProvider>
</template>
