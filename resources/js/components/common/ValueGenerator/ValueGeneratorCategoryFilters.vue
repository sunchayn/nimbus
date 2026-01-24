<script setup lang="ts">
/**
 * @component ValueGeneratorCategoryFilters
 * @description Category tabs for filtering the value generator command palette.
 */
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { useValueGeneratorStore } from '@/stores';

/*
 * Types & Interfaces.
 */

export interface AppValueGeneratorCategoryFiltersProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppValueGeneratorCategoryFiltersProps>();

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
        ? 'bg-background text-foreground shadow'
        : 'text-muted-foreground hover:text-foreground';
};

const changeFocusToTheCommandSearchBox = () => {
    const commandInput = document.querySelector(
        '[data-slot="command-input"]',
    ) as HTMLInputElement;

    commandInput?.focus();
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

    // Move the focus to the command search box for better UX.
    changeFocusToTheCommandSearchBox();
};
</script>

<template>
    <!-- Category Filters with horizontal scrolling -->
    <div class="p-2">
        <div class="relative">
            <div
                ref="scrollContainer"
                class="scrollbar-hide bg-subtle-background flex items-center gap-1 overflow-x-auto rounded-lg p-1"
                style="scrollbar-width: none; -ms-overflow-style: none"
                @scroll="updateScrollMasks"
                @keydown="handleCategoryArrowNavigation"
            >
                <button
                    v-for="category in store.categories"
                    :key="category.id"
                    tabindex="0"
                    :class="[
                        'flex flex-shrink-0 items-center justify-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-offset-background transition-all focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
                        getCategoryButtonClass(category.id),
                    ]"
                    @click="event => handleCategoryClick(event, category.id)"
                >
                    <component :is="category.icon" class="size-3" />
                    {{ category.name }}
                </button>
            </div>

            <!-- Scroll Gradient Masks -->
            <div
                v-show="showLeftMask"
                class="pointer-events-none absolute top-0 bottom-0 left-0 w-8 rounded-l-lg bg-gradient-to-r from-subtle-background via-subtle-background/80 to-transparent transition-opacity duration-200"
            />
            <div
                v-show="showRightMask"
                class="pointer-events-none absolute top-0 right-0 bottom-0 w-8 rounded-r-lg bg-gradient-to-l from-subtle-background via-subtle-background/80 to-transparent transition-opacity duration-200"
            />
        </div>
    </div>
</template>
