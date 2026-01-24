<script setup lang="ts">
/**
 * @component ValueGenerator
 * @description Global command palette for value generation across the application.
 */
import { AppCommand, AppCommandInput } from '@/components/base/command';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { useValueGeneratorStore } from '@/stores';
import { computed, watch } from 'vue';
import ValueGeneratorCategoryFilters from './ValueGeneratorCategoryFilters.vue';
import ValueGeneratorCommandKeepAlive from './ValueGeneratorCommandKeepAlive.vue';
import ValueGeneratorFooter from './ValueGeneratorFooter.vue';
import ValueGeneratorGeneratorList from './ValueGeneratorGeneratorList.vue';

/*
 * Types & Interfaces.
 */

export interface AppValueGeneratorEmits {
    (e: 'valueGenerated', value: string | number | bigint): void;
}

/*
 * Component Setup.
 */

const emits = defineEmits<AppValueGeneratorEmits>();

const store = useValueGeneratorStore();
const { restoreScrollPosition } = useTabHorizontalScroll();

/*
 * Computed & Methods.
 */

/**
 * Gets the command position relative to the input that opened it.
 */
const commandPosition = computed(() => calculateCommandPosition(store.currentInputRef));

/**
 * Creates an input event to trigger change handlers
 */
const createInputEvent = (): Event => {
    return new Event('input', { bubbles: true });
};

/**
 * Calculates the position for the command palette relative to the input element
 */
const calculateCommandPosition = (inputRef: HTMLElement | null) => {
    if (!inputRef) {
        return { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' };
    }

    const rect = inputRef.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const commandHeight = 400; // Approximate command height

    // Position below the input if there's space, otherwise above
    const shouldPositionBelow = rect.bottom + commandHeight < viewportHeight;
    const top = shouldPositionBelow ? rect.bottom + 4 : rect.top - commandHeight - 4;

    return {
        top: `${top}px`,
        left: `${rect.left}px`,
        transform: 'none',
    };
};

const changeFocusToTheCommandSearchBox = () => {
    const commandInput = document.querySelector(
        '[data-slot="command-input"]',
    ) as HTMLInputElement;

    commandInput?.focus();
};

const onGeneratorSelected = (generatorId: string) => {
    const value = store.generateValue(generatorId);

    if (store.currentInputRef) {
        (store.currentInputRef as HTMLInputElement).value = String(value);
        store.currentInputRef.dispatchEvent(createInputEvent());
    }

    emits('valueGenerated', value);
    store.closeCommand();
};

/*
 * Watchers.
 */

/**
 * Watches command open state to handle initialization when command opens.
 *
 * When command opens, it needs to restore the previous scroll position and
 * focus the command input for immediate keyboard navigation.
 */
watch(
    () => store.isCommandOpen,
    async open => {
        if (!open) {
            return;
        }

        await restoreScrollPosition();

        // As the command is opened, focus the command input for immediate keyboard navigation.
        changeFocusToTheCommandSearchBox();
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="store.isCommandOpen"
            class="fixed inset-0 z-50"
            data-testid="value-generator-overlay"
            @click="store.closeCommand"
        >
            <div
                class="absolute w-full max-w-md"
                :style="commandPosition"
                data-testid="value-generator-command"
                @click.stop
            >
                <AppCommand
                    class="rounded-lg border shadow-md"
                    data-ValueGenerator-focus-hook
                    data-testid="value-generator-focus-hook"
                    @keydown.escape="store.closeCommand"
                >
                    <AppCommandInput
                        placeholder="Search generators..."
                        class="border-0 focus:ring-0"
                    />

                    <ValueGeneratorCommandKeepAlive />

                    <ValueGeneratorCategoryFilters />

                    <ValueGeneratorGeneratorList
                        @generator-selected="onGeneratorSelected"
                    />

                    <ValueGeneratorFooter />
                </AppCommand>
            </div>
        </div>
    </Teleport>
</template>
