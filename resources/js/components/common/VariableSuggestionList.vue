<script setup lang="ts">
/**
 * @component VariableSuggestionList
 * @description Floating list that suggests variables after {{ and dynamic variables after {$.
 */
import type { VariableSuggestion } from '@/utils/variableSuggestions';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface VariableSuggestionListProps {
    items: VariableSuggestion[];
    activeIndex: number;
    position: { top: number; left: number; width: number };
}

/*
 * Component Setup.
 */

const props = defineProps<VariableSuggestionListProps>();

const emit = defineEmits<{
    select: [suggestion: VariableSuggestion];
}>();

/*
 * Computed & Methods.
 */

const groups = computed(() =>
    (['Variables', 'Dynamic'] as const)
        .map(name => ({
            name,
            entries: props.items
                .map((item, index) => ({ item, index }))
                .filter(({ item }) => item.group === name),
        }))
        .filter(group => group.entries.length > 0),
);
</script>

<template>
    <Teleport to="body">
        <div
            class="bg-default border-default z-overlay py-tight fixed max-h-60 min-w-56 overflow-y-auto rounded-md border text-xs shadow-md"
            :style="{
                top: `${position.top + 2}px`,
                left: `${position.left}px`,
                minWidth: `${Math.max(position.width, 224)}px`,
            }"
            role="listbox"
            data-testid="variable-suggestions"
            @mousedown.prevent
        >
            <template v-for="group in groups" :key="group.name">
                <div class="px-base text-muted py-tight font-medium">
                    {{ group.name }}
                </div>
                <button
                    v-for="{ item, index } in group.entries"
                    :key="item.name"
                    type="button"
                    role="option"
                    :aria-selected="index === activeIndex"
                    class="px-base hover:bg-elevated gap-base py-tight flex w-full items-center text-left"
                    :class="{ 'bg-elevated': index === activeIndex }"
                    data-testid="variable-suggestion"
                    @click="emit('select', item)"
                >
                    <span
                        v-if="item.group === 'Dynamic'"
                        class="bg-inverted/20 px-tight rounded-sm font-mono"
                    >
                        $
                    </span>
                    <span class="font-mono">{{ item.name }}</span>
                    <span v-if="item.sample" class="text-muted ms-auto truncate">
                        {{ item.sample }}
                    </span>
                </button>
            </template>
        </div>
    </Teleport>
</template>
