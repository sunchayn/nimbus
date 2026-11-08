import type { PendingRequest } from '@/interfaces/http';
import { useConfigStore, useEnvironmentVariablesStore, useTabsStore } from '@/stores';
import { collectLookups, lookupKey, type Lookup } from '@/utils/payload/lookups';
import axios from 'axios';
import { defineStore } from 'pinia';
import { reactive, watch } from 'vue';

type LookupStatus = 'loading' | 'ready' | 'failed';

interface LookupList {
    status: LookupStatus;
    values: Array<string | number>;
}

const READ_METHODS = ['GET', 'HEAD', 'OPTIONS'];

/**
 * Existing values for the exists rules of the active request.
 *
 * The lists live in memory only. Autofill never waits for them, it falls back to the
 * generator value while a list is loading, empty, or failed.
 */
export const useLookupValuesStore = defineStore('lookupValues', () => {
    const tabsStore = useTabsStore();
    const configStore = useConfigStore();
    const environmentVariablesStore = useEnvironmentVariablesStore();

    const lists = reactive<Record<string, LookupList>>({});

    const valuesFor = (lookup: Lookup): Array<string | number> =>
        lists[lookupKey(lookup)]?.status === 'ready'
            ? lists[lookupKey(lookup)].values
            : [];

    /**
     * A random value of a ready list, or undefined when the caller should generate one.
     */
    const pick = (lookup: Lookup): string | number | undefined => {
        const values = valuesFor(lookup);

        return values.length
            ? values[Math.floor(Math.random() * values.length)]
            : undefined;
    };

    /**
     * Loads the lists the request needs and does not have yet, in one call.
     */
    const prefetch = async (request: PendingRequest | null | undefined) => {
        const missing = collectLookups(request?.schema?.shape).filter(
            lookup => lists[lookupKey(lookup)] === undefined,
        );

        if (!request || missing.length === 0) {
            return;
        }

        missing.forEach(lookup => {
            lists[lookupKey(lookup)] = { status: 'loading', values: [] };
        });

        try {
            const response = await axios.post<{
                values: Record<string, Array<string | number>>;
            }>(`${configStore.appBasePath}/api/magic/lookup-values`, {
                method: request.routeDefinition.method,
                endpoint: request.routeDefinition.endpoint,
                pairs: missing,
            });

            missing.forEach(lookup => {
                lists[lookupKey(lookup)] = {
                    status: 'ready',
                    values: response.data.values?.[lookupKey(lookup)] ?? [],
                };
            });
        } catch {
            missing.forEach(lookup => {
                lists[lookupKey(lookup)] = { status: 'failed', values: [] };
            });
        }
    };

    const reset = () => {
        Object.keys(lists).forEach(key => delete lists[key]);
    };

    const refresh = () => {
        reset();

        return prefetch(tabsStore.pendingRequestData);
    };

    /**
     * Writes can change the rows, so the lists are loaded again after a write request.
     */
    const refreshAfter = (request: PendingRequest) => {
        if (!READ_METHODS.includes(request.method.toUpperCase())) {
            reset();

            return prefetch(request);
        }
    };

    watch(
        () => tabsStore.pendingRequestData,
        request => prefetch(request),
        { immediate: true },
    );

    watch(() => environmentVariablesStore.activeEnvironment.id, refresh);

    return { lists, valuesFor, pick, prefetch, refresh, refreshAfter, reset };
});
