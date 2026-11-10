import { shallowRef, type ShallowRef } from 'vue';

export interface Actor {
    id: string;
    label: string;
    model: string;
}

interface ActorsResponse {
    guard: string;
    guards: string[];
    actors: Actor[];
}

export interface UseActorsResult {
    actors: ShallowRef<Actor[]>;
    guards: ShallowRef<string[]>;
    defaultGuard: ShallowRef<string>;
    isLoading: ShallowRef<boolean>;
    hasFailed: ShallowRef<boolean>;
    search: (guard: string | undefined, query: string) => Promise<void>;
}

/**
 * Searches the users of an authentication guard through the actors endpoint.
 *
 * A newer search replaces the result of an older one that is still in flight.
 */
export const useActors = (): UseActorsResult => {
    const actors = shallowRef<Actor[]>([]);
    const guards = shallowRef<string[]>([]);
    const defaultGuard = shallowRef('');
    const isLoading = shallowRef(false);
    const hasFailed = shallowRef(false);

    let latestSearch = 0;

    const search = async (guard: string | undefined, query: string): Promise<void> => {
        const current = ++latestSearch;
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

            if (current !== latestSearch) {
                return;
            }

            actors.value = data.actors;
            guards.value = data.guards;
            defaultGuard.value = data.guard;
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

    return { actors, guards, defaultGuard, isLoading, hasFailed, search };
};
