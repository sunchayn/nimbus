import { useActors } from '@/composables/request/useActors';
import { afterEach, describe, expect, it, vi } from 'vitest';

describe('useActors', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('stores the actors of a search', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                actors: [{ id: '1', label: 'Ada', model: 'User' }],
            }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const { actors, search } = useActors();

        await search('api', 'ada');

        expect(fetchMock.mock.calls[0][0]).toContain('/api/actors?guard=api&q=ada');
        expect(actors.value).toHaveLength(1);
    });

    it('flags a failed search and clears the actors', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 500 }));

        const { actors, hasFailed, search } = useActors();

        await search(undefined, '');

        expect(hasFailed.value).toBe(true);
        expect(actors.value).toEqual([]);
    });

    it('keeps the result of the latest search only', async () => {
        const resolvers: Array<(value: unknown) => void> = [];
        vi.stubGlobal(
            'fetch',
            vi.fn(() => new Promise(resolve => resolvers.push(resolve))),
        );

        const { actors, search } = useActors();

        const first = search(undefined, 'a');
        const second = search(undefined, 'ab');

        const body = (id: string) => ({
            ok: true,
            json: async () => ({
                actors: [{ id, label: id, model: 'User' }],
            }),
        });

        resolvers[1](body('new'));
        await second;
        resolvers[0](body('old'));
        await first;

        expect(actors.value[0].id).toBe('new');
    });

    it('loads the list without a search once per guard', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ actors: [{ id: '1', label: 'Ada', model: 'User' }] }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const first = useActors();
        await first.search('cached', '');

        const second = useActors();
        await second.search('cached', '');

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(second.actors.value).toHaveLength(1);

        await second.search('cached', 'ada');

        expect(fetchMock).toHaveBeenCalledTimes(2);
    });
});
