<script setup lang="ts">
/**
 * @component DiagnosticsDialog
 * @description Runs the Nimbus doctor checks on the server and lists the results.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { AppIcon } from '@/components/base/icons';
import { useConfigStore } from '@/stores';
import axios from 'axios';
import { shallowRef } from 'vue';

/*
 * Types & Interfaces.
 */

type DiagnosticStatus = 'ok' | 'warning' | 'failed';

interface DiagnosticCheck {
    name: string;
    status: DiagnosticStatus;
    message: string;
}

/*
 * Stores.
 */

const configStore = useConfigStore();

/*
 * State.
 */

const open = shallowRef(false);
const loading = shallowRef(false);
const checks = shallowRef<DiagnosticCheck[]>([]);
const errorMessage = shallowRef<string | null>(null);

const icons: Record<DiagnosticStatus, string> = {
    ok: 'i-hugeicons-checkmark-circle-02',
    warning: 'i-hugeicons-alert-02',
    failed: 'i-hugeicons-cancel-circle',
};

const iconColors: Record<DiagnosticStatus, string> = {
    ok: 'text-success',
    warning: 'text-warning',
    failed: 'text-error',
};

/*
 * Methods.
 */

const run = async () => {
    open.value = true;
    loading.value = true;
    errorMessage.value = null;

    try {
        const { data } = await axios.get<DiagnosticCheck[]>(
            configStore.appBasePath + '/api/doctor',
        );

        checks.value = data;
    } catch (error) {
        checks.value = [];
        errorMessage.value =
            error instanceof Error ? error.message : 'The diagnostics could not run.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <AppButton
        variant="ghost"
        size="xs"
        icon="i-hugeicons-stethoscope"
        label="Diagnostics"
        data-testid="diagnostics-button"
        @click="run"
    />

    <AppDialog v-model:open="open" title="Diagnostics">
        <template #body>
            <div
                v-if="loading"
                class="gap-base py-lg text-muted flex items-center text-sm"
                data-testid="diagnostics-loading"
            >
                <AppIcon
                    name="i-hugeicons-loading-03"
                    class="size-icon-md animate-spin"
                />
                <span>Running the checks.</span>
            </div>
            <p
                v-else-if="errorMessage"
                class="text-error text-sm"
                data-testid="diagnostics-error"
            >
                {{ errorMessage }}
            </p>
            <ul v-else class="flex flex-col">
                <li
                    v-for="check in checks"
                    :key="check.name"
                    class="gap-base py-base border-default grid grid-cols-[auto_1fr] items-start border-b first:pt-0 last:border-b-0 last:pb-0"
                    :data-testid="`diagnostic-${check.status}`"
                >
                    <AppIcon
                        :name="icons[check.status]"
                        class="size-icon-sm"
                        :class="iconColors[check.status]"
                    />
                    <div class="gap-hair flex min-w-0 flex-col">
                        <span class="text-sm leading-none font-medium">
                            {{ check.name }}
                        </span>
                        <!-- eslint-disable vue/no-v-html -->
                        <span
                            class="text-muted text-xs leading-4 break-words whitespace-pre-line"
                            v-html="check.message"
                        ></span>
                        <!-- eslint-enable vue/no-v-html -->
                    </div>
                </li>
            </ul>
        </template>
    </AppDialog>
</template>
