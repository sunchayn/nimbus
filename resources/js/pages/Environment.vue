<script setup lang="ts">
import { AppButton } from '@/components/base/button';
import {
    EnvironmentCollectionHeader,
    EnvironmentCollectionList,
    EnvironmentCollectionRequests,
    EnvironmentVariablesEditor,
} from '@/components/domain/Environment';
import PageLayout from '@/components/layout/PageLayout.vue';
import { useEnvironmentVariablesStore } from '@/stores';
import { Layers3Icon, PlusIcon } from 'lucide-vue-next';

defineOptions({
    name: 'EnvironmentPage',
});

const environmentVariablesStore = useEnvironmentVariablesStore();
</script>

<template>
    <PageLayout title="Environments" :icon="Layers3Icon" data-testid="environment-page">
        <template #subheader-left>
            <div>
                <span class="text-sm">Global application variables</span>
            </div>
        </template>

        <template #subheader-right>
            <AppButton
                variant="ghost"
                size="xs"
                data-testid="add-collection-btn"
                @click="environmentVariablesStore.addCollection"
            >
                <PlusIcon />
                New Collection
            </AppButton>
        </template>

        <template #content>
            <div class="p-panel h-full overflow-auto">
                <div class="max-w-6xl">
                    <!-- Screen Header -->
                    <div class="mb-3">
                        <h2 class="text-xl font-semibold">Collections</h2>
                        <p class="text-subtle-foreground mb-1.5 text-sm leading-tight">
                            These are namespaces and group of global variables that can be
                            re-used across the application.
                        </p>
                        <p class="text-subtle-foreground text-xs leading-tight italic">
                            When collection sync is available, collections are shared with
                            your team as JSON files committed to the repository.
                        </p>
                    </div>

                    <div class="flex flex-col items-start gap-2 md:flex-row">
                        <!-- Sidebar: Collection List -->
                        <EnvironmentCollectionList />

                        <!-- Detail: Variable Editor + saved requests -->
                        <div class="w-full">
                            <EnvironmentVariablesEditor>
                                <template #header>
                                    <EnvironmentCollectionHeader />
                                </template>
                            </EnvironmentVariablesEditor>

                            <EnvironmentCollectionRequests />
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </PageLayout>
</template>
