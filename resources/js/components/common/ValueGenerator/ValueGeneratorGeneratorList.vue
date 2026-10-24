<script setup lang="ts">
/**
 * @component ValueGeneratorGeneratorList
 * @description The command palette listing the available generators, with search and recent items.
 */
import { AppCommand } from '@/components/base/command';
import { type ValueGenerator } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores';
import { computed, reactive, useTemplateRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ValueGeneratorGeneratorListProps {}

export interface ValueGeneratorGeneratorListEmits {
    (e: 'generator-selected', generatorId: string): void;
}

/*
 * Component Setup.
 */

defineProps<ValueGeneratorGeneratorListProps>();
const emits = defineEmits<ValueGeneratorGeneratorListEmits>();

const store = useValueGeneratorStore();

/*
 * State.
 */

const filterState = reactive({ search: '' });
const command = useTemplateRef<{ focusInput: () => void }>('command');

store.restoreCommandState({ filterState });

watch(
    () => filterState.search,
    newSearch => {
        store.setSearchQuery(newSearch || '');
    },
);

/*
 * Computed & Methods.
 */

const focusInput = (): void => command.value?.focusInput();

defineExpose({ focusInput });

const toItem = (generator: ValueGenerator) => ({
    label: generator.name,
    icon: generator.icon ?? 'i-hugeicons-sparkles',
    generator,
    onSelect: () => {
        filterState.search = '';
        emits('generator-selected', generator.id);
    },
});

const recentGenerators = computed(() => {
    const search = filterState.search.toLowerCase();

    return store.recentGenerators.filter((generator: ValueGenerator) =>
        generator.name.toLowerCase().includes(search),
    );
});

const groups = computed(() => {
    const filtered: ValueGenerator[] = store.filteredGenerators ?? [];

    const categoryGroups = (store.categories ?? []).map(category => ({
        id: category.id,
        label: category.name,
        ignoreFilter: true,
        items: filtered
            .filter(generator => generator.category.id === category.id)
            .map(toItem),
    }));

    return [
        {
            id: 'recent',
            label: 'Recent',
            ignoreFilter: true,
            items: recentGenerators.value.map(toItem),
        },
        ...categoryGroups,
    ].filter(group => group.items.length > 0);
});
</script>

<template>
    <AppCommand
        ref="command"
        v-model:search-term="filterState.search"
        :groups="groups"
        placeholder="Search generators..."
        :ui="{ content: 'max-h-64 overflow-y-auto' }"
    >
        <template #empty>No generators found. Try a different search query.</template>
        <template #header>
            <slot name="header" />
        </template>
        <template #footer>
            <slot name="footer" />
        </template>
    </AppCommand>
</template>
