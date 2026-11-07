import { useConfigStore } from '@/stores';
import axios from 'axios';
import { shallowRef, type ShallowRef } from 'vue';

/**
 * Sends a toolbox form to the backend and keeps the loading, result, and error state.
 */
export const useToolRequest = <T = Record<string, unknown>>(
    path: string,
): {
    loading: ShallowRef<boolean>;
    result: ShallowRef<T | null>;
    error: ShallowRef<string | null>;
    run: (payload: Record<string, unknown>) => Promise<void>;
} => {
    const configStore = useConfigStore();

    const loading = shallowRef(false);
    const result = shallowRef<T | null>(null);
    const error = shallowRef<string | null>(null);

    const run = async (payload: Record<string, unknown>) => {
        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const { data } = await axios.post<T>(
                `${configStore.appBasePath}/api/toolbox/${path}`,
                payload,
            );

            result.value = data;
        } catch (caught) {
            const message = axios.isAxiosError(caught)
                ? (caught.response?.data as { message?: string } | undefined)?.message
                : undefined;

            error.value = message ?? 'The tool could not run.';
        } finally {
            loading.value = false;
        }
    };

    return { loading, result, error, run };
};
