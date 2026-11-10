import { useEnvironmentVariablesStore } from '@/stores';
import {
    applySuggestion,
    findOpenPlaceholder,
    getVariableSuggestions,
    type OpenPlaceholder,
    type VariableSuggestion,
} from '@/utils/variableSuggestions';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    type Ref,
    shallowRef,
} from 'vue';

export interface UseVariableSuggestionsResult {
    isOpen: Readonly<Ref<boolean>>;
    items: Readonly<Ref<VariableSuggestion[]>>;
    activeIndex: Readonly<Ref<number>>;
    position: Readonly<Ref<{ top: number; left: number; width: number }>>;
    select: (suggestion: VariableSuggestion) => void;
}

/**
 * Opens a suggestion list when the user types {{ or {$ in a text input.
 *
 * @param getInput - Returns the native input element.
 * @param model - The text the input edits.
 */
export const useVariableSuggestions = (
    getInput: () => HTMLInputElement | null,
    model: Ref<string>,
): UseVariableSuggestionsResult => {
    const environmentVariablesStore = useEnvironmentVariablesStore();

    const items = shallowRef<VariableSuggestion[]>([]);
    const activeIndex = shallowRef(0);
    const position = shallowRef({ top: 0, left: 0, width: 0 });
    const placeholder = shallowRef<OpenPlaceholder | null>(null);

    const isOpen = computed(() => items.value.length > 0);

    const close = () => {
        items.value = [];
        placeholder.value = null;
    };

    const refresh = () => {
        const input = getInput();

        if (!input) {
            return;
        }

        const found = findOpenPlaceholder(input.value, input.selectionStart ?? 0);
        const suggestions = found
            ? getVariableSuggestions(
                  found.kind,
                  found.query,
                  environmentVariablesStore.variableNames,
              )
            : [];

        if (!found || suggestions.length === 0) {
            close();

            return;
        }

        const rect = input.getBoundingClientRect();

        position.value = { top: rect.bottom, left: rect.left, width: rect.width };
        placeholder.value = found;
        items.value = suggestions;
        activeIndex.value = 0;
    };

    const select = (suggestion: VariableSuggestion) => {
        const input = getInput();

        if (!input || !placeholder.value) {
            return;
        }

        const result = applySuggestion(
            input.value,
            input.selectionStart ?? 0,
            placeholder.value,
            suggestion,
        );

        model.value = result.text;
        close();

        nextTick(() => {
            input.focus();
            input.setSelectionRange(result.caret, result.caret);
        });
    };

    const handleKeydown = (event: KeyboardEvent) => {
        if (!isOpen.value) {
            return;
        }

        const total = items.value.length;

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const step = event.key === 'ArrowDown' ? 1 : -1;

            activeIndex.value = (activeIndex.value + step + total) % total;
        } else if (event.key === 'Enter' || event.key === 'Tab') {
            select(items.value[activeIndex.value]);
        } else if (event.key === 'Escape') {
            close();
        } else {
            return;
        }

        // The input and its parents must not also handle the key, such as Enter to send.
        event.preventDefault();
        event.stopPropagation();
    };

    onMounted(() => {
        const input = getInput();

        input?.addEventListener('keydown', handleKeydown, true);
        input?.addEventListener('input', refresh);
        input?.addEventListener('click', refresh);
        input?.addEventListener('blur', close);
    });

    onBeforeUnmount(() => {
        const input = getInput();

        input?.removeEventListener('keydown', handleKeydown, true);
        input?.removeEventListener('input', refresh);
        input?.removeEventListener('click', refresh);
        input?.removeEventListener('blur', close);
    });

    return { isOpen, items, activeIndex, position, select };
};
