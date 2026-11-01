<script setup lang="ts">
/**
 * @component EnvironmentList
 * @description Renders the list of all environments with active state and the enable toggle.
 */
import { AppBadge } from '@/components/base/badge';
import { AppSwitch } from '@/components/base/switch';
import { EnvironmentSource } from '@/interfaces/generated';
import { useEnvironmentVariablesStore } from '@/stores';
import type { Environment } from '@/stores/core/useEnvironmentVariablesStore';

/*
 * Stores & Dependencies.
 */

const environmentVariablesStore = useEnvironmentVariablesStore();

const ORIGIN_LABELS: Record<Environment['source'], string> = {
    current: 'Default',
    config: 'Pre-configured',
    custom: 'Custom',
};

const describe = (environment: Environment) => {
    const count = environment.variables.filter(
        variable => variable.key.trim() !== '',
    ).length;

    return `${ORIGIN_LABELS[environment.source]} · ${count} ${count === 1 ? 'variable' : 'variables'}`;
};
</script>

<template>
    <div class="gap-base flex w-full max-w-xs flex-col">
        <div
            v-for="environment in environmentVariablesStore.environments"
            :key="environment.id"
            class="p-panel hover:bg-muted w-full cursor-pointer rounded-sm border text-left text-sm"
            data-testid="environment-item"
            :class="{
                'bg-muted':
                    environmentVariablesStore.editingEnvironment.id === environment.id,
            }"
            @click="environmentVariablesStore.edit(environment.id)"
        >
            <div class="gap-base flex items-center">
                <div
                    class="gap-tight min-w-0 flex-1 leading-tight"
                    :class="{ 'opacity-60': !environment.enabled }"
                >
                    <div class="gap-tight flex items-center">
                        <span class="truncate font-medium">{{ environment.name }}</span>
                    </div>
                    <span class="text-muted block text-xs" data-testid="environment-meta">
                        {{ describe(environment) }}
                    </span>
                </div>
                <div class="gap-base flex items-center">
                    <AppBadge
                        v-if="
                            environmentVariablesStore.activeEnvironment.id ===
                            environment.id
                        "
                        variant="outline"
                        color="success"
                        data-testid="environment-active-badge"
                    >
                        Active
                    </AppBadge>
                    <AppSwitch
                        v-if="environment.source !== EnvironmentSource.Current"
                        :model-value="environment.enabled"
                        size="sm"
                        :aria-label="`Enable ${environment.name}`"
                        data-testid="environment-enabled-toggle"
                        @click.stop
                        @update:model-value="
                            (value: boolean | undefined) =>
                                environmentVariablesStore.setEnabled(
                                    environment.id,
                                    value ?? false,
                                )
                        "
                    />
                </div>
            </div>
        </div>
    </div>
</template>
