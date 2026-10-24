<script setup lang="ts">
/**
 * @component RequestAuthorizationSelector
 * @description A dropdown selector for choosing the authentication method.
 */
import { AppSelect } from '@/components/base/select';
import { AuthorizationType, type AuthorizationTypeItem } from '@/interfaces/generated';
import { useRequestStore, useSettingsStore } from '@/stores';
import { watchOnce } from '@vueuse/core';
import { computed, type ModelRef } from 'vue';

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

/*
 * Computed & Watchers.
 */

const specialIcons: Partial<Record<AuthorizationType, string>> = {
    [AuthorizationType.CurrentUser]: 'i-hugeicons-user',
    [AuthorizationType.Impersonate]: 'i-hugeicons-user-switch',
};

const items = computed(() => [
    [
        {
            type: 'label' as const,
            label: 'Special',
            class: 'label-icon-sparkles flex items-center gap-tight',
        },
        ...props.types.special.map(type => ({
            label: type.label,
            value: type.id,
            icon: specialIcons[type.id] ?? 'i-hugeicons-user',
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
