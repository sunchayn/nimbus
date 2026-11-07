<script setup lang="ts">
/**
 * @component UserPreferencesSection
 * @description Settings section for configuring user-specific application preferences.
 */
import { AppIcon } from '@/components/base/icons';
import { AppInput } from '@/components/base/input';
import { AppLabel } from '@/components/base/label';
import { AppRadioCardGroup } from '@/components/base/radio-card';
import { AppSelect } from '@/components/base/select';
import { useAppToast } from '@/components/base/toast';
import ThemePreview from '@/components/domain/Settings/ThemePreview.vue';
import { AuthorizationType } from '@/interfaces/generated'; // eslint-disable-line @typescript-eslint/consistent-type-imports
import { RequestBodyTypeEnum } from '@/interfaces/http'; // eslint-disable-line @typescript-eslint/consistent-type-imports
import { useSettingsStore } from '@/stores';
import type { UserPreferences } from '@/stores/core/useSettingsStore';
import { computed } from 'vue';

/*
 * Stores.
 */

const settingsStore = useSettingsStore();
const toast = useAppToast();

/*
 * Computed & Methods.
 */

const preferences = computed(() => settingsStore.preferences);

const themeItems = [
    {
        label: 'System',
        value: 'system',
        description:
            'Follows your device setting and switches between the light and dark themes automatically.',
    },
    {
        label: 'Light',
        value: 'light',
        description:
            'The standard Nimbus light theme with full color contrast and brightness.',
    },
    {
        label: 'Dark',
        value: 'dark',
        description:
            'The standard Nimbus dark theme with full color contrast and brightness on a dark background.',
    },
    {
        label: 'Solarized Light',
        value: 'solarized-light',
        description: 'Warm cream surfaces with deep teal text, based on Solarized.',
    },
    {
        label: 'Solarized Dark',
        value: 'solarized-dark',
        description: 'Deep teal surfaces with soft cream text, based on Solarized.',
    },
    {
        label: 'Sakura',
        value: 'sakura',
        description:
            'Soft pink surfaces with dark plum text, inspired by cherry blossoms.',
    },
];

const requestBodyTypeItems = [
    { label: 'Automatic (based on route payload)', value: -1 },
    { label: 'Empty', value: RequestBodyTypeEnum.EMPTY },
    { label: 'JSON', value: RequestBodyTypeEnum.JSON },
    { label: 'Form Data', value: RequestBodyTypeEnum.FORM_DATA },
    { label: 'Form URL Encoded', value: RequestBodyTypeEnum.URLENCODED },
    { label: 'Binary', value: RequestBodyTypeEnum.BINARY },
    { label: 'Plain Text', value: RequestBodyTypeEnum.PLAIN_TEXT },
    { label: 'HTML', value: RequestBodyTypeEnum.HTML },
    { label: 'XML', value: RequestBodyTypeEnum.XML },
];

const authorizationTypeItems = [
    { label: 'None', value: AuthorizationType.None },
    { label: 'Bearer Token', value: AuthorizationType.Bearer },
    { label: 'Basic Auth', value: AuthorizationType.Basic },
    { label: 'Current User', value: AuthorizationType.CurrentUser },
    { label: 'Impersonate User', value: AuthorizationType.Impersonate },
];

const updatePreference = <K extends keyof typeof preferences.value>(
    key: K,
    value: (typeof preferences.value)[K],
) => {
    settingsStore.updatePreference(key, value);

    toast.success('Settings updated', {
        description: 'Your preferences have been saved successfully.',
        duration: 3000,
    });
};

const updateMaxHistoryLogs = (value: string | number) => {
    const logs = Math.trunc(Number(value));

    if (logs >= 10 && logs <= 1000) {
        updatePreference('maxHistoryLogs', logs);
    }
};
</script>

<template>
    <div class="gap-loose flex flex-col">
        <!-- Request History -->
        <div class="gap-base sm:gap-loose grid sm:grid-cols-3">
            <div class="gap-tight flex flex-col">
                <div class="gap-base flex items-center">
                    <AppIcon name="i-hugeicons-history" class="size-icon-md" />
                    <h3 class="text-sm font-medium">Request History</h3>
                </div>
                <p class="text-muted text-xs">Control how many past requests are kept.</p>
            </div>
            <div
                class="border-default gap-tight p-card flex flex-col rounded-md border sm:col-span-2"
            >
                <AppLabel>Maximum history logs</AppLabel>
                <p class="text-muted text-xs">
                    Maximum number of request logs to keep in history, from 10 to 1000.
                </p>
                <AppInput
                    :model-value="preferences.maxHistoryLogs"
                    type="number"
                    :min="10"
                    :max="1000"
                    class="w-24"
                    data-testid="max-history-logs-input"
                    @update:model-value="updateMaxHistoryLogs"
                />
            </div>
        </div>

        <!-- Appearance -->
        <div class="gap-base sm:gap-loose grid sm:grid-cols-3">
            <div class="gap-tight flex flex-col">
                <div class="gap-base flex items-center">
                    <AppIcon name="i-hugeicons-paint-board" class="size-icon-md" />
                    <h3 class="text-sm font-medium">Appearance</h3>
                </div>
                <p class="text-muted text-xs">
                    Customize the visual appearance of the application.
                </p>
            </div>
            <div
                class="border-default gap-tight p-card flex flex-col rounded-md border sm:col-span-2"
            >
                <AppLabel>Theme</AppLabel>
                <p class="text-muted text-xs">Choose your preferred color scheme</p>
                <AppRadioCardGroup
                    label="Theme"
                    class="mt-base"
                    :items="themeItems"
                    :model-value="preferences.theme"
                    @update:model-value="
                        value =>
                            settingsStore.updatePreference(
                                'theme',
                                value as UserPreferences['theme'],
                            )
                    "
                >
                    <template #preview="{ item }">
                        <ThemePreview :theme="item.value as UserPreferences['theme']" />
                    </template>
                </AppRadioCardGroup>
            </div>
        </div>

        <!-- Request Defaults -->
        <div class="gap-base sm:gap-loose grid sm:grid-cols-3">
            <div class="gap-tight flex flex-col">
                <div class="gap-base flex items-center">
                    <AppIcon name="i-hugeicons-flash" class="size-icon-md" />
                    <h3 class="text-sm font-medium">Request Defaults</h3>
                </div>
                <p class="text-muted text-xs">Set default values for new requests.</p>
            </div>
            <div
                class="border-default flex flex-col divide-y rounded-md border sm:col-span-2"
            >
                <div class="gap-tight p-card flex flex-col">
                    <AppLabel>Default request body type</AppLabel>
                    <p class="text-muted text-xs">
                        Default content type for request bodies
                    </p>
                    <AppSelect
                        :model-value="preferences.defaultRequestBodyType"
                        :items="requestBodyTypeItems"
                        class="w-fit"
                        @update:model-value="
                            value =>
                                updatePreference(
                                    'defaultRequestBodyType',
                                    value as RequestBodyTypeEnum,
                                )
                        "
                    />
                </div>

                <div class="gap-tight p-card flex flex-col">
                    <AppLabel>Default authorization type</AppLabel>
                    <p class="text-muted text-xs">
                        Default authorization method for new requests
                    </p>
                    <AppSelect
                        :model-value="preferences.defaultAuthorizationType"
                        :items="authorizationTypeItems"
                        class="w-40"
                        @update:model-value="
                            value =>
                                updatePreference(
                                    'defaultAuthorizationType',
                                    value as AuthorizationType,
                                )
                        "
                    />
                </div>
            </div>
        </div>
    </div>
</template>
