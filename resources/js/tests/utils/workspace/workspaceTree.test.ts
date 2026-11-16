import type { WorkspaceSummary } from '@/interfaces/workspace';
import {
    buildWorkspaceTree,
    reorderFolderPaths,
    reorderRequestIds,
} from '@/utils/workspace';
import { describe, expect, it } from 'vitest';

const summary = (id: string, folder: string): WorkspaceSummary => ({
    id,
    name: id,
    method: 'GET',
    endpoint: '/',
    route: null,
    file: `requests/${folder}/${id}.request.json`,
    folder,
    hash: 'hash',
});

describe('buildWorkspaceTree', () => {
    it('puts root requests on the root node', () => {
        const tree = buildWorkspaceTree([], [summary('a', '')]);

        expect(tree.items.map(request => request.id)).toEqual(['a']);
        expect(tree.folders).toEqual([]);
    });

    it('nests folders and keeps empty ones', () => {
        const tree = buildWorkspaceTree(['users', 'users/admin', 'empty'], []);

        expect(tree.folders.map(folder => folder.path)).toEqual(['users', 'empty']);
        expect(tree.folders[0].folders[0]).toMatchObject({
            name: 'admin',
            path: 'users/admin',
        });
    });

    it('keeps the order of the folders it receives', () => {
        const tree = buildWorkspaceTree(['zebra', 'apple', 'apple/inner'], []);

        expect(tree.folders.map(folder => folder.path)).toEqual(['zebra', 'apple']);
    });

    it('creates the parents of a folder only known from a request', () => {
        const tree = buildWorkspaceTree([], [summary('a', 'users/admin')]);

        expect(tree.folders[0].path).toBe('users');
        expect(tree.folders[0].folders[0].items[0].id).toBe('a');
    });
});

describe('reorderFolderPaths', () => {
    const tree = buildWorkspaceTree(['a', 'a/one', 'a/two', 'b', 'c'], []);

    it('moves a folder before a sibling and keeps its content with it', () => {
        expect(reorderFolderPaths(tree, 'c', 'a', 'before')).toEqual([
            'c',
            'a',
            'a/one',
            'a/two',
            'b',
        ]);
    });

    it('moves a folder after a sibling', () => {
        expect(reorderFolderPaths(tree, 'a', 'b', 'after')).toEqual([
            'b',
            'a',
            'a/one',
            'a/two',
            'c',
        ]);
    });

    it('reorders inside a nested folder', () => {
        expect(reorderFolderPaths(tree, 'a/two', 'a/one', 'before')).toEqual([
            'a',
            'a/two',
            'a/one',
            'b',
            'c',
        ]);
    });

    it('ignores a move to another parent', () => {
        expect(reorderFolderPaths(tree, 'a/one', 'b', 'before')).toEqual([
            'a',
            'a/one',
            'a/two',
            'b',
            'c',
        ]);
    });
});

describe('reorderRequestIds', () => {
    const requests = [
        summary('a', 'users'),
        summary('b', 'users'),
        summary('c', 'users'),
        summary('d', ''),
    ];

    it('moves a request before a sibling', () => {
        expect(reorderRequestIds(requests, 'c', 'a', 'before')).toEqual({
            folder: 'users',
            ids: ['c', 'a', 'b'],
        });
    });

    it('moves a request after a sibling', () => {
        expect(reorderRequestIds(requests, 'a', 'b', 'after')).toEqual({
            folder: 'users',
            ids: ['b', 'a', 'c'],
        });
    });

    it('ignores a sibling in another folder and a move onto itself', () => {
        expect(reorderRequestIds(requests, 'a', 'd', 'before')).toBeNull();
        expect(reorderRequestIds(requests, 'a', 'a', 'before')).toBeNull();
        expect(reorderRequestIds(requests, 'a', 'missing', 'before')).toBeNull();
    });
});
