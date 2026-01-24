<script setup lang="ts">
/**
 * @component UserPreferencesSection
 * @description Settings section for configuring user-specific application preferences.
 */
import { AppLabel } from '@/components/base/label';
import {
    AppSelect,
    AppSelectContent,
    AppSelectItem,
    AppSelectTrigger,
    AppSelectValue,
} from '@/components/base/select';
import { AuthorizationType } from '@/interfaces/generated'; // eslint-disable-line @typescript-eslint/consistent-type-imports
import { RequestBodyTypeEnum } from '@/interfaces/http'; // eslint-disable-line @typescript-eslint/consistent-type-imports
import { useSettingsStore } from '@/stores';
import { PaletteIcon, ZapIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

/*
 * Stores.
 */

const settingsStore = useSettingsStore();

/*
 * Computed & Methods.
 */

const preferences = computed(() => settingsStore.preferences);

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
    <div class="space-y-8">
        <!-- Application Behavior -->
        <!--        <div class="grid gap-2 sm:grid-cols-12 sm:gap-8">-->
        <!--            <div class="col-span-4">-->
        <!--                <div class="space-y-2">-->
        <!--                    <div class="flex items-center space-x-2">-->
        <!--                        <SettingsIcon class="h-4 w-4" />-->
        <!--                        <h3 class="text-sm font-medium">Application Behavior</h3>-->
        <!--                    </div>-->
        <!--                    <p class="text-muted-foreground text-xs">-->
        <!--                        Configure how the application behaves on startup and during use.-->
        <!--                    </p>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--            <div class="col-span-8">-->
        <!--                <div class="rounded-lg border p-3.5">-->
        <!--                    <div class="space-y-4">-->
        <!--                        <div class="flex items-center justify-between">-->
        <!--                            <div class="space-y-1">-->
        <!--                                <AppLabel class="text-sm">Auto-refresh routes</AppLabel>-->
        <!--                                <p class="text-muted-foreground text-xs">-->
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
        <!--                            <p class="text-muted-foreground mb-2 text-xs">-->
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

        <!-- Divider -->
        <div class="border-t border-zinc-200 dark:border-zinc-800"></div>

        <!-- Appearance -->
        <div class="grid gap-2 sm:grid-cols-12 sm:gap-8">
            <div class="col-span-4">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <PaletteIcon class="h-4 w-4" />
                        <h3 class="text-sm font-medium">Appearance</h3>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Customize the visual appearance of the application.
                    </p>
                </div>
            </div>
            <div class="col-span-8">
                <div class="rounded-lg border p-3.5">
                    <div class="space-y-1">
                        <AppLabel class="text-sm">Theme</AppLabel>
                        <p class="text-muted-foreground mb-2 text-xs">
                            Choose your preferred color scheme
                        </p>
                        <AppSelect
                            :model-value="preferences.theme"
                            @update:model-value="
                                value =>
                                    updatePreference(
                                        'theme',
                                        value as 'light' | 'dark' | 'system',
                                    )
                            "
                        >
                            <AppSelectTrigger class="w-32">
                                <AppSelectValue />
                            </AppSelectTrigger>
                            <AppSelectContent>
                                <AppSelectItem value="light">Light</AppSelectItem>
                                <AppSelectItem value="dark">Dark</AppSelectItem>
                                <AppSelectItem value="system">System</AppSelectItem>
                            </AppSelectContent>
                        </AppSelect>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-border border-t"></div>

        <!-- Request Defaults -->
        <div class="grid gap-2 sm:grid-cols-12 sm:gap-8">
            <div class="col-span-4">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <ZapIcon class="h-4 w-4" />
                        <h3 class="text-sm font-medium">Request Defaults</h3>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Set default values for new requests.
                    </p>
                </div>
            </div>
            <div class="col-span-8">
                <div class="rounded-lg border p-3.5">
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <AppLabel class="text-sm">Default request body type</AppLabel>
                            <p class="text-muted-foreground mb-2 text-xs">
                                Default content type for request bodies
                            </p>
                            <AppSelect
                                :model-value="preferences.defaultRequestBodyType"
                                @update:model-value="
                                    value =>
                                        updatePreference(
                                            'defaultRequestBodyType',
                                            value as RequestBodyTypeEnum,
                                        )
                                "
                            >
                                <AppSelectTrigger>
                                    <AppSelectValue />
                                </AppSelectTrigger>
                                <AppSelectContent>
                                    <AppSelectItem :value="-1">
                                        Automatic (based on route payload)&nbsp;
                                    </AppSelectItem>
                                    <AppSelectItem :value="RequestBodyTypeEnum.EMPTY">
                                        Empty
                                    </AppSelectItem>
                                    <AppSelectItem :value="RequestBodyTypeEnum.JSON">
                                        JSON
                                    </AppSelectItem>
                                    <AppSelectItem :value="RequestBodyTypeEnum.FORM_DATA">
                                        Form Data
                                    </AppSelectItem>
                                    <AppSelectItem
                                        :value="RequestBodyTypeEnum.PLAIN_TEXT"
                                    >
                                        Plain Text
                                    </AppSelectItem>
                                </AppSelectContent>
                            </AppSelect>
                        </div>

                        <div class="space-y-1">
                            <AppLabel class="text-sm">
                                Default authorization type
                            </AppLabel>
                            <p class="text-muted-foreground mb-2 text-xs">
                                Default authorization method for new requests
                            </p>
                            <AppSelect
                                :model-value="preferences.defaultAuthorizationType"
                                @update:model-value="
                                    value =>
                                        updatePreference(
                                            'defaultAuthorizationType',
                                            value as AuthorizationType,
                                        )
                                "
                            >
                                <AppSelectTrigger class="w-40">
                                    <AppSelectValue />
                                </AppSelectTrigger>
                                <AppSelectContent>
                                    <AppSelectItem :value="AuthorizationType.None">
                                        None
                                    </AppSelectItem>
                                    <AppSelectItem :value="AuthorizationType.Bearer">
                                        Bearer Token
                                    </AppSelectItem>
                                    <AppSelectItem :value="AuthorizationType.Basic">
                                        Basic Auth
                                    </AppSelectItem>
                                    <AppSelectItem :value="AuthorizationType.CurrentUser">
                                        Current User
                                    </AppSelectItem>
                                    <AppSelectItem :value="AuthorizationType.Impersonate">
                                        Impersonate User
                                    </AppSelectItem>
                                </AppSelectContent>
                            </AppSelect>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
