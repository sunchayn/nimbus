import { shallowRef, type ShallowRef } from 'vue';

export interface Actor {
    id: string;
    label: string;
    model: string;
}

interface ActorsResponse {
    actors: Actor[];
}

export interface UseActorsResult {
    actors: ShallowRef<Actor[]>;
    isLoading: ShallowRef<boolean>;
    hasFailed: ShallowRef<boolean>;
    search: (guard: string | undefined, query: string) => Promise<void>;
}

// The list without a search is loaded once per guard and reused.
const defaultLists = new Map<string, Actor[]>();

export const clearActorsCache = (): void => defaultLists.clear();

/**
 * Searches the users of an authentication guard through the actors endpoint.
 * The guards themselves are provided on page load.
 *
 * A newer search replaces the result of an older one that is still in flight.
 */
export const useActors = (): UseActorsResult => {
    const actors = shallowRef<Actor[]>([]);
    const isLoading = shallowRef(false);
    const hasFailed = shallowRef(false);

    let latestSearch = 0;

    const search = async (guard: string | undefined, query: string): Promise<void> => {
        const current = ++latestSearch;
        const cacheKey = guard ?? '';
        const cached = query === '' ? defaultLists.get(cacheKey) : undefined;

        if (cached) {
            actors.value = cached;
            hasFailed.value = false;
            isLoading.value = false;

            return;
        }

        const parameters = new URLSearchParams();

        if (guard) {
            parameters.set('guard', guard);
        }

        if (query !== '') {
            parameters.set('q', query);
        }

        isLoading.value = true;

        try {
            const response = await fetch(
                `${window.Nimbus?.basePath ?? ''}/api/actors?${parameters.toString()}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) {
                throw new Error(`Actors request failed with ${response.status}.`);
            }

            const data = (await response.json()) as ActorsResponse;

            if (query === '') {
                defaultLists.set(cacheKey, data.actors);
            }

            if (current !== latestSearch) {
                return;
            }

            actors.value = data.actors;
            hasFailed.value = false;
        } catch {
            if (current === latestSearch) {
                actors.value = [];
                hasFailed.value = true;
            }
        } finally {
            if (current === latestSearch) {
                isLoading.value = false;
            }
        }
    };

    return { actors, isLoading, hasFailed, search };
};
