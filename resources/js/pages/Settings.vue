<script setup lang="ts">
import { AppButton } from '@/components/base/button';
import { CheckCircleIcon, RefreshCwIcon, SettingsIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import {
    AppDialog,
    AppDialogContent,
    AppDialogDescription,
    AppDialogFooter,
    AppDialogHeader,
    AppDialogTitle,
} from '@/components/base/dialog';
import {
    AppPopover,
    AppPopoverContent,
    AppPopoverTrigger,
} from '@/components/base/popover';
import CopyButton from '@/components/common/CopyButton.vue';
import ConfigReadonlySection from '@/components/domain/Settings/ConfigReadonlySection.vue';
import UserPreferencesSection from '@/components/domain/Settings/UserPreferencesSection.vue';
import PageLayout from '@/components/layout/PageLayout.vue';
import { useSettingsStore } from '@/stores';
import { useClipboard } from '@vueuse/core';

defineOptions({
    name: 'SettingsPage',
});

const VERSION = 'v0.6.0-alpha'; // x-release-please-version

/*
 * Stores.
 */

const settingsStore = useSettingsStore();

/*
 * State.
 */

const lastResetTime = ref<Date | null>(null);
const showResetDialog = ref(false);

/*
 * Methods.
 */

const showResetConfirmation = () => {
    showResetDialog.value = true;
};

const confirmReset = () => {
    settingsStore.resetPreferences();
    lastResetTime.value = new Date();
    showResetDialog.value = false;
    toast.success('Settings reset', {
        description: 'All preferences have been restored to their default values.',
        duration: 3000,
    });
};

const cancelReset = () => {
    showResetDialog.value = false;
};

const { copy, copied } = useClipboard();

const copyPublishCommand = async () => {
    copy('php artisan vendor:publish --tag=nimbus-config');
};
</script>

<template>
    <div>
        <PageLayout title="Settings" :icon="SettingsIcon">
            <!-- Header Actions -->
            <template #header-actions>
                <div v-if="lastResetTime" class="text-subtle-foreground mr-3 text-xs">
                    <CheckCircleIcon class="mr-1 inline h-3 w-3" />
                    Reset {{ lastResetTime.toLocaleTimeString() }}
                </div>
            </template>

            <!-- Subheader Left -->
            <template #subheader-left>
                <div>
                    <span class="text-sm">Application Configuration</span>
                </div>
            </template>

            <!-- Subheader Right -->
            <template #subheader-right>
                <AppButton variant="ghost" size="xs" @click="showResetConfirmation">
                    <RefreshCwIcon class="mr-1 h-3 w-3" />
                    Reset
                </AppButton>
            </template>

            <!-- Content -->
            <template #content>
                <div class="h-full overflow-auto">
                    <div class="p-panel">
                        <div class="max-w-6xl space-y-12">
                            <!-- Server Configuration Section -->
                            <div>
                                <div class="mb-6">
                                    <h2 class="text-xl font-semibold">
                                        Server Configuration
                                    </h2>
                                    <p class="text-subtle-foreground text-sm">
                                        These values are read from your Laravel
                                        application's
                                        <code
                                            class="rounded bg-zinc-100 px-1 dark:bg-zinc-900"
                                        >
                                            config/nimbus.php
                                        </code>
                                        file. They control how the API routes are
                                        structured, what base URL is used, and which
                                        headers are automatically included with requests.
                                    </p>
                                    <small class="text-subtle-foreground italic">
                                        To customize these settings,
                                        <AppPopover>
                                            <AppPopoverTrigger
                                                as="span"
                                                class="inline-block cursor-pointer text-zinc-600 underline hover:text-blue-800 dark:text-zinc-400 dark:hover:text-blue-200"
                                            >
                                                publish the config file
                                            </AppPopoverTrigger>
                                            <AppPopoverContent class="w-[450px]">
                                                <div class="space-y-3">
                                                    <div>
                                                        <h4 class="text-sm font-bold">
                                                            Publish Configuration
                                                        </h4>
                                                        <p class="text-xs">
                                                            Run this command to publish
                                                            the package configuration file
                                                            to your application's config
                                                            directory:
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="relative flex items-center space-x-2 rounded bg-zinc-100 p-3 font-mono text-xs dark:bg-zinc-900"
                                                    >
                                                        <code class="flex-1">
                                                            php artisan vendor:publish
                                                            --tag=nimbus-config
                                                        </code>
                                                        <CopyButton
                                                            :on-click="copyPublishCommand"
                                                            :copied="copied"
                                                        />
                                                    </div>
                                                    <p
                                                        class="text-subtle-foreground text-xs"
                                                    >
                                                        This will create
                                                        <code
                                                            class="rounded bg-zinc-100 px-1 dark:bg-zinc-900"
                                                        >
                                                            config/nimbus.php
                                                        </code>
                                                        in your project root.
                                                    </p>
                                                </div>
                                            </AppPopoverContent>
                                        </AppPopover>
                                        and edit the values directly.
                                    </small>
                                    <div class="my-2 text-xs">
                                        <div
                                            class="bg-subtle inline-flex items-center space-x-2 rounded px-1.5 py-0.5 font-mono"
                                        >
                                            <span>{{ VERSION }}</span>
                                            <CopyButton
                                                :on-click="() => copy(VERSION)"
                                                :copied="copied"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <ConfigReadonlySection />
                            </div>

                            <!-- Section Divider -->
                            <div
                                class="my-8 border-t border-zinc-200 dark:border-zinc-800"
                            />

                            <!-- User Preferences Section -->
                            <div>
                                <div class="mb-6">
                                    <h2 class="text-xl font-semibold">
                                        User Preferences
                                    </h2>
                                    <p class="text-subtle-foreground text-sm">
                                        Personal settings that are saved locally and
                                        customize your experience with the application.
                                    </p>
                                </div>
                                <UserPreferencesSection />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </PageLayout>

        <!-- Reset Confirmation Dialog -->
        <AppDialog v-model:open="showResetDialog">
            <AppDialogContent>
                <AppDialogHeader>
                    <AppDialogTitle>Reset Settings</AppDialogTitle>
                    <AppDialogDescription>
                        Are you sure you want to reset all your preferences to their
                        default values? This action cannot be undone.
                    </AppDialogDescription>
                </AppDialogHeader>
                <AppDialogFooter>
                    <AppButton size="xs" variant="outline" @click="cancelReset">
                        Cancel
                    </AppButton>
                    <AppButton size="xs" @click="confirmReset">Reset Settings</AppButton>
                </AppDialogFooter>
            </AppDialogContent>
        </AppDialog>
    </div>
</template>
