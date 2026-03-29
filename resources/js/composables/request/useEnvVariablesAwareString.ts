import { type StringSegment } from '@/interfaces/common/env-vars';
import { useEnvironmentVariablesStore } from '@/stores/core/useEnvironmentVariablesStore';
import { computed, type ComputedRef, type Ref, ref, watch } from 'vue';

export interface EnvVariablesAwareStringResult {
    raw: Ref<string>;
    resolved: ComputedRef<string>;
    segments: ComputedRef<StringSegment[]>;
}

/**
 * Composable that manages the resolution of environment variables within a string.
 *
 * @param source - The reactive string source to resolve.
 * @returns Reactive resolution state and utilities.
 */
export function useEnvVariablesAwareString(
    source: Ref<string>,
): EnvVariablesAwareStringResult {
    const environmentVariablesStore = useEnvironmentVariablesStore();

    /*
     * State.
     */

    const rawValue: Ref<string> = ref('');

    /*
     * Computed.
     */

    const fullyResolvedString = computed(() => {
        return environmentVariablesStore.resolve(rawValue.value);
    });

    const segments = computed(() => {
        return environmentVariablesStore.getSegments(rawValue.value);
    });

    /*
     * Watchers.
     */

    /**
     * Sync the local raw state with the source.
     */
    watch(
        source,
        (newSource: string) => {
            if (newSource !== rawValue.value) {
                rawValue.value = newSource;
            }
        },
        { immediate: true },
    );

    /**
     * Update the source when the local raw state changes.
     */
    watch(rawValue, newRaw => {
        if (newRaw !== source.value) {
            source.value = newRaw;
        }
    });

    return {
        raw: rawValue,
        resolved: fullyResolvedString,
        segments,
    };
}
