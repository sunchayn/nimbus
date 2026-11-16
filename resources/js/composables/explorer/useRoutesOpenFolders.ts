import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';

/**
 * Remembers which folders of the routes explorer are open.
 */
export const useRoutesOpenFolders = (): {
    isOpen: (path: string) => boolean;
    toggle: (path: string) => void;
} => {
    const open = useStorage<string[]>(
        singletonPersistenceKey('routes-explorer-open-folders'),
        [],
    );

    const isOpen = (path: string) => open.value.includes(path);

    const toggle = (path: string) => {
        open.value = isOpen(path)
            ? open.value.filter(item => item !== path)
            : [...open.value, path];
    };

    return { isOpen, toggle };
};
