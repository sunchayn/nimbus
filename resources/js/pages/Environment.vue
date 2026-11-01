<script setup lang="ts">
/**
 * @component EnvironmentPage
 * @description Page for managing environments, their base URLs and their variables.
 */
import { AppButton } from '@/components/base/button';
import {
    EnvironmentHeader,
    EnvironmentList,
    EnvironmentVariablesEditor,
} from '@/components/domain/Environment';
import PageLayout from '@/components/layout/PageLayout.vue';
import { useEnvironmentVariablesStore } from '@/stores';

defineOptions({
    name: 'EnvironmentPage',
});

const environmentVariablesStore = useEnvironmentVariablesStore();
</script>

<template>
    <PageLayout
        title="Environments"
        icon="i-hugeicons-all-bookmark"
        data-testid="environment-page"
    >
        <template #subheader-left>
            <div>
                <span class="text-sm">Targets and global variables</span>
            </div>
        </template>

        <template #subheader-right>
            <AppButton
                variant="ghost"
                size="xs"
                icon="i-hugeicons-plus-sign"
                label="Add environment"
                data-testid="add-environment-btn"
                @click="environmentVariablesStore.addEnvironment"
            />
        </template>

        <template #content>
            <div class="p-panel h-full overflow-auto">
                <div class="gap-loose flex max-w-6xl flex-col">
                    <!-- Screen Header -->
                    <div>
                        <h2 class="text-base font-semibold">Environments</h2>
                        <div class="gap-tight flex flex-col">
                            <p class="text-muted text-sm leading-tight">
                                An environment is a target that requests are sent to. Each
                                one has a base URL and its own variables. The current host
                                is always available by default.
                            </p>
                            <p class="text-muted text-xs leading-tight italic">
                                Note: environment variables and custom environments are
                                not shared between nimbus installations.
                            </p>
                        </div>
                    </div>

                    <div class="gap-base flex flex-col items-start md:flex-row">
                        <!-- Sidebar with the envs list -->
                        <EnvironmentList />

                        <!-- Detail with the variable editor -->
                        <EnvironmentVariablesEditor>
                            <template #header>
                                <EnvironmentHeader />
                            </template>
                        </EnvironmentVariablesEditor>
                    </div>
                </div>
            </div>
        </template>
    </PageLayout>
</template>
