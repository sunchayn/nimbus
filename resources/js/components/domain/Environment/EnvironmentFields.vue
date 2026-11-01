<script setup lang="ts">
/**
 * @component EnvironmentFields
 * @description Fixed fields of the environment being edited. Config and current host environments are read only.
 */
import { AppInput } from '@/components/base/input';
import { EnvironmentSource } from '@/interfaces/generated';
import { useEnvironmentVariablesStore } from '@/stores';
import { isValidBaseUrl } from '@/utils/environment';
import { computed, shallowRef, watch } from 'vue';

/*
 * Stores & Dependencies.
 */

const environmentVariablesStore = useEnvironmentVariablesStore();

/*
 * State.
 */

const baseUrl = shallowRef('');
const hasError = shallowRef(false);

/*
 * Computed & Methods.
 */

const environment = computed(() => environmentVariablesStore.editingEnvironment);
const isReadonly = computed(() => environment.value.source !== EnvironmentSource.Custom);

const commit = () => {
    if (isReadonly.value) {
        return;
    }

    hasError.value = !isValidBaseUrl(baseUrl.value);

    if (!hasError.value) {
        environmentVariablesStore.setBaseUrl(environment.value.id, baseUrl.value);
    }
};

watch(
    () => [environment.value.id, environment.value.baseUrl],
    () => {
        baseUrl.value = environment.value.baseUrl;
        hasError.value = false;
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="p-panel gap-tight flex flex-col border-b"
        data-testid="environment-fields"
    >
        <label class="text-muted text-xs" for="environment-base-url">Base URL</label>
        <AppInput
            id="environment-base-url"
            v-model="baseUrl"
            size="sm"
            placeholder="https://staging.example.com"
            :disabled="isReadonly"
            :color="hasError ? 'error' : undefined"
            data-testid="environment-base-url"
            @blur="commit"
            @keydown.enter="commit"
        />
        <p v-if="hasError" class="text-error text-xs" data-testid="base-url-error">
            Enter an absolute http or https URL.
        </p>
        <p v-else-if="isReadonly" class="text-muted text-xs" data-testid="base-url-note">
            This value is set in the config file (config/nimbus.php) and cannot be changed
            here.
        </p>
    </div>
</template>
