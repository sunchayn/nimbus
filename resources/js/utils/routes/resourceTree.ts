import type { RouteDefinition, RoutesGroup } from '@/interfaces/routes/routes';

/**
 * A tree node representing a folder in the route sidebar.
 *
 * Each node holds routes that belong directly to it, plus child nodes
 * for deeper nesting (e.g. "creator/auth/password" → creator → auth → password).
 */
export interface ResourceTreeNode {
    /** Segment name shown as the folder label (e.g. "password"). */
    name: string;

    /** Full resource path (e.g. "creator/auth/password"). */
    fullPath: string;

    /** Routes directly in this folder. */
    routes: RouteDefinition[];

    /** Child folder nodes, in first-seen order. */
    children: ResourceTreeNode[];
}

/**
 * Builds a nested tree of resource folders from a flat list of resource groups.
 *
 * Each group's `resource` is a slash-separated path (e.g. "creator/auth/password").
 * Segments become nested folders; routes attach to their group's folder node.
 *
 * Sibling folders preserve the input order so any relevance ordering from
 * search results is kept intact.
 */
export function buildResourceTree(groups: RoutesGroup[]): ResourceTreeNode[] {
    const root: ResourceTreeNode[] = [];
    const nodeIndex = new Map<string, ResourceTreeNode>();

    const getNode = (path: string[]): ResourceTreeNode => {
        const fullPath = path.join('/');
        let node = nodeIndex.get(fullPath);

        if (!node) {
            node = {
                name: path[path.length - 1] || fullPath,
                fullPath,
                routes: [],
                children: [],
            };
            nodeIndex.set(fullPath, node);

            if (path.length === 1) {
                root.push(node);
            } else {
                const parent = getNode(path.slice(0, -1));
                parent.children.push(node);
            }
        }

        return node;
    };

    for (const group of groups) {
        const segments = group.resource.split('/').filter(Boolean);

        if (segments.length === 0) {
            // Blank resources never reach the frontend (the backend maps them
            // to `<root>`), but skip defensively so we never build a nameless node.
            continue;
        }

        const node = getNode(segments);
        node.routes = group.routes;
    }

    return root;
}
