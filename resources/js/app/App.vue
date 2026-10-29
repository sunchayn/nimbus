<script setup lang="ts">
/**
 * @component App
 * @description Application root that mounts the Nuxt UI provider, top bar, routed page, and global value generator.
 */
import ValueGenerator from '@/components/common/ValueGenerator/ValueGenerator.vue';
import AppTopBar from '@/components/layout/AppTopBar.vue';
import MainPageSkeleton from '@/components/layout/skeleton/MainPageSkeleton.vue';
import PageSkeleton from '@/components/layout/skeleton/PageSkeleton.vue';
import { useThemePreference } from '@/composables/ui/useThemePreference';
import { useSettingsStore } from '@/stores';
import UApp from '@nuxt/ui/components/App.vue';
import { computed, shallowRef } from 'vue';
import { useRouter } from 'vue-router';

const settingsStore = useSettingsStore();

useThemePreference(() => settingsStore.preferences.theme);

/*
 * The page is a lazy chunk, so until the router resolves it a skeleton holds the space.
 */

const router = useRouter();
const isRouterReady = shallowRef(false);

router.isReady().then(() => (isRouterReady.value = true));

const isMainPending = computed(() => {
    const path = window.location.pathname.slice(router.options.history.base.length);

    return router.resolve(path || '/').name === 'main';
});
</script>

<template>
    <UApp
        :toaster="{ position: 'bottom-right', max: 5, duration: 5000, progress: false }"
    >
        <div class="bg-muted flex h-svh w-full flex-col">
            <AppTopBar />
            <main class="min-h-0 min-w-0 flex-1">
                <template v-if="!isRouterReady">
                    <MainPageSkeleton v-if="isMainPending" />
                    <PageSkeleton v-else />
                </template>
                <router-view v-slot="{ Component, route }">
                    <keep-alive>
                        <Component :is="Component" :key="route.name" class="h-full" />
                    </keep-alive>
                </router-view>
            </main>

            <!-- The following are global components -->

            <!-- ValueGenerator provides a global menu for generating values throughout the app. -->
            <ValueGenerator />
        </div>
    </UApp>
</template>
