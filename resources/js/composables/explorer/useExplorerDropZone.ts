import { FOLDER_DRAG_TYPE, REQUEST_DRAG_TYPE } from '@/config/workspace';
import type { ExplorerDropPosition } from '@/interfaces/explorer';
import type { ExplorerDrag } from './useExplorerDrag';

export interface ExplorerDropZone {
    onDragOver: (event: DragEvent) => void;
    onDragLeave: (event: DragEvent) => void;
    onDrop: (event: DragEvent) => void;
    onDragEnd: () => void;
}

export interface ExplorerDropHandlers {
    reorderFolder: (path: string, target: string, position: ExplorerDropPosition) => void;
    reorderItem: (id: string, target: string, position: ExplorerDropPosition) => void;
    moveItem: (id: string, folder: string) => void;
}

const FOLDER_SELECTOR = '[data-explorer-folder]';
const ITEM_SELECTOR = '[data-explorer-item]';
const HEADER_SELECTOR = '[data-explorer-folder-header]';

const parentOf = (path: string) =>
    path.includes('/') ? path.slice(0, path.lastIndexOf('/')) : '';

const folderUnder = (event: DragEvent) =>
    (event.target as HTMLElement).closest<HTMLElement>(FOLDER_SELECTOR)?.dataset.path ??
    '';

const isPointerInside = (event: DragEvent) => {
    const root = event.currentTarget as HTMLElement;
    const next = event.relatedTarget as Node | null;

    if (next) {
        return root.contains(next);
    }

    // Some browsers give no related target, so the pointer position decides.
    const box = root.getBoundingClientRect();

    return (
        event.clientX >= box.left &&
        event.clientX <= box.right &&
        event.clientY >= box.top &&
        event.clientY <= box.bottom
    );
};

/**
 * Builds the drag event handlers of the root of an explorer tree.
 * The root handles every drag event, and the folder or item under the pointer is the drop target.
 */
export const useExplorerDropZone = (
    drag: ExplorerDrag,
    handlers: ExplorerDropHandlers,
): ExplorerDropZone => {
    /**
     * Finds the sibling of the dragged folder under the pointer and the side of it the folder would land on.
     */
    const siblingUnder = (event: DragEvent) => {
        const dragged = drag.folder.value;
        const over = folderUnder(event);

        if (dragged === null) {
            return null;
        }

        const prefix = parentOf(dragged) === '' ? '' : `${parentOf(dragged)}/`;

        if (over === dragged || !over.startsWith(prefix) || over === '') {
            return null;
        }

        const path = prefix + over.slice(prefix.length).split('/')[0];

        if (path === dragged) {
            return null;
        }

        const block = [
            ...(event.currentTarget as HTMLElement).querySelectorAll<HTMLElement>(
                FOLDER_SELECTOR,
            ),
        ].find(element => element.dataset.path === path);

        const header = block
            ?.querySelector<HTMLElement>(HEADER_SELECTOR)
            ?.getBoundingClientRect();

        if (!header) {
            return null;
        }

        return {
            path,
            position: event.clientY < header.top + header.height / 2 ? 'before' : 'after',
        } as const;
    };

    /**
     * Finds the item under the pointer, and the side of it the dragged item would land on.
     * The item can come from another folder, in which case it moves there.
     */
    const itemSiblingUnder = (event: DragEvent) => {
        const dragged = drag.item.value;
        const item = (event.target as HTMLElement).closest<HTMLElement>(ITEM_SELECTOR);

        if (!dragged || !item?.dataset.id || item.dataset.id === dragged) {
            return null;
        }

        const box = item.getBoundingClientRect();

        return {
            id: item.dataset.id,
            position: event.clientY < box.top + box.height / 2 ? 'before' : 'after',
        } as const;
    };

    const onDragEnd = () => {
        drag.dropFolder.value = null;
        drag.folder.value = null;
        drag.target.value = null;
        drag.item.value = null;
        drag.itemTarget.value = null;
    };

    const onDragOver = (event: DragEvent) => {
        if (event.dataTransfer?.types.includes(FOLDER_DRAG_TYPE)) {
            const sibling = siblingUnder(event);

            drag.target.value = sibling;

            if (sibling) {
                event.preventDefault();
            }

            return;
        }

        if (event.dataTransfer?.types.includes(REQUEST_DRAG_TYPE)) {
            const sibling = itemSiblingUnder(event);

            event.preventDefault();
            drag.itemTarget.value = sibling;
            drag.dropFolder.value = sibling ? null : folderUnder(event);
        }
    };

    const onDragLeave = (event: DragEvent) => {
        if (!isPointerInside(event)) {
            drag.dropFolder.value = null;
            drag.target.value = null;
            drag.itemTarget.value = null;
        }
    };

    const onDrop = (event: DragEvent) => {
        const moved = drag.folder.value;
        const target = siblingUnder(event) ?? drag.target.value;

        if (event.dataTransfer?.types.includes(FOLDER_DRAG_TYPE) && moved && target) {
            event.preventDefault();
            handlers.reorderFolder(moved, target.path, target.position);
            onDragEnd();

            return;
        }

        const dragged = drag.item.value;
        // The target is read again, so a drop never relies on an outdated dragover.
        const sibling = itemSiblingUnder(event) ?? drag.itemTarget.value;

        if (event.dataTransfer?.types.includes(REQUEST_DRAG_TYPE) && dragged && sibling) {
            event.preventDefault();
            handlers.reorderItem(dragged, sibling.id, sibling.position);
            onDragEnd();

            return;
        }

        const id = event.dataTransfer?.getData(REQUEST_DRAG_TYPE);

        if (id) {
            event.preventDefault();
            handlers.moveItem(id, folderUnder(event));
        }

        drag.dropFolder.value = null;
    };

    return { onDragOver, onDragLeave, onDrop, onDragEnd };
};
