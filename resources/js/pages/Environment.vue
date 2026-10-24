<script setup lang="ts">
/**
 * @component EnvironmentPage
 * @description Page for managing global environment collections and their variables.
 */
import { AppButton } from '@/components/base/button';
import {
    EnvironmentCollectionHeader,
    EnvironmentCollectionList,
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
                <span class="text-sm">Global application variables</span>
            </div>
        </template>

        <template #subheader-right>
            <AppButton
                variant="ghost"
                size="xs"
                icon="i-hugeicons-plus-sign"
                label="New Collection"
                data-testid="add-collection-btn"
                @click="environmentVariablesStore.addCollection"
            />
        </template>

        <template #content>
            <div class="p-panel h-full overflow-auto">
                <div class="gap-loose flex max-w-6xl flex-col">
                    <!-- Screen Header -->
                    <div>
                        <h2 class="text-base font-semibold">Collections</h2>
                        <div class="gap-tight flex flex-col">
                            <p class="text-muted text-sm leading-tight">
                                These are namespaces and group of global variables that
                                can be re-used across the application.
                            </p>
                            <p class="text-muted text-xs leading-tight italic">
                                Note: environment configuration is not shared between
                                nimbus installations.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-start gap-2 md:flex-row">
                        <!-- Sidebar with the collection list -->
                        <EnvironmentCollectionList />

                        <!-- Detail with the variable editor -->
                        <EnvironmentVariablesEditor>
                            <template #header>
                                <EnvironmentCollectionHeader />
                            </template>
                        </EnvironmentVariablesEditor>
                    </div>
                </div>
            </div>
        </template>
    </PageLayout>
</template>
