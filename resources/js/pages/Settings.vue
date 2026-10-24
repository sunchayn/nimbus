<script setup lang="ts">
/**
 * @component SettingsPage
 * @description Page for user preferences and the read-only package configuration.
 */
import { AppButton } from '@/components/base/button';
import { AppIcon } from '@/components/base/icons';
import { useAppToast } from '@/components/base/toast';
import { ref, shallowRef } from 'vue';

import { AppDialog } from '@/components/base/dialog';
import { AppPopover } from '@/components/base/popover';
import CopyButton from '@/components/common/CopyButton.vue';
import ConfigReadonlySection from '@/components/domain/Settings/ConfigReadonlySection.vue';
import UserPreferencesSection from '@/components/domain/Settings/UserPreferencesSection.vue';
import PageLayout from '@/components/layout/PageLayout.vue';
import { useSettingsStore } from '@/stores';
import { useClipboard } from '@vueuse/core';

defineOptions({
    name: 'SettingsPage',
});

const VERSION = 'v0.8.0-alpha'; // x-release-please-version

/*
 * Stores.
 */

const settingsStore = useSettingsStore();

/*
 * State.
 */

const lastResetTime = ref<Date | null>(null);
const showResetDialog = shallowRef(false);
const toast = useAppToast();

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
        <PageLayout title="Settings" icon="i-hugeicons-settings-01">
            <!-- Header Actions -->
            <template #header-actions>
                <div
                    v-if="lastResetTime"
                    class="text-muted mr-loose gap-tight flex items-center text-xs"
                >
                    <AppIcon
                        name="i-hugeicons-checkmark-circle-02"
                        class="size-icon-xs"
                    />
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
                <AppButton
                    variant="ghost"
                    size="xs"
                    icon="i-hugeicons-refresh"
                    label="Reset"
                    @click="showResetConfirmation"
                />
            </template>

            <!-- Content -->
            <template #content>
                <div class="h-full overflow-auto">
                    <div class="p-panel">
                        <div class="gap-loose flex max-w-6xl flex-col">
                            <!-- Server Configuration Section -->
                            <div class="gap-loose flex flex-col">
                                <div class="gap-tight flex flex-col">
                                    <h2 class="text-base font-semibold">
                                        Server Configuration
                                    </h2>
                                    <p class="text-muted max-w-5xl text-sm text-balance">
                                        These values are read from your Laravel
                                        application's
                                        <code
                                            class="bg-elevated inline-block rounded-sm px-1"
                                        >
                                            config/nimbus.php
                                        </code>
                                        file. They control how the API routes are
                                        structured, what base URL is used, and which
                                        headers are automatically included with requests.
                                    </p>
                                    <p class="text-muted text-xs italic">
                                        To customize these settings,
                                        <AppPopover>
                                            <button
                                                type="button"
                                                class="text-muted hover:text-highlighted inline-block cursor-pointer underline"
                                            >
                                                publish the config file
                                            </button>
                                            <template #content>
                                                <div class="gap-base flex flex-col">
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
                                                        class="bg-muted gap-base p-base relative flex items-center rounded-md font-mono text-xs"
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
                                                    <p class="text-muted text-xs">
                                                        This will create
                                                        <code
                                                            class="bg-elevated inline-block rounded-sm px-1"
                                                        >
                                                            config/nimbus.php
                                                        </code>
                                                        in your project root.
                                                    </p>
                                                </div>
                                            </template>
                                        </AppPopover>
                                        and edit the values directly.
                                    </p>
                                    <div class="text-xs">
                                        <div
                                            class="bg-muted gap-base inline-flex items-center rounded-sm p-1 pl-2 font-mono"
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
                            <div class="border-default border-t" />

                            <!-- User Preferences Section -->
                            <div class="gap-loose flex flex-col">
                                <div class="gap-tight flex flex-col">
                                    <h2 class="text-base font-semibold">
                                        User Preferences
                                    </h2>
                                    <p class="text-muted max-w-5xl text-sm text-balance">
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
        <AppDialog
            v-model:open="showResetDialog"
            title="Reset Settings"
            description="Are you sure you want to reset all your preferences to their default values? This action cannot be undone."
        >
            <template #footer>
                <AppButton size="sm" variant="outline" @click="cancelReset">
                    Cancel
                </AppButton>
                <AppButton size="sm" @click="confirmReset">Reset Settings</AppButton>
            </template>
        </AppDialog>
    </div>
</template>
