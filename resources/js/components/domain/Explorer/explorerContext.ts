import type { AppContextMenuItem } from '@/components/base/context-menu';
import type { ExplorerDrag } from '@/composables/explorer/useExplorerDrag';
import type { ExplorerDropPosition, ExplorerNode } from '@/interfaces/explorer';
import { inject, type FunctionalComponent, type InjectionKey, type Slots } from 'vue';

export interface ExplorerOptions {
    /** The tree to show, where the root folder has an empty path. */
    node: ExplorerNode<unknown>;
    /** Gives the stable key of an item. */
    itemKey: (item: unknown) => string;
    /** Gives the text of an item, which is the starting value of its rename input. */
    itemLabel?: (item: unknown) => string;
    isOpen: (path: string) => boolean;
    toggle: (path: string) => void;
    /** Lets items and folders be dragged to reorder them or to move them into a folder. */
    draggable?: boolean;
    /** Lets items and folders be renamed from the context menu and on double click for items. */
    renamable?: boolean;
    /** The context menu groups of an item. */
    itemMenu?: (item: unknown) => AppContextMenuItem[][];
    /** The context menu groups of a folder. */
    folderMenu?: (node: ExplorerNode<unknown>) => AppContextMenuItem[][];
    /** Prefix of the test ids of the tree. */
    testId?: string;
}

export interface ExplorerContext {
    options: ExplorerOptions;
    slots: Slots;
    drag: ExplorerDrag;
    emit: {
        renameItem: (item: unknown, name: string) => void;
        renameFolder: (node: ExplorerNode<unknown>, name: string) => void;
        moveItem: (id: string, folder: string) => void;
        reorderItem: (id: string, target: string, position: ExplorerDropPosition) => void;
        reorderFolder: (
            path: string,
            target: string,
            position: ExplorerDropPosition,
        ) => void;
    };
}

export const EXPLORER_CONTEXT: InjectionKey<ExplorerContext> = Symbol('explorer-context');

export const useExplorerContext = (): ExplorerContext => {
    const context = inject(EXPLORER_CONTEXT);

    if (!context) {
        throw new Error('An explorer part must be used inside ExplorerTree.');
    }

    return context;
};

/**
 * Renders a slot of the tree, so every level of the recursion shows the same content.
 */
export const ExplorerSlot: FunctionalComponent<{
    name: string;
    scope?: object;
}> = props => {
    const slot = useExplorerContext().slots[props.name];

    return slot ? slot(props.scope ?? {}) : null;
};
ExplorerSlot.props = ['name', 'scope'];
