import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useMediaQuery, useStorage } from '@vueuse/core';
import { type Ref, shallowRef } from 'vue';

// The state is shared, so the top bar toggle and the main page stay in sync.
const isExplorerOpen = useStorage(singletonPersistenceKey('explorer-open'), true);

// On small screens the explorer is a slideover that starts closed on every load.
const isExplorerDrawerOpen = shallowRef(false);
const isCompact = useMediaQuery('(max-width: 767px)');

export const useExplorerVisibility = (): {
    isExplorerOpen: Ref<boolean>;
    isExplorerDrawerOpen: Ref<boolean>;
    isCompact: Ref<boolean>;
    toggleExplorer: () => void;
} => {
    const toggleExplorer = () => {
        if (isCompact.value) {
            isExplorerDrawerOpen.value = !isExplorerDrawerOpen.value;
        } else {
            isExplorerOpen.value = !isExplorerOpen.value;
        }
    };

    return { isExplorerOpen, isExplorerDrawerOpen, isCompact, toggleExplorer };
};
