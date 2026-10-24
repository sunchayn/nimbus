import { inject, type InjectionKey, type Ref } from 'vue';

export const showingSearchResultsKey: InjectionKey<Readonly<Ref<boolean>>> =
    Symbol('showingSearchResults');

/**
 * Reads the search state provided by RouteExplorer.
 * Throws when used outside of it.
 */
export const useShowingSearchResults = (): Readonly<Ref<boolean>> => {
    const showingSearchResults = inject(showingSearchResultsKey);

    if (showingSearchResults === undefined) {
        throw new Error('showingSearchResults must be provided by RouteExplorer.');
    }

    return showingSearchResults;
};
