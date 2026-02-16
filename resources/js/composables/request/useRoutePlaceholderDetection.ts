import { computed, type ComputedRef } from 'vue';

/**
 * Composable for detecting dynamic placeholders in a route endpoint URL.
 */
export function useRoutePlaceholderDetection(endpoint: ComputedRef<string>): {
    placeholders: ComputedRef<string[]>;
    hasPlaceholders: ComputedRef<boolean>;
} {
    const placeholders = computed(() => {
        const url = endpoint.value;

        if (!url) {
            return [];
        }

        const matches = Array.from(url.matchAll(/\{([a-zA-Z0-9_-]+)\}/g));

        return matches.map(match => match[1]);
    });

    const hasPlaceholders = computed(() => placeholders.value.length > 0);

    return {
        placeholders,
        hasPlaceholders,
    };
}
