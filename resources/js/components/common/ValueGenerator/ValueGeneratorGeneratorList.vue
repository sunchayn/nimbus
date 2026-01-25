<script setup lang="ts">
/**
 * @component ValueGeneratorGeneratorList
 * @description The list of available generators for the value generator command palette.
 */
import {
    AppCommandEmpty,
    AppCommandGroup,
    AppCommandItem,
    AppCommandList,
} from '@/components/base/command';
import { type ValueGenerator } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores';
import { SparklesIcon } from 'lucide-vue-next';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppValueGeneratorGeneratorListProps {}

export interface AppValueGeneratorGeneratorListEmits {
    (e: 'generator-selected', generatorId: string): void;
}

/*
 * Component Setup.
 */

defineProps<AppValueGeneratorGeneratorListProps>();
const emits = defineEmits<AppValueGeneratorGeneratorListEmits>();

const store = useValueGeneratorStore();

/*
 * Computed & Methods.
 */

const hasRecentGenerators = computed(() => store.recentGenerators.length > 0);

const getGeneratorIcon = (generator: ValueGenerator) => generator.icon || SparklesIcon;

const emitGeneratorSelectedEvent = (generatorId: string) => {
    emits('generator-selected', generatorId);
};

/**
 * Checks if a category has any generators in the filtered results.
 */
const categoryHasGenerators = (categoryId: string) => {
    if (!store.filteredGenerators) {
        return false;
    }

    return store.filteredGenerators.some(
        (generator: ValueGenerator) => generator.category.id === categoryId,
    );
};

/**
 * Gets all generators for a specific category from filtered results.
 */
const getGeneratorsForCategory = (categoryId: string) => {
    if (!store.filteredGenerators) {
        return [];
    }

    return store.filteredGenerators.filter(
        (generator: ValueGenerator) => generator.category.id === categoryId,
    );
};
</script>

<template>
    <AppCommandList class="max-h-64 overflow-y-auto">
        <AppCommandEmpty class="p-3 text-left">
            No generators found. Try a different search query.
        </AppCommandEmpty>

        <!-- Recent Generators Section -->
        <AppCommandGroup v-if="hasRecentGenerators" heading="Recent">
            <AppCommandItem
                v-for="generator in store.recentGenerators"
                :key="generator.id"
                :value="generator.name"
                @select="emitGeneratorSelectedEvent(generator.id)"
            >
                <component
                    :is="getGeneratorIcon(generator)"
                    class="text-subtle-foreground size-4 flex-shrink-0"
                />
                <span class="font-medium">{{ generator.name }}</span>
            </AppCommandItem>
        </AppCommandGroup>

        <!-- Category Groups -->
        <template v-for="category in store.categories || []" :key="category.id">
            <AppCommandGroup
                v-if="categoryHasGenerators(category.id)"
                :heading="category.name"
            >
                <AppCommandItem
                    v-for="generator in getGeneratorsForCategory(category.id)"
                    :key="generator.id"
                    :value="generator.name"
                    @select="emitGeneratorSelectedEvent(generator.id)"
                >
                    <component
                        :is="getGeneratorIcon(generator)"
                        class="size-4 flex-shrink-0 text-zinc-500"
                    />
                    <span class="font-medium">{{ generator.name }}</span>
                </AppCommandItem>
            </AppCommandGroup>
        </template>
    </AppCommandList>
</template>
