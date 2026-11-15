/**
 * A folder of an explorer tree, with the folders and the items directly inside it.
 * The root folder has an empty path.
 */
export interface ExplorerNode<T> {
    name: string;
    path: string;
    folders: ExplorerNode<T>[];
    items: T[];
}

export type ExplorerDropPosition = 'before' | 'after';
