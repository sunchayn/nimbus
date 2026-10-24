<script setup lang="ts">
/**
 * @component ValueGeneratorCategoryFilters
 * @description Category tabs for filtering the value generator command palette.
 */
import { AppIcon } from '@/components/base/icons';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { useValueGeneratorStore } from '@/stores';

/*
 * Types & Interfaces.
 */

export interface ValueGeneratorCategoryFiltersProps {}

/*
 * Component Setup.
 */

defineProps<ValueGeneratorCategoryFiltersProps>();
const emits = defineEmits<{ (e: 'focus-search'): void }>();

const store = useValueGeneratorStore();

const {
    scrollContainer,
    showLeftMask,
    showRightMask,
    updateScrollMasks,
    scrollTabIntoView,
} = useTabHorizontalScroll();

/*
 * Computed & Methods.
 */

const getCategoryButtonClass = (categoryId: string) => {
    const isSelected = store.commandState.selectedCategory === categoryId;

    return isSelected
        ? 'bg-default text-highlighted'
        : 'text-muted hover:text-highlighted';
};

/**
 * Handles category tab clicks with toggle behavior.
 */
const handleCategoryClick = (event: Event, categoryId: string) => {
    const isCurrentlySelected = store.commandState.selectedCategory === categoryId;

    const newCategory = isCurrentlySelected ? null : categoryId;

    store.setSelectedCategory(newCategory);

    scrollTabIntoView(event.currentTarget as HTMLElement);
};

/**
 * Handles arrow key navigation from category tabs.
 */
const handleCategoryArrowNavigation = (event: KeyboardEvent) => {
    const isArrowKey = event.key === 'ArrowDown' || event.key === 'ArrowUp';

    if (!isArrowKey) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    emits('focus-search');
};
</script>

<template>
    <!-- Category Filters with horizontal scrolling -->
    <div class="p-2">
        <div class="relative">
            <div
                ref="scrollContainer"
                class="scrollbar-hide bg-muted flex items-center gap-1 overflow-x-auto rounded-md p-1"
                style="scrollbar-width: none; -ms-overflow-style: none"
                @scroll="updateScrollMasks"
                @keydown="handleCategoryArrowNavigation"
            >
                <button
                    v-for="category in store.categories"
                    :key="category.id"
                    tabindex="0"
                    :class="[
                        'focus-visible:ring-primary h-control-xs flex shrink-0 items-center justify-center gap-1 rounded-sm px-2 text-xs font-medium whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
                        getCategoryButtonClass(category.id),
                    ]"
                    @click="event => handleCategoryClick(event, category.id)"
                >
                    <AppIcon
                        v-if="category.icon"
                        :name="category.icon"
                        class="size-icon-xs"
                    />
                    {{ category.name }}
                </button>
            </div>

            <!-- Scroll Gradient Masks -->
            <div
                v-show="showLeftMask"
                class="from-muted via-muted/80 w-fade pointer-events-none absolute top-0 bottom-0 left-0 rounded-l-md bg-gradient-to-r to-transparent transition-opacity duration-200"
            />
            <div
                v-show="showRightMask"
                class="from-muted via-muted/80 w-fade pointer-events-none absolute top-0 right-0 bottom-0 rounded-r-md bg-gradient-to-l to-transparent transition-opacity duration-200"
            />
        </div>
    </div>
</template>
