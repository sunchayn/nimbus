<script setup lang="ts">
/**
 * @component UserPreferencesSection
 * @description Settings section for configuring user-specific application preferences.
 */
import { AppIcon } from '@/components/base/icons';
import { AppLabel } from '@/components/base/label';
import { AppSelect } from '@/components/base/select';
import { useAppToast } from '@/components/base/toast';
import { AuthorizationType } from '@/interfaces/generated'; // eslint-disable-line @typescript-eslint/consistent-type-imports
import { RequestBodyTypeEnum } from '@/interfaces/http'; // eslint-disable-line @typescript-eslint/consistent-type-imports
import { useSettingsStore } from '@/stores';
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
    { label: 'Light', value: 'light' },
    { label: 'Dark', value: 'dark' },
    { label: 'System', value: 'system' },
];

const requestBodyTypeItems = [
    { label: 'Automatic (based on route payload)', value: -1 },
    { label: 'Empty', value: RequestBodyTypeEnum.EMPTY },
    { label: 'JSON', value: RequestBodyTypeEnum.JSON },
    { label: 'Form Data', value: RequestBodyTypeEnum.FORM_DATA },
    { label: 'Plain Text', value: RequestBodyTypeEnum.PLAIN_TEXT },
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
</script>

<template>
    <div class="gap-loose flex flex-col">
        <!-- Application Behavior -->
        <!--        <div class="grid gap-2 sm:grid-cols-12 sm:gap-8">-->
        <!--            <div class="col-span-4">-->
        <!--                <div class="space-y-2">-->
        <!--                    <div class="flex items-center space-x-2">-->
        <!--                        <SettingsIcon class="h-4 w-4" />-->
        <!--                        <h3 class="text-sm font-medium">Application Behavior</h3>-->
        <!--                    </div>-->
        <!--                    <p class="text-muted text-xs">-->
        <!--                        Configure how the application behaves on startup and during use.-->
        <!--                    </p>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--            <div class="col-span-8">-->
        <!--                <div class="rounded-lg border p-4">-->
        <!--                    <div class="space-y-4">-->
        <!--                        <div class="flex items-center justify-between">-->
        <!--                            <div class="space-y-1">-->
        <!--                                <AppLabel class="text-sm">Auto-refresh routes</AppLabel>-->
        <!--                                <p class="text-muted text-xs">-->
        <!--                                    Automatically refresh routes when the application-->
        <!--                                    starts-->
        <!--                                </p>-->
        <!--                            </div>-->
        <!--                            <AppSwitch-->
        <!--                                :model-value="preferences.autoRefreshRoutes"-->
        <!--                                @update:model-value="-->
        <!--                                    value => updatePreference('autoRefreshRoutes', value)-->
        <!--                                "-->
        <!--                            />-->
        <!--                        </div>-->

        <!--                        <div class="space-y-1">-->
        <!--                            <AppLabel class="text-sm">Maximum history logs</AppLabel>-->
        <!--                            <p class="text-muted mb-2 text-xs">-->
        <!--                                Maximum number of request logs to keep in history-->
        <!--                            </p>-->
        <!--                            <AppInput-->
        <!--                                :model-value="preferences.maxHistoryLogs.toString()"-->
        <!--                                type="numeric"-->
        <!--                                min="10"-->
        <!--                                max="1000"-->
        <!--                                class="w-24"-->
        <!--                                @update:model-value="-->
        <!--                                    value => {-->
        <!--                                        const numValue = parseInt(String(value));-->
        <!--                                        const finalValue: number = isNaN(numValue)-->
        <!--                                            ? 100-->
        <!--                                            : numValue;-->
        <!--                                        updatePreference('maxHistoryLogs', finalValue);-->
        <!--                                    }-->
        <!--                                "-->
        <!--                            />-->
        <!--                        </div>-->
        <!--                    </div>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--        </div>-->

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
                <AppSelect
                    :model-value="preferences.theme"
                    :items="themeItems"
                    class="w-32"
                    @update:model-value="
                        value =>
                            updatePreference(
                                'theme',
                                value as 'light' | 'dark' | 'system',
                            )
                    "
                />
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
