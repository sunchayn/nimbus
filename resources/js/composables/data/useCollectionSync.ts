import {
    type EnvironmentCollection,
    useConfigStore,
    useEnvironmentVariablesStore,
} from '@/stores';
import axios from 'axios';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

/**
 * Delay (ms) before a collection change is pushed to the backend. Collapses
 * rapid edits (typing a variable value) into a single request.
 */
const SYNC_DEBOUNCE_MS = 600;

interface CollectionsResponse {
    collections: EnvironmentCollection[];
}

/**
 * Keeps the environment collections in sync with the team's shared backend
 * store so collections travel with the repo instead of living only in one
 * browser's localStorage.
 *
 * On boot the server is authoritative for shared collections, but collections
 * that only exist locally (e.g. created before syncing) are merged up rather
 * than dropped, then the union is persisted. Subsequent changes are pushed
 * back, debounced. If the endpoint is unreachable (Nimbus disabled, offline) it
 * silently falls back to local-only; if a push is rejected by the server it
 * surfaces a toast so the user knows their changes were not saved.
 */
export function useCollectionSync(): { init: () => Promise<void> } {
    const configStore = useConfigStore();
    const environmentStore = useEnvironmentVariablesStore();

    const endpoint = configStore.appBasePath + '/api/collections';

    let pushTimer: ReturnType<typeof setTimeout> | null = null;

    const push = async (): Promise<void> => {
        try {
            await axios.put<CollectionsResponse>(endpoint, {
                collections: environmentStore.collections,
            });
        } catch (error) {
            // A rejected request (server reachable, e.g. a 4xx) means the change
            // was NOT saved: surface it so the user can act. A pure network
            // error keeps the local-only fallback silent.
            const wasRejected = !!(error as { response?: unknown } | null)?.response;

            if (wasRejected) {
                toast.error('Collection sync failed', {
                    description:
                        'Your collection changes were not saved to the team. Fix the issue and edit again to retry.',
                });
            }
        }
    };

    const schedulePush = (): void => {
        if (pushTimer !== null) {
            clearTimeout(pushTimer);
        }

        pushTimer = setTimeout(() => {
            void push();
        }, SYNC_DEBOUNCE_MS);
    };

    const adopt = (collections: EnvironmentCollection[]): void => {
        environmentStore.collections = collections;

        const activeStillExists = collections.some(
            collection => collection.id === environmentStore.activeCollectionId,
        );

        if (!activeStillExists) {
            environmentStore.select(collections[0]?.id ?? null);
        }
    };

    const watchForChanges = (): void => {
        watch(
            () => environmentStore.collections,
            () => schedulePush(),
            { deep: true },
        );
    };

    const init = async (): Promise<void> => {
        try {
            const { data } = await axios.get<CollectionsResponse>(endpoint);
            const remote = data.collections ?? [];
            const remoteIds = new Set(remote.map(collection => collection.id));
            const localOnly = environmentStore.collections.filter(
                collection => !remoteIds.has(collection.id),
            );

            if (remote.length > 0) {
                // Server wins for shared collections, but never drop collections
                // that only exist locally: merge them up and persist the union.
                adopt([...remote, ...localOnly]);

                if (localOnly.length > 0) {
                    await push();
                }
            } else if (environmentStore.collections.length > 0) {
                // Server has nothing yet: seed it from this browser's collections.
                await push();
            }
        } catch {
            // Endpoint unavailable: fall back to local-only collections.
        }

        // Attach the watcher only after the initial hydrate so adopting the
        // shared set never echoes straight back as a write.
        watchForChanges();
    };

    return { init };
}
