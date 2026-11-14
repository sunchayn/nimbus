import type { WorkspaceSummary } from '@/interfaces/workspace';
import { inject, provide, shallowRef, type InjectionKey, type ShallowRef } from 'vue';

type DropPosition = 'before' | 'after';

export interface FolderDrag {
    /** The folder being dragged. */
    folder: ShallowRef<string | null>;
    /** The sibling folder the dragged folder would land next to. */
    target: ShallowRef<{ path: string; position: DropPosition } | null>;
    /** The request being dragged. */
    request: ShallowRef<WorkspaceSummary | null>;
    /** The request the dragged request would land next to. */
    requestTarget: ShallowRef<{ id: string; position: DropPosition } | null>;
}

const FOLDER_DRAG: InjectionKey<FolderDrag> = Symbol('workspace-folder-drag');

/**
 * Shares the drag state of the workspace tree.
 * The first folder to call it, the root, owns the state and every folder below it reads the same one.
 */
export const useFolderDrag = (): FolderDrag => {
    const shared = inject(FOLDER_DRAG, null);

    if (shared) {
        return shared;
    }

    const drag: FolderDrag = {
        folder: shallowRef(null),
        target: shallowRef(null),
        request: shallowRef(null),
        requestTarget: shallowRef(null),
    };

    provide(FOLDER_DRAG, drag);

    return drag;
};
