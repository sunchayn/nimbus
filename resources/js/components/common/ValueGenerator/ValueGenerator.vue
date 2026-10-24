<script setup lang="ts">
/**
 * @component ValueGenerator
 * @description Global command palette for value generation across the application.
 */
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { useValueGeneratorStore } from '@/stores';
import { ref, useTemplateRef, watch } from 'vue';
import ValueGeneratorCategoryFilters from './ValueGeneratorCategoryFilters.vue';
import ValueGeneratorFooter from './ValueGeneratorFooter.vue';
import ValueGeneratorGeneratorList from './ValueGeneratorGeneratorList.vue';

/*
 * Types & Interfaces.
 */

export interface ValueGeneratorEmits {
    (e: 'valueGenerated', value: string | number | bigint): void;
}

/*
 * Component Setup.
 */

const emits = defineEmits<ValueGeneratorEmits>();

const store = useValueGeneratorStore();
const { restoreScrollPosition } = useTabHorizontalScroll();
const generatorList = useTemplateRef<{ focusInput: () => void }>('generatorList');

/*
 * Computed & Methods.
 */

/**
 * The command position relative to the input that opened it.
 *
 * It is calculated when the command opens, because the input rect is not reactive.
 */
const commandPosition = ref<Record<string, string | undefined>>({});

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
    const margin = 8;
    const commandWidth = Math.min(448, window.innerWidth - margin * 2);
    const width = `${commandWidth}px`;

    if (!inputRef) {
        return { top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width };
    }

    const rect = inputRef.getBoundingClientRect();
    const commandHeight = 400; // Approximate command height

    // Keep the command inside the viewport horizontally.
    const left = Math.max(
        margin,
        Math.min(rect.left, window.innerWidth - commandWidth - margin),
    );

    // Position below the input if there's space, otherwise above and anchored to the input.
    const shouldPositionBelow = rect.bottom + commandHeight < window.innerHeight;

    if (shouldPositionBelow) {
        return {
            top: `${rect.bottom + 4}px`,
            left: `${left}px`,
            transform: 'none',
            width,
        };
    }

    return {
        bottom: `${window.innerHeight - rect.top + 4}px`,
        left: `${left}px`,
        transform: 'none',
        width,
    };
};

const changeFocusToTheCommandSearchBox = (): void => generatorList.value?.focusInput();

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

        commandPosition.value = calculateCommandPosition(store.currentInputRef);

        await restoreScrollPosition();

        changeFocusToTheCommandSearchBox();
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="store.isCommandOpen"
            class="z-overlay fixed inset-0"
            data-testid="value-generator-overlay"
            @click="store.closeCommand"
        >
            <div
                class="absolute"
                :style="commandPosition"
                data-testid="value-generator-command"
                @click.stop
            >
                <div
                    class="border-default shadow-overlay overflow-hidden rounded-lg border"
                    data-ValueGenerator-focus-hook
                    data-testid="value-generator-focus-hook"
                    @keydown.escape="store.closeCommand"
                >
                    <ValueGeneratorGeneratorList
                        ref="generatorList"
                        @generator-selected="onGeneratorSelected"
                    >
                        <template #header>
                            <ValueGeneratorCategoryFilters
                                @focus-search="changeFocusToTheCommandSearchBox"
                            />
                        </template>
                        <template #footer>
                            <ValueGeneratorFooter />
                        </template>
                    </ValueGeneratorGeneratorList>
                </div>
            </div>
        </div>
    </Teleport>
</template>
