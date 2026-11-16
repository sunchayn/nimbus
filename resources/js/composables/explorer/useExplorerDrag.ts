import type { ExplorerDropPosition } from '@/interfaces/explorer';
import { shallowRef, type ShallowRef } from 'vue';

export interface ExplorerDrag {
    /** The path of the folder being dragged. */
    folder: ShallowRef<string | null>;
    /** The sibling folder the dragged folder would land next to. */
    target: ShallowRef<{ path: string; position: ExplorerDropPosition } | null>;
    /** The key of the item being dragged. */
    item: ShallowRef<string | null>;
    /** The item the dragged item would land next to. */
    itemTarget: ShallowRef<{ id: string; position: ExplorerDropPosition } | null>;
    /** The path of the folder a dragged item would be dropped into. */
    dropFolder: ShallowRef<string | null>;
}

/**
 * Creates the drag state of one explorer tree, shared by every folder and item of it.
 */
export const createExplorerDrag = (): ExplorerDrag => ({
    folder: shallowRef(null),
    target: shallowRef(null),
    item: shallowRef(null),
    itemTarget: shallowRef(null),
    dropFolder: shallowRef(null),
});
