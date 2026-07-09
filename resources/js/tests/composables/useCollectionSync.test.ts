import { useCollectionSync } from '@/composables/data/useCollectionSync';
import { useEnvironmentVariablesStore } from '@/stores';
import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import type { Mocked } from 'vitest';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

vi.mock('axios');
const mockedAxios = axios as Mocked<typeof axios>;

const remoteCollection = { id: 'remote-1', name: 'Remote', variables: [] };

describe('useCollectionSync', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        setActivePinia(createPinia());
        mockedAxios.put.mockResolvedValue({ data: { collections: [] } });
    });

    it('adopts the shared collections returned by the server', async () => {
        mockedAxios.get.mockResolvedValue({ data: { collections: [remoteCollection] } });
        const store = useEnvironmentVariablesStore();

        await useCollectionSync().init();

        expect(store.collections).toEqual([remoteCollection]);
        expect(store.activeCollectionId).toBe('remote-1');
        expect(mockedAxios.put).not.toHaveBeenCalled();
    });

    it('seeds the server from local collections when the server is empty', async () => {
        mockedAxios.get.mockResolvedValue({ data: { collections: [] } });
        const store = useEnvironmentVariablesStore();
        store.addCollection();

        await useCollectionSync().init();

        expect(mockedAxios.put).toHaveBeenCalledTimes(1);
        expect(mockedAxios.put.mock.calls[0][1]).toEqual({
            collections: store.collections,
        });
    });

    it('pushes changes to the server after the debounce window', async () => {
        vi.useFakeTimers();
        mockedAxios.get.mockResolvedValue({ data: { collections: [] } });
        const store = useEnvironmentVariablesStore();

        await useCollectionSync().init();
        expect(mockedAxios.put).not.toHaveBeenCalled();

        store.addCollection();
        await nextTick();
        await vi.advanceTimersByTimeAsync(600);

        expect(mockedAxios.put).toHaveBeenCalledTimes(1);
        vi.useRealTimers();
    });

    it('falls back to local collections when the endpoint is unavailable', async () => {
        mockedAxios.get.mockRejectedValue(new Error('unavailable'));
        const store = useEnvironmentVariablesStore();
        store.addCollection();
        const local = JSON.parse(JSON.stringify(store.collections));

        await useCollectionSync().init();

        expect(store.collections).toEqual(local);
        expect(mockedAxios.put).not.toHaveBeenCalled();
    });
});
