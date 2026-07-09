import {
    type EnvironmentCollection,
    useConfigStore,
    useEnvironmentVariablesStore,
} from '@/stores';
import axios from 'axios';
import { watch } from 'vue';

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
 * On boot it adopts the shared set from the server (or seeds the server from
 * local collections the first time), then pushes any subsequent change back,
 * debounced. If the endpoint is unavailable (e.g. Nimbus disabled, offline)
 * it silently falls back to the local-only behaviour.
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
        } catch {
            // Keep local edits; the next change (or reload) will retry.
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

            if (remote.length > 0) {
                adopt(remote);
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
