import {
    createMockRouteDefinition,
    createMockRoutesGroup,
} from '@/tests/_utils/testFactories';
import { buildResourceTree } from '@/utils/routes/resourceTree';
import { describe, expect, it } from 'vitest';

describe('buildResourceTree', () => {
    it('returns an empty tree for an empty group list', () => {
        expect(buildResourceTree([])).toEqual([]);
    });

    it('creates a single top-level folder for a single-segment resource', () => {
        const tree = buildResourceTree([createMockRoutesGroup({ resource: 'users' })]);

        expect(tree).toHaveLength(1);
        expect(tree[0].name).toBe('users');
        expect(tree[0].fullPath).toBe('users');
        expect(tree[0].routes).toHaveLength(1);
        expect(tree[0].children).toEqual([]);
    });

    it('nests folders for slash-separated resource paths', () => {
        const tree = buildResourceTree([
            createMockRoutesGroup({ resource: 'creator/auth' }),
            createMockRoutesGroup({ resource: 'creator/auth/password' }),
            createMockRoutesGroup({ resource: 'creator/profile' }),
        ]);

        expect(tree).toHaveLength(1);

        const creator = tree[0];
        expect(creator.name).toBe('creator');
        expect(creator.fullPath).toBe('creator');
        expect(creator.routes).toEqual([]);

        expect(creator.children).toHaveLength(2);

        const auth = creator.children[0];
        expect(auth.name).toBe('auth');
        expect(auth.fullPath).toBe('creator/auth');
        expect(auth.routes).toHaveLength(1);
        expect(auth.children).toHaveLength(1);
        expect(auth.children[0].fullPath).toBe('creator/auth/password');

        const profile = creator.children[1];
        expect(profile.name).toBe('profile');
        expect(profile.fullPath).toBe('creator/profile');
    });

    it('shares intermediate folder nodes between sibling groups', () => {
        const tree = buildResourceTree([
            createMockRoutesGroup({ resource: 'wallets/list' }),
            createMockRoutesGroup({ resource: 'wallets/balance' }),
        ]);

        expect(tree).toHaveLength(1);

        const wallets = tree[0];
        expect(wallets.children).toHaveLength(2);
        expect(wallets.children[0].fullPath).toBe('wallets/list');
        expect(wallets.children[1].fullPath).toBe('wallets/balance');
    });

    it('skips groups with an empty resource', () => {
        expect(buildResourceTree([createMockRoutesGroup({ resource: '' })])).toEqual([]);
        expect(buildResourceTree([createMockRoutesGroup({ resource: '///' })])).toEqual(
            [],
        );
    });

    it('keeps routes attached to their deepest folder node', () => {
        const route = createMockRouteDefinition({ method: 'POST' });
        const tree = buildResourceTree([
            createMockRoutesGroup({
                resource: 'creator/profile',
                routes: [route],
            }),
        ]);

        expect(tree[0].routes).toEqual([]);
        expect(tree[0].children[0].routes).toEqual([route]);
    });

    it('preserves input group order for siblings', () => {
        const tree = buildResourceTree([
            createMockRoutesGroup({ resource: 'b/resource' }),
            createMockRoutesGroup({ resource: 'a/resource' }),
        ]);

        expect(tree.map(node => node.name)).toEqual(['b', 'a']);
    });
});
