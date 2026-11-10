import { useLookupValuesStore } from '@/stores/request/useLookupValuesStore';
import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('axios');

const request = (method = 'POST') =>
    ({
        method,
        routeDefinition: { method, endpoint: 'api/posts' },
        schema: {
            shape: {
                type: 'object',
                properties: {
                    author_id: {
                        type: 'integer',
                        lookup: { table: 'users', column: 'id' },
                    },
                },
            },
        },
    }) as never;

describe('useLookupValuesStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('loads the missing pairs in one call and picks from them', async () => {
        vi.mocked(axios.post).mockResolvedValue({
            data: { values: { 'users.id': [7] } },
        });
        const store = useLookupValuesStore();

        await store.prefetch(request());
        await store.prefetch(request());

        expect(axios.post).toHaveBeenCalledTimes(1);
        expect(vi.mocked(axios.post).mock.calls[0][1]).toEqual({
            method: 'POST',
            endpoint: 'api/posts',
            pairs: [{ table: 'users', column: 'id' }],
        });
        expect(store.pick({ table: 'users', column: 'id' })).toBe(7);
    });

    it('picks nothing while loading, when empty, or after a failure', async () => {
        vi.mocked(axios.post).mockRejectedValue(new Error('down'));
        const store = useLookupValuesStore();
        const pending = store.prefetch(request());

        expect(store.pick({ table: 'users', column: 'id' })).toBeUndefined();

        await pending;

        expect(store.lists['users.id'].status).toBe('failed');
        expect(store.pick({ table: 'users', column: 'id' })).toBeUndefined();
    });

    it('loads again after a write request but not after a read', async () => {
        vi.mocked(axios.post).mockResolvedValue({
            data: { values: { 'users.id': [1] } },
        });
        const store = useLookupValuesStore();

        await store.prefetch(request());
        await store.refreshAfter(request('GET'));

        expect(axios.post).toHaveBeenCalledTimes(1);

        await store.refreshAfter(request('POST'));

        expect(axios.post).toHaveBeenCalledTimes(2);
    });
});
