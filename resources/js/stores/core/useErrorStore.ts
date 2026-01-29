import type { GlobalException } from '@/interfaces';
import { parseGlobalException } from '@/utils/exceptions';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useErrorStore = defineStore('errors', () => {
    /*
     * State.
     */

    const globalError = ref<GlobalException | null>(null);

    /*
     * Actions.
     */

    const initializeGlobalErrors = () => {
        globalError.value = parseGlobalException(
            (window.Nimbus?.globalException as string) ?? null,
        );
    };

    const clearGlobalError = () => {
        globalError.value = null;
    };

    /*
     * Computed.
     */

    const hasGlobalError = computed(() => globalError.value !== null);

    return {
        // State
        globalError,

        // Actions
        initializeGlobalErrors,
        clearGlobalError,

        // Computed
        hasGlobalError,
    };
});
