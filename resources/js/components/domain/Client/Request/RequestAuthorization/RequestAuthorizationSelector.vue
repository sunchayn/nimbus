<script setup lang="ts">
/**
 * @component RequestAuthorizationSelector
 * @description A dropdown selector for choosing the authentication method.
 */
import { AppSelect } from '@/components/base/select';
import { AuthorizationType, type AuthorizationTypeItem } from '@/interfaces/generated';
import {
    useEnvironmentVariablesStore,
    useRequestStore,
    useSettingsStore,
} from '@/stores';
import { HOST_ONLY_AUTHORIZATION_TYPES } from '@/utils/environment';
import { watchOnce } from '@vueuse/core';
import { computed, type ModelRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestAuthorizationSelectorProps {
    types: {
        special: readonly AuthorizationTypeItem[];
        traditional: readonly AuthorizationTypeItem[];
    };
}

/*
 * Component Setup.
 */

const props = defineProps<RequestAuthorizationSelectorProps>();

const model: ModelRef<AuthorizationType> = defineModel<AuthorizationType>({
    default: () => AuthorizationType.CurrentUser,
});

/*
 * Stores.
 */

const requestStore = useRequestStore();
const settingsStore = useSettingsStore();
const environmentStore = useEnvironmentVariablesStore();

/*
 * Computed & Watchers.
 */

const specialIcons: Partial<Record<AuthorizationType, string>> = {
    [AuthorizationType.CurrentUser]: 'i-hugeicons-user',
    [AuthorizationType.Impersonate]: 'i-hugeicons-user-switch',
};

const isDisabled = (type: AuthorizationType) =>
    !environmentStore.isCurrentHost && HOST_ONLY_AUTHORIZATION_TYPES.includes(type);

const items = computed(() => [
    [
        {
            type: 'label' as const,
            label: environmentStore.isCurrentHost
                ? 'Special'
                : 'Special (current host only)',
            class: 'label-icon-sparkles flex items-center gap-tight',
        },
        ...props.types.special.map(type => ({
            label: type.label,
            value: type.id,
            icon: specialIcons[type.id] ?? 'i-hugeicons-user',
            disabled: isDisabled(type.id),
        })),
    ],
    [
        { type: 'label' as const, label: 'Other' },
        ...props.types.traditional.map(type => ({
            label: type.label,
            value: type.id,
        })),
    ],
]);

const pendingRequestData = computed(() => requestStore.pendingRequestData);

watchOnce(
    pendingRequestData,
    newValue => {
        model.value =
            newValue?.authorization.type ??
            settingsStore.preferences.defaultAuthorizationType;
    },
    { deep: true },
);

// Covers tabs and defaults that still hold a host only type while on another environment.
watch(
    [model, () => environmentStore.isCurrentHost],
    () => {
        if (isDisabled(model.value)) {
            model.value = AuthorizationType.None;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex items-center">
        <span class="text-muted w-30 text-xs">Authorization Type:</span>
        <AppSelect
            v-model="model"
            :items="items"
            variant="ghost"
            size="sm"
            class="min-w-30 rounded-none"
        />
    </div>
</template>
